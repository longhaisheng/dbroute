<?php
/**
 * mysqli 操作类
 * @author longhaisheng(longhaisheng20@163.com,QQ:87188524)
 */
class cls_sqlexecute implements cls_idb {

    /** 主库链接 */
    private $connection;

    /** 读库链接  */
    private $read_connection;

    /** db链接字符串数组 */
    private $connect_array = array();

    /** 是否有读库 */
    private $has_read_db;

    /** 此次操作是否有事务 */
    private $this_operation_have_transaction = false;

    /** 存储数据库连接单例类数组,key为DB名称 */
    private static $single_instance_list = array();
    
    /** 一次事务中所包含的数据库名 */
    private static $db_name_list_in_one_transaction = array();
    
    /** 是否需要标记  一次事务中所包含的数据库名 默认为false,不标记,只有事务中代码才需要标记*/
    private static $need_record_db_name_in_one_transaction=false;
    
    /**
     * @param string $db_name  数据库名
     * @param ay $db_route_config 分库分表配置数组
     */
    private function __construct($db_name = '', $db_route_config = array()) {
        if (empty($this->connect_array)) {
            global $default_config_array;
            if ($db_route_config) {
                $this->connect_array = $db_route_config;
            } else {
                $this->connect_array = $default_config_array;
            }
            if ($db_name) {
                $this->connect_array['db'] = $db_name;
            }
        }
        if (isset($this->connect_array['read_db_hosts'])) {
            $this->has_read_db = true;
        }
    }

    public static function getInstance($db_name = '', $db_route_config = array()) {
        global $default_config_array;
        if (empty($db_name)) {
            $db_name = $default_config_array['db'];
        }
        if (isset(self::$single_instance_list[$db_name])) {
            return self::$single_instance_list[$db_name];
        } else {
            self::$single_instance_list[$db_name] = new self($db_name, $db_route_config);
            return self::$single_instance_list[$db_name];
        }
    }

    private function init() {
        if ($this->connection === null) {
            $connect_array = $this->connect_array;
            $db_host_array = isset($this->connect_array['db_hosts']) ? $this->connect_array['db_hosts'] : array();
            if ($db_host_array) {
                $db = $connect_array['db'];
                $host = $db_host_array[$db];
                if(stripos($host,',')){//双master
                    $host = cls_rollrand::get_write_db_host_rand($host);
                }
            } else {
                $host = $connect_array['host'];
            }
            //host 前加p:可以设置成持久化连接，不见意使用持久化
            $this->connection = new mysqli($host, $connect_array['user_name'], $connect_array['pass_word'], $connect_array['db'], $connect_array['port']);
            if ($this->connection->error) {
                echo('Database Connect Error : ' . $this->connection->error);
            } else {
                $this->connection->query("SET NAMES 'utf8'");
            }
        }
    }

    private function init_read_connection() {
        if ($this->has_read_db && $this->read_connection === null) {
            $connect_array = $this->connect_array;

            $db_read_host_array = isset($this->connect_array['read_db_hosts']) ? $this->connect_array['read_db_hosts'] : array();
            $db = $connect_array['db'];
            if ($db_read_host_array) {
                if(isset($connect_array['read_db_arithmetic']) && $connect_array['read_db_arithmetic']=='roll'){//轮询算法
                	$host = cls_rollrand::get_db_host_roll($db_read_host_array, $db);
                }else{
                	$host = cls_rollrand::get_db_host_rand($db_read_host_array, $db);//随机
                }
                if(empty($host)){//如果从库的host不存在，去主库中查找对应的host
                    $db_host_array = isset($this->connect_array['db_hosts']) ? $this->connect_array['db_hosts'] : array();
                    if ($db_host_array) {
                        $host = $db_host_array[$db];
                        if(stripos($host,',')){//双master
                            $host = cls_rollrand::get_write_db_host_rand($host);
                        }
                    } else {
                        $host = $connect_array['host'];
                    }
                }
            }
            $this->read_connection = new mysqli($host, $connect_array['user_name'], $connect_array['pass_word'], $connect_array['db'], $connect_array['port']);
            if ($this->read_connection->error) {
                echo('Database Connect Error : ' . $this->read_connection->error);
            } else {
                $this->read_connection->query("SET NAMES 'utf8'");
            }
        }
    }

