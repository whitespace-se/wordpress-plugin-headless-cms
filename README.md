# Whitespace Headless CMS plugin for Wordpress

Wordpress plugin that turns your site into a headless CMS.

## How to install

If you want to use this plugin as an MU-plugin, first add this to your
composer.json:

```json
{
  "extra": {
    "installer-paths": {
      "path/to/your/mu-plugins/{$name}/": [
        "whitespace-se/wordpress-plugin-headless-cms"
      ]
    }
  }
}
```

Where `path/to/your/mu-plugins` is something like `wp-content/mu-plugins` or
`web/app/mu-plugins`.

Then get the plugin via composer:

```bash
composer require whitespace-se/wordpress-plugin-headless-cms
```

## Features

### Notify CI

Define the `CI_NOTIFY_URLS` constant in you config (e.g. in `wp-config.php`) if
you need to trigger a build job in your CI tool. Example:

```php
define(
  "CI_NOTIFY_URLS",
  "https://ci.example.com/build_hooks/000000000000?cause={DESCRIPTION}",
);
```

The value can be a single URL or multiple URLs either as an array or
comma-separated in a single string. You can also use the
`WhitespaceHeadlessCms/ci_notify_urls` filter to alter the value.

You can use the `{DESCRIPTION}` placeholder to log what post caused the build to
be triggered if the CI tool supports that. You will see something like "Post 123
updated in WordPress" in the CI logs.
