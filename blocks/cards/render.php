<?php
/**
 * Cards block.
 *
 * @param array $block The block settings and attributes.
 *
 * @package ACF_Starter
 */

defined( 'ABSPATH' ) || exit;

$heading = get_field( 'heading' );
$columns = (int) ( get_field( 'columns' ) ?: 3 );
?>

<section <?php echo sgwrd_block_attributes( $block, 'cards', array( 'cards' ) ); ?>>

	<?php if ( $heading ) : ?>
		<h2 class="cards__heading"><?php echo esc_html( $heading ); ?></h2>
	<?php endif; ?>

	<?php if ( have_rows( 'cards' ) ) : ?>

		<div class="cards__grid" style="--columns:<?php echo esc_attr( (string) $columns ); ?>">

			<?php
			while ( have_rows( 'cards' ) ) :
				the_row();

				$image = get_sub_field( 'image' );
				$title = get_sub_field( 'title' );
				$text  = get_sub_field( 'text' );
				$link  = get_sub_field( 'link' );
				?>

				<article class="card">

					<?php if ( $image ) : ?>
						<div class="card__media"><?php sgwrd_the_image( $image, 'sgwrd-card' ); ?></div>
					<?php endif; ?>

					<div class="card__body">

						<?php if ( $title ) : ?>
							<h3 class="card__title">
								<?php if ( ! empty( $link['url'] ) ) : ?>
									<a href="<?php echo esc_url( $link['url'] ); ?>"><?php echo esc_html( $title ); ?></a>
								<?php else : ?>
									<?php echo esc_html( $title ); ?>
								<?php endif; ?>
							</h3>
						<?php endif; ?>

						<?php if ( $text ) : ?>
							<p class="card__text"><?php echo esc_html( $text ); ?></p>
						<?php endif; ?>

					</div>

				</article>

			<?php endwhile; ?>

		</div>

	<?php elseif ( $is_preview ) : ?>

		<p class="block__placeholder"><?php esc_html_e( 'Cards: add a card in the sidebar.', 'acf-starter' ); ?></p>

	<?php endif; ?>

</section>
