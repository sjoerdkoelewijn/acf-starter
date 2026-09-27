# ACF Starter

A minimal classic WordPress starter theme for **Advanced Custom Fields Pro** and
**WooCommerce**.

The theme gives you structure. It does not give you a design. Put the design in
a child theme, or in the CSS of this theme when it is the only theme on the
project. The backend stays small on purpose: there is no colour picker, no font
menu and no page builder. Those choices belong in the code.

---

## Requirements

| Item | Version |
| --- | --- |
| WordPress | 6.5 or higher |
| PHP | 8.0 or higher |
| Advanced Custom Fields **Pro** | 6.0 or higher |
| WooCommerce | 8.0 or higher (optional) |

There is no build step. The CSS and the JavaScript are plain files. You edit
them and you reload the page.

---

## What the theme does

* **Classic templates.** `header.php`, `footer.php` and the other PHP templates.
  There is no site editor, so a client cannot break the layout.
* **A locked editor.** `theme.json` turns off the colour, font, spacing and
  border controls. Only a small list of blocks is available.
* **ACF blocks from a folder.** Add a folder in `/blocks` with a `block.json`
  and a `render.php`, and the block is there. You register nothing by hand.
* **Field groups in git, edited in the backend.** Every field group is a JSON
  file in `/acf-json`. ACF writes the file when you save, and the theme reads a
  newer file back in by itself. You never click **Sync**.
* **WooCommerce with two template overrides only.** The rest is hooks and CSS,
  so a WooCommerce update cannot break the checkout.
* **A small admin.** No dashboard widgets, no file editor, no block widgets.

---

## Folder structure

```
acf-starter/
├── acf-json/                ACF field groups, in sync with the backend both ways.
├── assets/
│   ├── css/
│   │   ├── style.css        The front end.
│   │   ├── woocommerce.css  The shop.
│   │   ├── editor.css       The block editor canvas.
│   │   └── admin.css        The WordPress admin.
│   └── js/
│       └── theme.js         The menu button and the scroll bar width.
├── demo/                    One command builds a full demo shop. See demo/README.md.
├── blocks/                  One folder for each ACF block.
│   └── <slug>/
│       ├── block.json       The block definition.
│       └── render.php       The block markup.
├── functions/               One file for each concern. See the table below.
├── migrations/              Content migrations. See "Content migrations".
├── template-parts/          Small pieces that the templates share.
├── woocommerce/             The two WooCommerce template overrides.
├── functions.php            Loads the files in /functions. No logic here.
├── theme.json               The editor settings and the design tokens.
└── style.css                The theme header. It holds no styles.
```

### The files in /functions

| File | What it does |
| --- | --- |
| `constants.php` | Paths, the version and the cache busting helper. |
| `environment.php` | The staging badge and the mail guard. It does nothing on production. |
| `setup.php` | Theme support, menus, image sizes, the footer widget area. |
| `assets.php` | Loads the styles and the scripts. |
| `cleanup.php` | Removes the default WordPress output the theme does not use. |
| `editor.php` | The block allow list, the block category and the block styles. |
| `admin.php` | Makes the WordPress admin smaller. |
| `acf.php` | ACF JSON sync, the block loader and the options page. |
| `migrations.php` | Content migrations: the `wp sgwrd migrate` command and its helpers. |
| `content-sync.php` | Content from staging to production: the `wp sgwrd content` command. |
| `template-tags.php` | Small helpers for the templates and the blocks. |
| `shortcodes.php` | `[year]` and `[site_name]`. |
| `woocommerce.php` | The shop. The file stops at the top when WooCommerce is off. |

---

## The block allow list

The editor shows these blocks and nothing else:

**Text** — paragraph, heading, list, quote, separator, table
**Layout** — group, columns, buttons, spacer
**Theme blocks** — every block in `/blocks`

To change the list, add a filter in the child theme. Do not edit
`functions/editor.php`.

```php
add_filter( 'sgwrd_allowed_blocks', function ( $blocks ) {
	$blocks[] = 'core/image';
	$blocks[] = 'core/embed';

	return $blocks;
} );
```

