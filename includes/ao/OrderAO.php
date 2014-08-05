<?php
class OrderAO{

	private $orderModel;

	private $cityModel;

	function __construct(){
		$this->orderModel=new OrderModel();//分库分表类
		$this->cityModel=new CityModel();//未分库分表类
	}

	function testTransaction($user_id){//city表同逻辑表order在同一个库中
		$db_link=$this->orderModel->getMysqlConnection($user_id);
		$this->cityModel->setMysql($db_link);
		try{
			$this->cityModel->getMysql()->begin();
			$city_id=2;
			$city=$this->cityModel->getCityById($city_id);//事务里的代码读操作可配置 优先读主库,详见config
			if($city){
				$is_update=$this->cityModel->updateCityById($city_id);
				$id=$this->orderModel->insert($user_id);
			}
			
			$this->cityModel->getMysql()->commit();
			echo "insert id is ".$id." "."update count is ".$is_update;
			return true;
		}catch (Exception $e){
			$this->cityModel->getMysql()->rollBack();
			echo "exception for ".$e->getMessage();
			return false;
		}

	}



}