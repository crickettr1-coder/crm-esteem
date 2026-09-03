<?php

namespace Mailbox\Controllers;

use App\Controllers\Security_Controller;
use Mailbox\Libraries\General_imap;
use Mailbox\Libraries\Outlook_imap;
use Mailbox\Libraries\Outlook_smtp;

class Mailbox_settings extends Security_Controller {

    protected $Mailbox_settings_model;
    protected $Mailboxes_model;
    private $Outlook_imap;
    private $Outlook_smtp;

    function __construct() {
        parent::__construct();
        $this->access_only_admin_or_settings_admin();
        $this->Mailbox_settings_model = new \Mailbox\Models\Mailbox_settings_model();
        $this->Mailboxes_model = new \Mailbox\Models\Mailboxes_model();
        $this->Outlook_imap = new Outlook_imap();
        $this->Outlook_smtp = new Outlook_smtp();
    }

    function index() {
        return $this->template->rander("Mailbox\Views\settings\index");
    }

    function modal_form() {
        $this->validate_submitted_data(array(
            "id" => "numeric"
        ));

        $view_data['model_info'] = $this->Mailboxes_model->get_one($this->request->getPost('id'));

        return $this->template->view('Mailbox\Views\settings\modal_form', $view_data);
    }

    function save() {
        $this->validate_submitted_data(array(
            "id" => "numeric",
            "title" => "required",
        ));

        $id = $this->request->getPost('id');

        $data = array(
            "color" => $this->request->getPost('color'),
            "title" => $this->request->getPost('title')
        );

        if (!$id) {
            $data["permitted_users"] = $this->login_user->id; //add this admin user in permitted users list while creating a new mailbox
        }

        $save_id = $this->Mailboxes_model->ci_save($data, $id);

        if ($save_id) {
            echo json_encode(array("success" => true, "data" => $this->_row_data($save_id), 'id' => $save_id, 'message' => app_lang('record_saved')));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
        }
    }

    function list_data() {
        $list_data = $this->Mailboxes_model->get_details()->getResult();
        $result = array();
        foreach ($list_data as $data) {
            $result[] = $this->_make_row($data);
        }
        echo json_encode(array("data" => $result));
    }

    private function _row_data($id) {
        $options = array("id" => $id);
        $data = $this->Mailboxes_model->get_details($options)->getRow();
        return $this->_make_row($data);
    }

    private function _make_row($data) {
        $options = modal_anchor(get_uri("mailbox_settings/modal_form"), "<i data-feather='edit' class='icon-16'></i>", array("class" => "edit", "title" => app_lang('mailbox_edit_mailbox'), "data-post-id" => $data->id));

        $options .= js_anchor("<i data-feather='x' class='icon-16'></i>", array('title' => app_lang('mailbox_delete_mailbox'), "class" => "delete", "data-id" => $data->id, "data-action-url" => get_uri("mailbox_settings/delete"), "data-action" => "delete"));

        $status = "<span class='mt0 badge mailbox-badge-alert'>" . app_lang("unauthorized") . "</span>";
        if ($data->imap_authorized) {
            $status = "<span class='mt0 badge bg-success'>" . app_lang("authorized") . "</span>";
        }

        return array(
            "<span style='background-color:" . $data->color . "' class='color-tag float-start'></span>" . $data->title,
            modal_anchor(get_uri("mailbox_settings/imap_settings_modal_form"), "<i data-feather='download' class='icon-16'></i> " . app_lang("mailbox_incoming_email_settings"), array("title" => "IMAP " . strtolower(app_lang("settings")), "data-post-id" => $data->id, "class" => "mailbox-mr30")) .
            modal_anchor(get_uri("mailbox_settings/smtp_settings_modal_form"), "<i data-feather='upload' class='icon-16'></i> " . app_lang("mailbox_outgoing_email_settings"), array("title" => app_lang("mailbox_outgoing_email_settings"), "data-post-id" => $data->id, "class" => "mailbox-mr30")) .
            modal_anchor(get_uri("mailbox_settings/other_settings_modal_form"), "<i data-feather='settings' class='icon-16'></i> " . app_lang("mailbox_other_settings"), array("title" => app_lang("mailbox_other_settings"), "data-post-id" => $data->id)),
            $status,
            $options
        );
    }

