<?php
if (!defined('ABSPATH')) {
    exit;
}

class CWFB_Admin {
    private $funnels_table;
    private $stats_table;

    public function __construct() {
        global $wpdb;
        $this->funnels_table = $wpdb->prefix . 'cw_funnels';
        $this->stats_table   = $wpdb->prefix . 'cw_funnel_stats';

        add_action('admin_menu', array($this, 'register_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_post_cwfb_save_funnel', array($this, 'handle_save_funnel'));
        add_action('admin_post_cwfb_delete_funnel', array($this, 'handle_delete_funnel'));
    }

    public function register_menu() {
        add_menu_page(
            __('CashWala Funnels', 'cashwala-funnel-builder'),
            __('CashWala Funnels', 'cashwala-funnel-builder'),
            'manage_options',
            'cwfb-funnels',
            array($this, 'render_page'),
            'dashicons-filter',
            58
        );
    }

    public function enqueue_assets($hook) {
        if (false === strpos($hook, 'cwfb-funnels')) {
            return;
        }

        wp_enqueue_style('cwfb-style', CWFB_URL . 'assets/css/style.css', array(), CWFB_VERSION);
        wp_enqueue_script('cwfb-script', CWFB_URL . 'assets/js/script.js', array('jquery'), CWFB_VERSION, true);
    }

    public function render_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $action    = isset($_GET['action']) ? sanitize_key(wp_unslash($_GET['action'])) : 'list';
        $funnel_id = isset($_GET['funnel_id']) ? absint($_GET['funnel_id']) : 0;

        echo '<div class="wrap cwfb-admin-wrap">';
        echo '<h1>' . esc_html__('CashWala Funnels', 'cashwala-funnel-builder') . '</h1>';
        echo '<nav class="nav-tab-wrapper">';
        echo '<a class="nav-tab ' . ('list' === $action ? 'nav-tab-active' : '') . '" href="' . esc_url(admin_url('admin.php?page=cwfb-funnels&action=list')) . '">' . esc_html__('Funnels List', 'cashwala-funnel-builder') . '</a>';
        echo '<a class="nav-tab ' . ('edit' === $action ? 'nav-tab-active' : '') . '" href="' . esc_url(admin_url('admin.php?page=cwfb-funnels&action=edit')) . '">' . esc_html__('Funnel Editor', 'cashwala-funnel-builder') . '</a>';
        echo '<a class="nav-tab ' . ('stats' === $action ? 'nav-tab-active' : '') . '" href="' . esc_url(admin_url('admin.php?page=cwfb-funnels&action=stats')) . '">' . esc_html__('Stats Dashboard', 'cashwala-funnel-builder') . '</a>';
        echo '<a class="nav-tab ' . ('logs' === $action ? 'nav-tab-active' : '') . '" href="' . esc_url(admin_url('admin.php?page=cwfb-funnels&action=logs')) . '">' . esc_html__('Logs', 'cashwala-funnel-builder') . '</a>';
        echo '</nav>';

        switch ($action) {
            case 'edit':
                $this->render_editor($funnel_id);
                break;
            case 'stats':
                $this->render_stats();
                break;
            case 'logs':
                $this->render_logs();
                break;
            case 'list':
            default:
                $this->render_list();
                break;
        }

        echo '</div>';
    }

    private function render_list() {
        global $wpdb;
        $funnels = $wpdb->get_results("SELECT * FROM {$this->funnels_table} ORDER BY id DESC", ARRAY_A); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

        echo '<h2>' . esc_html__('Funnels List', 'cashwala-funnel-builder') . '</h2>';
        echo '<table class="widefat striped"><thead><tr>';
        echo '<th>' . esc_html__('ID', 'cashwala-funnel-builder') . '</th>';
        echo '<th>' . esc_html__('Name', 'cashwala-funnel-builder') . '</th>';
        echo '<th>' . esc_html__('Status', 'cashwala-funnel-builder') . '</th>';
        echo '<th>' . esc_html__('Created', 'cashwala-funnel-builder') . '</th>';
        echo '<th>' . esc_html__('Actions', 'cashwala-funnel-builder') . '</th>';
        echo '</tr></thead><tbody>';

        if (empty($funnels)) {
            echo '<tr><td colspan="5">' . esc_html__('No funnels found.', 'cashwala-funnel-builder') . '</td></tr>';
        } else {
            foreach ($funnels as $funnel) {
                $edit_url   = admin_url('admin.php?page=cwfb-funnels&action=edit&funnel_id=' . absint($funnel['id']));
                $delete_url = wp_nonce_url(admin_url('admin-post.php?action=cwfb_delete_funnel&funnel_id=' . absint($funnel['id'])), 'cwfb_delete_funnel_' . absint($funnel['id']));
                echo '<tr>';
                echo '<td>' . esc_html($funnel['id']) . '</td>';
                echo '<td>' . esc_html($funnel['name']) . '</td>';
                echo '<td>' . esc_html(ucfirst($funnel['status'])) . '</td>';
                echo '<td>' . esc_html($funnel['created_at']) . '</td>';
                echo '<td><a href="' . esc_url($edit_url) . '">' . esc_html__('Edit', 'cashwala-funnel-builder') . '</a> | <a href="' . esc_url($delete_url) . '" onclick="return confirm(\'' . esc_js(__('Delete funnel?', 'cashwala-funnel-builder')) . '\');">' . esc_html__('Delete', 'cashwala-funnel-builder') . '</a></td>';
                echo '</tr>';
            }
        }

        echo '</tbody></table>';
    }

