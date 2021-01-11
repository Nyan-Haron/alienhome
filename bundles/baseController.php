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
     * @var Db
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

//        $this->conf->devPrint('session', $_SESSION);
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
     * @param bool $forced
     * @return bool
     */
    public function checkSub($forced = false)
    {
        $subChecker = $this->dbConn->query("SELECT * FROM users WHERE twitch_id = 40955336")->fetch_assoc();

//        if ($this->isAuth && $this->authInfo['id'] == '82304594') { // Харон
        if ($this->isAuth && $this->authInfo['id'] == '40955336') { // Илья

            if(strtotime($subChecker['last_sub_date']) + 60*60*12 < time() || ($forced && strtotime($subChecker['last_sub_date']) + 60*10 < time())) {
                $subList = '<p>Привет, Илья! Проверяю твоих сабов.</p><ul>';
                $token = $this->authInfo['authToken'];
//                $this->conf->devPrint("authInfo", $this->authInfo);
                $cursor = '';
                $tries = 0;
                $extraTries = 0;
                $subCount = 0;
                $pageVolume = 0;
                do {
//                    $subCheck = curl_init("https://api.twitch.tv/helix/subscriptions?broadcaster_id=82304594&after=$cursor");
                    $subCheck = curl_init("https://api.twitch.tv/helix/subscriptions?broadcaster_id=40955336&after=$cursor");
                    curl_setopt(
                        $subCheck,
                        CURLOPT_HTTPHEADER,
                        ["Client-ID: {$this->conf->getTwitchAppClientId()}", "Authorization: Bearer {$token}"]
                    );
                    curl_setopt($subCheck, CURLOPT_RETURNTRANSFER, true);
                    $subInfo = json_decode(curl_exec($subCheck), true);
                    curl_close($subCheck);
//                    $this->conf->devPrint('subInfo', $subInfo);

                    if ($subInfo === null) {
                        header("Location: /auth/logout");
                    }

                    if (isset($subInfo['data'])) {
                        $pageVolume = count($subInfo['data']);

                        foreach ($subInfo['data'] as $sub) {
                            $subCheckUser = $this->dbConn->query("SELECT * FROM users WHERE twitch_id = {$sub['user_id']}")->fetch_assoc();
                            $q = $this->dbConn->query("SELECT * FROM subscriptions WHERE user_twitch_id = {$subCheckUser['twitch_id']} ORDER BY check_date DESC");
                            if ($subCheckUser !== null) {
                                $subCount++;
                                if ($q) {
                                    $prevSub = $q->fetch_assoc();
                                    $gap = floor(time() / 86400) - floor(strtotime($prevSub['check_date']) / 86400);
                                    $cumulativeSubDays = $prevSub['overall_sub_days'] + $gap;
                                } else {
                                    $cumulativeSubDays = 1;
                                }
                                $this->dbConn->query("INSERT INTO subscriptions (user_twitch_id, overall_sub_days) VALUES ('{$subCheckUser['twitch_id']}', {$cumulativeSubDays})");
                                $this->dbConn->query("UPDATE users SET last_sub_date = NOW() WHERE twitch_id = {$subCheckUser['twitch_id']}");
                                $subList .= "<li>{$subCheckUser['username']} ({$subCheckUser['twitch_id']}) &mdash; $cumulativeSubDays дней</li>";
                            }
                        }

                        if (!isset($subInfo['pagination']['cursor'])) break;
                        $cursor = $subInfo['pagination']['cursor'];
                    } else {
                        echo 'При проверке сабов что-то пошло не так и твитч ответил вот что: ';
                        var_dump($subInfo);
                        $tries++;
                    }

                    $extraTries++;
                } while ($pageVolume > 0 && $tries < 10 && $extraTries < 100);

                $subList .= '</ul><p>Найдено сабов: ' . $subCount . '</p>';
                $this->request->setViewVariable('subList', $subList);

                $_SESSION['sub'] = $this->authInfo['sub'] = true;

                return true;
            }
        }

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

        $user = $this->dbConn->query("SELECT * FROM users WHERE twitch_id = {$_SESSION['id']}")->fetch_assoc();

        if (strtotime($user['last_sub_date']) >= strtotime($subChecker['last_sub_date']) - 60*60) {
            $_SESSION['sub'] = $this->authInfo['sub'] = true;
            return true;
        }

        $_SESSION['sub'] = $this->authInfo['sub'] = false;

        return false;
    }

    protected function fixOverallSubDays() {

//        $r = $this->dbConn->query('SELECT * FROM users WHERE twitch_id != ""');
//        while ($user = $r->fetch_assoc()) {
//            $nextSub = $this->dbConn
//                ->query("SELECT * FROM subscriptions WHERE user_twitch_id = {$user['twitch_id']} AND overall_sub_days = 0 ORDER BY check_date ASC")
//                ->fetch_assoc();
//
//            if ($nextSub !== null) {
//                $this->dbConn->query("UPDATE subscriptions SET overall_sub_days = 1 WHERE id = {$nextSub['id']}");
//                var_dump("Set overall_sub_days to 1 for {$nextSub['id']}");
//            }
//        }

        $r = $this->dbConn->query('SELECT * FROM subscriptions WHERE user_twitch_id = 40955336 ORDER BY check_date ASC');
        $subChecks = [];
        while ($check = $r->fetch_assoc()) {
            $subChecks[] = new DateTime($check['check_date']);
        }

        $r = $this->dbConn->query('SELECT * FROM users WHERE twitch_id != ""');
        while ($user = $r->fetch_assoc()) {
            $this->conf->devPrint('User', $user['username']);
            $prevSub = $this->dbConn
                ->query("SELECT * FROM subscriptions WHERE user_twitch_id = {$user['twitch_id']} AND overall_sub_days != 0 ORDER BY check_date DESC")
                ->fetch_assoc();
            $nextSub = $this->dbConn
                ->query("SELECT * FROM subscriptions WHERE user_twitch_id = {$user['twitch_id']} AND overall_sub_days = 0 ORDER BY check_date ASC")
                ->fetch_assoc();
            if ($prevSub !== null) {
                $prevCheck = new DateTime($prevSub['check_date']);
                $streak = false;
                foreach ($subChecks as $subCheck) {
//                    $this->conf->devPrint('check', $subCheck->format('Y-m-d H:i:s'));
                    if ($subCheck->getTimestamp() - $prevCheck->getTimestamp() > -60) {
                        if (abs($prevCheck->getTimestamp() - $subCheck->getTimestamp()) < 60) {
                            var_dump("Found previous sub check: {$prevCheck->format('Y-m-d H:i:s')}");
                            $streak = true;
                        } elseif (abs((new DateTime($nextSub['check_date']))->getTimestamp() - $subCheck->getTimestamp()) < 60) {
                            break;
                        } else {
                            $streak = false;
                        }
                    }
                }
                var_dump($streak, $nextSub);

                if ($nextSub !== null) {
                    if ($streak) {
                        $prevCheckDay = floor((new DateTime($prevSub['check_date']))->getTimestamp() / 86400);
                        $nextCheckDay = floor((new DateTime($nextSub['check_date']))->getTimestamp() / 86400);
                        $gap = $nextCheckDay - $prevCheckDay;
                    } else {
                        $gap = 1;
                    }
                    $this->conf->devPrint('Gap', $gap);
                    $newOverallSubDays = (int) $prevSub['overall_sub_days'] + $gap;
//                    $this->dbConn->query("UPDATE subscriptions SET overall_sub_days = $newOverallSubDays WHERE id = {$nextSub['id']}");
                    var_dump("Set overall_sub_days to $newOverallSubDays for {$nextSub['id']}");
                }
            }
        }
    }

    public function loadHeader()
    {
        $this->request->setViewVariable('header', file_get_contents($this->conf->getPath('/public/bundles/index/views/header.html')));
    }
}