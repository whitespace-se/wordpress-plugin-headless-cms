<?php

function filter_pages($page, $page_to_compare = null, $function = 'filter')
{
    $default_page_values = [
        'parent_page' => null,
        'sub_page' => null,
        'action' => "hide"
    ];

    list('parent_page' => $parent_page, 'sub_page' => $sub_page, 'action' => $action) = array_merge($default_page_values, $page);

    if ($function == "filter") {
        switch ($action) {
            case "show":
                if ($sub_page && !empty($page_to_compare['sub_page'])) {
                    return $page_to_compare['sub_page'] !== $sub_page;
                } else {
                    return $page_to_compare['parent_page'] !== $parent_page;
                }
            default:
                return $page_to_compare;
        }
    } else if ($function == "hide") {
        switch ($action) {
            case "show":
                return;
            default:
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
    }
}


/**
 * ADD FILTER TO HIDE OR SHOW ADMIN PAGES
 */

add_filter("whitespace_headless_cms_filter_admin_pages", function ($pages = []) {
    $default_pages_to_filter = [
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

    if (!current_user_can('manage_dev_options')) {

        if (isset($pages)) {

            foreach ($pages as $key => $page) {
                $default_pages_to_filter = array_filter($default_pages_to_filter, function ($default_page_to_filter) use ($page) {
                    return filter_pages($page, $default_page_to_filter, 'filter');
                });
            }

            $default_pages_to_filter = array_merge($default_pages_to_filter, $pages);
        }



        foreach ($default_pages_to_filter as $key => $default_page_to_filter) {
            filter_pages($default_page_to_filter, null, 'hide');
        }
    }
}, 10, 1);



/* CREATE USER ROLE DEVELOPER */
add_action('admin_init', function () {

    $dev = $GLOBALS['wp_roles']->is_role('developer');

    if ($dev) return;

    $dev_cap = array_merge(get_role('administrator')->capabilities, ['manage_dev_options' => true], ["administrator" => true]);

    add_role('developer', __('Developer', 'whitespace-headless-cms'),  $dev_cap);
}, 99);



/* REMOVE SELECTED CAPABILITIES FROM ALL USER ROLES BUT DEVELOPER */
add_action('admin_init', function () {

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

        if ($wp_role_name === "developer") return;

        foreach ($caps_to_remove as $cap) {
            if (isset($wp_role['capabilities'][$cap])) {
                $role = get_role($wp_role_name);
                $role->remove_cap($cap);
            }
        }
    }
});


/* Hide acf settings for non developers */
add_filter('acf/settings/show_admin', function () {
    return current_user_can('manage_dev_options');
});
