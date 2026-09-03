<?php

/* Don't change or add any new config in this file */

namespace Social_Login\Config;

use CodeIgniter\Config\BaseConfig;
use Social_Login\Models\Social_Login_settings_model;

class Social_Login extends BaseConfig {

    public $app_settings_array = array();

    public function __construct() {
        $social_login_settings_model = new Social_Login_settings_model();

        $settings = $social_login_settings_model->get_all_settings()->getResult();
        foreach ($settings as $setting) {
            $this->app_settings_array[$setting->setting_name] = $setting->setting_value;
        }
    }

}
