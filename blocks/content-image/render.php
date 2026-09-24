<?php
/**
 * Content and image block.
 *
 * @param array $block The block settings and attributes.
 *
 * @package ACF_Starter
 */

defined( 'ABSPATH' ) || exit;

$eyebrow  = get_field( 'eyebrow' );
$heading  = get_field( 'heading' );
$text     = get_field( 'text' );
$image    = get_field( 'image' );
$position = get_field( 'image_position' ) ?: 'right';

$classes = array( 'media-text', 'media-text--' . $position );
?>

<section <?php echo sgwrd_block_attributes( $block, 'content-image', $classes ); ?>>

	<div class="media-text__body">

		<?php if ( $eyebrow ) : ?>
			<p class="media-text__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
		<?php endif; ?>

		<?php if ( $heading ) : ?>
			<h2 class="media-text__heading"><?php echo esc_html( $heading ); ?></h2>
		<?php endif; ?>

		<?php if ( $text ) : ?>
			<div class="media-text__text"><?php echo wp_kses_post( $text ); ?></div>
		<?php endif; ?>

		<?php sgwrd_the_buttons(); ?>

	</div>

	<?php if ( $image ) : ?>
		<figure class="media-text__media">
			<?php sgwrd_the_image( $image, 'large' ); ?>

			<?php if ( ! empty( $image['caption'] ) ) : ?>
				<figcaption class="media-text__caption"><?php echo esc_html( $image['caption'] ); ?></figcaption>
			<?php endif; ?>
		</figure>
	<?php endif; ?>

</section>
