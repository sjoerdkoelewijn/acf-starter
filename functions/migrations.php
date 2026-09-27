<?php
/**
 * Content migrations: small scripts that change the content of a live site.
 *
 * A migration makes a page, adds a menu item or sets an option, on top of the
 * database that is there. It never replaces the database, so orders,
 * customers and stock stay as they are.
 *
 * Each migration is one PHP file in migrations/, in the parent or the child
 * theme. The file returns a function:
 *
 *     <?php
 *     defined( 'ABSPATH' ) || exit;
 *
 *     return function () {
 *         $page = sgwrd_migrate_page( 'returns', array( 'title' => 'Returns' ) );
 *         sgwrd_migrate_menu_item( 'footer', 'Returns', $page );
 *     };
 *
 * The file name sets the order and must start with a digit. Use the date:
 * 2026-09-27-add-returns-page.php. A file that starts with "_" is skipped.
 *
 * Run them with WP-CLI:
 *
 *     wp sgwrd migrate status      Which migrations ran, and which wait.
 *     wp sgwrd migrate run         Run the waiting ones, in order.
 *     wp sgwrd migrate run --dry-run
 *     wp sgwrd migrate skip <name> Mark one as done without running it.
 *
 * Each migration runs once. The list of done migrations is in the option
 * sgwrd_migrations, so it moves with the database: after a pull from
 * production, staging knows what already ran there.
 *
 * This file does nothing outside WP-CLI.
 *
 * @package ACF_Starter
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * The option that holds the done migrations.
 */
const SGWRD_MIGRATIONS_OPTION = 'sgwrd_migrations';

/**
 * The folders to look in. The parent first, then the child theme.
 *
 * @return string[]
 */
function sgwrd_migration_dirs() {

	$dirs = array( SGWRD_DIR . '/migrations' );

	if ( is_child_theme() ) {
		$dirs[] = SGWRD_CHILD_DIR . '/migrations';
	}

	return apply_filters( 'sgwrd_migration_dirs', $dirs );
}

/**
 * All migration files, sorted by name.
 *
 * @return array Name => absolute path.
 * @throws RuntimeException When two folders hold the same name.
 */
function sgwrd_migrations() {

	$found = array();

	foreach ( sgwrd_migration_dirs() as $dir ) {
		foreach ( (array) glob( $dir . '/*.php' ) as $path ) {

			$name = basename( $path, '.php' );

			if ( ! preg_match( '/^[0-9][a-z0-9-]*$/', $name ) ) {
				continue;
			}

			if ( isset( $found[ $name ] ) ) {
				throw new RuntimeException( sprintf( 'Two migrations have the name %s. Rename one.', $name ) );
			}

			$found[ $name ] = $path;
		}
	}

	uksort( $found, 'strnatcmp' );

	return $found;
}

/**
 * The done migrations.
 *
 * @return array Name => array( 'at' => ISO date, 'env' => environment, 'skipped' => bool ).
 */
function sgwrd_migrations_done() {
	return (array) get_option( SGWRD_MIGRATIONS_OPTION, array() );
}

/**
 * Mark a migration as done.
 *
 * @param string $name    Migration name.
 * @param bool   $skipped True when it was marked by hand and did not run.
 */
function sgwrd_migration_mark( $name, $skipped = false ) {

	$done          = sgwrd_migrations_done();
	$done[ $name ] = array(
		'at'      => gmdate( 'c' ),
		'env'     => function_exists( 'sgwrd_environment' ) ? sgwrd_environment() : wp_get_environment_type(),
		'skipped' => $skipped,
	);

	update_option( SGWRD_MIGRATIONS_OPTION, $done, false );
}

/* -------------------------------------------------------------------------
 * Helpers for the migration files
 *
 * They are safe to run twice: each one looks for what is already there
 * first. They throw a RuntimeException when WordPress says no, so the run
 * stops and the migration is not marked as done.
 * ---------------------------------------------------------------------- */

/**
 * Stop the migration when WordPress returned an error.
 *
 * @param mixed  $result The value to check.
 * @param string $what   What was being made, for the message.
 * @return mixed The same value.
 * @throws RuntimeException On a WP_Error.
 */
function sgwrd_migrate_check( $result, $what ) {

	if ( is_wp_error( $result ) ) {
		throw new RuntimeException( $what . ': ' . $result->get_error_message() );
	}

	return $result;
}

