<?php
/**
 * Demo content for ACF Starter.
 *
 * Run it through WP-CLI, from the WordPress root:
 *
 *   wp eval-file wp-content/themes/acf-starter/demo/seed.php
 *   wp eval-file wp-content/themes/acf-starter/demo/seed.php fresh
 *
 * "fresh" removes the demo content of an earlier run first. Without it the
 * script updates what is there, so you can run it as often as you like.
 *
 * The script only ever touches content it made itself. Every post, term and
 * attachment gets the marker _sgwrd_demo. Your own content is safe.
 *
 * @package ACF_Starter
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( "Run this file through WP-CLI: wp eval-file demo/seed.php\n" );
}

/*
 * ---------------------------------------------------------------------------
 * Settings
 * ---------------------------------------------------------------------------
 */

define( 'SGWRD_DEMO_MARK', '_sgwrd_demo' );

$sgwrd_fresh    = in_array( 'fresh', (array) ( $args ?? array() ), true );
$sgwrd_demo_dir = __DIR__;
$sgwrd_images   = $sgwrd_demo_dir . '/images';
$sgwrd_csv      = $sgwrd_demo_dir . '/products.csv';

/*
 * ---------------------------------------------------------------------------
 * Checks
 * ---------------------------------------------------------------------------
 */

if ( ! class_exists( 'WooCommerce' ) ) {
	WP_CLI::error( 'WooCommerce is not active. Run demo/provision.sh first.' );
}

if ( ! function_exists( 'update_field' ) ) {
	WP_CLI::error( 'ACF Pro is not active. Run demo/provision.sh first.' );
}

if ( 'acf-starter' !== get_template() ) {
	WP_CLI::warning( 'The active theme is not acf-starter. The blocks will not render.' );
}

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

/*
 * ---------------------------------------------------------------------------
 * Helpers
 * ---------------------------------------------------------------------------
 */

/**
 * Print a line.
 *
 * @param string $message The line.
 * @return void
 */
function sgwrd_demo_say( $message ) {
	WP_CLI::log( '  ' . $message );
}

/**
 * Build the block comment of an ACF block.
 *
 * An ACF block keeps its values in the block comment, as a map of field name
 * to value plus a map of field name to field key. This function writes both.
 *
 * @param string $name   Block name, such as 'acf/hero'.
 * @param array  $fields Field name => array( value, field key ).
 * @param array  $attrs  Extra block attributes, such as align.
 * @return string
 */
function sgwrd_demo_block( $name, $fields, $attrs = array() ) {

	$data = array();

	foreach ( $fields as $field_name => $pair ) {
		$data[ $field_name ]       = $pair[0];
		$data[ '_' . $field_name ] = $pair[1];
	}

	$json = array_merge(
		array(
			'name' => $name,
			'data' => $data,
			'mode' => 'preview',
		),
		$attrs
	);

	return '<!-- wp:' . $name . ' ' . wp_json_encode( $json ) . ' /-->' . "\n\n";
}

/**
 * Flatten a repeater into the shape an ACF block stores it in.
 *
 * ACF writes a repeater as a row count plus one entry per row and sub field,
 * named <repeater>_<index>_<sub field>.
 *
 * @param string $name     Repeater field name.
 * @param string $key      Repeater field key.
 * @param array  $rows     Each row as sub field name => value.
 * @param array  $sub_keys Sub field name => field key.
 * @return array Ready to merge into the $fields array of sgwrd_demo_block().
 */
function sgwrd_demo_rows( $name, $key, $rows, $sub_keys ) {

	$out = array( $name => array( count( $rows ), $key ) );

	foreach ( array_values( $rows ) as $index => $row ) {
		foreach ( $row as $sub_name => $value ) {

			if ( ! isset( $sub_keys[ $sub_name ] ) ) {
				continue;
			}

			$out[ $name . '_' . $index . '_' . $sub_name ] = array( $value, $sub_keys[ $sub_name ] );
		}
	}

	return $out;
}

