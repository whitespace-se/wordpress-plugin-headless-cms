<?php

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
add_filter("admin_footer_text", function() {});

/*
 * Disable json rest api if WP GraphQL is enabled
 */
add_filter("json_enabled", function ($enabled) {
  return $enabled && !is_plugin_active("wp-grahql");
});
add_filter("json_jsonp_enabled", function ($enabled) {
  return $enabled && !is_plugin_active("wp-grahql");
});

/*
 * Enable GraphQL introspection
 */
add_filter(
  "graphql_get_setting_section_field_value",
  function ($value, $default, $option_name) {
    if ($option_name == "public_introspection_enabled") {
      $value = true;
    }
    return $value;
  },
  10,
  3,
);
