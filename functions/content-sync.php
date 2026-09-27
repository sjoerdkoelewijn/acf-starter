<?php
/**
 * Content sync: take the content changes from staging to production.
 *
 * Content is pages, posts, categories, menus, the menu locations, a few site
 * options and the theme settings. Never products, orders, customers or stock.
 *
 * How it knows what changed
 *
 *   A baseline is a fingerprint of every content item on staging, taken when
 *   staging is still an exact copy of production. On staging the theme takes
 *   it by itself: at the first admin page load after the copy or a pull.
 *
 *   Export compares staging with its baseline. Only the items that are new or
 *   changed go into the package.
 *
 *   Import on production compares each item with the same baseline:
 *
 *     production = staging now    -> "same", nothing to do
 *     production = the baseline   -> "apply": only staging changed it
 *     item is new on both sides   -> "apply"
 *     anything else               -> "conflict": production changed it too.
 *                                    Skipped, unless you pass --force.
 *
 *   After the import, staging moves its baseline forward for the items that
 *   went over (accept), so the next sync sends only the next changes.
 *
 * IDs and addresses differ between the two sites, so items are matched by
 * slug and path, never by ID. Images are matched by their file in uploads.
 * New images go along in the package. The site address is swapped too.
 *
 * WP-CLI:
 *
 *   wp sgwrd content baseline         Take the baseline now.
 *   wp sgwrd content diff             What changed on this site since the baseline.
 *   wp sgwrd content export <dir>     Write the changes to a folder. On staging.
 *   wp sgwrd content import <dir>     Put them in. On production. --dry-run, --force.
 *   wp sgwrd content accept <dir>     Move the staging baseline forward. On staging.
 *
 * The GitHub workflow runs these for you: content-diff, content-preview and
 * content-sync. See "Content sync" in the README.
 *
 * @package ACF_Starter
 */

defined( 'ABSPATH' ) || exit;

/**
 * The option that holds the baseline.
 */
const SGWRD_CONTENT_BASELINE = 'sgwrd_content_baseline';

/**
 * Stands in for the site address, so the same content has the same
 * fingerprint on both sites.
 */
const SGWRD_CONTENT_HOME = '{{sgwrd:home}}';

/**
 * The package format. Import refuses a package of another version.
 */
const SGWRD_CONTENT_VERSION = 1;

/* -------------------------------------------------------------------------
 * What counts as content
 * ---------------------------------------------------------------------- */

/**
 * Post types to sync. Products are left out on purpose: their stock and
 * price live in the same place as their content.
 *
 * @return string[]
 */
function sgwrd_content_post_types() {
	$types = (array) apply_filters( 'sgwrd_content_post_types', array( 'page', 'post' ) );
	return array_values( array_filter( $types, 'post_type_exists' ) );
}

/**
 * Taxonomies to sync. Their ACF fields (category header, FAQ) go too.
 *
 * @return string[]
 */
function sgwrd_content_taxonomies() {
	$taxonomies = (array) apply_filters( 'sgwrd_content_taxonomies', array( 'category', 'product_cat' ) );
	return array_values( array_filter( $taxonomies, 'taxonomy_exists' ) );
}

/**
 * WordPress options to sync. The theme settings page (ACF) always goes.
 *
 * @return string[]
 */
function sgwrd_content_options() {
	return (array) apply_filters(
		'sgwrd_content_options',
		array( 'blogname', 'blogdescription', 'show_on_front', 'page_on_front', 'page_for_posts' )
	);
}

/**
 * Options that hold a page ID.
 *
 * @return string[]
 */
function sgwrd_content_page_options() {
	return array( 'page_on_front', 'page_for_posts' );
}

/**
 * Meta that is not content: locks, counters, old slugs.
 *
 * @param string $key Meta key.
 * @return bool
 */
function sgwrd_content_skip_meta( $key ) {

	$skip = (array) apply_filters(
		'sgwrd_content_skip_meta',
		array(
			'_edit_lock',
			'_edit_last',
			'_wp_old_slug',
			'_wp_old_date',
			'_encloseme',
			'_pingme',
			'_wp_trash_meta_status',
			'_wp_trash_meta_time',
			'_wp_desired_post_slug',
			'_wp_page_template',
		)
	);

	return in_array( $key, $skip, true )
		|| 0 === strpos( $key, '_oembed_' )
		|| 0 === strpos( $key, 'product_count_' );
}

/**
 * Meta keys that hold an image ID, outside ACF.
 *
 * @return string[]
 */
function sgwrd_content_media_meta() {
	return array( '_thumbnail_id', 'thumbnail_id' );
}

/* -------------------------------------------------------------------------
 * References: an ID on one site, a name on both
 * ---------------------------------------------------------------------- */

/**
 * The path of a post: its slug, with the parent slugs in front for a page.
 * A draft with no slug yet uses its title.
 *
 * @param WP_Post $post The post.
 * @return string
 */
function sgwrd_content_post_path( $post ) {

	$parts = array();
	$node  = $post;
	$depth = 0;

	while ( $node && $depth < 20 ) {
		array_unshift( $parts, '' !== $node->post_name ? $node->post_name : sanitize_title( $node->post_title ) );
		$node = ( is_post_type_hierarchical( $post->post_type ) && $node->post_parent ) ? get_post( $node->post_parent ) : null;
		++$depth;
	}

	return implode( '/', $parts );
}

/**
 * Turn an ID into a reference that means the same on both sites.
 *
 * @param string $kind post or term. An attachment gives a media reference.
 * @param int    $id   The ID.
 * @return array|null array( '@ref' => ... ), or null for no ID.
 */
function sgwrd_content_ref( $kind, $id ) {

	$id = (int) $id;

	if ( $id <= 0 ) {
		return null;
	}

	if ( 'term' === $kind ) {
		$term = get_term( $id );
		return ( $term && ! is_wp_error( $term ) )
			? array( '@ref' => 'term:' . $term->taxonomy . ':' . $term->slug )
			: array( '@ref' => 'none' );
	}

	$post = get_post( $id );

	if ( ! $post ) {
		return array( '@ref' => 'none' );
	}

	if ( 'attachment' === $post->post_type ) {
		$file = (string) get_post_meta( $id, '_wp_attached_file', true );
		return '' !== $file ? array( '@ref' => 'media:' . $file ) : array( '@ref' => 'none' );
	}

	return array( '@ref' => 'post:' . $post->post_type . ':' . sgwrd_content_post_path( $post ) );
}