/**
 * Put a file from demo/images into the media library, once.
 *
 * @param string $file Absolute path of the image.
 * @return int Attachment ID, or 0 when it failed.
 */
function sgwrd_demo_media( $file ) {

	static $cache = array();

	if ( ! $file || ! is_readable( $file ) ) {
		return 0;
	}

	$name = basename( $file );

	if ( isset( $cache[ $name ] ) ) {
		return $cache[ $name ];
	}

	// Already imported by an earlier run?
	$found = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => SGWRD_DEMO_MARK, // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'     => $name,           // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);

	if ( $found ) {
		$cache[ $name ] = (int) $found[0];
		return $cache[ $name ];
	}

	// media_handle_sideload() moves the file, so hand it a copy.
	$temp = wp_tempnam( $name );

	if ( ! $temp || ! copy( $file, $temp ) ) {
		return 0;
	}

	$id = media_handle_sideload(
		array(
			'name'     => $name,
			'tmp_name' => $temp,
		),
		0
	);

	if ( is_wp_error( $id ) ) {
		@unlink( $temp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		WP_CLI::warning( 'Could not import ' . $name . ': ' . $id->get_error_message() );
		return 0;
	}

	update_post_meta( $id, SGWRD_DEMO_MARK, $name );

	$cache[ $name ] = (int) $id;

	return $cache[ $name ];
}

/**
 * Create or update a page, and mark it as demo content.
 *
 * @param string $slug    Page slug.
 * @param string $title   Page title.
 * @param string $content Page content.
 * @return int Post ID.
 */
function sgwrd_demo_page( $slug, $title, $content = '' ) {

	$existing = get_page_by_path( $slug );

	$postarr = array(
		'post_type'    => 'page',
		'post_name'    => $slug,
		'post_title'   => $title,
		'post_content' => $content,
		'post_status'  => 'publish',
	);

	if ( $existing ) {
		$postarr['ID'] = $existing->ID;
	}

	$id = wp_insert_post( $postarr, true );

	if ( is_wp_error( $id ) ) {
		WP_CLI::warning( 'Page ' . $slug . ': ' . $id->get_error_message() );
		return 0;
	}

	update_post_meta( $id, SGWRD_DEMO_MARK, '1' );

	return (int) $id;
}

/**
 * Remove everything an earlier run made.
 *
 * @return void
 */
function sgwrd_demo_purge() {

	$posts = get_posts(
		array(
			'post_type'      => array( 'page', 'product', 'attachment' ),
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_key'       => SGWRD_DEMO_MARK, // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);

	foreach ( $posts as $id ) {
		wp_delete_post( $id, true );
	}

	sgwrd_demo_say( 'removed ' . count( $posts ) . ' posts and attachments from the last run' );

	foreach ( array( 'product_cat', 'category' ) as $taxonomy ) {
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'meta_key'   => SGWRD_DEMO_MARK, // phpcs:ignore WordPress.DB.SlowDBQuery
			)
		);

		foreach ( is_wp_error( $terms ) ? array() : $terms as $term ) {
			wp_delete_term( $term->term_id, $taxonomy );
		}
	}

	foreach ( array( 'primary', 'footer', 'legal' ) as $location ) {
		$menu = wp_get_nav_menu_object( 'Demo ' . $location );

		if ( $menu ) {
			wp_delete_nav_menu( $menu->term_id );
		}
	}
}

if ( $sgwrd_fresh ) {
	WP_CLI::log( 'Clearing the last run' );
	sgwrd_demo_purge();
}

/*
 * ---------------------------------------------------------------------------
 * 1. Images
 * ---------------------------------------------------------------------------
 */

WP_CLI::log( 'Images' );

$sgwrd_files = array();

if ( is_dir( $sgwrd_images ) ) {
	foreach ( (array) glob( $sgwrd_images . '/*.{jpg,jpeg,png,webp,avif,JPG,JPEG,PNG,WEBP}', GLOB_BRACE ) as $file ) {
		$sgwrd_files[ basename( $file ) ] = $file;
	}
}

$sgwrd_pool = array_values( $sgwrd_files );

if ( ! $sgwrd_pool ) {
	sgwrd_demo_say( 'demo/images is empty. The demo still builds, with grey placeholders.' );
} else {
	sgwrd_demo_say( count( $sgwrd_pool ) . ' image(s) found' );
}

/**
 * Get the attachment ID for a file name from the CSV.
 *
 * When the name is not in demo/images, the script falls back to the next
 * image in the folder, so your own photos are used even before you edit the
 * CSV. It says so when it does.
 *
 * @param string $name File name from the CSV. May be empty.
 * @return int
 */
function sgwrd_demo_pick( $name ) {

	global $sgwrd_files, $sgwrd_pool;

	static $next = 0;

	$name = trim( (string) $name );

	if ( $name && isset( $sgwrd_files[ $name ] ) ) {
		return sgwrd_demo_media( $sgwrd_files[ $name ] );
	}

	if ( ! $sgwrd_pool ) {
		return 0;
	}

	$file = $sgwrd_pool[ $next % count( $sgwrd_pool ) ];
	++$next;

	return sgwrd_demo_media( $file );
}

/*
 * ---------------------------------------------------------------------------
 * 2. Product categories
 * ---------------------------------------------------------------------------
 */

WP_CLI::log( 'Product categories' );

$sgwrd_cats = array();

$sgwrd_cat_spec = array(
	'new-in'    => array(
		'name'  => 'New in',
		'intro' => 'The newest pieces, added this week.',
	),
	'clothing'  => array(
		'name'  => 'Clothing',
		'intro' => 'Everyday pieces in natural materials. Made to last, easy to wear.',
		'faq'   => array(
			array( 'How do I find my size?', '<p>Each product page has a size chart. Between two sizes, take the larger one.</p>' ),
			array( 'How do I wash these?', '<p>Wash at 30 degrees, inside out. Do not tumble dry.</p>' ),
		),
	),
	'accessories' => array(
		'name'  => 'Accessories',
		'intro' => 'Small things that finish the look.',
	),
	'home'      => array(
		'name'  => 'Home',
		'intro' => 'Objects for the table, the kitchen and the shelf.',
	),
);

foreach ( $sgwrd_cat_spec as $slug => $spec ) {

	$term = get_term_by( 'slug', $slug, 'product_cat' );

	if ( ! $term ) {
		$made = wp_insert_term( $spec['name'], 'product_cat', array( 'slug' => $slug ) );

		if ( is_wp_error( $made ) ) {
			WP_CLI::warning( 'Category ' . $slug . ': ' . $made->get_error_message() );
			continue;
		}

		$term = get_term( $made['term_id'], 'product_cat' );
	}

	update_term_meta( $term->term_id, SGWRD_DEMO_MARK, '1' );

	// The category header fields.
	update_field( 'field_sgwrd_term_intro', '<p>' . esc_html( $spec['intro'] ) . '</p>', $term );

	$banner = sgwrd_demo_pick( '' );

	if ( $banner ) {
		update_field( 'field_sgwrd_term_banner', $banner, $term );
	}

	// The category questions.
	if ( ! empty( $spec['faq'] ) ) {
		update_field( 'field_sgwrd_faq_heading', 'About ' . $spec['name'], $term );
		update_field(
			'field_sgwrd_faq_items',
			array_map(
				static function ( $row ) {
					return array(
						'field_sgwrd_faq_q' => $row[0],
						'field_sgwrd_faq_a' => $row[1],
					);
				},
				$spec['faq']
			),
			$term
		);
	}

	$sgwrd_cats[ $slug ] = $term;
	sgwrd_demo_say( $spec['name'] );
}

/*
 * ---------------------------------------------------------------------------
 * 3. Products
 * ---------------------------------------------------------------------------
 */

WP_CLI::log( 'Products' );

if ( ! is_readable( $sgwrd_csv ) ) {
	WP_CLI::error( 'demo/products.csv is missing.' );
}

$sgwrd_handle = fopen( $sgwrd_csv, 'r' );
$sgwrd_head   = fgetcsv( $sgwrd_handle );
$sgwrd_made   = 0;

while ( ( $sgwrd_line = fgetcsv( $sgwrd_handle ) ) !== false ) {

	if ( count( $sgwrd_line ) !== count( $sgwrd_head ) ) {
		continue;
	}

	$row = array_combine( $sgwrd_head, $sgwrd_line );

	$product = null;
	$id      = wc_get_product_id_by_sku( $row['sku'] );

	if ( $id ) {
		$product = wc_get_product( $id );
	}

	if ( ! $product ) {
		$product = new WC_Product_Simple();
	}

	$product->set_name( $row['name'] );
	$product->set_sku( $row['sku'] );
	$product->set_status( 'publish' );
	$product->set_catalog_visibility( 'visible' );
	$product->set_regular_price( $row['price'] );
	$product->set_sale_price( '' !== trim( $row['sale_price'] ) ? $row['sale_price'] : '' );
	$product->set_short_description( $row['short_description'] );
	$product->set_description( $row['description'] );
	$product->set_featured( '1' === trim( $row['featured'] ) );
	$product->set_manage_stock( true );
	$product->set_stock_quantity( (int) $row['stock'] );
	$product->set_stock_status( (int) $row['stock'] > 0 ? 'instock' : 'outofstock' );

	$slugs = array_filter( array_map( 'trim', explode( '|', $row['category'] ) ) );
	$ids   = array();

	foreach ( $slugs as $slug ) {
		if ( isset( $sgwrd_cats[ $slug ] ) ) {
			$ids[] = $sgwrd_cats[ $slug ]->term_id;
		}
	}

	if ( $ids ) {
		$product->set_category_ids( $ids );
	}

	$images = array_filter( array_map( 'trim', explode( '|', $row['images'] ) ) );
	$att    = array();

	foreach ( $images ? $images : array( '' ) as $image ) {
		$found = sgwrd_demo_pick( $image );

		if ( $found ) {
			$att[] = $found;
		}
	}

	if ( $att ) {
		$product->set_image_id( array_shift( $att ) );
		$product->set_gallery_image_ids( $att );
	}

	$product_id = $product->save();

	update_post_meta( $product_id, SGWRD_DEMO_MARK, '1' );

	// A few products get their own questions, to show the extra tab.
	if ( 0 === $sgwrd_made % 4 ) {
		update_field( 'field_sgwrd_faq_heading', '', $product_id );
		update_field(
			'field_sgwrd_faq_items',
			array(
				array(
					'field_sgwrd_faq_q' => 'When does this arrive?',
					'field_sgwrd_faq_a' => '<p>Order before 22:00 and we send it the same working day.</p>',
				),
				array(
					'field_sgwrd_faq_q' => 'Can I return it?',
					'field_sgwrd_faq_a' => '<p>Yes. You have 30 days, and the return is free.</p>',
				),
			),
			$product_id
		);
	}

	++$sgwrd_made;
}

fclose( $sgwrd_handle );
sgwrd_demo_say( $sgwrd_made . ' product(s)' );

/*
 * ---------------------------------------------------------------------------
 * 4. Pages
 * ---------------------------------------------------------------------------
 */

WP_CLI::log( 'Pages' );

$sgwrd_shop_url = get_permalink( wc_get_page_id( 'shop' ) );

/* -- Home ---------------------------------------------------------------- */

$home = '';

$home .= sgwrd_demo_block(
	'acf/hero',
	array_merge(
		array(
			'eyebrow'          => array( 'New season', 'field_sgwrd_hero_eyebrow' ),
			'heading'          => array( 'Made to last', 'field_sgwrd_hero_heading' ),
			'text'             => array( '<p>A small collection of everyday pieces, in materials that get better with age.</p>', 'field_sgwrd_hero_text' ),
			'layout'           => array( 'cover', 'field_sgwrd_hero_layout' ),
			'background_media' => array( 'image', 'field_sgwrd_hero_media' ),
			'image'            => array( sgwrd_demo_pick( '' ), 'field_sgwrd_hero_image' ),
			'overlay_opacity'  => array( 45, 'field_sgwrd_hero_overlay' ),
			'size'             => array( 'large', 'field_sgwrd_hero_size' ),
		),
		sgwrd_demo_rows(
			'buttons',
			'field_sgwrd_hero_buttons',
			array(
				array(
					'style' => 'primary',
					'link'  => array(
						'title'  => 'Shop the collection',
						'url'    => $sgwrd_shop_url,
						'target' => '',
					),
				),
			),
			array(
				'style' => 'field_sgwrd_hero_button_style',
				'link'  => 'field_sgwrd_hero_button_link',
			)
		)
	),
	array( 'align' => 'full' )
);

$home .= sgwrd_demo_block(
	'acf/usp',
	array_merge(
		array( 'heading' => array( '', 'field_sgwrd_usp_heading' ) ),
		sgwrd_demo_rows(
			'items',
			'field_sgwrd_usp_items',
			array(
				array(
					'title' => 'Free shipping from 50 euro',
					'text'  => 'Sent the same working day.',
				),
				array(
					'title' => '30 days to return',
					'text'  => 'Free, and no questions.',
				),
				array(
					'title' => 'Made in Europe',
					'text'  => 'Small workshops, fair pay.',
				),
			),
			array(
				'icon'  => 'field_sgwrd_usp_icon',
				'title' => 'field_sgwrd_usp_title',
				'text'  => 'field_sgwrd_usp_text',
			)
		)
	),
	array( 'align' => 'wide' )
);

$home .= sgwrd_demo_block(
	'acf/product-grid',
	array(
		'heading'  => array( 'Featured', 'field_sgwrd_pg_heading' ),
		'source'   => array( 'featured', 'field_sgwrd_pg_source' ),
		'count'    => array( 4, 'field_sgwrd_pg_count' ),
		'columns'  => array( '4', 'field_sgwrd_pg_columns' ),
		'link'     => array(
			array(
				'title'  => 'All products',
				'url'    => $sgwrd_shop_url,
				'target' => '',
			),
			'field_sgwrd_pg_link',
		),
	),
	array( 'align' => 'wide' )
);

$home .= sgwrd_demo_block(
	'acf/hero-split',
	array_merge(
		sgwrd_demo_rows(
			'panels',
			'field_sgwrd_hs_panels',
			array(
				array(
					'image'           => sgwrd_demo_pick( '' ),
					'overlay_opacity' => 40,
					'eyebrow'         => 'Clothing',
					'heading'         => 'Wear it every day',
					'text'            => 'Natural materials, cut to last.',
					'link'            => array(
						'title'  => 'Shop clothing',
						'url'    => isset( $sgwrd_cats['clothing'] ) ? get_term_link( $sgwrd_cats['clothing'] ) : $sgwrd_shop_url,
						'target' => '',
					),
				),
				array(
					'image'           => sgwrd_demo_pick( '' ),
					'overlay_opacity' => 55,
					'eyebrow'         => 'Home',
					'heading'         => 'For the table',
					'text'            => 'Objects that earn their place.',
					'link'            => array(
						'title'  => 'Shop home',
						'url'    => isset( $sgwrd_cats['home'] ) ? get_term_link( $sgwrd_cats['home'] ) : $sgwrd_shop_url,
						'target' => '',
					),
				),
			),
			array(
				'image'           => 'field_sgwrd_hs_image',
				'overlay_opacity' => 'field_sgwrd_hs_overlay',
				'eyebrow'         => 'field_sgwrd_hs_eyebrow',
				'heading'         => 'field_sgwrd_hs_heading',
				'text'            => 'field_sgwrd_hs_text',
				'link'            => 'field_sgwrd_hs_link',
			)
		),
		array( 'size' => array( 'medium', 'field_sgwrd_hs_size' ) )
	),
	array( 'align' => 'full' )
);

$home .= sgwrd_demo_block(
	'acf/product-grid',
	array(
		'heading' => array( 'New in', 'field_sgwrd_pg_heading' ),
		'source'  => array( 'recent', 'field_sgwrd_pg_source' ),
		'count'   => array( 4, 'field_sgwrd_pg_count' ),
		'columns' => array( '4', 'field_sgwrd_pg_columns' ),
	),
	array( 'align' => 'wide' )
);

$home .= sgwrd_demo_block(
	'acf/faq',
	array_merge(
		array( 'faq_heading' => array( 'Questions', 'field_sgwrd_faq_heading' ) ),
		sgwrd_demo_rows(
			'faq_items',
			'field_sgwrd_faq_items',
			array(
				array(
					'question' => 'How long does delivery take?',
					'answer'   => '<p>One to two working days in the Netherlands and Belgium. Three to five days elsewhere in Europe.</p>',
				),
				array(
					'question' => 'What does shipping cost?',
					'answer'   => '<p>Nothing above 50 euro. Under that it is 4.95 euro.</p>',
				),
				array(
					'question' => 'Can I return an order?',
					'answer'   => '<p>Yes. You have 30 days from the day it arrives, and the return is free.</p>',
				),
				array(
					'question' => 'Which payment methods can I use?',
					'answer'   => '<p>iDEAL, credit card, PayPal and bank transfer.</p>',
				),
			),
			array(
				'question' => 'field_sgwrd_faq_q',
				'answer'   => 'field_sgwrd_faq_a',
			)
		)
	),
	array( 'align' => 'full' )
);

$home .= sgwrd_demo_block(
	'acf/cta',
	array_merge(
		array(
			'heading' => array( 'Ten percent off your first order', 'field_sgwrd_cta_heading' ),
			'text'    => array( 'Join the list. One mail a month, no more.', 'field_sgwrd_cta_text' ),
			'tone'    => array( 'contrast', 'field_sgwrd_cta_tone' ),
		),
		sgwrd_demo_rows(
			'buttons',
			'field_sgwrd_cta_buttons',
			array(
				array(
					'style' => 'primary',
					'link'  => array(
						'title'  => 'Sign up',
						'url'    => '/contact/',
						'target' => '',
					),
				),
			),
			array(
				'style' => 'field_sgwrd_cta_button_style',
				'link'  => 'field_sgwrd_cta_button_link',
			)
		)
	),
	array( 'align' => 'wide' )
);

$home_id = sgwrd_demo_page( 'home', 'Home', $home );
sgwrd_demo_say( 'Home' );

/* -- About --------------------------------------------------------------- */

$about = sgwrd_demo_block(
	'acf/content-image',
	array(
		'eyebrow'        => array( 'Our story', 'field_sgwrd_ci_eyebrow' ),
		'heading'        => array( 'We started with one jumper', 'field_sgwrd_ci_heading' ),
		'text'           => array( '<p>We could not find a jumper that lasted more than a season, so we made one. Then people asked where to buy it.</p><p>Everything we sell is made in small workshops in Portugal and Italy. We visit each of them.</p>', 'field_sgwrd_ci_text' ),
		'image'          => array( sgwrd_demo_pick( '' ), 'field_sgwrd_ci_image' ),
		'image_position' => array( 'right', 'field_sgwrd_ci_position' ),
	),
	array( 'align' => 'wide' )
);

$about .= sgwrd_demo_block(
	'acf/cards',
	array_merge(
		array(
			'heading' => array( 'How we work', 'field_sgwrd_cards_heading' ),
			'columns' => array( '3', 'field_sgwrd_cards_columns' ),
		),
		sgwrd_demo_rows(
			'cards',
			'field_sgwrd_cards_items',
			array(
				array(
					'image' => sgwrd_demo_pick( '' ),
					'title' => 'Materials',
					'text'  => 'Wool, linen and cotton. Nothing that sheds plastic in the wash.',
				),
				array(
					'image' => sgwrd_demo_pick( '' ),
					'title' => 'Makers',
					'text'  => 'Four workshops, all in Europe, all visited twice a year.',
				),
				array(
					'image' => sgwrd_demo_pick( '' ),
					'title' => 'Repairs',
					'text'  => 'Send it back and we mend it, for as long as you own it.',
				),
			),
			array(
				'image' => 'field_sgwrd_cards_image',
				'title' => 'field_sgwrd_cards_title',
				'text'  => 'field_sgwrd_cards_text',
				'link'  => 'field_sgwrd_cards_link',
			)
		)
	),
	array( 'align' => 'wide' )
);

sgwrd_demo_page( 'about', 'About', $about );
sgwrd_demo_say( 'About' );

/* -- Contact ------------------------------------------------------------- */

$contact = sgwrd_demo_block(
	'acf/contact',
	array(
		'heading'        => array( 'Talk to us', 'field_sgwrd_contact_heading' ),
		'text'           => array( '<p>We answer within one working day.</p>', 'field_sgwrd_contact_text' ),
		'address'        => array( "Keizersgracht 1\n1015 CD Amsterdam\nThe Netherlands", 'field_sgwrd_contact_address' ),
		'email'          => array( 'hello@example.test', 'field_sgwrd_contact_email' ),
		'phone'          => array( '+31 20 123 4567', 'field_sgwrd_contact_phone' ),
		'form_shortcode' => array( '', 'field_sgwrd_contact_form' ),
	),
	array( 'align' => 'wide' )
);

sgwrd_demo_page( 'contact', 'Contact', $contact );
sgwrd_demo_say( 'Contact' );

/* -- Plain pages --------------------------------------------------------- */

$plain = array(
	'shipping' => array(
		'Shipping',
		"<!-- wp:heading -->\n<h2>Shipping</h2>\n<!-- /wp:heading -->\n\n<!-- wp:paragraph -->\n<p>Order before 22:00 and we send it the same working day. Free above 50 euro.</p>\n<!-- /wp:paragraph -->",
	),
	'returns'  => array(
		'Returns',
		"<!-- wp:heading -->\n<h2>Returns</h2>\n<!-- /wp:heading -->\n\n<!-- wp:paragraph -->\n<p>You have 30 days from the day the order arrives. The return is free.</p>\n<!-- /wp:paragraph -->",
	),
	'privacy'  => array(
		'Privacy',
		"<!-- wp:paragraph -->\n<p>This is a demo site. It stores no real personal data.</p>\n<!-- /wp:paragraph -->",
	),
	'terms'    => array(
		'Terms',
		"<!-- wp:paragraph -->\n<p>This is a demo site. These terms are placeholder text.</p>\n<!-- /wp:paragraph -->",
	),
);

foreach ( $plain as $slug => $page ) {
	sgwrd_demo_page( $slug, $page[0], $page[1] );
	sgwrd_demo_say( $page[0] );
}

/*
 * ---------------------------------------------------------------------------
 * 5. Front page and menus
 * ---------------------------------------------------------------------------
 */

WP_CLI::log( 'Front page and menus' );

if ( $home_id ) {
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $home_id );
}

