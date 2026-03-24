<?php

if (!defined('ABSPATH')) {
    exit;
}

class CW_SB_Frontend {
    private $settings;

    public function init() {
        $this->settings = CW_SB_Core::get_settings();
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('wp_footer', array($this, 'render_bar'));
    }

    public function should_display() {
        if (is_admin() || !$this->settings['enabled']) {
            return false;
        }

        if (!empty($this->settings['target_pages']) && is_page()) {
            $current_id = get_queried_object_id();
            if (!in_array($current_id, $this->settings['target_pages'], true)) {
                return false;
            }
        } elseif (!empty($this->settings['target_pages']) && !is_page()) {
            return false;
        }

        $is_mobile = wp_is_mobile();
        if ($this->settings['device_targeting'] === 'mobile' && !$is_mobile) {
            return false;
        }
        if ($this->settings['device_targeting'] === 'desktop' && $is_mobile) {
            return false;
        }

        return true;
    }

    public function enqueue_assets() {
        if (!$this->should_display()) {
            return;
        }

        wp_enqueue_style('cw-sb-style', CW_SB_URL . 'assets/css/style.css', array(), CW_SB_VERSION);
        wp_enqueue_script('cw-sb-script', CW_SB_URL . 'assets/js/script.js', array(), CW_SB_VERSION, true);

        wp_localize_script('cw-sb-script', 'cwStickyBar', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cw_sb_ajax_nonce'),
            'showTrigger' => $this->settings['show_trigger'],
            'showDelay' => (int) $this->settings['show_delay'],
            'showScrollPercent' => (int) $this->settings['show_scroll_percent'],
            'hideOnScroll' => (int) $this->settings['hide_on_scroll'],
            'rotationSpeed' => (int) $this->settings['rotation_speed'],
            'messages' => array_values($this->settings['messages']),
            'countdownEnabled' => (int) $this->settings['countdown_enabled'],
            'timerDuration' => (int) $this->settings['timer_duration'],
            'closeEnabled' => (int) $this->settings['close_enabled'],
            'reappearAfter' => (int) $this->settings['reappear_after'],
            'position' => $this->settings['position'],
        ));
    }

    public function render_bar() {
        if (!$this->should_display()) {
            return;
        }

        CW_SB_Core::increment_metric('views');

        $template = $this->settings['template'] === 'template-2' ? 'bar-template-2.php' : 'bar-template-1.php';
        $template_file = CW_SB_DIR . 'templates/' . $template;

        if (!file_exists($template_file)) {
            CW_SB_Logger::log('Template file not found', array('file' => $template_file));
            return;
        }

        $style_vars = sprintf(
            '--cw-bg:%1$s;--cw-text:%2$s;--cw-btn-bg:%3$s;--cw-btn-text:%4$s;--cw-font-size:%5$spx;--cw-padding:%6$spx;--cw-radius:%7$spx;',
            esc_attr($this->settings['background_color']),
            esc_attr($this->settings['text_color']),
            esc_attr($this->settings['button_bg_color']),
            esc_attr($this->settings['button_text_color']),
            (int) $this->settings['font_size'],
            (int) $this->settings['padding'],
            (int) $this->settings['border_radius']
        );

        include $template_file;
    }

    public static function build_button_url($button) {
        if ($button['type'] === 'whatsapp') {
            $phone = preg_replace('/[^0-9]/', '', $button['value']);
            return 'https://wa.me/' . $phone;
        }

        if ($button['type'] === 'call') {
            $phone = preg_replace('/[^0-9+]/', '', $button['value']);
            return 'tel:' . $phone;
        }

        return esc_url_raw($button['value']);
    }
}
