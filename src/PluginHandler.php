<?php

namespace Whitespace\HeadlessCms;

class PluginHandler {
  public const OPTION_NAME = "whitespace_headless_cms";

  public function __construct() {
  }

  public function getOption(string $name = null, mixed $default = null): mixed {
    $option_value = get_option(self::OPTION_NAME, []);
    if ($name) {
      $option_value = $option_value[$name] ?? $default;
    }
    return $option_value;
  }

  public function setOption(string $name, mixed $value): bool {
    $option_value = get_option(self::OPTION_NAME, []);
    $option_value[$name] = $value;
    return update_option(self::OPTION_NAME, $option_value);
  }
}
