<?php

declare(strict_types=1);

/**
 * Plugin Name: CAMOO CDN
 * Requires Plugins: wp-super-cache
 * Plugin URI: https://github.com/camoo/wp-camoo-cdn
 * Description: Integrates your WordPress site with Camoo.Hosting CDN for improved loading times and performance.
 * Version: 2.0.2
 * Author: CAMOO SARL
 * Author URI: https://www.camoo.hosting/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: camoo-cdn
 * Domain Path: /languages
 * Requires at least: 6.4.3
 * Tested up to: 6.5.2
 * Requires PHP: 8.0
 *
 * CAMOO CDN is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * CAMOO CDN is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CAMOO CDN.
 * If not, see <https://www.gnu.org/licenses/>.
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Autoload classes using Composer's autoload if available.
if (file_exists(plugin_dir_path(__FILE__) . 'vendor/autoload.php')) {
    require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';
    require_once plugin_dir_path(__FILE__) . '/config/defines.php';
}

// Initialize the plugin.
$bootstrap = new \WP_CAMOO\CDN\Bootstrap();
$bootstrap->initialize();
