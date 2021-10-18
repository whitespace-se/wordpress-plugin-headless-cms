<?php

/**
 * Plugin Name: Whitespace Headless CMS
 * Plugin URI: -
 * Description: Turns your Wordpress site into a headless CMS
 * Version: 0.1.1
 * Author: Whitespace
 * Author URI: https://www.whitespace.se/
 * Text Domain: whitespace-headless-cms
 */

define("WHITESPACE_HEADLESS_CMS_PLUGIN_FILE", __FILE__);
define("WHITESPACE_HEADLESS_CMS_PATH", dirname(__FILE__));
define(
  "WHITESPACE_HEADLESS_CMS_AUTOLOAD_PATH",
  WHITESPACE_HEADLESS_CMS_PATH . "/autoload",
);

add_action("init", function () {
  $path = plugin_basename(dirname(__FILE__)) . "/languages";
  load_plugin_textdomain("whitespace-headless-cms", false, $path);
  load_muplugin_textdomain("whitespace-headless-cms", $path);
});

array_map(static function () {
  include_once func_get_args()[0];
}, glob(WHITESPACE_HEADLESS_CMS_AUTOLOAD_PATH . "/*.php"));
