<?php
class cls_dbroute {

    /** 数据库前缀名 */
    private $db_prefix;

    /** 表前缀名 */
    private $table_prefix;

    /** 数据库总数 */
    private $db_total_num;

    /** 表总数 */
    private $table_total_num;

    /** 每个数据库里的表数 */
    private $one_db_table_num;

    /** 每个db存在于哪些表,key为库,value为库中所有表(数组) */
    private $db_tables = array();

    /** 每个表存在于哪些库中,key为表,value为库(字符串) */
    private $table_dbs = array();

    /** 逻辑表名 */
    private $logic_table;

    /** 分表的逻辑列名 */
    private $logic_column;

    /** select in 查询时时的参数key名 */
    private $select_in_logic_column;

    /** 是否单库多表 true为单库 */
    private $is_single_db;

    /** 数据库连接 cls_mysqli 类的数组*/
    private $connections = array();

    /** 分库分表配置项*/
    private $config_array;

    /** 使用默认配置项*/
    private $use_default_config = true;

    /** 默认使用mysqli扩展*/
    private $use_mysqli_extend = true;

    /** 是否为调试模式*/
    private $is_debug = false;

    /** 逻辑字段值类型*/
    private $logic_column_field_type;

    private function setLogicColumnFieldType($logic_column_field_type) {
        $this->logic_column_field_type = $logic_column_field_type;
    }

    private function getLogicColumnFieldType() {
        return $this->logic_column_field_type;
    }

    public function __construct($db_route_array = array()) {
        global $default_config_array;
        if ($db_route_array) {
            $this->config_array = $db_route_array;
            $this->use_default_config = false;
        } else {
            $this->config_array = $default_config_array;
            $this->use_default_config = true;
        }
        if (isset($this->config_array['logic_table']) && isset($this->config_array['logic_column'])) {
            $this->db_prefix = $this->config_array['db_prefix'];
            $this->table_prefix = $this->config_array['table_prefix'];
            $this->logic_table = $this->config_array['logic_table'];
            $this->logic_column = $this->config_array['logic_column'];
            $this->table_total_num = $this->config_array['table_total_num'];
            $this->one_db_table_num = $this->config_array['one_db_table_num'];
            $this->select_in_logic_column = $this->config_array['select_in_logic_column'];
            $this->logic_column_field_type = $this->config_array['logic_column_field_type'];
            if (defined("IS_DEBUG")) {
                $this->is_debug = IS_DEBUG;
            }
            if ($this->getOneDbTableNum() == $this->getTableTotalNum()) {
                $this->is_single_db = true; //单库
            } else {
                $this->is_single_db = false; //多库
            }
            $list = cls_shmop::readArray("init_logic_" . $this->logic_table);
            if ($list) {
                $this->db_tables = $list;
            } else {
                $this->init();
            }
        }
    }

    private function init() {
        $db_total_num = $this->getDbTotalNum();
        if ($this->is_debug) {
            $list = array();
        }
        $mod = $this->getTableTotalNum() % $this->getOneDbTableNum();
        $num = 0;
        for ($i = 0; $i < $db_total_num; $i++) {
            $num++;
            $crea = ($i * $this->one_db_table_num);
            $crea_two = ($i + 1) * $this->one_db_table_num;
            $tables = array();
            for ($j = $crea; $j < $crea_two; $j++) {
                $tables[] = $this->getTableName($j);
                if ($this->isSingleDb()) {
                    $this->db_tables[0] = $i;
                } else {
                    $this->db_tables[$j] = $i; //key为表的数字下缀，value为数据库数字下缀（下缀大于等于零）
                }
            }
            if ($mod && $num == $db_total_num) {
                $tables = array_slice($tables, 0, $mod);
            }
            if ($this->isSingleDb()) { //单库
                $prefix = explode("_", $this->getDbPrefix());
                $db_key = $prefix[0];
            } else {
                $db_key = substr_replace($this->getDbPrefix(), $i, strlen($this->getDbPrefix()) - strlen($i));
            }
            $list[$db_key] = $tables;
        }

        cls_shmop::writeArray("init_logic_" . $this->logic_table, $this->db_tables);
        if ($this->is_debug) {
            foreach ($list as $key => $val) {
                foreach ($val as $tab) {
                    $this->table_dbs[$tab] = $key;
                }
            }
            print_r($list);
            print_r($this->table_dbs);
        }
    }

    private function getDbName($mod) {
        if ($this->isSingleDb()) { //单库
            $mod = 0;
            $prefix = explode("_", $this->getDbPrefix());
            return $prefix[0];
        }
        $line = $this->db_tables[$mod];
        return substr_replace($this->getDbPrefix(), $line, strlen($this->getDbPrefix()) - strlen($line));
    }