/**
 * Build a menu and fill it, replacing whatever was in it.
 *
 * @param string $location Theme menu location.
 * @param array  $items    Each item as array( title, url ).
 * @return void
 */
function sgwrd_demo_menu( $location, $items ) {

	$name = 'Demo ' . $location;
	$menu = wp_get_nav_menu_object( $name );

	if ( $menu ) {
		foreach ( wp_get_nav_menu_items( $menu->term_id ) as $item ) {
			wp_delete_post( $item->ID, true );
		}
	} else {
		$id = wp_create_nav_menu( $name );

		if ( is_wp_error( $id ) ) {
			WP_CLI::warning( 'Menu ' . $name . ': ' . $id->get_error_message() );
			return;
		}

		$menu = wp_get_nav_menu_object( $id );
	}

	foreach ( $items as $item ) {
		wp_update_nav_menu_item(
			$menu->term_id,
			0,
			array(
				'menu-item-title'   => $item[0],
				'menu-item-url'     => $item[1],
				'menu-item-status'  => 'publish',
				'menu-item-type'    => 'custom',
			)
		);
	}

	$locations              = (array) get_theme_mod( 'nav_menu_locations', array() );
	$locations[ $location ] = $menu->term_id;
	set_theme_mod( 'nav_menu_locations', $locations );
}

