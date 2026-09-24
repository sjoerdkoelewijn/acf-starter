<?php
/**
 * The questions and answers.
 *
 * Used by the FAQ block, by the single product page and by a category page.
 *
 * The accordion is a <details> element. The browser opens and closes it, so
 * the theme needs no JavaScript, and it works with a keyboard and a screen
 * reader out of the box.
 *
 * @param array $args {
 *     @type mixed $source The ACF context. False for the current block, a post
 *                         ID for a product, or a WP_Term for a category.
 * }
 *
 * @package ACF_Starter
 */

defined( 'ABSPATH' ) || exit;

$sgwrd_source = $args['source'] ?? false;

if ( ! function_exists( 'have_rows' ) || ! have_rows( 'faq_items', $sgwrd_source ) ) {
	return;
}

$sgwrd_heading = get_field( 'faq_heading', $sgwrd_source );
?>

<div class="faq__inner">

	<?php if ( $sgwrd_heading ) : ?>
		<h2 class="faq__heading"><?php echo esc_html( $sgwrd_heading ); ?></h2>
	<?php endif; ?>

	<div class="faq__list">

		<?php
		while ( have_rows( 'faq_items', $sgwrd_source ) ) :
			the_row();

			$sgwrd_question = get_sub_field( 'question' );
			$sgwrd_answer   = get_sub_field( 'answer' );

			if ( ! $sgwrd_question ) {
				continue;
			}
			?>

			<details class="faq__item">
				<summary class="faq__question">
					<span class="faq__question-text"><?php echo esc_html( $sgwrd_question ); ?></span>
					<span class="faq__marker" aria-hidden="true"></span>
				</summary>

				<?php if ( $sgwrd_answer ) : ?>
					<div class="faq__answer"><?php echo wp_kses_post( $sgwrd_answer ); ?></div>
				<?php endif; ?>
			</details>

		<?php endwhile; ?>

	</div>

</div>
