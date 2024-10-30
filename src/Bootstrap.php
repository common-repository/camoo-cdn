<?php

declare(strict_types=1);

namespace WP_CAMOO\CDN;

use WP_CAMOO\CDN\Cache\Settings;
use WP_CAMOO\CDN\Services\Integration;
use WP_CAMOO\CDN\Services\QueryCaching;

final class Bootstrap
{
    private const PLUGIN_MAIN_FILE = 'camoo-cdn/camoo-cdn.php';

    public function initialize(): void
    {
        Integration::initialize();
        $this->addHooks();
    }

    public function modifyPluginDescription($all_plugins): array
    {
        if (isset($all_plugins[self::PLUGIN_MAIN_FILE])) {
            $all_plugins[self::PLUGIN_MAIN_FILE]['Description'] = sprintf(
                __(
                    'Camoo.Hosting Automatic Integration with CDN for WordPress. Check our <a target="_blank" href="%s">Managed WordPress packages</a> out for more.',
                    'camoo-cdn'
                ),
                esc_url(WP_CAMOO_CDN_SITE . '/wordpress-hosting')
            );
        }

        return $all_plugins;
    }

    public function displaySyncMessages(): void
    {
        if ($message = get_transient('wp_camoo_cdn_sync_message')) {
            $allowedHtml = [
                'a' => [
                    'href' => [],
                    'target' => [],
                ],
            ];
            echo '<div class="notice notice-error is-dismissible"><p>' . wp_kses($message, $allowedHtml) . '</p></div>';

            delete_transient('wp_camoo_cdn_sync_message');
        }
    }

    private function addHooks(): void
    {
        add_filter('all_plugins', [$this, 'modifyPluginDescription']);
        add_action('admin_notices', [$this, 'displaySyncMessages']);

        // Hook into the admin menu to add a settings page
        Settings::init();
        // Hook into the posts_results filter to cache queries
        add_filter('posts_results', [QueryCaching::class, 'read'], 10, 2);

        // Clear the cache when posts are saved or deleted
        add_action('save_post', [QueryCaching::class, 'clear']);
        add_action('deleted_post', [QueryCaching::class, 'clear']);
    }
}
