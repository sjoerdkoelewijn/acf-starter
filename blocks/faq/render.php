<?php
/**
 * FAQ block.
 *
 * The markup comes from template-parts/faq.php, which the product page and
 * the category page use as well. One set of fields, one piece of markup,
 * three places.
 *
 * @param array $block      The block settings and attributes.
 * @param bool  $is_preview True while the block shows in the editor.
 *
 * @package ACF_Starter
 */

defined( 'ABSPATH' ) || exit;

if ( ! have_rows( 'faq_items' ) ) {

	if ( $is_preview ) {
		echo '<p class="block__placeholder">' . esc_html__( 'FAQ: add a question in the sidebar.', 'acf-starter' ) . '</p>';
	}

	return;
}
?>

<section <?php echo sgwrd_block_attributes( $block, 'faq', array( 'faq' ) ); ?>>
	<?php sgwrd_the_faq(); ?>
</section>