    public function getTableName($mod) {
        return substr_replace($this->getTablePrefix(), $mod, strlen($this->getTablePrefix()) - strlen($mod));
    }

    private function getMod($id) {
        return $mod = $id % $this->getTableTotalNum();
    }

    private function setDbPrefix($db_prefix) {
        $this->db_prefix = $db_prefix;
    }

    private function getDbPrefix() {
        return $this->db_prefix;
    }

    private function setDbTotalNum($db_total_num) {
        $this->db_total_num = $db_total_num;
    }

    private function getDbTotalNum() {
        return ceil($this->getTableTotalNum() / $this->getOneDbTableNum());
    }

    private function setOneDbTableNum($one_db_table_num) {
        $this->one_db_table_num = $one_db_table_num;
    }

    private function getOneDbTableNum() {
        return $this->one_db_table_num;
    }

    private function setTablePrefix($table_prefix) {
        $this->table_prefix = $table_prefix;
    }

    private function getTablePrefix() {
        return $this->table_prefix;
    }

    private function setTableTotalNum($table_total_num) {
        $this->table_total_num = $table_total_num;
    }

    private function getTableTotalNum() {
        return $this->table_total_num;
    }

    private function setLogicColumn($logic_column) {
        $this->logic_column = $logic_column;
    }

    private function getLogicColumn() {
        return $this->logic_column;
    }

    private function setLogicTable($logic_table) {
        $this->logic_table = $logic_table;
    }

    private function getLogicTable() {
        return $this->logic_table;
    }

    private function setDbTables($db_tables) {
        $this->db_tables = $db_tables;
    }

    private function getDbTables() {
        return $this->db_tables;
    }

    private function setTableDbs($table_dbs) {
        $this->table_dbs = $table_dbs;
    }

    private function getTableDbs() {
        return $this->table_dbs;
    }

    private function setUseMysqliExtend() {
        if (defined("MYSQL_EXTEND")) {
            if (MYSQL_EXTEND == 'mysqli') {
                $this->use_mysqli_extend = true;
            }
            if (MYSQL_EXTEND == 'mysql_pdo') {
                $this->use_mysqli_extend = false;
            }
        }
    }

    private function getUseMysqliExtend() {
        return $this->use_mysqli_extend;
    }

    public static function strToIntKey($str) {
        $len = strlen($str);
        $total = 0;
        for ($i = 0; $i < $len; $i++) {
            $c = ord(substr($str, $i, 1));
            $total = $total + $c;
        }
        return $total;
    }

    /**
     *
     * @param string $sql 'select order_id,order_sn from order where user_id=#user_id# '
     * @param array $params 只能为一唯数组，并且包括分表的列名 array('user_id'=>100)
     */
    public function decorate($sql, $params = array()) {
        $logicTable = $this->getLogicTable();
        $db = null;
        if ($logicTable) {
            $logic_col = $this->getLogicColumn();
            if (!isset($params[$logic_col])) {
                throw new DBRouteException("error params ,it must have key " . $logic_col);
            }
            if ($this->getLogicColumnFieldType() && $this->getLogicColumnFieldType() == 'string') {
                $id = self::strToIntKey($params[$logic_col]);
            } else {
                $id = $params[$logic_col];
            }
            $mod = $this->getMod($id);
            $db = $this->getDbName($mod);

            $array['sql'] = $this->getNewSql($sql, $mod);
            $array['db_name'] = $db;
        } else {
            $array['sql'] = $sql;
            $array['db_name'] = $this->config_array['db'];
            $db = $this->config_array['db'];
        }
        $array['params'] = $params;
        $this->setDBConn($db);
        return $array;
    }

    private function getNewSql($sql, $mod) {
        $table_name = $this->getTableName($mod);
        $logic_table = $this->getLogicTable();
        $first_pos = stripos($sql, " " . $logic_table . " ");
        if (!$first_pos) {
            throw new DBRouteException("error sql in " . $sql);
        }
        $new_sql = substr_replace($sql, " " . $table_name . " ", $first_pos, strlen(" " . $logic_table . " "));
        return $new_sql;
    }

    private function setConnection($params = array()) {
        $logicTable = $this->getLogicTable();
        $db = null;
        if ($logicTable) {
            if (empty($params)) {
                return;
            }
            $logic_col = $this->getLogicColumn();
            if (!isset($params[$logic_col])) {
                throw new DBRouteException("error params ,it must have key " . $logic_col);
            }
            if ($this->getLogicColumnFieldType() && $this->getLogicColumnFieldType() == 'string') {
                $id = self::strToIntKey($params[$logic_col]);
            } else {
                $id = $params[$logic_col];
            }
            $mod = $this->getMod($id);
            $db = $this->getDbName($mod);
        } else {
            $db = $this->config_array['db'];
        }
        $this->setDBConn($db);
        return $db;
    }

