<?php

namespace Google_Sheets_Integration\Config;

use CodeIgniter\Config\BaseConfig;
use Google_Sheets_Integration\Models\Google_Sheets_Integration_settings_model;

class Google_Sheets_Integration extends BaseConfig {

    public $app_settings_array = array();

    public function __construct() {
        $google_sheets_integration_settings_model = new Google_Sheets_Integration_settings_model();

        $settings = $google_sheets_integration_settings_model->get_all_settings()->getResult();
        foreach ($settings as $setting) {
            $this->app_settings_array[$setting->setting_name] = $setting->setting_value;
        }
    }

}
