<?php

if (!function_exists("get_lead_automation_setting")) {
    function get_lead_automation_setting($key = "") {
        static $settings_model;
        if (!$settings_model) {
            $settings_model = new \Esteem_Lead_Automation\Models\Lead_Automation_settings_model();
        }
        return $settings_model->get_setting($key);
    }
}

