<?php
/**
 * Created by PhpStorm.
 * User: haron
 * Date: 06.04.2018
 * Time: 22:49
 */

class baseController {
    /**
     * @var mysqli
     */
    var $dbConn;

    /**
     * baseController constructor.
     * @param $dbConn mysqli
     */
    public function __construct($dbConn)
    {
        $this->dbConn = $dbConn;
    }
}