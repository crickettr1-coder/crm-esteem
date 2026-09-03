<?php

/**
 * get the defined config value by a key
 * @param string $key
 * @return config value
 */
if (!function_exists('get_social_login_setting')) {

    function get_social_login_setting($key = "") {
        $config = new Social_Login\Config\Social_Login();

        $setting_value = get_array_value($config->app_settings_array, $key);
        if ($setting_value !== NULL) {
            return $setting_value;
        } else {
            return "";
        }
    }

}
