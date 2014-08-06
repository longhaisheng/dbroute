<?php
class CityModel extends BaseModel{

	public function getAllCity(){
		$sql="select id,city_name,city_code from city where type=1 order by id desc ";
		return parent::getMysql()->getAll($sql);
	}
	
	public function getCityById($id){
		$sql="select id,city_name,city_code from city where id=#id# order by id desc ";
		return parent::getMysql()->getRow($sql,array("id"=>$id));
	}
	
	public function updateCityById($id){
		$sql="update  city set city_code='hhhh' where id=#id# ";
		return parent::getMysql()->update($sql,array("id"=>$id));
	}
}