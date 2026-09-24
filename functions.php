<?php
/**
 * Theme bootstrap.
 *
 * This file only loads the modules in /functions. Put no logic here.
 *
 * @package ACF_Starter
 */

defined( 'ABSPATH' ) || exit;

$sgwrd_modules = array(
	'constants',    // Version, paths and cache busting.
	'setup',        // Theme support, menus, image sizes.
	'assets',       // Styles and scripts.
	'cleanup',      // Remove the default WordPress output you do not need.
	'editor',       // Block editor rules: the allow list and the block styles.
	'admin',        // A smaller, simpler WordPress admin.
	'acf',          // ACF JSON sync, ACF blocks and the options page.
	'template-tags',// Small helper functions for the templates.
	'shortcodes',   // Theme shortcodes.
	'woocommerce',  // Shop support. The file does nothing when WooCommerce is off.
);

foreach ( $sgwrd_modules as $sgwrd_module ) {
	require_once get_template_directory() . '/functions/' . $sgwrd_module . '.php';
}

unset( $sgwrd_modules, $sgwrd_module );
