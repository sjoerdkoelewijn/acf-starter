# Paid plugins

`provision.sh` installs WooCommerce from wordpress.org by itself.

**ACF Pro is paid, so the script cannot download it.** Put the zip here:

    demo/plugins/advanced-custom-fields-pro.zip

The script then installs and activates it. If ACF Pro is already active on the
site, the script leaves it alone and this folder can stay empty.

Zip files never go into git. The `.gitignore` in this folder takes care of it.
