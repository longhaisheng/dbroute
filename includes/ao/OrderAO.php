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

	public function testTransaction($user_id){//逻辑表(order_goods、order)在同一个库中,根據user_id切分
		$tx_params=array('user_id'=>$user_id);
		try{
			$this->orderModel->getDbroute()->begin($tx_params);

			$p1=$this->orderModel->insert($user_id);
			$p2=$this->orderGoodsModel->insert($user_id);
			$is_update=$this->cityModel->insert();//未分表的表

			$this->orderModel->getDbroute()->commit($tx_params);
			echo "order_id id is ".$p1." "."order_goods_id is ".$p2." is_update:".$is_update;
			return true;
		}catch (Exception $e){
			$this->orderModel->getDbroute()->rollBack($tx_params);
			echo "exception for ".$e->getMessage();
			return false;
		}
	}

}