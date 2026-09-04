<?php
namespace Esteem_Email_Marketing\Libraries;

class Email_Queue
{
    public function process($limit = 25)
    {
        if (email_marketing_setting('enabled', '0') !== '1' || !email_marketing_mail_configured()) return 0;
        $db = db_connect('default');
        $p = get_db_prefix();
        $rows = $db->query("SELECT r.*, c.subject, c.html_content, c.reply_to, c.sender_name, c.test_recipient FROM `{$p}email_campaign_recipients` r JOIN `{$p}email_campaigns` c ON c.id = r.campaign_id WHERE r.status = 'queued' AND c.status IN ('scheduled','sending') AND (c.send_at IS NULL OR c.send_at <= NOW()) ORDER BY r.id LIMIT " . max(1, (int)$limit))->getResult();
        $sent = 0;
        $campaign_ids = [];

        foreach ($rows as $row) {
            $campaign_ids[(int)$row->campaign_id] = true;
            $recipient = strtolower(trim((string)$row->email));
            if (email_marketing_setting('test_mode', '1') === '1' && $recipient !== strtolower(trim((string)($row->test_recipient ?? '')))) {
                $db->table($p . 'email_campaign_recipients')->where('id', $row->id)->update(['status' => 'skipped', 'error_details' => 'Test mode recipient restriction']);
                continue;
            }
            if ($db->table($p . 'email_suppressions')->where('email', $recipient)->countAllResults()) {
                $db->table($p . 'email_campaign_recipients')->where('id', $row->id)->update(['status' => 'unsubscribed', 'error_details' => 'Recipient is suppressed']);
                continue;
            }

            $body = $row->html_content . '<hr><p>' . email_marketing_setting('company_footer') . '</p><p><a href="' . email_marketing_unsubscribe_url($recipient) . '">Unsubscribe</a></p>';
            $ok = false;
            $error = null;
            try {
                $ok = (bool)send_app_mail($recipient, $row->subject, $body, ['reply_to' => $row->reply_to, 'from_name' => $row->sender_name]);
                if (!$ok) $error = 'Mail transport returned false';
            } catch (\Throwable $exception) {
                $error = substr(preg_replace('/\s+/', ' ', (string)$exception->getMessage()), 0, 500);
            }
            $db->table($p . 'email_campaign_recipients')->where('id', $row->id)->update(['status' => $ok ? 'sent' : 'failed', 'sent_at' => $ok ? date('Y-m-d H:i:s') : null, 'error_details' => $ok ? null : ($error ?: 'Mail transport failed')]);
            if ((int)($row->is_test ?? 0) === 1) $db->table($p . 'email_test_send_log')->insert(['campaign_id' => (int)$row->campaign_id, 'contact_id' => (int)($row->entity_id ?? 0), 'email' => $recipient, 'status' => $ok ? 'sent' : 'failed', 'error_details' => $ok ? null : ($error ?: 'Mail transport failed'), 'created_at' => date('Y-m-d H:i:s')]);
            if ($ok) $sent++;
            $delay = (int)email_marketing_setting('delay_between_messages', '0');
            if ($delay > 0) usleep($delay * 1000);
        }

        foreach (array_keys($campaign_ids) as $campaign_id) {
            if (!$db->table($p . 'email_campaign_recipients')->where(['campaign_id' => $campaign_id, 'status' => 'queued'])->countAllResults()) {
                $db->table($p . 'email_campaigns')->where(['id' => $campaign_id, 'status' => 'sending'])->update(['status' => 'completed', 'updated_at' => date('Y-m-d H:i:s')]);
            }
        }
        return $sent;
    }
}
