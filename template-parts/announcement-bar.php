<?php
/**
 * The notice bar above the header.
 *
 * The text comes from the Theme settings page. The bar prints nothing when the
 * switch is off or when there is no text.
 *
 * @package ACF_Starter
 */

defined( 'ABSPATH' ) || exit;

if ( ! sgwrd_option( 'bar_active' ) ) {
	return;
}

$sgwrd_bar_text = sgwrd_option( 'bar_text' );

if ( ! $sgwrd_bar_text ) {
	return;
}

$sgwrd_bar_link = sgwrd_option( 'bar_link', array() );
$sgwrd_bar_url  = $sgwrd_bar_link['url'] ?? '';
?>

<div class="announcement-bar">
	<?php if ( $sgwrd_bar_url ) : ?>
		<a class="announcement-bar__link"
			href="<?php echo esc_url( $sgwrd_bar_url ); ?>"
			<?php echo ! empty( $sgwrd_bar_link['target'] ) ? 'target="' . esc_attr( $sgwrd_bar_link['target'] ) . '" rel="noopener"' : ''; ?>>
			<?php echo esc_html( $sgwrd_bar_text ); ?>
		</a>
	<?php else : ?>
		<span class="announcement-bar__text"><?php echo esc_html( $sgwrd_bar_text ); ?></span>
	<?php endif; ?>
</div>
