<?php
namespace Esteem_Email_Marketing\Controllers;

use App\Controllers\Security_Controller;
use Esteem_Email_Marketing\Libraries\Recipient_Service;

class Audit extends Security_Controller
{
    private $db;
    private $p;

    public function __construct()
    {
        parent::__construct();
        $this->access_only_admin();
        $this->db = db_connect('default');
        $this->p = get_db_prefix();
    }

    public function index()
    {
        $campaigns = $this->db->query("SELECT c.*, COUNT(r.id) AS recipient_count,
            SUM(r.status = 'queued') AS queued_count, SUM(r.status = 'sent') AS sent_count,
            SUM(r.status = 'failed') AS failed_count, SUM(r.status = 'skipped') AS skipped_count,
            SUM(r.status = 'unsubscribed') AS unsubscribed_count, MAX(r.sent_at) AS last_sent,
            MAX(r.queued_at) AS last_queued, COUNT(t.id) AS test_log_count, MAX(t.created_at) AS last_attempt
            FROM {$this->p}email_campaigns c
            LEFT JOIN {$this->p}email_campaign_recipients r ON r.campaign_id = c.id
            LEFT JOIN {$this->p}email_test_send_log t ON t.campaign_id = c.id
            WHERE c.status = 'sending'
            GROUP BY c.id ORDER BY c.id")->getResult();

        foreach ($campaigns as $campaign) {
            $filters = json_decode((string) $campaign->segment_filters, true);
            $campaign->actual_eligible_count = 0;
            if (is_array($filters) && count($filters)) {
                try {
                    $campaign->actual_eligible_count = count((new Recipient_Service())->query($filters, 100000, 0));
                } catch (\Throwable $e) {
                    $campaign->actual_eligible_count = null;
                    $campaign->eligibility_error = 'Eligibility query could not be evaluated.';
                }
            }
            $campaign->history = $this->db->table($this->p . 'email_campaign_status_history')
                ->where('campaign_id', (int) $campaign->id)->orderBy('id', 'DESC')->get()->getResult();
            $campaign->queue_rows = $this->db->table($this->p . 'email_campaign_recipients')
                ->where('campaign_id', (int) $campaign->id)->orderBy('id', 'DESC')->get()->getResult();
            $campaign->test_logs = $this->db->table($this->p . 'email_test_send_log')
                ->where('campaign_id', (int) $campaign->id)->orderBy('id', 'DESC')->get()->getResult();
        }

        $filters = [
            'state' => trim((string) ($this->request->getGet('state') ?? '')),
            'owner_id' => (int) ($this->request->getGet('owner_id') ?? 0),
            'status_id' => (int) ($this->request->getGet('status_id') ?? 0),
            'created_from' => trim((string) ($this->request->getGet('created_from') ?? '')),
            'created_to' => trim((string) ($this->request->getGet('created_to') ?? '')),
            'email' => in_array(($this->request->getGet('email') ?? ''), ['yes', 'no'], true) ? $this->request->getGet('email') : '',
            'consent' => in_array(($this->request->getGet('consent') ?? ''), ['Unknown', 'Opted In', 'Opted Out'], true) ? $this->request->getGet('consent') : ''
        ];

        $where = ["u.user_type = 'lead'", 'u.deleted = 1'];
        $params = [];
        if ($filters['state'] !== '') { $where[] = 'c.state = ?'; $params[] = $filters['state']; }
        if ($filters['owner_id']) { $where[] = 'c.owner_id = ?'; $params[] = $filters['owner_id']; }
        if ($filters['status_id']) { $where[] = 'c.lead_status_id = ?'; $params[] = $filters['status_id']; }
        if ($filters['created_from'] !== '') { $where[] = 'c.created_date >= ?'; $params[] = $filters['created_from'] . ' 00:00:00'; }
        if ($filters['created_to'] !== '') { $where[] = 'c.created_date <= ?'; $params[] = $filters['created_to'] . ' 23:59:59'; }
        if ($filters['email'] === 'yes') $where[] = "TRIM(u.email) <> ''";
        if ($filters['email'] === 'no') $where[] = "TRIM(u.email) = ''";
        if ($filters['consent'] !== '') { $where[] = 'COALESCE(mc.consent, ?) = ?'; $params[] = 'Unknown'; $params[] = $filters['consent']; }

        $candidate_sql = "SELECT u.id, TRIM(CONCAT_WS(' ', u.first_name, u.last_name)) AS lead_name,
            u.email, COALESCE(NULLIF(u.phone, ''), c.phone) AS phone,
            TRIM(CONCAT_WS(' ', o.first_name, o.last_name)) AS owner_name,
            COALESCE(ls.title, 'Unknown') AS lead_status, c.state, c.city, c.created_date,
            COALESCE(mc.consent, 'Unknown') AS consent
            FROM {$this->p}users u JOIN {$this->p}clients c ON c.id = u.client_id
            LEFT JOIN {$this->p}users o ON o.id = c.owner_id
            LEFT JOIN {$this->p}lead_status ls ON ls.id = c.lead_status_id
            LEFT JOIN {$this->p}marketing_consent mc ON mc.entity_type = 'lead' AND mc.entity_id = u.id
            WHERE " . implode(' AND ', $where) . " ORDER BY u.id DESC LIMIT 200";
        $candidates = $this->db->query($candidate_sql, $params)->getResult();

        $deletion_summary = [
            'total' => (int) $this->db->table($this->p . 'users')->where(['user_type' => 'lead', 'deleted' => 1])->countAllResults(),
            'with_email' => (int) $this->db->query("SELECT COUNT(*) AS n FROM {$this->p}users WHERE user_type='lead' AND deleted=1 AND TRIM(email) <> ''")->getRow()->n,
            'consent_rows' => (int) $this->db->query("SELECT COUNT(*) AS n FROM {$this->p}marketing_consent mc JOIN {$this->p}users u ON u.id=mc.entity_id AND mc.entity_type='lead' WHERE u.deleted=1")->getRow()->n,
            'campaign_rows' => (int) $this->db->query("SELECT COUNT(*) AS n FROM {$this->p}email_campaign_recipients r JOIN {$this->p}users u ON u.id=r.entity_id AND r.entity_type='lead' WHERE u.deleted=1")->getRow()->n,
            'suppression_rows' => (int) $this->db->query("SELECT COUNT(*) AS n FROM {$this->p}email_suppressions s JOIN {$this->p}users u ON u.id=s.entity_id AND s.entity_type='lead' WHERE u.deleted=1")->getRow()->n,
            'deletion_date_available' => false
        ];
        $owners = $this->db->query("SELECT o.id, TRIM(CONCAT_WS(' ',o.first_name,o.last_name)) AS name, COUNT(*) AS total
            FROM {$this->p}users u JOIN {$this->p}clients c ON c.id=u.client_id LEFT JOIN {$this->p}users o ON o.id=c.owner_id
            WHERE u.user_type='lead' AND u.deleted=1 GROUP BY o.id,name ORDER BY total DESC")->getResult();
        $statuses = $this->db->query("SELECT COALESCE(ls.title,'Unknown') AS name, COUNT(*) AS total
            FROM {$this->p}users u JOIN {$this->p}clients c ON c.id=u.client_id LEFT JOIN {$this->p}lead_status ls ON ls.id=c.lead_status_id
            WHERE u.user_type='lead' AND u.deleted=1 GROUP BY name ORDER BY total DESC")->getResult();

        $last_cron = $this->db->table($this->p . 'settings')->where('setting_name', 'last_cron_job_time')->get()->getRow();
        $safe_settings = [];
        foreach (['enabled', 'test_mode', 'approved_test_emails', 'batch_size'] as $key) {
            $row = $this->db->table($this->p . 'email_marketing_settings')->where('setting_name', $key)->get()->getRow();
            $safe_settings[$key] = $row ? (string) $row->setting_value : '';
        }
        $smtp = [];
        foreach (['email_protocol', 'email_smtp_host', 'email_smtp_port', 'email_smtp_user', 'email_smtp_pass', 'email_sent_from_address'] as $key) {
            $row = $this->db->table($this->p . 'settings')->where('setting_name', $key)->get()->getRow();
            $smtp[$key] = (bool) ($row && (string) $row->setting_value !== '');
        }

        return $this->template->rander('Esteem_Email_Marketing\\Views\\audit\\index', compact('campaigns', 'filters', 'candidates', 'deletion_summary', 'owners', 'statuses', 'last_cron', 'safe_settings', 'smtp'));
    }
}
