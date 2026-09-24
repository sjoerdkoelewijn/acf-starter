<?php
/**
 * Selling points block.
 *
 * @param array $block The block settings and attributes.
 *
 * @package ACF_Starter
 */

defined( 'ABSPATH' ) || exit;

$heading = get_field( 'heading' );
?>

<section <?php echo sgwrd_block_attributes( $block, 'usp', array( 'usp' ) ); ?>>

	<?php if ( $heading ) : ?>
		<h2 class="usp__heading"><?php echo esc_html( $heading ); ?></h2>
	<?php endif; ?>

	<?php if ( have_rows( 'items' ) ) : ?>

		<ul class="usp__list">

			<?php
			while ( have_rows( 'items' ) ) :
				the_row();

				$icon  = get_sub_field( 'icon' );
				$title = get_sub_field( 'title' );
				$text  = get_sub_field( 'text' );
				?>

				<li class="usp__item">

					<?php if ( $icon ) : ?>
						<span class="usp__icon"><?php sgwrd_the_image( $icon, 'thumbnail', array( 'alt' => '' ) ); ?></span>
					<?php endif; ?>

					<?php if ( $title ) : ?>
						<h3 class="usp__title"><?php echo esc_html( $title ); ?></h3>
					<?php endif; ?>

					<?php if ( $text ) : ?>
						<p class="usp__text"><?php echo esc_html( $text ); ?></p>
					<?php endif; ?>

				</li>

			<?php endwhile; ?>

		</ul>

	<?php elseif ( $is_preview ) : ?>

		<p class="block__placeholder"><?php esc_html_e( 'Selling points: add an item in the sidebar.', 'acf-starter' ); ?></p>

	<?php endif; ?>

</section>