/**
 * Find the ID of a reference on this site.
 *
 * @param string $ref A reference, such as post:page:about/team.
 * @return int The ID, or 0.
 */
function sgwrd_content_resolve( $ref ) {

	static $cache = array();

	if ( isset( $cache[ $ref ] ) && $cache[ $ref ] ) {
		return $cache[ $ref ];
	}

	$parts = explode( ':', (string) $ref, 3 );
	$id    = 0;

	switch ( $parts[0] ) {

		case 'post':
			if ( 3 === count( $parts ) ) {
				$type = $parts[1];
				$path = $parts[2];

				if ( is_post_type_hierarchical( $type ) ) {
					$post = get_page_by_path( $path, OBJECT, array( $type ) );
					$id   = $post ? $post->ID : 0;
				}

				if ( ! $id && ! is_post_type_hierarchical( $type ) ) {
					$found = get_posts(
						array(
							'post_type'        => $type,
							'name'             => basename( $path ),
							'post_status'      => array( 'publish', 'draft', 'pending', 'private', 'future' ),
							'numberposts'      => 1,
							'fields'           => 'ids',
							'suppress_filters' => true,
						)
					);
					$id    = $found ? (int) $found[0] : 0;
				}

				// A draft without a slug is known by its title.
				if ( ! $id ) {
					foreach ( get_posts( array( 'post_type' => $type, 'post_status' => array( 'draft', 'pending' ), 'numberposts' => -1, 'suppress_filters' => true ) ) as $post ) {
						if ( '' === $post->post_name && sgwrd_content_post_path( $post ) === $path ) {
							$id = $post->ID;
							break;
						}
					}
				}
			}
			break;

		case 'term':
			if ( 3 === count( $parts ) ) {
				$term = get_term_by( 'slug', $parts[2], $parts[1] );
				$id   = $term ? (int) $term->term_id : 0;
			}
			break;

		case 'media':
			$found = get_posts(
				array(
					'post_type'        => 'attachment',
					'post_status'      => 'inherit',
					'meta_key'         => '_wp_attached_file', // phpcs:ignore WordPress.DB.SlowDBQuery
					'meta_value'       => substr( $ref, 6 ), // phpcs:ignore WordPress.DB.SlowDBQuery
					'numberposts'      => 1,
					'fields'           => 'ids',
					'suppress_filters' => true,
				)
			);
			$id    = $found ? (int) $found[0] : 0;
			break;
	}

	$cache[ $ref ] = (int) $id;

	return (int) $id;
}

/**
 * Replace the IDs in a value with references. Works on one ID, a list of IDs
 * and on numeric strings. Anything else stays as it is.
 *
 * @param string $kind  post or term.
 * @param mixed  $value The value.
 * @return mixed
 */
function sgwrd_content_ids_out( $kind, $value ) {

	if ( is_array( $value ) ) {
		foreach ( $value as $index => $item ) {
			$value[ $index ] = sgwrd_content_ids_out( $kind, $item );
		}
		return $value;
	}

	if ( is_numeric( $value ) && (int) $value > 0 && (string) (int) $value === trim( (string) $value ) ) {
		return sgwrd_content_ref( $kind, (int) $value );
	}

	return $value;
}

/**
 * Normalize a flat set of ACF values: name => value, plus _name => field key.
 *
 * Post meta, term meta, the theme settings and the data of an ACF block all
 * look like this. The field key tells the field type, and the type tells
 * which values are IDs.
 *
 * @param array $values The values.
 * @return array
 */
function sgwrd_content_flat_out( array $values ) {

	foreach ( $values as $name => $value ) {

		$name = (string) $name;

		if ( in_array( $name, sgwrd_content_media_meta(), true ) ) {
			$values[ $name ] = sgwrd_content_ids_out( 'post', $value );
			continue;
		}

		// ACF writes name => value plus _name => field key. Older block data
		// uses the field key as the name.
		if ( 0 === strpos( $name, 'field_' ) ) {
			$key = $name;
		} else {
			$key = isset( $values[ '_' . $name ] ) && is_string( $values[ '_' . $name ] ) ? $values[ '_' . $name ] : '';
		}

		if ( 0 !== strpos( $key, 'field_' ) || ! function_exists( 'acf_get_field' ) ) {
			continue;
		}

		$field = acf_get_field( $key );

		if ( ! $field ) {
			continue;
		}

		switch ( $field['type'] ) {
			case 'image':
			case 'file':
			case 'gallery':
			case 'post_object':
			case 'relationship':
			case 'page_link':
				$values[ $name ] = sgwrd_content_ids_out( 'post', $value );
				break;

			case 'taxonomy':
				$values[ $name ] = sgwrd_content_ids_out( 'term', $value );
				break;
		}
	}

	return $values;
}

/**
 * The site address forms that the placeholder replaces.
 *
 * Only the part after the scheme, so http and https both match:
 * //example.com/sub and its JSON form \/\/example.com\/sub.
 *
 * @return array Search => replace.
 */
function sgwrd_content_home_map() {

	$home = untrailingslashit( preg_replace( '#^[a-z]+:#i', '', home_url() ) );

	return array(
		str_replace( '/', '\/', $home ) => '\/\/' . SGWRD_CONTENT_HOME,
		$home                           => '//' . SGWRD_CONTENT_HOME,
	);
}

/**
 * Swap the site address for the placeholder, or back.
 *
 * @param mixed $value The value.
 * @param bool  $out   True: address to placeholder. False: placeholder to address.
 * @return mixed
 */
function sgwrd_content_urls( $value, $out ) {

	static $maps = array();

	$maps[ home_url() ] = isset( $maps[ home_url() ] ) ? $maps[ home_url() ] : sgwrd_content_home_map();
	$map                = $maps[ home_url() ];

	if ( is_array( $value ) ) {
		foreach ( $value as $index => $item ) {
			$value[ $index ] = sgwrd_content_urls( $item, $out );
		}
		return $value;
	}

	if ( ! is_string( $value ) ) {
		return $value;
	}

	return $out
		? str_replace( array_keys( $map ), array_values( $map ), $value )
		: str_replace( array_values( $map ), array_keys( $map ), $value );
}

/**
 * Put the IDs of this site back in, and the site address.
 *
 * @param mixed $value   The value from a package.
 * @param array $missing Filled with the references this site does not have.
 * @return mixed
 */
