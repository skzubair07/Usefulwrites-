<?php
/**
 * Footer template.
 *
 * @package Cashwala_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$options = cashwala_get_options();
?>
</main>
<footer class="site-footer">
	<div class="container site-footer-inner">
		<div class="footer-copy">
			<p><?php echo esc_html( $options['website_name'] ); ?> © <?php echo esc_html( gmdate( 'Y' ) ); ?></p>
		</div>
		<nav class="footer-nav" aria-label="<?php esc_attr_e( 'Footer links', 'cashwala-theme' ); ?>">
			<?php cashwala_print_links( $options['footer_links'] ); ?>
		</nav>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
