=== BookingFish Calendar ===
Contributors: bookingfish
Tags: booking, calendar, reservation, gift certificate, fishing
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.2.12
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Connect your WordPress site to your BookingFish account. Display your reservation calendar and gift certificates on your own website.

== Description ==

**BookingFish Calendar** is the companion plugin for members of [BookingFish.ca](https://bookingfish.ca) — the online reservation platform for fishing guide services and boat rental companies.

Once installed on your WordPress website, this plugin lets you:

* **Connect** your site to your BookingFish.ca account with a single login.
* **Automatically retrieve** your embed codes (reservation calendar + gift certificate widget).
* **Create WordPress pages** for your calendar and gift certificates in one click — no code to copy and paste.
* **Get shareable URLs** instantly to promote on social media and in your marketing campaigns.
* **Live display** — your calendar and availability are always up to date in real time.

= Key Features =

* Bilingual interface (French / English)
* One-click page creation
* Live calendar display — availability updates in real time
* Support for multiple boats (individual calendar page per boat)
* Support for multiple gift certificate templates
* Clean admin dashboard with 2 tabs: Connection · Setup

= How It Works =

1. Install and activate the plugin.
2. Go to **WordPress Admin → BookingFish → Connection tab**.
3. Enter your BookingFish.ca email and password.
4. Switch to the **Setup tab** and click **"Create Page"** for each widget you want.
5. Copy the page URL and share it on Facebook, Instagram, or your website menu.

= Requirements =

* A valid BookingFish.ca vendor account ([register here](https://bookingfish.ca/inscription/)).
* WordPress 5.8 or higher.
* PHP 7.4 or higher.

== Installation ==

1. Upload the `bookingfish-calendar` folder to the `/wp-content/plugins/` directory.
   **Or:** upload the ZIP via **Plugins → Add New → Upload Plugin** in your WordPress admin.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Click **BookingFish** in the left-hand admin menu.
4. Enter your BookingFish.ca credentials to connect.

== Frequently Asked Questions ==

= Do I need a BookingFish account? =

Yes. This plugin requires a vendor account on [bookingfish.ca](https://bookingfish.ca). You can register for free at [bookingfish.ca/inscription/](https://bookingfish.ca/inscription/).

= Is my password stored on my WordPress site? =

No. Your password is sent securely to bookingfish.ca for authentication and is never stored locally. Only an expiring bearer token (valid for 30 days) is kept in your WordPress options.

= What happens if my BookingFish configuration changes? =

The plugin syncs automatically in the background. You can also trigger an immediate sync by clicking **Sync Now** in the Connection tab.

= Can I delete the pages the plugin created? =

Yes. In the **Setup tab** you can delete any page created by the plugin.

= Is the plugin compatible with page builders (Elementor, Divi, etc.)? =

The plugin creates standard WordPress pages with the BookingFish embed code in the content area. These pages work with any theme and most page builders.

== Screenshots ==

1. Connection tab — login to your BookingFish account.
2. Setup tab — create your calendar and certificate pages in one click.

== Changelog ==

= 1.2.12 =
* Fixed: Update notification no longer reappears after installing the latest version. Root cause: WordPress hooks `wp_update_plugins()` to `upgrader_process_complete` at priority 10. This function saves a preliminary "lock" transient before its HTTP request to api.wordpress.org; at that moment `$transient->checked` is not yet populated, so the version comparison fell back to the `BFC_VERSION` PHP constant (still the old version in memory), adding a stale update entry. Fix: replaced the version comparison with `get_plugin_data()`, which uses `fopen()` to read the version header directly from disk — immune to PHP OPcache and always reflects the file just installed.

= 1.2.9 =
* Fixed: Update notification no longer reappears after installing the latest version. Root cause: `wp_clean_plugins_cache(true)` was being called inside `upgrader_process_complete`, which triggers `wp_update_plugins()` synchronously while `BFC_VERSION` is still the old version in PHP memory — causing the stale update entry to be re-added immediately. Fix: replaced with a targeted `delete_site_transient('update_plugins')` scoped to our plugin only, so WordPress rebuilds the transient on the next page load with the correct installed version.

= 1.2.8 =
* Fixed: Update notification no longer reappears after a successful update. The update checker now explicitly clears any stale update entry from the WordPress transient when the installed version is current.
* Fixed: Added `upgrader_process_complete` hook to force-clear the plugins update cache immediately after any plugin update, ensuring the freshly installed version is re-evaluated on the next page load.

= 1.2.7 =
* Fixed: Email field on the Connection tab no longer auto-fills with the WordPress admin email. The field is always empty on first use and pre-filled with the last successfully connected BookingFish account after a logout.

= 1.2.6 =
* Fixed: After creating a page, the Delete button and Copy link button now appear immediately — no page refresh required.
* Fixed: After deleting a page, the Setup tab is restored instead of returning to the Connection tab.
* Fixed: Copy link button now appears on all existing pages when the Setup tab is loaded (not only after creation).
* Fixed: Pages are now scoped per vendor — switching to a different BookingFish account no longer shows the previous account's pages. Legacy pages (pre-1.2.6) are automatically attributed to the currently logged-in vendor on first load.

= 1.2.5 =
* Fixed: Missing translators comment — moved `sprintf()` to its own line immediately below the `// translators:` comment to satisfy the WordPress.org checker.
* Fixed: Plugin header `Tested up to` updated to `6.9` to match readme.txt.

= 1.2.4 =
* Fixed: Missing translators comment for placeholder in `__()` call (WordPress.org compliance).
* Fixed: `date()` replaced by `gmdate()` in token expiry log to avoid runtime timezone issues.
* Fixed: All output in deactivation feedback modal now properly escaped with `esc_html()`.
* Fixed: All `$_POST` inputs now unslashed with `wp_unslash()` before sanitization.
* Fixed: `$_POST['password']` and `$_POST['lang']` properly sanitized.
* Fixed: `error_log()` in `bfc_log()` wrapped in `@wporg-remove-start` block — removed from WordPress.org build, stub function preserved.
* Fixed: `load_plugin_textdomain()` wrapped in `@wporg-remove-start` block — removed from WordPress.org build (WP handles translations automatically since 4.6).
* Added: `languages/` folder created to satisfy "Domain Path" plugin header requirement.
* Updated: Tested up to WordPress 6.9.

= 1.2.3 =
* Renamed plugin from "BookingFish Calendar Client" to "BookingFish Calendar" for better discoverability in the WordPress plugin catalog.
* Added: Deactivation feedback modal — a brief survey appears when deactivating the plugin to help improve the product.
* Improved: Description now correctly reflects live calendar display (availability updates in real time, not on a daily sync delay).
* Updated: Plugin slug changed from `bookingfish-calendar-client` to `bookingfish-calendar`. Existing installations are automatically migrated on activation — no settings are lost.

= 1.2.2 =
* Fixed: Member selector in the booking list backoffice was deselecting after choice due to duplicate hidden input overriding the dropdown value.
* Fixed: Admin footer text ("Thank you for creating with WordPress") removed from all admin pages.
* Fixed: PHP Deprecated notices for `strpos(null)` and `str_replace(null)` (WordPress/WooCommerce core on PHP 8.2) silenced via `error_reporting` to keep debug.log clean without hiding real errors.
* Fixed: `debug.log` was not receiving errors due to a plugin-level `ini_set` redirecting the error log path. Both the path override and the `WP_DEBUG_DISPLAY` conflict have been resolved.

= 1.1.0 =
* Added: Auto-sync when switching to the Setup tab — displayed data is always up to date.
* Added: Boat Calendar button with automatic authentication (magic link) — opens Boat Calendar on bookingfish.ca without requiring a separate login.
* Added: Per-boat published-month validation — warns the user and blocks page creation if no month has been published for the selected boat.
* Fixed: Individual boat calendars now appear correctly in the Setup tab (removed overly strict `is_active` and `price > 0` filter).
* Improved: Boat Calendar button integrated directly into the "no published month" warning for easier navigation.

= 1.0.0 =
* Initial release.
* Connect to bookingfish.ca via secure Bearer token (30-day TTL).
* Setup tab: create WordPress pages for the all-boats calendar and individual boat calendars.
* Setup tab: create WordPress pages for gift certificate templates.
* Daily background sync via WordPress cron.
* Bilingual admin interface (French / English).


== Upgrade Notice ==

= 1.2.3 =
Plugin renamed to "BookingFish Calendar" for better discoverability. Existing settings are automatically migrated — no action required. Recommended update for all users.

= 1.1.0 =
Adds automatic authentication for the Boat Calendar button, per-boat published-month validation, and automatic sync on tab switch. Recommended update for all users.

= 1.0.0 =
Initial release.
