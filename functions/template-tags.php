<?php
/**
 * Small helper functions for the templates and the blocks.
 *
 * @package ACF_Starter
 */

defined( 'ABSPATH' ) || exit;

/**
 * Check if WooCommerce is active.
 *
 * @return bool
 */
function sgwrd_has_woocommerce() {

	return class_exists( 'WooCommerce' );
}

/**
 * Build the wrapper attributes for an ACF block.
 *
 * The function gives every block the same set of classes and a stable ID, so
 * the CSS in a child theme has something to hold on to.
 *
 * @param array  $block   The ACF block array.
 * @param string $slug    Block slug without the 'acf/' prefix. Example: 'hero'.
 * @param array  $classes Extra CSS classes.
 * @return string Escaped attribute string, ready to print inside a tag.
 */
function sgwrd_block_attributes( $block, $slug, $classes = array() ) {

	$anchor = ! empty( $block['anchor'] ) ? $block['anchor'] : 'block-' . str_replace( 'block_', '', $block['id'] );

	$class_list = array_merge(
		array( 'block', 'block--' . $slug ),
		(array) $classes
	);

	if ( ! empty( $block['className'] ) ) {
		$class_list[] = $block['className'];
	}

	if ( ! empty( $block['align'] ) ) {
		$class_list[] = 'align' . $block['align'];
	}

	$class_list = array_filter( array_unique( $class_list ) );

	return sprintf(
		'id="%s" class="%s"',
		esc_attr( $anchor ),
		esc_attr( implode( ' ', $class_list ) )
	);
}

/**
 * Print a link that ACF returned.
 *
 * ACF gives a link field as an array with url, title and target.
 *
 * @param array|null $link    The ACF link field value.
 * @param string     $classes CSS classes for the link.
 * @return void
 */
function sgwrd_the_link( $link, $classes = 'button' ) {

	if ( empty( $link['url'] ) ) {
		return;
	}

	$target = ! empty( $link['target'] ) ? $link['target'] : '';
	$title  = ! empty( $link['title'] ) ? $link['title'] : $link['url'];

	printf(
		'<a class="%1$s" href="%2$s"%3$s>%4$s</a>',
		esc_attr( $classes ),
		esc_url( $link['url'] ),
		$target ? ' target="' . esc_attr( $target ) . '" rel="noopener"' : '',
		esc_html( $title )
	);
}

/**
 * Print the buttons repeater that several blocks share.
 *
 * @param string $field_name Name of the repeater field.
 * @return void
 */
function sgwrd_the_buttons( $field_name = 'buttons' ) {

	if ( ! function_exists( 'have_rows' ) || ! have_rows( $field_name ) ) {
		return;
	}

	echo '<div class="block__actions">';

	while ( have_rows( $field_name ) ) {
		the_row();

		$style   = get_sub_field( 'style' );
		$link    = get_sub_field( 'link' );
		$classes = 'button button--' . ( $style ? $style : 'primary' );

		if ( 'link' === $style ) {
			$classes = 'text-link';
		}

		sgwrd_the_link( $link, $classes );
	}

	echo '</div>';
}

/**
 * Print a responsive image from an ACF image field.
 *
 * @param array|int|null $image ACF image field value, as an array or an ID.
 * @param string         $size  Image size name.
 * @param array          $attr  Extra HTML attributes.
 * @return void
 */
function sgwrd_the_image( $image, $size = 'large', $attr = array() ) {

	$id = is_array( $image ) ? ( $image['ID'] ?? 0 ) : (int) $image;

	if ( ! $id ) {
		return;
	}

	$attr = wp_parse_args(
		$attr,
		array(
			'loading' => 'lazy',
			'decoding' => 'async',
		)
	);

	echo wp_get_attachment_image( $id, $size, false, $attr );
}

/**
 * Print the site logo, or the site name when there is no logo.
 *
 * @return void
 */
function sgwrd_the_branding() {

	if ( has_custom_logo() ) {
		the_custom_logo();
		return;
	}

	printf(
		'<a class="site-branding__name" href="%1$s" rel="home">%2$s</a>',
		esc_url( home_url( '/' ) ),
		esc_html( get_bloginfo( 'name' ) )
	);
}

/**
 * Print a navigation menu, and print nothing when the menu is empty.
 *
 * @param string $location Menu location name.
 * @param array  $args     Extra wp_nav_menu() arguments.
 * @return void
 */
function sgwrd_the_menu( $location, $args = array() ) {

	if ( ! has_nav_menu( $location ) ) {
		return;
	}

	wp_nav_menu(
		wp_parse_args(
			$args,
			array(
				'theme_location' => $location,
				'container'      => false,
				'menu_class'     => 'menu menu--' . $location,
				'depth'          => 2,
				'fallback_cb'    => false,
			)
		)
	);
}

/**
 * Get the URL of the shop page, or of the blog page when there is no shop.
 *
 * @return string
 */
function sgwrd_shop_url() {

	if ( sgwrd_has_woocommerce() ) {
		$shop_id = wc_get_page_id( 'shop' );

		if ( $shop_id > 0 ) {
			return (string) get_permalink( $shop_id );
		}
	}

	return home_url( '/' );
}
