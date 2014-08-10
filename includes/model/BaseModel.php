<?php
abstract class BaseModel {

    /** 数据库连接对象*/
    protected $dbroute;

    function __construct() {
        global $default_oprater_dbroute;
        $this->setDbroute($default_oprater_dbroute);
    }

    public function setDbroute($dbroute) {
        $this->dbroute = $dbroute;
    }

    public function getDbroute() {
        return $this->dbroute;
    }

}
