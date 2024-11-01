<?php
/*
  Plugin Name: Unified
  Plugin URI: https://daev.tech/unified
  Description: SMTP setup, page cache, WordPress clean up, security http headers, focused on high performance, no bloat and low memory usage.
  Version: 1.2.0
  Author: Daev.tech
  Author URI: https://daev.tech
  Domain Path: /languages
  Text Domain: unified
  License: GPLv3
  License URI: http://www.gnu.org/licenses/gpl-3.0
 */

/**
 * 	Copyright (C) 2018 Daev.tech (email: support@daev.tech)
 *
 * 	This program is free software; you can redistribute it and/or
 * 	modify it under the terms of the GNU General Public License
 * 	as published by the Free Software Foundation; either version 2
 * 	of the License, or (at your option) any later version.
 *
 * 	This program is distributed in the hope that it will be useful,
 * 	but WITHOUT ANY WARRANTY; without even the implied warranty of
 * 	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * 	GNU General Public License for more details.
 *
 * 	You should have received a copy of the GNU General Public License
 * 	along with this program; if not, write to the Free Software
 * 	Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

define('DAEV_UNIFIED_VERSION', '1.2.0');
define('DAEV_UNIFIED_DB_VERSION', '1');
define('DAEV_UNIFIED_NEWEST_MU_VERSION', '1.0.6');

// Set the plugin root dir path and url
define('DAEV_UNIFIED_PLUGIN_DIR', \plugin_dir_path(__FILE__));
define('DAEV_UNIFIED_PLUGIN_URL', \plugins_url('/', __FILE__));

// Load composer autoloader
require_once dirname(__FILE__) . '/vendor/autoload.php';

/**
 *  On plugin activation
 */
function daevUnifiedActivation($networkwide)
{
    \Unified\Utilities\Activation::activate($networkwide);
}
register_activation_hook(__FILE__, 'daevUnifiedActivation');

/**
 *  On plugin deactivation
 */
function daevUnifiedDeactivation()
{
    \Unified\Utilities\Activation::deactivate();
}
register_deactivation_hook(__FILE__, 'daevUnifiedDeactivation');

/**
 *  On plugin uninstall
 */
function daevUnifiedUninstall()
{
    \Unified\Utilities\Activation::uninstall();
}
register_uninstall_hook(__FILE__, 'daevUnifiedUninstall');

/**
 *  Start unified
 */
(new \Unified\UnifiedPlugin())->init();
