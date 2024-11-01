<?php

/**
 * Bootstrap Unified plugin
 */

namespace Unified;

use Unified\Modules\Caching\CachingModule;
use Unified\Modules\Clean\CleanModule;
use Unified\Modules\Email\EmailModule;
use Unified\Modules\Security\SecurityModule;
use Unified\Pages\ConfigurationPage;
use Unified\Pages\EmailLog;
use Unified\Pages\LicensingPage;
use Unified\Utilities\AsyncRequest\AsyncRequestHandler;
use Unified\Utilities\CommonFunctions;
use Unified\Utilities\Configuration;
use Unified\Utilities\JSData\PageHeaderData;
use Unified\Utilities\Licensing\Licensing;
use Unified\Utilities\MUPlugin\MUPluginAdmin;

class UnifiedPlugin
{
    /**
     *  Initialize unified plugin
     */
    public function init()
    {
        // Only load backend stuff when needed
        if (is_admin()) {
            $this->loadBackendAdmin();
        }

        // Make sure MU plugin is loaded
        $mu_plugin_admin = new MUPluginAdmin();
        $mu_plugin_admin->checkNeedsUpdate();

        // Load features, but only based on what is enabled, to optimize performance
        $configuration = Configuration::getInstance();
        if (!$configuration->get('global_enabled')) {
            return;
        }

        // Caching module
        (new CachingModule())->init();

        // Email module
        (new EmailModule())->init();

        // Cleaning module
        (new CleanModule())->init();

        // Security module
        (new SecurityModule())->init();
    }

    /**
     *  Load admin related functions (menus,etc)
     */
    private function loadBackendAdmin()
    {
        $this->loadTextdomain();
        $this->addMenusToBackend();

        // Do for unified related pages only
        if (strpos($_SERVER['REQUEST_URI'], 'page=unified_') !== false) {
            // Add styles and scripts only to relevant pages
            $this->addStylesAndScripts();
        }

        // Async handlers
        (new AsyncRequestHandler())->init();

        // License check, if pro
        if (CommonFunctions::isPremiumVersion()) {
            $licensing = new Licensing();
            $licensing->verifyLicense();
        }
    }

    /**
     * Load text domain
     */
    private function loadTextdomain()
    {
        add_action(
            'init',
            function () {
                load_plugin_textdomain('unified', false, 'unified/languages');
            }
        );
    }

    /**
     * Add menu to backend
     */
    private function addMenusToBackend()
    {
        add_action(
            'admin_menu',
            function () {
                $configuration = Configuration::getInstance();
                add_menu_page('Unified', 'Unified', 'manage_options', 'unified_menu', [new ConfigurationPage(), 'render'], 'dashicons-admin-generic', 76);
                add_submenu_page('unified_menu', __('Setup', 'unified'), __('Setup', 'unified'), 'manage_options', 'unified_menu', [new ConfigurationPage(), 'render']);
                if ($configuration->get('email_log_list')) {
                    add_submenu_page('unified_menu', __('Email log', 'unified'), __('Email log', 'unified'), 'manage_options', 'unified_email_log', [new EmailLog(), 'render']);
                }
                if (CommonFunctions::isPremiumVersion()) {
                    add_submenu_page('unified_menu', __('Licensing', 'unified'), __('Licensing', 'unified'), 'manage_options', 'unified_licensing', [new LicensingPage(), 'render']);
                }
            }
        );
    }

    /**
     * Add CSS and JS to backend
     */
    private function addStylesAndScripts()
    {
        // Admin scripts
        add_action(
            'admin_enqueue_scripts',
            function ($hook) {
                if (strpos($hook, 'unified') > -1) {
                    $commonfunctions = CommonFunctions::getInstance();
                    wp_enqueue_script('unified_admin', $commonfunctions->getAssetUrl('unified.js'), ['wp-i18n'], DAEV_UNIFIED_VERSION, true);
                    wp_set_script_translations('unified_admin', 'unified', DAEV_UNIFIED_PLUGIN_DIR . 'languages');

                    // Load page header data
                    (new PageHeaderData())->load();
                }
            }
        );

        // Admin styles
        add_action('admin_enqueue_scripts', function ($hook) {
            if (strpos($hook, 'unified') > -1) {
                $commonfunctions = CommonFunctions::getInstance();
                wp_enqueue_style('unified_admin', $commonfunctions->getAssetUrl('unified.css'), [], DAEV_UNIFIED_VERSION);
            }
        });
    }
}