function sgwrd_content_in( $value, array &$missing ) {

	if ( is_array( $value ) ) {

		if ( array( '@ref' ) === array_keys( $value ) ) {

			if ( 'none' === $value['@ref'] ) {
				return '';
			}

			$id = sgwrd_content_resolve( $value['@ref'] );

			if ( ! $id ) {
				$missing[] = $value['@ref'];
				return '';
			}

			return $id;
		}

		foreach ( $value as $index => $item ) {
			$value[ $index ] = sgwrd_content_in( $item, $missing );
		}

		return $value;
	}

	return sgwrd_content_urls( $value, false );
}

/**
 * Sort the keys of every map in a value, so the fingerprint does not depend on
 * the order the database returned things in. Lists keep their order.
 *
 * @param mixed $value The value.
 * @return mixed
 */
function sgwrd_content_sort( $value ) {

	if ( ! is_array( $value ) ) {
		return $value;
	}

	foreach ( $value as $index => $item ) {
		$value[ $index ] = sgwrd_content_sort( $item );
	}

	if ( array_keys( $value ) !== range( 0, count( $value ) - 1 ) ) {
		ksort( $value, SORT_STRING );
	}

	return $value;
}

/**
 * The fingerprint of one item.
 *
 * @param array $data Normalized item data.
 * @return string
 */
function sgwrd_content_hash( $data ) {
	return md5( (string) wp_json_encode( sgwrd_content_sort( $data ) ) );
}

/* -------------------------------------------------------------------------
 * Reading the content of this site
 * ---------------------------------------------------------------------- */

/**
 * Read meta into name => value. A key with more than one value keeps a list.
 *
 * @param array $meta What get_post_meta() or get_term_meta() returned.
 * @return array
 */
function sgwrd_content_meta_out( $meta ) {

	$out = array();

	foreach ( (array) $meta as $key => $values ) {

		if ( sgwrd_content_skip_meta( $key ) ) {
			continue;
		}

		$values      = array_map( 'maybe_unserialize', (array) $values );
		$out[ $key ] = 1 === count( $values ) ? $values[0] : array( '@multi' => $values );
	}

	return sgwrd_content_flat_out( $out );
}

/**
 * The blocks of a post, with the IDs in ACF block data as references.
 *
 * @param array $blocks Parsed blocks.
 * @return array
 */
function sgwrd_content_blocks_out( $blocks ) {

	foreach ( $blocks as $index => $block ) {

		if ( 0 === strpos( (string) $block['blockName'], 'acf/' ) && ! empty( $block['attrs']['data'] ) && is_array( $block['attrs']['data'] ) ) {
			$blocks[ $index ]['attrs']['data'] = sgwrd_content_flat_out( $block['attrs']['data'] );
		}

		if ( ! empty( $block['innerBlocks'] ) ) {
			$blocks[ $index ]['innerBlocks'] = sgwrd_content_blocks_out( $block['innerBlocks'] );
		}
	}

	return $blocks;
}

/**
 * One post as an item.
 *
 * @param WP_Post $post The post.
 * @return array
 */
function sgwrd_content_post_item( $post ) {

	$terms = array();

	foreach ( get_object_taxonomies( $post->post_type ) as $taxonomy ) {
		$slugs = wp_get_object_terms( $post->ID, $taxonomy, array( 'fields' => 'slugs' ) );

		if ( ! is_wp_error( $slugs ) && $slugs ) {
			sort( $slugs );
			$terms[ $taxonomy ] = $slugs;
		}
	}

	return array(
		'title'    => $post->post_title,
		'excerpt'  => $post->post_excerpt,
		'status'   => $post->post_status,
		'order'    => (int) $post->menu_order,
		'parent'   => sgwrd_content_ref( 'post', $post->post_parent ),
		'template' => (string) get_post_meta( $post->ID, '_wp_page_template', true ),
		'blocks'   => sgwrd_content_blocks_out( parse_blocks( $post->post_content ) ),
		'meta'     => sgwrd_content_meta_out( get_post_meta( $post->ID ) ),
		'terms'    => $terms,
	);
}

/**
 * The theme settings (ACF options), grouped by top level field.
 *
 * ACF keeps an options page field as options_<name>, and a repeater as one
 * option per row and sub field. One top level field is one item, so a
 * repeater never goes over half.
 *
 * @return array Top level field name => flat values, without the prefix.
 */
function sgwrd_content_acf_options() {

	global $wpdb;

	$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		"SELECT option_name, option_value FROM {$wpdb->options}
		WHERE option_name LIKE 'options\\_%' OR option_name LIKE '\\_options\\_%'"
	);

	$flat = array();

	foreach ( $rows as $row ) {
		$name          = $row->option_name;
		$name          = '_' === $name[0] ? '_' . substr( $name, 9 ) : substr( $name, 8 );
		$flat[ $name ] = maybe_unserialize( $row->option_value );
	}

	// The top level fields: their parent is a field group, not a field.
	$tops = array();

	foreach ( $flat as $name => $value ) {

		$name = (string) $name;

		if ( '_' === $name[0] || ! isset( $flat[ '_' . $name ] ) || ! function_exists( 'acf_get_field' ) ) {
			continue;
		}

		$field = acf_get_field( $flat[ '_' . $name ] );

		if ( $field && ! acf_get_field( $field['parent'] ) ) {
			$tops[] = $name;
		}
	}

	// Longest first, so bar_text_extra is not put under bar_text.
	usort(
		$tops,
		static function ( $a, $b ) {
			return strlen( $b ) - strlen( $a );
		}
	);

	$groups = array();

	foreach ( $flat as $name => $value ) {

		$bare  = ltrim( (string) $name, '_' );
		$group = $bare;

		foreach ( $tops as $top ) {
			if ( $bare === $top || 0 === strpos( $bare, $top . '_' ) ) {
				$group = $top;
				break;
			}
		}

		$groups[ $group ][ $name ] = $value;
	}

	return $groups;
}

/**
 * All content items on this site.
 *
 * @return array Key => normalized data.
 */