    private function setDBConn($db) {
        $this->setUseMysqliExtend();
        if (!isset($this->connections[$db])) { //不存在则新创建一个连接
            if ($this->getUseMysqliExtend()) {
                $this->connections[$db] = cls_sqlexecute::getInstance($db, $this->config_array);
            } else {
                $this->connections[$db] = cls_pdosqlexecute::getInstance($db, $this->config_array);
            }
        }
    }

    private function isSingleDb() { //如果分库后是单库多表
        return $this->is_single_db;
    }

    private function getDbConnnection($db_name) { //如果分库后是单库多表
        return $this->connections[$db_name];
    }

    private function getSingleConn() { //如果分库后是单库多表
        if ($this->isSingleDb()) {
            foreach ($this->connections as $conn) {
                return $conn;
            }
        }
    }

    /**
     * @param $sql "insert order (order_id,order_sn,user_id) value (#order_id#,#order_sn#,#user_id#) "
     * @param array $params array('order_id'=>10,'order_sn'=>'123456','user_id'=>10)
     * @param bool $return_insert_id
     * @return int
     */
    public function insert($sql, $params = array(), $return_insert_id = false) {
        $decorate = $this->decorate($sql, $params);
        $db_name = $decorate['db_name'];
        return $this->getDbConnnection($db_name)->insert($decorate['sql'], $decorate['params'], $return_insert_id);
    }

    /**
     * @param $sql "update order set order_num=#order_num#,order_sn=#order_sn# where id=#id# and user_id=#user_id#"
     * @param array $params array('order_num'=>3,'order_sn'=>'sn123456','id'=>1,'user_id'=>10)
     * @param bool $return_affected_rows
     * @return int
     */
    public function update($sql, $params = array(), $return_affected_rows = true) {
        $decorate = $this->decorate($sql, $params);
        $db_name = $decorate['db_name'];
        return $this->getDbConnnection($db_name)->update($decorate['sql'], $decorate['params'], $return_affected_rows);
    }

    /**
     * @param $sql "delete from order where id=#id# and user_id=#user_id#"
     * @param array $params array('id'=>123,'user_id'=>10)
     * @param bool $return_affected_rows
     * @return int
     */
    public function delete($sql, $params = array(), $return_affected_rows = true) {
        $decorate = $this->decorate($sql, $params);
        $db_name = $decorate['db_name'];
        return $this->getDbConnnection($db_name)->delete($decorate['sql'], $decorate['params'], $return_affected_rows);
    }

    /**
     * 此方法只支持在一个表中批量插入 更新 删除 数据
     * @param $sql "insert order(order_id,order_sn,user_id) values (#order_id#,#order_sn#,#user_id#)"
     * @param array $batch_params (array(array('order_id'=>1,'order_sn'=>'password1','user_id'=>10),array('order_id'=>2,'order_sn'=>'password2','user_id'=>10)......))
     * @param array $logic_params 分表物理列名数组，如根据user_id分表的，此可传 array('user_id'=>10)
     * @param int $batch_num 不见意超过50,默认为20
     * @return 总共受影响行数
     */
    public function batchExecutes($sql, $batch_params = array(), $logic_params = array(), $batch_num = 20) {
        $decorate = $this->decorate($sql, $logic_params);
        $db_name = $decorate['db_name'];
        return $this->getDbConnnection($db_name)->batchExecutes($decorate['sql'], $batch_params, $batch_num);
    }

    /**
     * @param $sql "select order_id,order_sn from order where user_id=#user_id#"
     * @param array $bind_params array('user_id'=>10)
     * @return array
     */
    public function getAll($sql, $params = array()) {
        $decorate = $this->decorate($sql, $params);
        $db_name = $decorate['db_name'];
        return $this->getDbConnnection($db_name)->getAll($decorate['sql'], $decorate['params']);
    }

    /**
     * @param $sql "select  order_id,order_sn from order where user_id=#user_id# "
     * @param array $bind_params array('user_id'=>10)
     * @return array
     */
    public function getRow($sql, $params = array()) {
        $decorate = $this->decorate($sql, $params);
        $db_name = $decorate['db_name'];
        return $this->getDbConnnection($db_name)->getRow($decorate['sql'], $decorate['params']);
    }

