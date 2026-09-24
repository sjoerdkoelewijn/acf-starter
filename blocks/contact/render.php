<?php
/**
 * Contact block.
 *
 * The form comes from a form plugin. Paste the shortcode of the plugin in the
 * "Form shortcode" field. The theme adds no form plugin of its own.
 *
 * @param array $block The block settings and attributes.
 *
 * @package ACF_Starter
 */

defined( 'ABSPATH' ) || exit;

$heading   = get_field( 'heading' );
$text      = get_field( 'text' );
$address   = get_field( 'address' );
$email     = get_field( 'email' );
$phone     = get_field( 'phone' );
$shortcode = get_field( 'form_shortcode' );
?>

<section <?php echo sgwrd_block_attributes( $block, 'contact', array( 'contact' ) ); ?>>

	<div class="contact__details">

		<?php if ( $heading ) : ?>
			<h2 class="contact__heading"><?php echo esc_html( $heading ); ?></h2>
		<?php endif; ?>

		<?php if ( $text ) : ?>
			<div class="contact__text"><?php echo wp_kses_post( $text ); ?></div>
		<?php endif; ?>

		<ul class="contact__list">

			<?php if ( $address ) : ?>
				<li class="contact__item contact__item--address"><?php echo nl2br( esc_html( $address ) ); ?></li>
			<?php endif; ?>

			<?php if ( $email ) : ?>
				<li class="contact__item contact__item--email">
					<a href="mailto:<?php echo esc_attr( antispambot( $email ) ); ?>"><?php echo esc_html( antispambot( $email ) ); ?></a>
				</li>
			<?php endif; ?>

			<?php if ( $phone ) : ?>
				<li class="contact__item contact__item--phone">
					<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a>
				</li>
			<?php endif; ?>

		</ul>

	</div>

	<?php if ( $shortcode ) : ?>
		<div class="contact__form">
			<?php echo do_shortcode( wp_kses_post( $shortcode ) ); ?>
		</div>
	<?php endif; ?>

</section>
