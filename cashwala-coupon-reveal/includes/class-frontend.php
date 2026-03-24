<?php
if (! defined('ABSPATH')) {
    exit;
}

class CWCR_Frontend
{
    private static $instance;

    public static function instance()
    {
        if (! self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init()
    {
        add_shortcode('cashwala_coupon_reveal', [$this, 'render_inline_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
        add_action('wp_footer', [$this, 'maybe_render_popup']);
    }

    public function register_assets()
    {
        wp_register_style('cwcr-style', CWCR_URL . 'assets/css/style.css', [], CWCR_VERSION);
        wp_register_script('cwcr-script', CWCR_URL . 'assets/js/script.js', [], CWCR_VERSION, true);
    }

    private function should_render()
    {
        $settings = wp_parse_args(get_option('cwcr_settings', []), CWCR_DB::default_settings());
        return ! empty($settings['enabled']);
    }

    private function enqueue_assets($context = 'popup')
    {
        $settings = wp_parse_args(get_option('cwcr_settings', []), CWCR_DB::default_settings());

        wp_enqueue_style('cwcr-style');
        wp_enqueue_script('cwcr-script');

        wp_localize_script('cwcr-script', 'cwcrData', [
            'ajaxUrl'        => admin_url('admin-ajax.php'),
            'nonce'          => wp_create_nonce('cwcr_nonce'),
            'context'        => $context,
            'displayMode'    => $settings['display_mode'],
            'triggerType'    => $settings['trigger_type'],
            'triggerDelay'   => absint($settings['trigger_delay']),
            'triggerScroll'  => absint($settings['trigger_scroll']),
            'exitIntent'     => ! empty($settings['trigger_exit_intent']),
            'revealAction'   => $settings['reveal_action'],
            'expiryMinutes'  => absint($settings['expiry_minutes']),
            'leadEmail'      => ! empty($settings['lead_email']),
            'leadPhone'      => ! empty($settings['lead_phone']),
            'leadRequired'   => ! empty($settings['lead_required']),
            'messages'       => [
                'copied'  => __('Coupon copied to clipboard!', 'cashwala-coupon-reveal'),
                'failure' => __('Could not copy coupon code. Please copy manually.', 'cashwala-coupon-reveal'),
            ],
        ]);
    }

    public function maybe_render_popup()
    {
        if (! $this->should_render()) {
            return;
        }

        $settings = wp_parse_args(get_option('cwcr_settings', []), CWCR_DB::default_settings());
        if ('popup' !== $settings['display_mode']) {
            return;
        }

        $this->enqueue_assets('popup');
        $this->track('views');

        include CWCR_PATH . 'templates/popup-template.php';
    }

    public function render_inline_shortcode($atts)
    {
        if (! $this->should_render()) {
            return '';
        }

        $this->enqueue_assets('inline');
        $this->track('views');

        $settings = wp_parse_args(get_option('cwcr_settings', []), CWCR_DB::default_settings());

        ob_start();
        include CWCR_PATH . 'templates/inline-template.php';
        return ob_get_clean();
    }

    public function pick_coupon()
    {
        $settings = wp_parse_args(get_option('cwcr_settings', []), CWCR_DB::default_settings());
        $codes = preg_split('/\r\n|\r|\n/', (string) $settings['coupon_codes']);
        $codes = array_values(array_filter(array_map('trim', $codes)));

        if (empty($codes)) {
            return 'SAVE10';
        }

        if (! empty($settings['dynamic_coupon'])) {
            return $codes[array_rand($codes)];
        }

        return $codes[0];
    }

    public function track($metric)
    {
        $allowed = ['views', 'reveals', 'conversions'];
        if (! in_array($metric, $allowed, true)) {
            return;
        }

        $analytics = wp_parse_args(get_option('cwcr_analytics', []), [
            'views'       => 0,
            'reveals'     => 0,
            'conversions' => 0,
        ]);

        $analytics[$metric] = absint($analytics[$metric]) + 1;
        update_option('cwcr_analytics', $analytics, false);
    }
}
