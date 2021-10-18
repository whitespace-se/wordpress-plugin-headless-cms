<?php

use Whitespace\HeadlessCms\PluginHandler;

class WhitespaceHeadlessCms {
  private static ?PluginHandler $pluginHandler = null;
  public static function init() {
    if (is_null(self::$pluginHandler)) {
      self::$pluginHandler = new PluginHandler();
      add_action("plugins_loaded", function () {
        do_action("WhitespaceHeadlessCms/init", self::$pluginHandler);
      });
    }
  }
  public static function __callStatic($name, $arguments) {
    return self::$pluginHandler->$name(...$arguments);
  }
  public static function getPluginHandler(): PluginHandler {
    return self::$pluginHandler;
  }
}

WhitespaceHeadlessCms::init();
