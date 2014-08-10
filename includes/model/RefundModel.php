<?php
/**
 *
 * 此类是针对 退款表(refund)的分表数据库操作类，分表的逻辑列字段为 user_id,逻辑表名为sc_refund,in查询字段参数为user_ids
 * @author longhaisheng
 *
 */
class RefundModel extends BaseModel{//多库多表

	private $sequence;

	public function __construct(){
		global $sc_refund_multiple_dbroute_config;//分表的配置数组，在config.php中，此处可传不同的配置数组
		$this->sequence=new cls_sequence();
		$this->dbroute=new cls_dbroute($sc_refund_multiple_dbroute_config);
	}

	public function insert($user_id=10){
		$sql="insert sc_refund (id,refund_sn,reason,user_id,add_time,modify_time) value(#id#,#refund_sn#,#reason#,#user_id#,now(),now()) ";
		$params['id']=$this->sequence->nextValue('order');
		$params['refund_sn']='SN00000'.rand(1,1000);
		$params['reason']='abc'.rand(1,1000);
		$params['user_id']=$user_id;
		$this->dbroute->insert($sql,$params);
		return $params['id'];
	}

}