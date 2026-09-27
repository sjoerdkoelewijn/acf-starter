<?php
/**
 * Read-only health check for the demo.
 *
 *   wp eval-file wp-content/themes/acf-starter/demo/diagnose.php
 *
 * It changes nothing. It prints what the front end and the editor get, so a
 * problem can be found from a log instead of from a screenshot.
 *
 * @package ACF_Starter
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( "Run this file through WP-CLI.\n" );
}

/**
 * Print a heading.
 *
 * @param string $title Heading text.
 * @return void
 */
function sgwrd_diag_head( $title ) {
	WP_CLI::log( '' );
	WP_CLI::log( '== ' . $title . ' ==' );
}

/**
 * Print a label and a value.
 *
 * @param string $label Label.
 * @param mixed  $value Value.
 * @return void
 */
function sgwrd_diag_row( $label, $value ) {
	WP_CLI::log( sprintf( '  %-28s %s', $label, is_scalar( $value ) ? (string) $value : wp_json_encode( $value ) ) );
}

/* --------------------------------------------------------------------- */
sgwrd_diag_head( 'Versions' );

sgwrd_diag_row( 'WordPress', get_bloginfo( 'version' ) );
sgwrd_diag_row( 'ACF', defined( 'ACF_VERSION' ) ? ACF_VERSION : 'not loaded' );
sgwrd_diag_row( 'ACF Pro', defined( 'ACF_PRO' ) && ACF_PRO ? 'yes' : 'no' );
sgwrd_diag_row( 'WooCommerce', defined( 'WC_VERSION' ) ? WC_VERSION : 'not loaded' );
sgwrd_diag_row( 'Theme', get_template() . ' ' . wp_get_theme( get_template() )->get( 'Version' ) );

/* --------------------------------------------------------------------- */
sgwrd_diag_head( 'Editor stylesheets' );

// This is the list WordPress really hands to the editor. A missing file here
// means the editor shows the blocks with no design.
$sgwrd_editor = function_exists( 'get_editor_stylesheets' ) ? get_editor_stylesheets() : array();

if ( ! $sgwrd_editor ) {
	sgwrd_diag_row( 'result', 'NONE - the editor gets no theme CSS' );
}

foreach ( $sgwrd_editor as $sgwrd_url ) {
	sgwrd_diag_row( 'loads', str_replace( home_url(), '', $sgwrd_url ) );
}

/* --------------------------------------------------------------------- */
sgwrd_diag_head( 'Block types as ACF registered them' );

// What the editor really gets, straight from ACF. The keys differ between ACF
// versions, so print every setting whose name is about the version or the
// preview.
if ( function_exists( 'acf_get_block_types' ) ) {
	foreach ( acf_get_block_types() as $sgwrd_type_name => $sgwrd_type ) {
		if ( ! str_starts_with( (string) $sgwrd_type_name, 'acf/' ) ) {
			continue;
		}

		$sgwrd_bits = array();

		foreach ( (array) $sgwrd_type as $sgwrd_key => $sgwrd_value ) {
			if ( preg_match( '/version|preview|mode/i', (string) $sgwrd_key ) && ! is_array( $sgwrd_value ) ) {
				$sgwrd_bits[] = $sgwrd_key . '=' . var_export( $sgwrd_value, true );
			}
		}

		sgwrd_diag_row( $sgwrd_type_name, implode( '  ', $sgwrd_bits ) );
	}
} else {
	sgwrd_diag_row( 'acf_get_block_types', 'missing' );
}

/* --------------------------------------------------------------------- */
sgwrd_diag_head( 'Products' );

$sgwrd_products = get_posts(
	array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	)
);

$sgwrd_with_image = 0;

foreach ( $sgwrd_products as $sgwrd_pid ) {
	if ( has_post_thumbnail( $sgwrd_pid ) ) {
		++$sgwrd_with_image;
	}
}

sgwrd_diag_row( 'published', count( $sgwrd_products ) );
sgwrd_diag_row( 'with a photo', $sgwrd_with_image );

/* --------------------------------------------------------------------- */
sgwrd_diag_head( 'Footer widgets' );

$sgwrd_sidebars = wp_get_sidebars_widgets();
sgwrd_diag_row( 'footer', empty( $sgwrd_sidebars['footer'] ) ? 'empty' : implode( ', ', $sgwrd_sidebars['footer'] ) );

/* --------------------------------------------------------------------- */
sgwrd_diag_head( 'Home page blocks' );

$sgwrd_home = (int) get_option( 'page_on_front' );

if ( ! $sgwrd_home ) {
	sgwrd_diag_row( 'front page', 'not set' );
	return;
}

sgwrd_diag_row( 'front page id', $sgwrd_home );

// A shortcode inside a block reads the global post, as it would on a page view.
$GLOBALS['post'] = get_post( $sgwrd_home ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride
setup_postdata( $GLOBALS['post'] );

$sgwrd_render = function_exists( 'acf_rendered_block' );

if ( ! $sgwrd_render ) {
	sgwrd_diag_row( 'acf_rendered_block', 'missing - cannot compare front and editor' );
}

WP_CLI::log( '' );
WP_CLI::log( sprintf( '  %-18s %-7s %-6s %-9s %-9s %s', 'block', 'align', 'image', 'front', 'editor', 'notes' ) );

foreach ( parse_blocks( get_post_field( 'post_content', $sgwrd_home ) ) as $sgwrd_block ) {

	$sgwrd_name = $sgwrd_block['blockName'] ?? '';

	if ( ! str_starts_with( $sgwrd_name, 'acf/' ) ) {
		continue;
	}

	$sgwrd_attrs = $sgwrd_block['attrs'];
	$sgwrd_data  = $sgwrd_attrs['data'] ?? array();
	$sgwrd_notes = array();

	$sgwrd_image = $sgwrd_data['image'] ?? ( $sgwrd_data['panels_0_image'] ?? '-' );

	$sgwrd_front  = '';
	$sgwrd_editor = '';

	if ( $sgwrd_render ) {
		$sgwrd_front  = (string) acf_rendered_block( $sgwrd_attrs, '', false, $sgwrd_home );
		$sgwrd_editor = (string) acf_rendered_block( $sgwrd_attrs, '', true, $sgwrd_home );

		if ( str_contains( $sgwrd_editor, 'block__placeholder' ) && ! str_contains( $sgwrd_front, 'block__placeholder' ) ) {
			$sgwrd_notes[] = 'EDITOR SHOWS PLACEHOLDER';
		}

		$sgwrd_front_buttons  = substr_count( $sgwrd_front, 'class="button' );
		$sgwrd_editor_buttons = substr_count( $sgwrd_editor, 'class="button' );

		if ( $sgwrd_front_buttons !== $sgwrd_editor_buttons ) {
			$sgwrd_notes[] = sprintf( 'buttons front %d / editor %d', $sgwrd_front_buttons, $sgwrd_editor_buttons );
		}
	}

	WP_CLI::log(
		sprintf(
			'  %-18s %-7s %-6s %-9s %-9s %s',
			$sgwrd_name,
			$sgwrd_attrs['align'] ?? '-',
			$sgwrd_image,
			strlen( $sgwrd_front ) . 'b',
			strlen( $sgwrd_editor ) . 'b',
			implode( '; ', $sgwrd_notes )
		)
	);
}

WP_CLI::success( 'Done. Nothing was changed.' );