On the WooCommerce cart, checkout, account and shop pages the theme allows
every block. Those pages are built from WooCommerce blocks, and the list of
inner blocks changes with each WooCommerce release.

---

## The theme blocks

| Block | Slug | What it is for |
| --- | --- | --- |
| Hero | `acf/hero` | The introduction at the top of a page. |
| Split hero | `acf/hero-split` | Two cover panels next to each other. |
| FAQ | `acf/faq` | Questions and answers that open and close. |
| Content and image | `acf/content-image` | Text next to an image. |
| Selling points | `acf/usp` | A row of reasons to buy. |
| Call to action | `acf/cta` | One heading, one line, one or two buttons. |
| Cards | `acf/cards` | A grid of cards with an image and a link. |
| Contact | `acf/contact` | Contact details next to a form shortcode. |
| Recent posts | `acf/recent-posts` | The newest posts. |
| Product grid | `acf/product-grid` | WooCommerce products in a grid. |

### Editing a block

The blocks are **ACF Blocks V3**. The editor shows each block as a live
preview, so you see the page as it will look. The **Edit block** button in the
block toolbar opens the fields in the Expanded Editor, a large window with room
for repeaters. ACF shows that button as a small pencil; the theme turns it into
a dark button with its label, see `assets/css/admin.css`. The fields also show in the sidebar when the block is selected.

In the preview, links, buttons and form fields do nothing. A click selects the
block, so you never leave the page or fill a cart by accident while you edit.
The front end is not affected. See the end of `assets/css/editor.css`.

This is set in the `"acf"` key of each `block.json`:

```json
"acf": {
  "blockVersion": 3,
  "renderPreview": true,
  "renderTemplate": "render.php"
}
```

Set `"renderPreview": false` to show a block as a plain placeholder with an
**Edit block** button instead of a preview.

Why V3 is written down: ACF PRO 6.8.9 made V3 the default on WordPress 7.1,
and the old V2 "edit mode", with the form in the canvas, does not exist in
V3. The theme states the version, so it does not change under you with an
ACF update.

### The hero

The hero has two layouts. **Cover** puts the media behind the text, with a dark
overlay between the two. **Side by side** puts the image next to the text.

In the cover layout the background is an image or a video:

* The **video** is an MP4 or a WebM file with no sound. It plays by itself, in
  a loop, with no controls. Keep it under 5 MB.
* The **image** is the poster. It shows first, on a slow connection, and
  instead of the video when the visitor asked for less motion
  (`prefers-reduced-motion`). The theme needs no JavaScript for this.
* The **overlay** is a slider from 0 to 100. The block writes it to the CSS as
  the custom property `--hero-overlay`. No colour is ever written into the
  markup.

The **split hero** works the same way, but with two panels. Each panel has its
own image, its own overlay, its own title and summary, and one button. The
panels stack on a telephone.

### The FAQ

One set of fields serves three places, because the field group has four
location rules:

| Where | How it appears |
| --- | --- |
| A page | The `acf/faq` block, full width |
| A product | An extra **Questions** tab next to Description |
| A product category | Under the product grid |
| A blog category | Under the post list |

A product page and a category page are not built from blocks, so a block cannot
reach them. The fields sit on the product and on the term instead, and
`functions/woocommerce.php` prints them.

The accordion is a native `<details>` element. The browser opens and closes it,
so it works with a keyboard and a screen reader, and the theme ships no
JavaScript for it.

Use `sgwrd_the_faq( $source )` to print the questions anywhere else. Pass
nothing for a block, a post ID for a product, or a `WP_Term` for a category.

### The product grid

Six sources: newest, featured, on sale, best selling, from a category, and
**hand picked**. Hand picked uses a relationship field, and the grid keeps the
order you drag the products into.

The block builds a WooCommerce `[products]` shortcode from the fields, so the
products use the same card, the same hooks and the same CSS as the shop pages.

### How to add a block

1. Make the folder `blocks/my-block/`.
2. Add `block.json`. Copy one from another block and change the name, the title
   and the icon. Keep `"category": "sgwrd-blocks"`.
3. Add `render.php`. Start it with
   `<section <?php echo sgwrd_block_attributes( $block, 'my-block' ); ?>>`.
