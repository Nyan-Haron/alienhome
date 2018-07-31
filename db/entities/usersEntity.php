<?php
/**
 * Created by PhpStorm.
 * User: haron
 * Date: 08.04.2018
 * Time: 20:22
 */

class user extends baseEntity {
    /**
     * @var string
     */
    private $twitchId;

    /**
     * @var string
     */
    private $username;

    /**
     * @var boolean
     */
    private $pointController;

    /**
     * @var integer
     */
    private $points;

    /**
     * @var string
     */
    private $login;


    /**
     * @return string
     */
    public function getTwitchId()
    {
        return $this->twitchId;
    }

    /**
     * @param string $twitchId
     * @return user
     */
    public function setTwitchId($twitchId)
    {
        $this->twitchId = $twitchId;

        return $this;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string $username
     * @return user
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getPointController()
    {
        return $this->pointController;
    }

    /**
     * @param string $pointController
     * @return user
     */
    public function setPointController($pointController)
    {
        $this->pointController = $pointController;

        return $this;
    }

    /**
     * @return integer
     */
    public function getPoints()
    {
        return $this->points;
    }

    /**
     * @param string $points
     * @return user
     */
    public function setPoints($points)
    {
        $this->points = $points;

        return $this;
    }

    /**
     * @return string
     */
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * @param string $login
     * @return user
     */
    public function setLogin($login)
    {
        $this->login = $login;

        return $this;
    }
}