    /** 获取一次事务中所包含的所有数据库名*/
    public static function get_database_name_list_in_one_transaction(){
        return array_unique(self::$db_name_list_in_one_transaction);
    }

    private function getConnection() {
        return $this->connection;
    }

    /**
     * @param $sql "insert user (name,pwd) value (#name#,#pwd#) "
     * @param array $params array('name'=>'long','pwd'=>'123456')
     * @param bool $return_insert_id
     * @return int
     */
    public function insert($sql, $params = array(), $return_insert_id = true) {
        $stmt = $this->executeQuery($sql, $params);
        if ($stmt && $return_insert_id) {
            $insert_id = $stmt->insert_id;
            $stmt->close();
            return $insert_id;
	        /*if($return_insert_id){
	            $seq_sql="select LAST_INSERT_ID() as id ";
	            $id=$this->getColumn($seq_sql);
	            return $id;
	        }*/
        }
        if ($stmt != null) {
            $stmt->close();
        }
    }

    /**
     * @param $sql "update user set name=#name#,pwd=#pwd# where id=#id#"
     * @param array $params array('name'=>'longhaisheng','pwd'=>'pwd123456','id'=>1)
     * @param bool $return_affected_rows
     * @return int
     */
    public function update($sql, $params = array(), $return_affected_rows = true) {
        $stmt = $this->executeQuery($sql, $params);
        if ($stmt && $return_affected_rows) {
            $affected_rows = $stmt->affected_rows;
            $stmt->close();
            return $affected_rows;
        }
        if ($stmt != null) {
            $stmt->close();
        }
    }

    /**
     * @param $sql "delete from user where id=#id#"
     * @param array $params array('id'=>123)
     * @param bool $return_affected_rows
     * @return int
     */
    public function delete($sql, $params = array(), $return_affected_rows = true) {
        return $this->update($sql, $params, $return_affected_rows);
    }

    /**
     * @param $sql "insert user(name,pwd) values (#user_name#,#pwd#)"
     * @param array $batch_params (array(array('user_name'=>'username1','pwd'=>'password1'),array('user_name'=>'username2','pwd'=>'password2')......))
     * @param int $batch_num 不见意超过50,默认为20
     * @return 总共受影响行数
     */
    public function batchExecutes($sql, $batch_params = array(), $batch_num = 20) {
        $affected_rows = 0;
        if ($batch_params && is_array($batch_params)) {
            $this->init();
            $new_batch_params = array();
            $new_sql = '';
            foreach ($batch_params as $ps) {
                $result = $this->replaceSql($sql, $ps);
                $new_batch_params[] = $result['params'];
                if (empty($new_sql)) {
                    $new_sql = $result['sql'];
                }
            }


            $stmt = $this->connection->prepare($new_sql);
            $count = count($batch_params);
            $i = 0;
            foreach ($batch_params as $param) {
                $i++;
                if ($i % $batch_num == 0 || $i = $count) {
                    $this->begin();
                }
                $params = $this->get_bind_params($param);
                $this->bindParameters($stmt, $params);
                $stmt->execute();
                if ($i % $batch_num == 0 || $i = $count) {
                    $this->commit();
                    $affected_rows = $affected_rows + $stmt->affected_rows;
                }
            }
            if ($stmt != null) {
                $stmt->close();
            }
            if ($this->connection != null) {
                $this->connection->autocommit(true);
            }
            return $affected_rows;
        }
    }

