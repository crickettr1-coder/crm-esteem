<?php

defined('PLUGINPATH') or exit('No direct script access allowed');

helper("esteem_lead_automation_helper");

/*
  Plugin Name: Esteem Lead Automation
  Description: Creates one assigned follow-up task when a new lead is added.
  Version: 1.0.0
  Requires at least: 3.5
  Author: Esteem Energy
 */

app_hooks()->add_filter('app_filter_admin_settings_menu', function ($settings_menu) {
    $settings_menu["plugins"][] = array(
        "name" => "esteem_lead_automation",
        "url" => "esteem_lead_automation_settings"
    );
    return $settings_menu;
});

app_hooks()->add_filter('app_filter_action_links_of_Esteem_Lead_Automation', function ($action_links_array) {
    return array(anchor(get_uri("esteem_lead_automation_settings"), app_lang("settings")));
});

register_installation_hook("Esteem_Lead_Automation", function () {
    include PLUGINPATH . "Esteem_Lead_Automation/install/do_install.php";
});

register_uninstallation_hook("Esteem_Lead_Automation", function () {
    $db = db_connect('default');
    $dbprefix = get_db_prefix();
    $db->query("DROP TABLE IF EXISTS `" . $dbprefix . "lead_automation_audit`");
    $db->query("DROP TABLE IF EXISTS `" . $dbprefix . "lead_automation_settings`");
});

register_data_insert_hook(function ($hook_data) {
    $automation = new \Esteem_Lead_Automation\Libraries\Lead_Followup_Automation();
    $automation->handle_insert($hook_data);
});
