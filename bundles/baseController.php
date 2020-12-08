<?php
/**
 * Created by PhpStorm.
 * User: haron
 * Date: 06.04.2018
 * Time: 22:49
 */

class baseController
{
    /**
     * @var mysqli
     */
    var $dbConn;
    /**
     * @var Request
     */
    var $request;
    /**
     * @var Conf
     */
    var $conf;
    /**
     * @var array
     */
    var $authInfo = [];
    /**
     * @var bool
     */
    var $isAuth = false;

    /**
     * baseController constructor.
     * @param $dbConn mysqli
     * @param $request Request
     * @param $conf Conf
     */
    public function __construct($dbConn, $request, $conf)
    {
        $this->dbConn = $dbConn;
        $this->request = $request;
        $this->conf = $conf;
    }

    /**
     * @return bool
     */
    public function checkAuth()
    {
        if ($this->isAuth) {
            return true;
        }

        $this->conf->devPrint('session', $_SESSION);
        if (!empty($_SESSION['auth'])) {
            $this->authInfo['auth'] = $_SESSION['auth'];
            $this->authInfo['authToken'] = $_SESSION['authToken'];
            $this->authInfo['sub'] = !empty($_SESSION['sub']) ? $_SESSION['sub'] : false;
            $this->authInfo['name'] = $_SESSION['name'];
            $this->authInfo['id'] = $_SESSION['id'];
            $this->isAuth = true;

            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function checkSub()
    {
        if (!$this->isAuth) {
            return false;
        }
        if ($this->authInfo['sub']) {
            return true;
        }

        if (!empty($_SESSION['sub'])) {
            $this->authInfo['sub'];
            return $_SESSION['sub'];
        }

//        if ($this->isAuth && $this->authInfo['id'] == '82304594') { // Харон
        if ($this->isAuth && $this->authInfo['id'] == '40955336') { // Илья
            $token = $this->authInfo['authToken'];
            $this->conf->devPrint("authInfo", $this->authInfo);
            $cursor = '';
            $tries = 0;
            do {
//                $subCheck = curl_init("https://api.twitch.tv/helix/subscriptions?broadcaster_id=82304594&after=$cursor");
                $subCheck = curl_init("https://api.twitch.tv/helix/subscriptions?broadcaster_id=40955336&after=$cursor");
                curl_setopt(
                    $subCheck,
                    CURLOPT_HTTPHEADER,
                    ["Client-ID: {$this->conf->getTwitchAppClientId()}", "Authorization: Bearer {$token}"]
                );
                curl_setopt($subCheck, CURLOPT_RETURNTRANSFER, true);
                $subInfo = json_decode(curl_exec($subCheck), true);
                curl_close($subCheck);
                if ($subInfo === null) {
                    header("Location: /auth/logout");
                }
                $this->conf->devPrint('subInfo', $subInfo);
                foreach ($subInfo['data'] as $sub) {
                    $user = $this->dbConn->query("SELECT * FROM users WHERE twitch_id = {$sub['user_id']}")->fetch_assoc();
                    if ($user !== null) {
                        $this->dbConn->query("INSERT INTO subscriptions (user_twitch_id) VALUES ('{$user['twitch_id']}')");
                        $this->dbConn->query("UPDATE users SET last_sub_date = NOW() WHERE twitch_id = {$user['twitch_id']}");
                    }
                }
                $cursor = $subInfo['pagination']['cursor'];
                $tries++;
            } while (count($subInfo['data']) > 0 && $tries < 100);

            $_SESSION['sub'] = $this->authInfo['sub'] = true;

            return true;
        }

        $str = "SELECT * FROM subscriptions WHERE user_twitch_id = {$this->authInfo['id']} AND check_date >= NOW() - INTERVAL 30 DAY ORDER BY check_date DESC LIMIT 1";
        $this->conf->devPrint('str', $str);
        $sub = $this->dbConn->query($str)->fetch_assoc();

        if ($sub !== null) {
            $_SESSION['sub'] = $this->authInfo['sub'] = true;
            return true;
        }

        $_SESSION['sub'] = $this->authInfo['sub'] = false;

        return false;
    }

    public function loadHeader()
    {
        $this->request->setViewVariable('header', file_get_contents($this->conf->getPath('/public/bundles/index/views/header.html')));
    }
}