<?php
/**
 * Styles and scripts.
 *
 * Load order on the front end:
 *   1. WordPress global styles from theme.json
 *   2. assets/css/style.css        from the parent theme
 *   3. assets/css/woocommerce.css  from the parent theme, only when the shop is active
 *   4. assets/css/style.css        from the child theme, when the file is there
 *
 * @package ACF_Starter
 */

defined( 'ABSPATH' ) || exit;

/**
 * Load the front end styles and scripts.
 */
function sgwrd_enqueue_assets() {

	// The theme stylesheet.
	wp_enqueue_style(
		'sgwrd-style',
		SGWRD_URI . '/assets/css/style.css',
		array(),
		sgwrd_asset_version( 'assets/css/style.css' )
	);

	// The shop stylesheet.
	if ( sgwrd_has_woocommerce() ) {
		wp_enqueue_style(
			'sgwrd-woocommerce',
			SGWRD_URI . '/assets/css/woocommerce.css',
			array( 'sgwrd-style' ),
			sgwrd_asset_version( 'assets/css/woocommerce.css' )
		);
	}

	// The child theme stylesheet. It always loads last, so it wins.
	if ( is_child_theme() && is_readable( SGWRD_CHILD_DIR . '/assets/css/style.css' ) ) {
		wp_enqueue_style(
			'sgwrd-child-style',
			SGWRD_CHILD_URI . '/assets/css/style.css',
			array( 'sgwrd-style' ),
			sgwrd_asset_version( 'assets/css/style.css', true )
		);
	}

	// The theme script. It is small and it has no dependencies.
	wp_enqueue_script(
		'sgwrd-theme',
		SGWRD_URI . '/assets/js/theme.js',
		array(),
		sgwrd_asset_version( 'assets/js/theme.js' ),
		array( 'strategy' => 'defer' )
	);

	// The comment reply script. It loads only on a page that needs it.
	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'sgwrd_enqueue_assets' );

/**
 * Load the editor stylesheets.
 *
 * The editor gets the front end stylesheets as well as the editor one. The
 * theme blocks show no preview (see "renderPreview" in each block.json), but
 * the core blocks do, and a block that turns its preview back on needs the
 * front end CSS to look right. The shop stylesheet comes too, for the same
 * reason: the product grid block prints WooCommerce markup.
 *
 * WordPress rewrites the selectors so the rules stay inside the editor canvas.
 * See the note at the top of assets/css/editor.css.
 *
 * Give add_editor_style() plain paths, relative to the theme folder. Never add
 * a version query: WordPress checks that each path is a real file, and a path
 * with "?ver=" in it is not, so the stylesheet is silently dropped. There is
 * no cache to bust anyway, because the editor reads the file contents inline.
 */
function sgwrd_editor_styles() {

	$styles = array( 'assets/css/style.css' );

	if ( sgwrd_has_woocommerce() ) {
		$styles[] = 'assets/css/woocommerce.css';
	}

	// Last, so its editor only rules win.
	$styles[] = 'assets/css/editor.css';

	add_editor_style( $styles );
}
add_action( 'after_setup_theme', 'sgwrd_editor_styles', 20 );

/**
 * Load the admin stylesheet.
 */
function sgwrd_enqueue_admin_assets() {

	wp_enqueue_style(
		'sgwrd-admin',
		SGWRD_URI . '/assets/css/admin.css',
		array(),
		sgwrd_asset_version( 'assets/css/admin.css' )
	);
}
add_action( 'admin_enqueue_scripts', 'sgwrd_enqueue_admin_assets' );