sgwrd_demo_menu(
	'primary',
	array(
		array( 'Shop', $sgwrd_shop_url ),
		array( 'Clothing', isset( $sgwrd_cats['clothing'] ) ? get_term_link( $sgwrd_cats['clothing'] ) : $sgwrd_shop_url ),
		array( 'Accessories', isset( $sgwrd_cats['accessories'] ) ? get_term_link( $sgwrd_cats['accessories'] ) : $sgwrd_shop_url ),
		array( 'Home', isset( $sgwrd_cats['home'] ) ? get_term_link( $sgwrd_cats['home'] ) : $sgwrd_shop_url ),
		array( 'About', home_url( '/about/' ) ),
		array( 'Contact', home_url( '/contact/' ) ),
	)
);

sgwrd_demo_menu(
	'footer',
	array(
		array( 'Shipping', home_url( '/shipping/' ) ),
		array( 'Returns', home_url( '/returns/' ) ),
		array( 'Contact', home_url( '/contact/' ) ),
	)
);

sgwrd_demo_menu(
	'legal',
	array(
		array( 'Privacy', home_url( '/privacy/' ) ),
		array( 'Terms', home_url( '/terms/' ) ),
	)
);

sgwrd_demo_say( 'primary, footer and legal' );

/*
 * ---------------------------------------------------------------------------
 * 6. Theme settings
 * ---------------------------------------------------------------------------
 */

