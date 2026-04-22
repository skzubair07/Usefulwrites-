<?php
/**
 * Single masail display template.
 */

$lang = class_exists( 'DO_Language_Module' ) ? DO_Language_Module::get_current_language() : 'english';
$q    = $item['question'];
$a    = $item['answer'];

if ( class_exists( 'DO_Language_Module' ) ) {
    $translated_q = DO_Language_Module::translate_text( $q, $lang );
    $translated_a = DO_Language_Module::translate_text( wp_strip_all_tags( $a ), $lang );
    if ( ! is_wp_error( $translated_q ) ) {
        $q = $translated_q;
    }
    if ( ! is_wp_error( $translated_a ) ) {
        $a = $translated_a;
    }
}

$related = array();
if ( ! empty( $item['category'] ) ) {
    global $wpdb;
    $related = $wpdb->get_results(
        $wpdb->prepare(
            'SELECT id, question FROM ' . $wpdb->prefix . 'do_masail WHERE category = %s AND id != %d ORDER BY created_at DESC LIMIT 3',
            sanitize_text_field( $item['category'] ),
            absint( $item['id'] )
        ),
        ARRAY_A
    );
}
?>
<article class="do-masail-card" data-id="<?php echo esc_attr( $item['id'] ); ?>">
    <h3 class="do-masail-title"><?php echo esc_html( $q ); ?></h3>

    <div class="do-answer-box"><?php echo wp_kses_post( wpautop( $a ) ); ?></div>

    <div class="do-meta">
        <span><strong>Category:</strong> <?php echo esc_html( $item['category'] ); ?></span>
        <span><strong>Keywords:</strong> <?php echo esc_html( $item['keywords'] ); ?></span>
    </div>

    <?php if ( ! empty( $item['source_url'] ) ) : ?>
        <p class="do-source"><a href="<?php echo esc_url( $item['source_url'] ); ?>" target="_blank" rel="noopener">View Source</a></p>
    <?php endif; ?>

    <div class="do-actions">
        <button class="do-like-btn do-btn" data-id="<?php echo esc_attr( $item['id'] ); ?>">❤ Like <span><?php echo esc_html( $item['likes'] ); ?></span></button>
        <a class="do-share-btn do-btn" href="<?php echo esc_url( DO_Like_Share_Module::get_share_link( $item['id'] ) ); ?>">↗ Share</a>
        <button class="do-save-btn do-btn" data-id="<?php echo esc_attr( $item['id'] ); ?>">★ Save</button>
    </div>

    <?php if ( ! empty( $related ) ) : ?>
        <div class="do-related">
            <h4>Related Masail</h4>
            <ul>
                <?php foreach ( $related as $rel ) : ?>
                    <li><?php echo esc_html( $rel['question'] ); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="do-footer-cta">
        <a class="do-btn do-btn-donate" href="<?php echo esc_url( home_url( '/donate' ) ); ?>">Donate</a>
        <a class="do-btn do-btn-ask" href="<?php echo esc_url( add_query_arg( 'ask', '1', home_url( '/' ) ) ); ?>">Ask another question</a>
    </div>
</article>
