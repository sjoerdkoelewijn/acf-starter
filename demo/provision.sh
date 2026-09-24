#!/usr/bin/env bash
#
# Build the ACF Starter demo on a WordPress install.
#
# Run it from the WordPress root, over SSH:
#
#   bash wp-content/themes/acf-starter/demo/provision.sh
#   bash wp-content/themes/acf-starter/demo/provision.sh --fresh
#
# --fresh removes the demo content of an earlier run first. Without it the
# script updates what is there, so you can run it again at any time.
#
# The script changes settings and adds content. It never deletes content that
# it did not make itself.

set -euo pipefail

DEMO_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
THEME_DIR="$(dirname "$DEMO_DIR")"
THEME_SLUG="$(basename "$THEME_DIR")"

FRESH=""
[[ "${1:-}" == "--fresh" ]] && FRESH="fresh"

say()  { printf '\n\033[1m%s\033[0m\n' "$1"; }
ok()   { printf '  \033[32mok\033[0m   %s\n' "$1"; }
warn() { printf '  \033[33mnote\033[0m %s\n' "$1"; }
die()  { printf '\n\033[31mstopped:\033[0m %s\n\n' "$1" >&2; exit 1; }

# ---------------------------------------------------------------------------
say "Checking"
# ---------------------------------------------------------------------------

command -v wp >/dev/null 2>&1 || die "WP-CLI is not on this server. Cloudways has it as 'wp'."

wp core is-installed >/dev/null 2>&1 || die "No WordPress here. Run the script from the WordPress root."

ok "WP-CLI $(wp --version | awk '{print $2}')"
ok "WordPress $(wp core version)"
ok "theme folder: $THEME_SLUG"

# ---------------------------------------------------------------------------
say "Plugins"
# ---------------------------------------------------------------------------

# WooCommerce comes from wordpress.org.
if wp plugin is-installed woocommerce >/dev/null 2>&1; then
	ok "WooCommerce is installed"
else
	wp plugin install woocommerce --quiet
	ok "WooCommerce installed"
fi

wp plugin activate woocommerce --quiet
ok "WooCommerce active"

# ACF Pro is a paid plugin. It cannot come from wordpress.org.
if wp plugin is-active advanced-custom-fields-pro >/dev/null 2>&1; then
	ok "ACF Pro is active"
elif wp plugin is-installed advanced-custom-fields-pro >/dev/null 2>&1; then
	wp plugin activate advanced-custom-fields-pro --quiet
	ok "ACF Pro active"
elif compgen -G "$DEMO_DIR/plugins/*.zip" >/dev/null; then
	for zip in "$DEMO_DIR"/plugins/*.zip; do
		wp plugin install "$zip" --force --quiet
		ok "installed $(basename "$zip")"
	done
	wp plugin activate advanced-custom-fields-pro --quiet
	ok "ACF Pro active"
else
	die "ACF Pro is missing.
  It is a paid plugin, so this script cannot download it.
  Put the zip in demo/plugins/ and run the script again,
  or install and activate it from the WordPress admin first."
fi

# ---------------------------------------------------------------------------
say "Theme"
# ---------------------------------------------------------------------------

wp theme activate "$THEME_SLUG" --quiet
ok "$THEME_SLUG active"

# The theme reads its field groups from /acf-json on the next admin request.
wp eval 'if ( function_exists( "sgwrd_acf_sync_field_groups" ) ) { sgwrd_acf_sync_field_groups(); }' --quiet || true
ok "field groups read from /acf-json"

# ---------------------------------------------------------------------------
say "WordPress settings"
# ---------------------------------------------------------------------------

wp option update blogname "ACF Starter Demo" --quiet
wp option update blogdescription "A minimal shop, built on ACF Pro and WooCommerce" --quiet
wp rewrite structure '/%postname%/' --quiet
wp option update timezone_string "Europe/Amsterdam" --quiet
wp option update date_format "j F Y" --quiet
wp option update start_of_week 1 --quiet
wp option update default_comment_status closed --quiet
wp option update default_ping_status closed --quiet
wp option update thumbnail_crop 1 --quiet

# A demo site does not belong in a search engine.
wp option update blog_public 0 --quiet
ok "permalinks, title, timezone"
warn "search engines are discouraged. Set blog_public to 1 if this goes live."

# ---------------------------------------------------------------------------
say "WooCommerce settings"
# ---------------------------------------------------------------------------

wp option update woocommerce_store_address "Keizersgracht 1" --quiet
wp option update woocommerce_store_city "Amsterdam" --quiet
wp option update woocommerce_store_postcode "1015 CD" --quiet
wp option update woocommerce_default_country "NL:NH" --quiet
wp option update woocommerce_currency "EUR" --quiet
wp option update woocommerce_currency_pos "left_space" --quiet
wp option update woocommerce_price_thousand_sep "." --quiet
wp option update woocommerce_price_decimal_sep "," --quiet
wp option update woocommerce_weight_unit "kg" --quiet
wp option update woocommerce_dimension_unit "cm" --quiet
wp option update woocommerce_enable_guest_checkout "yes" --quiet
wp option update woocommerce_cart_redirect_after_add "no" --quiet
wp option update woocommerce_enable_ajax_add_to_cart "yes" --quiet
wp option update woocommerce_calc_taxes "no" --quiet

# Image sizes that match the theme card, which is 4 by 5.
wp option update woocommerce_thumbnail_image_width 600 --quiet
wp option update woocommerce_single_image_width 1200 --quiet
wp option update woocommerce_thumbnail_cropping "custom" --quiet
wp option update woocommerce_thumbnail_cropping_custom_width 4 --quiet
wp option update woocommerce_thumbnail_cropping_custom_height 5 --quiet

# A payment method, so the checkout can be walked through.
wp option patch update woocommerce_cod_settings enabled yes --quiet 2>/dev/null || \
	wp option update woocommerce_cod_settings '{"enabled":"yes","title":"Pay on delivery"}' --format=json --quiet
ok "currency, checkout, image sizes, one payment method"

# The cart, checkout and account pages.
wp wc tool run install_pages --user=1 --quiet >/dev/null 2>&1 || warn "could not run the WooCommerce page tool; check the shop pages by hand"
ok "shop pages"

# ---------------------------------------------------------------------------
say "Demo content"
# ---------------------------------------------------------------------------

wp eval-file "$DEMO_DIR/seed.php" $FRESH

# ---------------------------------------------------------------------------
say "Finishing"
# ---------------------------------------------------------------------------

wp rewrite flush --quiet
wp cache flush --quiet 2>/dev/null || true
wp transient delete --all --quiet 2>/dev/null || true
ok "caches cleared"

printf '\n\033[32mDone.\033[0m  %s\n\n' "$(wp option get home)"
printf 'Next, by hand:\n'
printf '  - Upload a logo at Appearance > Customize > Site Identity.\n'
printf '  - On Cloudways, purge Varnish and the object cache.\n'
printf '  - Put the demo behind a password if it is reachable from the internet.\n\n'
