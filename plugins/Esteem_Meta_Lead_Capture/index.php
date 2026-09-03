<?php
defined('PLUGINPATH') or exit('No direct script access allowed');
helper('esteem_meta_lead_capture_helper');
/* Plugin Name: Esteem Meta Lead Capture
 * Description: Secure Facebook and Instagram Lead Ads capture.
 * Version: 1.0.0
 * Author: Esteem Energy
 */
app_hooks()->add_filter('app_filter_admin_settings_menu', function ($menu) {
    $menu['plugins'][] = array('name' => 'esteem_meta_lead_capture', 'url' => 'esteem_meta_lead_capture_settings');
    return $menu;
});
app_hooks()->add_filter('app_filter_action_links_of_Esteem_Meta_Lead_Capture', function ($links) {
    return array(anchor(get_uri('esteem_meta_lead_capture_settings'), app_lang('settings')));
});
register_installation_hook('Esteem_Meta_Lead_Capture', function () {
    include PLUGINPATH . 'Esteem_Meta_Lead_Capture/install/do_install.php';
});
register_uninstallation_hook('Esteem_Meta_Lead_Capture', function () {
    $db = db_connect('default');
    foreach (array('meta_lead_capture_audit', 'meta_lead_capture_mapping', 'meta_lead_capture_round_robin', 'meta_lead_capture_settings') as $table) {
        $db->query('DROP TABLE IF EXISTS `' . $db->getPrefix() . $table . '`');
    }
});
