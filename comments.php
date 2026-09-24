<?php
/**
 * The comments area.
 *
 * @package ACF_Starter
 */

defined( 'ABSPATH' ) || exit;

// Do not show anything while a comment is in the moderation queue.
if ( post_password_required() ) {
	return;
}
?>

<section id="comments" class="comments">

	<?php if ( have_comments() ) : ?>

		<h2 class="comments__title">
			<?php
			printf(
				/* translators: %s: number of comments */
				esc_html( _n( '%s comment', '%s comments', get_comments_number(), 'acf-starter' ) ),
				esc_html( number_format_i18n( get_comments_number() ) )
			);
			?>
		</h2>

		<ol class="comments__list">
			<?php
			wp_list_comments(
				array(
					'style'       => 'ol',
					'short_ping'  => true,
					'avatar_size' => 48,
				)
			);
			?>
		</ol>

		<?php
		the_comments_pagination(
			array(
				'prev_text' => esc_html__( 'Previous', 'acf-starter' ),
				'next_text' => esc_html__( 'Next', 'acf-starter' ),
			)
		);
		?>

	<?php endif; ?>

	<?php if ( ! comments_open() && get_comments_number() ) : ?>
		<p class="comments__closed"><?php esc_html_e( 'Comments are closed.', 'acf-starter' ); ?></p>
	<?php endif; ?>

	<?php
	comment_form(
		array(
			'class_submit' => 'button button--primary',
		)
	);
	?>

</section>
