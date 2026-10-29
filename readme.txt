=== Awesome Car Rental & Booking ===
Contributors: nahian91
Tags: car rental, vehicle booking, rental history, car rental crm, car booking, custom mysql architecture, checkout engine
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

The most comprehensive, lightning-fast, and Super SEO-Friendly car rental engine for WordPress. Powered by a bespoke high-performance MySQL architecture (`dev_nahian_*`) with a bulletproof `[acrb_checkout]` engine.

== Description ==

**Awesome Car Rental Booking** is a professional-grade ERP and reservation engine built for high-volume vehicle rental agencies and luxury hire fleets. By replacing slow WordPress postmeta storage with dedicated custom database tables (`dev_nahian_cars`, `dev_nahian_bookings`, `dev_nahian_customers`, etc.), this plugin achieves maximum query speed and ultra-lean resource consumption.

Designed with a **Super SEO-Friendly** framework, Version 1.2 introduces clean semantic markup, Schema.org JSON-LD vehicle structured data, automated Open Graph tags for social sharing, and instant-loading shortcodes. From managing your diverse fleet to tracking multi-currency revenue in **£**, this plugin is the complete turnkey solution for your rental business.

== Key Modules & Features ==

### 💳 Bulletproof Checkout Engine (`[acrb_checkout]`)
A streamlined, frictionless booking and payment confirmation gateway.
- **Dynamic Calculation:** Instantly computes totals based on daily rates, seasonal pricing overrides, and selected insurance/border permits.
- **Secure Processing:** Supports Cash on Pickup, Direct Bank Transfer, and Pay Later workflows with instant database logging.
- **Dedicated Shortcode:** Easily deploy the standalone checkout page using `[acrb_checkout]`.

### 🚀 Super SEO-Friendly Architecture
Maximize your search engine visibility and organic traffic.
- **Schema.org Structured Data:** Automatically injects `Car` and `Product` microdata into single vehicle pages for rich search snippets.
- **Optimized Slugs & Permalinks:** Clean URL structuring for category taxonomies and vehicle profiles.
- **Lightning-Fast Queries:** Bypasses bloated postmeta tables using direct indexed MySQL queries (`$wpdb`) to ensure sub-millisecond page load times.

### 📊 Dashboard: Business Intelligence Overview
Get an instant pulse on your business. The centralized overview shows:
- Real-time reservation stats and revenue snapshots.
- Active rental counts and fleet utilization metrics.
- Quick-action shortcuts for daily operations.

### 🚗 Fleet Management (Cars)
Full lifecycle management of your vehicles.
- **All Cars:** A comprehensive inventory view with VIN tracking, categories, and rates.
- **Add/Edit Car:** Intuitive interface for technical specs, high-res galleries, seasonal rates, and assigned pickup/drop-off hubs.

### 📅 Booking Control Center
Manage reservations from initial inquiry to vehicle check-in/check-out.
- **All Bookings:** Track client details, rental routes, and dynamic statuses.
- **Condition Reports:** Integrated inspection logs for mileage, fuel levels, and damage tracking.

### ⚙️ Global Settings Engine & Localization
Tailor the software to your exact brand requirements.
- **Currency Customization:** Full native support for **£** with left/right positioning.
- **Extras & Protection Plans:** Manage customizable insurance waivers and cross-border travel permits.

== ⌨️ Shortcode Library ==

Deploy a professional rental site in minutes using our advanced shortcode suite:

- `[acrb_header_auth]` - Dynamic login/register toggle for your site header.
- `[acrb_search_form]` - A conversion-focused car availability search bar.
- `[acrb_all_cars]` - Display your entire fleet in a premium, responsive grid.
- `[acrb_checkout]` - The core secure checkout and reservation processing page.
- `[acrb_thanks]` - A professional post-booking confirmation page with print/receipt tools.
- `[acrb_login]` - Custom-styled customer login portal.
- `[acrb_register]` - User-friendly registration form for new clients.
- `[acrb_account]` - The elite customer dashboard for managing history and receipts.

== Installation ==

1. Upload the `awesome-car-rental` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress (triggers automatic custom table provisioning and default data seeding).
3. Navigate to **Car Rental > Settings** to set your currency (**£**) and operational preferences.
4. Create your checkout page, paste the `[acrb_checkout]` shortcode, and publish.

== Frequently Asked Questions ==

= What does the [acrb_checkout] shortcode do? =
`[acrb_checkout]` renders the secure booking confirmation and payment processing summary where users finalize their vehicle reservation details before committing to the rental.

= Is the plugin truly SEO friendly? =
Yes! Version 1.2 includes automated Schema.org JSON-LD structured data for every vehicle, clean semantic HTML5 markup, fast database response times, and optimized permalink routing to help you rank higher on Google.

= Can I use £ as my primary currency? =
Yes! Navigate to the **General Settings** to set £ as your symbol and choose its display position.

= How are vehicles stored in the database? =
Vehicles and bookings are stored in high-performance custom MySQL tables (`wp_dev_nahian_cars`, `wp_dev_nahian_bookings`) rather than slow postmeta tables, ensuring rapid scaling for large fleets.

== Changelog ==

= 1.2.0 =
- Added `[acrb_checkout]` shortcode and dedicated checkout processing engine.
- Implemented Super SEO-Friendly features including Schema.org JSON-LD vehicle microdata and optimized markup.
- Fully transitioned CPT architecture to high-performance custom MySQL tables (`dev_nahian_*`).
- Added native **£** currency support with flexible left/right positioning.
- Integrated automated schema migration and table provisioning on plugin activation.