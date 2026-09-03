<?php

use Easy_Backup\Libraries\Files_Builder;
use Easy_Backup\Libraries\Database_Builder;

/**
 * get the defined config value by a key
 * @param string $key
 * @return config value
 */
if (!function_exists('get_easy_backup_setting')) {

    function get_easy_backup_setting($key = "") {
        $config = new Easy_Backup\Config\Easy_Backup();

        $setting_value = get_array_value($config->app_settings_array, $key);
        if ($setting_value !== NULL) {
            return $setting_value;
        } else {
            return "";
        }
    }
}

if (!function_exists('easy_backup_process_backup')) {

    function easy_backup_process_backup($download_mode = false) {
        $easy_backup_all_files = get_easy_backup_setting("easy_backup_all_files");
        $easy_backup_database = get_easy_backup_setting("easy_backup_database");
        if (!$easy_backup_all_files && !$easy_backup_database) {
            echo json_encode(array("success" => false, 'message' => app_lang('easy_backup_no_backup_option_selected')));
            exit();
        }

        //execute maximum 300 seconds 
        ini_set('max_execution_time', 300);

        //create unique and secure file name
        $now = date("d-m-Y--h-i-s");
        $backup_full_file_name = get_setting('app_title') . "--" . get_setting("app_version") . "--" . $now . "--" . substr(md5(rand()), 0, 5);
        $backup_full_file_name = preg_replace('/\s+/', '-', $backup_full_file_name);
        $backup_full_file_name = str_replace("’", "-", $backup_full_file_name);
        $backup_full_file_name = str_replace("'", "-", $backup_full_file_name);
        $backup_full_file_name = str_replace("(", "-", $backup_full_file_name);
        $backup_full_file_name = str_replace(")", "-", $backup_full_file_name);

        $backup_zip_file_name = "$backup_full_file_name.zip";
        $backup_sql_file_name = "SQL--$backup_full_file_name.sql";

        //backup to local first
        $target_path = get_easy_backup_setting("easy_backup_backups_file_path");

        //check destination directory. if not found try to create a new one
        if (!is_dir($target_path)) {
            if (!mkdir($target_path, 0777, true)) {
                die('Failed to create file folders.');
            }
            //create a index.html file inside the folder
            copy(getcwd() . "/" . get_setting("system_file_path") . "index.html", $target_path . "index.html");
        }

        //Backup zip of whole folder
        $zip_file_name = $target_path . $backup_zip_file_name;
        $zip = new Files_Builder();
        $res = $zip->open($zip_file_name, \ZipArchive::CREATE);
        if ($res === TRUE) {

            if ($easy_backup_all_files) { //Backup whole RISE
                $zip->add_dir(ROOTPATH, basename(ROOTPATH));
            }

            if ($easy_backup_database) { //Backup database
                $Database_Builder = new Database_Builder();
                $sql_backup = $Database_Builder->build_database_seed();
                $zip->addFromString($backup_sql_file_name, $sql_backup);
            }

            $zip->close();
        }

        //check if the zip is created
        if (!file_exists($zip_file_name)) {
            return false;
        }

        if ($download_mode) {
            //for downloading, return the local backup file reference only
            return $zip_file_name;
        }

        //if google drive or any other third party storage is enabled, upload there and delete local file
        if ((get_setting("enable_google_drive_api_to_upload_file") && get_setting("google_drive_authorized")) || defined('PLUGIN_CUSTOM_STORAGE')) {
            $file_data = move_temp_file(
                $zip_file_name,
                $target_path,
                "easy_backup",
                $zip_file_name,
                $backup_zip_file_name,
                file_get_contents($zip_file_name),
                true
            );

            if (!$file_data) {
                // couldn't upload it
                return false;
            }

            //remove local file after uploading to third party storage
            unlink($zip_file_name);
        }

        return true;
    }
}
