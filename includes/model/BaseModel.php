<?php
abstract class BaseModel {

	/** 数据库连接对象*/
	private $mysql;

	function __construct($mysqli=null) {
		if($mysqli){
			$this->mysql=$mysqli;
		}else{
			global $db_mysqli;
			$this->mysql=$db_mysqli;
		}
	}

	public function setMysql($db_link){
		$this->mysql=$db_link;
	}

	public function getMysql(){
		return $this->mysql;
	}

}
