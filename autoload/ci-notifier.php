<?php

use Whitespace\HeadlessCms\CiNotifier;

add_action("WhitespaceHeadlessCms/init", function ($pluginHandler) {
  $ci_notifier = new CiNotifier($pluginHandler);
});
