<?php

namespace Unified\Modules\Security;

use Unified\Utilities\Configuration;

/**
 * Security module
 * @since 1.0
 */
class SecurityModule
{
    /**
     *  Initialize security module
     *  @since 1.0
     */
    public function init()
    {
        // Do security headers
        (new Headers())->init();
    }
}
