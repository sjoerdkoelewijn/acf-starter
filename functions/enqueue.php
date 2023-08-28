<?php

defined( 'ABSPATH' ) || exit;

add_action( 'wp_enqueue_scripts', 'sgwrd_load_assets' );
add_action( 'admin_enqueue_scripts', 'sgwrd_load_admin_assets' );

function sgwrd_load_assets() {  

    wp_enqueue_script( 
        'sgwrd-main-script', 
        get_template_directory_uri() . 'assets/js/sigward.js',
        array(),
        SGWRD_CACHE_BUST()
    );  
    
    wp_enqueue_script( 
        'scrollbar-width-script', 
        get_template_directory_uri() . 'assets/js/scrollbar-width.js',
        array(),
        SGWRD_CACHE_BUST() 
    );      

    // Removes the backward comp styles for buttons as they are not used anyway
    if (wp_style_is('classic-theme-styles', 'registered')) {

        wp_deregister_style('classic-theme-styles');

    }     
    
    // Import main style
    wp_enqueue_style(
        'sgwrd-main-style',
        get_template_directory_uri() . 'assets/css/style.css',
        array(),
        SGWRD_CACHE_BUST()
    );    

}    

function sgwrd_load_admin_assets() {
   
    wp_enqueue_style(
        'sgwrd-admin-style',
        get_template_directory_uri() . 'assets/css/admin.css',
        array(),
        SGWRD_CACHE_BUST()
    );  

}  