<?php

use App\Controllers\Security_Controller;

/**
 * get the defined config value by a key
 * @param string $key
 * @return config value
 */
if (!function_exists('get_google_sheets_integration_setting')) {

    function get_google_sheets_integration_setting($key = "") {
        $config = new Google_Sheets_Integration\Config\Google_Sheets_Integration();

        $setting_value = get_array_value($config->app_settings_array, $key);
        if ($setting_value !== NULL) {
            return $setting_value;
        } else {
            return "";
        }
    }

}

if (!function_exists('can_manage_google_sheets_integration')) {

    function can_manage_google_sheets_integration() {
        $instance = new Security_Controller();
        if ($instance->login_user->is_admin || get_array_value($instance->login_user->permissions, "google_sheets")) {
            return true;
        }
    }

}
