<?php
defined('PLUGINPATH') or exit('No direct script access allowed');

helper('esteem_email_marketing');
if (is_file(PLUGINPATH . 'Esteem_Email_Marketing/Config/Routes.php')) {
    include PLUGINPATH . 'Esteem_Email_Marketing/Config/Routes.php';
}

/*
  Plugin Name: Esteem Email Marketing
  Description: Consent-aware email campaigns, templates, segments and queued automation.
  Version: 1.0.0
  Requires at least: 3.7
  Author: Esteem Energy
 */

app_hooks()->add_filter('app_filter_admin_settings_menu', function ($menu) {
    $menu['plugins'][] = ['name' => 'email_marketing', 'url' => 'email_marketing'];
    return $menu;
});
app_hooks()->add_filter('app_filter_action_links_of_Esteem_Email_Marketing', function ($links) {
    return [anchor(get_uri('email_marketing'), 'Email Marketing')];
});
app_hooks()->add_filter('app_filter_client_details_ajax_tab', function ($tabs, $id) { $tabs[]=['title'=>'Marketing Consent','url'=>get_uri('email_marketing/consent/client/'.$id),'target'=>'email-marketing-consent-client']; return $tabs; });
app_hooks()->add_filter('app_filter_lead_details_ajax_tab', function ($tabs, $id) { $tabs[]=['title'=>'Marketing Consent','url'=>get_uri('email_marketing/consent/lead/'.$id),'target'=>'email-marketing-consent-lead']; return $tabs; });
register_installation_hook('Esteem_Email_Marketing', function () {
    include PLUGINPATH . 'Esteem_Email_Marketing/install/do_install.php';
});
register_uninstallation_hook('Esteem_Email_Marketing', function () {
    $db = db_connect('default'); $p = get_db_prefix();
    foreach (['email_test_send_log','email_campaign_status_history','email_automation_audit','email_automation_queue','email_automation_rules','email_campaign_recipients','email_campaigns','email_marketing_templates','marketing_consent','email_suppressions','email_marketing_settings'] as $table) $db->query("DROP TABLE IF EXISTS `{$p}{$table}`");
});

app_hooks()->add_action('app_hook_after_cron_run', function () {
    (new \Esteem_Email_Marketing\Libraries\Email_Queue())->process((int)email_marketing_setting('batch_size', '25'));
    (new \Esteem_Email_Marketing\Libraries\Automation())->daily_no_response();
});
register_data_insert_hook(function($data){(new \Esteem_Email_Marketing\Libraries\Automation())->event($data,'insert');});
register_data_update_hook(function($data){(new \Esteem_Email_Marketing\Libraries\Automation())->event($data,'update');});
