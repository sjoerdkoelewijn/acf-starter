<?php
/**
 * Product grid block.
 *
 * The block builds a WooCommerce [products] shortcode from the fields. That
 * way the products use the same card, the same hooks and the same CSS as the
 * shop pages.
 *
 * @param array $block The block settings and attributes.
 *
 * @package ACF_Starter
 */

defined( 'ABSPATH' ) || exit;

if ( ! sgwrd_has_woocommerce() ) {

	if ( $is_preview ) {
		echo '<p class="block__placeholder">' . esc_html__( 'Product grid: WooCommerce is not active.', 'acf-starter' ) . '</p>';
	}

	return;
}

$heading  = get_field( 'heading' );
$source   = get_field( 'source' ) ?: 'recent';
$columns  = (int) ( get_field( 'columns' ) ?: 4 );
$count    = (int) ( get_field( 'count' ) ?: 4 );
$category = get_field( 'product_category' );
$link     = get_field( 'link' );

$attributes = array(
	'limit'    => max( 1, min( 24, $count ) ),
	'columns'  => max( 1, min( 6, $columns ) ),
	'paginate' => 'false',
);

switch ( $source ) {
	case 'selected':
		$ids = array_filter( array_map( 'absint', (array) get_field( 'products' ) ) );

		if ( ! $ids ) {
			if ( $is_preview ) {
				echo '<p class="block__placeholder">' . esc_html__( 'Product grid: choose the products in the sidebar.', 'acf-starter' ) . '</p>';
			}

			return;
		}

		$attributes['ids']     = implode( ',', $ids );
		$attributes['limit']   = count( $ids );
		$attributes['orderby'] = 'post__in';
		break;

	case 'featured':
		$attributes['visibility'] = 'featured';
		break;

	case 'sale':
		$attributes['on_sale'] = 'true';
		break;

	case 'best-selling':
		$attributes['best_selling'] = 'true';
		break;

	case 'category':
		$terms = array();

		foreach ( (array) $category as $term ) {
			$term_object = is_object( $term ) ? $term : get_term( (int) $term, 'product_cat' );

			if ( $term_object instanceof WP_Term ) {
				$terms[] = $term_object->slug;
			}
		}

		if ( $terms ) {
			$attributes['category'] = implode( ',', $terms );
		}
		break;

	default:
		$attributes['orderby'] = 'date';
		$attributes['order']   = 'DESC';
}

$shortcode = '[products';

foreach ( $attributes as $key => $value ) {
	$shortcode .= sprintf( ' %s="%s"', $key, esc_attr( $value ) );
}

$shortcode .= ']';
?>

<section <?php echo sgwrd_block_attributes( $block, 'product-grid', array( 'product-feed' ) ); ?>>

	<?php if ( $heading || ! empty( $link['url'] ) ) : ?>
		<header class="product-feed__header">

			<?php if ( $heading ) : ?>
				<h2 class="product-feed__heading"><?php echo esc_html( $heading ); ?></h2>
			<?php endif; ?>

			<?php sgwrd_the_link( $link, 'text-link' ); ?>

		</header>
	<?php endif; ?>

	<?php echo do_shortcode( $shortcode ); ?>

</section>
