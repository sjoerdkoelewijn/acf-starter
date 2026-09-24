<?php
/**
 * Theme shortcodes.
 *
 * @package ACF_Starter
 */

defined( 'ABSPATH' ) || exit;

/**
 * Print the current year. Use it as [year] in a footer line.
 *
 * @return string
 */
function sgwrd_year_shortcode() {

	return esc_html( wp_date( 'Y' ) );
}
add_shortcode( 'year', 'sgwrd_year_shortcode' );

/**
 * Print the site name. Use it as [site_name].
 *
 * @return string
 */
function sgwrd_site_name_shortcode() {

	return esc_html( get_bloginfo( 'name' ) );
}
add_shortcode( 'site_name', 'sgwrd_site_name_shortcode' );
