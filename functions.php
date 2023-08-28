<?php

defined( 'ABSPATH' ) || exit;

// Define constants.
require_once get_template_directory() . '/functions/constants.php';

// Load styles & scripts 
require_once get_template_directory() . '/functions/enqueue.php';

// Remove default wp nonsense
require_once get_template_directory() . '/functions/cleanup.php';

// Add theme support
require_once get_template_directory() . '/functions/theme_support.php';

// Add custom block styles
require_once get_template_directory() . '/functions/block_styles.php';

// Add shortcodes
require_once get_template_directory() . '/functions/shortcodes.php';

// Add ACF Blocks
require_once get_template_directory() . '/functions/acf_blocks.php';

// Restict Blocks
require_once get_template_directory() . '/functions/restrict_blocks.php';