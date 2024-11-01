<?php

namespace Unified\Modules\Caching;

use Unified\Utilities\CommonFunctions;
use Unified\Utilities\PluginDirs;
use Unified\Utilities\SingletonTrait;

/**
 * Cache store
 */
class CacheStore
{
    use SingletonTrait;

    const OPTION_CACHE_KEY = 'unified_cache_key';

    private $cache_dir;

    /**
     *  Initialize
     */
    public function init()
    {
        $plugin_dirs = PluginDirs::getInstance();
        $this->cache_dir = $plugin_dirs->getUnifiedUploadsFilePath('page-cache');
    }

    /**
     *  Save a cache item
     */
    public function get(string $id)
    {
        $filename = $this->getCacheFilename($id);
        if (file_exists($filename)) {
            return file_get_contents($filename);
        }
        return false;
    }

    /**
     *  Get a filename cache id, based on a id to cache
     *  @since 1.0
     */
    private function getCacheFilename(string $id)
    {
        $md5_id = md5($id);
        $filename = $this->cache_dir . $md5_id;
        return $filename;
    }

    /**
     *  Clean all or specific item
     *  @since 1.0
     */
    public function clean(string $id = '')
    {
        if ($id == '') {
            // Clean all
            $common_functions = CommonFunctions::getInstance();
            $common_functions->removeDirectory($this->cache_dir);
        } else {
            // Remove a specific id
            $filename = $this->getCacheFilename($id);
            @unlink($filename);
        }
    }

    /**
     *  Save a cache item
     *  @since 1.0
     */
    public function save(string $id, string $value)
    {
        $filename = $this->getCacheFilename($id);

        // Write it
        file_put_contents($filename, $value);
    }
}
