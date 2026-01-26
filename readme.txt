=== Savri Analytics ===
Contributors: valueunlimited007
Tags: analytics, statistics, privacy, gdpr, cookieless
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Privacy-friendly website analytics. No cookies, GDPR compliant, lightweight.

== Description ==

Savri Analytics is a privacy-focused alternative to Google Analytics. Track your website visitors without compromising their privacy.

**Key Features:**

* **No cookies** - No annoying cookie banners needed
* **GDPR compliant** - Privacy-first by design
* **Lightweight** - Only ~2KB script, won't slow down your site
* **Real-time** - See your visitors as they browse
* **Simple dashboard** - Easy to understand metrics

**What you can track:**

* Page views and unique visitors
* Traffic sources (referrers)
* Top pages
* Countries and devices
* Outbound link clicks (optional)
* File downloads (optional)
* Form submissions (optional)
* Scroll depth (optional)

**Why Savri?**

Unlike traditional analytics tools, Savri doesn't use cookies or collect personal data. This means:

* No cookie consent banners needed
* Full GDPR/CCPA compliance out of the box
* Faster page loads
* More accurate data (no ad-blockers blocking it)

== Installation ==

1. Upload the `savri-analytics` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > Savri Analytics
4. Enter your Site ID (get it from your [Savri Dashboard](https://besokskollen.se/dashboard))
5. Save changes - you're done!

== Frequently Asked Questions ==

= Where do I get a Site ID? =

Sign up at [besokskollen.se](https://besokskollen.se) and create a new site. Your Site ID will be displayed in the site settings.

= Is this really GDPR compliant? =

Yes! Savri doesn't use cookies, doesn't store IP addresses, and doesn't collect any personal data. You don't need a cookie banner for Savri.

= Will this slow down my website? =

No. The tracking script is only ~2KB and loads asynchronously. It won't affect your page speed.

= Can I exclude my own visits? =

Yes! Enable "Exclude Administrators" in the plugin settings to stop tracking logged-in admins.

= Does it work with caching plugins? =

Yes, Savri works perfectly with all caching plugins (WP Super Cache, W3 Total Cache, etc.).

== Screenshots ==

1. Simple settings page
2. Real-time dashboard
3. Traffic overview

== Changelog ==

= 1.0.0 =
* Initial release
* Basic pageview tracking
* Enhanced tracking options (outbound links, downloads, forms, scroll)
* Admin exclusion option

== Upgrade Notice ==

= 1.0.0 =
Initial release of Savri Analytics for WordPress.
