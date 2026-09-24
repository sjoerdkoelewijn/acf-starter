<?php
/**
 * The banner and the intro of a category page.
 *
 * The fields sit on the term itself, so an editor fills them on the category
 * edit screen. There is no block to place.
 *
 * @package ACF_Starter
 */

defined( 'ABSPATH' ) || exit;

$sgwrd_term = get_queried_object();

if ( ! $sgwrd_term instanceof WP_Term ) {
	return;
}

$sgwrd_banner = sgwrd_term_field( 'banner', $sgwrd_term );
$sgwrd_intro  = sgwrd_term_field( 'intro', $sgwrd_term );

if ( ! $sgwrd_banner && ! $sgwrd_intro ) {
	return;
}
?>

<header class="term-header<?php echo $sgwrd_banner ? ' term-header--banner' : ''; ?>">

	<?php if ( $sgwrd_banner ) : ?>
		<div class="term-header__media">
			<?php sgwrd_the_image( $sgwrd_banner, 'sgwrd-wide', array( 'alt' => '', 'loading' => 'eager' ) ); ?>
		</div>
	<?php endif; ?>

	<div class="term-header__body">
		<h1 class="term-header__title"><?php echo esc_html( $sgwrd_term->name ); ?></h1>

		<?php if ( $sgwrd_intro ) : ?>
			<div class="term-header__intro"><?php echo wp_kses_post( $sgwrd_intro ); ?></div>
		<?php endif; ?>
	</div>

</header>
