<?php
/**
 *
 * 此类是针对 订单表(order)的分表数据库操作类，分表的逻辑列字段为 user_id,逻辑表名为order,in查询字段参数为user_ids
 * @author longhaisheng
 *
 */
class OrderModel {

	private $sequence;

	private $dbroute;

	public function __construct(){
		global $mysql_db_route_array_two;//分表的配置数组，在config.php中，此处可传不同的配置数组
		$this->sequence=new cls_sequence();
		$this->dbroute=new cls_dbroute($mysql_db_route_array_two);
	}

	public function insert($user_id=10){
		$sql="insert order (id,order_sn,user_id,add_time,modify_time) value(#id#,#order_sn#,#user_id#,now(),now()) ";
		$params['id']=$this->sequence->nextValue('order');
		$params['order_sn']='abc';
		$params['user_id']=$user_id;
		$this->dbroute->insert($sql,$params);
		return $params['id'];
	}

	public function getAll($user_id=10){
		$sql="select id,order_sn,user_id,add_time,modify_time from order where user_id=#user_id# ";
		$params['user_id']=$user_id;
		return $this->dbroute->getAll($sql,$params);
	}

	public function getRow($id){
		$sql="select id,order_sn,user_id,add_time,modify_time from order where id=#id# and user_id=#user_id# ";
		$params['id']=$id;
		$params['user_id']=10;
		return $this->dbroute->getRow($sql,$params);
	}

	public function getOne($id){
		$sql="select order_sn from order where id=#id# and user_id=#user_id# ";
		$params['id']=$id;
		$params['user_id']=10;
		return $this->dbroute->getOne($sql,$params);
	}

	public function delete($id,$user_id){
		$sql="delete from order where id=#id# and user_id=#user_id# ";
		$params['id']=$id;
		$params['user_id']=$user_id;
		return $this->dbroute->delete($sql,$params);
	}

	public function update($id,$user_id){
		$sql="update order set order_sn=#order_sn# where id=#id# and user_id=#user_id# ";
		$params['id']=$id;
		$params['order_sn']="1234a";
		$params['user_id']=$user_id;
		return $this->dbroute->update($sql,$params);
	}

	public function queryAll(){//遍历所有库表，不建议使用
		$sql="select order_sn,add_time,user_id from order where id>#id# order by id desc ";
		$params['id']=0;
		return $this->dbroute->queryResultFromAllDbTables($sql,$params);
	}

	public function queryAllByIn(){//in查询
		$params['size']=20;
		$params['id']=0;
		$params['sort_filed']='id';
		$params['sort_order']='desc';
		$params['user_ids']=array(1,1025,2,1026,2049,10);
		return $this->dbroute->selectByIn("select id,user_id,order_sn,add_time from order where id>#id# and user_id in(#user_ids#) order by id desc limit 0,20",$params);
	}

	public function transactionTest(){//事务测试
		$user_id=10;
		$tx_params=array('user_id'=>$user_id);
		$connection=$this->dbroute->getConnection($tx_params);
		$users=$connection->getAll("select user_id,user_name from user where id=#user_id#",$tx_params);
		try{
			$this->dbroute->begin($tx_params);

			$sql="insert order (id,order_sn,user_id,add_time,modify_time) value(#id#,#order_sn#,#user_id#,now(),now()) ";
			$params=array();
			$params['id']=$this->sequence->nextValue('order');
			$params['order_sn']='abc';
			$params['user_id']=$user_id;
			$this->dbroute->insert($sql,$params);
			$id=$params['id'];

			$update_sql="update order set order_sn=#order_sn# where id=#id# and #user_id# ";
			$params=array();
			$params['id']=$id;
			$params['order_sn']='bcd';
			$params['user_id']=$user_id;
			$this->dbroute->update($update_sql,$params);

			$this->dbroute->commit($tx_params);
			return true;
		}catch(Exception $e){
			echo $e->getMessage();
			$this->dbroute->rollBack($tx_params);
			return false;
		}
	}

	public function getMysqlConnection($user_id){
		$tx_params=array('user_id'=>$user_id);
		return $this->dbroute->getConnection($tx_params);
	}

}