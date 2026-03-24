<?php

if (!defined('ABSPATH')) {
    exit;
}

class CW_SB_Ajax {
    public function init() {
        add_action('wp_ajax_cw_sb_track_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_cw_sb_track_click', array($this, 'track_click'));
    }

    public function track_click() {
        check_ajax_referer('cw_sb_ajax_nonce', 'nonce');

        $button_text = isset($_POST['button_text']) ? sanitize_text_field(wp_unslash($_POST['button_text'])) : '';
        CW_SB_Core::increment_metric('clicks');

        if ($button_text !== '') {
            CW_SB_Logger::log('CTA click tracked', array('button' => $button_text, 'ip_hash' => wp_hash($_SERVER['REMOTE_ADDR'] ?? '')));
        }

        wp_send_json_success(array('message' => 'Click tracked'));
    }
}
