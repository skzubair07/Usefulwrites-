<?php
/**
 * Frontend master template - mobile-first app layout.
 */

$masail_items = DO_Masail_Module::get_masail( array( 'limit' => 10 ) );
$trending     = DO_Trending_Module::get_trending( 10 );
$news_items   = class_exists( 'DO_News_Module' ) ? DO_News_Module::get_news_items( 6 ) : array();
$lang         = class_exists( 'DO_Language_Module' ) ? DO_Language_Module::get_current_language() : 'english';
$prayer_times = class_exists( 'DO_Prayer_Module' ) ? DO_Prayer_Module::get_prayer_times() : array();
$for_you      = ( class_exists( 'DO_ForYou_Module' ) && is_user_logged_in() ) ? DO_ForYou_Module::get_feed( get_current_user_id(), 6 ) : array();

$banner = get_option(
    'do_ui_banner',
    array(
        'title'    => 'Deoband Online',
        'subtitle' => 'Trusted Islamic Q&A in a clean mobile-first experience',
        'image'    => '',
    )
);

$tr = static function ( $text ) use ( $lang ) {
    if ( class_exists( 'DO_Language_Module' ) ) {
        $translated = DO_Language_Module::translate_text( $text, $lang );
        return is_wp_error( $translated ) ? $text : $translated;
    }
    return $text;
};
?>

<div class="do-app" id="doApp">
    <header class="do-topbar">
        <div class="do-search-wrap">
            <input type="text" id="do-search" placeholder="<?php echo esc_attr( $tr( 'Search masail...' ) ); ?>" autocomplete="off" />
            <span class="do-loading" id="doSearchLoading" aria-hidden="true"></span>
        </div>
        <div class="do-lang-switch" aria-label="Language switcher">
            <a href="<?php echo esc_url( add_query_arg( 'do_lang', 'english' ) ); ?>" class="<?php echo 'english' === $lang ? 'active' : ''; ?>">EN</a>
            <a href="<?php echo esc_url( add_query_arg( 'do_lang', 'hindi' ) ); ?>" class="<?php echo 'hindi' === $lang ? 'active' : ''; ?>">HI</a>
            <a href="<?php echo esc_url( add_query_arg( 'do_lang', 'urdu' ) ); ?>" class="<?php echo 'urdu' === $lang ? 'active' : ''; ?>">UR</a>
        </div>
        <div id="do-search-results" class="do-search-results" aria-live="polite"></div>
    </header>

    <section class="do-hero <?php echo empty( $banner['image'] ) ? 'no-image' : ''; ?>" <?php if ( ! empty( $banner['image'] ) ) : ?>style="background-image:url('<?php echo esc_url( $banner['image'] ); ?>')"<?php endif; ?>>
        <div class="do-hero-overlay">
            <h1><?php echo esc_html( $tr( $banner['title'] ) ); ?></h1>
            <p><?php echo esc_html( $tr( $banner['subtitle'] ) ); ?></p>
        </div>
    </section>

    <section class="do-section">
        <div class="do-section-head">
            <h2><?php echo esc_html( $tr( 'Trending' ) ); ?></h2>
        </div>
        <div class="do-grid do-grid-trending">
            <?php foreach ( $trending as $trend ) : ?>
                <article class="do-trend-card">
                    <span class="rank">#<?php echo esc_html( $trend['id'] ); ?></span>
                    <h3><?php echo esc_html( $trend['question'] ); ?></h3>
                    <p><?php echo esc_html( $tr( 'Trend Score' ) ); ?>: <?php echo esc_html( round( (float) $trend['trend_score'], 1 ) ); ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="do-section">
        <div class="do-section-head">
            <h2><?php echo esc_html( $tr( 'For You' ) ); ?></h2>
        </div>
        <div class="do-grid do-grid-foryou">
            <?php if ( ! empty( $for_you ) ) : ?>
                <?php foreach ( $for_you as $item ) : ?>
                    <?php include DO_PLUGIN_DIR . 'templates/masail-view.php'; ?>
                <?php endforeach; ?>
            <?php else : ?>
                <p class="do-empty"><?php echo esc_html( $tr( 'Start searching and liking posts to personalize this section.' ) ); ?></p>
            <?php endif; ?>
        </div>
    </section>

    <section class="do-section">
        <div class="do-section-head">
            <h2><?php echo esc_html( $tr( 'Latest Masail' ) ); ?></h2>
        </div>
        <div class="do-grid do-grid-masail">
            <?php foreach ( $masail_items as $item ) : ?>
                <?php include DO_PLUGIN_DIR . 'templates/masail-view.php'; ?>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="do-section do-two-col">
        <div class="do-card-box">
            <h2><?php echo esc_html( $tr( 'News' ) ); ?></h2>
            <ul class="do-list-news">
                <?php foreach ( $news_items as $news ) : ?>
                    <li><a href="<?php echo esc_url( $news['link'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $news['title'] ); ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="do-card-box">
            <h2><?php echo esc_html( $tr( 'Prayer Times' ) ); ?></h2>
            <ul class="do-list-prayer">
                <?php if ( is_array( $prayer_times ) ) : ?>
                    <?php foreach ( $prayer_times as $name => $time ) : ?>
                        <li><span><?php echo esc_html( ucfirst( (string) $name ) ); ?></span><strong><?php echo esc_html( (string) $time ); ?></strong></li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
    </section>

    <a href="#doApp" class="do-fab" id="doAskFab"><?php echo esc_html( $tr( 'Ask Question' ) ); ?></a>
</div>
