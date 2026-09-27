# Migrations

Scripts that change the content of a live site: pages, menu items, options.
They add to the database. They never replace it, so orders, customers and
stock stay as they are.

See "Content migrations" in the theme README for the full explanation.

## Make one

1. Copy `_example.php` to a new name that starts with the date:
   `2026-09-27-add-outlet-page.php`. The name sets the order.
2. Change the function in it.
3. Commit and push.

A project migration goes in the child theme: `<child>/migrations/`. The parent
reads both folders.

## Run it

| Where | How |
| --- | --- |
| GitHub | Actions → Cloudways → Run workflow → `migrate` |
| Terminal | `wp sgwrd migrate run` |

Look first, change nothing: `wp sgwrd migrate run --dry-run`.
See what ran: `wp sgwrd migrate status`.

Each migration runs once. A migration that fails is not marked as done. Fix
it and run again.
