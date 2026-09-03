<?php

namespace Social_Login\Libraries;

class Social_Login_Facebook {

    public function __construct() {
        //load resources
        require_once(PLUGINPATH . "Social_Login/ThirdParty/Facebook/php-graph-sdk/vendor/autoload.php");
    }

    //authorize connection
    public function authorize() {
        $fb = $this->get_fb_client();
        $helper = $fb->getRedirectLoginHelper();

        $permissions = array('email');
        $login_url = $helper->getLoginUrl(get_uri("social_login/authenticate_facebook_login"), $permissions);

        app_redirect($login_url, true);
    }

    public function authenticate_facebook_login() {
        $fb = $this->get_fb_client();
        $helper = $fb->getRedirectLoginHelper();

        try {
            $accessToken = $helper->getAccessToken();
        } catch (\Facebook\Exception\ResponseException $e) {
            // When Graph returns an error
            echo 'Graph returned an error: ' . $e->getMessage();
            exit;
        } catch (\Facebook\Exception\SDKException $e) {
            // When validation fails or other local issues
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            exit;
        }

        if (!isset($accessToken)) {
            if ($helper->getError()) {
                header('HTTP/1.0 401 Unauthorized');
                echo "Error: " . $helper->getError() . "\n";
                echo "Error Code: " . $helper->getErrorCode() . "\n";
                echo "Error Reason: " . $helper->getErrorReason() . "\n";
                echo "Error Description: " . $helper->getErrorDescription() . "\n";
            } else {
                header('HTTP/1.0 400 Bad Request');
                echo 'Bad request';
            }
            exit;
        }

        try {
            // Get the \Facebook\GraphNode\GraphUser object for the current user.
            $response = $fb->get('/me?fields=email,first_name,last_name', $accessToken);
        } catch (\Facebook\Exception\FacebookResponseException $e) {
            // When Graph returns an error
            echo 'Graph returned an error: ' . $e->getMessage();
            exit;
        } catch (\Facebook\Exception\FacebookSDKException $e) {
            // When validation fails or other local issues
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            exit;
        }

        $user_info = $response->getGraphUser();

        return array(
            "email" => $user_info->getEmail(),
            "first_name" => $user_info->getFirstName(),
            "last_name" => $user_info->getLastName()
        );
    }

    //get client credentials
    private function get_fb_client() {
        return new \Facebook\Facebook([
            'app_id' => get_social_login_setting('facebook_login_app_id'),
            'app_secret' => get_social_login_setting('facebook_login_app_secret'),
            'default_graph_version' => 'v10.0'
        ]);
    }

}
