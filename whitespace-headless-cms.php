<?php
/**
 * Plugin Name: Whitespace Headless CMS
 * Plugin URI: -
 * Description: Turns your Wordpress site into a headless CMS
 * Version: 0.1.0
 * Author: Whitespace
 * Author URI: https://www.whitespace.se/
 */

namespace WhitespaceHeadlessCms;

/**
 * Notify CI tools when updating posts
 */
add_filter("WhitespaceHeadlessCms/ci_notify_urls", function ($endpoints) {
  if (defined("CI_NOTIFY_URLS") && !empty(CI_NOTIFY_URLS)) {
    $endpoints = CI_NOTIFY_URLS;
  }
  if (!is_array($endpoints)) {
    $endpoints = explode(",", CI_NOTIFY_URLS);
  }
  return $endpoints;
});

function ci_notify_urls() {
  return apply_filters("WhitespaceHeadlessCms/ci_notify_urls", []);
}

add_action(
  "save_post",
  function ($post_id, $post) {
    $ci_notify_urls = ci_notify_urls();
    if (empty($ci_notify_urls)) {
      return;
    }
    if (!(wp_is_post_revision($post) || wp_is_post_autosave($post))) {
      foreach ($ci_notify_urls as $url) {
        $url = str_replace(
          "{DESCRIPTION}",
          urlencode("Post {$post_id} updated in WordPress"),
          $url,
        );
        $user = parse_url($url, PHP_URL_USER);
        $pass = parse_url($url, PHP_URL_PASS);
        // error_log(var_export(["ci notify" => $url], true));
        $curl_refresh_gatsby = curl_init($url);
        curl_setopt($curl_refresh_gatsby, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl_refresh_gatsby, CURLOPT_RETURNTRANSFER, true);
        if ($user && $pass) {
          curl_setopt($curl_refresh_gatsby, CURLOPT_USERPWD, "$user:$pass");
          curl_setopt($curl_refresh_gatsby, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        }
        $response = curl_exec($curl_refresh_gatsby);
        // if ($error = curl_error($curl_refresh_gatsby)) {
        //   error_log(var_export(["curl error on ci notify" => $error], true));
        // } else {
        //   error_log(
        //     var_export(["curl response on ci notify" => $response], true)
        //   );
        // }
      }
    }
  },
  10,
  2,
);

/**
 * Disable comments
 */
add_action("admin_init", function () {
  // Redirect any user trying to access comments page
  global $pagenow;

  if ($pagenow === "edit-comments.php") {
    wp_redirect(admin_url());
    exit();
  }

  // Remove comments metabox from dashboard
  remove_meta_box("dashboard_recent_comments", "dashboard", "normal");

  // Disable support for comments and trackbacks in post types
  foreach (get_post_types() as $post_type) {
    if (post_type_supports($post_type, "comments")) {
      remove_post_type_support($post_type, "comments");
      remove_post_type_support($post_type, "trackbacks");
    }
  }
  if (is_admin_bar_showing()) {
    remove_action("admin_bar_menu", "wp_admin_bar_comments_menu", 60);
  }
});

// Close comments on the front-end
add_filter("comments_open", "__return_false", 20, 2);
add_filter("pings_open", "__return_false", 20, 2);

// Hide existing comments
add_filter("comments_array", "__return_empty_array", 10, 2);

// Remove comments page in menu
add_action("admin_menu", function () {
  remove_menu_page("edit-comments.php");
});

// Remove comments links from admin bar
add_action("init", function () {
  if (is_admin_bar_showing()) {
    remove_action("admin_bar_menu", "wp_admin_bar_comments_menu", 60);
  }
});

/*
 * Remove footer text
 */
add_filter("admin_footer_text", "");

/*
 * Disable json rest api if WP GraphQL is enabled
 */
if (is_plugin_active("wp-grahql")) {
  add_filter("json_enabled", "__return_false");
  add_filter("json_jsonp_enabled", "__return_false");
}
