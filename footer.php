<?php
/**
 * The footer and the end of every page.
 *
 * @package ACF_Starter
 */

defined( 'ABSPATH' ) || exit;
?>

<footer class="site-footer">
	<div class="site-footer__inner">

		<?php if ( is_active_sidebar( 'footer' ) ) : ?>
			<div class="site-footer__widgets">
				<?php dynamic_sidebar( 'footer' ); ?>
			</div>
		<?php endif; ?>

		<div class="site-footer__bottom">

			<p class="site-footer__copyright">
				&copy; <?php echo esc_html( wp_date( 'Y' ) ); ?> <?php echo esc_html( get_bloginfo( 'name' ) ); ?>
			</p>

			<nav class="site-footer__nav" aria-label="<?php esc_attr_e( 'Footer', 'acf-starter' ); ?>">
				<?php sgwrd_the_menu( 'footer', array( 'depth' => 1 ) ); ?>
				<?php sgwrd_the_menu( 'legal', array( 'depth' => 1 ) ); ?>
			</nav>

		</div>

	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
