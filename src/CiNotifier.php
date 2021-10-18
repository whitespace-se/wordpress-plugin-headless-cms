<?php

namespace Whitespace\HeadlessCms;

class CiNotifier {
  private $pluginHandler;

  public function __construct(PluginHandler $plugin_handler) {
    $this->pluginHandler = $plugin_handler;

    add_action("save_post", [$this, "onSavePost"], 10, 2);
  }

  private static function splitUserInput($input, $delimiter = "") {
    if (empty($input)) {
      return [];
    }
    preg_match_all(
      "/\s*([^\R{$delimiter}\s][^\R{$delimiter}]*?)\s*(?:[\R{$delimiter}]|$)/x",
      $input,
      $matches,
    );
    return $matches[0] ?? [];
  }

  public function getNotificationUrls(): array {
    if (defined("CI_NOTIFY_URLS")) {
      $endpoints = constant("CI_NOTIFY_URLS");
      if (!is_array($endpoints)) {
        $endpoints = self::splitUserInput($endpoints, ",");
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
