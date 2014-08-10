<?php
class OrderGoodsModel extends BaseModel { //单库多表

    private $sequence;

    public function __construct() {
        global $order_goods_dbroute_single_config; //分表的配置数组，在config.php中，此处可传不同的配置数组
        $this->sequence = new cls_sequence();
        $this->dbroute = new cls_dbroute($order_goods_dbroute_single_config);
    }

    public function insert($user_id = 10) {
        $sql = "insert sc_order_goods (id,order_id,goods_id,user_id,add_time,modify_time) value(#id#,#order_id#,#goods_id#,#user_id#,now(),now()) ";
        $params['id'] = $this->sequence->nextValue('order_goods');
        $params['order_id'] = 10;
        $params['goods_id'] = 11;
        $params['user_id'] = $user_id;
        $this->dbroute->insert($sql, $params);
        return $params['id'];
    }

}