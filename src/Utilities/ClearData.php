<?php

namespace Unified\Utilities;

use Unified\Modules\Caching\PageCache;

/**
 * Class to handle clearing data, such as on configuration changes
 *
 * @since 1.0
 */
class ClearData
{
    /**
     *  Clear all intermediate data from each module
     *  @since 1.0.
     */
    public function clearAllModuleData(): bool
    {
        $clear_functions = [
            [new PageCache(), 'clearCache']
        ];

        foreach ($clear_functions as $callback) {
            call_user_func_array($callback, []);
        }

        return true;
    }
}
