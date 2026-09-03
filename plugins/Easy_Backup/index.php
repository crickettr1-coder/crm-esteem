<?php

defined('PLUGINPATH') or exit('No direct script access allowed');

/*
  Plugin Name: Easy Backup 
  Description: Hassle-free backups for RISE CRM.
  Version: 1.2
  Requires at least: 3.5
  Author: ClassicCompiler
  Author URL: https://codecanyon.net/user/classiccompiler
 */

//add admin setting menu item
app_hooks()->add_filter('app_filter_admin_settings_menu', function($settings_menu) {
    $settings_menu["plugins"][] = array("name" => "easy_backup", "url" => "easy_backup_settings");
    return $settings_menu;
});

//add setting link to the plugin setting
app_hooks()->add_filter('app_filter_action_links_of_Easy_Backup', function ($action_links_array) {
    $action_links_array = array(
        anchor(get_uri("easy_backup_settings"), app_lang("settings"))
    );

    return $action_links_array;
});

//install dependencies
register_installation_hook("Easy_Backup", function ($item_purchase_code) {
    include PLUGINPATH . "Easy_Backup/install/do_install.php";
});

//uninstallation: remove data from database
register_uninstallation_hook("Easy_Backup", function () {
    $dbprefix = get_db_prefix();
    $db = db_connect('default');

    $sql_query = "DROP TABLE IF EXISTS `" . $dbprefix . "easy_backup_settings`;";
    $db->query($sql_query);
});

//update plugin
use Easy_Backup\Controllers\Easy_Backup_Updates;

register_update_hook("Easy_Backup", function () {
    $update = new Easy_Backup_Updates();
    return $update->index();
});

app_hooks()->add_filter('app_filter_add_folder_to_google_drive_valid_folders_array', function ($folders_array) {
    if (!in_array("easy_backup_files", $folders_array)) {
        array_push($folders_array, "easy_backup_files");
    }
    
    return $folders_array;
});