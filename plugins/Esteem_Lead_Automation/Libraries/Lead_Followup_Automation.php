<?php

namespace Esteem_Lead_Automation\Libraries;

class Lead_Followup_Automation {

    private $trigger_type = "clients_insert_lead_followup";

    public function handle_insert($hook_data) {
        if (get_array_value($hook_data, "table_without_prefix") !== "clients") {
            return false;
        }

        $data = get_array_value($hook_data, "data");
        $lead_id = (int) get_array_value($hook_data, "id");
        if (!$lead_id || (int) get_array_value($data, "is_lead") !== 1) {
            return false;
        }

        $settings = new \Esteem_Lead_Automation\Models\Lead_Automation_settings_model();
        if ($settings->get_setting("lead_automation_enabled") !== "1") {
            return false;
        }

        $db = db_connect('default');
        $audit_table = $db->prefixTable('lead_automation_audit');
        $existing_audit = $db->table('lead_automation_audit')
            ->where("lead_id", $lead_id)
            ->where("trigger_type", $this->trigger_type)
            ->get()->getRow();

        if ($existing_audit && (int) $existing_audit->task_id > 0) {
            return false;
        }

        if ($existing_audit && $existing_audit->status === "processing") {
            return false;
        }

        $lead = $this->get_lead($db, $lead_id);
        if (!$lead) {
            $this->write_audit($db, $lead_id, 0, "failed", "lead_not_found", $existing_audit);
            return false;
        }

        $owner = $db->table('users')
            ->where("id", (int) $lead->owner_id)
            ->where("user_type", "staff")
            ->where("status", "active")
            ->where("deleted", 0)
            ->get()->getRow();
        if (!$owner) {
            $this->write_audit($db, $lead_id, 0, "failed", "lead_owner_not_active_staff", $existing_audit);
            return false;
        }

        $claimed = $this->write_audit($db, $lead_id, 0, "processing", $this->trigger_type, $existing_audit);
        if (!$claimed) {
            //The unique lead/trigger key prevents two concurrent insert
            //events from both claiming the same follow-up task.
            $claimed_audit = $db->table('lead_automation_audit')
                ->where("lead_id", $lead_id)
                ->where("trigger_type", $this->trigger_type)
                ->get()->getRow();
            if ($claimed_audit && ((int) $claimed_audit->task_id > 0 || $claimed_audit->status === "processing")) {
                return false;
            }
            return false;
        }

        $delay = (int) $settings->get_setting("lead_automation_delay_minutes");
        $delay = max(1, min($delay, 43200));
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $deadline = $now->modify("+$delay minutes")->format('Y-m-d H:i:s');
        $lead_name = trim((string) $lead->company_name);
        $title_template = trim((string) $settings->get_setting("lead_automation_task_title"));
        $title_template = $title_template ?: "Follow up new lead: {Lead Name}";
        $title = str_replace("{Lead Name}", $lead_name, $title_template);

        $description = "Lead: " . $lead_name . "\n"
            . "Phone: " . ((string) $lead->phone ?: "-") . "\n"
            . "Email: " . ((string) $lead->email ?: "-") . "\n"
            . "Source: " . ((string) $lead->source_title ?: "-") . "\n"
            . "Status: " . ((string) $lead->status_title ?: "-") . "\n"
            . "Lead link: " . get_uri("leads/view/" . $lead_id);

        $task_status = $db->table('task_status')->where("key_name", "to_do")->where("deleted", 0)->get()->getRow();
        $task_priority = $db->table('task_priority')->where("deleted", 0)->orderBy("id", "ASC")->get()->getRow();
        if (!$task_status || !$task_priority) {
            $this->write_audit($db, $lead_id, 0, "failed", "task_defaults_not_found", $existing_audit);
            return false;
        }

        $tasks_model = model("App\\Models\\Tasks_model");
        $task_data = array(
            "title" => mb_substr($title, 0, 255),
            "description" => $description,
            "project_id" => 0,
            "milestone_id" => 0,
            "assigned_to" => (int) $owner->id,
            "deadline" => $deadline,
            "labels" => "",
            "points" => 1,
            "status" => "to_do",
            "status_id" => (int) $task_status->id,
            "priority_id" => (int) $task_priority->id,
            "start_date" => $now->format('Y-m-d H:i:s'),
            "collaborators" => "",
            "sort" => 0,
            "recurring" => 0,
            "repeat_every" => 0,
            "no_of_cycles" => 0,
            "recurring_task_id" => 0,
            "no_of_cycles_completed" => 0,
            "created_date" => $now->format('Y-m-d'),
            "blocking" => "",
            "blocked_by" => "",
            "parent_task_id" => 0,
            "ticket_id" => 0,
            "expense_id" => 0,
            "subscription_id" => 0,
            "proposal_id" => 0,
            "contract_id" => 0,
            "order_id" => 0,
            "estimate_id" => 0,
            "invoice_id" => 0,
            "lead_id" => $lead_id,
            "client_id" => 0,
            "context" => "lead",
            "created_by" => (int) $owner->id
        );

        $task_id = $tasks_model->ci_save($task_data);
        if (!$task_id) {
            $this->write_audit($db, $lead_id, 0, "failed", "task_create_failed", $existing_audit);
            return false;
        }

        $this->write_audit($db, $lead_id, (int) $task_id, "created", $this->trigger_type, $existing_audit);
        return true;
    }

    private function get_lead($db, $lead_id) {
        $sql = "SELECT c.id, c.company_name, c.phone, c.owner_id,
                    u.email, ls.title AS status_title, src.title AS source_title
                FROM " . $db->prefixTable('clients') . " c
                LEFT JOIN " . $db->prefixTable('users') . " u
                    ON u.client_id = c.id AND u.is_primary_contact = 1 AND u.deleted = 0
                LEFT JOIN " . $db->prefixTable('lead_status') . " ls ON ls.id = c.lead_status_id
                LEFT JOIN " . $db->prefixTable('lead_source') . " src ON src.id = c.lead_source_id
                WHERE c.id = ? AND c.is_lead = 1 AND c.deleted = 0 LIMIT 1";
        return $db->query($sql, array($lead_id))->getRow();
    }

    private function write_audit($db, $lead_id, $task_id, $status, $trigger_type, $existing_audit = null) {
        $data = array(
            "lead_id" => (int) $lead_id,
            "task_id" => (int) $task_id,
            "trigger_type" => $trigger_type,
            "created_at" => get_current_utc_time(),
            "status" => $status
        );

        if ($existing_audit && $existing_audit->id) {
            return $db->table('lead_automation_audit')->where("id", (int) $existing_audit->id)->update($data);
        }

        return $db->table('lead_automation_audit')->insert($data);
    }
}
