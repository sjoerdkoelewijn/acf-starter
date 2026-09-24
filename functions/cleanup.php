<?php
/**
 * Remove the default WordPress output that this theme does not use.
 *
 * Each item is a small function with its own hook. To keep one of them,
 * remove the matching add_action() or add_filter() line in a child theme.
 *
 * @package ACF_Starter
 */

defined( 'ABSPATH' ) || exit;

/**
 * Remove the emoji scripts, styles and filters.
 */
function sgwrd_disable_emojis() {

	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	remove_action( 'embed_head', 'print_emoji_detection_script' );

	add_filter( 'tiny_mce_plugins', 'sgwrd_disable_emojis_tinymce' );
	add_filter( 'wp_resource_hints', 'sgwrd_disable_emojis_resource_hints', 10, 2 );
}
add_action( 'init', 'sgwrd_disable_emojis' );

/**
 * Remove the emoji plugin from TinyMCE.
 *
 * @param array $plugins TinyMCE plugins.
 * @return array
 */
function sgwrd_disable_emojis_tinymce( $plugins ) {

	return is_array( $plugins ) ? array_diff( $plugins, array( 'wpemoji' ) ) : array();
}

/**
 * Remove the DNS prefetch for the emoji CDN.
 *
 * @param array  $urls          URLs for the given relation.
 * @param string $relation_type The relation type.
 * @return array
 */
function sgwrd_disable_emojis_resource_hints( $urls, $relation_type ) {

	if ( 'dns-prefetch' !== $relation_type ) {
		return $urls;
	}

	$emoji_url = 'https://s.w.org/images/core/emoji/';

	foreach ( $urls as $key => $url ) {
		$value = is_array( $url ) ? ( $url['href'] ?? '' ) : $url;

		if ( is_string( $value ) && str_contains( $value, $emoji_url ) ) {
			unset( $urls[ $key ] );
		}
	}

	return array_values( $urls );
}

/**
 * Remove the tags in <head> that this theme does not need.
 */
function sgwrd_clean_head() {

	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'wp_shortlink_wp_head' );
	remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10 );
	remove_action( 'wp_head', 'feed_links_extra', 3 );
}
add_action( 'init', 'sgwrd_clean_head' );

/**
 * Remove the WordPress version from the generator tag and from asset URLs.
 *
 * @return string
 */
function sgwrd_remove_generator() {

	return '';
}
add_filter( 'the_generator', 'sgwrd_remove_generator' );

/**
 * Remove the duotone SVG filters. The theme turns duotone off in theme.json.
 */
function sgwrd_remove_duotone_filters() {

	remove_action( 'wp_body_open', 'wp_global_styles_render_svg_filters' );
	remove_action( 'in_admin_header', 'wp_global_styles_render_svg_filters' );
}
add_action( 'after_setup_theme', 'sgwrd_remove_duotone_filters' );

/**
 * Remove the stylesheets that give core blocks their default look.
 *
 * The theme keeps 'wp-block-library', which holds the layout rules that the
 * blocks need. It removes only the decoration on top of it.
 */
function sgwrd_dequeue_default_block_styles() {

	if ( is_admin() ) {
		return;
	}

	wp_dequeue_style( 'wp-block-library-theme' );
	wp_dequeue_style( 'classic-theme-styles' );
	wp_deregister_style( 'classic-theme-styles' );
}
add_action( 'wp_enqueue_scripts', 'sgwrd_dequeue_default_block_styles', 100 );

/**
 * Turn XML-RPC off, with the pingback header and the pingback method.
 *
 * @return bool
 */
function sgwrd_disable_xmlrpc() {

	return false;
}
add_filter( 'xmlrpc_enabled', 'sgwrd_disable_xmlrpc' );

/**
 * Remove the X-Pingback header.
 *
 * @param array $headers Response headers.
 * @return array
 */
function sgwrd_remove_pingback_header( $headers ) {

	unset( $headers['X-Pingback'] );

	return $headers;
}
add_filter( 'wp_headers', 'sgwrd_remove_pingback_header' );

/**
 * Remove jQuery Migrate. The theme uses no jQuery of its own.
 *
 * @param WP_Scripts $scripts The script registry.
 */
function sgwrd_remove_jquery_migrate( $scripts ) {

	if ( is_admin() || empty( $scripts->registered['jquery'] ) ) {
		return;
	}

	$scripts->registered['jquery']->deps = array_diff(
		$scripts->registered['jquery']->deps,
		array( 'jquery-migrate' )
	);
}
add_action( 'wp_default_scripts', 'sgwrd_remove_jquery_migrate' );

/**
 * Remove the oEmbed discovery links and the wp-embed script.
 */
function sgwrd_disable_embeds() {

	remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
	remove_action( 'wp_head', 'wp_oembed_add_host_js' );

	add_filter( 'embed_oembed_discover', '__return_false' );
}
add_action( 'init', 'sgwrd_disable_embeds' );

/**
 * Send a strict referrer policy and stop MIME type sniffing.
 *
 * @param array $headers Response headers.
 * @return array
 */
function sgwrd_security_headers( $headers ) {

	$headers['X-Content-Type-Options'] = 'nosniff';
	$headers['Referrer-Policy']        = 'strict-origin-when-cross-origin';

	return $headers;
}
add_filter( 'wp_headers', 'sgwrd_security_headers' );
