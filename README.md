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
├── blocks/                  One folder for each ACF block.
│   └── <slug>/
│       ├── block.json       The block definition.
│       └── render.php       The block markup.
├── functions/               One file for each concern. See the table below.
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
| `setup.php` | Theme support, menus, image sizes, the footer widget area. |
| `assets.php` | Loads the styles and the scripts. |
| `cleanup.php` | Removes the default WordPress output the theme does not use. |
| `editor.php` | The block allow list, the block category and the block styles. |
| `admin.php` | Makes the WordPress admin smaller. |
| `acf.php` | ACF JSON sync, the block loader and the options page. |
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
