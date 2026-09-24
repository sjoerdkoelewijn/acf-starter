<?php
/**
 * One post in a list.
 *
 * @package ACF_Starter
 */

defined( 'ABSPATH' ) || exit;
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'card' ); ?>>

	<?php if ( has_post_thumbnail() ) : ?>
		<a class="card__media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
			<?php the_post_thumbnail( 'sgwrd-card', array( 'loading' => 'lazy' ) ); ?>
		</a>
	<?php endif; ?>

	<div class="card__body">

		<h2 class="card__title">
			<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
		</h2>

		<?php if ( 'post' === get_post_type() ) : ?>
			<p class="card__meta">
				<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>">
					<?php echo esc_html( get_the_date() ); ?>
				</time>
			</p>
		<?php endif; ?>

		<div class="card__excerpt">
			<?php the_excerpt(); ?>
		</div>

	</div>

</article>
