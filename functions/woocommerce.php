<?php
/**
 * WooCommerce support.
 *
 * The theme changes the shop with hooks, and it overrides two templates only:
 *
 *   woocommerce/content-product.php  the product card in a grid
 *   woocommerce/cart/mini-cart.php   the cart drawer in the header
 *
 * The cart page and the checkout page keep the WooCommerce templates. The theme
 * gives them a layout with CSS. That way a WooCommerce update never breaks the
 * checkout, which is the one page that must always work.
 *
 * @package ACF_Starter
 */

defined( 'ABSPATH' ) || exit;

// Do nothing at all when WooCommerce is off.
if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

/**
 * Tell WooCommerce that the theme supports the shop.
 */
function sgwrd_woocommerce_support() {

	add_theme_support(
		'woocommerce',
		array(
			'thumbnail_image_width' => 600,
			'single_image_width'    => 1200,
			'product_grid'          => array(
				'default_rows'    => 3,
				'min_rows'        => 1,
				'default_columns' => 3,
				'min_columns'     => 2,
				'max_columns'     => 4,
			),
		)
	);

	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
}
add_action( 'after_setup_theme', 'sgwrd_woocommerce_support' );

/**
 * Declare that the theme works with High Performance Order Storage.
 */
function sgwrd_declare_hpos_support() {

	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
}
add_action( 'before_woocommerce_init', 'sgwrd_declare_hpos_support' );

/*
 * ---------------------------------------------------------------------------
 * Layout
 * ---------------------------------------------------------------------------
 */

// Remove the WooCommerce page wrappers and print the theme wrappers instead.
remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );

/**
 * Open the shop wrapper.
 */
function sgwrd_woocommerce_wrapper_start() {

	echo '<main id="main" class="site-main site-main--shop">';
}
add_action( 'woocommerce_before_main_content', 'sgwrd_woocommerce_wrapper_start', 10 );

/**
 * Close the shop wrapper.
 */
function sgwrd_woocommerce_wrapper_end() {

	echo '</main>';
}
add_action( 'woocommerce_after_main_content', 'sgwrd_woocommerce_wrapper_end', 10 );

// The theme has no shop sidebar. Add one in a child theme when you need it.
remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );

// The demo store notice is a banner that the theme does not style.
remove_action( 'wp_footer', 'woocommerce_demo_store' );

/**
 * Add the theme class to the product loop, so the CSS can make a grid of it.
 *
 * @return string
 */
function sgwrd_product_loop_start() {

	return '<ul class="products product-grid">';
}
add_filter( 'woocommerce_product_loop_start', 'sgwrd_product_loop_start' );

/**
 * Show four products in a row and twelve on a page.
 *
 * @return int
 */
function sgwrd_loop_columns() {

	return 4;
}
add_filter( 'loop_shop_columns', 'sgwrd_loop_columns', 20 );

/**
 * Set the number of products on an archive page.
 *
 * @return int
 */
function sgwrd_products_per_page() {

	return 12;
}
add_filter( 'loop_shop_per_page', 'sgwrd_products_per_page', 20 );

/*
 * ---------------------------------------------------------------------------
 * The product card
 * ---------------------------------------------------------------------------
 */

// Move the sale flash into the image wrapper. See woocommerce/content-product.php.
remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10 );

// The default title is an <h2>. The theme prints its own, so the markup stays flat.
remove_action( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10 );

/**
 * Print the product title as a heading with a class.
 */
function sgwrd_loop_product_title() {

	echo '<h2 class="product-card__title">' . esc_html( get_the_title() ) . '</h2>';
}
add_action( 'woocommerce_shop_loop_item_title', 'sgwrd_loop_product_title', 10 );

// Show the star rating below the price, not above the title.
remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 );

/*
 * ---------------------------------------------------------------------------
 * The single product page
 * ---------------------------------------------------------------------------
 */

// A breadcrumb is useful in a shop, but the default position is too high.
remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
add_action( 'woocommerce_before_single_product_summary', 'woocommerce_breadcrumb', 4 );

/**
 * Set the number of related products.
 *
 * @param array $args Related product arguments.
 * @return array
 */
function sgwrd_related_products_args( $args ) {

	$args['posts_per_page'] = 4;
	$args['columns']        = 4;

	return $args;
}
add_filter( 'woocommerce_output_related_products_args', 'sgwrd_related_products_args', 20 );

/*
 * ---------------------------------------------------------------------------
 * ACF content on the shop pages
 * ---------------------------------------------------------------------------
 *
 * A product archive and a product page are not built from blocks, so a block
 * cannot reach them. The fields sit on the term and on the product itself, and
 * these hooks print them.
 */

/**
 * Print the banner and the intro of a product category.
 *
 * The hook runs inside the theme wrapper, above the WooCommerce page title.
 * The title of the category header replaces the WooCommerce one, so it is
 * never printed twice.
 */
