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
        $overriden = defined("CI_NOTIFY_URLS") && !is_null(CI_NOTIFY_URLS); ?>
        <textarea
          id="ci_notify_urls"
          name="<?php echo $this->pluginHandler
            ::OPTION_NAME; ?>[ci_notify_urls]"
          rows="5"
          cols="40"
          style="width: 100%;"
          <?php if ($overriden) {
            echo "disabled";
          } ?>
        ><?php echo htmlspecialchars(
          implode("\n", $this->pluginHandler->getOption("ci_notify_urls", [])),
          ENT_NOQUOTES,
        ); ?></textarea>

        <div class="description">
          <p><?php _e(
            "URLs that will be requested each time a post is updated. One per line.",
            "whitespace-headless-cms",
          ); ?></p>
          <p><?php _e(
            "Available replacements",
            "whitespace-headless-cms",
          ); ?>:</p>
          <ul>
            <li><code>{DESCRIPTION}</code> &ndash; <?php _e(
              "The reason for the notification, e.g. \"Post 42 updated in WordPress\"",
              "whitespace-headless-cms",
            ); ?></li>
            <li><code>{BLOG_ID}</code> &ndash; <?php _e(
              "Blog ID (for multisites)",
              "whitespace-headless-cms",
            ); ?></li>
            <li><code>{BLOG_DOMAIN}</code> &ndash; <?php _e(
              "Blog domain (for multisites)",
              "whitespace-headless-cms",
            ); ?></li>
            <li><code>{BLOG_PATH}</code> &ndash; <?php _e(
              "Blog path without leading or trailing slash (for multisites)",
              "whitespace-headless-cms",
            ); ?></li>
          </ul>
        </div>
        <?php
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
    if (is_array($input)) {
      return $input;
    }
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
      $this->notify(
        "Post {$post_id} ({$post->post_type}) updated in WordPress",
      );
    }
  }

  public function notify($description): void {
    $ci_notify_urls = $this->getNotificationUrls();
    if (empty($ci_notify_urls)) {
      return;
    }
    $replacements = [];
    $replacements["DESCRIPTION"] = $description;
    if (is_multisite()) {
      $blog_details = get_blog_details();
      $replacements["BLOG_ID"] = $blog_details->blog_id;
      $replacements["BLOG_DOMAIN"] = $blog_details->domain;
      $replacements["BLOG_PATH"] = trim($blog_details->path, "/");
    }
    foreach ($ci_notify_urls as $url) {
      foreach ($replacements as $key => $value) {
        $url = str_replace("{" . $key . "}", urlencode($value), $url);
      }
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
