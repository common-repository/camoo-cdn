<?php

declare(strict_types=1);

namespace WP_CAMOO\CDN\Services;

use WP_CAMOO\CDN\Gateways\Option;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

final class SyncFiles
{
    public static function sync(): void
    {
        $fetchedData = self::fetchCDNData();

        if (self::canUseCDN($fetchedData)) {
            self::configureCDN($fetchedData);
            self::updateWpSuperCache();
        } else {
            self::logCDNUnavailableError();
        }
    }

    private static function canUseCDN(array $data): bool
    {
        return !empty($data) && isset($data['status']) && $data['status'];
    }

    private static function configureCDN(array $data): void
    {
        $cdnPrefix = $data['cdn_prefix'] ?? '';
        if (empty($cdnPrefix)) {
            self::logError('Error: CDN domain cannot be fetched. Please contact Camoo.Hosting support.');

            return;
        }

        $cdnUrl = sprintf(WP_CAMOO_CDN_SUFFIX_DOMAIN, sanitize_text_field($cdnPrefix));
        update_option('ossdl_off_cdn_url', $cdnUrl);
        update_option('ossdl_off_blog_url', get_site_url());
    }

    private static function logCDNUnavailableError(): void
    {
        $packages_url = esc_url(WP_CAMOO_CDN_SITE . '/wordpress-hosting');
        $domain = parse_url(home_url(), PHP_URL_HOST);
        $link_text = __('Managed WordPress packages', 'camoo-cdn');
        $message_format = __('CDN is not available for your domain: %s. Check our %s out for more.', 'camoo-cdn');
        $link_html = sprintf('<a href="%s" target="_blank">%s</a>', $packages_url, esc_html($link_text));
        $fullMessage = sprintf($message_format, esc_html($domain), $link_html);

        self::logError($fullMessage);
    }

    private static function updateWpSuperCache(): void
    {
        if (get_option('wp_camoo_cdn_oss')) {
            return;
        }

        $configFile = WP_CONTENT_DIR . '/wp-cache-config.php';
        if (!file_exists($configFile)) {
            return;
        }

        $configurations = [
            'ossdlcdn' => '$ossdlcdn = 1;',
            'cache_enabled' => '$cache_enabled = true;',
            'super_cache_enabled' => '$super_cache_enabled = true;',
            'wp_cache_not_logged_in' => '$wp_cache_not_logged_in = 2;',
            'wp_supercache_304' => '$wp_supercache_304 = 1;',
            'wp_cache_clear_on_post_edit' => '$wp_cache_clear_on_post_edit = 1;',
            'wp_cache_front_page_checks' => '$wp_cache_front_page_checks = 1;',
            'wp_cache_mobile_enabled' => '$wp_cache_mobile_enabled = 1;',
            'cache_compression' => '$cache_compression = 1;',
        ];

        foreach ($configurations as $key => $replacement) {
            wp_cache_replace_line('^ *\$' . $key, $replacement, $configFile);
        }
        Option::add('wp_camoo_cdn_oss', 1);
    }

    private static function fetchCDNData(): array
    {
        $api_url = WP_CAMOO_CDN_SITE . '/cpanel/managed-wordpress/can-cdn.json?dn=' .
            urlencode(parse_url(home_url(), PHP_URL_HOST));
        $response = wp_remote_get($api_url);

        if (is_wp_error($response)) {
            self::logError('Error checking CDN availability: ' . $response->get_error_message());

            return [];
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }

    private static function logError($message): void
    {
        set_transient('wp_camoo_cdn_sync_message', $message, 60 * 10);
    }
}
