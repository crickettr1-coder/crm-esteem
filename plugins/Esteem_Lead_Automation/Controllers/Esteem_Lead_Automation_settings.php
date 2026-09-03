<?php

namespace Esteem_Lead_Automation\Controllers;

use App\Controllers\Security_Controller;

class Esteem_Lead_Automation_settings extends Security_Controller {

    private $settings_model;

    public function __construct() {
        parent::__construct();
        if (!$this->login_user->is_admin) {
            app_redirect("forbidden");
        }
        $this->settings_model = new \Esteem_Lead_Automation\Models\Lead_Automation_settings_model();
    }

    public function index() {
        return $this->template->rander("Esteem_Lead_Automation\\Views\\settings\\index");
    }

    public function save() {
        $delay = filter_var($this->request->getPost("lead_automation_delay_minutes"), FILTER_VALIDATE_INT);
        $title = trim((string) $this->request->getPost("lead_automation_task_title"));
        if ($delay === false || $delay < 1 || $delay > 43200) {
            echo json_encode(array("success" => false, "message" => "Follow-up delay must be between 1 and 43200 minutes."));
            return false;
        }
        if (!$title || mb_strlen($title) > 255) {
            echo json_encode(array("success" => false, "message" => "Task title is required and must be 255 characters or fewer."));
            return false;
        }

        $this->settings_model->save_setting("lead_automation_enabled", $this->request->getPost("lead_automation_enabled") ? "1" : "0");
        $this->settings_model->save_setting("lead_automation_delay_minutes", (string) $delay);
        $this->settings_model->save_setting("lead_automation_task_title", mb_substr($title, 0, 255));

        echo json_encode(array("success" => true, "message" => app_lang("settings_updated")));
        return true;
    }
}
