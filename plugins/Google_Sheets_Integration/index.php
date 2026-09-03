<?php

defined('PLUGINPATH') or exit('No direct script access allowed');

/*
  Plugin Name: Google Sheets Integration
  Description: Create and manage Google Spreadsheets with your team members and clients inside RISE CRM.
  Version: 1.0
  Requires at least: 3.1
  Author: ClassicCompiler
  Author URL: https://codecanyon.net/user/classiccompiler
 */

use App\Controllers\Security_Controller;

//add menu item to left menu
app_hooks()->add_filter('app_filter_staff_left_menu', 'google_sheets_integration_left_menu');
app_hooks()->add_filter('app_filter_client_left_menu', 'google_sheets_integration_left_menu');

if (!function_exists('google_sheets_integration_left_menu')) {

    function google_sheets_integration_left_menu($sidebar_menu) {
        if (!(get_google_sheets_integration_setting("integrate_google_sheets") && get_google_sheets_integration_setting('google_sheets_authorized'))) {
            return $sidebar_menu;
        }

        $instance = new Security_Controller();
        if ($instance->login_user->user_type === "client" && !get_google_sheets_integration_setting("client_can_access_google_sheets")) {
            return $sidebar_menu;
        }

        $sidebar_menu["google_sheets"] = array(
            "name" => "google_sheets",
            "url" => "google_sheets",
            "class" => "file-plus",
            "position" => 6,
            "badge_class" => "bg-primary"
        );

        return $sidebar_menu;
    }

}

//add integration setting
app_hooks()->add_filter('app_filter_integration_settings_tab', function ($hook_tabs) {
    $hook_tabs[] = array(
        "title" => "Google Sheets",
        "url" => get_uri("google_sheets_integration_settings"),
        "target" => "google-sheets-integration"
    );

    return $hook_tabs;
});

//install dependencies
register_installation_hook("Google_Sheets_Integration", function ($item_purchase_code) {
    include PLUGINPATH . "Google_Sheets_Integration/install/do_install.php";
});

//add setting link to the plugin setting
app_hooks()->add_filter('app_filter_action_links_of_Google_Sheets_Integration', function ($action_links_array) {
    $action_links_array = array(
        anchor(get_uri("settings/integration"), app_lang("settings"))
    );

    if (get_google_sheets_integration_setting("integrate_google_sheets") && get_google_sheets_integration_setting('google_sheets_authorized')) {
        $action_links_array[] = anchor(get_uri("google_sheets"), app_lang("google_sheets"));
    }

    return $action_links_array;
});

//update plugin
use Google_Sheets_Integration\Controllers\Google_Sheets_Integration_Updates;

register_update_hook("Google_Sheets_Integration", function () {
    $update = new Google_Sheets_Integration_Updates();
    return $update->index();
});

//uninstallation: remove data from database
register_uninstallation_hook("Google_Sheets_Integration", function () {
    $dbprefix = get_db_prefix();
    $db = db_connect('default');

    $sql_query = "DROP TABLE IF EXISTS `" . $dbprefix . "google_sheets_integration_settings`;";
    $db->query($sql_query);

    $sql_query = "DROP TABLE IF EXISTS `" . $dbprefix . "google_sheets`;";
    $db->query($sql_query);
});

//show permission in role setting
app_hooks()->add_action('app_hook_role_permissions_extension', function () {
    echo view("Google_Sheets_Integration\Views\settings\permission");
});

//save role setting
app_hooks()->add_filter('app_filter_role_permissions_save_data', function ($permissions) {
    $request = \Config\Services::request();
    $permissions["google_sheets"] = $request->getPost('google_sheets_permission');

    return $permissions;
});

//show client permission setting
app_hooks()->add_action('app_hook_client_permissions_extension', function () {
    echo view("Google_Sheets_Integration\Views\settings\client_permission");
});

//save client permission setting
app_hooks()->add_action('app_hook_client_permissions_save_data', function () {
    $request = \Config\Services::request();
    $client_can_access_google_sheets = $request->getPost("client_can_access_google_sheets");

    $Google_Sheets_Integration_settings_model = new \Google_Sheets_Integration\Models\Google_Sheets_Integration_settings_model();
    $Google_Sheets_Integration_settings_model->save_setting("client_can_access_google_sheets", $client_can_access_google_sheets);
});