/**
 * Make a page, or update the page with this slug.
 *
 * Only the keys you give are written. So a page that exists keeps its
 * content when you do not give 'content'.
 *
 * @param string $slug The page slug. Use "parent/child" for a sub page.
 * @param array  $args title, content, status (default publish), parent (a
 *                     slug), template, menu_order, meta (key => value).
 * @return int The page ID.
 */
function sgwrd_migrate_page( $slug, $args = array() ) {

	$existing = get_page_by_path( $slug, OBJECT, 'page' );
	$postarr  = array( 'post_type' => 'page' );

	if ( $existing ) {
		$postarr['ID'] = $existing->ID;
	} else {
		$postarr['post_name']   = basename( $slug );
		$postarr['post_status'] = 'publish';
		$postarr['post_title']  = ucfirst( str_replace( '-', ' ', basename( $slug ) ) );
	}

	$map = array(
		'title'      => 'post_title',
		'content'    => 'post_content',
		'status'     => 'post_status',
		'menu_order' => 'menu_order',
	);

	foreach ( $map as $key => $field ) {
		if ( array_key_exists( $key, $args ) ) {
			$postarr[ $field ] = $args[ $key ];
		}
	}

	$parent = isset( $args['parent'] ) ? $args['parent'] : ( false !== strpos( $slug, '/' ) ? dirname( $slug ) : '' );

	if ( $parent ) {
		$parent_page = get_page_by_path( $parent, OBJECT, 'page' );

		if ( ! $parent_page ) {
			throw new RuntimeException( sprintf( 'Page %s: the parent page %s is not there.', $slug, $parent ) );
		}

		$postarr['post_parent'] = $parent_page->ID;
	}

	if ( isset( $args['template'] ) ) {
		$postarr['page_template'] = $args['template'];
	}

	// wp_update_post() keeps the fields you do not give. wp_insert_post() would
	// empty them.
	$id = $existing
		? wp_update_post( wp_slash( $postarr ), true )
		: wp_insert_post( wp_slash( $postarr ), true );

	$id = sgwrd_migrate_check( $id, 'Page ' . $slug );

	foreach ( isset( $args['meta'] ) ? (array) $args['meta'] : array() as $key => $value ) {
		update_post_meta( $id, $key, $value );
	}

	WP_CLI::log( sprintf( '  page     %s (%d) %s', $slug, $id, $existing ? 'updated' : 'made' ) );

	return (int) $id;
}

/**
 * The markup for one ACF block, to put in the content of a page.
 *
 * ACF stores block data as field name => value plus _field name => field key.
 * This function writes both.
 *
 * @param string $name   Block name, such as 'acf/cta'.
 * @param array  $fields Field name => array( value, field key ).
 * @param array  $attrs  Extra block attributes, such as array( 'align' => 'wide' ).
 * @return string
 */
function sgwrd_migrate_block( $name, $fields, $attrs = array() ) {

	$data = array();

	foreach ( $fields as $field_name => $pair ) {
		$data[ $field_name ]       = $pair[0];
		$data[ '_' . $field_name ] = $pair[1];
	}

	$json = array_merge( array( 'name' => $name, 'data' => $data ), $attrs );

	return '<!-- wp:' . $name . ' ' . wp_json_encode( $json ) . ' /-->' . "\n\n";
}

/**
 * A repeater in the shape an ACF block stores it in. Merge the result into
 * the $fields of sgwrd_migrate_block().
 *
 * @param string $name     Repeater field name.
 * @param string $key      Repeater field key.
 * @param array  $rows     Each row as sub field name => value.
 * @param array  $sub_keys Sub field name => field key.
 * @return array
 */
function sgwrd_migrate_rows( $name, $key, $rows, $sub_keys ) {

	$out = array( $name => array( count( $rows ), $key ) );

	foreach ( array_values( $rows ) as $index => $row ) {
		foreach ( $row as $sub_name => $value ) {
			if ( isset( $sub_keys[ $sub_name ] ) ) {
				$out[ $name . '_' . $index . '_' . $sub_name ] = array( $value, $sub_keys[ $sub_name ] );
			}
		}
	}

	return $out;
}

/**
 * Find a menu, or make it.
 *
 * @param string $menu A theme menu location (primary, footer, legal) that has
 *                     a menu, or the name of a menu. A name that is not there
 *                     makes a new, empty menu.
 * @return int The menu ID.
 */
