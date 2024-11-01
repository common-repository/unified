<?php

namespace Unified\Utilities;

/**
 * Singleton Trait
 * @since 1.0
 */
trait SingletonTrait
{
    /**
     *  Constructor
     *  @since 1.0
     */
    private function __construct()
    {
    }

    /**
     *  Get instance
     *  @since 1.0
     *  @return static
     */
    public static function getInstance($force_new_instance = false)
    {
        static $instance = null;
        if ($instance == null || $force_new_instance) {
            $class = static::class;
            $instance = new $class();

            // Do any needed loads
            if (\method_exists($class, 'init')) {
                $instance->init();
            }
        }
        return $instance;
    }
}
