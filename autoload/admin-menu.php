<?php

function filter_pages($page)
{
    $default_page_values = [
        'parent_page' => null,
        'sub_page' => null,
    ];

    list('parent_page' => $parent_page, 'sub_page' => $sub_page) = array_merge($default_page_values, $page);

    if ($sub_page && !empty($sub_page)) {
        return remove_submenu_page(
            $parent_page,
            $sub_page
        );
    } else {
        return remove_menu_page(
            $parent_page
        );
    }
}

function whitespace_headless_cms_hide_admin_page($page)
{
    if (defined("WHITESPACE_HEADLESS_CMS_ACTIVATE_DEVELOPER_CAPABILITIES") && WHITESPACE_HEADLESS_CMS_ACTIVATE_DEVELOPER_CAPABILITIES !== FALSE) {

        return whitespace_headless_cms_hide_admin_pages([$page]);
    }
}

function whitespace_headless_cms_hide_admin_pages($pages)
{
    if (defined("WHITESPACE_HEADLESS_CMS_ACTIVATE_DEVELOPER_CAPABILITIES") && WHITESPACE_HEADLESS_CMS_ACTIVATE_DEVELOPER_CAPABILITIES !== FALSE) {

        add_filter("whitespace_headless_cms_hidden_admin_pages", function ($hidden_admin_pages) use ($pages) {
            $hidden_admin_pages = array_merge($hidden_admin_pages, $pages);
            return $hidden_admin_pages;
        });
    }
}

function whitespace_headless_cms_unhide_admin_page($page)
{
    if (defined("WHITESPACE_HEADLESS_CMS_ACTIVATE_DEVELOPER_CAPABILITIES") && WHITESPACE_HEADLESS_CMS_ACTIVATE_DEVELOPER_CAPABILITIES !== FALSE) {

        return whitespace_headless_cms_unhide_admin_pages([$page]);
    }
}
function whitespace_headless_cms_unhide_admin_pages($pages)
{
    if (defined("WHITESPACE_HEADLESS_CMS_ACTIVATE_DEVELOPER_CAPABILITIES") && WHITESPACE_HEADLESS_CMS_ACTIVATE_DEVELOPER_CAPABILITIES !== FALSE) {

        add_filter("whitespace_headless_cms_hidden_admin_pages", function ($hidden_admin_pages) use ($pages) {
            $admin_pages_to_display = [];
            foreach ($hidden_admin_pages as $hidden_admin_page) {
                if (!in_array($hidden_admin_page, $pages)) {
                    $admin_pages_to_display[] = $hidden_admin_page;
                };
            };

            return $admin_pages_to_display;
        });
    }
}



/**
 * ADD FILTER TO HIDE OR SHOW ADMIN PAGES
 */
add_action('admin_init', function () {
    if (defined("WHITESPACE_HEADLESS_CMS_ACTIVATE_DEVELOPER_CAPABILITIES") && WHITESPACE_HEADLESS_CMS_ACTIVATE_DEVELOPER_CAPABILITIES !== FALSE) {

        $hidden_admin_pages = [
            [
                "parent_page" => "tools.php",
            ],
            [
                "parent_page" => "options-general.php",

            ],
            [
                "parent_page" => "graphql",
            ],
            [
                "parent_page" => "themes.php",
                "sub_page" => "themes.php"
            ],
            [
                "parent_page" => "themes.php",
                "sub_page" => "theme-editor.php"
            ],
            [
                "parent_page" => "themes.php",
                "sub_page" => "customize.php?return=" .  urlencode($_SERVER['REQUEST_URI'])
            ],
            [
                "parent_page" => "themes.php",
                "sub_page" => "widgets.php"
            ],
            [
                "parent_page" => "themes.php",
                "sub_page" => "acf-options-header"
            ],
            [
                "parent_page" => "themes.php",
                "sub_page" => "acf-options-footer"
            ],
            [
                "parent_page" => "themes.php",
                "sub_page" => "acf-options-archives"
            ],
            [
                "parent_page" => "themes.php",
                "sub_page" => "acf-options-404"
            ],
            [
                "parent_page" => "themes.php",
                "sub_page" => "acf-options-taxonomies"
            ],
            [
                "parent_page" => "themes.php",
                "sub_page" => "acf-options-post-types"
            ],
            [
                "parent_page" => "themes.php",
                "sub_page" => "acf-options-content-editor"
            ],
            [
                "parent_page" => "themes.php",
                "sub_page" => "acf-options-content"
            ],
            [
                "parent_page" => "themes.php",
                "sub_page" => "acf-options-navigation"
            ],
            [
                "parent_page" => "themes.php",
                "sub_page" => "acf-options-google-translate"
            ],
            [
                "parent_page" => "themes.php",
                "sub_page" => "acf-options-css"
            ]
        ];

        $hidden_admin_pages = apply_filters("whitespace_headless_cms_hidden_admin_pages", $hidden_admin_pages);

        if (!current_user_can('manage_dev_options')) {
            foreach ($hidden_admin_pages as $key => $page) {
                filter_pages($page);
            }
        }
    }
});

