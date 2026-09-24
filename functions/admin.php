<?php
/**
 * A smaller, simpler WordPress admin.
 *
 * The theme removes admin screens that hold design settings. The design lives
 * in the code. It does not remove content, users or plugin screens.
 *
 * @package ACF_Starter
 */

defined( 'ABSPATH' ) || exit;

/**
 * Remove the dashboard widgets.
 */
function sgwrd_remove_dashboard_widgets() {

	remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );      // WordPress news.
	remove_meta_box( 'dashboard_secondary', 'dashboard', 'side' );    // Other WordPress news.
	remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );  // Quick draft.
	remove_meta_box( 'dashboard_incoming_links', 'dashboard', 'normal' );
	remove_meta_box( 'dashboard_plugins', 'dashboard', 'normal' );
	remove_meta_box( 'dashboard_recent_drafts', 'dashboard', 'side' );
	remove_meta_box( 'dashboard_activity', 'dashboard', 'normal' );
}
add_action( 'wp_dashboard_setup', 'sgwrd_remove_dashboard_widgets', 20 );

/**
 * Hide the welcome panel on the dashboard.
 */
function sgwrd_remove_welcome_panel() {

	remove_action( 'welcome_panel', 'wp_welcome_panel' );
}
add_action( 'admin_init', 'sgwrd_remove_welcome_panel' );

/**
 * Remove the theme and plugin file editors from the menu.
 *
 * For real safety, also put this line in wp-config.php:
 *   define( 'DISALLOW_FILE_EDIT', true );
 */
function sgwrd_remove_file_editors() {

	remove_submenu_page( 'themes.php', 'theme-editor.php' );
	remove_submenu_page( 'plugins.php', 'plugin-editor.php' );
}
add_action( 'admin_menu', 'sgwrd_remove_file_editors', 999 );

/**
 * Replace the admin footer text.
 *
 * @return string
 */
function sgwrd_admin_footer_text() {

	$theme = wp_get_theme( get_template() );

	return sprintf(
		/* translators: 1: theme name, 2: theme version */
		esc_html__( '%1$s %2$s', 'acf-starter' ),
		esc_html( $theme->get( 'Name' ) ),
		esc_html( $theme->get( 'Version' ) )
	);
}
add_filter( 'admin_footer_text', 'sgwrd_admin_footer_text' );

/**
 * Remove the WordPress version from the bottom right of the admin.
 *
 * @return string
 */
function sgwrd_remove_admin_version() {

	return '';
}
add_filter( 'update_footer', 'sgwrd_remove_admin_version', 11 );

/**
 * Remove the items from the admin bar that the client does not need.
 *
 * @param WP_Admin_Bar $admin_bar The admin bar.
 */
function sgwrd_clean_admin_bar( $admin_bar ) {

	$admin_bar->remove_node( 'wp-logo' );
	$admin_bar->remove_node( 'comments' );
	$admin_bar->remove_node( 'customize' );
}
add_action( 'admin_bar_menu', 'sgwrd_clean_admin_bar', 999 );

/**
 * Show a notice when ACF Pro is not active.
 *
 * The theme needs ACF Pro for its blocks and for the theme settings page.
 */
function sgwrd_acf_missing_notice() {

	if ( class_exists( 'ACF' ) || ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	printf(
		'<div class="notice notice-error"><p>%s</p></div>',
		esc_html__( 'ACF Starter needs Advanced Custom Fields Pro. The theme blocks and the theme settings do not work without it.', 'acf-starter' )
	);
}
add_action( 'admin_notices', 'sgwrd_acf_missing_notice' );

/**
 * Set a larger editor font for long text fields in the admin.
 *
 * @param array $settings TinyMCE settings.
 * @return array
 */
function sgwrd_tinymce_settings( $settings ) {

	// Remove the colour picker and the font size menu from the classic editor.
	$settings['toolbar1'] = 'formatselect,bold,italic,bullist,numlist,blockquote,link,unlink,removeformat,undo,redo';
	$settings['toolbar2'] = '';
	$settings['block_formats'] = 'Paragraph=p;Heading 2=h2;Heading 3=h3;Heading 4=h4';

	return $settings;
}
add_filter( 'tiny_mce_before_init', 'sgwrd_tinymce_settings' );
