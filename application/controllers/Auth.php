<?php
defined('BASEPATH') or exit('No direct script access allowed');

use Google\Client as Google_Client;
use Google\Service\Oauth2 as Google_Service_Oauth2;

class Auth extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('LoginModel');
    }

    function login()
    {

        include_once APPPATH . "libraries/vendor/autoload.php";

        $google_client = new Google_Client();

        $google_client->setClientId('996101800351-ic4lde6d5h0ghltcq7lor8etfopres8l.apps.googleusercontent.com'); //Define your ClientID

        $google_client->setClientSecret('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'); //Define your Client Secret Key

        $google_client->setRedirectUri('http://localhost/ci-google/auth/login'); //Define your Redirect Uri

        $google_client->addScope('email');

        $google_client->addScope('profile');

        if (isset($_GET["code"])) {
            $token = $google_client->fetchAccessTokenWithAuthCode($_GET["code"]);

            if (!isset($token["error"])) {
                $google_client->setAccessToken($token['access_token']);

                $this->session->set_userdata('access_token', $token['access_token']);

                $google_service = new Google_Service_Oauth2($google_client);

                $data = $google_service->userinfo->get();

                $current_datetime = date('Y-m-d H:i:s');

                if (LoginModel::where('login_oauth_uid', $data['id'])->exists()) {
                    // Update data
                    $user_data = [
                        'first_name' => $data['given_name'],
                        'last_name' => $data['family_name'],
                        'email_address' => $data['email'],
                        'profile_picture' => $data['picture'],
                        'updated_at' => $current_datetime,
                    ];

                    LoginModel::where('login_oauth_uid', $data['id'])->update($user_data);
                } else {
                    //insert data
                    $user_data = [
                        'login_oauth_uid' => $data['id'],
                        'first_name' => $data['given_name'],
                        'last_name' => $data['family_name'],
                        'email_address' => $data['email'],
                        'profile_picture' => $data['picture'],
                        'created_at' => $current_datetime,
                    ];

                    LoginModel::create($user_data);
                }
                $this->session->set_userdata('user_data', $user_data);
            }
        }
        $login_button = '';
        if (!$this->session->userdata('access_token')) {
            $login_button = '<a href="' . $google_client->createAuthUrl() . '"><img src="' . base_url() . 'assets/sign-in-with-google.png" /> Sign in With Google</a>';
            $data['login_button'] = $login_button;
            $this->load->view('google_login', $data);
        } else {
            $this->load->view('google_login', $data);
        }
    }

    function logout()
    {
        $this->session->unset_userdata('access_token');

        $this->session->unset_userdata('user_data');

        redirect('auth/login');
    }
}
