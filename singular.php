<?php
/**
 * The template for a single post and for a page.
 *
 * @package ACF_Starter
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="main" class="site-main">

	<?php
	while ( have_posts() ) :
		the_post();
		?>

		<article id="post-<?php the_ID(); ?>" <?php post_class( 'entry' ); ?>>

			<?php if ( is_singular( 'post' ) ) : ?>
				<header class="entry__header">
					<h1 class="entry__title"><?php the_title(); ?></h1>
					<p class="entry__meta">
						<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>">
							<?php echo esc_html( get_the_date() ); ?>
						</time>
					</p>
				</header>

				<?php if ( has_post_thumbnail() ) : ?>
					<figure class="entry__media">
						<?php the_post_thumbnail( 'sgwrd-wide' ); ?>
					</figure>
				<?php endif; ?>
			<?php endif; ?>

			<div class="entry__content">
				<?php
				the_content();

				wp_link_pages(
					array(
						'before' => '<nav class="page-links">',
						'after'  => '</nav>',
					)
				);
				?>
			</div>

		</article>

		<?php
		if ( comments_open() || get_comments_number() ) {
			comments_template();
		}
		?>

	<?php endwhile; ?>

</main>

<?php
get_footer();