    function delete() {
        $this->validate_submitted_data(array(
            "id" => "numeric|required"
        ));

        $id = $this->request->getPost('id');
        if ($this->request->getPost('undo')) {
            if ($this->Mailboxes_model->delete($id, true)) {
                echo json_encode(array("success" => true, "data" => $this->_row_data($id), "message" => app_lang('record_undone')));
            } else {
                echo json_encode(array("success" => false, app_lang('error_occurred')));
            }
        } else {
            if ($this->Mailboxes_model->delete($id)) {
                echo json_encode(array("success" => true, 'message' => app_lang('record_deleted')));
            } else {
                echo json_encode(array("success" => false, 'message' => app_lang('record_cannot_be_deleted')));
            }
        }
    }

    function imap_settings_modal_form() {
        $this->validate_submitted_data(array(
            "id" => "numeric|required"
        ));

        $view_data['model_info'] = $this->Mailboxes_model->get_one($this->request->getPost('id'));

        return $this->template->view('Mailbox\Views\settings\imap_settings_modal_form', $view_data);
    }

    function save_imap_settings() {
        $this->validate_submitted_data(array(
            "id" => "numeric|required",
            "imap_type" => "required",
        ));

        $id = $this->request->getPost('id');
        $imap_type = $this->request->getPost('imap_type');
        $mailbox_info = $this->Mailboxes_model->get_one($id);

        $settings = array("imap_encryption", "imap_host", "imap_port", "imap_email", "imap_password", "imap_type", "outlook_imap_client_id", "outlook_imap_client_secret");
        $data = array();

        foreach ($settings as $setting) {
            $value = $this->request->getPost($setting);

            //if user change credentials, flag as unauthorized
            if ($mailbox_info->imap_authorized && (($setting == "imap_password" && $value !== "******" && decode_password($mailbox_info->imap_password, "imap_password") != $value) || $mailbox_info->$setting != $value)) {
                $data["imap_authorized"] = 0;
            }

            if ($setting == "imap_password") {
                if ($value === "******") {
                    $value = $mailbox_info->imap_password;
                } else {
                    $value = encode_id($value, "imap_password");
                }
            }

            if (is_null($value)) {
                $value = "";
            }

            $data[$setting] = $value;
        }

        //reset failed login attempts count after running from settings page
        $data["imap_failed_login_attempts"] = 0;

        $save_id = $this->Mailboxes_model->ci_save($data, $id);

        if (!$save_id) {
            echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
        }

        //authorize imap
        $redirect_uri = "";
        if ($imap_type == "general_imap") {
            $imap = new General_imap();
            if (!$imap->authorize_imap_and_get_inbox($id)) {
                echo json_encode(array("success" => false, 'message' => app_lang("imap_error_credentials_message")));
                exit();
            }
        } else {
            $redirect_uri = $this->Outlook_imap->get_authorization_url($id);
        }

        echo json_encode(array("success" => true, "data" => $this->_row_data($save_id), 'id' => $save_id, 'message' => app_lang('record_saved'), "redirect_uri" => $redirect_uri));
    }

    function smtp_settings_modal_form() {
        $this->validate_submitted_data(array(
            "id" => "numeric|required"
        ));

        $view_data['model_info'] = $this->Mailboxes_model->get_one($this->request->getPost('id'));

        return $this->template->view('Mailbox\Views\settings\smtp_settings_modal_form', $view_data);
    }

