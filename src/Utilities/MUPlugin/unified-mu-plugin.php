<?php

/**
 * Plugin Name: Unified MU plugin
 * Plugin URI:  https://daev.tech/unified
 * Description: Optimizing site performance and compatibility for Unified functions
 * Author:      Daev
 * Author URI:  https://daev.tech
 * Version:     1.0.6
 */

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

define('DAEV_UNIFIED_MU_VERSION', '1.0.6');

// Return if it is frontend or installing
if (is_admin() || wp_installing()) {
    return;
}
// Also return if Unified plugin is not active
if (!in_array('unified/unified.php', (array) get_option('active_plugins', []), true)) {
    return;
}

// Guess the location of plugins
if (defined('WP_PLUGIN_DIR')) {
    $plugins_location = trailingslashit(WP_PLUGIN_DIR);
} elseif (defined('WP_CONTENT_DIR')) {
    $plugins_location = trailingslashit(WP_CONTENT_DIR) . 'plugins/';
} else {
    $plugins_location = trailingslashit(dirname(dirname(__FILE__))) . 'plugins/';
}

// Load the MU handler class of Unified, if found
$unified_mu_class_location = $plugins_location . "unified/src/Utilities/MUPlugin/MUPlugin.php";
if (!file_exists($unified_mu_class_location)) {
    return;
}
include_once($unified_mu_class_location);
if (class_exists("\Unified\Utilities\MUPlugin\MUPlugin")) {
    new \Unified\Utilities\MUPlugin\MUPlugin($plugins_location . 'unified');
}
