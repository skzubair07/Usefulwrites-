<?php
/**
 * Like/Share module with viral referral engine.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DO_Like_Share_Module {

    public static function init() {
        add_action( 'init', array( __CLASS__, 'track_referral_open' ) );
        add_action( 'wp_ajax_do_like_masail', array( __CLASS__, 'like_masail' ) );
        add_action( 'wp_ajax_nopriv_do_like_masail', array( __CLASS__, 'like_masail' ) );
        add_action( 'wp_footer', array( __CLASS__, 'render_share_script' ) );
    }

    public static function like_masail() {
        check_ajax_referer( 'do_ajax_nonce', 'nonce' );
        global $wpdb;
        $id = absint( $_POST['id'] ?? 0 );
        $wpdb->query( $wpdb->prepare( 'UPDATE ' . $wpdb->prefix . 'do_masail SET likes = likes + 1 WHERE id = %d', $id ) );

        if ( is_user_logged_in() && class_exists( 'DO_ForYou_Module' ) ) {
            DO_ForYou_Module::track_like( get_current_user_id(), $id );
        }

        wp_send_json_success();
    }

    /**
     * Share link: site.com/masail/{id}?ref={user_id}
     */
    public static function get_share_link( $id ) {
        $ref = is_user_logged_in() ? get_current_user_id() : 0;
        return add_query_arg( array( 'ref' => $ref ), home_url( '/masail/' . absint( $id ) ) );
    }

    /**
     * Track referral when shared link is opened.
     */
    public static function track_referral_open() {
        if ( empty( $_GET['ref'] ) ) {
            return;
        }

        $request_uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) );
        if ( ! preg_match( '#/masail/(\d+)#', $request_uri, $match ) ) {
            return;
        }

        $masail_id    = absint( $match[1] );
        $referrer_id  = absint( $_GET['ref'] );
        $visitor_id   = get_current_user_id();

        if ( $referrer_id <= 0 || $referrer_id === $visitor_id ) {
            return;
        }

        $log = get_option( 'do_share_referrals', array() );
        $log[] = array(
            'masail_id'   => $masail_id,
            'referrer_id' => $referrer_id,
            'visitor_id'  => $visitor_id,
            'opened_at'   => current_time( 'mysql' ),
        );

        update_option( 'do_share_referrals', array_slice( $log, -1000 ) );
        setcookie( 'do_referrer_id', (string) $referrer_id, time() + MONTH_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
        $_COOKIE['do_referrer_id'] = (string) $referrer_id;
    }

    /**
     * Inject WhatsApp and copy-link share controls below each masail card.
     */
    public static function render_share_script() {
        if ( is_admin() ) {
            return;
        }
        ?>
        <script>
        jQuery(function($){
            $('.do-masail-card').each(function(){
                var id = $(this).data('id');
                if(!id){ return; }

                var shareUrl = '<?php echo esc_js( home_url( '/masail/' ) ); ?>' + id + '?ref=<?php echo (int) get_current_user_id(); ?>';
                var msg = 'Ye masla dekho 👇\n' + shareUrl;
                var wa = 'https://wa.me/?text=' + encodeURIComponent(msg);

                var html = '<div class="do-share-tools">'
                    + '<p><em>Agar faida hua ho to share karein</em></p>'
                    + '<a class="button" target="_blank" rel="noopener" href="'+wa+'">WhatsApp</a> '
                    + '<button type="button" class="button do-copy-link" data-link="'+shareUrl+'">Copy Link</button> '
                    + '<a class="button" href="<?php echo esc_js( home_url('/donate') ); ?>">Donate</a> '
                    + '<a class="button" href="<?php echo esc_js( add_query_arg('ask', '1', home_url('/')) ); ?>">Ask another question</a>'
                    + '</div>';

                $(this).append(html);
            });

            $(document).on('click','.do-copy-link',function(){
                var link = $(this).data('link');
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(link);
                } else {
                    var temp = $('<input>');
                    $('body').append(temp);
                    temp.val(link).select();
                    document.execCommand('copy');
                    temp.remove();
                }
                alert('Link copied');
            });
        });
        </script>
        <?php
    }
}

DO_Like_Share_Module::init();
