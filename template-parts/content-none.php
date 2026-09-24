<?php
/**
 * The message for a list with no results.
 *
 * @package ACF_Starter
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="message">

	<h2 class="message__title"><?php esc_html_e( 'Nothing found', 'acf-starter' ); ?></h2>

	<p class="message__text">
		<?php esc_html_e( 'There is no content here. Try a different search.', 'acf-starter' ); ?>
	</p>

	<?php get_search_form(); ?>

</div>
