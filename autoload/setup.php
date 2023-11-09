<?php

function whitespace_headless_cms_activate() {
  add_option("whitespace_headless_cms_activated", true);
}

add_action("admin_init", function () {
  if (empty(get_option("whitespace_headless_cms_activated"))) {
    return;
  }
  do_action("whitespace_headless_cms/activate");
  delete_option("whitespace_headless_cms_activated");
});

register_activation_hook(
  WHITESPACE_HEADLESS_CMS_PLUGIN_FILE,
  "whitespace_headless_cms_activate",
);
