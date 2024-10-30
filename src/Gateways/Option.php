<?php

declare(strict_types=1);

namespace WP_CAMOO\CDN\Gateways;

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

/**
 * Class Option
 *
 * @author CamooSarl
 */
final class Option
{
    public const MAIN_SETTING_KEY = 'wp_camoo_cdn_url';

    /**
     * Get the whole Plugin Options
     *
     * @param string $setting_name setting name
     *
     * @return mixed|string
     */
    public static function get(?string $setting_name = null)
    {
        if (null === $setting_name) {
            $setting_name = self::MAIN_SETTING_KEY;
        }

        return get_option($setting_name);
    }

    /**
     * Add an option
     */
    public static function add(string $option_name, $option_value): void
    {
        add_option($option_name, $option_value);
    }

    public static function delete(string $name): void
    {
        delete_option($name);
    }

    public static function update(string $option_name, $option_value): void
    {
        update_option($option_name, $option_value);
    }
}
