<?php
/**
 * Popup template.
 *
 * @var array $options
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$options = lmp_get_options();
?>
<div id="lmp-overlay" class="lmp-overlay" aria-hidden="true">
    <div id="lmp-popup" class="lmp-popup" role="dialog" aria-modal="true" aria-labelledby="lmp-popup-title">
        <button type="button" class="lmp-close" aria-label="<?php echo esc_attr__( 'Close popup', 'lead-magnet-pro' ); ?>">&times;</button>
        <h2 id="lmp-popup-title" class="lmp-title"><?php echo esc_html( $options['title'] ); ?></h2>
        <p class="lmp-desc"><?php echo esc_html( $options['description'] ); ?></p>

        <form id="lmp-form" class="lmp-form">
            <?php if ( ! empty( $options['show_name'] ) ) : ?>
                <label class="lmp-field-label" for="lmp-name"><?php echo esc_html__( 'Name', 'lead-magnet-pro' ); ?></label>
                <input type="text" id="lmp-name" name="name" class="lmp-input" <?php echo ! empty( $options['required_name'] ) ? 'required' : ''; ?> />
            <?php endif; ?>

            <?php if ( ! empty( $options['show_email'] ) ) : ?>
                <label class="lmp-field-label" for="lmp-email"><?php echo esc_html__( 'Email', 'lead-magnet-pro' ); ?></label>
                <input type="email" id="lmp-email" name="email" class="lmp-input" <?php echo ! empty( $options['required_email'] ) ? 'required' : ''; ?> />
            <?php endif; ?>

            <?php if ( ! empty( $options['show_phone'] ) ) : ?>
                <label class="lmp-field-label" for="lmp-phone"><?php echo esc_html__( 'Phone', 'lead-magnet-pro' ); ?></label>
                <input type="tel" id="lmp-phone" name="phone" class="lmp-input" <?php echo ! empty( $options['required_phone'] ) ? 'required' : ''; ?> />
            <?php endif; ?>

            <input type="hidden" name="page_url" value="<?php echo esc_url( home_url( add_query_arg( null, null ) ) ); ?>" />
            <button type="submit" class="lmp-submit"><?php echo esc_html( $options['submit_button_text'] ); ?></button>
            <p class="lmp-privacy-note"><?php echo esc_html( $options['privacy_note'] ); ?></p>
            <div class="lmp-response" aria-live="polite"></div>
        </form>
    </div>
</div>
