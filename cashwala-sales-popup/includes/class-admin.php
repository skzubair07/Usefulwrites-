<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CW_Sales_Popup_Admin {
    private $db;
    private $logger;

    public function __construct( $db, $logger ) {
        $this->db     = $db;
        $this->logger = $logger;

        add_action( 'admin_menu', array( $this, 'register_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_post_cw_sales_popup_save_entry', array( $this, 'save_entry' ) );
        add_action( 'admin_post_cw_sales_popup_delete_entry', array( $this, 'delete_entry' ) );
        add_action( 'admin_post_cw_sales_popup_clear_logs', array( $this, 'clear_logs' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
    }

    public function register_menu() {
        add_menu_page(
            __( 'CashWala Sales Popup', 'cashwala-sales-popup' ),
            __( 'CashWala Sales Popup', 'cashwala-sales-popup' ),
            'manage_options',
            'cw-sales-popup',
            array( $this, 'render_page' ),
            'dashicons-megaphone',
            56
        );
    }

    public function enqueue_admin_assets( $hook ) {
        if ( 'toplevel_page_cw-sales-popup' !== $hook ) {
            return;
        }

        wp_enqueue_media();
    }

    public function register_settings() {
        register_setting( 'cw_sales_popup_settings_group', 'cw_sales_popup_settings', array( $this, 'sanitize_settings' ) );
    }

    public function sanitize_settings( $input ) {
        $defaults = CW_Sales_Popup_DB::default_settings();

        $output = array(
            'enabled'            => ! empty( $input['enabled'] ) ? 1 : 0,
            'data_mode'          => in_array( $input['data_mode'] ?? '', array( 'manual', 'random', 'hybrid' ), true ) ? $input['data_mode'] : $defaults['data_mode'],
            'initial_delay'      => absint( $input['initial_delay'] ?? $defaults['initial_delay'] ),
            'interval'           => absint( $input['interval'] ?? $defaults['interval'] ),
            'random_variation'   => absint( $input['random_variation'] ?? $defaults['random_variation'] ),
            'randomized_timing'  => ! empty( $input['randomized_timing'] ) ? 1 : 0,
            'loop_enabled'       => ! empty( $input['loop_enabled'] ) ? 1 : 0,
            'shuffle_entries'    => ! empty( $input['shuffle_entries'] ) ? 1 : 0,
            'position'           => in_array( $input['position'] ?? '', array( 'bottom-left', 'bottom-right' ), true ) ? $input['position'] : $defaults['position'],
            'display_mode'       => in_array( $input['display_mode'] ?? '', array( 'single', 'stack' ), true ) ? $input['display_mode'] : $defaults['display_mode'],
            'max_popups'         => max( 1, absint( $input['max_popups'] ?? $defaults['max_popups'] ) ),
            'show_duration'      => max( 2, absint( $input['show_duration'] ?? $defaults['show_duration'] ) ),
            'cta_enabled'        => ! empty( $input['cta_enabled'] ) ? 1 : 0,
            'cta_text'           => sanitize_text_field( $input['cta_text'] ?? $defaults['cta_text'] ),
            'sound_enabled'      => ! empty( $input['sound_enabled'] ) ? 1 : 0,
            'sound_url'          => esc_url_raw( $input['sound_url'] ?? '' ),
            'background_color'   => sanitize_text_field( $input['background_color'] ?? $defaults['background_color'] ),
            'text_color'         => sanitize_text_field( $input['text_color'] ?? $defaults['text_color'] ),
            'border_radius'      => absint( $input['border_radius'] ?? $defaults['border_radius'] ),
            'shadow'             => sanitize_text_field( $input['shadow'] ?? $defaults['shadow'] ),
            'avatar_url'         => esc_url_raw( $input['avatar_url'] ?? '' ),
            'template'           => in_array( $input['template'] ?? '', array( 'template-1', 'template-2' ), true ) ? $input['template'] : $defaults['template'],
            'enable_mobile'      => ! empty( $input['enable_mobile'] ) ? 1 : 0,
        );

        $this->logger->log( 'info', 'Settings updated.' );

        return $output;
    }

    public function save_entry() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Unauthorized.', 'cashwala-sales-popup' ) );
        }

        check_admin_referer( 'cw_sales_popup_entry_action', 'cw_sales_popup_entry_nonce' );

        $id   = absint( $_POST['id'] ?? 0 );
        $data = array(
            'name'    => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
            'city'    => sanitize_text_field( wp_unslash( $_POST['city'] ?? '' ) ),
            'product' => sanitize_text_field( wp_unslash( $_POST['product'] ?? '' ) ),
            'link'    => esc_url_raw( wp_unslash( $_POST['link'] ?? '' ) ),
        );

        if ( $id > 0 ) {
            $this->db->update_entry( $id, $data );
            $this->logger->log( 'info', 'Entry updated.', array( 'id' => $id ) );
        } else {
            $this->db->insert_entry( $data );
            $this->logger->log( 'info', 'Entry created.', array( 'name' => $data['name'] ) );
        }

        wp_safe_redirect( admin_url( 'admin.php?page=cw-sales-popup&tab=entries' ) );
        exit;
    }

    public function delete_entry() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Unauthorized.', 'cashwala-sales-popup' ) );
        }

        check_admin_referer( 'cw_sales_popup_delete_action', 'cw_sales_popup_delete_nonce' );

        $id = absint( $_GET['id'] ?? 0 );
        if ( $id ) {
            $this->db->delete_entry( $id );
            $this->logger->log( 'warning', 'Entry deleted.', array( 'id' => $id ) );
        }

        wp_safe_redirect( admin_url( 'admin.php?page=cw-sales-popup&tab=entries' ) );
        exit;
    }

    public function clear_logs() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Unauthorized.', 'cashwala-sales-popup' ) );
        }

        check_admin_referer( 'cw_sales_popup_clear_logs_action', 'cw_sales_popup_clear_logs_nonce' );

        $this->logger->clear();
        wp_safe_redirect( admin_url( 'admin.php?page=cw-sales-popup&tab=logs' ) );
        exit;
    }

    public function render_page() {
        $settings  = get_option( 'cw_sales_popup_settings', CW_Sales_Popup_DB::default_settings() );
        $analytics = get_option( 'cw_sales_popup_analytics', array( 'impressions' => 0, 'clicks' => 0 ) );
        $entries   = $this->db->get_entries();
        $logs      = $this->logger->get_logs();
        $edit_id   = absint( $_GET['edit_id'] ?? 0 );
        $editing   = $edit_id ? $this->db->get_entry( $edit_id ) : null;

        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'CashWala Sales Popup', 'cashwala-sales-popup' ); ?></h1>
            <h2 class="nav-tab-wrapper">
                <a href="#general" class="nav-tab nav-tab-active"><?php esc_html_e( 'General Settings', 'cashwala-sales-popup' ); ?></a>
                <a href="#entries" class="nav-tab"><?php esc_html_e( 'Entries Management', 'cashwala-sales-popup' ); ?></a>
                <a href="#timing" class="nav-tab"><?php esc_html_e( 'Timing Settings', 'cashwala-sales-popup' ); ?></a>
                <a href="#display" class="nav-tab"><?php esc_html_e( 'Display Settings', 'cashwala-sales-popup' ); ?></a>
                <a href="#sound" class="nav-tab"><?php esc_html_e( 'Sound Settings', 'cashwala-sales-popup' ); ?></a>
                <a href="#design" class="nav-tab"><?php esc_html_e( 'Design Settings', 'cashwala-sales-popup' ); ?></a>
                <a href="#analytics" class="nav-tab"><?php esc_html_e( 'Analytics', 'cashwala-sales-popup' ); ?></a>
                <a href="#logs" class="nav-tab"><?php esc_html_e( 'Logs', 'cashwala-sales-popup' ); ?></a>
            </h2>

            <form method="post" action="options.php">
                <?php settings_fields( 'cw_sales_popup_settings_group' ); ?>
                <div id="general" class="cw-card">
                    <h2><?php esc_html_e( 'General Settings', 'cashwala-sales-popup' ); ?></h2>
                    <label><input type="checkbox" name="cw_sales_popup_settings[enabled]" value="1" <?php checked( ! empty( $settings['enabled'] ) ); ?>> <?php esc_html_e( 'Enable Popup', 'cashwala-sales-popup' ); ?></label>
                    <p>
                        <label><?php esc_html_e( 'Data Mode', 'cashwala-sales-popup' ); ?></label>
                        <select name="cw_sales_popup_settings[data_mode]">
                            <option value="manual" <?php selected( $settings['data_mode'], 'manual' ); ?>><?php esc_html_e( 'Manual', 'cashwala-sales-popup' ); ?></option>
                            <option value="random" <?php selected( $settings['data_mode'], 'random' ); ?>><?php esc_html_e( 'Random', 'cashwala-sales-popup' ); ?></option>
                            <option value="hybrid" <?php selected( $settings['data_mode'], 'hybrid' ); ?>><?php esc_html_e( 'Hybrid', 'cashwala-sales-popup' ); ?></option>
                        </select>
                    </p>
                </div>

                <div id="timing" class="cw-card">
                    <h2><?php esc_html_e( 'Timing Settings', 'cashwala-sales-popup' ); ?></h2>
                    <p><label><?php esc_html_e( 'Initial Delay (seconds)', 'cashwala-sales-popup' ); ?> <input type="number" min="0" name="cw_sales_popup_settings[initial_delay]" value="<?php echo esc_attr( $settings['initial_delay'] ); ?>"></label></p>
                    <p><label><?php esc_html_e( 'Interval (seconds)', 'cashwala-sales-popup' ); ?> <input type="number" min="2" name="cw_sales_popup_settings[interval]" value="<?php echo esc_attr( $settings['interval'] ); ?>"></label></p>
                    <p><label><?php esc_html_e( 'Random Variation (seconds)', 'cashwala-sales-popup' ); ?> <input type="number" min="0" name="cw_sales_popup_settings[random_variation]" value="<?php echo esc_attr( $settings['random_variation'] ); ?>"></label></p>
                    <p><label><input type="checkbox" name="cw_sales_popup_settings[randomized_timing]" value="1" <?php checked( ! empty( $settings['randomized_timing'] ) ); ?>> <?php esc_html_e( 'Enable randomized timing', 'cashwala-sales-popup' ); ?></label></p>
                    <p><label><input type="checkbox" name="cw_sales_popup_settings[loop_enabled]" value="1" <?php checked( ! empty( $settings['loop_enabled'] ) ); ?>> <?php esc_html_e( 'Enable continuous loop', 'cashwala-sales-popup' ); ?></label></p>
                    <p><label><input type="checkbox" name="cw_sales_popup_settings[shuffle_entries]" value="1" <?php checked( ! empty( $settings['shuffle_entries'] ) ); ?>> <?php esc_html_e( 'Shuffle entries', 'cashwala-sales-popup' ); ?></label></p>
                </div>

                <div id="display" class="cw-card">
                    <h2><?php esc_html_e( 'Display Settings', 'cashwala-sales-popup' ); ?></h2>
                    <p>
                        <label><?php esc_html_e( 'Position', 'cashwala-sales-popup' ); ?></label>
                        <select name="cw_sales_popup_settings[position]">
                            <option value="bottom-left" <?php selected( $settings['position'], 'bottom-left' ); ?>><?php esc_html_e( 'Bottom Left', 'cashwala-sales-popup' ); ?></option>
                            <option value="bottom-right" <?php selected( $settings['position'], 'bottom-right' ); ?>><?php esc_html_e( 'Bottom Right', 'cashwala-sales-popup' ); ?></option>
                        </select>
                    </p>
                    <p>
                        <label><?php esc_html_e( 'Display Mode', 'cashwala-sales-popup' ); ?></label>
                        <select name="cw_sales_popup_settings[display_mode]">
                            <option value="single" <?php selected( $settings['display_mode'], 'single' ); ?>><?php esc_html_e( 'Single', 'cashwala-sales-popup' ); ?></option>
                            <option value="stack" <?php selected( $settings['display_mode'], 'stack' ); ?>><?php esc_html_e( 'Stack', 'cashwala-sales-popup' ); ?></option>
                        </select>
                    </p>
                    <p><label><?php esc_html_e( 'Max popups at once', 'cashwala-sales-popup' ); ?> <input type="number" min="1" name="cw_sales_popup_settings[max_popups]" value="<?php echo esc_attr( $settings['max_popups'] ); ?>"></label></p>
                    <p><label><?php esc_html_e( 'Show duration (seconds)', 'cashwala-sales-popup' ); ?> <input type="number" min="2" name="cw_sales_popup_settings[show_duration]" value="<?php echo esc_attr( $settings['show_duration'] ); ?>"></label></p>
                    <p><label><input type="checkbox" name="cw_sales_popup_settings[cta_enabled]" value="1" <?php checked( ! empty( $settings['cta_enabled'] ) ); ?>> <?php esc_html_e( 'Enable CTA button', 'cashwala-sales-popup' ); ?></label></p>
                    <p><label><?php esc_html_e( 'CTA text', 'cashwala-sales-popup' ); ?> <input type="text" name="cw_sales_popup_settings[cta_text]" value="<?php echo esc_attr( $settings['cta_text'] ); ?>"></label></p>
                    <p><label><input type="checkbox" name="cw_sales_popup_settings[enable_mobile]" value="1" <?php checked( ! empty( $settings['enable_mobile'] ) ); ?>> <?php esc_html_e( 'Enable on mobile', 'cashwala-sales-popup' ); ?></label></p>
                    <p>
                        <label><?php esc_html_e( 'Template', 'cashwala-sales-popup' ); ?></label>
                        <select name="cw_sales_popup_settings[template]">
                            <option value="template-1" <?php selected( $settings['template'], 'template-1' ); ?>><?php esc_html_e( 'Template 1', 'cashwala-sales-popup' ); ?></option>
                            <option value="template-2" <?php selected( $settings['template'], 'template-2' ); ?>><?php esc_html_e( 'Template 2', 'cashwala-sales-popup' ); ?></option>
                        </select>
                    </p>
                </div>

                <div id="sound" class="cw-card">
                    <h2><?php esc_html_e( 'Sound Settings', 'cashwala-sales-popup' ); ?></h2>
                    <p><label><input type="checkbox" name="cw_sales_popup_settings[sound_enabled]" value="1" <?php checked( ! empty( $settings['sound_enabled'] ) ); ?>> <?php esc_html_e( 'Enable notification sound', 'cashwala-sales-popup' ); ?></label></p>
                    <p><label><?php esc_html_e( 'Sound file URL', 'cashwala-sales-popup' ); ?> <input type="url" class="regular-text" name="cw_sales_popup_settings[sound_url]" value="<?php echo esc_attr( $settings['sound_url'] ); ?>"></label></p>
                </div>

                <div id="design" class="cw-card">
                    <h2><?php esc_html_e( 'Design Settings', 'cashwala-sales-popup' ); ?></h2>
                    <p><label><?php esc_html_e( 'Background color', 'cashwala-sales-popup' ); ?> <input type="text" name="cw_sales_popup_settings[background_color]" value="<?php echo esc_attr( $settings['background_color'] ); ?>"></label></p>
                    <p><label><?php esc_html_e( 'Text color', 'cashwala-sales-popup' ); ?> <input type="text" name="cw_sales_popup_settings[text_color]" value="<?php echo esc_attr( $settings['text_color'] ); ?>"></label></p>
                    <p><label><?php esc_html_e( 'Border radius', 'cashwala-sales-popup' ); ?> <input type="number" name="cw_sales_popup_settings[border_radius]" value="<?php echo esc_attr( $settings['border_radius'] ); ?>"></label></p>
                    <p><label><?php esc_html_e( 'Shadow', 'cashwala-sales-popup' ); ?> <input type="text" class="regular-text" name="cw_sales_popup_settings[shadow]" value="<?php echo esc_attr( $settings['shadow'] ); ?>"></label></p>
                    <p><label><?php esc_html_e( 'Avatar image URL', 'cashwala-sales-popup' ); ?> <input type="url" class="regular-text" name="cw_sales_popup_settings[avatar_url]" value="<?php echo esc_attr( $settings['avatar_url'] ); ?>"></label></p>
                </div>

                <?php submit_button( __( 'Save Settings', 'cashwala-sales-popup' ) ); ?>
            </form>

            <div id="entries" class="cw-card">
                <h2><?php esc_html_e( 'Entries Management', 'cashwala-sales-popup' ); ?></h2>
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                    <input type="hidden" name="action" value="cw_sales_popup_save_entry">
                    <input type="hidden" name="id" value="<?php echo esc_attr( $editing['id'] ?? 0 ); ?>">
                    <?php wp_nonce_field( 'cw_sales_popup_entry_action', 'cw_sales_popup_entry_nonce' ); ?>
                    <table class="form-table">
                        <tr><th><label><?php esc_html_e( 'Name', 'cashwala-sales-popup' ); ?></label></th><td><input type="text" name="name" required value="<?php echo esc_attr( $editing['name'] ?? '' ); ?>"></td></tr>
                        <tr><th><label><?php esc_html_e( 'City', 'cashwala-sales-popup' ); ?></label></th><td><input type="text" name="city" required value="<?php echo esc_attr( $editing['city'] ?? '' ); ?>"></td></tr>
                        <tr><th><label><?php esc_html_e( 'Product Name', 'cashwala-sales-popup' ); ?></label></th><td><input type="text" name="product" required value="<?php echo esc_attr( $editing['product'] ?? '' ); ?>"></td></tr>
                        <tr><th><label><?php esc_html_e( 'Link', 'cashwala-sales-popup' ); ?></label></th><td><input type="url" class="regular-text" name="link" required value="<?php echo esc_attr( $editing['link'] ?? '' ); ?>"></td></tr>
                    </table>
                    <?php submit_button( $editing ? __( 'Update Entry', 'cashwala-sales-popup' ) : __( 'Add Entry', 'cashwala-sales-popup' ) ); ?>
                </form>

                <table class="widefat striped">
                    <thead><tr><th><?php esc_html_e( 'Name', 'cashwala-sales-popup' ); ?></th><th><?php esc_html_e( 'City', 'cashwala-sales-popup' ); ?></th><th><?php esc_html_e( 'Product', 'cashwala-sales-popup' ); ?></th><th><?php esc_html_e( 'Link', 'cashwala-sales-popup' ); ?></th><th><?php esc_html_e( 'Actions', 'cashwala-sales-popup' ); ?></th></tr></thead>
                    <tbody>
                    <?php if ( empty( $entries ) ) : ?>
                        <tr><td colspan="5"><?php esc_html_e( 'No entries found.', 'cashwala-sales-popup' ); ?></td></tr>
                    <?php else : ?>
                        <?php foreach ( $entries as $entry ) : ?>
                            <tr>
                                <td><?php echo esc_html( $entry['name'] ); ?></td>
                                <td><?php echo esc_html( $entry['city'] ); ?></td>
                                <td><?php echo esc_html( $entry['product'] ); ?></td>
                                <td><a href="<?php echo esc_url( $entry['link'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $entry['link'] ); ?></a></td>
                                <td>
                                    <a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=cw-sales-popup&edit_id=' . absint( $entry['id'] ) . '#entries' ) ); ?>"><?php esc_html_e( 'Edit', 'cashwala-sales-popup' ); ?></a>
                                    <a class="button button-link-delete" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=cw_sales_popup_delete_entry&id=' . absint( $entry['id'] ) ), 'cw_sales_popup_delete_action', 'cw_sales_popup_delete_nonce' ) ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Delete this entry?', 'cashwala-sales-popup' ) ); ?>')"><?php esc_html_e( 'Delete', 'cashwala-sales-popup' ); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div id="analytics" class="cw-card">
                <h2><?php esc_html_e( 'Analytics', 'cashwala-sales-popup' ); ?></h2>
                <p><strong><?php esc_html_e( 'Total Impressions:', 'cashwala-sales-popup' ); ?></strong> <?php echo esc_html( absint( $analytics['impressions'] ) ); ?></p>
                <p><strong><?php esc_html_e( 'Total Clicks:', 'cashwala-sales-popup' ); ?></strong> <?php echo esc_html( absint( $analytics['clicks'] ) ); ?></p>
            </div>

            <div id="logs" class="cw-card">
                <h2><?php esc_html_e( 'Error Log Viewer', 'cashwala-sales-popup' ); ?></h2>
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                    <input type="hidden" name="action" value="cw_sales_popup_clear_logs">
                    <?php wp_nonce_field( 'cw_sales_popup_clear_logs_action', 'cw_sales_popup_clear_logs_nonce' ); ?>
                    <?php submit_button( __( 'Clear Logs', 'cashwala-sales-popup' ), 'secondary', 'submit', false ); ?>
                </form>
                <table class="widefat striped">
                    <thead><tr><th><?php esc_html_e( 'Time', 'cashwala-sales-popup' ); ?></th><th><?php esc_html_e( 'Level', 'cashwala-sales-popup' ); ?></th><th><?php esc_html_e( 'Message', 'cashwala-sales-popup' ); ?></th><th><?php esc_html_e( 'Context', 'cashwala-sales-popup' ); ?></th></tr></thead>
                    <tbody>
                    <?php if ( empty( $logs ) ) : ?>
                        <tr><td colspan="4"><?php esc_html_e( 'No logs found.', 'cashwala-sales-popup' ); ?></td></tr>
                    <?php else : ?>
                        <?php foreach ( $logs as $log ) : ?>
                            <tr>
                                <td><?php echo esc_html( $log['time'] ?? '' ); ?></td>
                                <td><?php echo esc_html( strtoupper( $log['level'] ?? '' ) ); ?></td>
                                <td><?php echo esc_html( $log['message'] ?? '' ); ?></td>
                                <td><code><?php echo esc_html( $log['context'] ?? '' ); ?></code></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <style>
            .cw-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-top: 20px; }
            .nav-tab-wrapper { margin-bottom: 12px; }
        </style>
        <?php
    }
}
