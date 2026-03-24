<?php
if (!defined('ABSPATH')) {
    exit;
}

class CWFB_Frontend {
    private $funnels_table;

    public function __construct() {
        global $wpdb;
        $this->funnels_table = $wpdb->prefix . 'cw_funnels';

        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('template_redirect', array($this, 'bootstrap_funnel_tracking'));
        add_filter('the_content', array($this, 'inject_next_step_cta'));
        add_filter('body_class', array($this, 'add_step_body_class'));
        add_shortcode('cwfb_funnel', array($this, 'render_funnel_shortcode'));
    }

    public function enqueue_assets() {
        wp_enqueue_style('cwfb-style', CWFB_URL . 'assets/css/style.css', array(), CWFB_VERSION);
        wp_enqueue_script('cwfb-script', CWFB_URL . 'assets/js/script.js', array('jquery'), CWFB_VERSION, true);

        wp_localize_script(
            'cwfb-script',
            'CWFB',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('cwfb_track_nonce'),
            )
        );
    }

    public function bootstrap_funnel_tracking() {
        if (!is_singular('page')) {
            return;
        }

        $funnel_id = isset($_GET['cwfunnel']) ? absint($_GET['cwfunnel']) : 0;
        if ($funnel_id < 1) {
            return;
        }

        $funnel = $this->get_active_funnel($funnel_id);
        if (!$funnel) {
            return;
        }

        $page_id = get_queried_object_id();
        $step    = $this->resolve_step($funnel, $page_id);

        if (!$step) {
            return;
        }

        if (!isset($_COOKIE['cwfb_uid'])) {
            $uid = wp_generate_uuid4();
            setcookie('cwfb_uid', $uid, time() + MONTH_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
            $_COOKIE['cwfb_uid'] = $uid;
        }

        setcookie('cwfb_funnel_id', (string) $funnel_id, time() + DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
        $_COOKIE['cwfb_funnel_id'] = (string) $funnel_id;

        if ('thankyou' === $step && !isset($_COOKIE['cwfb_conv_' . $funnel_id])) {
            setcookie('cwfb_conv_' . $funnel_id, '1', time() + DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
        }
    }

    public function inject_next_step_cta($content) {
        if (!is_singular('page')) {
            return $content;
        }

        $funnel_id = isset($_GET['cwfunnel']) ? absint($_GET['cwfunnel']) : 0;
        if ($funnel_id < 1) {
            return $content;
        }

        $funnel = $this->get_active_funnel($funnel_id);
        if (!$funnel) {
            return $content;
        }

        $page_id = get_queried_object_id();
        $step    = $this->resolve_step($funnel, $page_id);
        if ('landing' === $step) {
            $next = add_query_arg('cwfunnel', $funnel_id, get_permalink($funnel['checkout_page_id']));
            $content .= '<p><a class="cwfb-next-btn" href="' . esc_url($next) . '">' . esc_html__('Continue to Checkout', 'cashwala-funnel-builder') . '</a></p>';
        } elseif ('checkout' === $step) {
            $next = add_query_arg('cwfunnel', $funnel_id, get_permalink($funnel['thankyou_page_id']));
            $content .= '<p><a class="cwfb-next-btn" href="' . esc_url($next) . '">' . esc_html__('Complete Order', 'cashwala-funnel-builder') . '</a></p>';
        }

        return $content;
    }


    public function add_step_body_class($classes) {
        if (!is_singular('page')) {
            return $classes;
        }

        $funnel_id = isset($_GET['cwfunnel']) ? absint($_GET['cwfunnel']) : 0;
        if ($funnel_id < 1) {
            return $classes;
        }

        $funnel = $this->get_active_funnel($funnel_id);
        if (!$funnel) {
            return $classes;
        }

        $step = $this->resolve_step($funnel, get_queried_object_id());
        if ($step) {
            $classes[] = 'cwfb-step-' . $step;
        }

        return $classes;
    }
    public function render_funnel_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0, 'step' => 'landing'), $atts);
        $funnel_id = absint($atts['id']);
        $step = sanitize_key($atts['step']);

        if ($funnel_id < 1) {
            return '';
        }

        $funnel = $this->get_active_funnel($funnel_id);
        if (!$funnel) {
            return '';
        }

        $allowed_steps = array('landing', 'checkout', 'thankyou');
        if (!in_array($step, $allowed_steps, true)) {
            return '';
        }

        ob_start();
        $template = CWFB_PATH . 'templates/step-' . $step . '.php';
        if (file_exists($template)) {
            include $template;
        }
        return ob_get_clean();
    }

    private function get_active_funnel($funnel_id) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->funnels_table} WHERE id = %d AND status = 'active'", $funnel_id),
            ARRAY_A
        ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
    }

    private function resolve_step($funnel, $page_id) {
        if ((int) $funnel['landing_page_id'] === (int) $page_id) {
            return 'landing';
        }

        if ((int) $funnel['checkout_page_id'] === (int) $page_id) {
            return 'checkout';
        }

        if ((int) $funnel['thankyou_page_id'] === (int) $page_id) {
            return 'thankyou';
        }

        return '';
    }
}