    function save_outgoing_email_settings() {
        $this->validate_submitted_data(
                array("id" => "numeric|required")
        );

        $id = $this->request->getPost('id');
        $mailbox_info = $this->Mailboxes_model->get_one($id);

        $settings = array("email_sent_from_address", "email_sent_from_name", "email_protocol", "email_smtp_host", "email_smtp_port", "email_smtp_user", "email_smtp_pass", "email_smtp_security_type", "outlook_smtp_client_id", "outlook_smtp_client_secret");
        $data = array("use_global_email" => $this->request->getPost('use_global_email'));

        foreach ($settings as $setting) {
            $value = $this->request->getPost($setting);

            if ($setting == "email_smtp_pass") {
                if ($value === "******") {
                    $value = $mailbox_info->email_smtp_pass;
                } else {
                    $value = encode_id($value, "mailbox_email_smtp_pass");
                }
            }

            if (is_null($value)) {
                $value = "";
            }

            $data[$setting] = $value;
        }

        $save_id = $this->Mailboxes_model->ci_save($data, $id);
        if (!$save_id) {
            echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
            return false;
        }
        
        $email_protocol = $this->request->getPost("email_protocol");

        //send test email
        $test_email_to = $this->request->getPost("send_test_mail_to");
        if (!$test_email_to && $email_protocol !== "microsoft_outlook") {
            echo json_encode(array("success" => true, 'message' => app_lang('settings_updated')));
            return false;
        }

        //send test email
        $redirect_uri = "";
        if ($email_protocol === "microsoft_outlook") {
            //save test email address temporarily for outlook email
            $this->Mailbox_settings_model->save_setting("send_test_mail_to_" . $id, $test_email_to);
            $redirect_uri = $this->Outlook_smtp->get_authorization_url($id);
        } else {
            $email_config = Array(
                'charset' => 'utf-8',
                'mailType' => 'html'
            );
            if ($this->request->getPost("email_protocol") === "smtp") {
                $email_config["protocol"] = "smtp";
                $email_config["SMTPHost"] = $this->request->getPost("email_smtp_host");
                $email_config["SMTPPort"] = (int) $this->request->getPost("email_smtp_port");
                $email_config["SMTPUser"] = $this->request->getPost("email_smtp_user");

                $email_smtp_pass = $this->request->getPost("email_smtp_pass");
                if ($email_smtp_pass === "******") {
                    $email_smtp_pass = decode_password($mailbox_info->email_smtp_pass, "mailbox_email_smtp_pass");
                }
                $email_config["SMTPPass"] = $email_smtp_pass;
                $email_config["SMTPCrypto"] = $this->request->getPost("email_smtp_security_type");
                if ($email_config["SMTPCrypto"] === "none") {
                    $email_config["SMTPCrypto"] = "";
                }
            }

            $email = \CodeIgniter\Config\Services::email();
            $email->initialize($email_config);

            $email->setNewline("\r\n");
            $email->setCRLF("\r\n");
            $email->setFrom($this->request->getPost("email_sent_from_address"), $this->request->getPost("email_sent_from_name"));

            $email->setTo($test_email_to);
            $email->setSubject("Test message");
            $email->setMessage("This is a test message to check mail configuration.");

            if ($email->send()) {
                echo json_encode(array("success" => true, 'message' => app_lang('test_mail_sent')));

                return false;
            } else {
                log_message('error', $email->printDebugger());
                echo json_encode(array("success" => false, 'message' => app_lang('test_mail_send_failed')));

                return false;
            }
        }

        echo json_encode(array("success" => true, "data" => $this->_row_data($save_id), 'id' => $save_id, 'message' => app_lang('record_saved'), 'redirect_uri' => $redirect_uri));
    }

    function other_settings_modal_form() {
        $this->validate_submitted_data(array(
            "id" => "numeric|required"
        ));

        $view_data['model_info'] = $this->Mailboxes_model->get_one($this->request->getPost('id'));

        $team_members = $this->Users_model->get_all_where(array("deleted" => 0, "user_type" => "staff"))->getResult();
        $members_dropdown = array();

        foreach ($team_members as $team_member) {
            $members_dropdown[] = array("id" => $team_member->id, "text" => $team_member->first_name . " " . $team_member->last_name);
        }

        $view_data['members_dropdown'] = json_encode($members_dropdown);

        return $this->template->view('Mailbox\Views\settings\other_settings_modal_form', $view_data);
    }

    function save_other_settings() {
        $this->validate_submitted_data(array(
            "id" => "numeric|required",
        ));

        $id = $this->request->getPost('id');
        $data = array(
            "permitted_users" => $this->request->getPost("permitted_users") ? $this->request->getPost("permitted_users") : "",
            "signature" => $this->request->getPost("signature") ? decode_ajax_post_data($this->request->getPost("signature")) : "",
            "send_bcc_to" => $this->request->getPost("send_bcc_to") ? $this->request->getPost("send_bcc_to") : "",
        );

        $save_id = $this->Mailboxes_model->ci_save($data, $id);

        if ($save_id) {
            echo json_encode(array("success" => true, 'message' => app_lang('record_saved')));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
        }
    }
}
