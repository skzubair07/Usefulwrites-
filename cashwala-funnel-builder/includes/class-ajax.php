<?php

if (! defined('ABSPATH')) {
    exit;
}

class CWFB_Ajax
{
    public function hooks()
    {
        add_action('wp_ajax_cwfb_track_visit', array($this, 'track_visit'));
        add_action('wp_ajax_nopriv_cwfb_track_visit', array($this, 'track_visit'));
        add_action('wp_ajax_cwfb_track_conversion', array($this, 'track_conversion'));
        add_action('wp_ajax_nopriv_cwfb_track_conversion', array($this, 'track_conversion'));
    }

    public function track_visit()
    {
        $this->validate_nonce();

        $funnel_id = isset($_POST['funnel_id']) ? absint($_POST['funnel_id']) : 0;
        $step = isset($_POST['step']) ? sanitize_key($_POST['step']) : '';

        if (! $funnel_id || ! in_array($step, array('landing', 'checkout', 'thankyou'), true)) {
            wp_send_json_error(array('message' => __('Invalid tracking payload.', 'cashwala-funnel-builder')), 400);
        }

        $cookie_key = 'cwfb_v_' . $funnel_id . '_' . $step;
        if (empty($_COOKIE[$cookie_key])) {
            CWFB_DB::increment_visit($funnel_id, $step);
            setcookie($cookie_key, '1', time() + HOUR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
        }

        wp_send_json_success(array('tracked' => true));
    }

    public function track_conversion()
    {
        $this->validate_nonce();

        $funnel_id = isset($_POST['funnel_id']) ? absint($_POST['funnel_id']) : 0;
        $step = isset($_POST['step']) ? sanitize_key($_POST['step']) : '';

        if (! $funnel_id || ! in_array($step, array('landing', 'checkout', 'thankyou'), true)) {
            wp_send_json_error(array('message' => __('Invalid conversion payload.', 'cashwala-funnel-builder')), 400);
        }

        CWFB_DB::increment_conversion($funnel_id, $step);

        wp_send_json_success(array('tracked' => true));
    }

    private function validate_nonce()
    {
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (! wp_verify_nonce($nonce, 'cwfb_track_nonce')) {
            wp_send_json_error(array('message' => __('Invalid nonce.', 'cashwala-funnel-builder')), 403);
        }
    }
}
