<?php 

/*************************** Restrict gutenberg blocks *********************************/

function restrict_blocks( $allowed_blocks, $post ) {
	
	if( is_user_logged_in() ) {
		
		$user = wp_get_current_user();
		$roles = ( array ) $user->roles;
	
		if( in_array( strtolower('Editor'), $roles ) )
			$allowed_blocks = array(
				'core/heading',
				'core/image',
				'core/paragraph',
				'core/quote',
				'core/block',
				'core/list',
				'core/group',
				'core/embedyoutube',
				'core/embedvimeo',
				'core/embedtwitter',
				'acf/hero',
				'acf/image_slider',
				'acf/sidebar',
				'acf/related-posts',
				'acf/promo',
				'acf/sub-items',
				'acf/info',
				'acf/categories',
				'acf/collection-items',
				'acf/collection-overview-hero',
				'acf/collection-slider',
				'acf/home-hero',
				'acf/info-two-images',
				'acf/reviews',
				'acf/home-about',
				'acf/plan-visit',
			
			);
			return $allowed_blocks;
		}

	}	
	
	add_filter( 'allowed_block_types', 'restrict_blocks', 10, 2); 