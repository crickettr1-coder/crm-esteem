<?php

namespace Google_Sheets_Integration\Controllers;

use App\Controllers\Security_Controller;
use Google_Sheets_Integration\Libraries\Google_Sheets_Integration;

class Google_Sheets_Integration_settings extends Security_Controller {

    protected $Google_Sheets_Integration_settings_model;

    function __construct() {
        parent::__construct();
        $this->access_only_admin_or_settings_admin();
        $this->Google_Sheets_Integration_settings_model = new \Google_Sheets_Integration\Models\Google_Sheets_Integration_settings_model();
    }

    function index() {
        return $this->template->view("Google_Sheets_Integration\Views\settings\integration");
    }

    function save() {
        $settings = array("integrate_google_sheets", "google_sheets_client_id", "google_sheets_client_secret");

        $integrate_google_sheets = $this->request->getPost("integrate_google_sheets");

        foreach ($settings as $setting) {
            $value = $this->request->getPost($setting);
            if (is_null($value)) {
                $value = "";
            }

            //if user change credentials, flag it as unauthorized
            if (get_google_sheets_integration_setting('google_sheets_authorized') && ($setting == "google_sheets_client_id" || $setting == "google_sheets_client_secret") && $integrate_google_sheets && get_google_sheets_integration_setting($setting) != $value) {
                $this->Google_Sheets_Integration_settings_model->save_setting('google_sheets_authorized', "0");
            }

            $this->Google_Sheets_Integration_settings_model->save_setting($setting, $value);
        }

        echo json_encode(array("success" => true, 'message' => app_lang('settings_updated')));
    }

    //authorize
    function authorize_google_sheets() {
        $Google_Sheets_Integration = new Google_Sheets_Integration();
        $Google_Sheets_Integration->authorize();
    }

    //get access token and save
    function save_access_token() {
        if (!empty($_GET)) {
            $Google_Sheets_Integration = new Google_Sheets_Integration();
            $Google_Sheets_Integration->save_access_token(get_array_value($_GET, 'code'));
            app_redirect("settings/integration");
        }
    }
}
