=== BookingFish Calendar ===
Contributors: bookingfish
Tags: booking, calendar, reservation, gift certificate, fishing
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.2.15
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

= 1.2.15 =
* Updated: Added Privacy Policy and Terms of Service links to the plugin readme (WordPress.org External services section).
* Updated: Privacy Policy and Terms of Service links now appear on the Connection tab login form.

= 1.2.14 =
* Improved: Code quality and WordPress.org compliance improvements.

= 1.2.13 =
* Improved: Admin interface visual adjustments and compatibility improvements.
* Improved: Plugin details panel now displays banner and screenshots correctly.

= 1.2.12 =
* Fixed: Update notification no longer reappears after a successful update.

= 1.2.9 =
* Fixed: Improved reliability of the update notification.

= 1.2.8 =
* Fixed: Plugin update cache is now correctly cleared after an update.

= 1.2.7 =
* Fixed: Minor improvement to the Connection tab login form.

= 1.2.6 =
* Fixed: Page actions (Delete, Copy link) now take effect immediately without a page refresh.
* Fixed: Switching vendor accounts now correctly shows the associated pages.

= 1.2.5 =
* Fixed: Minor code quality improvements.

= 1.2.4 =
* Fixed: Various security and code quality improvements.
* Updated: Tested up to WordPress 6.9.

= 1.2.3 =
* Renamed plugin to "BookingFish Calendar" for better discoverability.
* Added: Deactivation feedback modal.
* Updated: Plugin slug is automatically migrated — no settings are lost.

= 1.2.2 =
* Fixed: Various admin interface and compatibility improvements.

= 1.1.0 =
* Added: Automatic sync when switching tabs.
* Added: Boat Calendar button with automatic authentication.
* Added: Published month validation per boat before page creation.

= 1.0.0 =
* Initial release.
* Connect to bookingfish.ca via a secure Bearer token.
* Create WordPress pages for calendars and gift certificates.
* Bilingual admin interface (French / English).


== External services ==

This plugin connects to the **BookingFish.ca** REST API (`https://bookingfish.ca/wp-json/bookingfish/v1`) to authenticate your account and retrieve your booking calendar embed codes.

**What data is sent and when:**

* Your email address and password are sent to BookingFish.ca **once at login** to obtain a secure authentication token. Your password is never stored on your WordPress site.
* A bearer token (valid 30 days) is sent with each subsequent API call: fetching embed codes, syncing, and logging out.
* If you choose to submit feedback when deactivating the plugin, your site name, site URL, WordPress version, plugin version, and the reason you selected are sent to BookingFish.ca.

**Service:** BookingFish.ca — online reservation platform for fishing guides and outfitters.

* Service home page: https://bookingfish.ca
* Terms of Service: https://bookingfish.ca/termes/
* Privacy Policy: https://bookingfish.ca/politique-de-confidentialite/

== Upgrade Notice ==

= 1.2.15 =
Adds Privacy Policy and Terms of Service links required by WordPress.org.

= 1.2.14 =
Required update for WordPress.org compliance. All existing settings are automatically migrated — no action required.

= 1.2.13 =
Recommended update. Improves admin interface and plugin details display.

= 1.2.3 =
Plugin renamed to "BookingFish Calendar". Existing settings are automatically migrated — no action required.