    /**
     * @param $sql "select count(1) as count_num from order where user_id=#user_id# "
     * @param array $bind_params array('user_id'=>100)
     * @return int
     * @see getColumn
     */
    public function getOne($sql, $params = array()) {
        $decorate = $this->decorate($sql, $params);
        $db_name = $decorate['db_name'];
        return $this->getDbConnnection($db_name)->getOne($decorate['sql'], $decorate['params']);
    }

    /**
     * @param $sql "select count(1) as count_num from order where user_id=#user_id# "
     * @param array $bind_params array('user_id'=>100)
     * @return int
     */
    public function getColumn($sql, $params = array()) {
        $decorate = $this->decorate($sql, $params);
        $db_name = $decorate['db_name'];
        return $this->getDbConnnection($db_name)->getColumn($decorate['sql'], $decorate['params']);
    }

    /**
     * 支持分表列in查询，此方法一般会查多个库表,主要根据in条件
     * select in 查询，只支持in，不支持分表列的大于等于 |小于等于| between...and 操作
     * @param string $sql select id,user_id,order_sn,add_time from order where id>#id# and user_id in(#user_ids#) limit 0,30  user_ids为config.php中的select_in_logic_column
     * @param array $params（key为:size|sort_field|sort_order|及当前类中select_in_logic_column的值）  key为select_in_logic_column 的值为数组 具体参见 OrderModel类中的方法
     */
    public function selectByIn($sql, $params = array()) {
        $logicTable = $this->getLogicTable();
        if (!$logicTable) {
            return;
        }
        if (!isset($params[$this->select_in_logic_column])) {
            throw new DBRouteException("select in 条件参数key名为" . $this->select_in_logic_column . "");
        }
        if (!stripos($sql, "#" . $this->select_in_logic_column . "#")) {
            throw new DBRouteException("select in 条件参数key名为#" . $this->select_in_logic_column . "#");
        }
        $in_param_arr = $params[$this->select_in_logic_column];
        if (!is_array($in_param_arr)) {
            throw new DBRouteException("select in 条件参数值为数组");
        }
        if (empty($in_param_arr)) {
            throw new DBRouteException("select in 条件参数值为空");
        }
        $size = isset($params['size']) ? $params['size'] : 20;
        $sort_filed = isset($params['sort_filed']) ? $params['sort_filed'] : '';
        $sort_order = isset($params['sort_order']) ? $params['sort_order'] : 'desc';
        if ($size >= 100) {
            $size = 100;
        }
        if (!stripos($sql, " limit ")) {
            $sql = $sql . " limit " . $size;
        }

        unset($params['size']);
        unset($params['sort_filed']);
        unset($params['sort_order']);
        unset($params[$this->select_in_logic_column]);

        $array = array();
        foreach ($in_param_arr as $key => $value) {
            $in = new InValue();
            if ($this->getLogicColumnFieldType() == 'string' && is_string($value)) {
                $mod = $this->getMod(self::strToIntKey($value));
            } else {
                $mod = $this->getMod($value);
            }
            $in->setMod($mod);
            $in->setValue($value);
            $db = $this->getDbName($mod);
            $this->setDBConn($db);
            $array[] = $in;
        }

        $new_array = array(); //key为mod, 值为相同mod的所有value
        $db_total_num = $this->getTableTotalNum();
        foreach ($array as $inValue) {
            for ($i = 0; $i < $db_total_num; $i++) {
                if ($inValue->getMod() == $i) {
                    $new_array[$i][] = $inValue->getValue();
                    break;
                }
            }
        }

        foreach ($new_array as $mod => $value_array) {
            $in_value_arrays[$mod] = array();
            $in_params[$mod] = array();
            foreach ($value_array as $key => $val) {
                $in_value_arrays[$mod][] = '#p_' . $key . '_v#';
                $in_params[$mod]['p_' . $key . "_v"] = $val;
            }
            foreach ($params as $k => $v) {
                $in_params[$mod][$k] = $v;
            }
        }

        $merge_result = array();
        foreach ($in_value_arrays as $mod => $val) {
            $new_sql = str_ireplace("#" . $this->select_in_logic_column . "#", implode(',', array_values($val)), $sql);
            $table_name = $this->getTableName($mod);
            $logic_table = $this->getLogicTable();
            $first_pos = stripos($new_sql, " " . $logic_table . " ");
            if (!$first_pos) {
                throw new DBRouteException("error sql in " . $sql);
            }
            $new_sql = substr_replace($new_sql, " " . $table_name . " ", $first_pos, strlen(" " . $logic_table . " "));

            $db_name = $this->getDbName($mod);
            $result = $this->getDbConnnection($db_name)->getAll($new_sql, $in_params[$mod]);
            if ($result) {
                foreach ($result as $row) {
                    $merge_result[] = $row;
                }
            }
        }

        if ($merge_result) {
            if ($sort_filed) {
                foreach ((array)$merge_result as $key => $row) {
                    $sort_folder[$key] = $row[$sort_filed];
                }
                if ($sort_order == 'desc') {
                    array_multisort($sort_folder, SORT_DESC, $merge_result);
                } else {
                    array_multisort($sort_folder, SORT_ASC, $merge_result);
                }
            }
            return array_slice($merge_result, 0, $size);
        } else {
            return array();
        }
    }

