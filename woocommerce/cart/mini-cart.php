<?php
/**
 * The mini cart.
 *
 * This file overrides woocommerce/templates/cart/mini-cart.php.
 *
 * The template keeps every WooCommerce hook and filter. It changes the class
 * names only, so the CSS of the theme can reach the parts.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package ACF_Starter
 * @version 9.4.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_mini_cart' );
?>

<?php if ( ! WC()->cart->is_empty() ) : ?>

	<ul class="woocommerce-mini-cart cart_list product_list_widget mini-cart <?php echo esc_attr( $args['list_class'] ?? '' ); ?>">

		<?php
		do_action( 'woocommerce_before_mini_cart_contents' );

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
			$product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );

			if ( ! $_product || ! $_product->exists() || $cart_item['quantity'] <= 0 ) {
				continue;
			}

			if ( ! apply_filters( 'woocommerce_widget_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
				continue;
			}

			$product_name      = apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key );
			$thumbnail         = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key );
			$product_price     = apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key );
			$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
			?>

			<li class="woocommerce-mini-cart-item mini-cart__item <?php echo esc_attr( apply_filters( 'woocommerce_mini_cart_item_class', 'mini_cart_item', $cart_item, $cart_item_key ) ); ?>">

				<div class="mini-cart__thumb">
					<?php if ( $product_permalink ) : ?>
						<a href="<?php echo esc_url( $product_permalink ); ?>" tabindex="-1" aria-hidden="true">
							<?php echo wp_kses_post( $thumbnail ); ?>
						</a>
					<?php else : ?>
						<?php echo wp_kses_post( $thumbnail ); ?>
					<?php endif; ?>
				</div>

				<div class="mini-cart__body">
					<?php if ( $product_permalink ) : ?>
						<a class="mini-cart__name" href="<?php echo esc_url( $product_permalink ); ?>">
							<?php echo wp_kses_post( $product_name ); ?>
						</a>
					<?php else : ?>
						<span class="mini-cart__name"><?php echo wp_kses_post( $product_name ); ?></span>
					<?php endif; ?>

					<?php echo wc_get_formatted_cart_item_data( $cart_item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

					<span class="quantity mini-cart__quantity">
						<?php
						printf(
							'%1$s &times; %2$s',
							esc_html( $cart_item['quantity'] ),
							wp_kses_post( $product_price )
						);
						?>
					</span>
				</div>

				<?php
				echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					'woocommerce_cart_item_remove_link',
					sprintf(
						'<a href="%s" class="remove remove_from_cart_button" aria-label="%s" data-product_id="%s" data-cart_item_key="%s" data-product_sku="%s">&times;</a>',
						esc_url( wc_get_cart_remove_url( $cart_item_key ) ),
						esc_attr(
							sprintf(
								/* translators: %s: product name */
								__( 'Remove %s from the cart', 'acf-starter' ),
								wp_strip_all_tags( $product_name )
							)
						),
						esc_attr( $product_id ),
						esc_attr( $cart_item_key ),
						esc_attr( $_product->get_sku() )
					),
					$cart_item_key
				);
				?>

			</li>

			<?php
		}

		do_action( 'woocommerce_mini_cart_contents' );
		?>

	</ul>

	<p class="woocommerce-mini-cart__total total mini-cart__total">
		<?php
		/**
		 * Hook: woocommerce_widget_shopping_cart_total.
		 *
		 * @hooked woocommerce_widget_shopping_cart_subtotal - 10
		 */
		do_action( 'woocommerce_widget_shopping_cart_total' );
		?>
	</p>

	<?php do_action( 'woocommerce_widget_shopping_cart_before_buttons' ); ?>

	<p class="woocommerce-mini-cart__buttons buttons mini-cart__actions">
		<?php do_action( 'woocommerce_widget_shopping_cart_buttons' ); ?>
	</p>

<?php else : ?>

	<p class="woocommerce-mini-cart__empty-message mini-cart__empty">
		<?php esc_html_e( 'The cart is empty.', 'acf-starter' ); ?>
	</p>

<?php endif; ?>

<?php do_action( 'woocommerce_after_mini_cart' ); ?>
