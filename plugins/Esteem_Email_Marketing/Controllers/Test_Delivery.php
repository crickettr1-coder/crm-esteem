<?php
namespace Esteem_Email_Marketing\Controllers;

use App\Controllers\Security_Controller;

class Test_Delivery extends Security_Controller
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
        $template_id = (int) ($this->request->getGet('template_id') ?? 0);
        $contact_id = (int) ($this->request->getGet('contact_id') ?? 0);
        $template = $template_id ? $this->db->table($this->p . 'email_marketing_templates')->where('id', $template_id)->get()->getRow() : null;
        $contact = $contact_id ? $this->eligible_contact($contact_id) : null;
        $preview_subject = $template && $contact ? $this->render((string) $template->subject, $contact) : '';
        $preview_html = $template && $contact ? $this->render((string) $template->html_content, $contact) : '';
        return $this->template->rander('Esteem_Email_Marketing\\Views\\test_delivery\\index', [
            'templates' => $this->db->table($this->p . 'email_marketing_templates')->orderBy('id', 'DESC')->get()->getResult(),
            'contacts' => $this->eligible_contacts(),
            'template' => $template,
            'contact' => $contact,
            'preview_subject' => $preview_subject,
            'preview_html' => $preview_html,
            'test_mode' => email_marketing_setting('test_mode', '1') === '1',
            'approved_emails' => email_marketing_approved_test_emails(),
            'mail_configured' => email_marketing_mail_configured()
        ]);
    }

    public function send()
    {
        $template_id = (int) ($this->request->getPost('template_id') ?? 0);
        $contact_id = (int) ($this->request->getPost('contact_id') ?? 0);
        $template = $this->db->table($this->p . 'email_marketing_templates')->where('id', $template_id)->get()->getRow();
        $contact = $this->eligible_contact($contact_id);
        $email = strtolower(trim((string) ($contact->email ?? '')));
        $error = '';
        if (!$template || !$contact) $error = 'Select an eligible template and contact.';
        elseif (email_marketing_setting('test_mode', '1') !== '1') $error = 'Test mode must remain enabled.';
        elseif (!email_marketing_mail_configured()) $error = 'CRM email transport is not configured.';
        elseif (!in_array($email, email_marketing_approved_test_emails(), true)) $error = 'The selected address is not on the approved test-email list.';
        if ($error) {
            $this->log($template_id, $contact_id, $email, 'blocked', $error);
            return redirect()->to(site_url('email_marketing/test-delivery'))->with('error', $error);
        }

        $subject = '[TEST EMAIL] ' . $this->render((string) $template->subject, $contact);
        $body = '<div><strong>TEST EMAIL — no live campaign delivery</strong></div><hr>' . $this->render((string) $template->html_content, $contact) . '<hr><p>' . email_marketing_setting('company_footer') . '</p><p>This was a single administrator-requested test email.</p>';
        try {
            $ok = (bool) send_app_mail($email, $subject, $body, ['reply_to' => $template->reply_to, 'from_name' => $template->sender_name]);
            if (!$ok) $error = 'Mail transport returned false.';
        } catch (\Throwable $exception) {
            $ok = false;
            $error = $this->safe_error($exception->getMessage());
        }
        $this->log(0, $contact_id, $email, $ok ? 'sent' : 'failed', $ok ? 'TEST EMAIL sent.' : $error);
        return redirect()->to(site_url('email_marketing/test-delivery'))->with($ok ? 'success' : 'error', $ok ? 'TEST EMAIL sent to the approved recipient.' : 'TEST EMAIL failed: ' . $error);
    }

    private function eligible_contacts()
    {
        return $this->db->query("SELECT u.id,u.first_name,u.last_name,u.email,c.is_lead
            FROM {$this->p}users u JOIN {$this->p}clients c ON c.id=u.client_id AND c.deleted=0
            LEFT JOIN {$this->p}marketing_consent mc ON mc.entity_id=u.id AND mc.entity_type=CASE WHEN c.is_lead=1 THEN 'lead' ELSE 'client' END
            WHERE u.deleted=0 AND u.status='active' AND u.disable_login=0 AND u.is_primary_contact=1 AND TRIM(u.email)<>''
            AND COALESCE(mc.consent,'Unknown')='Opted In'
            AND NOT EXISTS(SELECT 1 FROM {$this->p}email_suppressions es WHERE LOWER(es.email)=LOWER(u.email))
            AND LOWER(COALESCE(c.labels,'')) NOT LIKE '%do not contact%'
            ORDER BY u.first_name,u.last_name")->getResult();
    }

    private function eligible_contact($id)
    {
        foreach ($this->eligible_contacts() as $contact) if ((int) $contact->id === $id) return $contact;
        return null;
    }

    private function render($text, $contact)
    {
        $values = ['FIRST_NAME' => $contact->first_name, 'LAST_NAME' => $contact->last_name, 'FULL_NAME' => trim($contact->first_name . ' ' . $contact->last_name), 'EMAIL' => $contact->email, 'PHONE' => '', 'STATE' => '', 'POSTCODE' => '', 'LEAD_OWNER' => '', 'UNSUBSCRIBE_URL' => email_marketing_unsubscribe_url($contact->email), 'COMPANY_NAME' => 'Esteem Energy'];
        foreach ($values as $key => $value) $text = str_replace('{' . $key . '}', (string) $value, $text);
        return $text;
    }

    private function log($template_id, $contact_id, $email, $status, $error)
    {
        $this->db->table($this->p . 'email_test_send_log')->insert(['campaign_id' => 0, 'contact_id' => $contact_id, 'email' => strtolower((string) $email), 'status' => $status, 'error_details' => substr($error, 0, 500), 'created_at' => date('Y-m-d H:i:s')]);
    }

    private function safe_error($message)
    {
        $message = preg_replace('/(pass(word)?|secret|token|api[_ -]?key)\s*[:=]\s*[^\s,;]+/i', '$1=[redacted]', (string) $message);
        return substr(preg_replace('/\s+/', ' ', $message), 0, 500);
    }
}
