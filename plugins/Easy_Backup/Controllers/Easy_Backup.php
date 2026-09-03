<?php

namespace Easy_Backup\Controllers;

use App\Controllers\App_Controller;

class Easy_Backup extends App_Controller {

    function __construct() {
        parent::__construct();
    }

    function index() {
        $Easy_Backup_settings_model = new \Easy_Backup\Models\Easy_Backup_settings_model();
        $current_time = strtotime(get_current_utc_time());
        $Easy_Backup_settings_model->save_setting("last_cron_job_time", $current_time);
        
        if (easy_backup_process_backup()) {
            echo json_encode(array("success" => true, 'message' => app_lang('easy_backup_backed_up_successfully')));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
        }
    }

}
