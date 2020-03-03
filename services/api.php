<?php
/**
 * Created by PhpStorm.
 * User: haron
 * Date: 17.09.2019
 * Time: 20:44
 */

class Api
{
    private static $conf;

    public static function checkAuth()
    {
        require_once '../core/conf.php';
        self::$conf = new Conf('dev');

        $curl = curl_init("https://id.twitch.tv/oauth2/token");
        curl_setopt(
            $curl,
            CURLOPT_POSTFIELDS,
            sprintf(
                "client_id=%s&client_secret=%s&&grant_type=client_credentials&scope=channel:read:subscriptions",
                self::$conf->getTwitchAppClientId(),
                self::$conf->getTwitchAppSecret()
            )
        );
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $json = json_decode(curl_exec($curl), true);
        self::$conf->devPrint('json', $json);
        curl_close($curl);
        $auth = $json['access_token'];
        $_SESSION['authToken'] = $auth;

        $info = curl_init('https://api.twitch.tv/helix/users/follows?to_id=40955336');
        curl_setopt($info, CURLOPT_HTTPHEADER, ["Authorization: Bearer {$auth}"]);
        curl_setopt($info, CURLOPT_RETURNTRANSFER, true);
        $userInfo = json_decode(curl_exec($info), true);
        curl_close($info);
        self::$conf->devPrint('userInfo', $userInfo);


//        header("Location: /");
    }
}