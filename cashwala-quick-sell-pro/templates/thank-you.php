<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Thank You</title>
	<?php wp_head(); ?>
</head>
<body class="cwqsp-thankyou-page">
	<div class="cwqsp-thankyou">
		<h1>Thank you for your purchase!</h1>
		<p><?php echo esc_html( $status_msg ); ?></p>
		<p><strong>Product:</strong> <?php echo esc_html( $product_name ); ?></p>
		<p><a class="cwqsp-download-btn" href="<?php echo esc_url( $download ); ?>">Download Now</a></p>
	</div>
	<?php wp_footer(); ?>
</body>
</html>