function sgwrd_content_items() {

	$items = array();

	// Posts and pages.
	$ids = get_posts(
		array(
			'post_type'        => sgwrd_content_post_types(),
			'post_status'      => array( 'publish', 'draft', 'pending', 'private' ),
			'numberposts'      => -1,
			'fields'           => 'ids',
			'orderby'          => 'ID',
			'order'            => 'ASC',
			'suppress_filters' => true,
		)
	);

	foreach ( $ids as $id ) {
		$post = get_post( $id );
		$key  = 'post:' . $post->post_type . ':' . sgwrd_content_post_path( $post );

		$items[ $key ] = sgwrd_content_post_item( $post );
	}

	// Categories.
	foreach ( sgwrd_content_taxonomies() as $taxonomy ) {

		$terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) );

		foreach ( is_wp_error( $terms ) ? array() : $terms as $term ) {
			$items[ 'term:' . $taxonomy . ':' . $term->slug ] = array(
				'name'        => $term->name,
				'description' => $term->description,
				'parent'      => sgwrd_content_ref( 'term', $term->parent ),
				'meta'        => sgwrd_content_meta_out( get_term_meta( $term->term_id ) ),
			);
		}
	}

	// Menus.
	foreach ( wp_get_nav_menus() as $menu ) {

		$list  = array();
		$index = array();

		foreach ( (array) wp_get_nav_menu_items( $menu->term_id ) as $position => $item ) {
			$index[ (int) $item->ID ] = $position;
		}

		foreach ( (array) wp_get_nav_menu_items( $menu->term_id ) as $item ) {

			switch ( $item->type ) {
				case 'post_type':
					$target = sgwrd_content_ref( 'post', $item->object_id );
					break;
				case 'taxonomy':
					$target = sgwrd_content_ref( 'term', $item->object_id );
					break;
				default:
					$target = null;
			}

			$parent = (int) $item->menu_item_parent;

			$list[] = array(
				'title'       => $item->post_title,
				'type'        => $item->type,
				'object'      => $item->object,
				'target'      => $target,
				'url'         => 'custom' === $item->type ? $item->url : '',
				'parent'      => $parent && isset( $index[ $parent ] ) ? $index[ $parent ] : -1,
				'link_target' => (string) $item->target,
				'classes'     => implode( ' ', array_filter( (array) $item->classes ) ),
				'xfn'         => (string) $item->xfn,
				'attr_title'  => (string) $item->attr_title,
				'description' => (string) $item->post_content,
			);
		}

		$items[ 'menu:' . $menu->slug ] = array(
			'name'  => $menu->name,
			'items' => $list,
		);
	}

	// Which menu shows where.
	$locations = array();

	foreach ( (array) get_nav_menu_locations() as $location => $menu_id ) {
		$menu = $menu_id ? wp_get_nav_menu_object( $menu_id ) : false;

		if ( $menu ) {
			$locations[ $location ] = $menu->slug;
		}
	}

	$items['menu-locations'] = $locations;

	// Site options.
	foreach ( sgwrd_content_options() as $option ) {
		$value = get_option( $option );

		if ( in_array( $option, sgwrd_content_page_options(), true ) ) {
			$value = sgwrd_content_ref( 'post', $value );
		}

		$items[ 'option:' . $option ] = array( 'value' => $value );
	}

	// Theme settings.
	foreach ( sgwrd_content_acf_options() as $top => $values ) {
		$items[ 'acf-option:' . $top ] = sgwrd_content_flat_out( $values );
	}

	$items = (array) apply_filters( 'sgwrd_content_items', $items );

	return sgwrd_content_urls( $items, true );
}

/* -------------------------------------------------------------------------
 * The baseline
 * ---------------------------------------------------------------------- */

/**
 * A fingerprint of this database. A copy on another site has another one,
 * even when the search and replace changed all the addresses.
 *
 * @return string
 */
function sgwrd_content_site_id() {
	return md5( DB_NAME . '|' . $GLOBALS['wpdb']->prefix );
}

/**
 * Take the baseline: the fingerprint of every item, and the list of images.
 *
 * @return array The baseline.
 */
function sgwrd_content_take_baseline() {

	global $wpdb;

	$baseline = array(
		'taken' => gmdate( 'c' ),
		'site'  => sgwrd_content_site_id(),
		'items' => array_map( 'sgwrd_content_hash', sgwrd_content_items() ),
		'media' => $wpdb->get_col( "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file'" ), // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	);

	update_option( SGWRD_CONTENT_BASELINE, $baseline, false );

	return $baseline;
}

/**
 * The baseline of this site, when it belongs to this site.
 *
 * @return array|null
 */
function sgwrd_content_baseline() {

	$baseline = get_option( SGWRD_CONTENT_BASELINE );

	if ( ! is_array( $baseline ) || empty( $baseline['site'] ) || sgwrd_content_site_id() !== $baseline['site'] ) {
		return null;
	}

	return $baseline;
}

/**
 * On staging: take the baseline at the first admin page load after the copy.
 *
 * A fresh copy or a pull brings the database of production. Its baseline
 * belongs to another database, or there is none. So the first admin page
 * load takes a new one, before anyone can save a change.
 */
function sgwrd_content_auto_baseline() {

	if ( ! function_exists( 'sgwrd_is_production' ) || sgwrd_is_production() ) {
		return;
	}

	if ( ! apply_filters( 'sgwrd_content_auto_baseline', true ) || sgwrd_content_baseline() ) {
		return;
	}

	sgwrd_content_take_baseline();

	add_action(
		'admin_notices',
		static function () {
			echo '<div class="notice notice-info"><p>'
				. esc_html__( 'Content sync: this site is a new copy, so the theme took a baseline. Changes you make from now on can go to production.', 'acf-starter' )
				. '</p></div>';
		}
	);
}
add_action( 'admin_init', 'sgwrd_content_auto_baseline', 1 );

/**
 * The difference between this site and its baseline.
 *
 * @param array $baseline The baseline.
 * @return array( 'items' => key => array( base, hash, data ), 'removed' => keys ).
 */
function sgwrd_content_changes( $baseline ) {

	$now     = sgwrd_content_items();
	$changed = array();

	foreach ( $now as $key => $data ) {

		$hash = sgwrd_content_hash( $data );
		$base = isset( $baseline['items'][ $key ] ) ? $baseline['items'][ $key ] : null;

		if ( $hash !== $base ) {
			$changed[ $key ] = array(
				'base' => $base,
				'hash' => $hash,
				'data' => $data,
			);
		}
	}

	return array(
		'items'   => $changed,
		'removed' => array_values( array_diff( array_keys( $baseline['items'] ), array_keys( $now ) ) ),
	);
}

/**
 * Every media reference in a value.
 *
 * @param mixed $value The value.
 * @param array $found Filled with the references.
 */
function sgwrd_content_find_media( $value, array &$found ) {

	if ( ! is_array( $value ) ) {
		return;
	}

	if ( isset( $value['@ref'] ) && 0 === strpos( (string) $value['@ref'], 'media:' ) ) {
		$found[ $value['@ref'] ] = true;
		return;
	}

	foreach ( $value as $item ) {
		sgwrd_content_find_media( $item, $found );
	}
}

/* -------------------------------------------------------------------------
 * Writing the content into this site
 * ---------------------------------------------------------------------- */

/**
 * Put one image from the package into the media library.
 *
 * @param string $ref  media:<file>.
 * @param array  $info title, alt, caption, description, mime.
 * @param string $dir  The package folder.
 * @return string there, added or missing.
 */
function sgwrd_content_put_media( $ref, $info, $dir ) {

	if ( sgwrd_content_resolve( $ref ) ) {
		return 'there';
	}

	$file = substr( $ref, 6 );
	$src  = $dir . '/media/' . $file;

	// Never write outside uploads, and only file types WordPress allows.
	$type = wp_check_filetype( $file );

	if ( 0 !== validate_file( $file ) || ! $type['type'] || ! is_readable( $src ) ) {
		return 'missing';
	}

	$uploads = wp_get_upload_dir();
	$dest    = $uploads['basedir'] . '/' . $file;

	if ( ! file_exists( $dest ) ) {
		wp_mkdir_p( dirname( $dest ) );

		if ( ! copy( $src, $dest ) ) {
			return 'missing';
		}
	}

	$id = wp_insert_attachment(
		wp_slash(
			array(
				'post_title'     => isset( $info['title'] ) ? $info['title'] : basename( $file ),
				'post_excerpt'   => isset( $info['caption'] ) ? $info['caption'] : '',
				'post_content'   => isset( $info['description'] ) ? $info['description'] : '',
				'post_mime_type' => $type['type'],
				'post_status'    => 'inherit',
				'guid'           => $uploads['baseurl'] . '/' . $file,
			)
		),
		$dest,
		0,
		true
	);

	if ( is_wp_error( $id ) ) {
		return 'missing';
	}

	require_once ABSPATH . 'wp-admin/includes/image.php';
	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $dest ) );

	if ( ! empty( $info['alt'] ) ) {
		update_post_meta( $id, '_wp_attachment_image_alt', wp_slash( $info['alt'] ) );
	}

	return 'added';
}

