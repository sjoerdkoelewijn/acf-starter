<?php

defined( 'ABSPATH' ) || exit;


function SGWRD_CACHE_BUST() {

	$theme_stylesheet = get_template_directory() . '/assets/css/style.css';
	$theme_stylesheet_modified_time = filemtime($theme_stylesheet);

	if ( isset($theme_stylesheet_modified_time) ) {

		return trim($theme_stylesheet_modified_time);

	} else {

		return 'no-cache-bust';

	}

}

function SGWRD_CHILD_CACHE_BUST() {

	$childtheme_stylesheet = get_stylesheet_directory() . '/assets/css/style.css';
	$childtheme_stylesheet_modified_time = filemtime($childtheme_stylesheet);

	if ( isset($childtheme_stylesheet_modified_time) ) {

		return trim($childtheme_stylesheet_modified_time);

	} else {

		return 'no-cache-bust';

	}

}