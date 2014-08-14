<?php
class cls_pdosqlexecute implements cls_idb {

    private $connection;

    private $read_connection;

    private $connect_array;

    private $has_read_db = false;

    /** 此次操作是否有事务 */
    private $this_operation_have_transaction = false;

    /** 存储数据库连接单例类数组,key为DB名称 */
    private static $single_instance_list = array();

    /** 一次事务中所包含的数据库名 */
    private static $db_name_list = array();

    /** 是否需要标记  一次事务中所包含的数据库名 默认为false,不标记,只有事务中代码才需要标记*/
    private static $need_record_transaction_database_name=false;

    /**
     * @param string $db_name  数据库名
     * @param ay $db_route_config 分库分表配置
     */
    private function __construct($db_name = '', $db_route_config = array()) {
        if (!$this->connect_array) {
            global $default_config_array;
            $this->connect_array = $db_route_config ? $db_route_config : $default_config_array;
            if ($db_name) {
                $this->connect_array['db'] = $db_name;
            }
        }
        if (!$this->has_read_db) {
            $this->has_read_db = isset($this->connect_array['read_db_hosts']);
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

    /** 获取一次事务中所包含的所有数据库名*/
    public static function get_database_name_list_in_one_transaction(){
        return array_unique(self::$db_name_list);
    }

    public function getAll($sql, $params = array()) {
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetchAll();
        $stmt->closeCursor();
        return $result;
    }

    private function query($sql, $params = array()) {
        $condition = $this->format($sql, $params);
        $stmt = $this->prepare($condition['sql']);
        try {
            $stmt->execute($condition['params']);
            return $stmt;
        } catch (PDOException $e) {
            if ($stmt) {
                $stmt->closeCursor();
            }
            throw new Exception("Error in : " . $e->getMessage());
            return null;
        }
    }

    private function format($sql, $params = array()) {
        $sql = $this->formatSql($sql);
        $params = $this->formatParams($params);
        $condition = array('sql' => $sql, 'params' => $params);

        return $condition;
    }

    private function formatSql($sql) {
        return preg_replace('/#(\w+)#/', ':$1', $sql);
    }

    private function formatParams($params = array()) {
        $result = array();
        foreach ($params as $k => $v) {
            $result[':' . $k] = $v;
        }
        $params = $result;
        return $params;
    }

    private function prepare($sql) {
        $transaction_read_master = false; //事务中的读操作是否读主库
        if (defined("TRANSACTION_READ_MASTER")) {
            $transaction_read_master = TRANSACTION_READ_MASTER;
        }
        if ($this->this_operation_have_transaction && $transaction_read_master) { //有事务操作并且事务中select配置成操作主库,事务中select查询走主库
            $db = $this->getMasterConnection();
        } else {
            if ($this->has_read_db && preg_match('/^select\s/i', $sql)) { // 判断SQL语句是否为读
                $db = $this->getReadConnection();
            } else {
                $db = $this->getMasterConnection();
            }
        }
        $stmt = $db->prepare($sql);
        if(self::$need_record_transaction_database_name){
            self::$db_name_list[]=$this->connect_array['db'];
        }
        return $stmt;
    }

    private function getReadConnection() {
        if (!$this->read_connection) {
            $connect_array = $this->connect_array; // 载入读库配置
            $db_name = $this->connect_array['db'];
            $host_str = $this->connect_array['read_db_hosts'][$db_name];
            $host_array = explode(",", $host_str);
            $num = rand(0, count($host_array) - 1);
            $host = $host_array[$num];
            $connect_array['host'] = $host;
            return $this->read_connection = $this->getDbConnection($connect_array);
        }

        return $this->read_connection;
    }

    private function getDbConnection($connect_array) {
        $dsn_array = array(
            'dbname=' . $connect_array['db'],
            'host=' . $connect_array['host'],
            'port=' . $connect_array['port'],
        );
        $dsn = 'mysql:' . implode(';', $dsn_array);
        try {
            $connection = new PDO($dsn, $connect_array['user_name'], $connect_array['pass_word'], array(
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'",
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_CASE => PDO::CASE_LOWER,
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                )
            );
            return $connection;
        } catch (PDOException $e) {
            throw new Exception("Database Connect Error : " . $e->getMessage());
        }
    }

    private function getMasterConnection() {
        if (!$this->connection) {
            return $this->connection = $this->getDbConnection($this->connect_array);
        }
        return $this->connection;
    }

    public function begin() {
        $this->getMasterConnection();
        self::$need_record_transaction_database_name=true;
        if(self::$need_record_transaction_database_name){
            self::$db_name_list[]=$this->connect_array['db'];
        }
        $this->connection->setAttribute(PDO::ATTR_AUTOCOMMIT, false);
        $this->this_operation_have_transaction = true;
        return $this->connection->beginTransaction();
    }

    public function commit() {
        if(count(self::get_database_name_list_in_one_transaction())>1){//事务中超过一个数据库,抛出异常,让客户端回滚
            throw new Exception(" transactions have more than one database,plese check you code ");
        }
        $return = $this->connection->commit();
        if(self::$need_record_transaction_database_name){
            self::$db_name_list[]=array();
            self::$need_record_transaction_database_name=false;
        }
        $this->connection->setAttribute(PDO::ATTR_AUTOCOMMIT, true);
        $this->this_operation_have_transaction = false;
        return $return;
    }

    public function rollBack() {
        $return = $this->connection->rollBack();
        if(self::$need_record_transaction_database_name){
            self::$db_name_list[]=array();
            self::$need_record_transaction_database_name=false;
        }
        $this->connection->setAttribute(PDO::ATTR_AUTOCOMMIT, true);
        $this->this_operation_have_transaction = false;
        return $return;
    }

    /**
     * 插入数据
     * @param $sql
     * @param array $params
     * @param bool $return_insert_id
     * @return int
     */
    public function insert($sql, $params = array(), $return_insert_id = true) {
        $stmt = $this->query($sql, $params);
        $insertId = $return_insert_id ? $stmt->lastInsertId : 0;
        $stmt->closeCursor();

        return $insertId;
    }

    /**
     * @param $sql "update order set order_num=#order_num#,order_sn=#order_sn# where id=#id# and user_id=#user_id#"
     * @param array $params array('order_num'=>3,'order_sn'=>'sn123456','id'=>1,'user_id'=>10)
     * @param bool $return_affected_rows
     * @return int
     */
    public function update($sql, $params = array(), $return_affected_rows = true) {
        $stmt = $this->query($sql, $params);
        $rowCount = $return_affected_rows ? $stmt->rowCount() : 0;
        $stmt->closeCursor();

        return $rowCount;
    }

    /**
     * @param $sql "delete from order where id=#id# and user_id=#user_id#"
     * @param array $params array('id'=>123,'user_id'=>10)
     * @param bool $return_affected_rows
     * @return int
     */
    public function delete($sql, $params = array(), $return_affected_rows = true) {
        return $this->update($sql, $params, $return_affected_rows);
    }

    /**
     * @param $sql "insert order(order_id,order_sn,user_id) values (#order_id#,#order_sn#,#user_id#)"
     * @param array $batch_params (array(array('order_id'=>1,'order_sn'=>'password1','user_id'=>10),array('order_id'=>2,'order_sn'=>'password2','user_id'=>10)......))
     * @param int $batch_num 不见意超过50,默认为20
     * @internal param array $logic_params 分表物理列名数组，如根据user_id分表的，此可传 array('user_id'=>10)
     * @return int
     */
    public function batchExecutes($sql, $batch_params = array(), $batch_num = 20) {
        if (empty($batch_params)) return;
        $affectedRows = 0;
        $sql = $this->formatSql($sql);
        array_walk($batch_params, array($this, 'formatParams'));
        $stmt = $this->prepare($sql);

        $paramsGroups = array_chunk($batch_params, $batch_num);
        foreach ($paramsGroups as $group) {
            $this->begin();
            foreach ($group as $params) {
                $stmt->execute($params);
                $affectedRows += $stmt->rowCount();
            }
            $this->commit();
            $stmt->closeCursor();
        }

        return $affectedRows;
    }

    /**
     * @param $sql "select  order_id,order_sn from order where user_id=#user_id# "
     * @param array $params
     * @internal param array $bind_params array('user_id'=>10)
     * @return array
     */
    public function getRow($sql, $params = array()) {
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch();
        $stmt->closeCursor();

        return $result;
    }

    /**
     * @param $sql "select count(1) as count_num from order where user_id=#user_id# "
     * @param array $params
     * @internal param array $bind_params array('user_id'=>100)
     * @return int
     * @see getColumn
     */
    public function getOne($sql, $params = array()) {
        return $this->getColumn($sql, $params);
    }

    /**
     * @param $sql "select count(1) as count_num from order where user_id=#user_id# "
     * @param array $params
     * @internal param array $bind_params array('user_id'=>100)
     * @return int
     */
    public function getColumn($sql, $params = array()) {
        $stmt = $this->query($sql, $params);
        $column = $stmt->fetchColumn();
        $stmt->closeCursor();

        return $column;
    }

    public function __destruct() {
        $this->closeConnection();
    }

    public function closeConnection() {
        $this->connection = null;
        $this->read_connection = null;
    }
}