<?php
/**
 * Plugin Name: Cashwala AI Smart Support System
 * Description: Smart support system with FAQ search, AI fallback chatbot, auto FAQ generation, and unanswered question management.
 * Version: 1.0.0
 * Author: Cashwala
 * Text Domain: cashwala-ai-support
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CW_AI_Smart_Support_System {
    const OPTION_KEY = 'cw_ai_support_settings';
    const NONCE_ACTION = 'cw_ai_support_nonce';

    public function __construct() {
        register_activation_hook( __FILE__, array( __CLASS__, 'activate' ) );

        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );

        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'wp_footer', array( $this, 'render_widget' ) );
        add_shortcode( 'cw_ai_support', array( $this, 'shortcode_ui' ) );

        add_action( 'wp_ajax_cw_ai_support_search', array( $this, 'ajax_search' ) );
        add_action( 'wp_ajax_nopriv_cw_ai_support_search', array( $this, 'ajax_search' ) );
        add_action( 'wp_ajax_cw_ai_support_chat', array( $this, 'ajax_chat' ) );
        add_action( 'wp_ajax_nopriv_cw_ai_support_chat', array( $this, 'ajax_chat' ) );
        add_action( 'wp_ajax_cw_ai_support_save_unanswered', array( $this, 'ajax_save_unanswered' ) );
        add_action( 'admin_post_cw_ai_support_answer_unanswered', array( $this, 'handle_answer_unanswered' ) );
    }

    public static function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $faq_table = $wpdb->prefix . 'cw_ai_support_faq';
        $unanswered_table = $wpdb->prefix . 'cw_ai_support_unanswered';

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql_faq = "CREATE TABLE {$faq_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            question TEXT NOT NULL,
            answer LONGTEXT NOT NULL,
            source VARCHAR(50) NOT NULL DEFAULT 'default',
            category VARCHAR(100) NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY source (source),
            KEY category (category(50))
        ) {$charset_collate};";

        $sql_unanswered = "CREATE TABLE {$unanswered_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            question TEXT NOT NULL,
            context LONGTEXT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'open',
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY status (status)
        ) {$charset_collate};";

        dbDelta( $sql_faq );
        dbDelta( $sql_unanswered );

        $existing = get_option( self::OPTION_KEY );
        if ( ! is_array( $existing ) ) {
            $defaults = self::default_settings();
            add_option( self::OPTION_KEY, $defaults );
        }

        self::insert_default_faqs();
    }

    public static function default_settings() {
        return array(
            'provider' => 'openai',
            'api_key' => '',
            'model_name' => 'gpt-4o-mini',
            'whatsapp_number' => '',
            'support_email' => get_option( 'admin_email' ),
            'enable_whatsapp_fallback' => 1,
            'business_summary' => 'We provide practical digital products and professional support to help customers get fast results.',
            'info_boxes' => "Delivery: Instant digital delivery after successful payment.\nSupport hours: Monday to Saturday, 9 AM to 9 PM.\nRefund policy: Eligible requests are handled quickly and fairly.",
            'max_messages_per_user' => 20,
            'enable_chatbot' => 1,
            'enable_search' => 1,
            'system_prompt' => "You are a professional and confident sales assistant.\nAlways give helpful, clear, and positive answers.\nNever say 'I don't know'.\nIf unsure, guide the user and suggest contacting support.\nKeep answers short and persuasive.",
            'message_max_chars' => 500,
        );
    }

    public static function insert_default_faqs() {
        global $wpdb;
        $table = $wpdb->prefix . 'cw_ai_support_faq';

        $count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE source='default'" );
        if ( $count > 0 ) {
            return;
        }

        $faqs = self::default_faq_dataset();
        $now = current_time( 'mysql' );

        foreach ( $faqs as $item ) {
            $wpdb->insert(
                $table,
                array(
                    'question' => $item['question'],
                    'answer' => $item['answer'],
                    'source' => 'default',
                    'category' => $item['category'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ),
                array( '%s', '%s', '%s', '%s', '%s', '%s' )
            );
        }
    }

    public static function default_faq_dataset() {
        return array(
            array( 'category' => 'Product info', 'question' => 'What is included after purchase?', 'answer' => 'You get complete access to the product files, usage instructions, and support contact details immediately after payment.' ),
            array( 'category' => 'Product info', 'question' => 'Is this a physical product?', 'answer' => 'No, this is a digital product delivered online instantly.' ),
            array( 'category' => 'Product info', 'question' => 'Do I need technical knowledge?', 'answer' => 'No advanced technical skills are required. The setup steps are beginner-friendly.' ),
            array( 'category' => 'Product info', 'question' => 'How fast will I get access?', 'answer' => 'Access is typically instant after successful payment confirmation.' ),
            array( 'category' => 'Product info', 'question' => 'Can I use this for multiple projects?', 'answer' => 'Yes, you can use it for multiple projects according to the included license terms.' ),
            array( 'category' => 'Product info', 'question' => 'Is documentation provided?', 'answer' => 'Yes, clear guidance is included so you can start quickly.' ),
            array( 'category' => 'Product info', 'question' => 'Will this save me time?', 'answer' => 'Yes, the system is designed to reduce manual effort and speed up your workflow.' ),
            array( 'category' => 'Product info', 'question' => 'Can I use it on WordPress?', 'answer' => 'Yes, it is made to work smoothly with WordPress websites.' ),
            array( 'category' => 'Product info', 'question' => 'Is regular update planned?', 'answer' => 'Yes, updates are rolled out to improve quality and compatibility.' ),
            array( 'category' => 'Product info', 'question' => 'How does this help my business?', 'answer' => 'It helps automate repetitive support responses, improves speed, and can increase conversion confidence.' ),

            array( 'category' => 'Installation', 'question' => 'How do I install the plugin?', 'answer' => 'Upload the plugin folder to your plugins directory, activate it, then add your API key in settings.' ),
            array( 'category' => 'Installation', 'question' => 'Do I need to edit code?', 'answer' => 'No code editing is required. Everything is configurable from the plugin settings page.' ),
            array( 'category' => 'Installation', 'question' => 'Where do I add the API key?', 'answer' => 'Go to AI Support System settings in the admin panel and paste your API key in API Settings.' ),
            array( 'category' => 'Installation', 'question' => 'Does it work after activation?', 'answer' => 'Yes, core search and FAQ support work immediately after activation.' ),
            array( 'category' => 'Installation', 'question' => 'Can I place support UI on a page?', 'answer' => 'Yes, use shortcode [cw_ai_support] to place the search interface anywhere.' ),
            array( 'category' => 'Installation', 'question' => 'Will it affect site speed?', 'answer' => 'The plugin is lightweight and optimized to avoid heavy performance impact.' ),
            array( 'category' => 'Installation', 'question' => 'Do I need another plugin for chat?', 'answer' => 'No, chat UI is included with this plugin.' ),
            array( 'category' => 'Installation', 'question' => 'Can I switch providers later?', 'answer' => 'Yes, you can switch between OpenAI and Gemini anytime in settings.' ),
            array( 'category' => 'Installation', 'question' => 'What model should I use?', 'answer' => 'Use a fast and cost-efficient model first, then upgrade if you need deeper responses.' ),
            array( 'category' => 'Installation', 'question' => 'Is mobile supported?', 'answer' => 'Yes, the support UI is responsive and optimized for mobile screens.' ),

            array( 'category' => 'Beginner help', 'question' => 'How should I start as a beginner?', 'answer' => 'Start by asking your common customer questions in search, then test chat fallback for unmatched queries.' ),
            array( 'category' => 'Beginner help', 'question' => 'What is the easiest setup flow?', 'answer' => 'Activate plugin, add API key, set business summary, and your system is ready.' ),
            array( 'category' => 'Beginner help', 'question' => 'What are info boxes used for?', 'answer' => 'Info boxes store key business facts that are checked before API calls to save cost.' ),
            array( 'category' => 'Beginner help', 'question' => 'How does auto FAQ work?', 'answer' => 'When a new question is answered, it can be stored automatically so future users get instant replies.' ),
            array( 'category' => 'Beginner help', 'question' => 'How do unanswered questions help?', 'answer' => 'They show gaps in your support data so you can add better answers and improve response quality.' ),
            array( 'category' => 'Beginner help', 'question' => 'Can I edit generated FAQs?', 'answer' => 'Yes, admin can update answers any time by adding corrected responses to unanswered items.' ),
            array( 'category' => 'Beginner help', 'question' => 'How many messages can users send?', 'answer' => 'You can define max messages per user in system settings.' ),
            array( 'category' => 'Beginner help', 'question' => 'Why is search shown before chat?', 'answer' => 'Search answers instantly from local FAQs and reduces API usage costs.' ),
            array( 'category' => 'Beginner help', 'question' => 'Can I disable chatbot only?', 'answer' => 'Yes, chatbot and search can be enabled or disabled independently.' ),
            array( 'category' => 'Beginner help', 'question' => 'What if users need human help?', 'answer' => 'Contact fallback can show WhatsApp and email for instant support handover.' ),

            array( 'category' => 'Pricing & value', 'question' => 'Will this reduce support cost?', 'answer' => 'Yes, local FAQ and smart matching reduce repeated API and manual support effort.' ),
            array( 'category' => 'Pricing & value', 'question' => 'Is there ongoing value after setup?', 'answer' => 'Yes, the plugin keeps learning by storing answered questions for faster future responses.' ),
            array( 'category' => 'Pricing & value', 'question' => 'Is this useful for small businesses?', 'answer' => 'Absolutely, it helps teams handle more questions without hiring extra support immediately.' ),
            array( 'category' => 'Pricing & value', 'question' => 'Can this improve conversions?', 'answer' => 'Yes, instant confident replies reduce hesitation and improve buyer trust.' ),
            array( 'category' => 'Pricing & value', 'question' => 'Why use AI fallback only?', 'answer' => 'Fallback-first design avoids unnecessary API calls and keeps answers fast and affordable.' ),
            array( 'category' => 'Pricing & value', 'question' => 'Is setup expensive?', 'answer' => 'No, setup is simple and the only required external piece is your API key.' ),
            array( 'category' => 'Pricing & value', 'question' => 'How can I control API spend?', 'answer' => 'Use concise model, max message limits, and rely on FAQ/info matching first.' ),
            array( 'category' => 'Pricing & value', 'question' => 'Does this provide long-term ROI?', 'answer' => 'Yes, each saved FAQ improves automation quality and lowers repeated handling costs.' ),
            array( 'category' => 'Pricing & value', 'question' => 'Can I use my own sales tone?', 'answer' => 'Yes, business summary and prompt style steer response tone to fit your brand voice.' ),
            array( 'category' => 'Pricing & value', 'question' => 'Does it scale with traffic?', 'answer' => 'Yes, search-based responses handle repeated common queries quickly.' ),

            array( 'category' => 'Objections', 'question' => 'What if I am not satisfied?', 'answer' => 'Reach support quickly and we will guide you with the fastest practical solution.' ),
            array( 'category' => 'Objections', 'question' => 'Is this trustworthy?', 'answer' => 'Yes, the plugin stores your business knowledge locally and uses secure API requests when needed.' ),
            array( 'category' => 'Objections', 'question' => 'What if AI gives a weak answer?', 'answer' => 'The system flags unanswered questions so you can add a stronger manual response for future use.' ),
            array( 'category' => 'Objections', 'question' => 'Can I get human support?', 'answer' => 'Yes, you can contact support through WhatsApp or email when instant human help is needed.' ),
            array( 'category' => 'Objections', 'question' => 'Will my data be safe?', 'answer' => 'Yes, inputs are sanitized, output is escaped, and nonces protect AJAX requests.' ),
            array( 'category' => 'Objections', 'question' => 'Does the plugin break existing theme?', 'answer' => 'No, styling is isolated and lightweight to minimize conflicts.' ),
            array( 'category' => 'Objections', 'question' => 'Can I stop it anytime?', 'answer' => 'Yes, you can disable chat or search from settings with one click.' ),
            array( 'category' => 'Objections', 'question' => 'Will users see bad responses?', 'answer' => 'The system prioritizes known answers and uses contact fallback for uncertain cases.' ),
            array( 'category' => 'Objections', 'question' => 'Is refund support available?', 'answer' => 'Refund-related questions are handled quickly by support based on policy and order details.' ),
            array( 'category' => 'Objections', 'question' => 'Can this replace my team?', 'answer' => 'It supports your team by handling repetitive queries so humans can focus on important cases.' ),

            array( 'category' => 'Product info', 'question' => 'Can I customize welcome message?', 'answer' => 'Yes, welcome and system behavior can be adjusted through business summary and settings.' ),
            array( 'category' => 'Installation', 'question' => 'Do I need cron setup?', 'answer' => 'No, this plugin runs core logic on-demand without manual cron configuration.' ),
            array( 'category' => 'Beginner help', 'question' => 'Can I test before going live?', 'answer' => 'Yes, test on your site while logged in, then open to visitors once you are satisfied.' ),
            array( 'category' => 'Pricing & value', 'question' => 'How does smart priority work?', 'answer' => 'It checks FAQ first, then info boxes, then business summary, then API, and lastly contact fallback.' ),
            array( 'category' => 'Objections', 'question' => 'What if my API key is missing?', 'answer' => 'Search and local responses still work; add API key to enable AI fallback responses.' ),
        );
    }

    public function admin_menu() {
        add_menu_page(
            'AI Support System',
            'AI Support System',
            'manage_options',
            'cw-ai-support',
            array( $this, 'settings_page' ),
            'dashicons-format-chat',
            57
        );
    }

    public function register_settings() {
        register_setting( 'cw_ai_support_group', self::OPTION_KEY, array( $this, 'sanitize_settings' ) );
    }

    public function sanitize_settings( $input ) {
        $defaults = self::default_settings();

        $output = array();
        $output['provider'] = ( isset( $input['provider'] ) && in_array( $input['provider'], array( 'openai', 'gemini' ), true ) ) ? $input['provider'] : $defaults['provider'];
        $output['api_key'] = isset( $input['api_key'] ) ? sanitize_text_field( wp_unslash( $input['api_key'] ) ) : '';
        $output['model_name'] = isset( $input['model_name'] ) ? sanitize_text_field( wp_unslash( $input['model_name'] ) ) : $defaults['model_name'];
        $output['whatsapp_number'] = isset( $input['whatsapp_number'] ) ? preg_replace( '/[^0-9]/', '', wp_unslash( $input['whatsapp_number'] ) ) : '';
        $output['support_email'] = isset( $input['support_email'] ) ? sanitize_email( wp_unslash( $input['support_email'] ) ) : '';
        $output['enable_whatsapp_fallback'] = ! empty( $input['enable_whatsapp_fallback'] ) ? 1 : 0;
        $output['business_summary'] = isset( $input['business_summary'] ) ? sanitize_textarea_field( wp_unslash( $input['business_summary'] ) ) : $defaults['business_summary'];
        $output['info_boxes'] = isset( $input['info_boxes'] ) ? sanitize_textarea_field( wp_unslash( $input['info_boxes'] ) ) : $defaults['info_boxes'];
        $output['max_messages_per_user'] = isset( $input['max_messages_per_user'] ) ? max( 1, min( 100, (int) $input['max_messages_per_user'] ) ) : 20;
        $output['enable_chatbot'] = ! empty( $input['enable_chatbot'] ) ? 1 : 0;
        $output['enable_search'] = ! empty( $input['enable_search'] ) ? 1 : 0;
        $output['system_prompt'] = isset( $input['system_prompt'] ) ? sanitize_textarea_field( wp_unslash( $input['system_prompt'] ) ) : $defaults['system_prompt'];
        $output['message_max_chars'] = isset( $input['message_max_chars'] ) ? max( 100, min( 1000, (int) $input['message_max_chars'] ) ) : 500;

        return $output;
    }

    public function settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $settings = $this->get_settings();
        $unanswered = $this->get_unanswered_questions();
        ?>
        <div class="wrap">
            <h1>AI Support System</h1>
            <form method="post" action="options.php">
                <?php settings_fields( 'cw_ai_support_group' ); ?>
                <table class="form-table" role="presentation">
                    <tr><th colspan="2"><h2>1. API Settings</h2></th></tr>
                    <tr>
                        <th scope="row"><label for="cw_provider">API Provider</label></th>
                        <td>
                            <select id="cw_provider" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[provider]">
                                <option value="openai" <?php selected( $settings['provider'], 'openai' ); ?>>OpenAI</option>
                                <option value="gemini" <?php selected( $settings['provider'], 'gemini' ); ?>>Gemini</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="cw_api_key">API Key</label></th>
                        <td><input id="cw_api_key" type="password" class="regular-text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[api_key]" value="<?php echo esc_attr( $settings['api_key'] ); ?>" autocomplete="off"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="cw_model_name">Model Name</label></th>
                        <td><input id="cw_model_name" type="text" class="regular-text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[model_name]" value="<?php echo esc_attr( $settings['model_name'] ); ?>"></td>
                    </tr>

                    <tr><th colspan="2"><h2>2. Contact Settings</h2></th></tr>
                    <tr>
                        <th scope="row"><label for="cw_whatsapp_number">WhatsApp Number</label></th>
                        <td><input id="cw_whatsapp_number" type="text" class="regular-text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[whatsapp_number]" value="<?php echo esc_attr( $settings['whatsapp_number'] ); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="cw_support_email">Support Email</label></th>
                        <td><input id="cw_support_email" type="email" class="regular-text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[support_email]" value="<?php echo esc_attr( $settings['support_email'] ); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row">Enable WhatsApp fallback</th>
                        <td><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[enable_whatsapp_fallback]" value="1" <?php checked( $settings['enable_whatsapp_fallback'], 1 ); ?>> Yes</label></td>
                    </tr>

                    <tr><th colspan="2"><h2>3. Training Settings</h2></th></tr>
                    <tr>
                        <th scope="row"><label for="cw_business_summary">Business Summary</label></th>
                        <td><textarea id="cw_business_summary" class="large-text" rows="4" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[business_summary]"><?php echo esc_textarea( $settings['business_summary'] ); ?></textarea></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="cw_info_boxes">Info Boxes (one per line)</label></th>
                        <td><textarea id="cw_info_boxes" class="large-text" rows="6" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[info_boxes]"><?php echo esc_textarea( $settings['info_boxes'] ); ?></textarea></td>
                    </tr>

                    <tr><th colspan="2"><h2>4. System Settings</h2></th></tr>
                    <tr>
                        <th scope="row"><label for="cw_max_messages">Max messages per user</label></th>
                        <td><input id="cw_max_messages" type="number" min="1" max="100" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[max_messages_per_user]" value="<?php echo esc_attr( (string) $settings['max_messages_per_user'] ); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row">Enable chatbot</th>
                        <td><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[enable_chatbot]" value="1" <?php checked( $settings['enable_chatbot'], 1 ); ?>> Yes</label></td>
                    </tr>
                    <tr>
                        <th scope="row">Enable search system</th>
                        <td><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[enable_search]" value="1" <?php checked( $settings['enable_search'], 1 ); ?>> Yes</label></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="cw_message_max_chars">Max message length</label></th>
                        <td><input id="cw_message_max_chars" type="number" min="100" max="1000" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[message_max_chars]" value="<?php echo esc_attr( (string) $settings['message_max_chars'] ); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="cw_system_prompt">System Prompt</label></th>
                        <td><textarea id="cw_system_prompt" class="large-text" rows="6" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[system_prompt]"><?php echo esc_textarea( $settings['system_prompt'] ); ?></textarea></td>
                    </tr>
                </table>
                <?php submit_button( 'Save Settings' ); ?>
            </form>

            <hr>
            <h2>Unanswered Questions</h2>
            <?php if ( empty( $unanswered ) ) : ?>
                <p>No unanswered questions right now.</p>
            <?php else : ?>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th>Question</th>
                            <th>Date</th>
                            <th>Answer & Save to FAQ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $unanswered as $item ) : ?>
                            <tr>
                                <td><?php echo esc_html( $item->question ); ?></td>
                                <td><?php echo esc_html( $item->created_at ); ?></td>
                                <td>
                                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                                        <input type="hidden" name="action" value="cw_ai_support_answer_unanswered">
                                        <input type="hidden" name="id" value="<?php echo esc_attr( (string) $item->id ); ?>">
                                        <?php wp_nonce_field( 'cw_ai_answer_unanswered_' . $item->id ); ?>
                                        <textarea required name="answer" rows="3" class="large-text"></textarea>
                                        <?php submit_button( 'Save Answer', 'secondary', 'submit', false ); ?>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    public function handle_answer_unanswered() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized' );
        }

        $id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
        check_admin_referer( 'cw_ai_answer_unanswered_' . $id );

        $answer = isset( $_POST['answer'] ) ? sanitize_textarea_field( wp_unslash( $_POST['answer'] ) ) : '';
        if ( $id <= 0 || '' === $answer ) {
            wp_safe_redirect( admin_url( 'admin.php?page=cw-ai-support' ) );
            exit;
        }

        global $wpdb;
        $unanswered_table = $wpdb->prefix . 'cw_ai_support_unanswered';
        $faq_table = $wpdb->prefix . 'cw_ai_support_faq';

        $question = $wpdb->get_var( $wpdb->prepare( "SELECT question FROM {$unanswered_table} WHERE id=%d AND status='open'", $id ) );
        if ( ! $question ) {
            wp_safe_redirect( admin_url( 'admin.php?page=cw-ai-support' ) );
            exit;
        }

        $now = current_time( 'mysql' );
        $wpdb->insert(
            $faq_table,
            array(
                'question' => $question,
                'answer' => $answer,
                'source' => 'manual',
                'category' => 'admin-fixed',
                'created_at' => $now,
                'updated_at' => $now,
            ),
            array( '%s', '%s', '%s', '%s', '%s', '%s' )
        );

        $wpdb->update(
            $unanswered_table,
            array( 'status' => 'resolved' ),
            array( 'id' => $id ),
            array( '%s' ),
            array( '%d' )
        );

        wp_safe_redirect( admin_url( 'admin.php?page=cw-ai-support' ) );
        exit;
    }

    public function enqueue_assets() {
        $settings = $this->get_settings();
        wp_enqueue_style(
            'cw-ai-support-style',
            plugins_url( 'assets/css/style.css', __FILE__ ),
            array(),
            '1.0.0'
        );

        wp_enqueue_script(
            'cw-ai-support-script',
            plugins_url( 'assets/js/app.js', __FILE__ ),
            array(),
            '1.0.0',
            true
        );

        wp_localize_script(
            'cw-ai-support-script',
            'cwAiSupport',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( self::NONCE_ACTION ),
                'maxMessages' => (int) $settings['max_messages_per_user'],
                'messageMaxChars' => (int) $settings['message_max_chars'],
                'enableChatbot' => (int) $settings['enable_chatbot'],
                'enableSearch' => (int) $settings['enable_search'],
                'strings' => array(
                    'searchPlaceholder' => 'Search your problem...',
                    'chatHeader' => 'Ask us anything',
                    'fallbackButton' => 'Still need help? Chat with us',
                    'typing' => 'Thinking...',
                    'limitReached' => 'You reached the maximum number of messages for now.',
                    'emptyMessage' => 'Please type a message first.',
                    'tooLongMessage' => 'Please keep your message shorter.',
                ),
            )
        );
    }

    public function shortcode_ui() {
        ob_start();
        $this->render_search_ui();
        return ob_get_clean();
    }

    public function render_widget() {
        $settings = $this->get_settings();
        if ( ! $settings['enable_search'] && ! $settings['enable_chatbot'] ) {
            return;
        }

        echo '<div id="cw-ai-support-root">';
        if ( $settings['enable_search'] ) {
            $this->render_search_ui();
        }

        if ( $settings['enable_chatbot'] ) {
            ?>
            <button type="button" id="cw-ai-chat-toggle" class="cw-ai-chat-toggle" aria-expanded="false" aria-controls="cw-ai-chat-popup">💬</button>
            <div id="cw-ai-chat-popup" class="cw-ai-chat-popup" aria-hidden="true">
                <div class="cw-ai-chat-header">
                    <strong>Ask us anything</strong>
                    <button type="button" class="cw-ai-chat-close" id="cw-ai-chat-close" aria-label="Close">×</button>
                </div>
                <div class="cw-ai-chat-messages" id="cw-ai-chat-messages"></div>
                <div class="cw-ai-chat-input-wrap">
                    <textarea id="cw-ai-chat-input" rows="2" maxlength="1000" placeholder="Type your message..."></textarea>
                    <button type="button" id="cw-ai-chat-send">Send</button>
                </div>
            </div>
            <?php
        }
        echo '</div>';
    }

    private function render_search_ui() {
        ?>
        <div class="cw-ai-search-box">
            <input type="text" id="cw-ai-search-input" placeholder="Search your problem..." autocomplete="off">
            <div id="cw-ai-search-results" class="cw-ai-search-results"></div>
            <button type="button" id="cw-ai-open-chat-btn" class="cw-ai-open-chat-btn" style="display:none;">Still need help? Chat with us</button>
        </div>
        <?php
    }

    public function ajax_search() {
        check_ajax_referer( self::NONCE_ACTION, 'nonce' );

        $query = isset( $_POST['query'] ) ? sanitize_text_field( wp_unslash( $_POST['query'] ) ) : '';
        if ( '' === $query ) {
            wp_send_json_success( array( 'results' => array() ) );
        }

        $results = $this->search_faqs( $query, 5 );
        wp_send_json_success( array( 'results' => $results ) );
    }

    public function ajax_chat() {
        check_ajax_referer( self::NONCE_ACTION, 'nonce' );

        $settings = $this->get_settings();
        if ( ! $settings['enable_chatbot'] ) {
            wp_send_json_error( array( 'message' => 'Chatbot is disabled.' ), 400 );
        }

        $message = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';
        $message = trim( $message );

        if ( '' === $message ) {
            wp_send_json_error( array( 'message' => 'Empty message.' ), 400 );
        }

        if ( strlen( $message ) > (int) $settings['message_max_chars'] ) {
            wp_send_json_error( array( 'message' => 'Message too long.' ), 400 );
        }

        $reply = $this->resolve_answer( $message, $settings );
        wp_send_json_success( $reply );
    }

    public function ajax_save_unanswered() {
        check_ajax_referer( self::NONCE_ACTION, 'nonce' );

        $question = isset( $_POST['question'] ) ? sanitize_textarea_field( wp_unslash( $_POST['question'] ) ) : '';
        $question = trim( $question );

        if ( '' === $question ) {
            wp_send_json_error( array( 'message' => 'Empty question.' ), 400 );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'cw_ai_support_unanswered';
        $wpdb->insert(
            $table,
            array(
                'question' => $question,
                'context' => '',
                'status' => 'open',
                'created_at' => current_time( 'mysql' ),
            ),
            array( '%s', '%s', '%s', '%s' )
        );

        wp_send_json_success( array( 'saved' => true ) );
    }

    private function resolve_answer( $message, $settings ) {
        $faq_hit = $this->search_faqs( $message, 1 );
        if ( ! empty( $faq_hit ) ) {
            return array(
                'answer' => $faq_hit[0]['answer'],
                'source' => 'faq',
            );
        }

        $info_hit = $this->search_info_boxes( $message, $settings['info_boxes'] );
        if ( $info_hit ) {
            return array(
                'answer' => $info_hit,
                'source' => 'info_box',
            );
        }

        $business_hit = $this->search_business_summary( $message, $settings['business_summary'] );
        if ( $business_hit ) {
            return array(
                'answer' => $business_hit,
                'source' => 'business_summary',
            );
        }

        $api_answer = $this->call_provider( $message, $settings );
        if ( $api_answer ) {
            $this->save_faq( $message, $api_answer, 'ai', 'auto-generated' );
            return array(
                'answer' => $api_answer,
                'source' => 'api',
            );
        }

        $this->save_unanswered( $message );
        return array(
            'answer' => $this->contact_fallback_message( $settings ),
            'source' => 'fallback',
        );
    }

    private function search_info_boxes( $message, $info_boxes_raw ) {
        $lines = array_filter( array_map( 'trim', explode( "\n", (string) $info_boxes_raw ) ) );
        $message_lower = strtolower( $message );

        foreach ( $lines as $line ) {
            $parts = preg_split( '/[:\-]/', $line, 2 );
            $key = isset( $parts[0] ) ? strtolower( trim( $parts[0] ) ) : '';
            if ( $key && false !== strpos( $message_lower, $key ) ) {
                return $line;
            }

            $tokens = preg_split( '/\s+/', strtolower( $line ) );
            $match_count = 0;
            foreach ( $tokens as $token ) {
                if ( strlen( $token ) > 4 && false !== strpos( $message_lower, $token ) ) {
                    $match_count++;
                }
            }
            if ( $match_count >= 2 ) {
                return $line;
            }
        }

        return '';
    }

    private function search_business_summary( $message, $summary ) {
        $tokens = preg_split( '/\s+/', strtolower( (string) $summary ) );
        $message_lower = strtolower( $message );
        $match_count = 0;

        foreach ( $tokens as $token ) {
            $token = trim( $token, "\t\n\r\0\x0B.,!?:;()[]{}\"'" );
            if ( strlen( $token ) > 5 && false !== strpos( $message_lower, $token ) ) {
                $match_count++;
            }
        }

        if ( $match_count >= 2 ) {
            return wp_trim_words( $summary, 40, '...' );
        }

        return '';
    }

    private function call_provider( $user_message, $settings ) {
        if ( empty( $settings['api_key'] ) ) {
            return '';
        }

        $provider = $settings['provider'];
        if ( 'gemini' === $provider ) {
            return $this->call_gemini( $user_message, $settings );
        }

        return $this->call_openai( $user_message, $settings );
    }

    private function call_openai( $user_message, $settings ) {
        $endpoint = 'https://api.openai.com/v1/chat/completions';

        $body = array(
            'model' => $settings['model_name'],
            'temperature' => 0.4,
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => $settings['system_prompt'],
                ),
                array(
                    'role' => 'user',
                    'content' => $user_message,
                ),
            ),
        );

        $response = wp_remote_post(
            $endpoint,
            array(
                'timeout' => 20,
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $settings['api_key'],
                ),
                'body' => wp_json_encode( $body ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return '';
        }

        $code = (int) wp_remote_retrieve_response_code( $response );
        $json = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code >= 200 && $code < 300 && isset( $json['choices'][0]['message']['content'] ) ) {
            return sanitize_textarea_field( $json['choices'][0]['message']['content'] );
        }

        return '';
    }

    private function call_gemini( $user_message, $settings ) {
        $endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode( $settings['model_name'] ) . ':generateContent?key=' . rawurlencode( $settings['api_key'] );

        $body = array(
            'system_instruction' => array(
                'parts' => array(
                    array( 'text' => $settings['system_prompt'] ),
                ),
            ),
            'contents' => array(
                array(
                    'role' => 'user',
                    'parts' => array(
                        array( 'text' => $user_message ),
                    ),
                ),
            ),
            'generationConfig' => array(
                'temperature' => 0.4,
            ),
        );

        $response = wp_remote_post(
            $endpoint,
            array(
                'timeout' => 20,
                'headers' => array(
                    'Content-Type' => 'application/json',
                ),
                'body' => wp_json_encode( $body ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return '';
        }

        $code = (int) wp_remote_retrieve_response_code( $response );
        $json = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code >= 200 && $code < 300 && isset( $json['candidates'][0]['content']['parts'][0]['text'] ) ) {
            return sanitize_textarea_field( $json['candidates'][0]['content']['parts'][0]['text'] );
        }

        return '';
    }

    private function contact_fallback_message( $settings ) {
        $message = "This needs a quick check 👍\nYou can contact us here for instant help:";

        if ( ! empty( $settings['enable_whatsapp_fallback'] ) && ! empty( $settings['whatsapp_number'] ) ) {
            $wa = 'https://wa.me/' . preg_replace( '/[^0-9]/', '', $settings['whatsapp_number'] );
            $message .= "\nWhatsApp: " . esc_url_raw( $wa );
        }

        if ( ! empty( $settings['support_email'] ) ) {
            $message .= "\nEmail: " . sanitize_email( $settings['support_email'] );
        }

        return $message;
    }

    private function save_faq( $question, $answer, $source, $category ) {
        if ( '' === trim( $question ) || '' === trim( $answer ) ) {
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'cw_ai_support_faq';
        $now = current_time( 'mysql' );

        $existing = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE question=%s LIMIT 1",
                $question
            )
        );

        if ( $existing ) {
            $wpdb->update(
                $table,
                array(
                    'answer' => $answer,
                    'updated_at' => $now,
                ),
                array( 'id' => (int) $existing ),
                array( '%s', '%s' ),
                array( '%d' )
            );
            return;
        }

        $wpdb->insert(
            $table,
            array(
                'question' => $question,
                'answer' => $answer,
                'source' => $source,
                'category' => $category,
                'created_at' => $now,
                'updated_at' => $now,
            ),
            array( '%s', '%s', '%s', '%s', '%s', '%s' )
        );
    }

    private function save_unanswered( $question ) {
        global $wpdb;
        $table = $wpdb->prefix . 'cw_ai_support_unanswered';

        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE question=%s AND status='open' LIMIT 1",
                $question
            )
        );

        if ( $exists ) {
            return;
        }

        $wpdb->insert(
            $table,
            array(
                'question' => $question,
                'context' => '',
                'status' => 'open',
                'created_at' => current_time( 'mysql' ),
            ),
            array( '%s', '%s', '%s', '%s' )
        );
    }

    private function search_faqs( $query, $limit = 5 ) {
        global $wpdb;
        $table = $wpdb->prefix . 'cw_ai_support_faq';

        $query = trim( $query );
        if ( '' === $query ) {
            return array();
        }

        $search = '%' . $wpdb->esc_like( $query ) . '%';

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, question, answer, source, category,
                (CASE
                    WHEN question LIKE %s THEN 4
                    WHEN answer LIKE %s THEN 2
                    ELSE 0
                END) AS score
                FROM {$table}
                WHERE question LIKE %s OR answer LIKE %s
                ORDER BY score DESC, updated_at DESC
                LIMIT %d",
                $search,
                $search,
                $search,
                $search,
                (int) $limit
            ),
            ARRAY_A
        );

        $results = array();
        foreach ( $rows as $row ) {
            $results[] = array(
                'id' => (int) $row['id'],
                'question' => sanitize_text_field( $row['question'] ),
                'answer' => wp_kses_post( $row['answer'] ),
                'source' => sanitize_text_field( $row['source'] ),
                'category' => sanitize_text_field( $row['category'] ),
            );
        }

        return $results;
    }

    private function get_unanswered_questions() {
        global $wpdb;
        $table = $wpdb->prefix . 'cw_ai_support_unanswered';
        return $wpdb->get_results( "SELECT id, question, created_at FROM {$table} WHERE status='open' ORDER BY created_at DESC LIMIT 200" );
    }

    private function get_settings() {
        $defaults = self::default_settings();
        $settings = get_option( self::OPTION_KEY, array() );

        if ( ! is_array( $settings ) ) {
            return $defaults;
        }

        return wp_parse_args( $settings, $defaults );
    }
}

new CW_AI_Smart_Support_System();
