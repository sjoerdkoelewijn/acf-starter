<?php

/***************************  Custom Gutenberg Blocks **********************************/

function sgwrd_register_acf_blocks() {

    if( function_exists('acf_register_block') ) {

		/***** Hero Block *****/

		acf_register_block(array(
			'name'				=> 'hero-block',
			'title'				=> __('Hero'),
			'description'		=> __('A hero block for the top of the page'),
			'render_callback'	=> 'sgwrd_acf_block_render_callback',
			'category'			=> 'common',
			'icon'				=> 'document',
			'mode'				=> 'edit', // start in edit mode
			'keywords'			=> array( 'image', 'hero' ),
		));

		/***** Content + Image Block *****/	

        acf_register_block(array(
			'name'				=> 'content-image-block',
			'title'				=> __('Content + Image'),
			'description'		=> __('A block with text and image'),
			'render_callback'	=> 'sgwrd_acf_block_render_callback',
			'category'			=> 'common',
			'icon'				=> 'document',
			'mode'				=> 'edit', // start in edit mode
			'keywords'			=> array( 'text', 'image' ),
		));

		/***** Unique Selling Points Block *****/	

		/***** Contact Block *****/	

		/***** Call To Action Block *****/	

		/***** Card Repeater Block *****/	

		/***** Recent Posts Block *****/	

		/***** Google Maps Block *****/	

    }
}

add_action( 'init', 'sgwrd_register_acf_blocks' );



/***************************  Get Custom Block Templates **********************************/

function sgwrd_acf_block_render_callback( $block ) {
	
	// convert name ("acf/testimonial") into path friendly slug ("testimonial")
	$slug = str_replace('acf/', '', $block['name']);
	
	// include a template part from within the "blocks" folder
	if ( file_exists( get_template_directory() . "/blocks/{$slug}.php" ) ) {
		include( get_template_directory() . "/blocks/{$slug}.php" );
	}
	
}



/***************************  Save Field Group JSON **********************************/

define( get_template_directory(), untrailingslashit( plugin_dir_path( __FILE__ ) ) );
 
function sgwrd_acf_json_save_point( $path ) {
    
    // Update path
    $path = get_template_directory() . '/acf-json';

    // Return path
    return $path;
    
}

add_filter('acf/settings/save_json', 'sgwrd_acf_json_save_point');



/***************************  Get Saved Field Group JSON from parent theme **********************************/

function sgwrd_acf_json_load_point( $paths ) {
   	unset( $paths[0] ); // Remove default path
	$paths[] = get_template_directory() . '/acf-json';
   	return $paths; // Append our new path
}

add_filter('acf/settings/load_json', 'sgwrd_acf_json_load_point');