4. Open **Custom Fields → Field Groups** and make a group with the location
   rule **Block is equal to My Block**. ACF writes the JSON file into
   `/acf-json` for you when you save.
5. Add the CSS in `assets/css/style.css`.

The block is now in the editor. You do not register it anywhere.

---

## Field groups and ACF

You edit field groups in the backend, on the normal **Custom Fields** screen.
The theme keeps that screen and the `/acf-json` folder in step, in both
directions. You never click the ACF **Sync** button.

### How the two directions work

**Backend → code.** ACF writes a JSON file into `/acf-json` each time you save
a field group. This is the ACF feature called Local JSON.
`sgwrd_acf_json_save_point()` points it at the theme. Commit the file with the
rest of your change.

**Code → backend.** `sgwrd_acf_sync_field_groups()` runs on each admin page
load and compares each JSON file with the database:

| What it finds | What it does |
| --- | --- |
| The JSON file is there, the database record is not | Import the group |
| The JSON file is newer than the database record | Import the group |
| The database record is newer | Do nothing. You are editing it now. |

So after `git pull` or a deploy, the field groups are simply there. A notice at
the top of the admin says which groups came in.

The comparison uses the `modified` timestamp that ACF writes into each JSON
file. A database record can never be overwritten by an older file.

### Two people at the same time

The rule "the newer one wins" is per field group, not per field. If two people
change the **same** field group at the same time, the second save overwrites
the first, and git shows you the conflict in the JSON file. Treat a field group
like a source file: one person at a time.

### Deleting a field group

Delete it in the backend. ACF removes the JSON file from `/acf-json` as well.
Commit that deletion.

### Turning the automatic sync off

```php
add_filter( 'sgwrd_acf_auto_sync', '__return_false' );
```

ACF then shows its own **Sync available** tab again, and you click the button
by hand.

### Hiding the ACF menu on a live site

The theme no longer hides it. Use the ACF filter if you want it hidden:

```php
add_filter( 'acf/settings/show_admin', '__return_false' );
```

Be careful with this on a site where you also want the automatic sync: the sync
keeps working, but nobody can see or edit the field groups any more.

### Fields that are not blocks

Two field groups sit outside the block editor:

| Group | Where | What it does |
| --- | --- | --- |
| Category header | Product category, blog category | A banner image and an intro text above the list. The title of the category header replaces the normal one, so it is never printed twice. |
| FAQ | Product, product category, blog category | See **The FAQ** above. |

### The theme settings page

There is one options page: **Theme settings**. It has four tabs: the company
details, the social links, the notice bar above the header, and three short
shop notices. Read a value like this:

```php
echo esc_html( sgwrd_option( 'company_name' ) );
```

Keep this page for content. A colour, a font or a spacing value does not belong
on it.

---

## WooCommerce

The theme overrides two templates:

| File | Why |
| --- | --- |
| `woocommerce/content-product.php` | The product card needs a media wrapper and a badge wrapper. |
| `woocommerce/cart/mini-cart.php` | The mini cart needs its own class names. |

Both files keep every WooCommerce hook and filter, so plugins keep working.

The product page is laid out with hooks, not a template override: the photo
on the left with the sale badge on it, the product info on the right above the
fold, then the reviews and the questions as sections below. The tabs are gone;
the description and the details table sit in the right column. The star rating
next to the title links down to the reviews, and a product with none shows a
link to write the first one. See `sgwrd_single_product_hooks()` in
`functions/woocommerce.php`.

The cart page, the checkout page and the account page use the WooCommerce
templates without a change. The theme gives them a layout with CSS only. A
WooCommerce update can therefore never break the checkout.

The theme removes three WooCommerce stylesheets and loads
`assets/css/woocommerce.css` instead. To go back to the WooCommerce design:

```php
add_filter( 'sgwrd_remove_woocommerce_styles', '__return_false' );
```

The block stylesheet `wc-blocks-style` stays. The block cart and the block
checkout need it.

---

## Design tokens

Every colour, space and size is a custom property. `theme.json` makes the
WordPress variables. `assets/css/style.css` gives them a short name.

