<?php
/**
 * Runs when the plugin is deleted (not just deactivated).
 * Removes all options stored by BookingFish Calendar.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

$bfish_options = array(
    // Current option names (v1.2.14+)
    'bfish_language',
    'bfish_auth_token',
    'bfish_token_expires',
    'bfish_vendor_email',
    'bfish_vendor_name',
    'bfish_embed_codes',
    'bfish_created_pages',
    'bfish_last_sync',
    'bfish_last_login_email',
    // Previous option names (v1.2.3–1.2.13)
    'bfc_language',
    'bfc_auth_token',
    'bfc_token_expires',
    'bfc_vendor_email',
    'bfc_vendor_name',
    'bfc_embed_codes',
    'bfc_created_pages',
    'bfc_last_sync',
    'bfc_last_login_email',
    // Legacy option names (bookingfish-calendar-client, v1.2.2 and earlier)
    'bfcc_language',
    'bfcc_auth_token',
    'bfcc_token_expires',
    'bfcc_vendor_email',
    'bfcc_vendor_name',
    'bfcc_embed_codes',
    'bfcc_created_pages',
    'bfcc_last_sync',
);

foreach ( $bfish_options as $bfish_option ) {
    delete_option( $bfish_option );
}

// Remove scheduled cron events
wp_clear_scheduled_hook( 'bfish_daily_sync' );
wp_clear_scheduled_hook( 'bfc_daily_sync' );
wp_clear_scheduled_hook( 'bfcc_daily_sync' );
