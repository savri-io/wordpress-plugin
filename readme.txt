=== Savri Analytics ===
Contributors: valueunlimited
Tags: analytics, statistics, privacy, gdpr, cookieless
Requires at least: 5.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.3.0
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
* AI crawler visits - see which AI models (ChatGPT, Claude, Perplexity and more) read your site
* Site searches - what visitors search for, searches with no results, and clicks on results
* 404 pages - broken links your visitors actually hit
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
4. Enter your Site ID (get it from your [Savri Dashboard](https://savri.io/dashboard))
5. Save changes, you're done!

== External services ==

This plugin connects to the Savri Analytics service (savri.io or besokskollen.se, selectable in the plugin settings) to load the tracking script and send pageview data. This is required for the plugin to function, since Savri Analytics is the service that records and displays your site's traffic.

savri.io and besokskollen.se are operated by the same company and provide an identical service. besokskollen.se is the Swedish-language interface and savri.io is the international (English) interface. You only need an account on one of them, and you should select the matching domain in the plugin settings.

**What the service is and what it is used for**

Savri Analytics is a privacy-friendly, cookieless web analytics service that records anonymous pageviews and aggregated traffic data (referrers, top pages, countries, devices). The plugin uses the service to track visits to your WordPress site and display the results in your Savri dashboard.

**What data is sent and when**

* Every time a visitor loads a page on your site, the plugin loads the tracking script from `https://savri.io/script.js` or `https://besokskollen.se/script.js` (depending on the domain configured in the plugin settings).
* That script then sends a pageview event to the same domain (`/api/event` endpoint) containing: your Site ID, the page URL, the page title, the referrer, the visitor's user-agent string, the browser language, and the screen size.
* If you enable the optional tracking flags in the plugin settings, additional events are sent when the corresponding action happens on the page: clicks on outbound links, file downloads, form submissions, and scroll depth.
* On WordPress search result pages, the plugin sends a site search event containing the search term (trimmed, lowercased, capped at 100 characters), the number of results, and a click event when the visitor clicks a search result. This shows what visitors search for under Site Search in your Savri dashboard. It can be disabled under Settings > Savri Analytics > Site Search Tracking.
* On 404 pages, the tracking script sends a 404 event containing the requested path, so you can find broken links.
* When a known AI crawler (for example GPTBot, ClaudeBot or PerplexityBot) requests a page, the plugin sends a non-blocking report to the same domain (`/api/ai-crawl` endpoint) containing: your Site ID, the requested URL path, the crawler's user-agent string, and (since 1.3.0) the IP address the crawler connected from, so Savri can verify with a reverse DNS lookup that the visit really came from the AI company and not from someone impersonating its bot. This only happens for user-agents matching a fixed list of known AI bots, never for regular visitors. It can be disabled under Settings > Savri Analytics > AI Crawler Tracking.
* No cookies are set, no visitor IP addresses are stored, and no personal data is collected. Visitors are not tracked across sites. The only IP address ever sent is the server address of a known AI crawler (see above), never a visitor's.

**Service terms and privacy policy**

* Terms of service: [https://savri.io/terms](https://savri.io/terms)
* Privacy policy: [https://savri.io/privacy](https://savri.io/privacy)

== Frequently Asked Questions ==

= Where do I get a Site ID? =

Sign up at [savri.io](https://savri.io) (international) or [besokskollen.se](https://besokskollen.se) (Sweden) and create a new site. Your Site ID will be displayed in the site settings.

= Is this really GDPR compliant? =

Yes! Savri doesn't use cookies, doesn't store IP addresses, and doesn't collect any personal data. You don't need a cookie banner for Savri.

= Will this slow down my website? =

No. The tracking script is only ~2KB and loads asynchronously. It won't affect your page speed.

= Can I exclude my own visits? =

Yes! Enable "Exclude Administrators" in the plugin settings to stop tracking logged-in admins.

= Does it work with caching plugins? =

Yes, Savri works perfectly with all caching plugins (WP Super Cache, W3 Total Cache, etc.).

= What is AI Crawler Tracking? =

AI assistants like ChatGPT, Claude and Perplexity send bots to read websites, but those bots never run JavaScript, so they are invisible to regular analytics. Savri Analytics 1.1.0+ detects them on the server and reports the visits to your Savri dashboard (under AI Insights), so you can see which AI models read your site, which pages they fetch and how often. The report is sent non-blocking and never slows down your pages. Since 1.3.0 the report also includes the crawler's IP address, which Savri checks against the AI company's reverse DNS so impersonated bots show up as spoofed in your dashboard instead of as real AI visits. You can turn it off in the plugin settings.

= Does AI Crawler Tracking slow down my site? =

No. The check is a simple text comparison, and the report is sent with a non-blocking request that WordPress fires and forgets. Page loads are never delayed, even if the analytics service were unreachable.

= What is Site Search Tracking? =

When a visitor uses your WordPress search, the plugin reports what they searched for, how many results they got, and whether they clicked a result. Your Savri dashboard shows the top searches, the searches that returned nothing (a goldmine for content ideas), the zero-result rate and the search click-through rate. The search term is trimmed, lowercased and capped at 100 characters before it is sent. You can turn it off in the plugin settings.

= Does the plugin track 404 pages? =

Yes. Since 1.2.0 the plugin marks 404 pages so the tracking script reports them, and your dashboard shows which broken URLs visitors actually hit.

== Screenshots ==

1. Simple settings page: enter your Site ID, toggle enhanced tracking features
2. Real-time dashboard with traffic overview, bot filtering and AI bot visits
3. Site search analytics: top searches, zero-result searches, zero rate and search CTR

== Changelog ==

= 1.3.0 =
* AI Crawler Tracking: the report now includes the IP address the crawler connected from, so Savri can verify with a reverse DNS lookup that a visit really came from the AI company. Visits show as verified, spoofed or unverifiable under AI Insights in your dashboard; without the IP every visit is unverifiable.
* Documentation: the External services section and the AI Crawler Tracking FAQ describe the added field.

= 1.2.0 =
* New: Site Search Tracking. WordPress native searches are now reported automatically: the search term, the number of results, zero-result searches and clicks on search results. See them under Site Search in your Savri dashboard. On by default, can be turned off under Settings > Savri Analytics > Site Search Tracking.
* New: 404 tracking. The plugin marks 404 pages so the tracking script reports them, letting you find broken links your visitors hit.
* Documentation: the External services section describes the new site search and 404 events.

= 1.1.0 =
* New: AI Crawler Tracking. The plugin now detects visits from AI bots (GPTBot, ChatGPT-User, OAI-SearchBot, ClaudeBot, Claude-User, PerplexityBot, Google-Extended, MistralAI-User, Grok and more) server-side and reports them to your Savri dashboard, where they appear under AI Insights. AI bots do not run JavaScript, so this is the only way to see them.
* The report is sent as a non-blocking request and never delays page loads.
* Enabled by default, can be turned off under Settings > Savri Analytics > AI Crawler Tracking.
* Documentation: the External services section now describes the AI crawler reports (Site ID, URL path and the bot's user-agent string are sent, only for known AI bots).

= 1.0.3 =
* Documentation: add "External services" section to the readme describing the third-party Savri Analytics service the plugin connects to, what data is sent, and links to terms of service and privacy policy. No code changes.

= 1.0.2 =
* Internal refactor: centralize allowed API domains and tracking flags in class constants to remove duplication. No functional or behavioral changes.

= 1.0.1 =
* Security: escape all translated strings before output to prevent any theoretical XSS via translation files
* Security: validate api_domain against an allowlist on save and on read
* Fix: Plugin URI now points to a real documentation page
* Improvement: tracking script tag is built with esc_url + esc_attr and filtered through wp_kses on output

= 1.0.0 =
* Initial release
* Basic pageview tracking
* Enhanced tracking options (outbound links, downloads, forms, scroll)
* Admin exclusion option

== Upgrade Notice ==

= 1.3.0 =
AI crawler visits are now verified as genuine through reverse DNS using the crawler's IP address. Safe upgrade, no settings changes.

= 1.2.0 =
New: automatic site search analytics (including zero-result searches) and 404 tracking. Both on by default, both can be disabled in settings.

= 1.1.0 =
New: see which AI models (ChatGPT, Claude, Perplexity and more) read your site. Server-side AI crawler tracking, on by default, zero impact on page speed.

= 1.0.3 =
Documentation-only update for WordPress.org review compliance. Safe upgrade, no code changes.

= 1.0.2 =
Internal refactor only. Safe upgrade with no behavior changes.

= 1.0.1 =
Security and hardening release. Recommended for all users.

= 1.0.0 =
Initial release of Savri Analytics for WordPress.
