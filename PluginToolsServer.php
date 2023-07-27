<?php
/**
 * Plugin Name:     Plugin Tools Server By YDTB
 * Plugin URI:      https://github.com/roots/clover
 * Description:     Plugin Tools Server By YDTB
 * Version:         0.0.14
 * Author:          John Kraczek
 * Author URI:      https://github.com/johnkraczek
 * License:         GPLv2 or later
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
