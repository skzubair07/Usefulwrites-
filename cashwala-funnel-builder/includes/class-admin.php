<?php

if (! defined('ABSPATH')) {
    exit;
}

class CWFB_Admin
{
    public function hooks()
    {
        add_action('admin_menu', array($this, 'menu'));
        add_action('admin_post_cwfb_save_funnel', array($this, 'save_funnel'));
        add_action('admin_post_cwfb_delete_funnel', array($this, 'delete_funnel'));
        add_action('admin_post_cwfb_clear_logs', array($this, 'clear_logs'));
    }

    public function menu()
    {
        add_menu_page(
            __('CashWala Funnels', 'cashwala-funnel-builder'),
            __('CashWala Funnels', 'cashwala-funnel-builder'),
            'manage_options',
            'cwfb-funnels',
            array($this, 'render_page'),
            'dashicons-chart-line'
        );
    }

    public function render_page()
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized.', 'cashwala-funnel-builder'));
        }

        $tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'list';
        $funnels = CWFB_DB::get_funnels(false);
        $edit_id = isset($_GET['edit']) ? absint($_GET['edit']) : 0;
        $edit_funnel = $edit_id ? CWFB_DB::get_funnel($edit_id) : null;
        $pages = get_pages();

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('CashWala Funnel Builder Lite', 'cashwala-funnel-builder') . '</h1>';

        echo '<h2 class="nav-tab-wrapper">';
        $this->tab_link('list', __('Funnels List', 'cashwala-funnel-builder'), $tab);
        $this->tab_link('editor', __('Funnel Editor', 'cashwala-funnel-builder'), $tab);
        $this->tab_link('stats', __('Stats Dashboard', 'cashwala-funnel-builder'), $tab);
        $this->tab_link('logs', __('Logs', 'cashwala-funnel-builder'), $tab);
        echo '</h2>';

        if ($tab === 'editor') {
            $this->render_editor($edit_funnel, $pages);
        } elseif ($tab === 'stats') {
            $this->render_stats($funnels);
        } elseif ($tab === 'logs') {
            $this->render_logs();
        } else {
            $this->render_list($funnels);
        }

        echo '</div>';
    }

    private function tab_link($slug, $label, $current)
    {
        $class = ($slug === $current) ? ' nav-tab-active' : '';
        $url = admin_url('admin.php?page=cwfb-funnels&tab=' . $slug);
        echo '<a href="' . esc_url($url) . '" class="nav-tab' . esc_attr($class) . '">' . esc_html($label) . '</a>';
    }

    private function render_list(array $funnels)
    {
        echo '<h2>' . esc_html__('Funnels List', 'cashwala-funnel-builder') . '</h2>';
        echo '<p><a href="' . esc_url(admin_url('admin.php?page=cwfb-funnels&tab=editor')) . '" class="button button-primary">' . esc_html__('Create Funnel', 'cashwala-funnel-builder') . '</a></p>';

        if (empty($funnels)) {
            echo '<p>' . esc_html__('No funnels created yet.', 'cashwala-funnel-builder') . '</p>';
            return;
        }

        echo '<table class="widefat striped"><thead><tr>';
        echo '<th>' . esc_html__('ID', 'cashwala-funnel-builder') . '</th>';
        echo '<th>' . esc_html__('Name', 'cashwala-funnel-builder') . '</th>';
        echo '<th>' . esc_html__('Status', 'cashwala-funnel-builder') . '</th>';
        echo '<th>' . esc_html__('Actions', 'cashwala-funnel-builder') . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($funnels as $funnel) {
            $edit_url = admin_url('admin.php?page=cwfb-funnels&tab=editor&edit=' . (int) $funnel['id']);
            $delete_url = wp_nonce_url(admin_url('admin-post.php?action=cwfb_delete_funnel&id=' . (int) $funnel['id']), 'cwfb_delete_funnel_' . (int) $funnel['id']);
            echo '<tr>';
            echo '<td>' . esc_html($funnel['id']) . '</td>';
            echo '<td>' . esc_html($funnel['name']) . '</td>';
            echo '<td>' . esc_html(ucfirst($funnel['status'])) . '</td>';
            echo '<td>';
            echo '<a class="button button-small" href="' . esc_url($edit_url) . '">' . esc_html__('Edit', 'cashwala-funnel-builder') . '</a> ';
            echo '<a class="button button-small" href="' . esc_url($delete_url) . '" onclick="return confirm(\'' . esc_js(__('Delete this funnel?', 'cashwala-funnel-builder')) . '\');">' . esc_html__('Delete', 'cashwala-funnel-builder') . '</a>';
            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    private function render_editor($funnel, array $pages)
    {
        echo '<h2>' . esc_html__('Funnel Editor', 'cashwala-funnel-builder') . '</h2>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('cwfb_save_funnel');
        echo '<input type="hidden" name="action" value="cwfb_save_funnel">';
        echo '<input type="hidden" name="id" value="' . esc_attr($funnel['id'] ?? 0) . '">';

        echo '<table class="form-table">';
        echo '<tr><th><label for="cwfb_name">' . esc_html__('Funnel Name', 'cashwala-funnel-builder') . '</label></th>';
        echo '<td><input type="text" id="cwfb_name" name="name" class="regular-text" required value="' . esc_attr($funnel['name'] ?? '') . '"></td></tr>';

        $this->page_select('landing_page_id', __('Landing Page', 'cashwala-funnel-builder'), $pages, (int) ($funnel['landing_page_id'] ?? 0));
        $this->page_select('checkout_page_id', __('Checkout Page', 'cashwala-funnel-builder'), $pages, (int) ($funnel['checkout_page_id'] ?? 0));
        $this->page_select('thankyou_page_id', __('Thank You Page', 'cashwala-funnel-builder'), $pages, (int) ($funnel['thankyou_page_id'] ?? 0));

        $status = $funnel['status'] ?? 'inactive';
        echo '<tr><th>' . esc_html__('Status', 'cashwala-funnel-builder') . '</th><td>';
        echo '<label><input type="radio" name="status" value="active" ' . checked($status, 'active', false) . '> ' . esc_html__('Active', 'cashwala-funnel-builder') . '</label> ';
        echo '<label><input type="radio" name="status" value="inactive" ' . checked($status, 'inactive', false) . '> ' . esc_html__('Inactive', 'cashwala-funnel-builder') . '</label>';
        echo '</td></tr>';
        echo '</table>';

        submit_button($funnel ? __('Update Funnel', 'cashwala-funnel-builder') : __('Create Funnel', 'cashwala-funnel-builder'));
        echo '</form>';
    }

    private function page_select($name, $label, array $pages, $selected)
    {
        echo '<tr><th><label for="' . esc_attr($name) . '">' . esc_html($label) . '</label></th><td>';
        echo '<select id="' . esc_attr($name) . '" name="' . esc_attr($name) . '">';
        echo '<option value="0">' . esc_html__('Select a page', 'cashwala-funnel-builder') . '</option>';
        foreach ($pages as $page) {
            echo '<option value="' . esc_attr($page->ID) . '" ' . selected($selected, $page->ID, false) . '>' . esc_html($page->post_title) . '</option>';
        }
        echo '</select></td></tr>';
    }

    private function render_stats(array $funnels)
    {
        echo '<h2>' . esc_html__('Stats Dashboard', 'cashwala-funnel-builder') . '</h2>';

        if (empty($funnels)) {
            echo '<p>' . esc_html__('Create a funnel to see stats.', 'cashwala-funnel-builder') . '</p>';
            return;
        }

        echo '<table class="widefat striped"><thead><tr>';
        echo '<th>' . esc_html__('Funnel', 'cashwala-funnel-builder') . '</th>';
        echo '<th>' . esc_html__('Step', 'cashwala-funnel-builder') . '</th>';
        echo '<th>' . esc_html__('Visits', 'cashwala-funnel-builder') . '</th>';
        echo '<th>' . esc_html__('Conversions', 'cashwala-funnel-builder') . '</th>';
        echo '<th>' . esc_html__('Conversion Rate', 'cashwala-funnel-builder') . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($funnels as $funnel) {
            $rows = CWFB_DB::get_stats((int) $funnel['id']);
            foreach ($rows as $row) {
                $rate = ((int) $row['visits'] > 0) ? round(((int) $row['conversions'] / (int) $row['visits']) * 100, 2) : 0;
                echo '<tr>';
                echo '<td>' . esc_html($funnel['name']) . ' (#' . esc_html($funnel['id']) . ')</td>';
                echo '<td>' . esc_html(ucfirst($row['step'])) . '</td>';
                echo '<td>' . esc_html($row['visits']) . '</td>';
                echo '<td>' . esc_html($row['conversions']) . '</td>';
                echo '<td>' . esc_html($rate) . '%</td>';
                echo '</tr>';
            }
        }

        echo '</tbody></table>';
    }

    private function render_logs()
    {
        echo '<h2>' . esc_html__('Error Logs', 'cashwala-funnel-builder') . '</h2>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="margin-bottom:10px;">';
        wp_nonce_field('cwfb_clear_logs');
        echo '<input type="hidden" name="action" value="cwfb_clear_logs">';
        submit_button(__('Clear Logs', 'cashwala-funnel-builder'), 'secondary', 'submit', false);
        echo '</form>';

        $logs = CWFB_Logger::get_logs();
        if (empty($logs)) {
            echo '<p>' . esc_html__('No logs recorded.', 'cashwala-funnel-builder') . '</p>';
            return;
        }

        echo '<table class="widefat striped"><thead><tr>';
        echo '<th>' . esc_html__('Time', 'cashwala-funnel-builder') . '</th>';
        echo '<th>' . esc_html__('Level', 'cashwala-funnel-builder') . '</th>';
        echo '<th>' . esc_html__('Message', 'cashwala-funnel-builder') . '</th>';
        echo '<th>' . esc_html__('Context', 'cashwala-funnel-builder') . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($logs as $entry) {
            echo '<tr>';
            echo '<td>' . esc_html($entry['time']) . '</td>';
            echo '<td>' . esc_html(strtoupper($entry['level'])) . '</td>';
            echo '<td>' . esc_html($entry['message']) . '</td>';
            echo '<td><code>' . esc_html($entry['context']) . '</code></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    public function save_funnel()
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized.', 'cashwala-funnel-builder'));
        }
        check_admin_referer('cwfb_save_funnel');

        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;
        $result = CWFB_DB::save_funnel($_POST, $id);

        if (is_wp_error($result)) {
            CWFB_Logger::log('error', 'Save funnel failed', array('error' => $result->get_error_message()));
            wp_safe_redirect(admin_url('admin.php?page=cwfb-funnels&tab=editor&error=1'));
            exit;
        }

        wp_safe_redirect(admin_url('admin.php?page=cwfb-funnels&tab=list&saved=1'));
        exit;
    }

    public function delete_funnel()
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized.', 'cashwala-funnel-builder'));
        }

        $id = isset($_GET['id']) ? absint($_GET['id']) : 0;
        check_admin_referer('cwfb_delete_funnel_' . $id);

        if ($id > 0) {
            CWFB_DB::delete_funnel($id);
        }

        wp_safe_redirect(admin_url('admin.php?page=cwfb-funnels&tab=list&deleted=1'));
        exit;
    }

    public function clear_logs()
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized.', 'cashwala-funnel-builder'));
        }
        check_admin_referer('cwfb_clear_logs');
        CWFB_Logger::clear_logs();

        wp_safe_redirect(admin_url('admin.php?page=cwfb-funnels&tab=logs&cleared=1'));
        exit;
    }
}
