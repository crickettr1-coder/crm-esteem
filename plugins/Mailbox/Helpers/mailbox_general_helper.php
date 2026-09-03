<?php

use App\Controllers\Security_Controller;
use Mailbox\Libraries\Outlook_smtp;

/**
 * link the css files 
 * 
 * @param array $array
 * @return print css links
 */
if (!function_exists('mailbox_load_css')) {

    function mailbox_load_css(array $array) {
        $version = get_setting("app_version");

        foreach ($array as $uri) {
            echo "<link rel='stylesheet' type='text/css' href='" . base_url($uri) . "?v=$version' />";
        }

        echo view('Mailbox\Views\includes\dark_theme_helper_js');
    }

}

/**
 * get the defined config value by a key
 * @param string $key
 * @return config value
 */
if (!function_exists('get_mailbox_setting')) {

    function get_mailbox_setting($key = "") {
        $config = new Mailbox\Config\Mailbox();

        $setting_value = get_array_value($config->app_settings_array, $key);
        if ($setting_value !== NULL) {
            return $setting_value;
        } else {
            return "";
        }
    }

}

if (!function_exists('prepare_recipients_data')) {

    function prepare_recipients_data($data) {
        if (!$data->recipients) {
            return "-";
        }

        $recipients_data = "";
        $Users_model = model("App\Models\Users_model");

        $recipients = explode(',', $data->recipients);
        foreach ($recipients as $recipient) {
            if (!$recipient) {
                continue;
            }

            $email_data = "";

            if (is_numeric($recipient)) {
                //user is a contact
                $contact_info = $Users_model->get_one($recipient);
                if ($contact_info->user_type === "client") {
                    $email_data = get_client_contact_profile_link($contact_info->id, $contact_info->first_name . " " . $contact_info->last_name, array("title" => $contact_info->email));
                } else if ($contact_info->user_type === "lead") {
                    $email_data = get_lead_contact_profile_link($contact_info->id, $contact_info->first_name . " " . $contact_info->last_name, array("title" => $contact_info->email));
                }
            } else {
                if ($data->creator_name && $data->creator_email) {
                    $email_data = $data->creator_name . " [" . $data->creator_email . "]";
                } else if ($data->creator_email) {
                    $email_data = $data->creator_email;
                } else {
                    $email_data = $recipient;
                }
            }

            if ($recipients_data) {
                $recipients_data .= ", ";
            }

            $recipients_data .= $email_data;
        }

        return $recipients_data;
    }

}

if (!function_exists('mailbox_count_unread_emails')) {

    function mailbox_count_unread_emails() {
        $mailbox_emails_model = new Mailbox\Models\Mailbox_emails_model();
        $allowed_mailboxes_ids = get_allowed_mailboxes_ids();
        return $mailbox_emails_model->count_unread_emails($allowed_mailboxes_ids);
    }

}

//prepare allowed mailbox ids
if (!function_exists('get_allowed_mailboxes_ids')) {

    function get_allowed_mailboxes_ids() {
        $instance = new Security_Controller();
        $options = array(
            "user_id" => $instance->login_user->id,
        );

        $Mailboxes_model = new \Mailbox\Models\Mailboxes_model();
        $allowed_mailboxes = $Mailboxes_model->get_details($options)->getResult();

        $allowed_mailboxes_ids = array();
        foreach ($allowed_mailboxes as $allowed_mailbox) {
            array_push($allowed_mailboxes_ids, $allowed_mailbox->id);
        }

        return $allowed_mailboxes_ids;
    }

}

/**
 * send mail
 * 
 * @param stdClass $mailbox_info
 * @param string $to
 * @param string $subject
 * @param string $message
 * @param array $optoins
 * @return true/false
 */
