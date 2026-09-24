<?php
/**
 * The search results template.
 *
 * @package ACF_Starter
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="main" class="site-main">

	<header class="archive-header">
		<h1 class="archive-header__title">
			<?php
			printf(
				/* translators: %s: the search term */
				esc_html__( 'Results for %s', 'acf-starter' ),
				'<span>' . esc_html( get_search_query() ) . '</span>'
			);
			?>
		</h1>
		<?php get_search_form(); ?>
	</header>

	<?php if ( have_posts() ) : ?>

		<div class="post-list">
			<?php
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/content', 'card' );
			endwhile;
			?>
		</div>

		<?php
		the_posts_pagination(
			array(
				'mid_size'  => 1,
				'prev_text' => esc_html__( 'Previous', 'acf-starter' ),
				'next_text' => esc_html__( 'Next', 'acf-starter' ),
			)
		);
		?>

	<?php else : ?>

		<?php get_template_part( 'template-parts/content', 'none' ); ?>

	<?php endif; ?>

</main>

<?php
get_footer();
