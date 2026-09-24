<?php
/**
 * Hero block.
 *
 * @param array  $block      The block settings and attributes.
 * @param string $content    The block inner HTML. Empty for this block.
 * @param bool   $is_preview True while the block shows in the editor.
 *
 * @package ACF_Starter
 */

defined( 'ABSPATH' ) || exit;

$eyebrow = get_field( 'eyebrow' );
$heading = get_field( 'heading' );
$text    = get_field( 'text' );
$image   = get_field( 'image' );
$size    = get_field( 'size' ) ?: 'medium';

$classes = array( 'hero', 'hero--' . $size );

if ( ! $image ) {
	$classes[] = 'hero--no-image';
}
?>

<section <?php echo sgwrd_block_attributes( $block, 'hero', $classes ); ?>>

	<div class="hero__body">

		<?php if ( $eyebrow ) : ?>
			<p class="hero__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
		<?php endif; ?>

		<?php if ( $heading ) : ?>
			<h1 class="hero__heading"><?php echo esc_html( $heading ); ?></h1>
		<?php endif; ?>

		<?php if ( $text ) : ?>
			<div class="hero__text"><?php echo wp_kses_post( $text ); ?></div>
		<?php endif; ?>

		<?php sgwrd_the_buttons(); ?>

	</div>

	<?php if ( $image ) : ?>
		<div class="hero__media">
			<?php sgwrd_the_image( $image, 'sgwrd-wide', array( 'loading' => 'eager', 'fetchpriority' => 'high' ) ); ?>
		</div>
	<?php endif; ?>

	<?php if ( $is_preview && ! $heading ) : ?>
		<p class="block__placeholder"><?php esc_html_e( 'Hero: add a heading in the sidebar.', 'acf-starter' ); ?></p>
	<?php endif; ?>

</section>