    /**
     * 访问所有库表 不见意使用此方法
     * @param string $sql select user_id,order_sn,add_time from order where id >1000 and id<10000 limit 0,20 order by add_time desc
     * @param array $params 参数 size、sort_filed、sort_order(0:asc,1:desc) 需设置  不能设置逻辑列的值
     */
    public function queryResultFromAllDbTables($sql, $params = array()) {
        $logicTable = $this->getLogicTable();
        if (!$logicTable) {
            return;
        }
        $size = isset($params['size']) ? $params['size'] : 20;
        $sort_filed = isset($params['sort_filed']) ? $params['sort_filed'] : '';
        $sort_order = isset($params['sort_order']) ? $params['sort_order'] : 1;

        unset($params['size']);
        unset($params['sort_filed']);
        unset($params['sort_order']);

        $logic_col = $this->getLogicColumn();
        if (isset($params[$logic_col])) {
            throw new DBRouteException("error params ,it must not have key " . $logic_col);
        }

        if ($this->isSingleDb()) {
            $tables = array();
            $total_table_num = $this->getTableTotalNum();
            for ($i = 0; $i < $total_table_num; $i++) {
                $tables[$i] = $i;
            }
        } else {
            $tables = array_keys($this->db_tables);
        }

        $merge_result = array();
        foreach ($tables as $mod) {
            $db = $this->getDbName($mod);
            $this->setDBConn($db);
            $new_sql = $this->getNewSql($sql, $mod);
            $result = $this->getDbConnnection($db)->getAll($new_sql, $params);
            if ($result) {
                foreach ($result as $row) {
                    $merge_result[] = $row;
                }
            }
        }

        if ($merge_result) {
            if ($sort_filed) {
                foreach ((array)$merge_result as $key => $row) {
                    $sort_folder[$key] = $row[$sort_filed];
                }
                if ($sort_order) {
                    array_multisort($sort_folder, SORT_DESC, $merge_result);
                } else {
                    array_multisort($sort_folder, SORT_ASC, $merge_result);
                }
            }
            return array_slice($merge_result, 0, $size);
        } else {
            return array();
        }
    }

    public function getConnection($params = array()) { //用于分表的表与不分表的表共用同一个数据库链接，一般在事务中可能用到
        $db_name = $this->setConnection($params);
        if ($this->isSingleDb()) {
            return $this->getSingleConn();
        }
        return $this->getDbConnnection($db_name);
    }

    public function begin($params = array()) {
        $db_name = $this->setConnection($params);
        if ($this->isSingleDb()) {
            $this->getSingleConn()->begin();
        } else {
            if (empty($params)) throw new DBRouteException('请传递参数');
            $this->getDbConnnection($db_name)->begin();
        }
    }

    public function commit($params = array()) {
        if ($this->isSingleDb()) {
            $this->getSingleConn()->commit();
        } else {
            if (empty($params)) throw new DBRouteException('请传递参数');
            $db_name = $this->setConnection($params);
            $this->getDbConnnection($db_name)->commit();
        }
    }

    public function rollBack($params = array()) {
        if ($this->isSingleDb()) {
            $this->getSingleConn()->rollBack();
        } else {
            if (empty($params)) throw new DBRouteException('请传递参数');
            $db_name = $this->setConnection($params);
            $this->getDbConnnection($db_name)->rollBack();
        }
    }

    public function __destruct() {
        if ($this->connections) {
            foreach ($this->connections as $conn) {
                if ($conn) {
                    $conn->closeConnection();
                }
            }
        }
    }
}

class InValue {

    private $mod; //余数值

    private $value; //原值

    public function setMod($mod) {
        $this->mod = $mod;
    }

    public function getMod() {
        return $this->mod;
    }

    public function setValue($value) {
        $this->value = $value;
    }

    public function getValue() {
        return $this->value;
    }

}

class DBRouteException extends Exception {

    public function __construct($message) {
        $this->message = $message;
    }

}
