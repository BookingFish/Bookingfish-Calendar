<?php
/**
 * Plugin Name:  BookingFish Calendar
 * Plugin URI:   https://bookingfish.ca
 * Description:  Connect your WordPress site to your BookingFish account. Display your reservation calendar and gift certificates on your own website in just a few clicks.
 * Version:      1.2.12
 * Author:       BookingFish
 * Author URI:   https://profiles.wordpress.org/bookingfish/
 * License:      GPL v2 or later
 * License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:  bookingfish-calendar
 * Domain Path:  /languages
 * Requires at least: 5.8
 * Tested up to:      6.9
 * Requires PHP:      7.4
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'BFC_VERSION', '1.2.12' );
define( 'BFC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BFC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'BFC_API_BASE',   'https://bookingfish.ca/wp-json/bookingfish/v1' );
define( 'BFC_SITE_URL',   'https://bookingfish.ca' );

require_once BFC_PLUGIN_DIR . 'includes/class-bfc-api.php';
require_once BFC_PLUGIN_DIR . 'includes/class-bfc-pages.php';
require_once BFC_PLUGIN_DIR . 'includes/class-bfc-admin.php';

if ( ! function_exists( 'bfc_log' ) ) {
    function bfc_log( $message, $level = 'INFO' ) {} // No-op stub for WordPress.org build
}

function bfc_init() {

    $admin = new BFC_Admin();
    $admin->init();
}
add_action( 'plugins_loaded', 'bfc_init' );

register_activation_hook( __FILE__, 'bfc_activate' );

function bfc_activate() {
    // Migrate options from old plugin slug (bookingfish-calendar-client, v1.2.2 and earlier)
    $migrate = [
        'bfcc_language'      => 'bfc_language',
        'bfcc_auth_token'    => 'bfc_auth_token',
        'bfcc_token_expires' => 'bfc_token_expires',
        'bfcc_vendor_email'  => 'bfc_vendor_email',
        'bfcc_vendor_name'   => 'bfc_vendor_name',
        'bfcc_embed_codes'   => 'bfc_embed_codes',
        'bfcc_created_pages' => 'bfc_created_pages',
        'bfcc_last_sync'     => 'bfc_last_sync',
    ];
    foreach ( $migrate as $old => $new ) {
        $val = get_option( $old );
        if ( $val !== false ) {
            update_option( $new, $val );
            delete_option( $old );
        }
    }
    wp_clear_scheduled_hook( 'bfcc_daily_sync' );

    if ( ! get_option( 'bfc_language' ) )        add_option( 'bfc_language',      'fr' );
    if ( false === get_option( 'bfc_auth_token' ) )    add_option( 'bfc_auth_token',    '' );
    if ( false === get_option( 'bfc_token_expires' ) ) add_option( 'bfc_token_expires', 0 );
    if ( false === get_option( 'bfc_vendor_email' ) )  add_option( 'bfc_vendor_email',  '' );
    if ( false === get_option( 'bfc_vendor_name' ) )   add_option( 'bfc_vendor_name',   '' );
    if ( false === get_option( 'bfc_embed_codes' ) )   add_option( 'bfc_embed_codes',   array() );
    if ( false === get_option( 'bfc_created_pages' ) ) add_option( 'bfc_created_pages', array() );
    if ( false === get_option( 'bfc_last_sync' ) )     add_option( 'bfc_last_sync',     0 );
}

register_deactivation_hook( __FILE__, 'bfc_deactivate' );

function bfc_deactivate() {
    wp_clear_scheduled_hook( 'bfc_daily_sync' );
}

add_action( 'wp', 'bfc_maybe_schedule_sync' );

function bfc_maybe_schedule_sync() {
    if ( ! wp_next_scheduled( 'bfc_daily_sync' ) ) {
        wp_schedule_event( time(), 'daily', 'bfc_daily_sync' );
    }
}

add_action( 'bfc_daily_sync', 'bfc_do_sync' );

function bfc_do_sync() {
    $token = get_option( 'bfc_auth_token', '' );
    if ( empty( $token ) ) {
        bfc_log( 'bfc_do_sync — skipped: no token stored.', 'INFO' );
        return;
    }

    bfc_log( 'bfc_do_sync — starting scheduled sync.', 'INFO' );

    $api         = new BFC_API();
    $embed_codes = $api->get_embed_codes( $token );

    if ( is_wp_error( $embed_codes ) ) {
        bfc_log( 'bfc_do_sync — embed code fetch failed: ' . $embed_codes->get_error_message(), 'ERROR' );
        return;
    }

    if ( ! empty( $embed_codes['success'] ) ) {
        update_option( 'bfc_embed_codes', $embed_codes );
        update_option( 'bfc_last_sync',   time() );
        bfc_log( 'bfc_do_sync — embed codes refreshed.', 'INFO' );

        $pages = new BFC_Pages();
        $pages->sync_pages( $embed_codes );
        bfc_log( 'bfc_do_sync — pages synced.', 'INFO' );
    }
}
