<?php
/**
 * Recent posts block.
 *
 * @param array $block The block settings and attributes.
 *
 * @package ACF_Starter
 */

defined( 'ABSPATH' ) || exit;

$heading  = get_field( 'heading' );
$count    = (int) ( get_field( 'count' ) ?: 3 );
$category = get_field( 'category' );

$args = array(
	'post_type'           => 'post',
	'post_status'         => 'publish',
	'posts_per_page'      => max( 1, min( 12, $count ) ),
	'ignore_sticky_posts' => true,
	'no_found_rows'       => true,
);

if ( $category ) {
	$args['cat'] = (int) ( is_object( $category ) ? $category->term_id : $category );
}

$posts_query = new WP_Query( $args );
?>

<section <?php echo sgwrd_block_attributes( $block, 'recent-posts', array( 'post-feed' ) ); ?>>

	<?php if ( $heading ) : ?>
		<h2 class="post-feed__heading"><?php echo esc_html( $heading ); ?></h2>
	<?php endif; ?>

	<?php if ( $posts_query->have_posts() ) : ?>

		<div class="post-feed__grid">
			<?php
			while ( $posts_query->have_posts() ) :
				$posts_query->the_post();
				get_template_part( 'template-parts/content', 'card' );
			endwhile;
			?>
		</div>

	<?php else : ?>

		<p class="block__placeholder"><?php esc_html_e( 'There are no posts yet.', 'acf-starter' ); ?></p>

	<?php endif; ?>

</section>

<?php
wp_reset_postdata();
