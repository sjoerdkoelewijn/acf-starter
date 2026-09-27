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

	// Keep the column count as a class, so the CSS can follow it.
	$columns = max( 1, (int) wc_get_loop_prop( 'columns', 4 ) );

	return '<ul class="products product-grid columns-' . esc_attr( (string) $columns ) . '">';
}
add_filter( 'woocommerce_product_loop_start', 'sgwrd_product_loop_start' );

/**
 * Show three products in a row and twelve on a page.
 *
 * @return int
 */
function sgwrd_loop_columns() {

	return 3;
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

/**
 * Take out the WooCommerce parts of the card that the theme prints itself.
 *
 *   - The sale flash. The theme prints it in the image wrapper, next to the
 *     sold out badge. See woocommerce/content-product.php.
 *   - The title. The default is a bare <h2>; the theme prints its own with a
 *     class, so the markup stays flat.
 *   - The star rating above the title. The theme adds it again below the
 *     price, further down.
 *
 * This runs at the start of every card, not when the theme loads. On a normal
 * page WooCommerce adds these hooks before the theme, so removing them early
 * works. But in the block editor a product grid is rendered in a REST
 * request, and there WooCommerce adds its hooks later, when the shortcode
 * runs. A removal at theme load then comes too early, and the card shows two
 * sale badges and two titles. At the start of the card the hooks are always
 * there, and removing a hook twice is harmless.
 */
function sgwrd_loop_card_hooks() {

	remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10 );
	remove_action( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10 );
	remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 );
}
add_action( 'woocommerce_before_shop_loop_item', 'sgwrd_loop_card_hooks', 0 );

/**
 * Print the product title as a heading with a class.
 */
function sgwrd_loop_product_title() {

	echo '<h2 class="product-card__title">' . esc_html( get_the_title() ) . '</h2>';
}
add_action( 'woocommerce_shop_loop_item_title', 'sgwrd_loop_product_title', 10 );

/**
 * Remove the button from a sold out product in a grid.
 *
 * WooCommerce cannot put a sold out product in the cart, so it swaps the
 * button for a "Read more" link to the product page. The card already says
 * "Sold out" on its badge, and the whole card is already a link, so that
 * button only adds noise. A product that is in stock keeps its button.
 *
 * @param string     $html    The button markup.
 * @param WC_Product $product The product.
 * @return string
 */
function sgwrd_loop_hide_sold_out_button( $html, $product ) {

	if ( $product instanceof WC_Product && ! $product->is_in_stock() ) {
		return '';
	}

	return $html;
}
add_filter( 'woocommerce_loop_add_to_cart_link', 'sgwrd_loop_hide_sold_out_button', 10, 2 );

// The star rating, below the price (10) instead of above the title.
add_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 15 );

/*
 * ---------------------------------------------------------------------------
 * The single product page
 * ---------------------------------------------------------------------------
 */

// A breadcrumb is useful in a shop, but the default position is too high.
remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
add_action( 'woocommerce_before_single_product_summary', 'woocommerce_breadcrumb', 4 );

/**
 * Lay out the single product page.
 *
 * Photo on the left, product info on the right, both above the fold on a
 * desktop. The tabs go, and what was in them gets a place of its own:
 *
 *   Right column   title, star rating (or a link to write the first review),
 *                  price, short text, add to cart, then the description and
 *                  the details table
 *   Below the fold the reviews, then the questions, then related products
 *
 * The sale badge sits on the photo. A wrapper round the badge and the gallery
 * makes that possible without overriding a WooCommerce template.
 *
 * This runs when a product starts to render, not when the theme loads, for the
 * same reason as the product card: WooCommerce may add its hooks late.
 */
