<?php
class CityModel extends BaseModel{//单表

	public function __construct(){
		parent::__construct();
	}

	public function insert(){
		$sql="insert into city(city_name,city_code,parent_id,type,gmt_created,gmt_modified) value (#city_name#,#city_code#,0,#type#,now(),now()) ";
		$params['city_name']='city_name'.rand(1, 100);
		$params['city_code']='city_code'.rand(1, 100);
		$params['type']=1;
		return $this->getDbroute()->insert($sql,$params);
	}
	public function getAllCity(){
		$sql="select id,city_name,city_code from city where type=1 order by id desc ";
		return $this->getDbroute()->getAll($sql);
	}

	public function getCityById($id){
		$sql="select id,city_name,city_code from city where id=#id# order by id desc ";
		return $this->getDbroute()->getRow($sql,array("id"=>$id));
	}

	public function updateCityById($id){
		print_r($this->getDbroute());
		$sql="update  city set city_code='haahhh' where id=#id# ";
		return $this->getDbroute()->update($sql,array("id"=>$id));
	}
}