<?php
/**
 * Uninstall routine for Oxyplug Preload.
 *
 * Runs only when the plugin is deleted from the WordPress admin. It removes the
 * `# BEGIN Oxyplug Preload ... # END Oxyplug Preload` block from `.htaccess` and
 * deletes the `_oxyplug_preload_*` options so nothing stale is left behind.
 */

// Bail if WordPress did not invoke this file as an uninstall.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Delete the plugin's options for the current site/blog context.
 *
 * Mirrors OxyPreload::oxyplug_preload_update_option(), which stores options as
 * network options (keyed by the current blog id) on multisite and as regular
 * options otherwise.
 *
 * @return void
 */
function oxyplug_preload_delete_options(): void
{
    $option_names = array(
        '_oxyplug_preload_featured_image',
        '_oxyplug_preload_preloads',
    );

    foreach ($option_names as $option_name) {
        if (is_multisite()) {
            delete_network_option(get_current_blog_id(), $option_name);
        } else {
            delete_option($option_name);
        }
    }
}

/**
 * Strip the Oxyplug Preload section from the site's .htaccess file.
 *
 * @return void
 */
function oxyplug_preload_clean_htaccess(): void
{
    $htaccess_path = ABSPATH . '.htaccess';

    global $wp_filesystem;
    if (!$wp_filesystem) {
        require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
        require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
        $wp_filesystem = new \WP_Filesystem_Direct(null);
    }

    if (!$wp_filesystem->exists($htaccess_path)
        || !$wp_filesystem->is_readable($htaccess_path)
        || !$wp_filesystem->is_writable($htaccess_path)) {
        return;
    }

    $current_content = $wp_filesystem->get_contents($htaccess_path);
    if ($current_content === false) {
        return;
    }

    // Same marker pattern used when writing the section in oxy-preload.php.
    $pattern = '/\n*# BEGIN Oxyplug Preload\n.*?# END Oxyplug Preload\n*/s';
    $cleaned = preg_replace($pattern, '', $current_content);

    if ($cleaned !== null && $cleaned !== $current_content) {
        $wp_filesystem->put_contents($htaccess_path, rtrim($cleaned) . "\n");
    }
}

oxyplug_preload_clean_htaccess();

if (is_multisite()) {
    // The plugin stores options per blog, so clean each site on the network.
    $site_ids = get_sites(array('fields' => 'ids'));
    foreach ($site_ids as $site_id) {
        switch_to_blog($site_id);
        oxyplug_preload_delete_options();
        restore_current_blog();
    }
} else {
    oxyplug_preload_delete_options();
}
