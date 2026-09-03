<?php

namespace Mailbox\Libraries;

use App\Controllers\App_Controller;

class Outlook_imap {

    private $ci;
    private $login_url;
    private $graph_url;
    private $redirect_uri;
    private $mailbox_info;
    private $responseCode = 0;
    protected $Mailboxes_model;
    protected $Mailbox_emails_model;

    public function __construct($mailbox_info = null) {
        $this->ci = new App_Controller();
        $this->login_url = "https://login.microsoftonline.com/common/oauth2/v2.0";
        $this->graph_url = "https://graph.microsoft.com/beta/me/";
        $this->redirect_uri = get_uri("mailbox_microsoft_api/save_outlook_imap_access_token");
        $this->mailbox_info = $mailbox_info;

        $this->Mailboxes_model = new \Mailbox\Models\Mailboxes_model();
        $this->Mailbox_emails_model = new \Mailbox\Models\Mailbox_emails_model();
    }

    public function process_emails() {
        $messages = $this->do_request("GET", 'mailFolders/inbox/messages');

        foreach ($messages->value as $message) {
            //create tickets for unread mails
            if (!$message->isRead) {
                $this->_create_email_from_imap($message);

                //mark the mail as read
                $this->do_request("PATCH", "messages/$message->id", array("isRead" => true));
            }
        }
    }

    //authorize connection
    public function get_authorization_url($mailbox_id = 0) {
        if (!$mailbox_id) {
            return false;
        }

        $mailbox_info = $this->Mailboxes_model->get_one($mailbox_id);

        $url = "$this->login_url/authorize?";
        $auth_array = array(
            "client_id" => $mailbox_info->outlook_imap_client_id,
            "response_type" => "code",
            "redirect_uri" => $this->redirect_uri . "/$mailbox_id",
            "response_mode" => "query",
            "scope" => "offline_access%20user.read%20IMAP.AccessAsUser.All%20Mail.ReadWrite",
        );

        foreach ($auth_array as $key => $value) {
            $url .= "$key=$value";

            if ($key !== "scope") {
                $url .= "&";
            }
        }

        return $url;
    }

    private function common_error_handling_for_curl($result, $err, $decode_result = true) {
        if ($decode_result) {
            try {
                $result = json_decode($result);
            } catch (\Exception $ex) {
                echo json_encode(array("success" => false, 'message' => $ex->getMessage()));
                log_message('error', $ex); //log error for every exception
                exit();
            }
        }

        if ($err) {
            //got curl error
            echo json_encode(array("success" => false, 'message' => "cURL Error #:" . $err));
            log_message('error', $err); //log error for every exception
            exit();
        }

        if (isset($result->error_description) && $result->error_description) {
            //got error message from curl
            echo json_encode(array("success" => false, 'message' => $result->error_description));
            log_message('error', $result->error_description); //log error for every exception
            exit();
        }

        if (isset($result->error) && $result->error &&
                isset($result->error->message) && $result->error->message &&
                isset($result->error->code) && $result->error->code !== "InvalidAuthenticationToken") {
            //got error message from curl
            echo json_encode(array("success" => false, 'message' => $result->error->message));
            log_message('error', $result->error->message); //log error for every exception
            exit();
        }

        return $result;
    }

