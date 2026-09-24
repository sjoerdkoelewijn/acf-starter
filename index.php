<?php
/**
 * The fallback template.
 *
 * WordPress uses this template when no other template matches.
 *
 * @package ACF_Starter
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="main" class="site-main">

	<?php if ( have_posts() ) : ?>

		<?php
		/*
		 * A category with a banner or an intro prints its own header. Every
		 * other archive gets the plain one.
		 */
		sgwrd_the_term_header();
		?>

		<?php if ( ! is_front_page() && ! sgwrd_has_term_header() ) : ?>
			<header class="archive-header">
				<h1 class="archive-header__title"><?php echo esc_html( get_the_archive_title() ); ?></h1>
				<?php the_archive_description( '<div class="archive-header__description">', '</div>' ); ?>
			</header>
		<?php endif; ?>

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

	<?php if ( is_category() && sgwrd_has_faq( get_queried_object() ) ) : ?>
		<section class="faq faq--archive">
			<?php sgwrd_the_faq( get_queried_object() ); ?>
		</section>
	<?php endif; ?>

</main>

<?php
get_footer();