| Token | Source in theme.json |
| --- | --- |
| `--c-base`, `--c-text`, `--c-muted`, `--c-surface`, `--c-line`, `--c-accent`, `--c-sale` | `settings.color.palette` |
| `--s-1` to `--s-5` | `settings.spacing.spacingSizes` |
| `--w-content`, `--w-wide` | `settings.layout` |
| `--radius`, `--radius-lg`, `--radius-pill`, `--transition`, `--header-height`, `--grid-min` | `settings.custom` |

This is a classic theme, so the page gutter comes from the templates
(`.site-main`, `.site-header__inner`, `.site-footer__inner`) and not from
`theme.json`. `useRootPaddingAwareAlignments` is therefore `false`. Turn it on
only if you also remove `padding-inline` from those three rules, or the gutter
is applied twice.

To change the whole look, set new values in the child theme:

```css
:root {
	--c-accent: #1d4ed8;
	--c-surface: #f1f5f9;
	--radius: 0;
	--radius-lg: 0;
	--grid-min: 18rem;
}
```

---

## The child theme

Make a folder next to this one, for example `acf-starter-child`.

**`style.css`**

```css
/*
Theme Name:  ACF Starter Child
Template:    acf-starter
Version:     1.0.0
Text Domain: acf-starter-child
*/
```

**`functions.php`**

```php
<?php
defined( 'ABSPATH' ) || exit;
// The parent theme loads assets/css/style.css from the child theme by itself.
```

Then add **`assets/css/style.css`** with your design. The parent theme finds it
and loads it after its own stylesheet, with a cache busting version.

A child theme may also hold:

* `blocks/<slug>/` — the parent registers child blocks in the same way.
* `acf-json/` — the parent loads child field groups too.
* `woocommerce/` — WooCommerce reads the child theme first.

---

## Staging and production

On a staging copy of a shop, one test order can send a real mail to a real
customer. The theme stops that.

### Tell WordPress which site it is

Put this line in `wp-config.php` on the staging site, above the line
`/* That's all, stop editing! */`:

```php
define( 'WP_ENVIRONMENT_TYPE', 'staging' );
```

Do not put it on production. Without the line WordPress says `production`.

### What the theme does on staging

On every environment except `production`:

* The admin bar shows a coloured badge, for example **STAGING · mail blocked**.
  Orange for `staging`, blue for `development` and `local`. You see it in the
  admin and on the site.
* All mail from `wp_mail()` stops. WooCommerce order mails and password mails
  too. Each blocked mail writes one line to the PHP error log.

You want to see the mails? Send them all to one address. Add this to
`wp-config.php` on staging:

```php
define( 'SGWRD_STAGING_MAIL_TO', 'you@example.com' );
```

The subject then starts with `[STAGING → customer@example.com]`. The theme
removes Cc and Bcc.

### A second guard in the code

Give the theme the production domain, in the child theme:

```php
add_filter( 'sgwrd_production_host', fn() => 'www.example.com' );
```

When a site says `production` but its domain is not this one, the theme treats
it as staging. So mail stays blocked when a staging copy got the production
`wp-config.php` by mistake.

### Cloudways push and pull

A push or a pull on Cloudways can copy `wp-config.php` too. Then the staging
line goes to production, or it disappears from staging. In the Cloudways push
and pull screen, use **Exclude files/folders** and exclude `wp-config.php`.
After every push or pull, look at the admin bar on both sites.

Never push the database from staging to production on a shop. Production gets
new orders, customers and stock while you work on staging. A database push
overwrites them. Push the code only (the workflow or Git). For content, use
the content sync or a content migration (the next two sections).

### Limits

* An SMTP plugin that replaces `wp_mail()` completely skips this guard. Most
  SMTP plugins use `wp_mail()`, so the guard works. Test it once: request a
  password reset on staging and look at the badge and the error log.
* The guard does not stop payments, webhooks or API calls. Put the payment
  plugins in test mode on staging.

### Filters

| Filter | What it changes |
| --- | --- |
| `sgwrd_environment` | The environment type the theme uses. |
| `sgwrd_production_host` | The production domain for the second guard. |
| `sgwrd_mail_guard` | Return `false` to send mail on staging. |
| `sgwrd_staging_mail_to` | The redirect address. |