    /**
     * @param $sql "select id,name,pwd from user where id >#id#"
     * @param array $bind_params array('id'=>10)
     * @return array
     */
    public function getAll($sql, $params = array()) {
        $stmt = $this->executeQuery($sql, $params);
        $fields_list = $this->fetchFields($stmt);

        foreach ($fields_list as $field) {
            $bind_result[] = & ${$field}; //http://www.php.net/manual/zh/language.variables.variable.php
        }
        $this->bindResult($stmt, $bind_result);
        $result_list = array();
        $i = 0;
        while ($stmt->fetch()) { //http://cn2.php.net/manual/zh/mysqli-stmt.bind-result.php
            foreach ($fields_list as $field) {
                $result_list[$i][$field] = ${$field};
            }
            $i++;
        }
        if ($stmt != null) {
            $stmt->close();
        }
        return $result_list;
    }

    /**
     * @param $sql "select id,name,pwd from user where id=#id# "
     * @param array $bind_params array('id'=>10)
     * @return array
     */
    public function getRow($sql, $params = array()) {
        $list = $this->getAll($sql, $params);
        if ($list) {
            return $list[0];
        }
        return array();
    }

    /**
     * @param $sql "select count(1) as count_num from user where id >#id# "
     * @param array $bind_params array('id'=>100)
     * @return int
     * @see getColumn
     */
    public function getOne($sql, $params = array()) {
        return $this->getColumn($sql, $params);
    }

    /**
     * @param $sql "select count(1) as count_num from user where id >#id# "
     * @param array $bind_params array('id'=>100)
     * @return int
     */
    public function getColumn($sql, $params = array()) {
        $row = $this->getRow($sql, $params);
        if ($row) {
            sort($row);
            return $row[0];
        }
        return 0;
    }

    private function executeQuery($sql, $params = array()) {
        $result = $this->replaceSql($sql, $params);
        $transaction_read_master = false; //事务中的读操作是否读主库
        if (defined('TRANSACTION_READ_MASTER')) {
            $transaction_read_master = TRANSACTION_READ_MASTER;
        }
        $read_conn = false;
        if ($this->this_operation_have_transaction && $transaction_read_master) { //有事务操作并且事务中select配置成操作主库,事务中select查询走主库
            $this->init();
            $stmt = $this->connection->prepare($result['sql']);
        } else {
            if ($this->has_read_db && (stristr($sql, 'select ') || stristr($sql, 'SELECT '))) { //有读库配置并且是 select 查询 走读库
                $this->init_read_connection();
                $stmt = $this->read_connection->prepare($result['sql']);
                $read_conn = true;
            } else {
                $this->init();
                $stmt = $this->connection->prepare($result['sql']);
            }
        }
        if(self::$need_record_db_name_in_one_transaction){
			self::$db_name_list_in_one_transaction[]=$this->connect_array['db'];
        }
        if (!$stmt) {
            throw new Exception('error sql in ' . $sql);
        }

        $params = $this->get_bind_params($result['params']);
        $this->bindParameters($stmt, $params);

        if ($stmt->execute()) {
            return $stmt;
        } else {
            if ($stmt != null) {
                $stmt->close();
            }
            $error_msg = $read_conn ? $this->read_connection->error : $this->connection->error;
            throw new Exception('Error in : ' . $error_msg);
        }
    }

    private function get_bind_params($bind_params) {
        if ($bind_params && is_array($bind_params)) {
            ksort($bind_params);
            $param_key = '';
            foreach ($bind_params as $key => $value) {
                $type = gettype($value);
                if ($type === 'integer') {
                    $param_key .= 'i';
                } else if ($type === 'double') {
                    $param_key .= 'd';
                } else if ($type === 'string') {
                    $param_key .= 's';
                } else {
                    $param_key .= 'b';
                }
            }
            array_unshift($bind_params, $param_key); //在数组最前面插入一条数据
            return $bind_params;
        }
        return array();
    }

    private function bindParameters($stmt, $bind_params = array()) {
        if ($bind_params) {
            call_user_func_array(array($stmt, 'bind_param'), $this->refValues($bind_params));
        }
    }

