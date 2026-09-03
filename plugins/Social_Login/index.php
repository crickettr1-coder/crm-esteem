<?php

defined('PLUGINPATH') or exit('No direct script access allowed');

/*
  Plugin Name: Social Login
  Description: Login to your RISE dashboard with one-click.
  Version: 1.0.3
  Requires at least: 3.2
  Author: ClassicCompiler
  Author URL: https://codecanyon.net/user/classiccompiler
 */

//add admin setting menu item
app_hooks()->add_filter('app_filter_admin_settings_menu', function ($settings_menu) {
    $settings_menu["plugins"][] = array("name" => "social_login", "url" => "social_login_settings");
    return $settings_menu;
});

//add login buttons to signin and signup page
app_hooks()->add_filter('app_hook_signin_extension', 'social_login_get_login_buttons');
app_hooks()->add_filter('app_hook_signup_extension', 'social_login_get_login_buttons');

if (!function_exists('social_login_get_login_buttons')) {

    function social_login_get_login_buttons() {
        $google = "";
        if (get_social_login_setting("enable_google_login") && get_social_login_setting("google_login_authorized")) {
            $google = anchor(get_uri("social_login/authorize_google_login"), view("Social_Login\Views\svg_icons\google") . " " . app_lang("social_login_continue_with_google"), array("class" => "btn btn-default w-100 mt20"));
        }

        $facebook = "";
        if (get_social_login_setting("enable_facebook_login") && get_social_login_setting("facebook_login_authorized")) {
            $margin_top_class = $google ? "mt15" : "mt20";
            $facebook = anchor(get_uri("social_login/authorize_facebook_login"), view("Social_Login\Views\svg_icons\\facebook") . " " . app_lang("social_login_continue_with_facebook"), array("class" => "btn btn-default w-100 $margin_top_class"));
        }

        echo $google . $facebook;
    }

}

//install dependencies
register_installation_hook("Social_Login", function ($item_purchase_code) {
    include PLUGINPATH . "Social_Login/install/do_install.php";
});

//add setting link to the plugin setting
app_hooks()->add_filter('app_filter_action_links_of_Social_Login', function ($action_links_array) {
    $action_links_array = array(
        anchor(get_uri("social_login_settings"), app_lang("settings"))
    );

    return $action_links_array;
});

//uninstallation: remove data from database
register_uninstallation_hook("Social_Login", function () {
    $dbprefix = get_db_prefix();
    $db = db_connect('default');

    $sql_query = "DROP TABLE IF EXISTS `" . $dbprefix . "social_login_settings`;";
    $db->query($sql_query);
});

//update plugin
use Social_Login\Controllers\Social_Login_Updates;

register_update_hook("Social_Login", function () {
    $update = new Social_Login_Updates();
    return $update->index();
});
