<?php

namespace Social_Login\Controllers;

use App\Controllers\Security_Controller;
use Social_Login\Libraries\Social_Login_Google;
use Social_Login\Libraries\Social_Login_Facebook;

class Social_Login extends Security_Controller {

    private $Social_Login_settings_model;
    private $Social_Login_model;
    private $social_login_google;
    private $social_login_facebook;

    function __construct() {
        parent::__construct(false);
        $this->Social_Login_settings_model = new \Social_Login\Models\Social_Login_settings_model();
        $this->Social_Login_model = new \Social_Login\Models\Social_Login_model();
        $this->social_login_google = new Social_Login_Google();
        $this->social_login_facebook = new Social_Login_Facebook();
    }

    function index() {
        app_redirect("social_login/authorize_google_login");
    }

    function authorize_google_login() {
        $this->social_login_google->authorize();
    }

    function authenticate_google_login() {
        if (!empty($_GET)) {
            $google_user_info = $this->social_login_google->authenticate_google_login(get_array_value($_GET, 'code'));
            $email = get_array_value($google_user_info, "email");
            if (!($email && filter_var($email, FILTER_VALIDATE_EMAIL))) {
                show_404();
            }

            //1. check if it's from settings and already a login exists
            $this->is_login_exists("google");

            //2. check if there has any user with this email
            $this->authenticate_user($google_user_info);

            //3. create new client contact
            return $this->create_new_client_contact($google_user_info);
        }
    }

    function authorize_facebook_login() {
        $this->social_login_facebook->authorize();
    }

    function authenticate_facebook_login() {
        if (!empty($_GET)) {
            $facebook_user_info = $this->social_login_facebook->authenticate_facebook_login();
            $email = get_array_value($facebook_user_info, "email");
            if (!($email && filter_var($email, FILTER_VALIDATE_EMAIL))) {
                show_404();
            }

            //1. check if it's from settings and already a login exists
            $this->is_login_exists("facebook");

            //2. check if there has any user with this email
            $this->authenticate_user($facebook_user_info);

            //3. create new client contact
            return $this->create_new_client_contact($facebook_user_info);
        }
    }

    private function authenticate_user($user_info = array()) {
        $email = get_array_value($user_info, "email");
        if ($this->Social_Login_model->authenticate_user($email)) {
            app_redirect('dashboard/view');
        }
    }

    private function create_new_client_contact($user_info = array()) {
        if (!$user_info || get_setting("disable_client_signup") || get_setting("disable_client_login")) {
            show_404();
        }

        $email = get_array_value($user_info, "email");
        if ($this->Users_model->is_email_exists($email)) {
            //validate duplicate email address
            $view_data["heading"] = "Account exists!";
            $view_data["message"] = app_lang("account_already_exists_for_your_mail") . " " . anchor("signin", app_lang("signin"));
            return $this->template->view("errors/html/error_general", $view_data);
        }

        $first_name = get_array_value($user_info, "first_name");
        $last_name = get_array_value($user_info, "last_name");
        $now = get_current_utc_time();

        $user_data = array(
            "first_name" => $first_name,
            "last_name" => $last_name,
            "job_title" => "Untitled",
            "created_at" => $now,
            "email" => $email,
            "user_type" => "client",
            "is_primary_contact" => 1,
            "client_permissions" => "all", // primary contact can access everything
        );

        $company_name = $first_name . " " . $last_name;
        $client_data = array(
            "company_name" => $company_name,
            "type" => "person",
            "created_date" => $now,
        );

        $user_data = clean_data($user_data);
        $client_data = clean_data($client_data);

        //check duplicate company name, if found then show an error message
        if (get_setting("disallow_duplicate_client_company_name") == "1" && $this->Clients_model->is_duplicate_company_name($company_name)) {
            $view_data["heading"] = app_lang("social_login_duplicate_company_name");
            $view_data["message"] = app_lang("account_already_exists_for_your_company_name") . " " . anchor("signin", app_lang("signin"));
            return $this->template->view("errors/html/error_general", $view_data);
        }

        //create a client
        $client_id = $this->Clients_model->ci_save($client_data);
        if (!$client_id) {
            show_404();
        }

        //client created, now create the client contact
        $user_data["client_id"] = $client_id;
        $user_id = $this->Users_model->ci_save($user_data);

        log_notification("client_signup", array("client_id" => $client_id), $user_id);

        //send welcome email
        $email_template = $this->Email_templates_model->get_final_template("new_client_greetings");

        $parser_data["SIGNATURE"] = $email_template->signature;
        $parser_data["CONTACT_FIRST_NAME"] = $first_name;
        $parser_data["CONTACT_LAST_NAME"] = $last_name;

        $Company_model = model('App\Models\Company_model');
        $company_info = $Company_model->get_one_where(array("is_default" => true, "deleted" => 0));
        $parser_data["COMPANY_NAME"] = $company_info->name;

        $parser_data["DASHBOARD_URL"] = base_url();
        $parser_data["CONTACT_LOGIN_EMAIL"] = $email;
        $parser_data["CONTACT_LOGIN_PASSWORD"] = "<b>" . app_lang("social_login_login_password_help_message") . "</b>";
        $parser_data["LOGO_URL"] = get_logo_url();

        $message = $this->parser->setData($parser_data)->renderString($email_template->message);
        $subject = $this->parser->setData($parser_data)->renderString($email_template->subject);
        send_app_mail($email, $subject, $message);

        //set user session and redirect to dashboard
        $session = \Config\Services::session();
        $session->set('user_id', $user_id);

        try {
            app_hooks()->do_action('app_hook_after_signin');
        } catch (\Exception $ex) {
            log_message('error', '[ERROR] {exception}', ['exception' => $ex]);
        }

        app_redirect("dashboard/view");
    }

    private function is_login_exists($method = "") {
        if (isset($this->login_user->id) && $this->login_user->id && $method) {
            //authenticating from settings
            //skip login and redirect to setting
            $this->Social_Login_settings_model->save_setting($method . "_login_authorized", "1");
            app_redirect("social_login_settings");
        }
    }

}
