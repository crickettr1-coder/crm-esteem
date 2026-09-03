<?php

namespace Mailbox\Libraries;

class General_imap {

    protected $Mailbox_settings_model;
    protected $Mailbox_emails_model;
    protected $Mailboxes_model;

    public function __construct() {
        $this->Mailbox_settings_model = new \Mailbox\Models\Mailbox_settings_model();
        $this->Mailbox_emails_model = new \Mailbox\Models\Mailbox_emails_model();
        $this->Mailboxes_model = new \Mailbox\Models\Mailboxes_model();

        require_once(PLUGINPATH . "Mailbox/ThirdParty/Imap/ddeboer-imap/vendor/autoload.php");
        require_once(PLUGINPATH . "Mailbox/ThirdParty/Imap/mail-mime-parser/vendor/autoload.php");
    }

    function authorize_imap_and_get_inbox($mailbox_id = 0, $is_cron = false) {
        if (!$mailbox_id) {
            return false;
        }

        $mailbox_info = $this->Mailboxes_model->get_one($mailbox_id);
        $server = new \Ddeboer\Imap\Server($mailbox_info->imap_host, $mailbox_info->imap_port, $mailbox_info->imap_encryption);

        //try to login 10 times and save the count on each load of cron job
        //after a success login, reset the count to 0
        try {
            $connection = $server->authenticate($mailbox_info->imap_email, decode_password($mailbox_info->imap_password, "imap_password"));

            $data = array(
                "imap_authorized" => 1, //the credentials is valid. store to settings that it's authorized
                "imap_failed_login_attempts" => 0, //reset failed login attempts count
            );

            $this->Mailboxes_model->ci_save($data, $mailbox_id);
            return $connection;
        } catch (\Exception $exc) {
            //the credentials is invalid, increase attempt count and store
            $attempts_count = $mailbox_info->imap_failed_login_attempts;
            if ($is_cron) {
                $attempts_count = $attempts_count ? ($attempts_count * 1 + 1) : 1;
                $data = array("imap_failed_login_attempts" => $attempts_count);
                $this->Mailboxes_model->ci_save($data, $mailbox_id);
            }

            //log error for every exception
            log_message('error', '[ERROR] {exception}', ['exception' => $exc]);

            if ($attempts_count === 10 || !$is_cron) {
                //flag it's unauthorized, only after 10 failed attempts
                $data = array("imap_authorized" => 0);
                $this->Mailboxes_model->ci_save($data, $mailbox_id);
            }

            return false;
        }
    }

    public function process_emails($mailbox_info = null) {
        if (!$mailbox_info->id) {
            return false;
        }

        $connection = $this->authorize_imap_and_get_inbox($mailbox_info->id, true);
        if (!$connection) {
            return false; //couldn't get connection of this email
        }

        $mailbox_name = "";

        if ($connection->hasMailbox("INBOX")) {
            $mailbox_name = "INBOX";
        } else if ($connection->hasMailbox("Inbox")) {
            $mailbox_name = "Inbox";
        } else if ($connection->hasMailbox("inbox")) {
            $mailbox_name = "inbox";
        }

        if (!$mailbox_name) {
            log_message('error', 'IMAP integration will not work since there is no mailbox named INBOX for ' . $mailbox->title);
            return false;
        }

        $mailbox = $connection->getMailbox($mailbox_name); //get mails of inbox only

        $messages = $mailbox->getMessages();

        $last_seen_settings_name = "last_seen_imap_message_number_" . $mailbox_info->id;
        $saved_last_message = get_mailbox_setting($last_seen_settings_name);
        $saved_last_message = $saved_last_message ? $saved_last_message : 0;

        $collection_count = 0;
        $last_number = 0;

        foreach ($messages as $key => $message) {
            $last_number = $messages[$key];

            if ($saved_last_message > $last_number) {
                //Skip already seen messages Nothing to do there.
                continue;
            }

            $collection_count++;
            if (get_mailbox_setting("max_email_collection_count_per_cron_run") && $collection_count >= get_mailbox_setting("max_email_collection_count_per_cron_run")) {
                break;
            }

            //create emails for unread emails
            if (!$message->isSeen()) {

                $this->_create_email_from_imap($mailbox_info->id, $message);

                //mark the mail as read
                $message->markAsSeen();
            }
        }

        $this->Mailbox_settings_model->save_setting($last_seen_settings_name, $last_number);
    }

    private function _create_email_from_imap($mailbox_id, $message_info = "") {
        if (!$message_info || is_null($message_info->getFrom())) {
            return false;
        }

        $email = $message_info->getFrom()->getAddress();
        $creator_name = $message_info->getFrom()->getName();
        $subject = $message_info->getSubject();
        $now = get_current_utc_time();

        //check if there has any client containing this email address
        //if so, go through with the client id
        $contact_info = $this->Mailbox_emails_model->get_user_of_email($email)->getRow();
        $contact_id = isset($contact_info->id) ? $contact_info->id : 0;

        //check if the email is exists on the app
        //if not, that will be considered as a new email
        //but for this case, it's a replying email. we've to parse the message
        $email_id = $this->_get_email_id_from_subject($subject, $email, $contact_id, $mailbox_id);

        $email_data = array(
            "subject" => $subject ? $subject : "(No subject)",
            "created_by" => $contact_id,
            "created_at" => $now,
            "last_activity_at" => $now,
            "creator_name" => $creator_name ? $creator_name : "",
            "creator_email" => $email,
            "email_id" => $email_id,
            "mailbox_id" => $mailbox_id,
            "encoding_type" => "raw"
        );

        $email_data = clean_data($email_data);

        //don't clean email raw content
        $email_data["message"] = $this->get_email_message($message_info, $email_id);

        $files_data = $this->_prepare_attachment_data_of_mail($message_info);
        $email_data["files"] = serialize($files_data);

        $this->Mailbox_emails_model->ci_save($email_data);

        if ($email_id) {
            //save last activity to the parent email
            $email_data = array(
                "last_activity_at" => $now
            );

            $this->Mailbox_emails_model->ci_save($email_data, $email_id);
        }
    }

    //save emails comment
    private function get_email_message($message_info, $email_id) {
        $raw_content = $message_info->getRawMessage(); //save raw content to process it in the view later
        $pattern = '/Content preview:(.*?)Content analysis details:/s';
        $raw_content = preg_replace($pattern, "", $raw_content);
        return $raw_content;
    }

    //get email id
    private function _get_email_id_from_subject($subject = "", $email = "", $contact_id = 0, $mailbox_id = 0) {
        if (!($subject && $email)) {
            return 0;
        }

        //find 'Re: '
        $reply_text = "Re: ";
        if (substr($subject, 0, strlen($reply_text)) !== $reply_text) {
            return 0;
        }

        //it's a replying email
        $main_subject = str_replace($reply_text, "", $subject);
        $email_info = $this->Mailbox_emails_model->get_email_with_subject($main_subject, $email, $contact_id, $mailbox_id)->getRow();

        return isset($email_info->id) ? $email_info->id : 0;
    }

    //download attached files to local
    private function _prepare_attachment_data_of_mail($message_info = "") {
        if ($message_info) {
            $files_data = array();
            $attachments = $message_info->getAttachments();

            foreach ($attachments as $attachment) {
                //move files to the directory
                $file_name = $attachment->getFilename();
                $file_name = str_replace("/", "-", $file_name);
                $file_data = move_temp_file($file_name, get_mailbox_setting("mailbox_email_file_path"), "mailbox", NULL, "", $attachment->getDecodedContent());

                array_push($files_data, $file_data);
            }

            return $files_data;
        }
    }

}
