<?php
/**
 * Plugin Name:     Plugin Tools Server By YDTB
 * Plugin URI:      https://github.com/roots/clover
 * Description:     WordPress starter plugin
 * Version:         0.0.1
 * Author:          Roots
 * Author URI:      https://roots.io/
 * License:         MIT License
 * Text Domain:     ydtb-plugin-tools-server
 * Domain Path:     /resources/lang
 */


 if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__.'/vendor/autoload.php';

define("YDTB_PTOOLS_SERVER_URL", plugin_dir_url(__FILE__));
define("YDTB_PTOOLS_SERVER_PATH", plugin_dir_path(__FILE__));
define("YDTB_PTOOLS_OPTIONS_SLUG", "pts_settings");

$YDTBServerPlugin = new \PluginToolsServer\Plugin;
$YDTBServerPlugin->register();

add_action('init', [$YDTBServerPlugin, 'boot']);
