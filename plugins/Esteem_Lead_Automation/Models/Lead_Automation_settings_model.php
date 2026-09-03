<?php

namespace Esteem_Lead_Automation\Models;

use App\Models\Crud_model;

class Lead_Automation_settings_model extends Crud_model {

    protected $table = null;

    private $defaults = array(
        "lead_automation_enabled" => "0",
        "lead_automation_delay_minutes" => "15",
        "lead_automation_task_title" => "Follow up new lead: {Lead Name}"
    );

    public function __construct() {
        $this->table = 'lead_automation_settings';
        parent::__construct($this->table);
    }

    public function get_setting($setting_name) {
        $result = $this->db_builder->getWhere(array("setting_name" => $setting_name, "deleted" => 0), 1);
        if ($result->getNumRows() === 1) {
            return $result->getRow()->setting_value;
        }

        return array_key_exists($setting_name, $this->defaults) ? $this->defaults[$setting_name] : null;
    }

    public function save_setting($setting_name, $setting_value) {
        if (!array_key_exists($setting_name, $this->defaults)) {
            return false;
        }

        $existing = $this->db_builder->getWhere(array("setting_name" => $setting_name), 1)->getRow();
        $data = array("setting_name" => $setting_name, "setting_value" => $setting_value, "deleted" => 0);

        if ($existing) {
            return $this->db_builder->where("setting_name", $setting_name)->update(array("setting_value" => $setting_value, "deleted" => 0));
        }

        return $this->db_builder->insert($data);
    }
}

