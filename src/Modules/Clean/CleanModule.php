<?php

namespace Unified\Modules\Clean;

use Unified\Utilities\Configuration;

/**
 * Cleaning module
 * @since 1.0
 */
class CleanModule
{
    /**
     *  Initialize cleaning module
     *  @since 1.0
     */
    public function init()
    {
        $configuration = Configuration::getInstance();
        $wp_output = new WPOutput();
        if ($configuration->get('clean_wp_output')) {
            $wp_output->disableBaseOutput();
        }
        if ($configuration->get('clean_disable_emoji')) {
            $wp_output->disableEmojiOutput();
        }
        if ($configuration->get('clean_disable_feeds')) {
            $wp_output->disableFeeds();
        }
        if ($configuration->get('clean_disable_author_pages')) {
            $wp_output->disableAuthorPages();
        }
        if ($configuration->get('clean_disable_admin_bar')) {
            $wp_output->disableAdminBar();
        }
        if ($configuration->get('clean_disable_default_rest_routes')) {
            $wp_output->disableRESTDefaultRoutes();
        }
    }
}