/**
 * Replace the meta of an object with the meta from the package.
 *
 * @param string $type    post or term.
 * @param int    $id      Object ID.
 * @param array  $meta    Meta from the package, with IDs put back.
 */
function sgwrd_content_put_meta( $type, $id, $meta ) {

	$current = 'post' === $type ? get_post_meta( $id ) : get_term_meta( $id );

	foreach ( array_keys( (array) $current ) as $key ) {
		if ( ! sgwrd_content_skip_meta( $key ) && ! array_key_exists( $key, $meta ) ) {
			'post' === $type ? delete_post_meta( $id, $key ) : delete_term_meta( $id, $key );
		}
	}

	foreach ( $meta as $key => $value ) {

		if ( is_array( $value ) && array( '@multi' ) === array_keys( $value ) ) {
			'post' === $type ? delete_post_meta( $id, $key ) : delete_term_meta( $id, $key );

			foreach ( $value['@multi'] as $single ) {
				'post' === $type ? add_post_meta( $id, $key, wp_slash( $single ) ) : add_term_meta( $id, $key, wp_slash( $single ) );
			}
			continue;
		}

		'post' === $type ? update_post_meta( $id, $key, wp_slash( $value ) ) : update_term_meta( $id, $key, wp_slash( $value ) );
	}
}

/**
 * Write one category.
 *
 * @param string $key     term:<taxonomy>:<slug>.
 * @param array  $data    Item data.
 * @param array  $missing Filled with the references this site does not have.
 * @return int|WP_Error
 */
function sgwrd_content_put_term( $key, $data, array &$missing ) {

	list( , $taxonomy, $slug ) = explode( ':', $key, 3 );

	$data = sgwrd_content_in( $data, $missing );
	$args = array(
		'slug'        => $slug,
		'description' => $data['description'],
		'parent'      => (int) $data['parent'],
	);

	$id = sgwrd_content_resolve( $key );

	$result = $id
		? wp_update_term( $id, $taxonomy, wp_slash( array_merge( $args, array( 'name' => $data['name'] ) ) ) )
		: wp_insert_term( wp_slash( $data['name'] ), $taxonomy, wp_slash( $args ) );

	if ( is_wp_error( $result ) ) {
		return $result;
	}

	sgwrd_content_put_meta( 'term', (int) $result['term_id'], (array) $data['meta'] );

	return (int) $result['term_id'];
}

/**
 * Write one post: first the post itself, so others can point to it.
 *
 * @param string $key     post:<type>:<path>.
 * @param array  $data    Item data.
 * @param array  $missing Filled with the references this site does not have.
 * @return int|WP_Error
 */
function sgwrd_content_put_post_shell( $key, $data, array &$missing ) {

	list( , $type, $path ) = explode( ':', $key, 3 );

	$parent = sgwrd_content_in( $data['parent'], $missing );
	$id     = sgwrd_content_resolve( $key );

	$postarr = array(
		'post_type'    => $type,
		'post_name'    => basename( $path ),
		'post_title'   => sgwrd_content_urls( $data['title'], false ),
		'post_excerpt' => sgwrd_content_urls( $data['excerpt'], false ),
		'post_status'  => $data['status'],
		'menu_order'   => (int) $data['order'],
		'post_parent'  => (int) $parent,
	);

	if ( $id ) {
		$postarr['ID'] = $id;
		return wp_update_post( wp_slash( $postarr ), true );
	}

	return wp_insert_post( wp_slash( $postarr ), true );
}

/**
 * Write the content, meta, template and categories of a post.
 *
 * @param int   $id      Post ID.
 * @param array $data    Item data.
 * @param array $missing Filled with the references this site does not have.
 */
function sgwrd_content_put_post_body( $id, $data, array &$missing ) {

	$blocks = sgwrd_content_in( $data['blocks'], $missing );

	wp_update_post(
		wp_slash(
			array(
				'ID'           => $id,
				'post_content' => serialize_blocks( $blocks ),
			)
		)
	);

	sgwrd_content_put_meta( 'post', $id, (array) sgwrd_content_in( $data['meta'], $missing ) );

	if ( '' !== $data['template'] ) {
		update_post_meta( $id, '_wp_page_template', $data['template'] );
	} else {
		delete_post_meta( $id, '_wp_page_template' );
	}

	foreach ( get_object_taxonomies( get_post_type( $id ) ) as $taxonomy ) {
		wp_set_object_terms( $id, isset( $data['terms'][ $taxonomy ] ) ? $data['terms'][ $taxonomy ] : array(), $taxonomy );
	}
}