---

## Content sync

Make the content changes on staging, look at them, and send only those
changes to production. Production keeps its orders, customers, stock and
its own new content.

### What goes over

| Goes over | Never goes over |
| --- | --- |
| Pages and posts, with their blocks and ACF fields | Products, prices, stock |
| Categories (`category`, `product_cat`), with the category header and FAQ | Orders, customers, reviews |
| Menus, and which menu shows where | Users |
| Site title, tagline, front page, posts page | WooCommerce settings |
| The theme settings page | Plugins and code (those go with Git) |
| New images that the changed content uses | |

A filter changes each list: `sgwrd_content_post_types`,
`sgwrd_content_taxonomies`, `sgwrd_content_options`.

### How it knows what changed

1. **The baseline.** When staging is a fresh copy of production, the theme
   takes a fingerprint of all content on staging. It does this by itself at
   the first admin page load after the copy or a pull. Nothing to do for you,
   as long as staging has the `WP_ENVIRONMENT_TYPE` line (see above).
2. **Your changes.** You change pages, menus and settings on staging.
3. **The compare.** Each content item on staging is compared with the
   baseline. Only new and changed items go into the package.
4. **The check on production.** Each item is compared with the same baseline
   on production:

| On production | What happens |
| --- | --- |
| Same as the baseline | The item goes in. Only staging changed it. |
| Already the same as staging | Nothing. |
| Changed since the baseline | **Conflict.** The item stays as it is, and the log shows it. |

A conflict means that somebody changed the same page on production. You
decide: make the change by hand, or run again with `force_conflicts` ticked
so the staging version wins. WordPress keeps a revision of every page it
changes, so you can go back.

5. **After the sync**, staging moves its baseline forward for the items that
   went over. The next sync sends only the next changes.

Items are matched by slug and path, never by ID, because the IDs differ
between the two sites. Images are matched by their file name in uploads. The
site address is swapped too: a link to the staging site becomes a link to
production.

An item that you removed on staging is **not** removed on production. The log
shows it once, and you remove it by hand. Renaming a slug counts as a remove
plus a new item.

### Run it

In GitHub: **Actions → Cloudways → Run workflow**.

1. `content-diff`: what changed on staging. Changes nothing.
2. `content-preview`: what would happen on production, with the conflicts.
   Changes nothing.
3. `content-sync`: a database backup of production, then the changes.

With WP-CLI, on the servers:

```bash
wp sgwrd content diff                   # staging
wp sgwrd content export ~/sync          # staging
wp sgwrd content import ~/sync --dry-run   # production, after you copy the folder
wp sgwrd content import ~/sync          # production
wp sgwrd content accept ~/sync          # staging, with result.json from production
```

### Set up staging in the workflow

The workflow needs two more secrets for the staging app. Add them in GitHub
under **Settings → Secrets and variables → Actions**:

| Secret | Value |
| --- | --- |
| `CW_STAGING_SSH_USER` | The SSH user of the staging application |
| `CW_STAGING_APP_PATH` | `applications/<staging-app>/public_html` |

Put the same public key on the staging application as on production. Then the
workflow uses the same private key. A staging app on another server needs
`CW_STAGING_SSH_HOST` too, and a different key needs `CW_STAGING_SSH_KEY`.

Every other action gets a `target` choice: `production` or `staging`. So you
can deploy and test a migration on staging first.

### Sync or migration?

| Use the sync | Use a migration |
| --- | --- |
| You made the change by hand on staging | The change is part of a feature in Git |
| Pages, menus, settings | Anything you can write in PHP |
| Once | On every site that gets the feature, in the same way |

---

## Content migrations

Some content cannot wait on the live site: a new feature needs a page, or the
menu changes. Do not click it in by hand twice, and never push the database.
Write a migration: a small PHP file that makes the change.

A migration adds to the database of the site it runs on. It never replaces
it. Orders, customers and stock stay as they are.

### How it goes

1. Build the feature on staging.
2. Write a migration for the content it needs, in `migrations/`.
3. Commit and push. Run it on staging and look at the result.
4. At the release, run the same migration on production.