/* CREATE USER ROLE DEVELOPER */
add_action('admin_init', function () {
    if (defined("WHITESPACE_HEADLESS_CMS_ACTIVATE_DEVELOPER_CAPABILITIES") && WHITESPACE_HEADLESS_CMS_ACTIVATE_DEVELOPER_CAPABILITIES !== FALSE) {

        $dev = $GLOBALS['wp_roles']->is_role('developer');

        if ($dev) return;

        $dev_cap = array_merge(get_role('administrator')->capabilities, ['manage_dev_options' => true], ["administrator" => true]);

        add_role('developer', __('Developer', 'whitespace-headless-cms'),  $dev_cap);
    }
}, 99);



/* REMOVE SELECTED CAPABILITIES FROM ALL USER ROLES BUT DEVELOPER */
add_action('admin_init', function () {
    if (defined("WHITESPACE_HEADLESS_CMS_ACTIVATE_DEVELOPER_CAPABILITIES") && WHITESPACE_HEADLESS_CMS_ACTIVATE_DEVELOPER_CAPABILITIES !== FALSE) {

        $caps_to_remove = [
            'update_core',
            'update_plugins',
            'update_themes',
            'install_plugins',
            'install_themes',
            'edit_themes',
            'delete_themes',
            'delete_plugins',
            'edit_plugins',
            'manage_options',
            'activate_plugins',
            'export',
            'import',
            'switch_themes',
            'customize',
            'delete_site'
        ];

        global $wp_roles;

        foreach ($wp_roles->roles as $wp_role) {
            $wp_role_name = strtolower($wp_role['name']);

            if ($wp_role_name === "developer") break;

            foreach ($caps_to_remove as $cap) {
                if (isset($wp_role['capabilities'][$cap])) {
                    $role = get_role($wp_role_name);
                    $role->remove_cap($cap);
                }
            }
        }

        $role = get_role('developer');
        foreach ($caps_to_remove as $cap) {
            $role->add_cap($cap);
        }
    }
});


/* Hide acf settings for non developers */
add_filter('acf/settings/show_admin', function () {
    if (defined("WHITESPACE_HEADLESS_CMS_ACTIVATE_DEVELOPER_CAPABILITIES") && WHITESPACE_HEADLESS_CMS_ACTIVATE_DEVELOPER_CAPABILITIES !== FALSE) {
        return current_user_can('manage_dev_options');
    }
});

add_action('whitespace_headless_cms/activate', function () {
    if (defined("WHITESPACE_HEADLESS_CMS_ACTIVATE_DEVELOPER_CAPABILITIES") && WHITESPACE_HEADLESS_CMS_ACTIVATE_DEVELOPER_CAPABILITIES !== FALSE) {

        $user = wp_get_current_user();
        $user->set_role('developer');
    }
});