    //fetch access token with auth code and save to database
    public function save_access_token($mailbox_id = 0, $code = "", $is_refresh_token = false) {
        if (!$mailbox_id) {
            return false;
        }

        $mailbox_info = $this->Mailboxes_model->get_one($mailbox_id);

        $fields = array(
            "client_id" => $mailbox_info->outlook_imap_client_id,
            "client_secret" => $mailbox_info->outlook_imap_client_secret,
            "redirect_uri" => $this->redirect_uri . "/$mailbox_id",
            "scope" => "IMAP.AccessAsUser.All Mail.ReadWrite",
            "grant_type" => "authorization_code",
        );

        if ($is_refresh_token) {
            $fields["refresh_token"] = $code;
            $fields["grant_type"] = "refresh_token";
        } else {
            $fields["code"] = $code;
        }

        $fields_string = http_build_query($fields);

        //open connection
        $ch = curl_init();

        //set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, "$this->login_url/token");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Cache-Control: no-cache",
            "Content-Type: application/x-www-form-urlencoded",
        ));

        //So that curl_exec returns the contents of the cURL;
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        //execute post
        $result = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        $result = $this->common_error_handling_for_curl($result, $err);

        if (!(
                (!$is_refresh_token && isset($result->access_token) && isset($result->refresh_token)) ||
                ($is_refresh_token && isset($result->access_token))
                )) {
            echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
            exit();
        }

        if ($is_refresh_token) {
            //while refreshing token, refresh_token value won't be available
            $result->refresh_token = $code;
        }

        // Save the token to database
        $new_access_token = json_encode($result);

        if ($new_access_token) {
            $data = array("outlook_imap_oauth_access_token" => $new_access_token);
            $this->Mailboxes_model->ci_save($data, $mailbox_id);
            $this->refresh_mailbox_info($mailbox_id);

            if (!$is_refresh_token) {
                //store email address for the first time
                $user_info = $this->do_request("GET");
                if (isset($user_info->userPrincipalName) && $user_info->userPrincipalName) {

                    $data = array("outlook_imap_email" => $user_info->userPrincipalName);
                    $this->Mailboxes_model->ci_save($data, $mailbox_id);
                    $this->refresh_mailbox_info($mailbox_id);
                } else {
                    echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
                    exit();
                }
            }

            //got the valid access token. store to setting that it's authorized
            $data = array("imap_authorized" => 1);
            $this->Mailboxes_model->ci_save($data, $mailbox_id);
        }
    }

    private function refresh_mailbox_info($mailbox_id) {
        $mailbox_info = $this->Mailboxes_model->get_one($mailbox_id);
        $this->mailbox_info = $mailbox_info;
    }

    private function headers($access_token) {
        return array(
            'Authorization: Bearer ' . $access_token,
            'Content-Type: application/json'
        );
    }

    private function do_request($method, $path = "", $body = array(), $decode_result = true) {
        if (is_array($body)) {
            // Treat an empty array in the body data as if no body data was set
            if (!count($body)) {
                $body = '';
            } else {
                $body = json_encode($body);
            }
        }

        $oauth_access_token = json_decode($this->mailbox_info->outlook_imap_oauth_access_token);

        $method = strtoupper($method);
        $url = $this->graph_url . $path;

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers($oauth_access_token->access_token));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if (in_array($method, array('DELETE', 'PATCH', 'POST', 'PUT', 'GET'))) {

            // All except DELETE can have a payload in the body
            if ($method != 'DELETE' && strlen($body)) {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }

            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }

        $result = curl_exec($ch);
        $err = curl_error($ch);
        $this->responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = $this->common_error_handling_for_curl($result, $err, $decode_result);

        if (isset($result->error->code) && $result->error->code === "InvalidAuthenticationToken") {
            //access token is expired
            $this->save_access_token($this->mailbox_info->id, $oauth_access_token->refresh_token, true);
            return $this->do_request($method, $path, $body, $decode_result);
        }

        return $result;
    }

    private function _create_email_from_imap($message_info = "") {
        if (!$message_info) {
            return false;
        }

        $email = $message_info->from->emailAddress->address;
        if (!$email) {
            return false;
        }

        $creator_name = $message_info->from->emailAddress->name;
        $subject = $message_info->subject;
        $now = get_current_utc_time();

        //check if there has any client containing this email address
        //if so, go through with the client id
        $contact_info = $this->Mailbox_emails_model->get_user_of_email($email)->getRow();
        $contact_id = isset($contact_info->id) ? $contact_info->id : 0;

        //check if the email is exists on the app
        //if not, that will be considered as a new email
        //but for this case, it's a replying email. we've to parse the message
        $email_id = $this->_get_email_id_from_subject($subject, $email, $contact_id);

        $email_data = array(
            "subject" => $subject ? $subject : "(No subject)",
            "created_by" => $contact_id,
            "created_at" => $now,
            "last_activity_at" => $now,
            "creator_name" => $creator_name ? $creator_name : "",
            "creator_email" => $email,
            "email_id" => $email_id,
            "mailbox_id" => $this->mailbox_info->id,
            "encoding_type" => "base64"
        );

        $email_data = clean_data($email_data);

        //don't clean email content
        //we are not getting the raw content of the email for outlook
        //store the encoded content with base64 encoding system
        $email_data["message"] = base64_encode($this->get_email_message($message_info));

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

    //get content of email
    private function get_email_message($message_info) {
        $description = $message_info->body->content;
        return $description;
    }

    //get email id
    private function _get_email_id_from_subject($subject = "", $email = "", $contact_id = 0) {
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
        $email_info = $this->Mailbox_emails_model->get_email_with_subject($main_subject, $email, $contact_id, $this->mailbox_info->id)->getRow();

        return isset($email_info->id) ? $email_info->id : 0;
    }

    //download attached files to local
    private function _prepare_attachment_data_of_mail($message_info = "") {
        $files_data = array();

        if ($message_info && $message_info->hasAttachments) {
            $attachments = $this->do_request("GET", "messages/$message_info->id/attachments");

            foreach ($attachments->value as $attachment) {
                $content = $this->do_request("GET", "messages/$message_info->id/attachments/$attachment->id/" . '$value', array(), false);

                $file_name = $attachment->name;
                $file_name = str_replace("/", "-", $file_name);
                $file_data = move_temp_file($attachment->name, get_mailbox_setting("mailbox_email_file_path"), "mailbox", NULL, "", $content);

                array_push($files_data, $file_data);
            }
        }

        return $files_data;
    }

}
