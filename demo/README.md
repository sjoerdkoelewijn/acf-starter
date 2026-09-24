# The demo

One command builds a complete shop on a clean WordPress install: products with
photos, categories with banners, a home page made from the theme blocks, the
menus, the WooCommerce settings and the theme settings.

It is made for Cloudways, but it runs on any host that has WP-CLI.

---

## What you do, and what the script does

| You | The script |
| --- | --- |
| Make the application on Cloudways | Installs and activates WooCommerce |
| Put the theme in `wp-content/themes/` | Activates the theme |
| Put the ACF Pro zip in `demo/plugins/` | Installs and activates ACF Pro |
| Put your photos in `demo/images/` | Imports them into the media library |
| Run one command | Everything below |

The script never deletes content it did not make. Every post, term and
attachment it creates carries the marker `_sgwrd_demo`.

---

## Step by step on Cloudways

### 1. Make the application

In the Cloudways panel: **Add Application → WordPress (clean)**. Take the
newest PHP. A clean install is better than the WooCommerce package, because
the script sets WooCommerce up the way the theme wants it.

### 2. Open a terminal

Use the **SSH Terminal** in the Cloudways panel, or your own client with the
master credentials from **Servers → Master Credentials**.

```bash
cd applications/<your-app>/public_html
wp core version          # this must print a version number
```

### 3. Put the theme in place

```bash
cd wp-content/themes
git clone https://github.com/sjoerdkoelewijn/acf-starter.git
cd ../..
```

Later you update it with `git -C wp-content/themes/acf-starter pull`.

### 4. Add ACF Pro

ACF Pro is paid, so no script can download it. Upload the zip with SFTP to:

```
applications/<your-app>/public_html/wp-content/themes/acf-starter/demo/plugins/
```

Skip this step if ACF Pro is already active on the site.

### 5. Add your photos

Upload them with SFTP to:

```
.../wp-content/themes/acf-starter/demo/images/
```

You do not have to name them. The seeder uses them in the order it finds them.
See `demo/images/README.md` to tie a photo to a product by name.

### 6. Run it

```bash
cd applications/<your-app>/public_html
bash wp-content/themes/acf-starter/demo/provision.sh
```

It takes one to three minutes, most of it making image sizes.

### 7. Clear the Cloudways caches

In the panel: **Application → Application Settings → Purge Varnish**, and
**Object Cache → Purge**. Cloudways caches hard, and a fresh demo looks broken
without this.

---

## Running it again

```bash
# Update the content, keep what is there
bash wp-content/themes/acf-starter/demo/provision.sh

# Throw the old demo away and build it again
bash wp-content/themes/acf-starter/demo/provision.sh --fresh
```

`--fresh` removes only the content with the `_sgwrd_demo` marker.

---

## What you get

**Products.** Twelve products in four categories, from `demo/products.csv`.
Two are on sale, four are featured, one is out of stock. Every fourth product
has its own questions, so you can see the extra product tab.

**Categories.** New in, Clothing, Accessories and Home. Each one has a banner
and an intro. Clothing also has its own questions under the grid.

**Home page.** Built from the theme blocks: cover hero, selling points,
featured products, split hero, newest products, FAQ and a call to action.

**Other pages.** About (content and image, cards), Contact (contact block),
Shipping, Returns, Privacy, Terms, plus the WooCommerce cart, checkout and
account pages.

**Menus.** Primary, footer and legal, all filled and assigned.

**Theme settings.** Company details, two social links, the notice bar and the
three shop notices.

**Shop settings.** Euro, Dutch number format, guest checkout, AJAX add to cart,
tax off, one payment method so the checkout can be walked through, and product
image sizes cropped to 4 by 5 to match the theme card.

---

## Your own products

Edit `demo/products.csv`. The columns are:

| Column | Notes |
| --- | --- |
| `sku` | Must be unique. The script finds a product again by its SKU. |
| `name` | |
| `category` | Category slug. Several slugs split by `\|`. |
| `price` | |
| `sale_price` | Leave it empty for no sale. |
| `featured` | `1` or empty. |
| `stock` | `0` makes the product sold out. |
| `short_description` | Shows under the price. Quote it if it holds a comma. |
| `description` | HTML is allowed. Quote it. |
| `images` | File names from `demo/images/`, split by `\|`. |

Add a category by editing the `$sgwrd_cat_spec` array near the top of
`demo/seed.php`.

---

## If something goes wrong

**"WP-CLI is not on this server"** — Cloudways has it. Check that you are in
`applications/<app>/public_html` and not in your home folder.

**"ACF Pro is missing"** — put the zip in `demo/plugins/`, or activate the
plugin in the admin first.

**The blocks are empty in the editor** — the field groups did not import. Open
any admin page once: the theme reads `/acf-json` on each admin request and a
notice tells you what it read.

**The home page shows block comments as text** — the theme is not active, so
the `acf/*` blocks are not registered. Run `wp theme activate acf-starter`.

**The site looks unstyled** — purge Varnish in the Cloudways panel.

**Products have no photo** — `demo/images/` was empty when you ran it. Add the
photos and run again with `--fresh`.

---

## Before anyone else sees it

The script sets **Discourage search engines** on. That is right for a demo.
Turn it off only when the site goes live:

```bash
wp option update blog_public 1
```

If the demo is reachable from the internet, put it behind a password as well.
Cloudways does that under **Application → Application Settings → Basic Auth**.
