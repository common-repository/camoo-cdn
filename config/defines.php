<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

/**
 * Include the plugin.php file to use the get_plugin_data function if it's not already available.
 * This function is needed to retrieve plugin metadata stored in the plugin's header comment.
 */
if (!function_exists('get_plugin_data')) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

// Set Plugin path and url defines.

define('WP_CAMOO_CDN_DIR', plugin_dir_path(__DIR__));

// Get plugin Data.
$plugin_data = get_plugin_data(WP_CAMOO_CDN_DIR . 'camoo-cdn.php');

// Set another useful Plugin definition.
define('WP_CAMOO_CDN_VERSION', $plugin_data['Version']);
const WP_CAMOO_CDN_SUFFIX_DOMAIN = 'https://%s.camoo.site';
const WP_CAMOO_CDN_SITE = 'https://www.camoo.hosting';
