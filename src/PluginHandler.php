<?php

namespace Whitespace\HeadlessCms;

class PluginHandler {
  public const OPTION_NAME = "whitespace_headless_cms";
  public const OPTION_GROUP = "whitespace_headless_cms";
  public const OPTION_SECTIONS = "whitespace-headless-cms";

  public function __construct() {
    add_action("admin_menu", [$this, "onAdminMenu"]);
    add_action("admin_init", [$this, "onAdminInit"]);
  }

  public function onAdminMenu() {
    add_options_page(
      __("Headless", "whitespace-headless-cms"),
      __("Headless settings", "whitespace-headless-cms"),
      "manage_options",
      __("whitespace-headless-cms"),
      [$this, "renderOptionsPage"],
    );
  }
  public function renderOptionsPage(): void {
    ?>
      <div class="wrap">
          <h1><?php _e("Headless settings", "whitespace-headless-cms"); ?></h1>
          <form method="post" action="options.php">
          <?php
          // This prints out all hidden setting fields
          settings_fields(self::OPTION_GROUP);
          do_settings_sections(self::OPTION_SECTIONS);
          submit_button();?>
          </form>
      </div>
      <?php
  }

  public function onAdminInit() {
    register_setting(self::OPTION_GROUP, self::OPTION_NAME, [
      $this,
      "sanitizeOption",
    ]);
  }

  public function sanitizeOption($input) {
    $input = apply_filters("WhitespaceHeadlessCms/sanitize_option", $input);
    return $input;
  }

  public function getOption(string $name = null, $default = null) {
    $option_value = get_option(self::OPTION_NAME, []);
    if ($name) {
      $option_value = $option_value[$name] ?? $default;
    }
    return $option_value;
  }

  public function setOption(string $name, $value): bool {
    $option_value = get_option(self::OPTION_NAME, []);
    $option_value[$name] = $value;
    return update_option(self::OPTION_NAME, $option_value);
  }
}
