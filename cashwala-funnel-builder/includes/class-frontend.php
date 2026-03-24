<?php

if (! defined('ABSPATH')) {
    exit;
}

class CWFB_Frontend
{
    public function hooks()
    {
        add_action('wp_enqueue_scripts', array($this, 'assets'));
        add_filter('the_content', array($this, 'append_step_navigation'));
        add_action('template_redirect', array($this, 'handle_conversion_by_transition'));
    }

    public function assets()
    {
        if (! is_singular('page')) {
            return;
        }

        $funnel_context = $this->get_current_funnel_context();
        if (empty($funnel_context)) {
            return;
        }

        wp_enqueue_style('cwfb-style', CWFB_PLUGIN_URL . 'assets/css/style.css', array(), CWFB_VERSION);
        wp_enqueue_script('cwfb-script', CWFB_PLUGIN_URL . 'assets/js/script.js', array('jquery'), CWFB_VERSION, true);

        wp_localize_script('cwfb-script', 'CWFunnel', array(
            'ajax_url'  => admin_url('admin-ajax.php'),
            'nonce'     => wp_create_nonce('cwfb_track_nonce'),
            'funnel_id' => (int) $funnel_context['id'],
            'step'      => $funnel_context['step'],
        ));
    }

    public function append_step_navigation($content)
    {
        if (! is_singular('page') || ! in_the_loop() || ! is_main_query()) {
            return $content;
        }

        $funnel_context = $this->get_current_funnel_context();
        if (empty($funnel_context)) {
            return $content;
        }

        $template_file = CWFB_PLUGIN_PATH . 'templates/step-' . $funnel_context['step'] . '.php';
        if (! file_exists($template_file)) {
            return $content;
        }

        $funnel = $funnel_context['funnel'];
        $next_url = '';

        if ($funnel_context['step'] === 'landing' && ! empty($funnel['checkout_page_id'])) {
            $next_url = add_query_arg(array('cwf_id' => (int) $funnel['id'], 'cw_prev' => 'landing'), get_permalink((int) $funnel['checkout_page_id']));
        }
        if ($funnel_context['step'] === 'checkout' && ! empty($funnel['thankyou_page_id'])) {
            $next_url = add_query_arg(array('cwf_id' => (int) $funnel['id'], 'cw_prev' => 'checkout'), get_permalink((int) $funnel['thankyou_page_id']));
        }

        ob_start();
        $context = array(
            'funnel'   => $funnel,
            'step'     => $funnel_context['step'],
            'next_url' => $next_url,
        );
        include CWFB_PLUGIN_PATH . 'templates/funnel-view.php';
        $template_markup = ob_get_clean();

        return $content . $template_markup;
    }

    public function handle_conversion_by_transition()
    {
        if (! is_singular('page')) {
            return;
        }

        $funnel_context = $this->get_current_funnel_context();
        if (empty($funnel_context)) {
            return;
        }

        $funnel_id = (int) $funnel_context['id'];
        $step = $funnel_context['step'];
        $prev = isset($_GET['cw_prev']) ? sanitize_key($_GET['cw_prev']) : '';
        $tracked_key = 'cwfb_c_' . $funnel_id . '_' . $prev;

        if ($step === 'checkout' && $prev === 'landing' && empty($_COOKIE[$tracked_key])) {
            CWFB_DB::increment_conversion($funnel_id, 'landing');
            setcookie($tracked_key, '1', time() + HOUR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
        }

        if ($step === 'thankyou' && $prev === 'checkout' && empty($_COOKIE[$tracked_key])) {
            CWFB_DB::increment_conversion($funnel_id, 'checkout');
            setcookie($tracked_key, '1', time() + HOUR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
        }
    }

    private function get_current_funnel_context()
    {
        $page_id = get_queried_object_id();
        if (! $page_id) {
            return null;
        }

        $forced_funnel_id = isset($_GET['cwf_id']) ? absint($_GET['cwf_id']) : 0;
        $funnel = $forced_funnel_id > 0 ? CWFB_DB::get_funnel($forced_funnel_id) : CWFB_DB::get_funnel_by_page($page_id);

        if (empty($funnel) || ($funnel['status'] ?? 'inactive') !== 'active') {
            return null;
        }

        $step = '';
        if ((int) $funnel['landing_page_id'] === (int) $page_id) {
            $step = 'landing';
        } elseif ((int) $funnel['checkout_page_id'] === (int) $page_id) {
            $step = 'checkout';
        } elseif ((int) $funnel['thankyou_page_id'] === (int) $page_id) {
            $step = 'thankyou';
        }

        if (! $step) {
            return null;
        }

        return array(
            'id'     => (int) $funnel['id'],
            'step'   => $step,
            'funnel' => $funnel,
        );
    }
}
