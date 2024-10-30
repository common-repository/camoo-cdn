<?php

declare(strict_types=1);

namespace WP_CAMOO\CDN\Cache;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use function add_settings_section;

use WP_CAMOO\CDN\Gateways\Option;
use WP_CAMOO\CDN\Services\QueryCaching;

final class Settings
{
    /** @var array|null Holds the plugin settings to minimize repeated database calls. */
    private static ?array $options = null;

    /** Hooks into WordPress to initialize plugin settings. */
    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'appendMenuLink']);
        add_action('admin_init', [self::class, 'registerSettings']);
        add_action('updated_option', [self::class, 'onOptionUpdate'], 10, 3);
    }

    /** Method to handle actions after option updates. */
    public static function onOptionUpdate($option, $oldValue, $newValue): void
    {
        if ($option === 'camoo_cdn_cache_settings') {
            QueryCaching::clear();
        }
    }

    /** Appends the CAMOO CDN settings page to the WordPress admin menu. */
    public static function appendMenuLink(): void
    {
        add_menu_page(
            __('CAMOO CDN Query Cache Settings', 'camoo-cdn'),
            'CAMOO CDN',
            'manage_options',
            'camoo_cdn',
            [self::class, 'renderSettingsPage']
        );
    }

    public static function sanitizeSettings($inputs): array
    {
        foreach ($inputs as $key => $value) {
            if ($key === 'enable_caching') {
                $inputs[$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            } else {
                $inputs[$key] = sanitize_text_field($value);
            }
        }

        return $inputs;
    }

    /** Registers all settings, sections, and fields for the CAMOO CDN plugin. */
    public static function registerSettings(): void
    {
        register_setting(
            'camoo_cdn_options',
            'camoo_cdn_cache_settings',
            ['sanitize_callback' => [self::class, 'sanitizeSettings']]
        );

        add_settings_section(
            'camoo_cdn_main',
            __('CAMOO CDN Query Cache Settings', 'camoo-cdn'),
            [self::class, 'settingsDescription'],
            'camoo_cdn'
        );
        /*
                self::addSettingsField(
                    'excluded_pages',
                    __('Pages to Exclude from Caching', 'camoo-cdn'),
                    [self::class, 'renderTextInput'],
                    '/foo-page, /bar-page'
                );
                self::addSettingsField(
                    'table_inclusion',
                    __('Tables to Include in Caching', 'camoo-cdn'),
                    [self::class, 'renderTextInput']
                );
                self::addSettingsField(
                    'table_exclusion',
                    __('Tables to Exclude from Caching', 'camoo-cdn'),
                    [self::class, 'renderTextInput']
                );*/
        self::addSettingsField(
            'cache_duration',
            __('Cache Duration (minutes)', 'camoo-cdn'),
            [self::class, 'renderNumberInput']
        );
        self::addSettingsField(
            'enable_caching',
            __('Enable Query Caching', 'camoo-cdn'),
            [self::class, 'renderCheckbox']
        );
    }

    /** Renders a text input field for settings. */
    public static function renderTextInput(array $args): void
    {
        $options = self::getOptions();
        $value = $options[$args['id']] ?? '';
        echo "<input id='camoo_cdn_{$args['id']}'
        name='camoo_cdn_cache_settings[{$args['id']}]' size='40' type='text' value='" .
            esc_attr($value) . "' placeholder='" . esc_attr($args['placeholder']) . "' />";
    }

    /** Renders a number input field for settings. */
    public static function renderNumberInput(array $args): void
    {
        $options = self::getOptions();
        $value = $options[$args['id']] ?? '';
        echo "<input id='camoo_cdn_{$args['id']}'
            name='camoo_cdn_cache_settings[{$args['id']}]' size='40' type='number' value='" . esc_attr($value) . "' />";
    }

    /** Renders a checkbox for settings. */
    public static function renderCheckbox(array $args): void
    {
        $options = self::getOptions();
        $checked = !empty($options[$args['id']]) ? 'checked' : '';
        echo "<input id='camoo_cdn_{$args['id']}'
            name='camoo_cdn_cache_settings[{$args['id']}]' type='checkbox' {$checked} /> Enable";
    }

    /** Provides a description for the settings section. */
    public static function settingsDescription(): void
    {
        echo '<p>' . __(
            'Customize the caching behavior for your WordPress site with CAMOO CDN Query Cache settings.',
            'camoo-cdn'
        ) . '</p>';

    }

    /** Renders the settings page in the WordPress admin. */
    public static function renderSettingsPage(): void
    {
        echo '<div class="wrap"><h1>' . esc_html(get_admin_page_title()) . '</h1>';
        echo '<form action="options.php" method="post">';
        settings_fields('camoo_cdn_options');
        do_settings_sections('camoo_cdn');
        submit_button(__('Save Settings', 'wp-super-cache'));
        echo '</form></div>';
    }

    /** Fetches the plugin settings from the database only once per request. */
    private static function getOptions(): array
    {
        if (self::$options === null) {
            self::$options = Option::get('camoo_cdn_cache_settings') ?? [];
        }

        return self::$options;
    }

    /** Generic method to add a settings field. */
    private static function addSettingsField(
        string $id,
        string $title,
        callable $callback,
        string $placeholder = ''
    ): void {
        add_settings_field(
            "camoo_cdn_{$id}",
            $title,
            $callback,
            'camoo_cdn',
            'camoo_cdn_main',
            ['id' => $id, 'placeholder' => $placeholder]
        );
    }
}
