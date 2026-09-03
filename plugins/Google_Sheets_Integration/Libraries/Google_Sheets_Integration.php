<?php

namespace Google_Sheets_Integration\Libraries;

class Google_Sheets_Integration
{

    private $Google_Sheets_Integration_settings_model;

    public function __construct()
    {
        $this->Google_Sheets_Integration_settings_model = new \Google_Sheets_Integration\Models\Google_Sheets_Integration_settings_model();

        //load resources
        require_once(PLUGINPATH . "Google_Sheets_Integration/ThirdParty/google-api-php-client-2-16-0/vendor/autoload.php");
    }

    //authorize connection
    public function authorize()
    {
        $client = $this->_get_client_credentials();
        $this->_check_access_token($client, true);
    }

    public function save_spreadsheet($data, $id)
    {
        $service = $this->_get_google_sheets_service();
        $spreadsheet_data = array();

        if ($id) {
            $Google_Sheets_model = new \Google_Sheets_Integration\Models\Google_Sheets_model();
            $google_spreadsheet_id = $Google_Sheets_model->get_one($id)->google_spreadsheet_id;
            $spreadsheetId = $this->_updateSpreadsheet($service, $google_spreadsheet_id, $data);
        } else {
            $spreadsheetId = $this->_createSpreadsheet($service, $data);
        }

        if ($spreadsheetId) {
            $spreadsheet_data["google_spreadsheet_id"] = $spreadsheetId;
        }
        return $spreadsheet_data;
    }

    private function _updateSpreadsheet($service, $spreadsheetId, $data)
    {
        // Create a batch update request
        $request = new \Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
            'requests' => [
                'updateSpreadsheetProperties' => [
                    'properties' => [
                        'title' => get_array_value($data, "title")
                    ],
                    'fields' => 'title'
                ]
            ]
        ]);

        // Execute the batch update request
        $service->spreadsheets->batchUpdate($spreadsheetId, $request);
        return $spreadsheetId;
    }

    // Function to create a new spreadsheet
    private function _createSpreadsheet($service, $data)
    {
        $spreadsheet = new \Google_Service_Sheets_Spreadsheet([
            'properties' => [
                'title' => get_array_value($data, "title"),
            ],
        ]);

        $spreadsheet = $service->spreadsheets->create($spreadsheet, ['fields' => 'spreadsheetId']);
        $this->_make_spreadsheet_public($spreadsheet->spreadsheetId);

        return $spreadsheet->spreadsheetId;
    }

    private function _get_google_drive_service()
    {
        $client = $this->_get_client_credentials();
        $this->_check_access_token($client);

        return new \Google_Service_Drive($client);
    }

    private function _make_spreadsheet_public($spreadsheetId)
    {
        // Set the visibility of the spreadsheet to public
        $driveService = $this->_get_google_drive_service();
        $newPermission = new \Google_Service_Drive_Permission([
            'type' => 'anyone',
            'role' => 'writer'
        ]);

        $driveService->permissions->create($spreadsheetId, $newPermission);
    }

    //check access token
    private function _check_access_token($client, $redirect_to_settings = false)
    {
        //load previously authorized token from database, if it exists.
        $accessToken = get_google_sheets_integration_setting("google_sheets_oauth_access_token");
        if (get_google_sheets_integration_setting("google_sheets_authorized") && $accessToken && !$redirect_to_settings) {
            $client->setAccessToken(json_decode($accessToken, true));
        }

        // If there is no previous token or it's expired.
        if ($client->isAccessTokenExpired()) {
            // Refresh the token if possible, else fetch a new one.
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                if ($redirect_to_settings) {
                    app_redirect("settings/integration/google_sheets");
                }
            } else {
                $authUrl = $client->createAuthUrl();
                app_redirect($authUrl, true);
            }
        } else {
            if ($redirect_to_settings) {
                app_redirect("settings/integration/google_sheets");
            }
        }
    }

    //fetch access token with auth code and save to database
    public function save_access_token($auth_code)
    {
        $client = $this->_get_client_credentials();

        // Exchange authorization code for an access token.
        $accessToken = $client->fetchAccessTokenWithAuthCode($auth_code);

        $error = get_array_value($accessToken, "error");

        if ($error)
            die($error);


        $client->setAccessToken($accessToken);

        // Save the token to database
        $new_access_token = json_encode($client->getAccessToken());

        if ($new_access_token) {
            $this->Google_Sheets_Integration_settings_model->save_setting("google_sheets_oauth_access_token", $new_access_token);

            //got the valid access token. store to setting that it's authorized
            $this->Google_Sheets_Integration_settings_model->save_setting("google_sheets_authorized", "1");
        }
    }
    //get service
    private function _get_google_sheets_service()
    {
        $client = $this->_get_client_credentials();
        $this->_check_access_token($client);

        return new \Google_Service_Sheets($client);
    }

    //delete file
    public function delete_spreadsheet($spreadsheet_id)
    {
        $driveService = $this->_get_google_drive_service();
        $driveService->files->delete($spreadsheet_id);
    }

    //get client credentials
    private function _get_client_credentials()
    {
        $url = get_uri("google_sheets_integration_settings/save_access_token");

        $client = new \Google_Client();
        $client->setApplicationName(get_setting('app_title'));
        $client->setRedirectUri($url);
        $client->setClientId(get_google_sheets_integration_setting('google_sheets_client_id'));
        $client->setClientSecret(get_google_sheets_integration_setting('google_sheets_client_secret'));
        $client->setScopes(array(
            \Google_Service_Sheets::SPREADSHEETS,
            \Google_Service_Drive::DRIVE
        ));
        $client->setAccessType("offline");
        $client->setPrompt('select_account consent');

        return $client;
    }
}