/**
 * Write one menu. The items of the menu are replaced as a whole.
 *
 * @param string $key     menu:<slug>.
 * @param array  $data    Item data.
 * @param array  $missing Filled with the references this site does not have.
 * @return int|WP_Error
 */
function sgwrd_content_put_menu( $key, $data, array &$missing ) {

	$slug = substr( $key, 5 );
	$menu = wp_get_nav_menu_object( $slug );

	if ( $menu ) {
		$menu_id = (int) $menu->term_id;
		wp_update_nav_menu_object( $menu_id, wp_slash( array( 'menu-name' => $data['name'] ) ) );

		foreach ( (array) wp_get_nav_menu_items( $menu_id, array( 'post_status' => 'any' ) ) as $item ) {
			wp_delete_post( $item->ID, true );
		}
	} else {
		$menu_id = wp_create_nav_menu( $data['name'] );

		if ( is_wp_error( $menu_id ) ) {
			return $menu_id;
		}
	}

	$made = array();
	$args = array();

	foreach ( $data['items'] as $position => $item ) {

		$one = array(
			'menu-item-title'       => $item['title'],
			'menu-item-type'        => $item['type'],
			'menu-item-object'      => $item['object'],
			'menu-item-status'      => 'publish',
			'menu-item-position'    => $position + 1,
			'menu-item-target'      => $item['link_target'],
			'menu-item-classes'     => $item['classes'],
			'menu-item-xfn'         => $item['xfn'],
			'menu-item-attr-title'  => $item['attr_title'],
			'menu-item-description' => $item['description'],
		);

		if ( 'custom' === $item['type'] ) {
			$one['menu-item-url'] = sgwrd_content_urls( $item['url'], false );
		} elseif ( null !== $item['target'] ) {
			$object_id = (int) sgwrd_content_in( $item['target'], $missing );

			if ( ! $object_id ) {
				continue;
			}

			$one['menu-item-object-id'] = $object_id;
		}

		$id = wp_update_nav_menu_item( $menu_id, 0, wp_slash( $one ) );

		if ( ! is_wp_error( $id ) ) {
			$made[ $position ] = (int) $id;
			$args[ $position ] = $one;
		}
	}

	// Now every item is there, so the sub items can find their parent.
	foreach ( $data['items'] as $position => $item ) {
		if ( $item['parent'] >= 0 && isset( $made[ $position ], $made[ $item['parent'] ] ) ) {
			wp_update_nav_menu_item(
				$menu_id,
				$made[ $position ],
				wp_slash( array_merge( $args[ $position ], array( 'menu-item-parent-id' => $made[ $item['parent'] ] ) ) )
			);
		}
	}

	return (int) $menu_id;
}

/**
 * Write the theme settings of one top level field.
 *
 * @param string $top     The top level field name.
 * @param array  $values  Flat values, without the prefix.
 * @param array  $missing Filled with the references this site does not have.
 */
function sgwrd_content_put_acf_option( $top, $values, array &$missing ) {

	$groups  = sgwrd_content_acf_options();
	$current = isset( $groups[ $top ] ) ? $groups[ $top ] : array();
	$values  = sgwrd_content_in( $values, $missing );

	foreach ( array_keys( $current ) as $name ) {
		if ( ! array_key_exists( $name, $values ) ) {
			$name = (string) $name;
			delete_option( '_' === $name[0] ? '_options_' . substr( $name, 1 ) : 'options_' . $name );
		}
	}

	foreach ( $values as $name => $value ) {
		$name = (string) $name;
		update_option( '_' === $name[0] ? '_options_' . substr( $name, 1 ) : 'options_' . $name, $value, false );
	}
}

/* -------------------------------------------------------------------------
 * The WP-CLI command
 * ---------------------------------------------------------------------- */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * Take content changes from staging to production.
 */
class Sgwrd_Content_Command {

	/**
	 * Take the baseline: remember the content of this site as it is now.
	 *
	 * Take it when staging is an exact copy of production. On staging the
	 * theme does this by itself at the first admin page load.
	 *
	 * ## OPTIONS
	 *
	 * [--yes]
	 * : Replace a baseline that is there, without asking.
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Named arguments.
	 */
	public function baseline( $args, $assoc_args ) {

		if ( sgwrd_content_baseline() ) {
			WP_CLI::confirm( 'There is a baseline. Changes since then will no longer show as changes. Replace it?', $assoc_args );
		}

		$baseline = sgwrd_content_take_baseline();

		WP_CLI::success( sprintf( 'Baseline taken: %d items, %d images.', count( $baseline['items'] ), count( $baseline['media'] ) ) );
	}

	/**
	 * Show what changed on this site since the baseline.
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Named arguments.
	 */
	public function diff( $args, $assoc_args ) {

		$baseline = $this->need_baseline();
		$changes  = sgwrd_content_changes( $baseline );
		$rows     = array();

		foreach ( $changes['items'] as $key => $item ) {
			$rows[] = array(
				'item'   => $key,
				'change' => null === $item['base'] ? 'new' : 'changed',
			);
		}

		foreach ( $changes['removed'] as $key ) {
			$rows[] = array(
				'item'   => $key,
				'change' => 'removed (not synced)',
			);
		}

		WP_CLI::log( sprintf( 'Baseline taken %s.', $baseline['taken'] ) );

		if ( ! $rows ) {
			WP_CLI::success( 'Nothing changed.' );
			return;
		}

		WP_CLI\Utils\format_items( 'table', $rows, array( 'item', 'change' ) );
	}

