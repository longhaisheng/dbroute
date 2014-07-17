<?php
class OrderModel {

	private $sequence;

	private $db;

	public function __construct(){
		global $mysql_db_route_array_two;
		$this->sequence=new cls_sequence();
		$this->db=new cls_dbroute($mysql_db_route_array_two);
	}

	public function insert(){
		$sql="insert order (id,order_sn,user_id,add_time,modify_time) value(#id#,#order_sn#,#user_id#,now(),now()) ";
		$params['id']=$this->sequence->nextValue('order');
		$params['order_sn']='abc';
		$params['user_id']=10;
		$this->db->insert($sql,$params);
		return $params['id'];
	}

	public function getAll(){
		$sql="select id,order_sn,user_id,add_time,modify_time from order where user_id=#user_id# ";
		$params['user_id']=10;
		return $this->db->getAll($sql,$params);
	}

	public function getRow($id){
		$sql="select id,order_sn,user_id,add_time,modify_time from order where id=#id# and user_id=#user_id# ";
		$params['id']=$id;
		$params['user_id']=10;
		return $this->db->getRow($sql,$params);
	}

	public function getOne($id){
		$sql="select order_sn from order where id=#id# and user_id=#user_id# ";
		$params['id']=$id;
		$params['user_id']=10;
		return $this->db->getOne($sql,$params);
	}

	public function delete($id,$user_id){
		$sql="delete from order where id=#id# and user_id=#user_id# ";
		$params['id']=$id;
		$params['user_id']=$user_id;
		return $this->db->delete($sql,$params);
	}

	public function update($id,$user_id){
		$sql="update order set order_sn=#order_sn# where id=#id# and user_id=#user_id# ";
		$params['id']=$id;
		$params['order_sn']="1234a";
		$params['user_id']=$user_id;
		return $this->db->update($sql,$params);
	}

	public function queryAll(){
		$sql="select order_sn,add_time,user_id from order where id>#id# order by id desc ";
		$params['id']=0;
		return $this->db->queryResultFromAllDbTables($sql,$params);
	}

	public function queryAllByIn(){
		$params['size']=20;
		$params['sort_filed']='id';
		$params['id']=0;
		$params['sort_order']='asc';
		$params['user_ids']=array(1,1025,2,1026,2049,10);
		return $this->db->selectByIn("select id,user_id,order_sn,add_time from order where id>#id# and user_id in(#user_ids#) order by id asc limit 0,30",$params);
	}

}