<?php
/**
 * Call to action block.
 *
 * @param array $block The block settings and attributes.
 *
 * @package ACF_Starter
 */

defined( 'ABSPATH' ) || exit;

$heading = get_field( 'heading' );
$text    = get_field( 'text' );
$tone    = get_field( 'tone' ) ?: 'surface';

$classes = array( 'cta', 'cta--' . $tone );
?>

<section <?php echo sgwrd_block_attributes( $block, 'cta', $classes ); ?>>

	<div class="cta__body">

		<?php if ( $heading ) : ?>
			<h2 class="cta__heading"><?php echo esc_html( $heading ); ?></h2>
		<?php endif; ?>

		<?php if ( $text ) : ?>
			<p class="cta__text"><?php echo esc_html( $text ); ?></p>
		<?php endif; ?>

	</div>

	<?php sgwrd_the_buttons(); ?>

</section>
