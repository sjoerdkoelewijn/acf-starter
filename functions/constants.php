<?php
/**
 * Constants and cache busting.
 *
 * @package ACF_Starter
 */

defined( 'ABSPATH' ) || exit;

$sgwrd_theme = wp_get_theme( get_template() );

/** Theme version. Used as the fallback asset version. */
define( 'SGWRD_VERSION', (string) $sgwrd_theme->get( 'Version' ) );

/** Text domain. Keep this the same as the theme folder name. */
define( 'SGWRD_TEXTDOMAIN', 'acf-starter' );

/** Absolute path to the parent theme, with no slash at the end. */
define( 'SGWRD_DIR', untrailingslashit( get_template_directory() ) );

/** URL of the parent theme, with no slash at the end. */
define( 'SGWRD_URI', untrailingslashit( get_template_directory_uri() ) );

/** Absolute path to the active theme. This is the child theme when one is active. */
define( 'SGWRD_CHILD_DIR', untrailingslashit( get_stylesheet_directory() ) );

/** URL of the active theme. This is the child theme when one is active. */
define( 'SGWRD_CHILD_URI', untrailingslashit( get_stylesheet_directory_uri() ) );

unset( $sgwrd_theme );

/**
 * Get a version string for an asset.
 *
 * The function uses the file modification time, so the browser gets the new file
 * after each change. It falls back to the theme version when the file is missing.
 *
 * @param string $relative_path Path inside the theme. Example: 'assets/css/style.css'.
 * @param bool   $child         True to look in the active (child) theme.
 * @return string
 */
function sgwrd_asset_version( $relative_path, $child = false ) {

	$base = $child ? SGWRD_CHILD_DIR : SGWRD_DIR;
	$file = $base . '/' . ltrim( $relative_path, '/' );

	if ( is_readable( $file ) ) {
		return (string) filemtime( $file );
	}

	return SGWRD_VERSION;
}
