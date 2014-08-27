<?php
interface cls_idb {

    /**
     * @param $sql "insert order (order_id,order_sn,user_id) value (#order_id#,#order_sn#,#user_id#) "
     * @param array $params array('order_id'=>10,'order_sn'=>'123456','user_id'=>10)
     * @param bool $return_insert_id 是否返回插入主键值 
     * @return int 插入主键id
     */
    public function insert($sql, $params = array(), $return_insert_id = true);

    /**
     * @param $sql "update order set order_num=#order_num#,order_sn=#order_sn# where id=#id# and user_id=#user_id#"
     * @param array $params array('order_num'=>3,'order_sn'=>'sn123456','id'=>1,'user_id'=>10)
     * @param bool $return_affected_rows 
     * @return int 受影响行数
     */
    public function update($sql, $params = array(), $return_affected_rows = true);

    /**
     * @param $sql "delete from order where id=#id# and user_id=#user_id#"
     * @param array $params array('id'=>123,'user_id'=>10)
     * @param bool $return_affected_rows
     * @return int 受影响行数
     */
    public function delete($sql, $params = array(), $return_affected_rows = true);

    /**
     * @param $sql "insert order(order_id,order_sn,user_id) values (#order_id#,#order_sn#,#user_id#)"
     * @param array $batch_params (array(array('order_id'=>1,'order_sn'=>'password1','user_id'=>10),array('order_id'=>2,'order_sn'=>'password2','user_id'=>10)......))
     * @param array $logic_params 分表物理列名数组，如根据user_id分表的，此可传 array('user_id'=>10)
     * @param int $batch_num 不见意超过50,默认为20
     * @return int 总共受影响行数
     */
    public function batchExecutes($sql, $batch_params = array(), $batch_num = 20);

    /**
     * @param $sql "select order_id,order_sn from order where user_id=#user_id#"
     * @param array $params array('user_id'=>10)
     * @return array
     */
    public function getAll($sql, $params = array());

    /**
     * @param $sql "select  order_id,order_sn from order where user_id=#user_id# "
     * @param array $params array('user_id'=>10)
     * @return array
     */
    public function getRow($sql, $params = array());

    /**
     * @param $sql "select count(1) as count_num from order where user_id=#user_id# "
     * @param array $params array('user_id'=>100)
     * @return mixed 某列的值
     * @see getColumn
     */
    public function getOne($sql, $params = array());

    /**
     * @param $sql "select count(1) as count_num from order where user_id=#user_id# "
     * @param array $params array('user_id'=>100)
     * @return mixed 某列的值
     */
    public function getColumn($sql, $params = array());

    public function begin();

    public function commit();

    public function rollBack();

    public function closeConnection();
}