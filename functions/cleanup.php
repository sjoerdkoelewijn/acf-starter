<?php 

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'SGWRD_disable_emojis' ) ) {

	function SGWRD_disable_emojis() {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' ); 
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' ); 
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	}

	add_action( 'init', 'SGWRD_disable_emojis' );

}

if ( ! function_exists( 'SGWRD_remove_duotone' ) ) {

	function SGWRD_remove_duotone() {
		remove_action( 'wp_body_open', 'wp_global_styles_render_svg_filters' );
		remove_action( 'in_admin_header', 'wp_global_styles_render_svg_filters' );
	}

	add_action('after_setup_theme', 'SGWRD_remove_duotone', 10, 0);

}

function SGWRD_remove_wp_version() {
    return '';
}

add_filter('the_generator', 'SGWRD_remove_wp_version');