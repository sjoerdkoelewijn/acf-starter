<?php
/**
 * Split hero block.
 *
 * Two cover panels next to each other. Each panel has its own image, its own
 * overlay strength, its own title and summary, and one button. The panels
 * stack on a telephone.
 *
 * Each panel gets the custom property --hero-overlay. The CSS reads it.
 *
 * @param array $block      The block settings and attributes.
 * @param bool  $is_preview True while the block shows in the editor.
 *
 * @package ACF_Starter
 */

defined( 'ABSPATH' ) || exit;

$size = get_field( 'size' ) ?: 'medium';

if ( ! have_rows( 'panels' ) ) {

	if ( $is_preview ) {
		echo '<p class="block__placeholder">' . esc_html__( 'Split hero: add two panels in the sidebar.', 'acf-starter' ) . '</p>';
	}

	return;
}
?>

<section <?php echo sgwrd_block_attributes( $block, 'hero-split', array( 'hero-split', 'hero-split--' . $size ) ); ?>>

	<?php
	while ( have_rows( 'panels' ) ) :
		the_row();

		$image   = get_sub_field( 'image' );
		$eyebrow = get_sub_field( 'eyebrow' );
		$heading = get_sub_field( 'heading' );
		$text    = get_sub_field( 'text' );
		$link    = get_sub_field( 'link' );

		$overlay = get_sub_field( 'overlay_opacity' );
		$overlay = is_numeric( $overlay ) ? max( 0, min( 100, (int) $overlay ) ) : 40;
		?>

		<article class="hero-split__panel" style="--hero-overlay:<?php echo esc_attr( (string) round( $overlay / 100, 2 ) ); ?>">

			<?php if ( $image ) : ?>
				<div class="hero-split__background" aria-hidden="true">
					<?php sgwrd_the_image( $image, 'sgwrd-card', array( 'alt' => '', 'loading' => 'eager' ) ); ?>
				</div>
			<?php endif; ?>

			<div class="hero-split__body">

				<?php if ( $eyebrow ) : ?>
					<p class="hero-split__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
				<?php endif; ?>

				<?php if ( $heading ) : ?>
					<h2 class="hero-split__heading"><?php echo esc_html( $heading ); ?></h2>
				<?php endif; ?>

				<?php if ( $text ) : ?>
					<p class="hero-split__text"><?php echo esc_html( $text ); ?></p>
				<?php endif; ?>

				<?php sgwrd_the_link( $link, 'button button--primary' ); ?>

			</div>

		</article>

	<?php endwhile; ?>

</section>
