<?php
/**
 * The header and the start of every page.
 *
 * @package ACF_Starter
 */

defined( 'ABSPATH' ) || exit;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link" href="#main"><?php esc_html_e( 'Skip to content', 'acf-starter' ); ?></a>

<header class="site-header" data-sticky>
	<div class="site-header__inner">

		<div class="site-branding">
			<?php sgwrd_the_branding(); ?>
		</div>

		<button
			class="nav-toggle"
			type="button"
			aria-expanded="false"
			aria-controls="site-navigation"
			data-nav-toggle
		>
			<span class="nav-toggle__bars" aria-hidden="true"></span>
			<span class="screen-reader-text"><?php esc_html_e( 'Menu', 'acf-starter' ); ?></span>
		</button>

		<nav id="site-navigation" class="site-nav" aria-label="<?php esc_attr_e( 'Primary', 'acf-starter' ); ?>">
			<?php sgwrd_the_menu( 'primary' ); ?>
		</nav>

		<?php if ( sgwrd_has_woocommerce() ) : ?>
			<div class="site-header__cart">
				<?php sgwrd_the_cart_button(); ?>
			</div>
		<?php endif; ?>

	</div>
</header>
