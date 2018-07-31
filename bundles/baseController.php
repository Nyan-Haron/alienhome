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
    var $authInfo = [];

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
        if (array_key_exists('auth', $_SESSION)) {
            $this->authInfo['auth'] = $_SESSION['auth'];
            $this->authInfo['authToken'] = $_SESSION['authToken'];
            $this->authInfo['sub'] = $_SESSION['sub'];
            $this->authInfo['name'] = $_SESSION['name'];
            $this->authInfo['id'] = $_SESSION['id'];

            return true;
        }

        return false;
    }
}