Each migration runs once per site. The list of migrations that ran is in the
option `sgwrd_migrations`, so it moves with the database. After a pull from
production, staging knows what already ran there.

### Write one

Copy `migrations/_example.php` to a name that starts with the date, for
example `migrations/2026-09-27-add-outlet-page.php`. The name sets the order.
A name that starts with `_` never runs. Put project migrations in the child
theme (`<child>/migrations/`). The theme reads both folders.

```php
<?php
defined( 'ABSPATH' ) || exit;

return function () {
	$page = sgwrd_migrate_page( 'outlet', array( 'title' => 'Outlet' ) );
	sgwrd_migrate_menu_item( 'primary', 'Outlet', $page, array( 'position' => 2 ) );
};
```

The helpers look first and make only what is not there. So a second run
changes nothing, and a migration that failed halfway can run again.

| Helper | What it does |
| --- | --- |
| `sgwrd_migrate_page( $slug, $args )` | Makes the page, or updates it. Only the keys you give are written: `title`, `content`, `status`, `parent`, `template`, `menu_order`, `meta`. |
| `sgwrd_migrate_block( $name, $fields, $attrs )` | The markup of one ACF block, for the page content. |
| `sgwrd_migrate_rows( $name, $key, $rows, $sub_keys )` | A repeater for `sgwrd_migrate_block()`. |
| `sgwrd_migrate_menu_item( $menu, $title, $target, $args )` | Adds an item when the menu does not have it. `$menu` is a location or a menu name. `$target` is a post ID or a URL. `$args`: `parent` (the title of an item), `position`. |
| `sgwrd_migrate_menu_location( $location, $menu )` | Shows a menu in a location. |
| `sgwrd_migrate_menu( $menu )` | Finds a menu, or makes it. |

For options and theme settings, use WordPress and ACF directly:
`update_option()` and `update_field( $name, $value, 'option' )`.

**A new menu.** Make it on the live site in Appearance → Menus, and give it
no location. Visitors do not see it. At the release, a migration switches the
location: `sgwrd_migrate_menu_location( 'primary', 'Main menu 2027' )`. The old
menu stays, so you can switch back.

### Run it

| Where | How |
| --- | --- |
| GitHub | Actions → Cloudways → Run workflow → `migrate-status` first, then `migrate` |
| Terminal | `wp sgwrd migrate status`, then `wp sgwrd migrate run` |

`wp sgwrd migrate run --dry-run` shows what would run and changes nothing.
`wp sgwrd migrate skip <name>` marks a migration as done without running it,
for a change you already made by hand.

The `migrate` action in the workflow does three things:

1. It sends the theme to the server, with the new migrations.
2. It makes a database backup in `db-backups/`, next to `public_html`, out of
   reach of the web. It keeps the last ten. When the backup fails, nothing
   runs.
3. It runs the waiting migrations and clears the caches.

A migration that fails stops the run. It is not marked as done, and the
migrations after it wait. Fix it, push, and run again. To go back to the
state before the run, restore the backup: in the Cloudways panel, or with
`wp db import` in a terminal. The workflow does not allow `db import`.

---

## The demo

`demo/` builds a complete shop on a clean WordPress install with one command:
products with photos, categories with banners, a home page made from the theme
blocks, the menus and all the settings.

```bash
bash wp-content/themes/acf-starter/demo/provision.sh
```

It is written for Cloudways and runs on any host with WP-CLI.

`.github/workflows/cloudways.yml` runs the same thing from the GitHub Actions
tab, over SSH, so you need no terminal at all. See
[demo/README.md](demo/README.md) for the steps, the secrets it needs, and how
to put your own products and photos in.

---

## Things to do on a new project

1. Rename the theme in `style.css`, and rename the folder to match the text
   domain.
2. Set the palette and the widths in `theme.json`.
3. Set `define( 'DISALLOW_FILE_EDIT', true );` in `wp-config.php`.
4. Turn `WP_DEBUG` on while you build, and off when you go live.
5. Delete the blocks you do not need. Delete the folder and the matching file
   in `/acf-json`.

---

## Licence

GPL-2.0-or-later.
