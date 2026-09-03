<?php

namespace Social_Login\Controllers;

use App\Controllers\Security_Controller;

class Social_Login_settings extends Security_Controller {

    protected $Social_Login_settings_model;

    function __construct() {
        parent::__construct();
        $this->access_only_admin_or_settings_admin();
        $this->Social_Login_settings_model = new \Social_Login\Models\Social_Login_settings_model();
    }

    function index() {
        return $this->template->rander("Social_Login\Views\settings\index");
    }

    function google() {
        return $this->template->view("Social_Login\Views\settings\google");
    }

    function save_google_settings() {
        $settings = array("enable_google_login", "google_login_client_id", "google_login_client_secret");

        $enable_google_login = $this->request->getPost("enable_google_login");

        foreach ($settings as $setting) {
            $value = $this->request->getPost($setting);
            if (is_null($value)) {
                $value = "";
            }

            //if user change credentials, flag google login as unauthorized
            if (get_social_login_setting("google_login_authorized") && ($setting == "google_login_client_id" || $setting == "google_login_client_secret") && $enable_google_login && get_social_login_setting($setting) != $value) {
                $this->Social_Login_settings_model->save_setting("google_login_authorized", "0");
            }

            $this->Social_Login_settings_model->save_setting($setting, $value);
        }

        echo json_encode(array("success" => true, 'message' => app_lang('settings_updated')));
    }

    function facebook() {
        return $this->template->view("Social_Login\Views\settings\\facebook");
    }

    function save_facebook_settings() {
        $settings = array("enable_facebook_login", "facebook_login_app_id", "facebook_login_app_secret");

        $enable_facebook_login = $this->request->getPost("enable_facebook_login");

        foreach ($settings as $setting) {
            $value = $this->request->getPost($setting);
            if (is_null($value)) {
                $value = "";
            }

            //if user change credentials, flag facebook login as unauthorized
            if (get_social_login_setting("facebook_login_authorized") && ($setting == "facebook_login_app_id" || $setting == "facebook_login_app_secret") && $enable_facebook_login && get_social_login_setting($setting) != $value) {
                $this->Social_Login_settings_model->save_setting("facebook_login_authorized", "0");
            }

            $this->Social_Login_settings_model->save_setting($setting, $value);
        }

        echo json_encode(array("success" => true, 'message' => app_lang('settings_updated')));
    }

}
