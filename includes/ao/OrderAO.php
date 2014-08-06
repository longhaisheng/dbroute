<?php
class OrderAO{

	private $orderModel;

	private $cityModel;

	public function __construct(){
		$this->orderModel=new OrderModel();//分库分表类
		$this->cityModel=new CityModel();//未分库分表类
	}

	public function testTransaction($user_id){//city表同逻辑表order在同一个库中
		$city_id=2;
		$connection=$this->orderModel->getMysqlConnection($user_id);
		$this->cityModel->setMysql($connection);
		try{
			$connection->begin();
			$city=$this->cityModel->getCityById($city_id);//事务里的代码读操作可配置 优先读主库,详见config
			if($city){
				$is_update=$this->cityModel->updateCityById($city_id);
				$insert_id=$this->orderModel->insert($user_id);
			}
			
			$connection->commit();
			echo "insert id is ".$insert_id." "."update count is ".$is_update;
			return true;
		}catch (Exception $e){
			$connection->rollBack();
			echo "exception for ".$e->getMessage();
			return false;
		}
	}



}