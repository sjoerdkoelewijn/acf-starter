<?php 

defined( 'ABSPATH' ) || exit;


/** Current year *************************/

function current_year_shortcode() {
    $year = date('Y');
    return $year;
}
add_shortcode('year', 'current_year_shortcode');
