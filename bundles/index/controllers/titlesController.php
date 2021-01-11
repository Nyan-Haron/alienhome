<?php
/**
 * Created by PhpStorm.
 * User: haron
 * Date: 14.07.2020
 * Time: 17:10
 */

class titlesController extends baseController
{
    public function index()
    {
        $this->request->setLayout('');

        if (isset($_GET['user'])) {
            $userName = $_GET['user'];
            $user = $this->dbConn->query("SELECT * FROM users WHERE login = '$userName'")->fetch_assoc();

            if (empty($user)) {
                echo "User $userName does not exist";
            } else {
                if (isset($_GET['title'])) {
                    $title = trim(str_replace($userName, '', $_GET['title']));
                    $this->dbConn->query("INSERT INTO user_titles (user_twitch_id, title) VALUES ('{$user['twitch_id']}', '$title')");
//            echo "{$user['username']} is now '$title'";
                }
            }

            $r = $this->dbConn->query("SELECT * FROM user_titles WHERE user_twitch_id = '{$user['twitch_id']}'");

            $titles = [];
            while ($title = $r->fetch_assoc()) {
                $titles[] = $title['title'];
            }

            echo implode(', ', $titles) . ' ' . $user['username'];
        }
    }
}