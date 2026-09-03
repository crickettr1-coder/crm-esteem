<?php

namespace Easy_Backup\Config;

use CodeIgniter\Config\BaseConfig;
use Easy_Backup\Models\Easy_Backup_settings_model;

class Easy_Backup extends BaseConfig {

    public $app_settings_array = array(
        "easy_backup_backups_file_path" => PLUGIN_URL_PATH. "Easy_Backup/files/easy_backup_files/"
    );

    public function __construct() {
        $easy_backup_settings_model = new Easy_Backup_settings_model();

        $settings = $easy_backup_settings_model->get_all_settings()->getResult();
        foreach ($settings as $setting) {
            $this->app_settings_array[$setting->setting_name] = $setting->setting_value;
        }
    }

}
