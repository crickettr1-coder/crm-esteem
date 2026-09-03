<?php

namespace Easy_Backup\Controllers;

use App\Controllers\Security_Controller;

class Easy_Backup_settings extends Security_Controller {

    function __construct() {
        parent::__construct();
        $this->access_only_admin_or_settings_admin();
    }

    function index() {
        return $this->template->rander("Easy_Backup\Views\settings\index");
    }

    function save() {
        $Easy_Backup_settings_model = new \Easy_Backup\Models\Easy_Backup_settings_model();
        $settings = array("easy_backup_all_files", "easy_backup_database");

        foreach ($settings as $setting) {
            $value = $this->request->getPost($setting);
            if (is_null($value)) {
                $value = "";
            }

            $Easy_Backup_settings_model->save_setting($setting, $value);
        }

        echo json_encode(array("success" => true, 'message' => app_lang('settings_updated')));
    }

    function backup() {
        if (easy_backup_process_backup()) {
            echo json_encode(array("success" => true, 'message' => app_lang('easy_backup_backed_up_successfully')));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
        }
    }

    function download() {
        $zip_file_name = easy_backup_process_backup(true);
        if ($zip_file_name) {
            //download now from server's local directory
            //note: the existing method which is used to download file in RISE, isn't working for big files here
            //so we've to use direct url method to download the file
            echo json_encode(array("success" => true, 'zip_file_url' => $zip_file_name));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
        }
    }

    function delete_temp_backup() {
        $this->validate_submitted_data(array(
            "zip_file_url" => "required"
        ));

        $zip_file_name = $this->request->getPost("zip_file_url");

        if (file_exists($zip_file_name)) {
            unlink($zip_file_name);
        }
    }
}
