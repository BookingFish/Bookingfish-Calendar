<?php
/**
 * BFC_Pages — creates, updates and tracks WordPress pages that embed
 * BookingFish calendar and gift-certificate widgets.
 *
 * Stored data structure (option: bfc_created_pages) — array of entries:
 * [
 *   'page_id'      => int,
 *   'title'        => string,
 *   'url'          => string,
 *   'type'         => 'calendar' | 'certificate',
 *   'sub_type'     => 'all_boats' | 'boat:BOAT_NAME' | 'cert:TEMPLATE_ID',
 *   'embed_key'    => string,
 *   'vendor_email' => string,   ← added in 1.2.6 to scope pages per vendor
 *   'created_at'   => string,
 * ]
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BFC_Pages {

    // =========================================================================
    // Create
    // =========================================================================

    /**
     * Create a new WordPress page and embed the BookingFish widget.
     *
     * @param string $title
     * @param string $embed_code
     * @param string $type     'calendar' | 'certificate'
     * @param string $sub_type 'all_boats' | 'boat:BOAT_NAME' | 'cert:TEMPLATE_ID'
     * @return array|WP_Error  ['page_id', 'url'] on success.
     */
    public function create_page( $title, $embed_code, $type, $sub_type ) {
        bfc_log( "BFC_Pages::create_page — title='{$title}' type='{$type}' sub_type='{$sub_type}'" );

        $existing = $this->find_page_by_sub_type( $sub_type );
        if ( $existing ) {
            bfc_log( "BFC_Pages::create_page — page for sub_type '{$sub_type}' already exists (ID {$existing['page_id']}).", 'WARNING' );
            // translators: %s: URL of the existing WordPress page that already embeds this widget.
            $message = sprintf( __( 'A page for this embed already exists: %s', 'bookingfish-calendar' ), $existing['url'] );
            return new WP_Error( 'bfc_page_exists', $message );
        }

        $post_id = wp_insert_post( array(
            'post_title'   => sanitize_text_field( $title ),
            'post_content' => $embed_code,
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ), true );

        if ( is_wp_error( $post_id ) ) {
            bfc_log( 'BFC_Pages::create_page — wp_insert_post failed: ' . $post_id->get_error_message(), 'ERROR' );
            return $post_id;
        }

        $url = get_permalink( $post_id );
        bfc_log( "BFC_Pages::create_page — page created ID={$post_id} URL={$url}" );

        $entry = array(
            'page_id'      => $post_id,
            'title'        => sanitize_text_field( $title ),
            'url'          => $url,
            'type'         => $type,
            'sub_type'     => $sub_type,
            'embed_key'    => $sub_type,
            'vendor_email' => get_option( 'bfc_vendor_email', '' ),
            'created_at'   => current_time( 'mysql' ),
        );

        $all_pages   = get_option( 'bfc_created_pages', array() );
        $all_pages[] = $entry;
        update_option( 'bfc_created_pages', $all_pages );

        return array( 'page_id' => $post_id, 'url' => $url );
    }

    // =========================================================================
    // Delete
    // =========================================================================

    /**
     * Trash the WordPress page and remove it from the stored list.
     *
     * @param int $page_id
     * @return bool
     */
    public function delete_page( $page_id ) {
        $page_id = (int) $page_id;
        bfc_log( "BFC_Pages::delete_page — trashing page ID={$page_id}" );

        wp_trash_post( $page_id );

        $all_pages = get_option( 'bfc_created_pages', array() );
        $all_pages = array_values( array_filter( $all_pages, function( $p ) use ( $page_id ) {
            return (int) $p['page_id'] !== $page_id;
        } ) );
        update_option( 'bfc_created_pages', $all_pages );

        bfc_log( "BFC_Pages::delete_page — page ID={$page_id} removed from registry." );
        return true;
    }

    // =========================================================================
    // Rename
    // =========================================================================

    /**
     * Rename the WordPress page and update the stored entry.
     *
     * @param int    $page_id
     * @param string $new_title
     * @return bool|WP_Error
     */
    public function rename_page( $page_id, $new_title ) {
        $page_id   = (int) $page_id;
        $new_title = sanitize_text_field( $new_title );
        bfc_log( "BFC_Pages::rename_page — ID={$page_id} new_title='{$new_title}'" );

        $result = wp_update_post( array( 'ID' => $page_id, 'post_title' => $new_title ), true );

        if ( is_wp_error( $result ) ) {
            bfc_log( 'BFC_Pages::rename_page — wp_update_post failed: ' . $result->get_error_message(), 'ERROR' );
            return $result;
        }

        $all_pages = get_option( 'bfc_created_pages', array() );
        foreach ( $all_pages as &$page ) {
            if ( (int) $page['page_id'] === $page_id ) {
                $page['title'] = $new_title;
                break;
            }
        }
        unset( $page );
        update_option( 'bfc_created_pages', $all_pages );

        bfc_log( "BFC_Pages::rename_page — done." );
        return true;
    }

    // =========================================================================
    // Sync
    // =========================================================================

    /**
     * Update the content of every managed page (current vendor only) with
     * the latest embed codes.
     *
     * @param array $embed_codes  Response body from /client/embed-codes.
     */
    public function sync_pages( array $embed_codes ) {
        // Load ALL pages with lazy vendor migration applied; we'll filter below.
        $all_pages = $this->load_pages( false );
        $pages     = $this->filter_vendor_pages( $all_pages );

        bfc_log( 'BFC_Pages::sync_pages — syncing ' . count( $pages ) . ' page(s).' );

        if ( empty( $pages ) ) {
            bfc_log( 'BFC_Pages::sync_pages — no pages to sync.' );
            return;
        }

        $code_map   = $this->build_code_map( $embed_codes );
        $updated    = 0;
        $needs_save = false;

        // Update page content in the full $all_pages array (to preserve other vendors' pages).
        foreach ( $all_pages as &$page ) {
            $sub_type = $page['sub_type'] ?? '';
            if ( ! isset( $code_map[ $sub_type ] ) ) {
                continue;
            }
            if ( ! $this->is_current_vendor_page( $page ) ) {
                continue;
            }

            $new_code = $code_map[ $sub_type ];
            $post     = get_post( (int) $page['page_id'] );

            if ( ! $post || $post->post_status === 'trash' ) {
                bfc_log( "BFC_Pages::sync_pages — page ID={$page['page_id']} is trashed or missing, skipping.", 'WARNING' );
                continue;
            }

            if ( $post->post_content !== $new_code ) {
                wp_update_post( array( 'ID' => (int) $page['page_id'], 'post_content' => $new_code ) );
                $updated++;
                $needs_save = true;
                bfc_log( "BFC_Pages::sync_pages — updated page ID={$page['page_id']}." );
            } else {
                bfc_log( "BFC_Pages::sync_pages — page ID={$page['page_id']} already up to date." );
            }
        }
        unset( $page );

        if ( $needs_save ) {
            update_option( 'bfc_created_pages', $all_pages );
        }

        bfc_log( "BFC_Pages::sync_pages — sync complete. {$updated} page(s) updated." );
    }

    // =========================================================================
    // Getters
    // =========================================================================

    /**
     * Return all tracked pages for the current vendor with refreshed permalink.
     *
     * @return array
     */
    public function get_created_pages() {
        $pages = $this->load_pages( true );

        foreach ( $pages as &$page ) {
            $url = get_permalink( (int) $page['page_id'] );
            if ( $url ) {
                $page['url'] = $url;
            }
        }
        unset( $page );

        bfc_log( 'BFC_Pages::get_created_pages — returned ' . count( $pages ) . ' page(s).' );
        return $pages;
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    /**
     * Load all stored pages from the option, lazily tagging any untagged entries
     * with the current vendor email (backward compatibility for pre-1.2.6 installs).
     * Optionally filter to only the current vendor's pages.
     *
     * @param bool $vendor_only  True to return only current vendor's pages.
     * @return array
     */
    private function load_pages( $vendor_only = true ) {
        $all_pages      = get_option( 'bfc_created_pages', array() );
        $current_vendor = get_option( 'bfc_vendor_email', '' );
        $needs_save     = false;

        // Lazy migration: tag legacy entries (no vendor_email) to the current vendor.
        // This is a one-time operation per install after upgrading to 1.2.6.
        foreach ( $all_pages as &$page ) {
            if ( ( ! isset( $page['vendor_email'] ) || $page['vendor_email'] === '' ) && $current_vendor ) {
                $page['vendor_email'] = $current_vendor;
                $needs_save           = true;
            }
        }
        unset( $page );

        if ( $needs_save ) {
            update_option( 'bfc_created_pages', $all_pages );
            bfc_log( 'BFC_Pages::load_pages — migrated legacy entries to vendor ' . $current_vendor . '.' );
        }

        if ( $vendor_only ) {
            return $this->filter_vendor_pages( $all_pages );
        }

        return $all_pages;
    }

    /**
     * Filter a pages array to only entries belonging to the current vendor.
     *
     * @param array $pages
     * @return array
     */
    private function filter_vendor_pages( array $pages ) {
        $current_vendor = get_option( 'bfc_vendor_email', '' );

        if ( ! $current_vendor ) {
            return array();
        }

        return array_values( array_filter( $pages, function( $p ) use ( $current_vendor ) {
            return isset( $p['vendor_email'] ) && $p['vendor_email'] === $current_vendor;
        } ) );
    }

    /**
     * Check if a page entry belongs to the current vendor.
     *
     * @param array $page
     * @return bool
     */
    private function is_current_vendor_page( array $page ) {
        $current_vendor = get_option( 'bfc_vendor_email', '' );
        return $current_vendor && isset( $page['vendor_email'] ) && $page['vendor_email'] === $current_vendor;
    }

    /**
     * Build a map of sub_type → embed_code from the API response.
     */
    private function build_code_map( array $embed_codes ) {
        $map = array();

        if ( ! empty( $embed_codes['calendar']['all_boats'] ) ) {
            $map['all_boats'] = $embed_codes['calendar']['all_boats'];
        }

        if ( ! empty( $embed_codes['calendar']['boats'] ) ) {
            foreach ( $embed_codes['calendar']['boats'] as $boat ) {
                $map[ 'boat:' . $boat['name'] ] = $boat['code'];
            }
        }

        if ( ! empty( $embed_codes['certificates'] ) ) {
            foreach ( $embed_codes['certificates'] as $cert ) {
                $map[ 'cert:' . $cert['id'] ] = $cert['code'];
            }
        }

        bfc_log( 'BFC_Pages::build_code_map — ' . count( $map ) . ' embed code(s) mapped.' );
        return $map;
    }

    /**
     * Find an existing tracked page by sub_type for the current vendor.
     */
    private function find_page_by_sub_type( $sub_type ) {
        foreach ( $this->load_pages( true ) as $page ) {
            if ( isset( $page['sub_type'] ) && $page['sub_type'] === $sub_type ) {
                $post = get_post( (int) $page['page_id'] );
                if ( $post && $post->post_status !== 'trash' ) {
                    return $page;
                }
            }
        }
        return null;
    }
}
