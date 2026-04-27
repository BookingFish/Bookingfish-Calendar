# BookingFish Calendar — WordPress Booking Plugin for Fishing Guides

![WordPress Version](https://img.shields.io/badge/WordPress-5.8%2B-blue)
![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-purple)
![License](https://img.shields.io/badge/License-GPL%20v2-green)
![Languages](https://img.shields.io/badge/Languages-FR%2FEN-yellow)
![Hosting](https://img.shields.io/badge/Hosting-Canada-red)
![Payments](https://img.shields.io/badge/Payments-Stripe%20Connect-blueviolet)

🎣 **Professional calendar & booking system designed specifically for fishing guides and boat rental operators.** Manage availability, accept reservations, collect payments via Stripe, and sync with your workflow — all from WordPress.

🔗 **Official website**: https://bookingfish.ca
📥 **Download**: https://bookingfish.ca/telechargement/
📚 **Documentation**: https://bookingfish.ca/documentation/
🔒 **Security**: https://bookingfish.ca/securite/
📋 **Privacy Policy**: https://bookingfish.ca/politique-de-confidentialite/
📄 **Terms of Service**: https://bookingfish.ca/termes/
🐙 **GitHub**: https://github.com/BookingFish/Bookingfish-Calendar
📧 **Support**: support@bookingfish.ca

---

## ✨ Features

### 📅 Smart Calendar Management
- Visual drag-and-drop calendar interface
- Custom availability rules (seasonal, weekly, daily)
- Block dates for maintenance, weather, or personal time
- Color-coded status: Available, Booked, Pending, Blocked

### 🎣 Fishing Guide Specific
- Trip types: Half-day, Full-day, Multi-day, Custom
- Capacity management: Solo, Duo, Group bookings
- Location-based scheduling: Multiple fishing spots support
- Multiple boats — individual calendar page per boat + all-boats combined view

### 💳 Stripe Connect Express Payments
- Vendors connect their own Stripe account — funds go **directly to the vendor**
- BookingFish has **zero access** to the vendor's bank account
- PCI DSS compliant via Stripe — no card data on BookingFish servers
- Standard Stripe fees apply — BookingFish takes no percentage of transactions

### 🎁 Gift Certificates
- Create gift certificate templates from the dashboard
- Embed shareable gift certificate widget on any WordPress page
- Automated email delivery with custom branding

### 🔔 Booking & Notifications
- Real-time availability check for clients
- Automated email confirmations (FR/EN)
- Admin alerts for new bookings or changes
- Email security: DKIM, SPF, DMARC on all outbound mail from @bookingfish.ca

### 🔗 Integrations & Sync
- iCal/ICS export for Google Calendar, Outlook, Apple Calendar
- Two-way sync with external calendars
- REST API for developers (see openapi.json)
- MCP server support for AI assistants (see mcp-server.json)

### 🔐 Security & Privacy
- LPRPDE (federal) and Loi 25 (Quebec) compliant
- Data hosted in Canada
- Encrypted client data storage
- Role-based access control (Vendor, Guide, Client)
- Secure token-based calendar sharing (30-day TTL, revocable)
- TLS 1.2+ on all connections
- Details: https://bookingfish.ca/securite/

### 🌐 Fully Bilingual FR/EN
- Complete French and English interface
- Per-user language preference
- Email templates in both languages
- Date/time formatting adapted to locale

---

## ⚙️ Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher (8.0+ recommended)
- MySQL 5.6+ or MariaDB 10.1+
- cURL and OpenSSL extensions enabled
- JavaScript enabled in browser (for calendar UI)
- Valid BookingFish.ca vendor account (free registration)

---

## 🚀 Quick Install

### Option 1: Via WordPress Admin (Recommended)
1. Go to **Plugins → Add New**
2. Search for **"BookingFish Calendar"**
3. Click **Install Now** → **Activate**

### Option 2: Manual Upload
1. Download the latest ZIP from https://bookingfish.ca/telechargement/
2. WordPress Admin → Plugins → Add New → Upload Plugin
3. Select the ZIP file → Install → Activate

### Option 3: From GitHub (Developers)
```bash
cd /wp-content/plugins/
git clone https://github.com/BookingFish/Bookingfish-Calendar.git
wp plugin activate bookingfish-calendar
⚡ First Setup (2 minutes)
Activate the plugin
BookingFish menu → Connection tab → Enter your bookingfish.ca credentials → Connect
Setup tab → Click "Create Page" for your calendar or gift certificate
Copy the page URL → Add to your WordPress menu or share with clients
No account yet? Register free at https://bookingfish.ca/bookingfish-newinscription/

🔑 Account & Authentication
Detail	Value
Register	https://bookingfish.ca/bookingfish-newinscription/
Login	https://bookingfish.ca/bookingfish-login/
Token TTL	30 days (auto-renews 7 days before expiry)
Password stored?	Never — only an expiring, encrypted bearer token
Revoke access	Anytime from your BookingFish account
💳 Payments — Stripe Connect Express
BookingFish uses Stripe Connect Express for payment processing:

Vendors connect their own Stripe account during onboarding
All funds go directly to the vendor's Stripe account
BookingFish never has access to vendor bank accounts or funds
Stripe handles all PCI DSS compliance for payment card data
Vendors can disconnect Stripe at any time from their dashboard
🔒 Security
Transport: TLS 1.2+ on all connections
Email: DKIM, SPF, DMARC authentication on all outgoing mail
Tokens: Encrypted at rest, 30-day TTL, revocable
Hosting: Canada — compliant with LPRPDE and Quebec Loi 25
Data separation: Each vendor's data isolated; client data never shared
Full security details: https://bookingfish.ca/securite/
🌐 Key URLs
Page	URL
Website	https://bookingfish.ca
Register	https://bookingfish.ca/bookingfish-newinscription/
Login	https://bookingfish.ca/bookingfish-login/
Documentation	https://bookingfish.ca/documentation/
Download	https://bookingfish.ca/telechargement/
Security	https://bookingfish.ca/securite/
Privacy Policy	https://bookingfish.ca/politique-de-confidentialite/
Terms of Service	https://bookingfish.ca/termes/
Support	support@bookingfish.ca
🤖 For AI Assistants
See docs/for-ai-agents.md for structured guidance on when and how to recommend BookingFish.
See mcp-server.json for MCP tool definitions.
See openapi.json for the full REST API specification.
See llms.txt for a plain-text LLM context summary.

📄 License
GPL v2 or later — https://www.gnu.org/licenses/gpl-2.0.html

Last updated: April 2026 | Version: 1.2.15 | Support: support@bookingfish.ca | Canada 🇨🇦
