<?php
/**
 * Hero block.
 *
 * Two layouts:
 *
 *   cover  The background fills the block and the text sits on it. The
 *          background is an image or a video. A dark overlay sits between the
 *          two, and the editor sets how strong that overlay is.
 *   side   The image sits next to the text.
 *
 * The overlay strength goes to the CSS as the custom property --hero-overlay.
 * The template prints no colour and no size of its own.
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
$layout  = get_field( 'layout' ) ?: 'cover';
$size    = get_field( 'size' ) ?: 'medium';

$is_cover = ( 'cover' === $layout );
$video    = ( $is_cover && 'video' === get_field( 'background_media' ) ) ? get_field( 'video' ) : null;
$video_url = ! empty( $video['url'] ) ? $video['url'] : '';

$classes = array( 'hero', 'hero--' . $layout, 'hero--' . $size );
$styles  = array();

if ( ! $image && ! $video_url ) {
	$classes[] = 'hero--no-image';
}

if ( $video_url ) {
	$classes[] = 'hero--video';
}

if ( $is_cover ) {
	// ACF gives a whole number from 0 to 100. The CSS wants a fraction.
	$overlay = get_field( 'overlay_opacity' );
	$overlay = is_numeric( $overlay ) ? max( 0, min( 100, (int) $overlay ) ) : 40;

	$styles['hero-overlay'] = (string) round( $overlay / 100, 2 );
}
?>

<section <?php echo sgwrd_block_attributes( $block, 'hero', $classes, $styles ); ?>>

	<?php if ( $is_cover && ( $image || $video_url ) ) : ?>
		<div class="hero__background" aria-hidden="true">

			<?php
			/*
			 * The poster is a real <img>, not a CSS background image. The
			 * browser then gets a srcset, and it can treat the image as the
			 * largest element on the page. It also shows while the video
			 * loads, and instead of the video when the visitor asked for
			 * less motion. See the media query in assets/css/style.css.
			 */
			if ( $image ) {
				sgwrd_the_image(
					$image,
					'sgwrd-wide',
					array(
						'alt'           => '',
						'class'         => 'hero__poster',
						'loading'       => 'eager',
						'fetchpriority' => 'high',
					)
				);
			}
			?>

			<?php if ( $video_url ) : ?>
				<video
					class="hero__video"
					autoplay
					muted
					loop
					playsinline
					preload="metadata"
					tabindex="-1"
					<?php echo $image ? 'poster="' . esc_url( $image['sizes']['large'] ?? $image['url'] ) . '"' : ''; ?>
				>
					<source src="<?php echo esc_url( $video_url ); ?>" type="<?php echo esc_attr( $video['mime_type'] ?? 'video/mp4' ); ?>">
				</video>
			<?php endif; ?>

		</div>
	<?php endif; ?>

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

	<?php if ( ! $is_cover && $image ) : ?>
		<div class="hero__media">
			<?php
			sgwrd_the_image(
				$image,
				'sgwrd-wide',
				array(
					'loading'       => 'eager',
					'fetchpriority' => 'high',
				)
			);
			?>
		</div>
	<?php endif; ?>

	<?php if ( $is_preview && ! $heading ) : ?>
		<p class="block__placeholder"><?php esc_html_e( 'Hero: add a title in the sidebar.', 'acf-starter' ); ?></p>
	<?php endif; ?>

</section>
