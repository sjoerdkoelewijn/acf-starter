<?php
/**
 * Environment safety: a coloured admin bar badge and a mail block.
 *
 * WordPress reads the environment from WP_ENVIRONMENT_TYPE in wp-config.php:
 *
 *     define( 'WP_ENVIRONMENT_TYPE', 'staging' );
 *
 * The values are production (the default), staging, development and local.
 * On every value except production this module:
 *
 *   1. shows a coloured badge in the admin bar, in the admin and on the site,
 *   2. stops all mail that goes through wp_mail(), or sends it all to one
 *      address when SGWRD_STAGING_MAIL_TO is set in wp-config.php:
 *
 *          define( 'SGWRD_STAGING_MAIL_TO', 'dev@example.com' );
 *
 * A second guard does not depend on wp-config.php. Give the production domain
 * to the theme, for example in the child theme:
 *
 *     add_filter( 'sgwrd_production_host', fn() => 'www.example.com' );
 *
 * When the site says "production" but runs on a different domain, the theme
 * treats it as staging. So a staging copy with the production wp-config.php
 * still blocks its mail.
 *
 * @package ACF_Starter
 */

defined( 'ABSPATH' ) || exit;

/**
 * Get the environment type, with the domain guard applied.
 *
 * @return string production, staging, development or local.
 */
function sgwrd_environment() {

	$type = wp_get_environment_type();

	if ( 'production' === $type ) {
		$production_host = apply_filters(
			'sgwrd_production_host',
			defined( 'SGWRD_PRODUCTION_HOST' ) ? SGWRD_PRODUCTION_HOST : ''
		);
		$current_host    = (string) wp_parse_url( home_url(), PHP_URL_HOST );

		if ( $production_host && strtolower( $current_host ) !== strtolower( $production_host ) ) {
			$type = 'staging';
		}
	}

	return apply_filters( 'sgwrd_environment', $type );
}

/**
 * Is this the live site?
 *
 * @return bool
 */
function sgwrd_is_production() {
	return 'production' === sgwrd_environment();
}

/**
 * The address that gets all mail on a non-production site, or an empty string.
 *
 * @return string
 */
function sgwrd_staging_mail_to() {

	$to = defined( 'SGWRD_STAGING_MAIL_TO' ) ? SGWRD_STAGING_MAIL_TO : '';
	$to = apply_filters( 'sgwrd_staging_mail_to', $to );

	return is_email( $to ) ? $to : '';
}

/**
 * Must the theme hold back the mail on this site?
 *
 * A child theme or a plugin can say no with the sgwrd_mail_guard filter.
 *
 * @return bool
 */
function sgwrd_mail_guard_active() {
	return (bool) apply_filters( 'sgwrd_mail_guard', ! sgwrd_is_production() );
}

/* -------------------------------------------------------------------------
 * The mail guard
 * ---------------------------------------------------------------------- */

/**
 * Stop the mail before WordPress sends it.
 *
 * With a redirect address the mail goes on, and sgwrd_redirect_mail() changes
 * the recipients. Without one, the mail stops here and a line goes to the
 * PHP error log. The function returns true, so WordPress and WooCommerce see a
 * sent mail and show no error.
 *
 * @param null|bool $return Short circuit value. Null lets the mail go on.
 * @param array     $atts   The wp_mail() arguments.
 * @return null|bool
 */
function sgwrd_block_mail( $return, $atts ) {

	if ( null !== $return || ! sgwrd_mail_guard_active() || sgwrd_staging_mail_to() ) {
		return $return;
	}

	$to = is_array( $atts['to'] ) ? implode( ', ', $atts['to'] ) : (string) $atts['to'];

	error_log(
		sprintf(
			'[%s] Mail blocked. To: %s. Subject: %s',
			strtoupper( sgwrd_environment() ),
			$to,
			(string) $atts['subject']
		)
	);

	return true;
}
add_filter( 'pre_wp_mail', 'sgwrd_block_mail', 999, 2 );

/**
 * Send all mail to the redirect address.
 *
 * The subject gets the environment and the first recipient, so you can see
 * who the mail was for. Cc and Bcc headers are removed, so nobody else gets a
 * copy.
 *
 * @param array $atts The wp_mail() arguments.
 * @return array
 */
function sgwrd_redirect_mail( $atts ) {

	$redirect = sgwrd_staging_mail_to();

	if ( ! $redirect || ! sgwrd_mail_guard_active() ) {
		return $atts;
	}

	$to = is_array( $atts['to'] ) ? implode( ', ', $atts['to'] ) : (string) $atts['to'];

	$atts['to']      = $redirect;
	$atts['subject'] = sprintf( '[%s → %s] %s', strtoupper( sgwrd_environment() ), $to, $atts['subject'] );

	$headers = $atts['headers'];
	if ( ! is_array( $headers ) ) {
		$headers = explode( "\n", str_replace( "\r\n", "\n", (string) $headers ) );
	}

	$atts['headers'] = array_values(
		array_filter(
			$headers,
			static function ( $header ) {
				return ! preg_match( '/^\s*b?cc\s*:/i', (string) $header );
			}
		)
	);

	return $atts;
}
add_filter( 'wp_mail', 'sgwrd_redirect_mail', 999 );

/* -------------------------------------------------------------------------
 * The admin bar badge
 * ---------------------------------------------------------------------- */

/**
 * Add the environment badge to the admin bar.
 *
 * @param WP_Admin_Bar $bar The admin bar.
 */
function sgwrd_environment_badge( $bar ) {

	if ( sgwrd_is_production() ) {
		return;
	}

	$environment = sgwrd_environment();

	if ( ! sgwrd_mail_guard_active() ) {
		$mail = __( 'mail is sent', 'acf-starter' );
	} elseif ( sgwrd_staging_mail_to() ) {
		/* translators: %s: the mail address that gets all mail. */
		$mail = sprintf( __( 'mail to %s', 'acf-starter' ), sgwrd_staging_mail_to() );
	} else {
		$mail = __( 'mail blocked', 'acf-starter' );
	}

	$bar->add_node(
		array(
			'id'     => 'sgwrd-environment',
			'parent' => 'top-secondary',
			'title'  => esc_html( strtoupper( $environment ) . ' · ' . $mail ),
			'meta'   => array(
				'class' => 'sgwrd-environment sgwrd-environment--' . sanitize_html_class( $environment ),
				'title' => __( 'This is not the live site.', 'acf-starter' ),
			),
		)
	);
}
add_action( 'admin_bar_menu', 'sgwrd_environment_badge', 0 );

/**
 * Colour the badge. The admin bar stylesheet loads in the admin and on the
 * site, so the rules go inline on it.
 */
function sgwrd_environment_badge_style() {

	if ( sgwrd_is_production() || ! is_admin_bar_showing() ) {
		return;
	}

	$css = '#wpadminbar .sgwrd-environment > .ab-item{background:#c2410c;color:#fff;font-weight:600;letter-spacing:.02em}'
		. '#wpadminbar .sgwrd-environment--development > .ab-item,'
		. '#wpadminbar .sgwrd-environment--local > .ab-item{background:#1d4ed8}'
		. '#wpadminbar .sgwrd-environment > .ab-item:hover{color:#fff}';

	wp_add_inline_style( 'admin-bar', $css );
}
add_action( 'wp_enqueue_scripts', 'sgwrd_environment_badge_style' );
add_action( 'admin_enqueue_scripts', 'sgwrd_environment_badge_style' );
