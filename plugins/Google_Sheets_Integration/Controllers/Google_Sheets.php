<?php

namespace Google_Sheets_Integration\Controllers;

use App\Controllers\Security_Controller;
use Google_Sheets_Integration\Libraries\Google_Sheets_Integration;

class Google_Sheets extends Security_Controller
{

    protected $Google_Sheets_model;

    function __construct()
    {
        parent::__construct();
        if ($this->login_user->user_type === "client" && !get_google_sheets_integration_setting("client_can_access_google_sheets")) {
            app_redirect("forbidden");
        }

        $this->Google_Sheets_model = new \Google_Sheets_Integration\Models\Google_Sheets_model();
    }

    function index()
    {
        return $this->template->rander('Google_Sheets_Integration\Views\google_sheets\index');
    }

    private function can_manage_google_sheets()
    {
        if (!can_manage_google_sheets_integration()) {
            app_redirect("forbidden");
        }
    }

    function modal_form()
    {
        $this->can_manage_google_sheets();
        $id = $this->request->getPost("id");
        $model_info = $this->Google_Sheets_model->get_one($id);

        $view_data['members_and_teams_dropdown'] = json_encode(get_team_members_and_teams_select2_data_list());
        $view_data['clients_dropdown'] = $this->get_client_contacts_dropdown();
        $view_data['model_info'] = $model_info;

        return $this->template->view('Google_Sheets_Integration\Views\google_sheets\modal_form', $view_data);
    }

    private function get_client_contacts_dropdown()
    {
        $contacts_dropdown = array();

        $contacts = $this->Google_Sheets_model->get_client_contacts_list()->getResult();

        foreach ($contacts as $contact) {
            $contact_name = $contact->first_name . " " . $contact->last_name . " - " . app_lang("client") . ": " . $contact->company_name . "";
            $contacts_dropdown[] = array("id" => "contact:" . $contact->id, "text" => $contact_name);
        }

        return json_encode($contacts_dropdown);
    }

    /* list data of google sheets */

    function list_data()
    {
        $is_client = false;
        if ($this->login_user->user_type == "client") {
            $is_client = true;
        }

        $options = array(
            "is_admin" => $this->login_user->is_admin,
            "user_id" => $this->login_user->id,
            "team_ids" => $this->login_user->team_ids,
            "is_client" => $is_client
        );

        $list_data = $this->Google_Sheets_model->get_details($options)->getResult();
        $result = array();
        foreach ($list_data as $data) {
            $result[] = $this->_make_row($data);
        }
        echo json_encode(array("data" => $result));
    }

    //prepare a row of sheets list table
    private function _make_row($data)
    {
        $image_url = get_avatar($data->created_by_avatar);
        $user = "<span class='avatar avatar-xs mr10'><img src='$image_url' alt=''></span> $data->created_by_name";

        $row_data = array(
            anchor(get_uri("google_sheets/view/" . $data->id), $data->title),
            process_images_from_content($data->description),
            get_team_member_profile_link($data->created_by, $user),
            modal_anchor(get_uri("google_sheets/modal_form"), "<i data-feather='edit' class='icon-16'></i>", array("class" => "edit", "title" => app_lang('google_sheets_integration_edit_spreadsheet'), "data-post-id" => $data->id))
                . js_anchor("<i data-feather='x' class='icon-16'></i>", array('title' => app_lang('google_sheets_integration_delete_spreadsheet'), "class" => "delete", "data-id" => $data->id, "data-action-url" => get_uri("google_sheets/delete"), "data-action" => "delete-confirmation"))
        );

        return $row_data;
    }

    /* insert/update a spreadsheet */

    function save()
    {
        $this->can_manage_google_sheets();
        if (!(get_google_sheets_integration_setting("integrate_google_sheets") && get_google_sheets_integration_setting('google_sheets_authorized'))) {
            show_404();
        }

        $this->validate_submitted_data(array(
            "id" => "numeric",
            "title" => "required",
        ));

        $id = $this->request->getPost('id');

        //prepare share with data
        $share_with_team_members = $this->request->getPost('share_with_team_members');
        if ($share_with_team_members == "specific") {
            $share_with_team_members = $this->request->getPost('share_with_specific_team_members');
        }
        $share_with_client_contacts = $this->request->getPost('share_with_client_contacts');
        if ($share_with_client_contacts == "specific") {
            $share_with_client_contacts = $this->request->getPost('share_with_specific_client_contacts');
        }

        $data = array(
            "title" => $this->request->getPost('title'),
            "description" => $this->request->getPost('description'),
            "share_with_team_members" => $share_with_team_members,
            "share_with_client_contacts" => $share_with_client_contacts,
        );

        //save user_id only on insert and it will not be editable
        if (!$id) {
            $data["created_by"] = $this->login_user->id;
        }

        //add/modify the sheet in google
        //save to google first then save to RISE 
        $Google_Sheets_Integration = new Google_Sheets_Integration();
        $spreadsheet_data = $Google_Sheets_Integration->save_spreadsheet($data, $id);
        if (!$spreadsheet_data) {
            show_404();
        }

        $data = array_merge($data, $spreadsheet_data);

        $save_id = $this->Google_Sheets_model->ci_save($data, $id);
        if ($save_id) {
            echo json_encode(array("success" => true, "data" => $this->_row_data($save_id), 'id' => $save_id, 'message' => app_lang('record_saved')));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
        }
    }

    /* permanently delete a sheet */

    function delete()
    {
        $this->can_manage_google_sheets();
        $id = $this->request->getPost('id');

        $spreadsheet_info = $this->Google_Sheets_model->get_one($id);

        if ($this->Google_Sheets_model->delete($id)) {
            if (get_google_sheets_integration_setting("integrate_google_sheets") && get_google_sheets_integration_setting('google_sheets_authorized') && $spreadsheet_info->google_spreadsheet_id) {
                $Google_Sheets_Integration = new Google_Sheets_Integration();
                $Google_Sheets_Integration->delete_spreadsheet($spreadsheet_info->google_spreadsheet_id);
            }

            echo json_encode(array("success" => true, 'message' => app_lang('record_deleted')));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('record_cannot_be_deleted')));
        }
    }

    /* return a row of spreadsheet list table */

    private function _row_data($id)
    {
        $options = array("id" => $id);
        $data = $this->Google_Sheets_model->get_details($options)->getRow();

        return $this->_make_row($data);
    }

    function view($spreadsheet_id = 0)
    {
        $is_client = false;
        if ($this->login_user->user_type == "client") {
            $is_client = true;
        }

        $options = array(
            "id" => $spreadsheet_id,
            "is_admin" => $this->login_user->is_admin,
            "user_id" => $this->login_user->id,
            "is_client" => $is_client
        );

        $spreadsheet_info = $this->Google_Sheets_model->get_details($options)->getRow();
        if (!$spreadsheet_info) {
            show_404();
        }

        $view_data['model_info'] = $spreadsheet_info;

        return $this->template->rander('Google_Sheets_Integration\Views\google_sheets\view', $view_data);
    }
}
