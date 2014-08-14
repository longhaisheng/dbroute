<?php
/**
 *
 * 此类是针对 退款单详情表(sc_refund_info)的分表数据库操作类，分表的逻辑列字段为 user_id,逻辑表名为sc_refund_info,in查询字段参数为user_ids
 * @author longhaisheng
 *
 */
class RefundInfoModel extends BaseModel { //多库多表

    private $sequence;

    public function __construct() {
        global $sc_refund_info_multiple_dbroute_config; //分表的配置数组，在config.php中，此处可传不同的配置数组
        $this->sequence = new cls_sequence();
        $this->dbroute = new cls_dbroute($sc_refund_info_multiple_dbroute_config);
    }

    public function insert($user_id = 10, $refund_id) {
        $sql = "insert sc_refund_info (id,refund_id,goods_id,goods_num,user_id,add_time,modify_time) value(#id#,#refund_id#,#goods_id#,#goods_num#,#user_id#,now(),now()) ";
        $params['id'] = $this->sequence->nextValue('order');
        $params['refund_id'] = $refund_id;
        $params['goods_id'] = rand(1, 1000);
        $params['goods_num'] = rand(1, 10);
        $params['user_id'] = $user_id;
        $this->dbroute->insert($sql, $params);
        return $params['id'];
    }

    public function getAll($user_id = 10) {
        $sql = "select id,refund_id,goods_id,goods_num,user_id,add_time,modify_time from sc_refund_info where user_id=#user_id# ";
        $params['user_id'] = $user_id;
        return $this->dbroute->getAll($sql, $params);
    }

    public function selectByIn() {
        $sql = "select id,refund_id,goods_id,goods_num,user_id,add_time,modify_time from sc_refund_info where user_id in (#user_ids#)order by id desc  ";
        $params['user_ids']=array(10,74,1034,139);
        return$this->dbroute->selectByIn($sql,$params);
    }

    public function queryAllFromTable() {
        $sql = "select id,refund_id,goods_id,goods_num,user_id,add_time,modify_time from sc_refund_info order by id desc  ";
        return$this->dbroute->queryResultFromAllDbTables($sql);
    }

}