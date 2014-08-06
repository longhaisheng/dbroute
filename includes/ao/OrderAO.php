<?php
class OrderAO{

	private $orderModel;

	private $orderGoodsModel;
	
	private $cityModel;

	public function __construct(){
		$this->orderModel=new OrderModel();//分库分表类
		$this->orderGoodsModel=new OrderGoodsModel();//分库分表类
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
				$insert_id=$this->ordergooModel->insert($user_id);
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

	public function testTransactionTwo($user_id){//逻辑表(order_goods、order)在同一个库中,根據user_id切分
		$connection=$this->orderModel->getMysqlConnection($user_id);
		try{
			$connection->begin();
				
			$p1=$this->orderModel->deractor_insert($user_id);
			$p2=$this->orderGoodsModel->deractor_insert($user_id);
			
			$connection->insert($p1['sql'],$p1['params']);
			$connection->insert($p2['sql'],$p2['params']);
			
			$connection->commit();
			$order_id=$p1['params']['id'];
			$order_goods_id=$p2['params']['id'];
			echo "order_id id is ".$order_id." "."order_goods_id is ".$order_goods_id;
			return true;
		}catch (Exception $e){
			$connection->rollBack();
			echo "exception for ".$e->getMessage();
			return false;
		}
	}



}