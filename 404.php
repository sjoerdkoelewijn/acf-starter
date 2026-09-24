<?php
/**
 * The template for a page that does not exist.
 *
 * @package ACF_Starter
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="main" class="site-main site-main--narrow">

	<div class="message">
		<h1 class="message__title"><?php esc_html_e( 'Page not found', 'acf-starter' ); ?></h1>

		<p class="message__text">
			<?php esc_html_e( 'This page is not here. Use the search or go back to the shop.', 'acf-starter' ); ?>
		</p>

		<?php get_search_form(); ?>

		<p class="message__actions">
			<a class="button button--primary" href="<?php echo esc_url( sgwrd_shop_url() ); ?>">
				<?php esc_html_e( 'Continue shopping', 'acf-starter' ); ?>
			</a>
		</p>
	</div>

</main>

<?php
get_footer();