    private function bindResult($stmt, $bind_result_fields = array()) {
        call_user_func_array(array($stmt, 'bind_result'), $bind_result_fields);
    }

    private function refValues($arr) {
        if (strnatcmp(phpversion(), '5.3') >= 0) { //Reference is required for PHP 5.3+
            $refs = array();
            foreach ($arr as $key => $value) {
                $refs[$key] = & $arr[$key];
            }
            return $refs;
        }
        return $arr;
    }

    private function fetchFields($stmt) {
        $metadata = $stmt->result_metadata();
        $field_list = array();
        while ($field = $metadata->fetch_field()) {
            $field_list[] = strtolower($field->name);
        }
        return $field_list;
    }


    private function replaceSql($sql, $object = array()) {
        $matchSql = $this->iteratePropertyReplaceByArray($sql, $object);
        $sql = $matchSql['sql'];
        $map = $matchSql['match_property'];
        $params = array();
        if ($object) {
            foreach ($object as $key => $value) {
                if (!stripos($sql, ':' . $key)) {
                    throw new Exception(' array key:'. $key.' not in sql:' . $sql);
                } else {
                    $sql = str_ireplace(':' . $key, '?', $sql);
                    foreach ($map as $k => $v) {
                        if (strtolower($v) === strtolower("#$key#")) {
                            $params[$k] = $value;
                            break;
                        }
                    }
                }
            }
        }
        $return_array = array('sql' => $sql, 'params' => $params);
        return $return_array;
    }

    private function iteratePropertyReplaceByArray($sql, $array) {
        preg_match_all('/(#)(.*?)(#)/', $sql, $match);
        if ($match) {
            $match = $match[0];
        }
        $matchSql = array();
        $matchSql['match_property'] = $match;
        if ($array) {
            foreach ($array as $key => $value) {
                if (stristr($sql, $key)) {
                    $sql = str_ireplace("#$key#", ":$key", $sql);
                }
            }
        }
        $matchSql['sql'] = $sql;
        return $matchSql;
    }

    public function begin() {
        $this->init();
        self::$need_record_db_name_in_one_transaction=true;
        if(self::$need_record_db_name_in_one_transaction){
        	self::$db_name_list_in_one_transaction[]=$this->connect_array['db'];
        }
        $this->this_operation_have_transaction = true;
        $this->connection->autocommit(false); //关闭本次数据库连接的自动命令提交事务模式
    }

    public function commit() {
        if(self::$need_record_db_name_in_one_transaction){
        	self::$db_name_list_in_one_transaction[]=$this->connect_array['db'];
        }
    	if(count(self::get_database_name_list_in_one_transaction())>1){//事务中超过一个数据库,抛出异常,让客户端回滚
    		throw new Exception(" transactions have more than one database,plese check you code ");
    	}
        $this->connection->commit(); //提交事务后，打开本次数据库连接的自动命令提交事务模式
        if(self::$need_record_db_name_in_one_transaction){
        	self::$db_name_list_in_one_transaction=array();
        	self::$need_record_db_name_in_one_transaction=false;
        }
        $this->this_operation_have_transaction = false;
        $this->connection->autocommit(true);
    }

    public function rollBack() {
        $this->connection->rollback(); //回滚事务后，打开本次数据库连接的自动命令提交事务模式
        if(self::$need_record_db_name_in_one_transaction){
        	self::$db_name_list_in_one_transaction=array();
        	self::$need_record_db_name_in_one_transaction=false;
        }
        $this->this_operation_have_transaction = false;
        $this->connection->autocommit(true);
    }

    public function closeConnection() {
        if ($this->connection != null) {
            $this->connection->close();
            $this->connection = null;
        }
        if ($this->read_connection != null) {
            $this->read_connection->close();
            $this->read_connection = null;
        }
    }

    public function __destruct() {
        $this->closeConnection();
    }
}

?>

