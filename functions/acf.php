<?php
/**
 * Advanced Custom Fields Pro.
 *
 * The theme keeps every field group in /acf-json. That folder is the source of
 * truth, and it is in git. The ACF admin screens are open only when WP_DEBUG is
 * on, so nobody can change a field group on a live site by accident.
 *
 * @package ACF_Starter
 */

defined( 'ABSPATH' ) || exit;

/**
 * Save new and changed field groups to the theme folder.
 *
 * @param string $path Current save path.
 * @return string
 */
function sgwrd_acf_json_save_point( $path ) {

	return SGWRD_DIR . '/acf-json';
}
add_filter( 'acf/settings/save_json', 'sgwrd_acf_json_save_point' );

/**
 * Load the field groups from the parent theme and from the child theme.
 *
 * @param array $paths Current load paths.
 * @return array
 */
function sgwrd_acf_json_load_point( $paths ) {

	// Remove the default path, which points at the active theme only.
	unset( $paths[0] );

	$paths[] = SGWRD_DIR . '/acf-json';

	if ( is_child_theme() && is_dir( SGWRD_CHILD_DIR . '/acf-json' ) ) {
		$paths[] = SGWRD_CHILD_DIR . '/acf-json';
	}

	return $paths;
}
add_filter( 'acf/settings/load_json', 'sgwrd_acf_json_load_point' );

/**
 * Show the ACF admin menu only while you develop.
 *
 * Turn WP_DEBUG on in wp-config.php to edit field groups. On a live site the
 * menu stays hidden, and the field groups come from the JSON files.
 *
 * @return bool
 */
function sgwrd_acf_show_admin() {

	/**
	 * Filter the ACF admin menu.
	 *
	 * @param bool $show True to show the ACF menu.
	 */
	return (bool) apply_filters( 'sgwrd_acf_show_admin', defined( 'WP_DEBUG' ) && WP_DEBUG );
}
add_filter( 'acf/settings/show_admin', 'sgwrd_acf_show_admin' );

/**
 * Register every block in the /blocks folder.
 *
 * A block is a folder with a block.json file in it. ACF reads the "acf" key in
 * that file and renders the block with the template it names. You add a block
 * by adding a folder. There is nothing else to change.
 */
function sgwrd_register_blocks() {

	if ( ! function_exists( 'acf_register_block_type' ) ) {
		return;
	}

	foreach ( (array) glob( SGWRD_DIR . '/blocks/*/block.json' ) as $metadata ) {
		register_block_type( dirname( $metadata ) );
	}

	// A child theme may add its own blocks in the same way.
	if ( is_child_theme() ) {
		foreach ( (array) glob( SGWRD_CHILD_DIR . '/blocks/*/block.json' ) as $metadata ) {
			register_block_type( dirname( $metadata ) );
		}
	}
}
add_action( 'init', 'sgwrd_register_blocks', 5 );

/**
 * Add one options page for the details that belong to the client.
 *
 * Keep this page for content only, such as an address or a social link.
 * Design choices belong in the code, not on this page.
 */
function sgwrd_acf_options_page() {

	if ( ! function_exists( 'acf_add_options_page' ) ) {
		return;
	}

	acf_add_options_page(
		array(
			'page_title'      => __( 'Theme settings', 'acf-starter' ),
			'menu_title'      => __( 'Theme settings', 'acf-starter' ),
			'menu_slug'       => 'sgwrd-settings',
			'capability'      => 'manage_options',
			'position'        => 59,
			'icon_url'        => 'dashicons-admin-generic',
			'redirect'        => false,
			'update_button'   => __( 'Save', 'acf-starter' ),
			'updated_message' => __( 'Settings saved.', 'acf-starter' ),
		)
	);
}
add_action( 'acf/init', 'sgwrd_acf_options_page' );

/**
 * Read a theme setting from the options page.
 *
 * @param string $name    Field name.
 * @param mixed  $default Value to return when the field is empty.
 * @return mixed
 */
function sgwrd_option( $name, $default = '' ) {

	if ( ! function_exists( 'get_field' ) ) {
		return $default;
	}

	$value = get_field( $name, 'option' );

	return ( null === $value || '' === $value || array() === $value ) ? $default : $value;
}
