# BookingFish Calendar — WordPress Booking Plugin for Fishing Guides

![WordPress Version](https://img.shields.io/badge/WordPress-5.8%2B-blue)
![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-purple)
![License](https://img.shields.io/badge/License-GPL%20v2-green)
![Languages](https://img.shields.io/badge/Languages-FR%2FEN-yellow)

🎣 **Professional calendar & booking system designed specifically for fishing guides.** Manage availability, accept reservations, and sync with your workflow — all from WordPress.

🔗 **Official website**: https://bookingfish.ca  
📥 **Download**: https://bookingfish.ca/telechargement/  
📚 **Documentation**: https://bookingfish.ca/docs/  
🐙 **GitHub**: https://github.com/BookingFish/Bookingfish-Calendar  

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
- Equipment tracking: Rods, boats, gear included per trip

### 🔔 Booking & Notifications
- Real-time availability check for clients
- Automated email confirmations (FR/EN)
- SMS notification support (via integration)
- Admin alerts for new bookings or changes

### 🔗 Integrations & Sync
- iCal/ICS export for Google Calendar, Outlook, Apple Calendar
- Two-way sync with external calendars
- Webhook support for custom integrations
- REST API for developers

### 🔐 Security & Privacy
- GDPR/LPRPSP compliant (Canada)
- Encrypted client data storage
- Role-based access control (Guide, Assistant, Admin)
- Secure token-based calendar sharing

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
