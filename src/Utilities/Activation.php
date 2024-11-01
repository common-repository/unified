<?php

namespace Unified\Utilities;

use Unified\Utilities\MUPlugin\MUPluginAdmin;

/**
 * Class for handling activate/deactivate/uninstall tasks
 * @since 1.0
 */
class Activation
{
    /**
     *  Activate
     *  @since 1.0
     */
    public static function activate($networkwide)
    {
    }

    /**
     *  Deactivation
     *  @since 1.0
     */
    public static function deactivate()
    {
        $mu_plugin_admin = new MUPluginAdmin();
        $mu_plugin_admin->disablePlugin();
    }

    /**
     *  Uninstall
     *  @since 1.0
     */
    public static function uninstall()
    {
        // Remove MU plugin if still there
        $mu_plugin_admin = new MUPluginAdmin();
        $mu_plugin_admin->disablePlugin();

        // Remove all database entries
        global $wpdb;
        $wpdb->query('delete FROM ' . $wpdb->options . " WHERE option_name like 'unified_%' ");

        // Thats all, all should be clear
        // kk thx bye
    }
}
