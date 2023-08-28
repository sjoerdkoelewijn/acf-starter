<?php

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'SGWRD' ) ) {

}

class SGWRD {

    public function __construct() {

		add_action( 'after_setup_theme', array( $this, 'SGWRD_theme_support' ) );

	}
        
    public function SGWRD_theme_support() {


        /** Add full width option to blocks **/
        add_theme_support('align-wide');

        /** automatic feed link in head **/
        add_theme_support( 'automatic-feed-links' );

        /** tag-title **/
        add_theme_support( 'title-tag' );

        /** post thumbnail **/
        add_theme_support( 'post-thumbnails' );

        /** HTML5 support **/
        add_theme_support( 'html5', array( 'comment-list', 'comment-form', 'search-form', 'gallery', 'caption', 'style', 'script' ) );

        /** refresh widgets **/
        add_theme_support( 'customize-selective-refresh-widgets' );



        // Editor Color Palette.
        add_theme_support(
            'editor-color-palette',
            array(
                array(
                    'name'  => __( 'Primary Color', 'sgwrd' ),
                    'slug'  => 'sgwrd-primary-theme',
                    'color' => '#ffffff',
                ),
                array(
                    'name'  => __( 'Secondary Color', 'sgwrd' ),
                    'slug'  => 'sgwrd-secondary-theme',
                    'color' => '#000000',
                ),
			
            )
        );

    
    }

}

return new SGWRD();