function sgwrd_single_product_hooks() {

	// One wrapper round the sale badge (10) and the gallery (20).
	add_action( 'woocommerce_before_single_product_summary', 'sgwrd_product_media_open', 5 );
	add_action( 'woocommerce_before_single_product_summary', 'sgwrd_product_media_close', 25 );

	// No tabs. Their content moves to the right column and below the fold.
	remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );

	// Right after the title (5), before the star rating (10) that only shows
	// when there are reviews.
	add_action( 'woocommerce_single_product_summary', 'sgwrd_product_rating_empty', 9 );

	// After add to cart (30), before the SKU and category (40).
	add_action( 'woocommerce_single_product_summary', 'sgwrd_product_description', 35 );
	add_action( 'woocommerce_single_product_summary', 'sgwrd_product_details', 36 );

	// Below the fold, before the upsells (15) and related products (20).
	add_action( 'woocommerce_after_single_product_summary', 'sgwrd_product_sections', 10 );
}
add_action( 'woocommerce_before_single_product', 'sgwrd_single_product_hooks', 0 );

/**
 * Open the wrapper round the photo and its sale badge.
 */
function sgwrd_product_media_open() {

	echo '<div class="product-media">';
}

/**
 * Close the wrapper round the photo and its sale badge.
 */
function sgwrd_product_media_close() {

	echo '</div>';
}

/**
 * Say "Sale", like the product card does, not "Sale!".
 *
 * @return string
 */
function sgwrd_sale_flash() {

	return '<span class="onsale">' . esc_html__( 'Sale', 'acf-starter' ) . '</span>';
}
add_filter( 'woocommerce_sale_flash', 'sgwrd_sale_flash' );

/**
 * Link to the reviews when a product has none yet.
 *
 * WooCommerce prints the star rating next to the title only when there are
 * reviews. Without any, this line takes its place, so the way to the reviews
 * section is always there.
 */
function sgwrd_product_rating_empty() {

	global $product;

	if ( ! $product instanceof WC_Product || ! wc_reviews_enabled() || ! comments_open() ) {
		return;
	}

	if ( $product->get_review_count() > 0 ) {
		return;
	}

	printf(
		'<p class="product-rating product-rating--empty"><a href="#reviews">%s</a></p>',
		esc_html__( 'No reviews yet. Write the first one.', 'acf-starter' )
	);
}

/**
 * Print the long description in the right column.
 */
function sgwrd_product_description() {

	if ( '' === trim( (string) get_the_content() ) ) {
		return;
	}

	echo '<div class="product-description">';
	the_content();
	echo '</div>';
}

/**
 * Print the details table (attributes, weight, size) in the right column.
 *
 * The same test WooCommerce uses for its "Additional information" tab.
 */
function sgwrd_product_details() {

	global $product;

	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$has_size = apply_filters( 'wc_product_enable_dimensions_display', $product->has_weight() || $product->has_dimensions() );

	if ( ! $product->has_attributes() && ! $has_size ) {
		return;
	}

	echo '<div class="product-details">';
	echo '<h2 class="product-details__title">' . esc_html__( 'Details', 'acf-starter' ) . '</h2>';
	wc_display_product_attributes( $product );
	echo '</div>';
}

/**
 * Print the reviews and the questions below the fold.
 *
 * WooCommerce prints the reviews with id="reviews", which the star rating and
 * the "write the first one" link point at.
 */
function sgwrd_product_sections() {

	global $post;

	if ( wc_reviews_enabled() && comments_open() ) {
		echo '<section class="product-section product-section--reviews">';
		comments_template();
		echo '</section>';
	}

	if ( $post && sgwrd_has_faq( $post->ID ) ) {
		echo '<section class="product-section product-section--faq">';
		echo '<h2 class="product-section__title">' . esc_html__( 'Questions', 'acf-starter' ) . '</h2>';
		sgwrd_the_faq( $post->ID );
		echo '</section>';
	}
}

/**
 * Set the number of related products.
 *
 * @param array $args Related product arguments.
 * @return array
 */
function sgwrd_related_products_args( $args ) {

	$args['posts_per_page'] = 3;
	$args['columns']        = 3;

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
