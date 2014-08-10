<?php
/**
 *
 * 此类是针对 用户表(user)的分表数据库操作类，分表的逻辑列字段为 user_name,用户名唯一,逻辑表名为sc_user,in查询字段参数为user_names
 * @author longhaisheng
 *
 */
class UserModel extends BaseModel { //多库多表

    private $sequence;

    private $table_total_num;

    public function __construct() {
        global $user_multiple_dbroute_config; //分表的配置数组，在config.php中，此处可传不同的配置数组
        $this->sequence = new cls_sequence();
        $this->dbroute = new cls_dbroute($user_multiple_dbroute_config);
        $this->table_total_num = $user_multiple_dbroute_config['table_total_num'];
    }

    public function getUserNameIntValue($user_name) { //根据用户名返回表名后缀下标
        return cls_dbroute::strToIntKey($user_name) % $this->table_total_num;
    }

    public function insert() {
        $sql = "insert sc_user (id,user_name,pwd,add_time,modify_time) value(#id#,#user_name#,#pwd#,now(),now()) ";
        $params = array();
        $params['id'] = $this->sequence->nextValue('sc_user');
        $params['user_name'] = 'abc' . rand(1, 1000000);
        $params['pwd'] = 'pwd' . rand(1, 10000);
        $this->dbroute->insert($sql, $params);
        return $params['user_name'];
    }

    public function getAll($user_name) {
        $sql = "select id,user_name,pwd,add_time,modify_time from sc_user  where user_name=#user_name# ";
        $params = array();
        $params['user_name'] = $user_name;
        return $this->dbroute->getAll($sql, $params);
    }

    public function getRow($user_name) {
        $sql = "select id,user_name,pwd,add_time,modify_time from sc_user where user_name=#user_name# ";
        $params = array();
        $params['user_name'] = $user_name;
        return $this->dbroute->getRow($sql, $params);
    }

    public function getOne($user_name) {
        $sql = "select pwd from sc_user  where user_name=#user_name# ";
        $params = array();
        $params['user_name'] = $user_name;
        return $this->dbroute->getOne($sql, $params);
    }

    public function delete($user_name) {
        $sql = "delete from sc_user where user_name=#user_name# ";
        $params = array();
        $params['user_name'] = $user_name;
        return $this->dbroute->delete($sql, $params);
    }

    public function update($user_name, $new_pwd) {
        $sql = "update sc_user set pwd=#new_pwd# where user_name=#user_name#  ";
        $params = array();
        $params['user_name'] = $user_name;
        $params['new_pwd'] = $new_pwd;
        return $this->dbroute->update($sql, $params);
    }

    public function queryAll($name) { //遍历所有库表，不建议使用
        $sql = "select  id,user_name,pwd,add_time,modify_time  from sc_user where user_name like #name# order by id desc ";
        $params = array();
        $params['name'] = $name . "%";
        return $this->dbroute->queryResultFromAllDbTables($sql, $params);
    }

    public function queryAllByIn() { //in查询
        $params = array();
        $params['size'] = 20;
        $params['sort_filed'] = 'id';
        $params['sort_order'] = 'desc';
        $params['user_names'] = array('abc659058', 'abc341218', 'abc291901');
        return $this->dbroute->selectByIn("select  id,user_name,pwd,add_time,modify_time  from sc_user where user_name  in(#user_names#) order by id desc limit 0,20", $params);
    }

    public function transactionTest($user_name) { //事务测试
        if (empty($user_name)) return;
        $tx_params = array('user_name' => $user_name);
        try {
            $this->dbroute->begin($tx_params);
            $user = $this->getRow($user_name);
            $user_name = null;
            if (!$user) {
                $user_name = $this->insert();
                $update_sql = "update sc_user set pwd=#new_pwd# where  user_name=#user_name# ";
                $params = array();
                $params['user_name'] = $user_name;
                $params['new_pwd'] = 'new_pwd_' . rand(1, 10000);
                $this->dbroute->update($update_sql, $params);
            }
            $this->dbroute->commit($tx_params);
            return $user_name;
        } catch (Exception $e) {
            echo $e->getMessage();
            $this->dbroute->rollBack($tx_params);
            return false;
        }
    }

    public function getMysqlConnection($user_name) {
        $tx_params = array('user_name' => $user_name);
        return $this->dbroute->getConnection($tx_params);
    }

}