    private function render_editor($funnel_id) {
        global $wpdb;

        $funnel = array(
            'id'               => 0,
            'name'             => '',
            'landing_page_id'  => 0,
            'checkout_page_id' => 0,
            'thankyou_page_id' => 0,
            'status'           => 'inactive',
        );

        if ($funnel_id > 0) {
            $found = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->funnels_table} WHERE id = %d", $funnel_id), ARRAY_A); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            if ($found) {
                $funnel = $found;
            }
        }

        echo '<h2>' . esc_html__('Funnel Editor', 'cashwala-funnel-builder') . '</h2>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('cwfb_save_funnel');
        echo '<input type="hidden" name="action" value="cwfb_save_funnel" />';
        echo '<input type="hidden" name="funnel_id" value="' . esc_attr($funnel['id']) . '" />';

        echo '<table class="form-table">';
        echo '<tr><th><label for="cwfb_name">' . esc_html__('Name', 'cashwala-funnel-builder') . '</label></th><td><input required type="text" id="cwfb_name" name="name" class="regular-text" value="' . esc_attr($funnel['name']) . '" /></td></tr>';
        echo '<tr><th>' . esc_html__('Landing Page', 'cashwala-funnel-builder') . '</th><td>';
        wp_dropdown_pages(array('name' => 'landing_page_id', 'selected' => absint($funnel['landing_page_id']), 'show_option_none' => __('Select page', 'cashwala-funnel-builder')));
        echo '</td></tr>';
        echo '<tr><th>' . esc_html__('Checkout Page', 'cashwala-funnel-builder') . '</th><td>';
        wp_dropdown_pages(array('name' => 'checkout_page_id', 'selected' => absint($funnel['checkout_page_id']), 'show_option_none' => __('Select page', 'cashwala-funnel-builder')));
        echo '</td></tr>';
        echo '<tr><th>' . esc_html__('Thank You Page', 'cashwala-funnel-builder') . '</th><td>';
        wp_dropdown_pages(array('name' => 'thankyou_page_id', 'selected' => absint($funnel['thankyou_page_id']), 'show_option_none' => __('Select page', 'cashwala-funnel-builder')));
        echo '</td></tr>';
        echo '<tr><th>' . esc_html__('Status', 'cashwala-funnel-builder') . '</th><td><select name="status"><option value="inactive" ' . selected($funnel['status'], 'inactive', false) . '>' . esc_html__('Inactive', 'cashwala-funnel-builder') . '</option><option value="active" ' . selected($funnel['status'], 'active', false) . '>' . esc_html__('Active', 'cashwala-funnel-builder') . '</option></select></td></tr>';
        echo '</table>';

        submit_button(__('Save Funnel', 'cashwala-funnel-builder'));
        echo '</form>';
    }

    private function render_stats() {
        global $wpdb;

        $rows = $wpdb->get_results(
            "SELECT f.id, f.name, s.step, s.visits, s.conversions
             FROM {$this->funnels_table} f
             LEFT JOIN {$this->stats_table} s ON f.id = s.funnel_id
             ORDER BY f.id DESC",
            ARRAY_A
        ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

        $stats = array();
        foreach ($rows as $row) {
            $fid = absint($row['id']);
            if (!isset($stats[$fid])) {
                $stats[$fid] = array(
                    'name'        => $row['name'],
                    'visits'      => 0,
                    'conversions' => 0,
                );
            }

            $stats[$fid]['visits'] += isset($row['visits']) ? (int) $row['visits'] : 0;
            if ('thankyou' === $row['step']) {
                $stats[$fid]['conversions'] += isset($row['conversions']) ? (int) $row['conversions'] : 0;
            }
        }

        echo '<h2>' . esc_html__('Stats Dashboard', 'cashwala-funnel-builder') . '</h2>';
        echo '<table class="widefat striped"><thead><tr><th>' . esc_html__('Funnel', 'cashwala-funnel-builder') . '</th><th>' . esc_html__('Visits', 'cashwala-funnel-builder') . '</th><th>' . esc_html__('Conversions', 'cashwala-funnel-builder') . '</th><th>' . esc_html__('Conversion Rate', 'cashwala-funnel-builder') . '</th></tr></thead><tbody>';

        if (empty($stats)) {
            echo '<tr><td colspan="4">' . esc_html__('No stats yet.', 'cashwala-funnel-builder') . '</td></tr>';
        } else {
            foreach ($stats as $item) {
                $rate = $item['visits'] > 0 ? round(($item['conversions'] / $item['visits']) * 100, 2) : 0;
                echo '<tr><td>' . esc_html($item['name']) . '</td><td>' . esc_html($item['visits']) . '</td><td>' . esc_html($item['conversions']) . '</td><td>' . esc_html($rate) . '%</td></tr>';
            }
        }

        echo '</tbody></table>';
    }

    private function render_logs() {
        $logs = CWFB_Logger::get_logs(200);
        echo '<h2>' . esc_html__('Error Logs', 'cashwala-funnel-builder') . '</h2>';
        echo '<div class="cwfb-logs">';

        if (empty($logs)) {
            echo '<p>' . esc_html__('No log entries yet.', 'cashwala-funnel-builder') . '</p>';
        } else {
            echo '<pre>';
            foreach ($logs as $line) {
                echo esc_html($line) . "\n";
            }
            echo '</pre>';
        }
        echo '</div>';
    }

    public function handle_save_funnel() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized', 'cashwala-funnel-builder'));
        }

        check_admin_referer('cwfb_save_funnel');

        global $wpdb;

        $id               = isset($_POST['funnel_id']) ? absint($_POST['funnel_id']) : 0;
        $name             = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
        $landing_page_id  = isset($_POST['landing_page_id']) ? absint($_POST['landing_page_id']) : 0;
        $checkout_page_id = isset($_POST['checkout_page_id']) ? absint($_POST['checkout_page_id']) : 0;
        $thankyou_page_id = isset($_POST['thankyou_page_id']) ? absint($_POST['thankyou_page_id']) : 0;
        $status           = isset($_POST['status']) && 'active' === sanitize_key(wp_unslash($_POST['status'])) ? 'active' : 'inactive';

        if (empty($name) || $landing_page_id < 1 || $checkout_page_id < 1 || $thankyou_page_id < 1) {
            CWFB_Logger::log('Invalid funnel data on save', compact('id', 'name', 'landing_page_id', 'checkout_page_id', 'thankyou_page_id', 'status'));
            wp_safe_redirect(admin_url('admin.php?page=cwfb-funnels&action=edit&error=1'));
            exit;
        }

        $data = array(
            'name'             => $name,
            'landing_page_id'  => $landing_page_id,
            'checkout_page_id' => $checkout_page_id,
            'thankyou_page_id' => $thankyou_page_id,
            'status'           => $status,
        );

        if ($id > 0) {
            $wpdb->update($this->funnels_table, $data, array('id' => $id), array('%s', '%d', '%d', '%d', '%s'), array('%d'));
            $funnel_id = $id;
        } else {
            $data['created_at'] = current_time('mysql');
            $wpdb->insert($this->funnels_table, $data, array('%s', '%d', '%d', '%d', '%s', '%s'));
            $funnel_id = (int) $wpdb->insert_id;
        }

        if ($funnel_id > 0) {
            $this->seed_stats_rows($funnel_id);
        }

        wp_safe_redirect(admin_url('admin.php?page=cwfb-funnels&action=list&saved=1'));
        exit;
    }

    public function handle_delete_funnel() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized', 'cashwala-funnel-builder'));
        }

        $funnel_id = isset($_GET['funnel_id']) ? absint($_GET['funnel_id']) : 0;
        check_admin_referer('cwfb_delete_funnel_' . $funnel_id);

        if ($funnel_id > 0) {
            global $wpdb;
            $wpdb->delete($this->funnels_table, array('id' => $funnel_id), array('%d'));
            $wpdb->delete($this->stats_table, array('funnel_id' => $funnel_id), array('%d'));
        }

        wp_safe_redirect(admin_url('admin.php?page=cwfb-funnels&action=list&deleted=1'));
        exit;
    }

    private function seed_stats_rows($funnel_id) {
        global $wpdb;

        $steps = array('landing', 'checkout', 'thankyou');
        foreach ($steps as $step) {
            $wpdb->query(
                $wpdb->prepare(
                    "INSERT INTO {$this->stats_table} (funnel_id, step, visits, conversions)
                    VALUES (%d, %s, 0, 0)
                    ON DUPLICATE KEY UPDATE step = VALUES(step)",
                    $funnel_id,
                    $step
                )
            ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        }
    }
}
