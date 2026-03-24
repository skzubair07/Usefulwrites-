<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CWBP_Automation {

    public static function init() {
        add_action( 'admin_post_cwbp_save_automation', array( __CLASS__, 'save_automation' ) );
        add_action( 'cwbp_contact_added', array( __CLASS__, 'enqueue_for_new_contact' ), 10, 2 );
    }

    public static function save_automation() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Unauthorized.', 'cashwala-broadcast-pro' ) );
        }

        check_admin_referer( 'cwbp_save_automation' );

        $name         = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
        $trigger      = isset( $_POST['trigger_event'] ) ? sanitize_key( wp_unslash( $_POST['trigger_event'] ) ) : 'new_lead';
        $step_subjects= isset( $_POST['step_subject'] ) ? array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['step_subject'] ) ) : array();
        $step_messages= isset( $_POST['step_message'] ) ? array_map( 'wp_kses_post', (array) wp_unslash( $_POST['step_message'] ) ) : array();
        $step_delays  = isset( $_POST['step_delay'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['step_delay'] ) ) : array();

        $steps = array();
        foreach ( $step_subjects as $i => $subject ) {
            if ( empty( $subject ) || empty( $step_messages[ $i ] ) ) {
                continue;
            }

            $steps[] = array(
                'delay_days' => isset( $step_delays[ $i ] ) ? absint( $step_delays[ $i ] ) : 0,
                'subject'    => $subject,
                'message'    => $step_messages[ $i ],
            );
        }

        global $wpdb;
        $wpdb->insert(
            cwbp_table_automations(),
            array(
                'name'          => $name,
                'trigger_event' => in_array( $trigger, array( 'new_lead', 'new_purchase' ), true ) ? $trigger : 'new_lead',
                'steps'         => wp_json_encode( $steps ),
                'status'        => 'active',
                'created_at'    => current_time( 'mysql' ),
            ),
            array( '%s', '%s', '%s', '%s', '%s' )
        );

        wp_safe_redirect( admin_url( 'admin.php?page=cwbp_automation&created=1' ) );
        exit;
    }

    public static function enqueue_for_new_contact( $contact_id, $status ) {
        $trigger = 'buyer' === $status ? 'new_purchase' : 'new_lead';
        $automations = self::get_active_automations( $trigger );

        global $wpdb;
        foreach ( $automations as $automation ) {
            $wpdb->insert(
                cwbp_table_automation_runs(),
                array(
                    'automation_id' => $automation->id,
                    'contact_id'    => $contact_id,
                    'step_index'    => 0,
                    'execute_at'    => current_time( 'mysql' ),
                    'status'        => 'pending',
                    'created_at'    => current_time( 'mysql' ),
                ),
                array( '%d', '%d', '%d', '%s', '%s', '%s' )
            );
        }
    }

    public static function get_active_automations( $trigger ) {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                'SELECT * FROM ' . cwbp_table_automations() . ' WHERE trigger_event=%s AND status=%s',
                $trigger,
                'active'
            )
        );
    }

    public static function process_runs( $batch = 30 ) {
        global $wpdb;

        $runs = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT * FROM ' . cwbp_table_automation_runs() . ' WHERE status=%s AND execute_at <= %s ORDER BY id ASC LIMIT %d',
                'pending',
                current_time( 'mysql' ),
                absint( $batch )
            )
        );

        foreach ( $runs as $run ) {
            $automation = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . cwbp_table_automations() . ' WHERE id=%d', $run->automation_id ) );
            $contact    = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . cwbp_table_contacts() . ' WHERE id=%d', $run->contact_id ) );

            if ( ! $automation || ! $contact ) {
                $wpdb->update( cwbp_table_automation_runs(), array( 'status' => 'done' ), array( 'id' => $run->id ), array( '%s' ), array( '%d' ) );
                continue;
            }

            $steps = json_decode( $automation->steps, true );
            if ( ! is_array( $steps ) || ! isset( $steps[ $run->step_index ] ) ) {
                $wpdb->update( cwbp_table_automation_runs(), array( 'status' => 'done' ), array( 'id' => $run->id ), array( '%s' ), array( '%d' ) );
                continue;
            }

            $step = $steps[ $run->step_index ];
            $subject = CWBP_Campaigns::personalize_message( $step['subject'], $contact );
            $message = CWBP_Campaigns::personalize_message( $step['message'], $contact );

            $wpdb->insert(
                cwbp_table_email_queue(),
                array(
                    'campaign_id' => null,
                    'contact_id'  => $contact->id,
                    'email'       => $contact->email,
                    'subject'     => $subject,
                    'message'     => $message,
                    'send_after'  => current_time( 'mysql' ),
                    'status'      => 'pending',
                    'attempts'    => 0,
                    'max_attempts'=> 3,
                    'created_at'  => current_time( 'mysql' ),
                ),
                array( '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s' )
            );

            $next_index = (int) $run->step_index + 1;
            if ( isset( $steps[ $next_index ] ) ) {
                $delay_days = isset( $steps[ $next_index ]['delay_days'] ) ? absint( $steps[ $next_index ]['delay_days'] ) : 0;
                $execute_at = gmdate( 'Y-m-d H:i:s', strtotime( '+' . $delay_days . ' days', current_time( 'timestamp', true ) ) );

                $wpdb->update(
                    cwbp_table_automation_runs(),
                    array(
                        'step_index' => $next_index,
                        'execute_at' => $execute_at,
                        'updated_at' => current_time( 'mysql' ),
                    ),
                    array( 'id' => $run->id ),
                    array( '%d', '%s', '%s' ),
                    array( '%d' )
                );
            } else {
                $wpdb->update(
                    cwbp_table_automation_runs(),
                    array(
                        'status'     => 'done',
                        'updated_at' => current_time( 'mysql' ),
                    ),
                    array( 'id' => $run->id ),
                    array( '%s', '%s' ),
                    array( '%d' )
                );
            }
        }
    }

    public static function list_automations() {
        global $wpdb;
        return $wpdb->get_results( 'SELECT * FROM ' . cwbp_table_automations() . ' ORDER BY id DESC' );
    }
}
