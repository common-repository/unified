<?php

namespace Unified\Utilities\MUPlugin;

use Unified\Modules\Caching\PageCache;
use Unified\Utilities\Configuration;

/**
 * MU Plugin class
 *
 * @since 1.0
 */
class MUPlugin
{
    private $plugin_dir = '';

    /**
     *  Constructor
     */
    public function __construct($plugin_dir)
    {
        $this->plugin_dir = $plugin_dir;

        // Load composer autoloader
        require_once $this->plugin_dir . '/vendor/autoload.php';

        $this->init();
    }

    /**
     *  Initialize
     */
    public function init()
    {
        $configuration = Configuration::getInstance();

        // Check global first
        if (!$configuration->get('global_enabled')) {
            return;
        }

        // Check page cache
        if ($configuration->get('cache_pages')) {
            (new PageCache())->checkCache();
        }
    }
}
