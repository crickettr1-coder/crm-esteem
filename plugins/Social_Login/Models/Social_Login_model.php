<?php

namespace Social_Login\Models;

use App\Models\Crud_model;

class Social_Login_model extends Crud_model {

    protected $table = null;

    function __construct() {
        $this->table = 'users';
        parent::__construct($this->table);
    }

    function authenticate_user($email) {
        $email = $this->_get_clean_value($email);

        $this->db_builder->select("id,user_type,client_id");
        $result = $this->db_builder->getWhere(array('email' => $email, 'status' => 'active', 'deleted' => 0, 'disable_login' => 0));

        $result_count = count($result->getResult());
        if (!$result_count) {
            return false;
        }

        if ($result_count === 1) {
            $user_info = $result->getRow();
            return $this->verify_user($user_info);
        } else {
            //same email on multiple client contacts
            //check with every user
            foreach ($result->getResult() as $user_info) {
                if ($this->verify_user($user_info)) {
                    return true;
                }
            }
        }
    }

    private function verify_user($user_info) {
        if ($this->_client_can_login($user_info) !== false) {
            $session = \Config\Services::session();
            $session->set('user_id', $user_info->id);

            try {
                app_hooks()->do_action('app_hook_after_signin');
            } catch (\Exception $ex) {
                log_message('error', '[ERROR] {exception}', ['exception' => $ex]);
            }

            return true;
        }
    }

    private function _client_can_login($user_info) {
        //check client login settings
        if ($user_info->user_type === "client" && get_setting("disable_client_login")) {
            return false;
        } else if ($user_info->user_type === "client") {
            //user can't be loged in if client has deleted
            $clients_table = $this->db->prefixTable('clients');

            $sql = "SELECT $clients_table.id
                    FROM $clients_table
                    WHERE $clients_table.id = $user_info->client_id AND $clients_table.deleted=0";
            $client_result = $this->db->query($sql);

            if ($client_result->resultID->num_rows !== 1) {
                return false;
            }
        }
    }

}
