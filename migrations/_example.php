<?php
/**
 * An example migration. It never runs: the name starts with "_".
 *
 * Copy it to a name that starts with the date, for example
 * 2026-09-27-add-outlet-page.php, and change it.
 *
 * Every helper looks first and makes only what is not there, so a second
 * run changes nothing. Orders, customers and stock are never touched.
 *
 * @package ACF_Starter
 */

defined( 'ABSPATH' ) || exit;

return function () {

	// 1. A page with a call to action block. A page with this slug that is
	//    already there gets this title and content.
	$outlet = sgwrd_migrate_page(
		'outlet',
		array(
			'title'   => 'Outlet',
			'content' => sgwrd_migrate_block(
				'acf/cta',
				array_merge(
					array(
						'heading' => array( 'Last pieces, lower prices', 'field_sgwrd_cta_heading' ),
						'tone'    => array( 'contrast', 'field_sgwrd_cta_tone' ),
					),
					sgwrd_migrate_rows(
						'buttons',
						'field_sgwrd_cta_buttons',
						array(
							array(
								'style' => 'primary',
								'link'  => array( 'title' => 'Shop the outlet', 'url' => '/shop/', 'target' => '' ),
							),
						),
						array(
							'style' => 'field_sgwrd_cta_button_style',
							'link'  => 'field_sgwrd_cta_button_link',
						)
					)
				),
				array( 'align' => 'wide' )
			),
		)
	);

	// 2. Put the page in the menu that shows in the primary location.
	sgwrd_migrate_menu_item( 'primary', 'Outlet', $outlet, array( 'position' => 2 ) );

	// 3. Or: switch to a menu you prepared on the live site. Make the menu in
	//    Appearance -> Menus first, and give it no location.
	// sgwrd_migrate_menu_location( 'primary', 'Main menu 2027' );

	// 4. A WordPress option, or a field on the theme settings page.
	// update_option( 'woocommerce_enable_reviews', 'yes' );
	// update_field( 'bar_text', 'Free shipping this week', 'option' );
};
