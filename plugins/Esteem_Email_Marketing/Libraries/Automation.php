<?php
namespace Esteem_Email_Marketing\Libraries;

class Automation
{
    private $db;
    private $p;

    public function __construct()
    {
        $this->db = db_connect('default');
        $this->p = get_db_prefix();
    }

    public function event($hook, $kind)
    {
        if (email_marketing_setting('enabled', '0') !== '1' || ($hook['table_without_prefix'] ?? '') !== 'clients') return;
        $id = (int)($hook['id'] ?? 0);
        $data = is_array($hook['data'] ?? null) ? $hook['data'] : [];
        if (!$id || !in_array($kind, ['insert', 'update'], true)) return;
        if ($kind === 'update' && !array_key_exists('lead_status_id', $data)) return;
        $lead = $this->lead($id);
        if (!$lead || (int)$lead->is_lead !== 1 || !$lead->email || $this->is_do_not_contact($lead) || !$this->eligible($lead)) return;
        $trigger = $kind === 'insert' ? 'new_lead' : 'quote_sent';
        foreach ($this->db->table($this->p . 'email_automation_rules')->where(['enabled' => 1, 'trigger_type' => $trigger])->get()->getResult() as $rule) {
            if ($trigger === 'quote_sent' && ((int)$lead->lead_status_id !== (int)$rule->quote_status_id || (int)$rule->quote_status_id < 1)) continue;
            if ($this->has_audit($rule->id, $lead->id, $trigger)) continue;
            $this->enqueue($rule, $lead, $trigger);
        }
    }

    public function daily_no_response()
    {
        if (email_marketing_setting('enabled', '0') !== '1' || !email_marketing_mail_configured()) return;
        $today = date('Y-m-d');
        if (email_marketing_setting('last_no_response_run_date', '') === $today) return;
        $checked = 0; $queued = 0; $skipped = 0; $errors = 0;
        foreach ($this->db->table($this->p . 'email_automation_rules')->where(['enabled' => 1, 'trigger_type' => 'no_response'])->get()->getResult() as $rule) {
            $status_ids = array_values(array_filter(array_map('intval', explode(',', (string)$rule->status_ids))));
            if (!$status_ids) continue;
            $rows = $this->db->query("SELECT c.*, u.email, u.first_name, u.last_name FROM {$this->p}clients c JOIN {$this->p}users u ON u.client_id = c.id AND u.is_primary_contact = 1 AND u.deleted = 0 WHERE c.is_lead = 1 AND c.deleted = 0 AND c.lead_status_id IN (" . implode(',', $status_ids) . ")")->getResult();
            $days = max(1, (int)$rule->no_response_days);
            foreach ($rows as $lead) {
                $checked++;
                if (!$this->eligible($lead) || $this->recent_no_response_audit($rule->id, $lead->id, $days)) { $skipped++; continue; }
                if ($this->enqueue($rule, $lead, 'no_response_' . date('Ymd'))) $queued++; else $errors++;
            }
        }
        if ($errors === 0) {
            $this->db->table($this->p . 'email_marketing_settings')->replace(['setting_name' => 'last_no_response_run_date', 'setting_value' => $today]);
        }
        log_message('info', 'Email Marketing no-response cron: checked={checked}, queued={queued}, skipped={skipped}, errors={errors}', compact('checked', 'queued', 'skipped', 'errors'));
    }

    private function recent_no_response_audit($rule_id, $entity_id, $days)
    {
        return $this->db->table($this->p . 'email_automation_audit')->where(['rule_id' => (int)$rule_id, 'entity_type' => 'lead', 'entity_id' => (int)$entity_id])->where('trigger_type LIKE', 'no_response_%')->where('created_at >=', date('Y-m-d H:i:s', strtotime('-' . (int)$days . ' days')))->countAllResults() > 0;
    }

    private function has_audit($rule_id, $entity_id, $trigger)
    {
        return $this->db->table($this->p . 'email_automation_audit')->where(['rule_id' => (int)$rule_id, 'entity_type' => 'lead', 'entity_id' => (int)$entity_id, 'trigger_type' => $trigger])->countAllResults() > 0;
    }

    private function is_do_not_contact($lead) { return stripos((string)($lead->labels ?? ''), 'do not contact') !== false; }

    private function eligible($lead)
    {
        return $this->db->table($this->p . 'marketing_consent')->where(['entity_type' => 'lead', 'entity_id' => (int)$lead->id, 'consent' => 'Opted In'])->countAllResults() > 0 && !$this->db->table($this->p . 'email_suppressions')->where('email', strtolower($lead->email))->countAllResults() && !$this->is_do_not_contact($lead);
    }

    private function lead($id)
    {
        return $this->db->query("SELECT c.*, u.email, u.first_name, u.last_name FROM {$this->p}clients c LEFT JOIN {$this->p}users u ON u.client_id = c.id AND u.is_primary_contact = 1 AND u.deleted = 0 WHERE c.id = ? AND c.deleted = 0 LIMIT 1", [$id])->getRow();
    }

    private function enqueue($rule, $lead, $trigger)
    {
        $template = $this->db->table($this->p . 'email_marketing_templates')->where('id', (int)$rule->template_id)->get()->getRow();
        if (!$template || !$lead->email) return false;
        $now = date('Y-m-d H:i:s');
        $this->db->table($this->p . 'email_campaigns')->insert(['name' => 'Automation: ' . $rule->name, 'subject' => $template->subject, 'html_content' => $template->html_content, 'sender_name' => email_marketing_setting('default_sender_name', 'Esteem Energy'), 'reply_to' => email_marketing_setting('default_reply_to', ''), 'status' => 'sending', 'created_by' => 1, 'created_at' => $now]);
        $campaign_id = (int)$this->db->insertID();
        if (!$campaign_id) return false;
        $this->db->table($this->p . 'email_campaign_recipients')->insert(['campaign_id' => $campaign_id, 'entity_type' => 'lead', 'entity_id' => (int)$lead->id, 'email' => strtolower($lead->email), 'recipient_name' => trim(($lead->first_name ?? '') . ' ' . ($lead->last_name ?? '')), 'status' => 'queued', 'queued_at' => $now]);
        $queue_id = (int)$this->db->insertID();
        return (bool)$this->db->table($this->p . 'email_automation_audit')->insert(['rule_id' => (int)$rule->id, 'entity_type' => 'lead', 'entity_id' => (int)$lead->id, 'template_id' => (int)$rule->template_id, 'campaign_id' => $campaign_id, 'queue_id' => $queue_id, 'trigger_type' => $trigger, 'created_at' => $now]);
    }
}