	/**
	 * Write the changes since the baseline to a folder.
	 *
	 * ## OPTIONS
	 *
	 * <dir>
	 * : The folder. It is made when it is not there.
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Named arguments.
	 */
	public function export( $args, $assoc_args ) {

		$dir      = untrailingslashit( $args[0] );
		$baseline = $this->need_baseline();
		$changes  = sgwrd_content_changes( $baseline );

		if ( ! wp_mkdir_p( $dir . '/media' ) ) {
			WP_CLI::error( 'Cannot make the folder ' . $dir );
		}

		// The images the changed items use and production may not have.
		$refs = array();
		sgwrd_content_find_media( wp_list_pluck( $changes['items'], 'data' ), $refs );

		$uploads = wp_get_upload_dir();
		$known   = array_flip( (array) $baseline['media'] );
		$media   = array();

		foreach ( array_keys( $refs ) as $ref ) {

			$file = substr( $ref, 6 );
			$id   = sgwrd_content_resolve( $ref );

			if ( isset( $known[ $file ] ) || ! $id || 0 !== validate_file( $file ) ) {
				continue;
			}

			wp_mkdir_p( dirname( $dir . '/media/' . $file ) );

			if ( ! copy( $uploads['basedir'] . '/' . $file, $dir . '/media/' . $file ) ) {
				WP_CLI::warning( 'Could not copy ' . $file );
				continue;
			}

			$media[ $ref ] = array(
				'title'       => get_the_title( $id ),
				'caption'     => get_post_field( 'post_excerpt', $id ),
				'description' => get_post_field( 'post_content', $id ),
				'alt'         => (string) get_post_meta( $id, '_wp_attachment_image_alt', true ),
				'mime'        => get_post_mime_type( $id ),
			);
		}

		$package = array(
			'version'  => SGWRD_CONTENT_VERSION,
			'made'     => gmdate( 'c' ),
			'baseline' => $baseline['taken'],
			'items'    => $changes['items'],
			'removed'  => $changes['removed'],
			'media'    => $media,
		);

		file_put_contents( $dir . '/package.json', wp_json_encode( $package, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions

		foreach ( array_keys( $changes['items'] ) as $key ) {
			WP_CLI::log( '  ' . ( null === $changes['items'][ $key ]['base'] ? 'new      ' : 'changed  ' ) . $key );
		}

		foreach ( $changes['removed'] as $key ) {
			WP_CLI::log( '  removed  ' . $key . ' (not synced: remove it by hand on production)' );
		}

		WP_CLI::success( sprintf( '%d items and %d new images written to %s.', count( $changes['items'] ), count( $media ), $dir ) );
	}

	/**
	 * Put a package into this site. Only items that production did not
	 * change since the baseline go in.
	 *
	 * ## OPTIONS
	 *
	 * <dir>
	 * : The folder that export wrote.
	 *
	 * [--dry-run]
	 * : Show what would happen. Change nothing.
	 *
	 * [--force]
	 * : Put conflicts in too. The staging version wins.
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Named arguments.
	 */
	public function import( $args, $assoc_args ) {

		$dir     = untrailingslashit( $args[0] );
		$dry_run = (bool) WP_CLI\Utils\get_flag_value( $assoc_args, 'dry-run', false );
		$force   = (bool) WP_CLI\Utils\get_flag_value( $assoc_args, 'force', false );
		$package = $this->read( $dir . '/package.json' );

		if ( SGWRD_CONTENT_VERSION !== (int) $package['version'] ) {
			WP_CLI::error( 'The package is from another version of the theme. Deploy the same theme on both sites.' );
		}

		// The state of this site before anything changes.
		$here = array_map( 'sgwrd_content_hash', sgwrd_content_items() );
		$plan = array();
		$rows = array();

		foreach ( $package['items'] as $key => $item ) {

			if ( ! $this->allowed( $key ) ) {
				WP_CLI::error( 'The package holds an item this site does not sync: ' . $key );
			}

			$now = isset( $here[ $key ] ) ? $here[ $key ] : null;

			if ( $now === $item['hash'] ) {
				$state = 'same';
			} elseif ( $now === $item['base'] ) {
				$state = null === $now ? 'new' : 'update';
			} else {
				$state = $force ? 'update (forced)' : 'conflict';
			}

			$plan[ $key ] = $state;
			$rows[]       = array(
				'item'   => $key,
				'action' => $state,
			);
		}

		foreach ( (array) $package['removed'] as $key ) {
			$rows[] = array(
				'item'   => $key,
				'action' => 'removed on staging: remove it by hand',
			);
		}

		if ( ! $rows ) {
			WP_CLI::success( 'Nothing to do. Staging has no changes since the baseline.' );
			$this->write_result( $dir, array(), array() );
			return;
		}

		WP_CLI\Utils\format_items( 'table', $rows, array( 'item', 'action' ) );

		$conflicts = array_keys( $plan, 'conflict', true );

		if ( $conflicts ) {
			WP_CLI::warning( sprintf( '%d conflict(s): production changed these too, so they stay as they are. Make the change by hand, or run with --force to take the staging version.', count( $conflicts ) ) );
		}

		if ( $dry_run ) {
			WP_CLI::success( 'Dry run. Nothing was changed.' );
			return;
		}

		kses_remove_filters();

		$apply   = array_keys(
			array_filter(
				$plan,
				static function ( $state ) {
					return in_array( $state, array( 'new', 'update', 'update (forced)' ), true );
				}
			)
		);
		$missing = array();
		$failed  = array();
		$done    = array();

		// 1. Images.
		foreach ( (array) $package['media'] as $ref => $info ) {
			if ( 'missing' === sgwrd_content_put_media( $ref, $info, $dir ) ) {
				WP_CLI::warning( 'Image not in the package: ' . $ref );
			}
		}

		// 2. Categories. A parent goes in before its children.
		$terms = preg_grep( '/^term:/', $apply );

		while ( $terms ) {
			$ready = array();

			foreach ( $terms as $key ) {
				$parent = $package['items'][ $key ]['data']['parent'];
				$ref    = is_array( $parent ) && isset( $parent['@ref'] ) ? $parent['@ref'] : '';

				if ( '' === $ref || ! in_array( $ref, $terms, true ) ) {
					$ready[] = $key;
				}
			}

			// A loop in the parents: put the rest in as they are.
			$ready = $ready ? $ready : $terms;

			foreach ( $ready as $key ) {
				$result = sgwrd_content_put_term( $key, $package['items'][ $key ]['data'], $missing );

				if ( is_wp_error( $result ) ) {
					$failed[ $key ] = $result->get_error_message();
				} else {
					$done[] = $key;
				}
			}

			$terms = array_diff( $terms, $ready );
		}

		// 3. Posts. First the posts themselves, parents first. Then their
		//    content, which can point to any of them.
		$posts = preg_grep( '/^post:/', $apply );
		usort( $posts, array( $this, 'by_depth' ) );
		$ids = array();

		foreach ( $posts as $key ) {
			$result = sgwrd_content_put_post_shell( $key, $package['items'][ $key ]['data'], $missing );

			if ( is_wp_error( $result ) ) {
				$failed[ $key ] = $result->get_error_message();
			} elseif ( ! $result ) {
				$failed[ $key ] = 'not saved';
			} else {
				$ids[ $key ] = (int) $result;
			}
		}

		foreach ( $ids as $key => $id ) {
			sgwrd_content_put_post_body( $id, $package['items'][ $key ]['data'], $missing );
			$done[] = $key;
		}

		// 4. Options and theme settings.
		foreach ( preg_grep( '/^option:/', $apply ) as $key ) {
			$value = sgwrd_content_in( $package['items'][ $key ]['data']['value'], $missing );
			update_option( substr( $key, 7 ), $value );
			$done[] = $key;
		}

		foreach ( preg_grep( '/^acf-option:/', $apply ) as $key ) {
			sgwrd_content_put_acf_option( substr( $key, 11 ), $package['items'][ $key ]['data'], $missing );
			$done[] = $key;
		}

		// 5. Menus, then where they show.
		foreach ( preg_grep( '/^menu:/', $apply ) as $key ) {
			$result = sgwrd_content_put_menu( $key, $package['items'][ $key ]['data'], $missing );

			if ( is_wp_error( $result ) ) {
				$failed[ $key ] = $result->get_error_message();
			} else {
				$done[] = $key;
			}
		}

		if ( in_array( 'menu-locations', $apply, true ) ) {
			$locations = array();

			foreach ( $package['items']['menu-locations']['data'] as $location => $slug ) {
				$menu = wp_get_nav_menu_object( $slug );

				if ( $menu ) {
					$locations[ $location ] = (int) $menu->term_id;
				} else {
					$missing[] = 'menu:' . $slug;
				}
			}

			set_theme_mod( 'nav_menu_locations', $locations );
			$done[] = 'menu-locations';
		}

		wp_cache_flush();

		foreach ( array_unique( $missing ) as $ref ) {
			WP_CLI::warning( 'Production does not have ' . $ref . '. That link or image is empty now.' );
		}

		foreach ( $failed as $key => $message ) {
			WP_CLI::warning( $key . ' failed: ' . $message );
		}

		// Staging moves its baseline forward for what is now the same on both.
		$accepted = array_merge( $done, array_keys( $plan, 'same', true ) );
		$this->write_result( $dir, $accepted, $package );

		if ( $failed ) {
			WP_CLI::error( sprintf( '%d item(s) went in, %d failed.', count( $done ), count( $failed ) ) );
		}

		WP_CLI::success( sprintf( '%d item(s) went in. %d conflict(s) skipped.', count( $done ), count( $conflicts ) ) );
	}

	/**
	 * Move the baseline forward for the items that went to production.
	 *
	 * Run it on staging, with the folder that import wrote its result to.
	 *
	 * ## OPTIONS
	 *
	 * <dir>
	 * : The package folder, with result.json from the import.
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Named arguments.
	 */
	public function accept( $args, $assoc_args ) {

		$baseline = $this->need_baseline();
		$result   = $this->read( untrailingslashit( $args[0] ) . '/result.json' );

		foreach ( (array) $result['items'] as $key => $hash ) {
			$baseline['items'][ $key ] = $hash;
		}

		// A removed item was reported once. It is not reported again.
		foreach ( isset( $result['removed'] ) ? (array) $result['removed'] : array() as $key ) {
			unset( $baseline['items'][ $key ] );
		}

		$baseline['media'] = array_values( array_unique( array_merge( (array) $baseline['media'], (array) $result['media'] ) ) );

		update_option( SGWRD_CONTENT_BASELINE, $baseline, false );

		WP_CLI::success( sprintf( 'Baseline moved forward for %d item(s).', count( (array) $result['items'] ) ) );
	}

	/**
	 * The baseline, or stop.
	 *
	 * @return array
	 */
	private function need_baseline() {

		$baseline = sgwrd_content_baseline();

		if ( ! $baseline ) {
			WP_CLI::error( 'This site has no baseline. Take one when it is an exact copy of production: wp sgwrd content baseline. On staging the theme takes it by itself at the first admin page load.' );
		}

		return $baseline;
	}

	/**
	 * Read a JSON file, or stop.
	 *
	 * @param string $file Path.
	 * @return array
	 */
	private function read( $file ) {

		$data = is_readable( $file ) ? json_decode( (string) file_get_contents( $file ), true ) : null; // phpcs:ignore WordPress.WP.AlternativeFunctions

		if ( ! is_array( $data ) ) {
			WP_CLI::error( 'Cannot read ' . $file );
		}

		return $data;
	}

	/**
	 * Write the result for accept: the items that are now the same on both.
	 *
	 * @param string $dir      Package folder.
	 * @param array  $accepted Item keys.
	 * @param array  $package  The package.
	 */
	private function write_result( $dir, $accepted, $package ) {

		$items = array();

		foreach ( $accepted as $key ) {
			$items[ $key ] = $package['items'][ $key ]['hash'];
		}

		$media = array();

		foreach ( array_keys( isset( $package['media'] ) ? (array) $package['media'] : array() ) as $ref ) {
			$media[] = substr( $ref, 6 );
		}

		$result = array(
			'items'   => $items,
			'media'   => $media,
			'removed' => isset( $package['removed'] ) ? (array) $package['removed'] : array(),
		);

		file_put_contents( $dir . '/result.json', wp_json_encode( $result ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	}

	/**
	 * Is this an item this site syncs? A package can only touch the post
	 * types, taxonomies and options in the lists at the top of this file.
	 *
	 * @param string $key Item key.
	 * @return bool
	 */
	private function allowed( $key ) {

		$parts = explode( ':', $key, 3 );

		switch ( $parts[0] ) {
			case 'post':
				return 3 === count( $parts ) && in_array( $parts[1], sgwrd_content_post_types(), true );
			case 'term':
				return 3 === count( $parts ) && in_array( $parts[1], sgwrd_content_taxonomies(), true );
			case 'option':
				return in_array( substr( $key, 7 ), sgwrd_content_options(), true );
			case 'menu':
			case 'acf-option':
				return 2 === count( $parts ) && '' !== $parts[1];
			case 'menu-locations':
				return 'menu-locations' === $key;
		}

		return false;
	}

	/**
	 * Sort keys so a parent comes before its child.
	 *
	 * @param string $a Key.
	 * @param string $b Key.
	 * @return int
	 */
	private function by_depth( $a, $b ) {
		return substr_count( $a, '/' ) - substr_count( $b, '/' );
	}
}

WP_CLI::add_command( 'sgwrd content', 'Sgwrd_Content_Command' );
