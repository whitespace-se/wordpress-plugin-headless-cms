<?php

namespace Whitespace\HeadlessCms;

class CiNotifier {
  private $pluginHandler;

  public const OPTION_SECTION = "ci-notifier";

  public function __construct(PluginHandler $plugin_handler) {
    $this->pluginHandler = $plugin_handler;

    add_action("save_post", [$this, "onSavePost"], 10, 2);
    add_action("admin_init", [$this, "onAdminInit"]);
    add_filter("WhitespaceHeadlessCms/sanitize_option", [
      $this,
      "sanitizeOption",
    ]);
  }

  public function onAdminInit() {
    add_settings_section(
      self::OPTION_SECTION,
      __("Headless settings", "whitespace-headless-cms"),
      null,
      $this->pluginHandler::OPTION_SECTIONS,
    );

    add_settings_field(
      "ci_notify_urls",
      __("CI Notify URLs", "whitespace-headless-cms"),
      function () {
        printf(
          '<textarea id="%s" name="%s[%s]" rows="5" cols="40" placeholder="%s">%s</textarea>',
          "ci_notify_urls",
          $this->pluginHandler::OPTION_NAME,
          "ci_notify_urls",
          esc_attr(implode('\n', $this->getNotificationUrls())),
          esc_attr(
            implode(
              '\n',
              $this->pluginHandler->getOption("ci_notify_urls", []),
            ),
          ),
        );
      },
      $this->pluginHandler::OPTION_SECTIONS,
      self::OPTION_SECTION,
    );
  }

  public function sanitizeOption($input) {
    $input["ci_notify_urls"] = isset($input["ci_notify_urls"])
      ? $this->splitUserInput($input["ci_notify_urls"])
      : [];
    return $input;
  }

  private static function splitUserInput($input) {
    if (empty($input)) {
      return [];
    }
    preg_match_all('/([^,\s][^,]+?)\s*(?:,|$)/m', $input, $matches);
    return $matches[1] ?? [];
  }

  public function getNotificationUrls(): array {
    if (defined("CI_NOTIFY_URLS") && !is_null(CI_NOTIFY_URLS)) {
      $endpoints = CI_NOTIFY_URLS;
      if (!is_array($endpoints)) {
        $endpoints = self::splitUserInput($endpoints);
      }
    } else {
      $endpoints = $this->pluginHandler->getOption("ci_notify_urls", []);
    }
    $endpoints = apply_filters(
      "WhitespaceHeadlessCms/ci_notify_urls",
      $endpoints,
    );
    return $endpoints;
  }

  public function setNotificationUrls(array $urls): bool {
    return $this->pluginHandler->setOption("ci_notify_urls", $urls);
  }

  public function onSavePost($post_id, $post): void {
    if (!wp_is_post_revision($post) && !wp_is_post_autosave($post)) {
      $this->notify("Post {$post_id} updated in WordPress");
    }
  }

  public function notify($description): void {
    $ci_notify_urls = $this->getNotificationUrls();
    if (empty($ci_notify_urls)) {
      return;
    }
    foreach ($ci_notify_urls as $url) {
      $url = str_replace("{DESCRIPTION}", urlencode($description), $url);
      $user = parse_url($url, PHP_URL_USER);
      $pass = parse_url($url, PHP_URL_PASS);
      // error_log(var_export(["ci notify" => $url], true));
      $handle = curl_init($url);
      curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
      if ($user && $pass) {
        curl_setopt($handle, CURLOPT_USERPWD, "$user:$pass");
        curl_setopt($handle, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
      }
      $response = curl_exec($handle);
      // if ($error = curl_error($handle)) {
      //   error_log(var_export(["curl error on ci notify" => $error], true));
      // } else {
      //   error_log(
      //     var_export(["curl response on ci notify" => $response], true)
      //   );
      // }
    }
  }
}