if (!function_exists('mailbox_send_mail')) {

    function mailbox_send_mail($mailbox_info, $to, $subject, $message, $optoins = array(), $convert_message_to_html = true) {

        //return global function if it's selected
        if ($mailbox_info->use_global_email) {
            return send_app_mail($to, $subject, $message, $optoins);
        }

        if (config('Logger')->threshold >= 6) {
            log_message('notice', 'Email: ' . $to . ' Subject: ' . $subject);
        }

        if ($mailbox_info->email_protocol === "microsoft_outlook") {
            $Outlook_smtp = new Outlook_smtp($mailbox_info);
            return $Outlook_smtp->send_app_mail($to, $subject, $message, $optoins, $convert_message_to_html);
        } else {

            $email_config = Array(
                'charset' => 'utf-8',
                'mailType' => 'html'
            );

            //added custom settings, use that
            if ($mailbox_info->email_protocol === "smtp") {
                $email_config["protocol"] = "smtp";
                $email_config["SMTPHost"] = $mailbox_info->email_smtp_host;
                $email_config["SMTPPort"] = (int) $mailbox_info->email_smtp_port;
                $email_config["SMTPUser"] = $mailbox_info->email_smtp_user;
                $email_config["SMTPPass"] = decode_password($mailbox_info->email_smtp_pass, "mailbox_email_smtp_pass");
                $email_config["SMTPCrypto"] = $mailbox_info->email_smtp_security_type;

                if (!$email_config["SMTPCrypto"]) {
                    $email_config["SMTPCrypto"] = "tls"; //for old clients, we have to set this by default
                }

                if ($email_config["SMTPCrypto"] === "none") {
                    $email_config["SMTPCrypto"] = "";
                }
            }

            $email = \CodeIgniter\Config\Services::email();
            $email->initialize($email_config);
            $email->clear(true); //clear previous message and attachment

            $email->setNewline("\r\n");
            $email->setCRLF("\r\n");
            $email->setFrom($mailbox_info->email_sent_from_address, $mailbox_info->email_sent_from_name);

            $email->setTo($to);
            $email->setSubject($subject);

            if ($convert_message_to_html) {
                $message = htmlspecialchars_decode($message);
            }

            $email->setMessage($message);

            //add attachment
            $attachments = get_array_value($optoins, "attachments");
            if (is_array($attachments)) {
                foreach ($attachments as $value) {
                    $file_path = get_array_value($value, "file_path");
                    $file_name = get_array_value($value, "file_name");
                    $email->attach(trim($file_path), "attachment", $file_name);
                }
            }

            //check reply-to
            $reply_to = get_array_value($optoins, "reply_to");
            if ($reply_to) {
                $email->setReplyTo($reply_to);
            }

            //check cc
            $cc = get_array_value($optoins, "cc");
            if ($cc) {
                $email->setCC($cc);
            }

            //check bcc
            $bcc = get_array_value($optoins, "bcc");
            if ($bcc) {
                $email->setBCC($bcc);
            }

            //send email
            if ($email->send()) {
                return true;
            } else {
                //show error message in none production version
                if (ENVIRONMENT !== 'production') {
                    throw new \Exception($email->printDebugger());
                }
                return false;
            }
        }
    }

}

if (!function_exists('mailbox_get_email_view')) {

    function mailbox_get_email_view($email) {

        if ($email->encoding_type === "readable") {

            return nl2br(link_it($email->message));
        } else if ($email->encoding_type === "raw") {

            require_once(PLUGINPATH . "Mailbox/ThirdParty/Imap/mail-mime-parser/vendor/autoload.php");

            $mail_mime_parser = \ZBateson\MailMimeParser\Message::from($email->message, false);
            if (get_mailbox_setting("mailbox_show_html_view_of_email")) {
                $email_message = $mail_mime_parser->getHtmlContent();
            } else {
                $email_message = $mail_mime_parser->getTextContent();
                $email_message = nl2br(link_it($email_message));
            }

            return $email_message;
        } else if ($email->encoding_type === "base64") {

            return base64_decode($email->message);
        }
    }

}