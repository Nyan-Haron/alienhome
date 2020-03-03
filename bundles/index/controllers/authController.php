<?php
/**
 * Created by PhpStorm.
 * User: haron
 * Date: 08.04.2018
 * Time: 19:40
 */

class authController extends baseController
{
    public function index()
    {
        $this->request->setViewVariable('page', 'Authentication');
        $this->request->setViewVariable('header', '');
    }

    public function auth()
    {
        $authToken = $_GET['code'];
        $curl = curl_init("https://id.twitch.tv/oauth2/token");
        curl_setopt(
            $curl,
            CURLOPT_POSTFIELDS,
            sprintf(
                "client_id=%s&client_secret=%s&code=%s&grant_type=authorization_code&redirect_uri=http://new.alienhome.ru/auth",
                $this->conf->getTwitchAppClientId(),
                $this->conf->getTwitchAppSecret(),
                $authToken
            )
        );
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $json = json_decode(curl_exec($curl), true);
        $this->conf->devPrint('json', $json);
        curl_close($curl);
        $auth = $json['access_token'];
        $_SESSION['authToken'] = $auth;

        $info = curl_init('https://api.twitch.tv/helix/users');
        curl_setopt($info, CURLOPT_HTTPHEADER, ["Client-ID: {$this->conf->getTwitchAppClientId()}", "Authorization: Bearer {$auth}"]);
        curl_setopt($info, CURLOPT_RETURNTRANSFER, true);
        $userInfo = json_decode(curl_exec($info), true);
        curl_close($info);
        $this->conf->devPrint('userInfo', $userInfo);
        if (@$userInfo['data'][0]['login']) {
            $_SESSION['auth'] = $userInfo['data'][0]['login'];
            $_SESSION['name'] = $userInfo['data'][0]['display_name'];
            $_SESSION['id'] = $userInfo['data'][0]['id'];
        }

        $user = $this->dbConn->query("SELECT * FROM users WHERE twitch_id = '" . $userInfo['data'][0]['id'] . "';")->fetch_assoc();
        if (empty($user)) {
            $this->dbConn->query("INSERT INTO users (twitch_id, login, username) VALUES ('" . $userInfo['data'][0]['id'] . "', '" . $userInfo['data'][0]['login'] . "', '" . $userInfo['data'][0]['display_name'] . "')");
        } elseif ($user['username'] != $userInfo['data'][0]['display_name']) {
            $this->dbConn->query("UPDATE users SET login = '" . $userInfo['data'][0]['login'] . "', username = '" . $userInfo['data'][0]['display_name'] . "' WHERE twitch_id = " . $userInfo['data'][0]['id']);
        }

        header("Location: /");
    }

    public function logout()
    {
        if ($this->checkAuth()) {
            $_SESSION['auth'] = null;
            $_SESSION['authToken'] = null;
            $_SESSION['sub'] = null;
            $_SESSION['name'] = null;
            $_SESSION['id'] = null;
        }

        header("Location: /");
    }
}