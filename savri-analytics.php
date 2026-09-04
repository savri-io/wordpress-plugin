<?php
/**
 * Plugin Name: Savri Analytics
 * Plugin URI: https://savri.io/docs/integration-guides/wordpress
 * Description: Privacy-friendly website analytics. No cookies, GDPR compliant.
 * Version: 1.3.0
 * Author: Savri
 * Author URI: https://savri.io
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: savri-analytics
 */

if (!defined('ABSPATH')) {
    exit;
}

class Savri_Analytics {

    /**
     * Allowed API domains (option key => display label).
     * Adding a new region is a one-line change here.
     */
    private const ALLOWED_DOMAINS = array(
        'savri.io'        => 'savri.io (International)',
        'besokskollen.se' => 'besokskollen.se (Sweden)',
    );

    /**
     * Boolean tracking flags (option key => script data-attribute).
     * Drives both the rendered tag and the wp_kses allowlist.
     */
    private const TRACKING_FLAGS = array(
        'track_outbound'  => 'data-outbound-links',
        'track_downloads' => 'data-file-downloads',
        'track_forms'     => 'data-forms',
        'track_scroll'    => 'data-scroll-depth',
    );

    /**
     * User-Agent substrings (case-insensitive) that identify AI crawlers.
     * Mirrors lib/ai-crawlers.ts in the Savri codebase; keep in sync when
     * new bots are added. The Savri API re-detects the exact crawler from
     * the full UA, so this list only decides whether to report at all.
     */
    private const AI_CRAWLER_PATTERNS = array(
        'OAI-SearchBot', 'ChatGPT-User', 'GPTBot',
        'Claude-User', 'Claude-SearchBot', 'Claude-Web', 'ClaudeBot', 'anthropic-ai',
        'Perplexity-User', 'PerplexityBot',
        'Google-Extended', 'GoogleOther', 'Google-CloudVertexBot',
        'Google-NotebookLM', 'GoogleAgent-Mariner',
        'MistralAI-User',
        'Grok-DeepSearch', 'xAI-Grok', 'GrokBot',
        'Applebot',
        'meta-externalfetcher', 'meta-externalagent',
        'Amazonbot',
        'cohere-ai', 'cohere-training-data-crawler',
        'Bytespider', 'CCBot', 'DuckAssistBot', 'Diffbot', 'YouBot',
    );

    private $options;

    public function __construct() {
        $this->options = get_option('savri_analytics_options', array());

        // Admin hooks
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_settings'));

        // Frontend hooks
        add_action('wp_head', array($this, 'inject_tracking_script'), 1);
        add_action('template_redirect', array($this, 'report_ai_crawler'));

        // Plugin action links
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_action_links'));
    }

