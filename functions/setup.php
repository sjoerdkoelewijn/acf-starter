<?php
/**
 * Theme setup: theme support, navigation menus and image sizes.
 *
 * @package ACF_Starter
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register theme support.
 */
function sgwrd_setup() {

	// Translations. Put .mo files in /languages.
	load_theme_textdomain( SGWRD_TEXTDOMAIN, SGWRD_DIR . '/languages' );

	// WordPress prints the <title> tag.
	add_theme_support( 'title-tag' );

	// Featured images.
	add_theme_support( 'post-thumbnails' );

	// Clean HTML5 markup instead of the old XHTML markup.
	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' )
	);

	// A logo that the client can change. The design stays in the CSS.
	add_theme_support(
		'custom-logo',
		array(
			'height'               => 64,
			'width'                => 240,
			'flex-height'          => true,
			'flex-width'           => true,
			'unlink-homepage-logo' => true,
		)
	);

	// Wide and full width alignment for blocks.
	add_theme_support( 'align-wide' );

	// Load the editor stylesheet, so the editor looks like the front end.
	add_theme_support( 'editor-styles' );

	// Let plugins and blocks add the elements they need to <head>.
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'responsive-embeds' );

	// Navigation menus.
	register_nav_menus(
		array(
			'primary' => __( 'Primary menu', 'acf-starter' ),
			'footer'  => __( 'Footer menu', 'acf-starter' ),
			'legal'   => __( 'Legal menu', 'acf-starter' ),
		)
	);

	/*
	 * Let the content flow to the full width.
	 * The layout widths come from theme.json, not from $content_width.
	 */
	add_image_size( 'sgwrd-card', 720, 900, true );
	add_image_size( 'sgwrd-wide', 1600, 900, true );
}
add_action( 'after_setup_theme', 'sgwrd_setup' );

/**
 * Stop WordPress from making image sizes that the theme never shows.
 *
 * @param array $sizes Image size names.
 * @return array
 */
function sgwrd_remove_unused_image_sizes( $sizes ) {

	foreach ( array( 'medium_large', '1536x1536', '2048x2048' ) as $size ) {
		unset( $sizes[ $size ] );
	}

	return $sizes;
}
add_filter( 'intermediate_image_sizes_advanced', 'sgwrd_remove_unused_image_sizes' );

/**
 * Register the widget areas.
 *
 * The theme has one footer area only. Use blocks or ACF for all other content.
 */
function sgwrd_widgets_init() {

	register_sidebar(
		array(
			'name'          => __( 'Footer', 'acf-starter' ),
			'id'            => 'footer',
			'description'   => __( 'Shows in the footer, above the copyright line.', 'acf-starter' ),
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<h2 class="widget__title">',
			'after_title'   => '</h2>',
		)
	);
}
add_action( 'widgets_init', 'sgwrd_widgets_init' );
