<?php

namespace Unified\Modules\Caching;

use Unified\Utilities\Configuration;

/**
 * Caching module
 * @since 1.0
 */
class CachingModule
{
    /**
     *  Initialize caching module
     *  @since 1.0
     */
    public function init()
    {
        $configuration = Configuration::getInstance();
        if ($configuration->get('cache_pages')) {
            $page_cache = new PageCache();

            add_action('activated_plugin', [$page_cache, 'clearCache']);
            add_action('deactivated_plugin', [$page_cache, 'clearCache']);

            add_action('upgrader_process_complete', [$page_cache, 'clearCache']);

            add_action('switch_theme', [$page_cache, 'clearCache']);
            add_action('wp_create_nav_menu', [$page_cache, 'clearCache']);
            add_action('wp_update_nav_menu', [$page_cache, 'clearCache']);
            add_action('wp_delete_nav_menu', [$page_cache, 'clearCache']);
            add_action('update_option_sidebars_widgets', [$page_cache, 'clearCache']);


            add_action('save_post', [$page_cache, 'clearPostCache']);
            add_action('delete_post', [$page_cache, 'clearPostCache']);
            add_action('clean_post_cache', [$page_cache, 'clearPostCache']);
            add_action('post_updated', [$page_cache, 'clearPostCache']);
            add_action('pre_post_update', [$page_cache, 'clearPostCache']);

            add_action('woocommerce_product_set_stock', [$page_cache, 'clearCache']);
            add_action('woocommerce_product_set_stock_status', [$page_cache, 'clearCache']);
            add_action('update_option_comment_mail_options', [$page_cache, 'clearCache']);

            add_action('added_term_relationship', [$page_cache, 'clearCache']);
            add_action('delete_term_relationships', [$page_cache, 'clearCache']);

            add_action('trackback_post', [$page_cache, 'clearPostCacheForComment']);
            add_action('pingback_post', [$page_cache, 'clearPostCacheForComment']);
            add_action('comment_post', [$page_cache, 'clearPostCacheForComment']);
            add_action('edit_comment', [$page_cache, 'clearPostCacheForComment']);
            add_action('transition_comment_status', [$page_cache, 'clearPostCacheForCommentTransition'], 10, 3);

            add_action('create_term', [$page_cache, 'clearCache']);
            add_action('edit_terms', [$page_cache, 'clearCache']);
            add_action('delete_term', [$page_cache, 'clearCache']);

            add_action('add_link', [$page_cache, 'clearCache']);
            add_action('edit_link', [$page_cache, 'clearCache']);
            add_action('delete_link', [$page_cache, 'clearCache']);

            if (!\is_admin()) {
                $page_cache->init();
            }
        }
    }
}
