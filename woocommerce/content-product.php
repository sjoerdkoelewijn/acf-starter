<?php
/**
 * The product card in a grid.
 *
 * This file overrides woocommerce/templates/content-product.php.
 *
 * The template keeps every WooCommerce hook. It adds two wrappers only:
 * a media wrapper for the image, and a badge wrapper for the sale label.
 * Plugins that hook into the loop keep working.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package ACF_Starter
 * @version 3.6.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

// Stop when the product is not there or is not visible.
if ( empty( $product ) || ! $product->is_visible() ) {
	return;
}
?>
<li <?php wc_product_class( 'product-card', $product ); ?>>

	<?php
	/**
	 * Hook: woocommerce_before_shop_loop_item.
	 *
	 * @hooked woocommerce_template_loop_product_link_open - 10
	 */
	do_action( 'woocommerce_before_shop_loop_item' );
	?>

	<div class="product-card__media">

		<div class="product-card__badges">
			<?php if ( $product->is_on_sale() ) : ?>
				<span class="onsale"><?php esc_html_e( 'Sale', 'acf-starter' ); ?></span>
			<?php endif; ?>

			<?php if ( ! $product->is_in_stock() ) : ?>
				<span class="out-of-stock-badge"><?php esc_html_e( 'Sold out', 'acf-starter' ); ?></span>
			<?php endif; ?>
		</div>

		<?php
		/**
		 * Hook: woocommerce_before_shop_loop_item_title.
		 *
		 * @hooked woocommerce_template_loop_product_thumbnail - 10
		 */
		do_action( 'woocommerce_before_shop_loop_item_title' );
		?>

	</div>

	<?php
	/**
	 * Hook: woocommerce_shop_loop_item_title.
	 *
	 * @hooked sgwrd_loop_product_title - 10
	 */
	do_action( 'woocommerce_shop_loop_item_title' );

	/**
	 * Hook: woocommerce_after_shop_loop_item_title.
	 *
	 * @hooked woocommerce_template_loop_price - 10
	 */
	do_action( 'woocommerce_after_shop_loop_item_title' );

	/**
	 * Hook: woocommerce_after_shop_loop_item.
	 *
	 * @hooked woocommerce_template_loop_product_link_close - 5
	 * @hooked woocommerce_template_loop_add_to_cart - 10
	 */
	do_action( 'woocommerce_after_shop_loop_item' );
	?>

</li>
