<?php
/**
 * The search form.
 *
 * @package ACF_Starter
 */

defined( 'ABSPATH' ) || exit;

$sgwrd_search_id = wp_unique_id( 'search-' );
?>
<form role="search" method="get" class="search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="<?php echo esc_attr( $sgwrd_search_id ); ?>">
		<?php esc_html_e( 'Search', 'acf-starter' ); ?>
	</label>

	<input
		type="search"
		id="<?php echo esc_attr( $sgwrd_search_id ); ?>"
		class="search-form__input"
		name="s"
		value="<?php echo esc_attr( get_search_query() ); ?>"
		placeholder="<?php esc_attr_e( 'Search', 'acf-starter' ); ?>"
		required
	>

	<button type="submit" class="search-form__submit button button--primary">
		<?php esc_html_e( 'Search', 'acf-starter' ); ?>
	</button>
</form>