function sgwrd_shop_term_header() {

	if ( ! is_product_taxonomy() ) {
		return;
	}

	sgwrd_the_term_header();
}
add_action( 'woocommerce_before_main_content', 'sgwrd_shop_term_header', 15 );

/**
 * Hide the WooCommerce page title when the category header printed one.
 *
 * @param bool $show Current value.
 * @return bool
 */
function sgwrd_shop_page_title( $show ) {

	return sgwrd_has_term_header() ? false : $show;
}
add_filter( 'woocommerce_show_page_title', 'sgwrd_shop_page_title' );

/**
 * Print the questions and answers of a product category, under the grid.
 */
function sgwrd_shop_term_faq() {

	if ( ! is_product_taxonomy() ) {
		return;
	}

	$term = get_queried_object();

	if ( ! $term instanceof WP_Term || ! sgwrd_has_faq( $term ) ) {
		return;
	}

	echo '<section class="faq faq--archive">';
	sgwrd_the_faq( $term );
	echo '</section>';
}
add_action( 'woocommerce_after_main_content', 'sgwrd_shop_term_faq', 5 );

/**
 * Add the questions and answers of a product as an extra tab.
 *
 * A tab keeps the product page short, and it puts the answers where a buyer
 * looks for them.
 *
 * @param array $tabs The product tabs.
 * @return array
 */
function sgwrd_product_faq_tab( $tabs ) {

	global $post;

	if ( ! $post || ! sgwrd_has_faq( $post->ID ) ) {
		return $tabs;
	}

	$tabs['sgwrd_faq'] = array(
		'title'    => __( 'Questions', 'acf-starter' ),
		'priority' => 25,
		'callback' => 'sgwrd_product_faq_tab_content',
	);

	return $tabs;
}
add_filter( 'woocommerce_product_tabs', 'sgwrd_product_faq_tab' );

/**
 * Print the content of the product questions tab.
 */
function sgwrd_product_faq_tab_content() {

	global $post;

	echo '<div class="faq faq--product">';
	sgwrd_the_faq( $post->ID );
	echo '</div>';
}

/*
 * ---------------------------------------------------------------------------
 * The header cart
 * ---------------------------------------------------------------------------
 */

/**
 * Print the cart button for the header.
 *
 * The markup is inside a wrapper with the class 'header-cart', because
 * WooCommerce replaces that wrapper after an AJAX add to cart.
 *
 * @return void
 */
function sgwrd_the_cart_button() {

	if ( ! function_exists( 'WC' ) || null === WC()->cart ) {
		return;
	}

	$count = WC()->cart->get_cart_contents_count();

	printf(
		'<a class="header-cart" href="%1$s" aria-label="%2$s"><span class="header-cart__label">%3$s</span><span class="header-cart__count" data-empty="%4$s">%5$s</span></a>',
		esc_url( wc_get_cart_url() ),
		esc_attr(
			sprintf(
				/* translators: %d: number of items in the cart */
				_n( 'Cart, %d item', 'Cart, %d items', $count, 'acf-starter' ),
				$count
			)
		),
		esc_html__( 'Cart', 'acf-starter' ),
		esc_attr( $count > 0 ? 'false' : 'true' ),
		esc_html( $count )
	);
}

/**
 * Refresh the header cart button after an AJAX add to cart.
 *
 * @param array $fragments Cart fragments.
 * @return array
 */
function sgwrd_cart_fragment( $fragments ) {

	ob_start();
	echo '<div class="site-header__cart">';
	sgwrd_the_cart_button();
	echo '</div>';

	$fragments['div.site-header__cart'] = ob_get_clean();

	return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'sgwrd_cart_fragment' );

/*
 * ---------------------------------------------------------------------------
 * Styles
 * ---------------------------------------------------------------------------
 */

/**
 * Remove the WooCommerce stylesheets that fight with the theme design.
 *
 * The theme ships assets/css/woocommerce.css instead. The block stylesheet
 * stays, because the block cart and the block checkout need it.
 *
 * @param array $styles WooCommerce stylesheet handles.
 * @return array
 */
function sgwrd_woocommerce_styles( $styles ) {

	/**
	 * Filter the removal of the WooCommerce stylesheets.
	 *
	 * Return false in a child theme to keep the WooCommerce design.
	 *
	 * @param bool $remove True to remove the WooCommerce stylesheets.
	 */
	if ( ! apply_filters( 'sgwrd_remove_woocommerce_styles', true ) ) {
		return $styles;
	}

	unset( $styles['woocommerce-general'] );
	unset( $styles['woocommerce-layout'] );
	unset( $styles['woocommerce-smallscreen'] );

	return $styles;
}
add_filter( 'woocommerce_enqueue_styles', 'sgwrd_woocommerce_styles' );
