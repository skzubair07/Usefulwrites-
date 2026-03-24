<?php
if (! defined('ABSPATH')) {
    exit;
}

class CWCR_Ajax
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
        add_action('wp_ajax_cwcr_submit_lead', [$this, 'submit_lead']);
        add_action('wp_ajax_nopriv_cwcr_submit_lead', [$this, 'submit_lead']);
        add_action('wp_ajax_cwcr_track_reveal', [$this, 'track_reveal']);
        add_action('wp_ajax_nopriv_cwcr_track_reveal', [$this, 'track_reveal']);
    }

    public function submit_lead()
    {
        check_ajax_referer('cwcr_nonce', 'nonce');

        $settings = wp_parse_args(get_option('cwcr_settings', []), CWCR_DB::default_settings());

        $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
        $phone = isset($_POST['phone']) ? sanitize_text_field(wp_unslash($_POST['phone'])) : '';

        if (! empty($settings['lead_required'])) {
            if (! empty($settings['lead_email']) && empty($email)) {
                wp_send_json_error(['message' => __('Email is required.', 'cashwala-coupon-reveal')], 422);
            }

            if (! empty($settings['lead_phone']) && empty($phone)) {
                wp_send_json_error(['message' => __('Phone is required.', 'cashwala-coupon-reveal')], 422);
            }
        }

        if (! empty($email) && ! is_email($email)) {
            wp_send_json_error(['message' => __('Invalid email address.', 'cashwala-coupon-reveal')], 422);
        }

        $coupon = CWCR_Frontend::instance()->pick_coupon();
        $saved = CWCR_DB::insert_lead($email, $phone, $coupon);

        if (! $saved) {
            wp_send_json_error(['message' => __('Could not save lead.', 'cashwala-coupon-reveal')], 500);
        }

        CWCR_Frontend::instance()->track('conversions');
        CWCR_Frontend::instance()->track('reveals');

        wp_send_json_success([
            'coupon'         => esc_html($coupon),
            'expiresIn'      => absint($settings['expiry_minutes']) * 60,
            'messageAfter'   => sanitize_text_field($settings['message_after']),
        ]);
    }

    public function track_reveal()
    {
        check_ajax_referer('cwcr_nonce', 'nonce');
        CWCR_Frontend::instance()->track('reveals');
        wp_send_json_success(['tracked' => true]);
    }
}