function sgwrd_migrate_menu( $menu ) {

	$locations = (array) get_nav_menu_locations();

	if ( ! empty( $locations[ $menu ] ) && wp_get_nav_menu_object( $locations[ $menu ] ) ) {
		return (int) $locations[ $menu ];
	}

	$object = wp_get_nav_menu_object( $menu );

	if ( $object ) {
		return (int) $object->term_id;
	}

	$id = sgwrd_migrate_check( wp_create_nav_menu( $menu ), 'Menu ' . $menu );

	WP_CLI::log( sprintf( '  menu     %s (%d) made', $menu, $id ) );

	return (int) $id;
}

/**
 * Add an item to a menu, when the menu does not have it yet.
 *
 * @param string     $menu   A location or a menu name. See sgwrd_migrate_menu().
 * @param string     $title  The label.
 * @param int|string $target A post or page ID, or a URL.
 * @param array      $args   parent (the title of an item in the same menu),
 *                           position (a number, 1 is first).
 * @return int The menu item ID.
 */
function sgwrd_migrate_menu_item( $menu, $title, $target, $args = array() ) {

	$menu_id = sgwrd_migrate_menu( $menu );
	$items   = (array) wp_get_nav_menu_items( $menu_id, array( 'post_status' => 'any' ) );
	$url     = is_int( $target ) ? get_permalink( $target ) : $target;
	$parent  = 0;

	foreach ( $items as $item ) {
		if ( $item->title === $title && ( is_int( $target ) ? (int) $item->object_id === $target : $item->url === $url ) ) {
			WP_CLI::log( sprintf( '  item     %s is already in %s', $title, $menu ) );
			return (int) $item->ID;
		}

		if ( isset( $args['parent'] ) && $item->title === $args['parent'] && ! $item->menu_item_parent ) {
			$parent = (int) $item->ID;
		}
	}

	if ( isset( $args['parent'] ) && ! $parent ) {
		throw new RuntimeException( sprintf( 'Menu %s: the parent item %s is not there.', $menu, $args['parent'] ) );
	}

	// Make room: move the items at this position and after it one down.
	$position = isset( $args['position'] ) ? max( 1, (int) $args['position'] ) : 0;

	if ( $position ) {
		foreach ( $items as $item ) {
			if ( (int) $item->menu_order >= $position ) {
				wp_update_post( array( 'ID' => $item->ID, 'menu_order' => (int) $item->menu_order + 1 ) );
			}
		}
	}

	$data = array(
		'menu-item-title'     => $title,
		'menu-item-status'    => 'publish',
		'menu-item-parent-id' => $parent,
		'menu-item-position'  => $position,
	);

	if ( is_int( $target ) ) {
		$post = get_post( $target );

		if ( ! $post ) {
			throw new RuntimeException( sprintf( 'Menu %s: post %d is not there.', $menu, $target ) );
		}

		$data['menu-item-type']      = 'post_type';
		$data['menu-item-object']    = $post->post_type;
		$data['menu-item-object-id'] = $post->ID;
	} else {
		$data['menu-item-type'] = 'custom';
		$data['menu-item-url']  = $target;
	}

	$id = sgwrd_migrate_check( wp_update_nav_menu_item( $menu_id, 0, $data ), 'Menu item ' . $title );

	WP_CLI::log( sprintf( '  item     %s added to %s', $title, $menu ) );

	return (int) $id;
}

/**
 * Show a menu in a theme location.
 *
 * Use this at the release to switch to a menu you prepared: the new menu
 * gets the location, and the old menu stays as it was.
 *
 * @param string $location Theme menu location.
 * @param string $menu     Menu name.
 */
function sgwrd_migrate_menu_location( $location, $menu ) {

	if ( ! array_key_exists( $location, get_registered_nav_menus() ) ) {
		throw new RuntimeException( sprintf( 'The theme has no menu location %s.', $location ) );
	}

	$object = wp_get_nav_menu_object( $menu );

	if ( ! $object ) {
		throw new RuntimeException( sprintf( 'There is no menu %s.', $menu ) );
	}

	$locations              = (array) get_theme_mod( 'nav_menu_locations', array() );
	$locations[ $location ] = (int) $object->term_id;
	set_theme_mod( 'nav_menu_locations', $locations );

	WP_CLI::log( sprintf( '  location %s now shows %s', $location, $menu ) );
}

/* -------------------------------------------------------------------------
 * The WP-CLI command
 * ---------------------------------------------------------------------- */

/**
 * Run the content migrations of the theme.
 */
class Sgwrd_Migrate_Command {

