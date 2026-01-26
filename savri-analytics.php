<?php
/**
 * Plugin Name: Savri Analytics
 * Plugin URI: https://savri.io/docs/integrations/wordpress
 * Description: Privacy-friendly website analytics. No cookies, GDPR compliant.
 * Version: 1.0.0
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

    private $options;

    public function __construct() {
        $this->options = get_option('savri_analytics_options', array());

        // Admin hooks
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_settings'));

        // Frontend hooks
        add_action('wp_head', array($this, 'inject_tracking_script'), 1);

        // Plugin action links
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_action_links'));
    }

    /**
     * Add settings link to plugins page
     */
    public function add_action_links($links) {
        $settings_link = '<a href="options-general.php?page=savri-analytics">' . __('Settings', 'savri-analytics') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            __('Savri Analytics', 'savri-analytics'),
            __('Savri Analytics', 'savri-analytics'),
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
            __('Configuration', 'savri-analytics'),
            array($this, 'render_section_main'),
            'savri-analytics'
        );

        // Site ID field
        add_settings_field(
            'site_id',
            __('Site ID', 'savri-analytics'),
            array($this, 'render_field_site_id'),
            'savri-analytics',
            'savri_analytics_main'
        );

        // API Domain field
        add_settings_field(
            'api_domain',
            __('API Domain', 'savri-analytics'),
            array($this, 'render_field_api_domain'),
            'savri-analytics',
            'savri_analytics_main'
        );

        // Enhanced tracking section
        add_settings_section(
            'savri_analytics_enhanced',
            __('Enhanced Tracking', 'savri-analytics'),
            array($this, 'render_section_enhanced'),
            'savri-analytics'
        );

        // Outbound links
        add_settings_field(
            'track_outbound',
            __('Outbound Links', 'savri-analytics'),
            array($this, 'render_field_track_outbound'),
            'savri-analytics',
            'savri_analytics_enhanced'
        );

        // File downloads
        add_settings_field(
            'track_downloads',
            __('File Downloads', 'savri-analytics'),
            array($this, 'render_field_track_downloads'),
            'savri-analytics',
            'savri_analytics_enhanced'
        );

        // Form submissions
        add_settings_field(
            'track_forms',
            __('Form Submissions', 'savri-analytics'),
            array($this, 'render_field_track_forms'),
            'savri-analytics',
            'savri_analytics_enhanced'
        );

        // Scroll depth
        add_settings_field(
            'track_scroll',
            __('Scroll Depth', 'savri-analytics'),
            array($this, 'render_field_track_scroll'),
            'savri-analytics',
            'savri_analytics_enhanced'
        );

        // Exclusions section
        add_settings_section(
            'savri_analytics_exclusions',
            __('Exclusions', 'savri-analytics'),
            array($this, 'render_section_exclusions'),
            'savri-analytics'
        );

        // Exclude admins
        add_settings_field(
            'exclude_admins',
            __('Exclude Administrators', 'savri-analytics'),
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
        $sanitized['api_domain'] = sanitize_text_field($input['api_domain'] ?? 'savri.io');
        $sanitized['track_outbound'] = !empty($input['track_outbound']) ? 1 : 0;
        $sanitized['track_downloads'] = !empty($input['track_downloads']) ? 1 : 0;
        $sanitized['track_forms'] = !empty($input['track_forms']) ? 1 : 0;
        $sanitized['track_scroll'] = !empty($input['track_scroll']) ? 1 : 0;
        $sanitized['exclude_admins'] = !empty($input['exclude_admins']) ? 1 : 0;

        return $sanitized;
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <?php
            $api_domain = $this->options['api_domain'] ?? 'savri.io';
            ?>
            <div style="background: #fff; border: 1px solid #ccd0d4; border-left: 4px solid #0073aa; padding: 12px; margin: 20px 0;">
                <p style="margin: 0;">
                    <strong><?php _e('Need a Site ID?', 'savri-analytics'); ?></strong><br>
                    <?php printf(
                        __('Log in to your %s and copy the Site ID from your site settings.', 'savri-analytics'),
                        '<a href="https://' . esc_attr($api_domain) . '/dashboard" target="_blank">Savri Dashboard</a>'
                    ); ?>
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

            <h2><?php _e('About Savri Analytics', 'savri-analytics'); ?></h2>
            <p>
                <?php _e('Savri is a privacy-friendly analytics platform that helps you understand your website traffic without compromising visitor privacy.', 'savri-analytics'); ?>
            </p>
            <ul>
                <li>✓ <?php _e('No cookies - no cookie banner needed', 'savri-analytics'); ?></li>
                <li>✓ <?php _e('GDPR compliant by design', 'savri-analytics'); ?></li>
                <li>✓ <?php _e('Lightweight script (~2KB)', 'savri-analytics'); ?></li>
                <li>✓ <?php _e('Real-time dashboard', 'savri-analytics'); ?></li>
            </ul>

            <p>
                <a href="https://<?php echo esc_attr($api_domain); ?>" target="_blank" class="button"><?php _e('Learn More', 'savri-analytics'); ?></a>
                <a href="https://<?php echo esc_attr($api_domain); ?>/docs" target="_blank" class="button"><?php _e('Documentation', 'savri-analytics'); ?></a>
            </p>
        </div>
        <?php
    }

    /**
     * Section renderers
     */
    public function render_section_main() {
        echo '<p>' . __('Enter your Savri Site ID to start tracking.', 'savri-analytics') . '</p>';
    }

    public function render_section_enhanced() {
        echo '<p>' . __('Enable additional tracking features. All features are privacy-friendly and GDPR compliant.', 'savri-analytics') . '</p>';
    }

    public function render_section_exclusions() {
        echo '<p>' . __('Choose what to exclude from tracking.', 'savri-analytics') . '</p>';
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
               placeholder="e.g., abc123xyz">
        <p class="description">
            <?php _e('Find this in your Savri dashboard under Site Settings.', 'savri-analytics'); ?>
        </p>
        <?php
    }

    public function render_field_api_domain() {
        $value = $this->options['api_domain'] ?? 'savri.io';
        ?>
        <select name="savri_analytics_options[api_domain]">
            <option value="savri.io" <?php selected($value, 'savri.io'); ?>>savri.io (International)</option>
            <option value="besokskollen.se" <?php selected($value, 'besokskollen.se'); ?>>besokskollen.se (Sweden)</option>
        </select>
        <p class="description">
            <?php _e('Choose based on where you registered your account.', 'savri-analytics'); ?>
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
            <?php _e('Track clicks on external links', 'savri-analytics'); ?>
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
            <?php _e('Track file downloads (PDF, ZIP, etc.)', 'savri-analytics'); ?>
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
            <?php _e('Track form submissions', 'savri-analytics'); ?>
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
            <?php _e('Track scroll depth (25%, 50%, 75%, 100%)', 'savri-analytics'); ?>
        </label>
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
            <?php _e('Don\'t track logged-in administrators', 'savri-analytics'); ?>
        </label>
        <p class="description">
            <?php _e('Recommended to avoid skewing your statistics.', 'savri-analytics'); ?>
        </p>
        <?php
    }

    /**
     * Inject tracking script
     */
    public function inject_tracking_script() {
        $site_id = $this->options['site_id'] ?? '';

        // Don't inject if no site ID
        if (empty($site_id)) {
            return;
        }

        // Don't track admins if excluded
        if (!empty($this->options['exclude_admins']) && current_user_can('manage_options')) {
            return;
        }

        // Don't track in admin area
        if (is_admin()) {
            return;
        }

        // Don't track preview pages
        if (is_preview()) {
            return;
        }

        // Get API domain (default to savri.io)
        $api_domain = $this->options['api_domain'] ?? 'savri.io';

        // Build data attributes
        $attributes = array(
            'defer' => true,
            'data-site-id' => esc_attr($site_id),
            'data-api' => 'https://' . esc_attr($api_domain),
            'src' => 'https://' . esc_attr($api_domain) . '/script.js'
        );

        if (!empty($this->options['track_outbound'])) {
            $attributes['data-outbound-links'] = true;
        }

        if (!empty($this->options['track_downloads'])) {
            $attributes['data-file-downloads'] = true;
        }

        if (!empty($this->options['track_forms'])) {
            $attributes['data-forms'] = true;
        }

        if (!empty($this->options['track_scroll'])) {
            $attributes['data-scroll-depth'] = true;
        }

        // Build script tag
        $script = '<script';
        foreach ($attributes as $key => $value) {
            if ($value === true) {
                $script .= ' ' . $key;
            } else {
                $script .= ' ' . $key . '="' . $value . '"';
            }
        }
        $script .= '></script>';

        echo "\n<!-- Savri Analytics -->\n" . $script . "\n";
    }
}

// Initialize plugin
new Savri_Analytics();
