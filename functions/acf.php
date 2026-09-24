<?php
/**
 * Advanced Custom Fields Pro.
 *
 * The field groups live in two places at the same time, and the theme keeps
 * the two in step:
 *
 *   The backend  You edit a field group on the Custom Fields screen, as usual.
 *                ACF writes the change to a JSON file in /acf-json when you
 *                save. See sgwrd_acf_json_save_point() below.
 *
 *   The code     The JSON files are in git. On another site, or after a
 *                deploy, sgwrd_acf_sync_field_groups() reads a newer JSON file
 *                back into the database by itself.
 *
 * So the backend stays the place where you work, and git stays the record. You
 * never click the ACF "Sync" button, and you never lose a change in a deploy.
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
 * Import the field groups from the JSON files into the database.
 *
 * ACF calls this a sync. ACF writes a JSON file each time you save a field
 * group, but it does not read those files back by itself. Without this
 * function you must open Custom Fields and click "Sync" after each deploy.
 *
 * The function makes the sync automatic:
 *
 *   - A group that is in a JSON file, but not in the database, is imported.
 *   - A group whose JSON file is newer than the database record is imported.
 *   - A group whose database record is newer is left alone. You are editing
 *     it right now, and ACF writes the JSON file when you save it.
 *
 * The result is a loop in two directions. You edit a field group in the
 * backend, and ACF writes the change to /acf-json. You commit that file and
 * deploy it, and the next site reads the change back in.
 *
 * To turn the automatic sync off:
 *   add_filter( 'sgwrd_acf_auto_sync', '__return_false' );
 */
function sgwrd_acf_sync_field_groups() {

	if ( wp_doing_ajax() || ! function_exists( 'acf_get_field_groups' ) ) {
		return;
	}

	/**
	 * Filter the automatic sync.
	 *
	 * @param bool $sync True to import newer JSON files by itself.
	 */
	if ( ! apply_filters( 'sgwrd_acf_auto_sync', true ) ) {
		return;
	}

	$imported = array();

	foreach ( acf_get_field_groups() as $group ) {

		$key = $group['key'] ?? '';

		// Look at a group that comes from a JSON file only.
		if ( ! $key || 'json' !== ( $group['local'] ?? '' ) ) {
			continue;
		}

		$id = (int) ( $group['ID'] ?? 0 );

		/*
		 * The group is in the database, and that copy is the same age or
		 * newer. Somebody is working on it in the backend. Leave it alone.
		 */
		if ( $id && (int) ( $group['modified'] ?? 0 ) <= (int) get_post_modified_time( 'U', true, $id, true ) ) {
			continue;
		}

		$local = acf_get_local_field_group( $key );

		if ( ! $local ) {
			continue;
		}

		if ( acf_have_local_fields( $key ) ) {
			$local['fields'] = acf_get_local_fields( $key );
		}

		acf_import_field_group( $local );

		$imported[] = $local['title'] ?? $key;
	}

	if ( $imported ) {
		set_transient( 'sgwrd_acf_synced', $imported, MINUTE_IN_SECONDS );
	}
}
add_action( 'admin_init', 'sgwrd_acf_sync_field_groups', 20 );

/**
 * Tell the user which field groups the sync imported.
 *
 * An import that says nothing is hard to trust. The notice shows once.
 */
function sgwrd_acf_sync_notice() {

	$imported = get_transient( 'sgwrd_acf_synced' );

	if ( ! $imported || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	delete_transient( 'sgwrd_acf_synced' );

	printf(
		'<div class="notice notice-info is-dismissible"><p>%1$s<br><em>%2$s</em></p></div>',
		esc_html(
			sprintf(
				/* translators: %d: number of field groups */
				_n(
					'ACF Starter read %d field group from the theme files.',
					'ACF Starter read %d field groups from the theme files.',
					count( $imported ),
					'acf-starter'
				),
				count( $imported )
			)
		),
		esc_html( implode( ', ', $imported ) )
	);
}
add_action( 'admin_notices', 'sgwrd_acf_sync_notice' );

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
