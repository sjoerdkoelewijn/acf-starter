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

## Running it from GitHub, with no terminal

`.github/workflows/cloudways.yml` runs all of this over SSH from a GitHub
runner. After a one-time setup you never open a terminal again: you pick an
action in the Actions tab and read the log.

### One-time setup

**1. Make a key just for this.**

Press Enter at `Enter passphrase`, and again at the confirmation. **The key
must have no passphrase**, because a GitHub runner cannot type one.

On macOS or Linux:

```bash
mkdir -p ~/.ssh
ssh-keygen -t ed25519 -C "github-actions-cloudways" -f ~/.ssh/cloudways_deploy
```

On Windows PowerShell:

```powershell
New-Item -ItemType Directory -Force -Path "$env:USERPROFILE\.ssh" | Out-Null
ssh-keygen -t ed25519 -C "github-actions-cloudways" -f "$env:USERPROFILE\.ssh\cloudways_deploy"
```

Two things go wrong on Windows if you use the macOS line:

* `ssh-keygen` does not understand `~`. PowerShell passes it through as a plain
  character, and the key fails to save with `No such file or directory`.
* `-N ""` sets an empty passphrase on other systems, but PowerShell drops an
  empty argument before the program sees it, and `ssh-keygen` stops with
  `option requires an argument -- N`. Leave the flag off and use the prompt.

Read the two halves back with `cat` on macOS and Linux, or with
`Get-Content <path> -Raw` in PowerShell.

**2. Put the public half on Cloudways.** In the panel, under the server's
**Settings & Packages → SSH Public Keys**, add the contents of
`~/.ssh/cloudways_deploy.pub`. Use an application user, not the master user.

**3. Pin the host key** so the runner cannot be sent to the wrong server:

```bash
ssh-keyscan <server-ip>
```

**4. Add the secrets.** On GitHub, under **Settings → Secrets and variables →
Actions → Repository secrets**:

| Secret | Value |
| --- | --- |
| `CW_SSH_HOST` | The server IP |
| `CW_SSH_USER` | The application SSH user |
| `CW_SSH_KEY` | The whole of `~/.ssh/cloudways_deploy`, the private half |
| `CW_APP_PATH` | `applications/<your-app>/public_html` |
| `CW_SSH_KNOWN_HOSTS` | The output of step 3. Optional but do it. |
| `CW_SSH_PORT` | Only when it is not 22 |

Never put these in a chat, an issue or a commit.

### Using it

**Actions → Cloudways → Run workflow**, then pick:

| Action | What it does |
| --- | --- |
| `status` | Reads the theme, plugins, content counts and settings. Changes nothing. |
| `deploy` | Sends the theme to the server and activates it. |
| `provision` | Deploy, then the whole setup and the demo content. |
| `seed` | Rebuilds the demo content only. Faster. |
| `seed-fresh` | Deletes the demo content and builds it again. Needs the tick box. |
| `purge` | Clears the WordPress caches. |
| `wp` | Runs one WP-CLI command that you type. |

Start with `status`. It proves the key, the path and WP-CLI all work, and it
cannot break anything.

### What the workflow will not do

The `wp` action sends your text to a shell on the server, so it is guarded:

* Any shell character that could chain a second command is refused:
  `; & | < > $ ( ) \` and backtick.
* These are refused outright: `db drop`, `db reset`, `db query`, `db import`,
  `site empty`, `user delete`, `plugin delete`, `theme delete`, `core
  download`, `core update` and `eval`.
* `search-replace` runs with `--dry-run` only.
* `seed-fresh` needs the confirmation box ticked.

**Be clear about what this is.** It stops a slip and a stray paste. It is not a
security boundary: anybody who can push to this repository can change the
workflow and run anything. So give the key one application, not the server, and
keep write access to people you trust. Pull the key at any time by removing it
in the Cloudways panel.

A deploy uses `rsync --delete`, but it leaves `demo/images/` and
`demo/plugins/` alone. Your photos and the ACF Pro zip survive it.

### Adding a review gate

To make a run need a second pair of eyes, make an Environment called
`cloudways` under **Settings → Environments**, add yourself as a required
reviewer, and put `environment: cloudways` in the job in the workflow file.

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
