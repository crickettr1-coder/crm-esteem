<?php

namespace Social_Login\Libraries;

class Social_Login_Google {

    public function __construct() {
        //load resources
        require_once(PLUGINPATH . "Social_Login/ThirdParty/Google/google-api-php-client-v2.17.0/vendor/autoload.php");
    }

    //authorize connection
    public function authorize() {
        $client = $this->_get_client_credentials();
        $authUrl = $client->createAuthUrl();
        app_redirect($authUrl, true);
    }

    public function authenticate_google_login($auth_code) {
        $client = $this->_get_client_credentials();

        //Exchange authorization code for an access token.
        $accessToken = $client->fetchAccessTokenWithAuthCode($auth_code);
        $error = get_array_value($accessToken, "error");

        if ($error)
            die($error);

        $client->setAccessToken($accessToken);

        //get profile info
        $google_oauth = new \Google_Service_Oauth2($client);
        $google_account_info = $google_oauth->userinfo->get();

        return array(
            "email" => $google_account_info->email,
            "first_name" => $google_account_info->given_name,
            "last_name" => $google_account_info->family_name
        );
    }

    //get client credentials
    private function _get_client_credentials() {
        $url = get_uri("social_login/authenticate_google_login");

        $client = new \Google_Client();
        $client->setApplicationName(get_setting('app_title'));
        $client->setRedirectUri($url);
        $client->setClientId(get_social_login_setting('google_login_client_id'));
        $client->setClientSecret(get_social_login_setting('google_login_client_secret'));
        $client->addScope("email");
        $client->addScope("profile");
        $client->setPrompt('select_account consent');

        return $client;
    }

}
