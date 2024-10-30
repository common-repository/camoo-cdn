<?php

declare(strict_types=1);

namespace WP_CAMOO\CDN\Services;

use WP_CAMOO\CDN\Gateways\Option;
use WP_Query;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

final class QueryCaching
{
    /** @var array|null Cache settings loaded once per request. */
    private static ?array $options = null;

    /**
     * Handles reading and potentially caching query results.
     *
     * @param array    $posts The posts retrieved by the query.
     * @param WP_Query $query The WP_Query instance.
     *
     * @return array The possibly modified posts array.
     */
    public static function read(array $posts, WP_Query $query): array
    {
        // Only modify main query and non-admin pages.
        if (!is_admin() && $query->is_main_query()) {
            $currentUrl = self::getCurrentUrl();
            self::logDebug('Start caching: ' . $currentUrl);
            if (!self::shouldCache() || self::isExcluded($currentUrl)) {
                self::logDebug("Caching is disabled or URL is excluded: {$currentUrl}");

                return $posts;
            }

            $transientName = 'camoo_cdn_' . md5($currentUrl);
            $cachedPosts = get_transient($transientName);

            if ($cachedPosts !== false) {
                self::logDebug('Cache content found for ' . $currentUrl);
                self::logQueries('After retrieving cache for ' . $currentUrl);

                return $cachedPosts;
            }
            self::logDebug('No cache found for ' . $currentUrl);
            $cacheDuration = self::getCacheDuration();
            self::logDebug('Cache duration is ' . $cacheDuration);
            set_transient($transientName, $posts, MINUTE_IN_SECONDS * $cacheDuration);
            self::logQueries("After setting cache for {$currentUrl}");

        }

        return $posts;
    }

    /** Clears all cached content related to this feature. */
    public static function clear(): void
    {
        self::logDebug('Clear cache started...');
        global $wpdb;
        $wpdb->query("
           DELETE FROM {$wpdb->options}
           WHERE option_name LIKE '\_transient\_camoo\_cdn\_%' OR option_name LIKE '\_transient\_timeout\_camoo\_cdn\_%'
        ");
        self::logDebug('Clear cache ended...');
    }

    private static function shouldCache(): bool
    {
        if (is_user_logged_in() || WC()->cart->get_cart_contents_count() > 0) {
            self::logDebug("Don't cache loggedin user or non empty Woocommerce cart");

            return false;
        }
        $options = self::getOptions();

        return isset($options['enable_caching']) && $options['enable_caching'];
    }

    /**
     * Ensures that settings are loaded only once per request.
     *
     * @return array The settings array.
     */
    private static function getOptions(): array
    {
        if (self::$options === null) {
            self::$options = Option::get('camoo_cdn_cache_settings') ?? [];
        }

        return self::$options;
    }

    /**
     * Determines if the current URL is excluded from caching.
     *
     * @param string $url The current URL.
     *
     * @return bool True if the URL is excluded, false otherwise.
     */
    private static function isExcluded(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '/';

        // Regular expression to match file extensions that should not be cached
        $excludedPatterns = '/\.(?:png|jpg|jpeg|gif|ico|css|js|woff|woff2|ttf|svg|mp3|mp4|mov|avi|map)$|\/(cart|checkout|my-account)\/?$/';

        if (preg_match($excludedPatterns, $path)) {
            return true;
        }
        $excludedPages = self::getExcludedPages();

        return in_array($url, $excludedPages, true);
    }

    /**
     * Retrieves excluded pages from settings.
     *
     * @return array List of excluded page URLs.
     */
    private static function getExcludedPages(): array
    {
        $options = self::getOptions();
        $excludedPages = $options['excluded_pages'] ?? '';

        return array_map('trim', explode(',', $excludedPages));
    }

    /**
     * Fetches the cache duration from settings, defaulting to 2 minutes if not set.
     *
     * @return int Cache duration in hours.
     */
    private static function getCacheDuration(): int
    {
        $options = self::getOptions();

        return max((int)($options['cache_duration'] ?? 2), 2);
    }

    /**
     * Constructs the current URL.
     *
     * @return string The full URL of the current request.
     */
    private static function getCurrentUrl(): string
    {
        global $wp;
        $url = home_url(add_query_arg([], $wp->request));
        self::logDebug('Current URL: ' . $url);

        return $url;
    }

    private static function logDebug(string $message): void
    {
        // Check if WP_DEBUG and WP_DEBUG_LOG are enabled
        if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            // Define the custom log path
            $log_path = WP_CONTENT_DIR . '/debug.log';
            // Check if the log file is writable or if it does not exist and can be created
            if (is_writable($log_path) || (!file_exists($log_path) && is_writable(dirname($log_path)))) {
                error_log(current_time('mysql') . " - {$message}\n", 3, $log_path);
            } else {
                error_log(current_time('mysql') . " - Failed to write to log: {$message}\n");
            }
        }
    }

    private static function logQueries($context): void
    {
        global $wpdb;
        if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG && isset($wpdb->queries)) {
            $logMessage = "Logged SQL Queries - {$context}:\n";
            foreach ($wpdb->queries as $query) {
                $logMessage .= "{$query[0]} - ({$query[1]} seconds)\n";
            }
            self::logDebug($logMessage);
        }
    }
}
