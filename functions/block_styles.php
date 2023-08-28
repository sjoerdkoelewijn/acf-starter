<?php 

defined( 'ABSPATH' ) || exit;

// Get all block names by running wp.blocks.getBlockTypes(); in the browser console.

add_action('init', function() {

	// Add a hero style to the cover image block
	register_block_style('core/cover', [
		'name' => 'hero',
		'label'        => __( 'Hero', 'ACFSTARTER' ),
	]);
	
});