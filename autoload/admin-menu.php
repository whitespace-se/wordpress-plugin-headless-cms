/* CREATE USER ROLE DEVELOPER */
add_action('admin_init', function() {

$dev = $GLOBALS['wp_roles']->is_role( 'developer' );

if($dev) return;

$dev_cap = array_merge(get_role( 'administrator' )->capabilities, ['devop' => true]);

add_role( 'developer', __('Developer', 'whitespace-headless-cms'), $dev_cap);
});



/* REMOVE SELECTED CAPABILITIES FROM ALL USER ROLES BUT DEVELOPER */
add_action('admin_init', function() {

$caps_to_remove = ['update_core',
'update_plugins',
'update_themes',
'install_plugins',
'install_themes',
'edit_themes',
'delete_themes',
'delete_plugins',
'edit_plugins',
'activate_plugins',
'export',
'import',
'manage_options',
'switch_themes',
'customize',
'delete_site'
];

global $wp_roles;

foreach($wp_roles->roles as $wp_role) {
$wp_role_name = strtolower($wp_role['name']);

if($wp_role_name === "developer") return;

foreach ( $caps_to_remove as $cap ) {
if(isset($wp_role['capabilities'][$cap])) {
$role = get_role($wp_role_name);
$role->remove_cap($cap);
}
}
}


});


/* if current user isn't developer add this custom filter to block them from seeings some pages */
add_action('admin_init', function() {
if ( !current_user_can( 'devop' ) ) {

remove_menu_page("tools.php");

remove_submenu_page(
"themes.php",
"themes.php"
);

// remove_submenu_page(
// "themes.php",
// "customize.php?return=%2Fwp-admin%2Fedit.php%3Fpost_type%3Devent_place"
// );

remove_submenu_page(
"themes.php",
"widgets.php"
);


// add_filter( 'show_admin_bar', '__return_false' );
}

});