WP_CLI::log( 'Theme settings' );

update_field( 'field_sgwrd_set_company', 'ACF Starter Demo', 'option' );
update_field( 'field_sgwrd_set_email', 'hello@example.test', 'option' );
update_field( 'field_sgwrd_set_phone', '+31 20 123 4567', 'option' );
update_field( 'field_sgwrd_set_address', "Keizersgracht 1\n1015 CD Amsterdam", 'option' );
update_field( 'field_sgwrd_set_vat', 'NL000000000B01', 'option' );
update_field( 'field_sgwrd_set_coc', '00000000', 'option' );

update_field(
	'field_sgwrd_set_socials',
	array(
		array(
			'field_sgwrd_set_social_network' => 'instagram',
			'field_sgwrd_set_social_url'     => 'https://instagram.com/',
		),
		array(
			'field_sgwrd_set_social_network' => 'linkedin',
			'field_sgwrd_set_social_url'     => 'https://linkedin.com/',
		),
	),
	'option'
);

update_field( 'field_sgwrd_set_bar_active', 1, 'option' );
update_field( 'field_sgwrd_set_bar_text', 'Free shipping from 50 euro. Sent the same working day.', 'option' );
update_field(
	'field_sgwrd_set_bar_link',
	array(
		'title'  => 'Shipping',
		'url'    => home_url( '/shipping/' ),
		'target' => '',
	),
	'option'
);

update_field( 'field_sgwrd_set_usp_1', 'Free shipping from 50 euro', 'option' );
update_field( 'field_sgwrd_set_usp_2', '30 days to return', 'option' );
update_field( 'field_sgwrd_set_usp_3', 'Made in Europe', 'option' );

sgwrd_demo_say( 'company, socials, notice bar and shop notices' );

/*
 * ---------------------------------------------------------------------------
 * Done
 * ---------------------------------------------------------------------------
 */

if ( function_exists( 'wc_delete_product_transients' ) ) {
	wc_delete_product_transients();
}

flush_rewrite_rules( false );

WP_CLI::success( 'The demo is ready: ' . home_url( '/' ) );
