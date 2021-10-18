<?php

use Whitespace\HeadlessCms\CiNotifier;

add_action("WhitespaceHeadlessCms/init", function ($pluginHandler) {
  new CiNotifier($pluginHandler);
});
