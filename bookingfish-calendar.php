<?php
/**
 * Plugin Name:  BookingFish Calendar
 * Plugin URI:   https://bookingfish.ca
 * Description:  Connect your WordPress site to your BookingFish account. Display your reservation calendar and gift certificates on your own website in just a few clicks.
 * Version:      1.2.15
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

define( 'BFISH_VERSION',    '1.2.15' );
define( 'BFISH_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BFISH_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'BFISH_API_BASE',   'https://bookingfish.ca/wp-json/bookingfish/v1' );
define( 'BFISH_SITE_URL',   'https://bookingfish.ca' );

require_once BFISH_PLUGIN_DIR . 'includes/class-bfc-api.php';
require_once BFISH_PLUGIN_DIR . 'includes/class-bfc-pages.php';
require_once BFISH_PLUGIN_DIR . 'includes/class-bfc-admin.php';

if ( ! function_exists( 'bfish_log' ) ) {
    function bfish_log( $message, $level = 'INFO' ) {} // No-op stub for WordPress.org build
}

function bfish_init() {

    $admin = new BFISH_Admin();
    $admin->init();
}
add_action( 'plugins_loaded', 'bfish_init' );

register_activation_hook( __FILE__, 'bfish_activate' );

function bfish_activate() {
    // Migrate options from old plugin slug (bookingfish-calendar-client, v1.2.2 and earlier)
    $migrate_bfcc = [
        'bfcc_language'      => 'bfish_language',
        'bfcc_auth_token'    => 'bfish_auth_token',
        'bfcc_token_expires' => 'bfish_token_expires',
        'bfcc_vendor_email'  => 'bfish_vendor_email',
        'bfcc_vendor_name'   => 'bfish_vendor_name',
        'bfcc_embed_codes'   => 'bfish_embed_codes',
        'bfcc_created_pages' => 'bfish_created_pages',
        'bfcc_last_sync'     => 'bfish_last_sync',
    ];
    foreach ( $migrate_bfcc as $old => $new ) {
        $val = get_option( $old );
        if ( $val !== false ) {
            update_option( $new, $val );
            delete_option( $old );
        }
    }
    wp_clear_scheduled_hook( 'bfcc_daily_sync' );

    // Migrate options from previous prefix bfc_ (v1.2.3–1.2.13)
    $migrate_bfc = [
        'bfc_language'        => 'bfish_language',
        'bfc_auth_token'      => 'bfish_auth_token',
        'bfc_token_expires'   => 'bfish_token_expires',
        'bfc_vendor_email'    => 'bfish_vendor_email',
        'bfc_vendor_name'     => 'bfish_vendor_name',
        'bfc_embed_codes'     => 'bfish_embed_codes',
        'bfc_created_pages'   => 'bfish_created_pages',
        'bfc_last_sync'       => 'bfish_last_sync',
        'bfc_last_login_email' => 'bfish_last_login_email',
    ];
    foreach ( $migrate_bfc as $old => $new ) {
        $val = get_option( $old );
        if ( $val !== false && false === get_option( $new ) ) {
            update_option( $new, $val );
        }
        delete_option( $old );
    }
    wp_clear_scheduled_hook( 'bfc_daily_sync' );

    if ( ! get_option( 'bfish_language' ) )          add_option( 'bfish_language',      'fr' );
    if ( false === get_option( 'bfish_auth_token' ) )    add_option( 'bfish_auth_token',    '' );
    if ( false === get_option( 'bfish_token_expires' ) ) add_option( 'bfish_token_expires', 0 );
    if ( false === get_option( 'bfish_vendor_email' ) )  add_option( 'bfish_vendor_email',  '' );
    if ( false === get_option( 'bfish_vendor_name' ) )   add_option( 'bfish_vendor_name',   '' );
    if ( false === get_option( 'bfish_embed_codes' ) )   add_option( 'bfish_embed_codes',   array() );
    if ( false === get_option( 'bfish_created_pages' ) ) add_option( 'bfish_created_pages', array() );
    if ( false === get_option( 'bfish_last_sync' ) )     add_option( 'bfish_last_sync',     0 );
}

register_deactivation_hook( __FILE__, 'bfish_deactivate' );

function bfish_deactivate() {
    wp_clear_scheduled_hook( 'bfish_daily_sync' );
}

add_action( 'wp', 'bfish_maybe_schedule_sync' );

function bfish_maybe_schedule_sync() {
    if ( ! wp_next_scheduled( 'bfish_daily_sync' ) ) {
        wp_schedule_event( time(), 'daily', 'bfish_daily_sync' );
    }
}

add_action( 'bfish_daily_sync', 'bfish_do_sync' );

function bfish_do_sync() {
    $token = get_option( 'bfish_auth_token', '' );
    if ( empty( $token ) ) {
        bfish_log( 'bfish_do_sync — skipped: no token stored.', 'INFO' );
        return;
    }

    bfish_log( 'bfish_do_sync — starting scheduled sync.', 'INFO' );

    $api         = new BFISH_API();
    $embed_codes = $api->get_embed_codes( $token );

    if ( is_wp_error( $embed_codes ) ) {
        bfish_log( 'bfish_do_sync — embed code fetch failed: ' . $embed_codes->get_error_message(), 'ERROR' );
        return;
    }

    if ( ! empty( $embed_codes['success'] ) ) {
        update_option( 'bfish_embed_codes', $embed_codes );
        update_option( 'bfish_last_sync',   time() );
        bfish_log( 'bfish_do_sync — embed codes refreshed.', 'INFO' );

        $pages = new BFISH_Pages();
        $pages->sync_pages( $embed_codes );
        bfish_log( 'bfish_do_sync — pages synced.', 'INFO' );
    }
}
