<?php
/**
 * Created by PhpStorm.
 * User: haron
 * Date: 06.04.2018
 * Time: 23:17
 */

class baseEntity {
    /**
     * @var integer
     */
    private $id;

    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param $id int
     * @return baseEntity
     */
    public function setId($id) {
        $this->id = $id;

        return $this;
    }
}