	/**
	 * Show which migrations ran and which wait.
	 *
	 * ## EXAMPLES
	 *
	 *     wp sgwrd migrate status
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Named arguments.
	 */
	public function status( $args, $assoc_args ) {

		$all  = $this->all();
		$done = sgwrd_migrations_done();
		$rows = array();

		foreach ( $all as $name => $path ) {
			$rows[] = array(
				'migration' => $name,
				'state'     => isset( $done[ $name ] ) ? ( empty( $done[ $name ]['skipped'] ) ? 'done' : 'skipped' ) : 'waiting',
				'when'      => isset( $done[ $name ] ) ? $done[ $name ]['at'] : '',
				'where'     => isset( $done[ $name ] ) ? $done[ $name ]['env'] : '',
			);
		}

		if ( ! $rows ) {
			WP_CLI::log( 'There are no migrations.' );
			return;
		}

		WP_CLI\Utils\format_items( 'table', $rows, array( 'migration', 'state', 'when', 'where' ) );

		$waiting = count( array_diff_key( $all, $done ) );
		WP_CLI::log( sprintf( '%d waiting.', $waiting ) );
	}

	/**
	 * Run the waiting migrations, in order.
	 *
	 * A migration that fails stops the run. It is not marked as done, so the
	 * next run tries it again. The migrations after it wait.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Show what would run. Change nothing.
	 *
	 * ## EXAMPLES
	 *
	 *     wp sgwrd migrate run
	 *     wp sgwrd migrate run --dry-run
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Named arguments.
	 */
	public function run( $args, $assoc_args ) {

		$dry_run = (bool) WP_CLI\Utils\get_flag_value( $assoc_args, 'dry-run', false );
		$waiting = array_diff_key( $this->all(), sgwrd_migrations_done() );

		if ( ! $waiting ) {
			WP_CLI::success( 'Nothing to do. All migrations ran.' );
			return;
		}

		WP_CLI::log( sprintf( 'Environment: %s', function_exists( 'sgwrd_environment' ) ? sgwrd_environment() : wp_get_environment_type() ) );

		foreach ( $waiting as $name => $path ) {

			if ( $dry_run ) {
				WP_CLI::log( 'Would run ' . $name );
				continue;
			}

			WP_CLI::log( 'Running ' . $name );

			try {
				$migration = require $path;

				if ( ! is_callable( $migration ) ) {
					throw new RuntimeException( 'The file does not return a function.' );
				}

				$result = call_user_func( $migration );

				if ( is_wp_error( $result ) ) {
					throw new RuntimeException( $result->get_error_message() );
				}

				if ( false === $result ) {
					throw new RuntimeException( 'The migration returned false.' );
				}
			} catch ( Throwable $e ) {
				WP_CLI::error( sprintf( '%s failed: %s. It is not marked as done. Fix it and run again.', $name, rtrim( $e->getMessage(), '.' ) ) );
			}

			sgwrd_migration_mark( $name );
			WP_CLI::log( '  done' );
		}

		if ( $dry_run ) {
			WP_CLI::success( sprintf( '%d would run. Nothing was changed.', count( $waiting ) ) );
			return;
		}

		wp_cache_flush();

		WP_CLI::success( sprintf( '%d migration(s) ran.', count( $waiting ) ) );
	}

	/**
	 * Mark migrations as done without running them.
	 *
	 * Use it when you did the change by hand already.
	 *
	 * ## OPTIONS
	 *
	 * <name>...
	 * : The migration name, the file name without .php.
	 *
	 * ## EXAMPLES
	 *
	 *     wp sgwrd migrate skip 2026-09-27-add-returns-page
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Named arguments.
	 */
	public function skip( $args, $assoc_args ) {

		$all = $this->all();

		foreach ( $args as $name ) {
			if ( ! isset( $all[ $name ] ) ) {
				WP_CLI::error( sprintf( 'There is no migration %s.', $name ) );
			}
		}

		foreach ( $args as $name ) {
			sgwrd_migration_mark( $name, true );
			WP_CLI::log( 'Skipped ' . $name );
		}

		WP_CLI::success( 'Marked as done.' );
	}

	/**
	 * All migrations, or a clean error.
	 *
	 * @return array Name => path.
	 */
	private function all() {

		try {
			return sgwrd_migrations();
		} catch ( RuntimeException $e ) {
			WP_CLI::error( $e->getMessage() );
		}

		return array();
	}
}

WP_CLI::add_command( 'sgwrd migrate', 'Sgwrd_Migrate_Command' );