    /**
     * Add settings link to plugins page
     */
    public function add_action_links($links) {
        $settings_link = '<a href="options-general.php?page=savri-analytics">' . esc_html__('Settings', 'savri-analytics') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            esc_html__('Savri Analytics', 'savri-analytics'),
            esc_html__('Savri Analytics', 'savri-analytics'),
            'manage_options',
            'savri-analytics',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Initialize settings
     */
    public function init_settings() {
        register_setting('savri_analytics', 'savri_analytics_options', array(
            'sanitize_callback' => array($this, 'sanitize_options')
        ));

        // Main section
        add_settings_section(
            'savri_analytics_main',
            esc_html__('Configuration', 'savri-analytics'),
            array($this, 'render_section_main'),
            'savri-analytics'
        );

        // Site ID field
        add_settings_field(
            'site_id',
            esc_html__('Site ID', 'savri-analytics'),
            array($this, 'render_field_site_id'),
            'savri-analytics',
            'savri_analytics_main'
        );

        // API Domain field
        add_settings_field(
            'api_domain',
            esc_html__('API Domain', 'savri-analytics'),
            array($this, 'render_field_api_domain'),
            'savri-analytics',
            'savri_analytics_main'
        );

        // Enhanced tracking section
        add_settings_section(
            'savri_analytics_enhanced',
            esc_html__('Enhanced Tracking', 'savri-analytics'),
            array($this, 'render_section_enhanced'),
            'savri-analytics'
        );

        // Outbound links
        add_settings_field(
            'track_outbound',
            esc_html__('Outbound Links', 'savri-analytics'),
            array($this, 'render_field_track_outbound'),
            'savri-analytics',
            'savri_analytics_enhanced'
        );

        // File downloads
        add_settings_field(
            'track_downloads',
            esc_html__('File Downloads', 'savri-analytics'),
            array($this, 'render_field_track_downloads'),
            'savri-analytics',
            'savri_analytics_enhanced'
        );

        // Form submissions
        add_settings_field(
            'track_forms',
            esc_html__('Form Submissions', 'savri-analytics'),
            array($this, 'render_field_track_forms'),
            'savri-analytics',
            'savri_analytics_enhanced'
        );

        // Scroll depth
        add_settings_field(
            'track_scroll',
            esc_html__('Scroll Depth', 'savri-analytics'),
            array($this, 'render_field_track_scroll'),
            'savri-analytics',
            'savri_analytics_enhanced'
        );

        // AI crawler tracking (server-side)
        add_settings_field(
            'track_ai_crawlers',
            esc_html__('AI Crawler Tracking', 'savri-analytics'),
            array($this, 'render_field_track_ai_crawlers'),
            'savri-analytics',
            'savri_analytics_enhanced'
        );

        // Site search tracking (WordPress native search)
        add_settings_field(
            'track_search',
            esc_html__('Site Search Tracking', 'savri-analytics'),
            array($this, 'render_field_track_search'),
            'savri-analytics',
            'savri_analytics_enhanced'
        );

        // Exclusions section
        add_settings_section(
            'savri_analytics_exclusions',
            esc_html__('Exclusions', 'savri-analytics'),
            array($this, 'render_section_exclusions'),
            'savri-analytics'
        );

        // Exclude admins
        add_settings_field(
            'exclude_admins',
            esc_html__('Exclude Administrators', 'savri-analytics'),
            array($this, 'render_field_exclude_admins'),
            'savri-analytics',
            'savri_analytics_exclusions'
        );
    }

    /**
     * Sanitize options
     */
    public function sanitize_options($input) {
        $sanitized = array();

        $sanitized['site_id'] = sanitize_text_field($input['site_id'] ?? '');

        // Restrict api_domain to known values to prevent open-redirect-style abuse
        $api_domain = sanitize_text_field($input['api_domain'] ?? 'savri.io');
        $sanitized['api_domain'] = array_key_exists($api_domain, self::ALLOWED_DOMAINS)
            ? $api_domain
            : 'savri.io';

        $sanitized['track_outbound'] = !empty($input['track_outbound']) ? 1 : 0;
        $sanitized['track_downloads'] = !empty($input['track_downloads']) ? 1 : 0;
        $sanitized['track_forms'] = !empty($input['track_forms']) ? 1 : 0;
        $sanitized['track_scroll'] = !empty($input['track_scroll']) ? 1 : 0;
        $sanitized['track_ai_crawlers'] = !empty($input['track_ai_crawlers']) ? 1 : 0;
        $sanitized['track_search'] = !empty($input['track_search']) ? 1 : 0;
        $sanitized['exclude_admins'] = !empty($input['exclude_admins']) ? 1 : 0;

        return $sanitized;
    }

    /**
     * Get configured API domain, validated against allowlist.
     * Read-side validation guards against legacy values stored before
     * the save-side allowlist existed in 1.0.0.
     */
    private function get_api_domain() {
        $domain = $this->options['api_domain'] ?? 'savri.io';
        return array_key_exists($domain, self::ALLOWED_DOMAINS) ? $domain : 'savri.io';
    }

    /**
     * Build a URL under the configured API domain.
     */
    private function build_url($path = '') {
        return 'https://' . $this->get_api_domain() . $path;
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $dashboard_link = '<a href="' . esc_url($this->build_url('/dashboard')) . '" target="_blank" rel="noopener noreferrer">Savri Dashboard</a>';
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <div style="background: #fff; border: 1px solid #ccd0d4; border-left: 4px solid #0073aa; padding: 12px; margin: 20px 0;">
                <p style="margin: 0;">
                    <strong><?php esc_html_e('Need a Site ID?', 'savri-analytics'); ?></strong><br>
                    <?php
                    echo wp_kses(
                        sprintf(
                            /* translators: %s: link to the Savri Dashboard */
                            esc_html__('Log in to your %s and copy the Site ID from your site settings.', 'savri-analytics'),
                            $dashboard_link
                        ),
                        array(
                            'a' => array(
                                'href' => array(),
                                'target' => array(),
                                'rel' => array(),
                            ),
                        )
                    );
                    ?>
                </p>
            </div>

            <form action="options.php" method="post">
                <?php
                settings_fields('savri_analytics');
                do_settings_sections('savri-analytics');
                submit_button();
                ?>
            </form>

            <hr>

            <h2><?php esc_html_e('About Savri Analytics', 'savri-analytics'); ?></h2>
            <p>
                <?php esc_html_e('Savri is a privacy-friendly analytics platform that helps you understand your website traffic without compromising visitor privacy.', 'savri-analytics'); ?>
            </p>
            <ul>
                <li>&#10003; <?php esc_html_e('No cookies, no cookie banner needed', 'savri-analytics'); ?></li>
                <li>&#10003; <?php esc_html_e('GDPR compliant by design', 'savri-analytics'); ?></li>
                <li>&#10003; <?php esc_html_e('Lightweight script (~2KB)', 'savri-analytics'); ?></li>
                <li>&#10003; <?php esc_html_e('Real-time dashboard', 'savri-analytics'); ?></li>
            </ul>

            <p>
                <a href="<?php echo esc_url($this->build_url()); ?>" target="_blank" rel="noopener noreferrer" class="button"><?php esc_html_e('Learn More', 'savri-analytics'); ?></a>
                <a href="<?php echo esc_url($this->build_url('/docs')); ?>" target="_blank" rel="noopener noreferrer" class="button"><?php esc_html_e('Documentation', 'savri-analytics'); ?></a>
            </p>
        </div>
        <?php
    }

    /**
     * Section renderers
     */
    public function render_section_main() {
        echo '<p>' . esc_html__('Enter your Savri Site ID to start tracking.', 'savri-analytics') . '</p>';
    }

    public function render_section_enhanced() {
        echo '<p>' . esc_html__('Enable additional tracking features. All features are privacy-friendly and GDPR compliant.', 'savri-analytics') . '</p>';
    }

    public function render_section_exclusions() {
        echo '<p>' . esc_html__('Choose what to exclude from tracking.', 'savri-analytics') . '</p>';
    }

    /**
     * Field renderers
     */
    public function render_field_site_id() {
        $value = $this->options['site_id'] ?? '';
        ?>
        <input type="text"
               name="savri_analytics_options[site_id]"
               value="<?php echo esc_attr($value); ?>"
               class="regular-text"
               placeholder="<?php esc_attr_e('e.g., abc123xyz', 'savri-analytics'); ?>">
        <p class="description">
            <?php esc_html_e('Find this in your Savri dashboard under Site Settings.', 'savri-analytics'); ?>
        </p>
        <?php
    }

    public function render_field_api_domain() {
        $value = $this->get_api_domain();
        ?>
        <select name="savri_analytics_options[api_domain]">
            <?php foreach (self::ALLOWED_DOMAINS as $domain => $label): ?>
                <option value="<?php echo esc_attr($domain); ?>" <?php selected($value, $domain); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description">
            <?php esc_html_e('Choose based on where you registered your account.', 'savri-analytics'); ?>
        </p>
        <?php
    }

    public function render_field_track_outbound() {
        $checked = !empty($this->options['track_outbound']);
        ?>
        <label>
            <input type="checkbox"
                   name="savri_analytics_options[track_outbound]"
                   value="1"
                   <?php checked($checked); ?>>
            <?php esc_html_e('Track clicks on external links', 'savri-analytics'); ?>
        </label>
        <?php
    }

    public function render_field_track_downloads() {
        $checked = !empty($this->options['track_downloads']);
        ?>
        <label>
            <input type="checkbox"
                   name="savri_analytics_options[track_downloads]"
                   value="1"
                   <?php checked($checked); ?>>
            <?php esc_html_e('Track file downloads (PDF, ZIP, etc.)', 'savri-analytics'); ?>
        </label>
        <?php
    }

    public function render_field_track_forms() {
        $checked = !empty($this->options['track_forms']);
        ?>
        <label>
            <input type="checkbox"
                   name="savri_analytics_options[track_forms]"
                   value="1"
                   <?php checked($checked); ?>>
            <?php esc_html_e('Track form submissions', 'savri-analytics'); ?>
        </label>
        <?php
    }

    public function render_field_track_scroll() {
        $checked = !empty($this->options['track_scroll']);
        ?>
        <label>
            <input type="checkbox"
                   name="savri_analytics_options[track_scroll]"
                   value="1"
                   <?php checked($checked); ?>>
            <?php esc_html_e('Track scroll depth (25%, 50%, 75%, 100%)', 'savri-analytics'); ?>
        </label>
        <?php
    }

    public function render_field_track_ai_crawlers() {
        $checked = $this->is_ai_tracking_enabled();
        ?>
        <label>
            <input type="checkbox"
                   name="savri_analytics_options[track_ai_crawlers]"
                   value="1"
                   <?php checked($checked); ?>>
            <?php esc_html_e('Report visits from AI crawlers (GPTBot, ClaudeBot, PerplexityBot and more)', 'savri-analytics'); ?>
        </label>
        <p class="description">
            <?php esc_html_e('AI bots do not run JavaScript, so they are invisible to the regular tracking script. This server-side reporting shows them under AI Insights in your Savri dashboard. Enabled by default.', 'savri-analytics'); ?>
        </p>
        <?php
    }

    public function render_field_track_search() {
        $checked = $this->is_search_tracking_enabled();
        ?>
        <label>
            <input type="checkbox"
                   name="savri_analytics_options[track_search]"
                   value="1"
                   <?php checked($checked); ?>>
            <?php esc_html_e('Track WordPress site searches (queries, zero-result searches and result clicks)', 'savri-analytics'); ?>
        </label>
        <p class="description">
            <?php esc_html_e('Shows what visitors search for on your site under Site Search in your Savri dashboard, including searches that returned no results. Enabled by default.', 'savri-analytics'); ?>
        </p>
        <?php
    }

    public function render_field_exclude_admins() {
        $checked = !empty($this->options['exclude_admins']);
        ?>
        <label>
            <input type="checkbox"
                   name="savri_analytics_options[exclude_admins]"
                   value="1"
                   <?php checked($checked); ?>>
            <?php esc_html_e('Do not track logged-in administrators', 'savri-analytics'); ?>
        </label>
        <p class="description">
            <?php esc_html_e('Recommended to avoid skewing your statistics.', 'savri-analytics'); ?>
        </p>
        <?php
    }

    /**
     * AI crawler tracking is on by default: installs that saved their
     * settings before 1.1.0 have no 'track_ai_crawlers' key at all, and
     * those should report. Only an explicit unchecked save (0) disables it.
     */
    private function is_ai_tracking_enabled() {
        if (!array_key_exists('track_ai_crawlers', $this->options)) {
            return true;
        }
        return !empty($this->options['track_ai_crawlers']);
    }

    /**
     * Site search tracking is on by default, same opt-out pattern as
     * AI crawler tracking: installs saved before 1.2.0 have no
     * 'track_search' key and should track. Only an explicit 0 disables.
     */
    private function is_search_tracking_enabled() {
        if (!array_key_exists('track_search', $this->options)) {
            return true;
        }
        return !empty($this->options['track_search']);
    }

    /**
     * Normalize a search query the way the Savri site-search standard
     * expects: trimmed, lowercased, capped at 100 characters.
     */
    private function normalize_search_query($query) {
        $query = trim($query);
        if (function_exists('mb_strtolower')) {
            $query = mb_strtolower($query);
            $query = mb_substr($query, 0, 100);
        } else {
            $query = strtolower($query);
            $query = substr($query, 0, 100);
        }
        return $query;
    }

    /**
     * Report AI crawler visits server-side.
     *
     * AI bots (GPTBot, ClaudeBot, PerplexityBot ...) never execute the JS
     * tracker, so they are invisible to regular analytics. This hook checks
     * the User-Agent of every frontend request and, on a match, sends a
     * non-blocking POST to the Savri API. 'blocking' => false means WordPress
     * fires the request and moves on immediately: page loads are never
     * delayed, even if the API were slow or unreachable.
     */
    public function report_ai_crawler() {
        $site_id = $this->options['site_id'] ?? '';

        if (empty($site_id) || !$this->is_ai_tracking_enabled()) {
            return;
        }

        if (is_admin() || is_preview()) {
            return;
        }

        $ua = isset($_SERVER['HTTP_USER_AGENT'])
            ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT']))
            : '';
        if ('' === $ua) {
            return;
        }

        $matched = false;
        foreach (self::AI_CRAWLER_PATTERNS as $pattern) {
            if (stripos($ua, $pattern) !== false) {
                $matched = true;
                break;
            }
        }
        if (!$matched) {
            return;
        }

        $pathname = isset($_SERVER['REQUEST_URI'])
            ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']))
            : '/';

        // The crawler's IP as seen by this server (since 1.3.0). Savri runs a
        // reverse-DNS check on it to confirm the visit really came from the
        // AI company: a User-Agent string can be faked, a forward-confirmed
        // PTR record cannot. Without it every visit is stored as unverifiable.
        // REMOTE_ADDR on purpose, never X-Forwarded-For (the client controls
        // that header). Behind a proxy/CDN that does not restore the real IP
        // this is the proxy address and the verdict cannot be positive.
        $ip = isset($_SERVER['REMOTE_ADDR'])
            ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']))
            : '';

        wp_remote_post($this->build_url('/api/ai-crawl'), array(
            'blocking' => false,
            'timeout'  => 1,
            'headers'  => array('Content-Type' => 'application/json'),
            'body'     => wp_json_encode(array(
                'siteId'    => $site_id,
                'pathname'  => $pathname,
                'userAgent' => $ua,
                'ip'        => '' !== $ip ? $ip : null,
            )),
        ));
    }

    /**
     * Inject tracking script
     */
    public function inject_tracking_script() {
        $site_id = $this->options['site_id'] ?? '';

        if (empty($site_id)) {
            return;
        }

        if (!empty($this->options['exclude_admins']) && current_user_can('manage_options')) {
            return;
        }

        if (is_admin() || is_preview()) {
            return;
        }

        $script_url = esc_url($this->build_url('/script.js'));
        $api_url = esc_url($this->build_url());
        $escaped_site_id = esc_attr($site_id);

        $enabled_flags = array();
        foreach (self::TRACKING_FLAGS as $option_key => $data_attr) {
            if (!empty($this->options[$option_key])) {
                $enabled_flags[] = $data_attr;
            }
        }

        // Keys are hardcoded literals, no escaping needed on them.
        $tag = '<script defer'
            . ' src="' . $script_url . '"'
            . ' data-site-id="' . $escaped_site_id . '"'
            . ' data-api="' . $api_url . '"';
        foreach ($enabled_flags as $flag) {
            $tag .= ' ' . $flag;
        }
        $tag .= '></script>';

        $script_allowlist = array(
            'src'           => array(),
            'defer'         => array(),
            'data-site-id'  => array(),
            'data-api'      => array(),
        );
        foreach (self::TRACKING_FLAGS as $data_attr) {
            $script_allowlist[$data_attr] = array();
        }

        echo "\n<!-- Savri Analytics -->\n";

        // 404 tracking: script.js sends a 404 event when this meta is present.
        if (is_404()) {
            echo '<meta name="va-404" content="1">' . "\n";
        }

        echo wp_kses($tag, array('script' => $script_allowlist)) . "\n";

        if (is_search() && $this->is_search_tracking_enabled()) {
            $this->inject_search_tracking();
        }
    }

    /**
     * Site search tracking on WordPress native search result pages.
     *
     * Sends the Savri site-search standard events through the tracker's
     * public va() API: 'site_search' (query, results_count, source) on every
     * search, 'site_search_zero' when the search returned nothing, and
     * 'site_search_click' when the visitor clicks a result. The result count
     * comes from the main query server-side, so no DOM guessing is needed.
     * script.js is deferred, hence window.va exists by the load event.
     */
    private function inject_search_tracking() {
        global $wp_query;

        $query = $this->normalize_search_query(get_search_query(false));
        if ('' === $query) {
            return;
        }

        $results_count = isset($wp_query->found_posts) ? (int) $wp_query->found_posts : 0;

        $js = '(function(){'
            . 'var q=' . wp_json_encode($query) . ',n=' . $results_count . ';'
            . 'function fire(){'
            . 'if(!window.va)return;'
            . "va('event','site_search',{query:q,results_count:n,source:'wordpress'});"
            . "if(n===0)va('event','site_search_zero',{query:q,source:'wordpress'});"
            . '}'
            . "if(document.readyState==='complete'){fire();}else{window.addEventListener('load',fire);}"
            . "document.addEventListener('click',function(e){"
            . 'var t=e.target;'
            . 'if(!t||!t.closest)return;'
            . "var link=t.closest('article a,.hentry a,.search-results .post a');"
            . "if(link&&window.va)va('event','site_search_click',{query:q,source:'wordpress'});"
            . '});'
            . '})();';

        if (function_exists('wp_print_inline_script_tag')) {
            wp_print_inline_script_tag($js);
        } else {
            echo '<script>' . $js . '</script>' . "\n"; // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript
        }
    }
}

// Initialize plugin
new Savri_Analytics();
