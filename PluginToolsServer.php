<?php
/**
 * Plugin Name:     Plugin Tools Server By YDTB
 * Plugin URI:      https://github.com/roots/clover
 * Description:     WordPress starter plugin
 * Version:         0.0.1
 * Author:          Roots
 * Author URI:      https://roots.io/
 * License:         MIT License
 * Text Domain:     clover
 * Domain Path:     /resources/lang
 */


 if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__.'/vendor/autoload.php';

define("YDTB_PTOOLS_URL", plugin_dir_url(__FILE__));
define("YDTB_PTOOLS_PATH", plugin_dir_path(__FILE__));

$YDTBServerPlugin = new \PluginTools\Plugin;
$YDTBServerPlugin->register();

add_action('init', [$YDTBServerPlugin, 'boot']);
