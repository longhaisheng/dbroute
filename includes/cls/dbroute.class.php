<?php
class cls_dbroute {

	/** DB路由解析器  */
	private $dbParse;

	/** 连接配置数组  */
	private $config_array;

	/** 是否使用mysqli扩展，默认为true*/
	private $use_mysqli_extend=true;

	/** 数据库连接 cls_mysqli 类的数组*/
	private $connections = array();

	public function __construct($db_route_array=array()){
		global $default_config_array;
		if ($db_route_array) {
			$this->config_array = $db_route_array;
		} else {
			$this->config_array = $default_config_array;
		}
        if(isset($this->config_array['consistent_hash_separate_string'])){
		    $this->dbParse=new ConsistentHash($this->config_array);
        }else{
            $this->dbParse=new ModHash($this->config_array);
        }
	}

	private function setDbParse($parse) {
		$this->dbParse = $parse;
	}

	public function getDbParse() {
		return $this->dbParse;
	}
	
	public function getDBAndTableName($logic_colum_value){
		$table_name=$this->getDbParse()->getTableName($logic_colum_value);
		$db_name=$this->getDbParse()->getDbName($logic_colum_value);
		return array('db_name'=>$db_name,'table_name'=>$table_name);
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

	public static function strToIntKey($str) {//字符串转数字
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
	private function decorate($sql, $params = array()) {
		$logicTable = $this->getDbParse()->getLogicTable();
		$db = null;
		if ($logicTable) {
			$logic_col = $this->getDbParse()->getLogicColumn();
			if (!isset($params[$logic_col])) {
				throw new DBRouteException("error params ,it must have key " . $logic_col);
			}
			$logic_col_value=$params[$logic_col];
			$db = $this->getDbParse()->getDbName($logic_col_value);
			$array['sql'] = $this->getNewSql($sql, $logic_col_value);
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

	private function getNewSql($sql,$logic_column_value) {
		$table_name = $this->getDbParse()->getTableName($logic_column_value);
		$logic_table = $this->getDbParse()->getLogicTable();
		$first_pos = stripos($sql, " " . $logic_table . " ");
		if (!$first_pos) {
			throw new DBRouteException("error sql in " . $sql);
		}
		$new_sql = substr_replace($sql, " " . $table_name . " ", $first_pos, strlen(" " . $logic_table . " "));
		return $new_sql;
	}

	private function setConnection($params = array()) {
		$logicTable = $this->getDbParse()->getLogicTable();
		$db = null;
		if ($logicTable) {
			if (empty($params)) {
				return;
			}
			$logic_col = $this->getDbParse()->getLogicColumn();
			if (!isset($params[$logic_col])) {
				throw new DBRouteException("error params ,it must have key " . $logic_col);
			}
			$logic_column_value=$params[$logic_col];
			$db = $this->getDbParse()->getDbName($logic_column_value);
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

	private function getDbConnnection($db_name) { //如果分库后是单库多表
		return $this->connections[$db_name];
	}

	private function getSingleConn() { //如果分库后是单库多表
		if ($this->getDbParse()->getIsSingleDb()) {
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
	 */
	public function getColumn($sql, $params = array()) {
		$decorate = $this->decorate($sql, $params);
		$db_name = $decorate['db_name'];
		return $this->getDbConnnection($db_name)->getColumn($decorate['sql'], $decorate['params']);
	}

	/**
	 * 只支持分表的表
	 * 支持分表列in查询，此方法一般会查多个库表,主要根据in条件
	 * select in 查询，只支持in，不支持分表列的大于等于 |小于等于| between...and 操作
	 * @param string $sql select id,user_id,order_sn,add_time from order where id>#id# and user_id in(#user_ids#) limit 0,30  user_ids为config.php中的select_in_logic_column
	 * @param array $params（key为:size|sort_field|sort_order|及当前类中select_in_logic_column的值）  key为select_in_logic_column 的值为数组 具体参见 OrderModel类中的方法
	 */
	public function selectByIn($sql, $params = array()) {
		$logicTable = $this->getDbParse()->getLogicTable();
		if (!$logicTable) {
			return;
		}
		$select_in_logic_column=$this->getDbParse()->getSelectInLogicColumn();
		if (!isset($params[$select_in_logic_column])) {
			throw new DBRouteException("select in 条件参数key名为" . $select_in_logic_column . "");
		}
		if (!stripos($sql, "#" . $select_in_logic_column . "#")) {
			throw new DBRouteException("select in 条件参数key名为#" . $select_in_logic_column . "#");
		}
		$in_param_arr = $params[$select_in_logic_column];
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
		unset($params[$select_in_logic_column]);

		$array = array();
		foreach ($in_param_arr as $key => $value) {
			$in = new InValue();
			if ($this->getDbParse()->getLogicColumnFieldType() == 'string' && is_string($value) && !is_numeric($value)) {
				$mod = self::strToIntKey($value) % $this->getDbParse()->getTableTotalNum();
			} else {
				$mod = $value % $this->getDbParse()->getTableTotalNum();

			}
			$in->setMod($mod);
			$in->setValue($value);
			$db = $this->getDbParse()->getDbName($mod);
			$this->setDBConn($db);
			$array[] = $in;
		}

		$new_array = array(); //key为mod, 值为相同mod的所有value
		$db_total_num = $this->getDbParse()->getTableTotalNum();
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
			$new_sql = str_ireplace("#" . $this->getDbParse()->getSelectInLogicColumn() . "#", implode(',', array_values($val)), $sql);
			$table_name = $this->getDbParse()->getTableName($mod);
			$logic_table = $this->getDbParse()->getLogicTable();
			$first_pos = stripos($new_sql, " " . $logic_table . " ");
			if (!$first_pos) {
				throw new DBRouteException("error sql in " . $sql);
			}
			$new_sql = substr_replace($new_sql, " " . $table_name . " ", $first_pos, strlen(" " . $logic_table . " "));

			$db_name = $this->getDbParse()->getDbName($mod);
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
	 * 只支持分表的表
	 * 访问所有库表 不见意使用此方法
	 * @param string $sql select user_id,order_sn,add_time from order where id >1000 and id<10000 limit 0,20 order by add_time desc
	 * @param array $params 参数 size、sort_filed、sort_order(0:asc,1:desc) 需设置  不能设置逻辑列的值
	 */
	public function queryResultFromAllDbTables($sql, $params = array()) {
		$logicTable = $this->getDbParse()->getLogicTable();
		if (!$logicTable) {
			return;
		}
		$size = isset($params['size']) ? $params['size'] : 20;
		$sort_filed = isset($params['sort_filed']) ? $params['sort_filed'] : '';
		$sort_order = isset($params['sort_order']) ? $params['sort_order'] : 1;

		unset($params['size']);
		unset($params['sort_filed']);
		unset($params['sort_order']);

		$logic_col = $this->getDbParse()->getLogicColumn();
		if (isset($params[$logic_col])) {
			throw new DBRouteException("error params ,it must not have key " . $logic_col);
		}

		$tables = array();
		$total_table_num = $this->getDbParse()->getTableTotalNum();
		for ($i = 0; $i < $total_table_num; $i++) {
			$tables[$i] = $i;
		}

		$merge_result = array();
		foreach ($tables as $mod) {
			$db = $this->getDbParse()->getDbName($mod);
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
		if ($this->getDbParse()->getIsSingleDb()) {
			return $this->getSingleConn();
		}
		return $this->getDbConnnection($db_name);
	}

	public function begin($params = array()) {
		$db_name = $this->setConnection($params);
		if ($this->getDbParse()->getIsSingleDb()) {
			$this->getSingleConn()->begin();
		} else {
			if (empty($params)) throw new DBRouteException('请传递参数');
			$this->getDbConnnection($db_name)->begin();
		}
	}

	public function commit($params = array()) {
		if ($this->getDbParse()->getIsSingleDb()) {
			$this->getSingleConn()->commit();
		} else {
			if (empty($params)) throw new DBRouteException('请传递参数');
			$db_name = $this->setConnection($params);
			$this->getDbConnnection($db_name)->commit();
		}
	}

	public function rollBack($params = array()) {
		if ($this->getDbParse()->getIsSingleDb()) {
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

abstract class BaseConfig{

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
	private $db_tables = array();////////////////

	private $table_dbs=array();

	/** 逻辑表名 */
	private $logic_table;

	/** 分表的逻辑列名 */
	private $logic_column;

	/** select in 查询时时的参数key名 */
	private $select_in_logic_column;

	/** 是否单库多表 true为单库 */
	private $is_single_db;

	/** 分库分表配置项*/
	private $config_array;

	/** 是否为调试模式*/
	private $is_debug = false;

	/** 逻辑字段值类型*/
	private $logic_column_field_type;

	private $db_list=array();

	protected function __construct($config_array = array()) {
		if(empty($config_array)) echo 'BaseConfig init error ';
		$this->config_array = $config_array;
		if (isset($this->config_array['logic_table']) && isset($this->config_array['logic_column'])) {
			$this->setDbPrefix($this->config_array['db_prefix']);
			$this->setTablePrefix($this->config_array['table_prefix']);
			$this->setLogicTable($this->config_array['logic_table']);
			$this->setLogicColumn($this->config_array['logic_column']);
			$this->setTableTotalNum($this->config_array['table_total_num']);
			$this->setOneDbTableNum($this->config_array['one_db_table_num']);
			$this->setSelectInLogicColumn($this->config_array['select_in_logic_column']);
			$this->setLogicColumnFieldType($this->config_array['logic_column_field_type']);
			if (defined("IS_DEBUG")) {
				$this->setIsDebug (IS_DEBUG);
			}
			if ($this->getOneDbTableNum() == $this->getTableTotalNum()) {
				$this-> setIsSingleDb(true); //单库
			} else {
				$this-> setIsSingleDb(false); //多库
			}
			$list = cls_shmop::readArray("init_logic_" . $this->getLogicTable());
			if ($list) {
				$this->db_list=$list;
			} else {
				$this->init();
			}
		}
	}

	private function init() {
		$db_total_num = $this->getDbTotalNum();
		$mod = $this->getTableTotalNum() % $this->getOneDbTableNum();
		$num = 0;
		for ($i = 0; $i < $db_total_num; $i++) {
			$num++;
			$crea = ($i * $this->getOneDbTableNum());
			$crea_two = ($i + 1) * $this->getOneDbTableNum();
			$tables = array();
			for ($j = $crea; $j < $crea_two; $j++) {
				$tables[] = $this->getTable($j);
				/*if ($this->getIsSingleDb()) {
					$this->db_tables[0] = $i;
				} else {
					$this->db_tables[$j] = $i; //key为表的数字下缀，value为数据库数字下缀（下缀大于等于零）
				}*/
			}
			if ($mod && $num == $db_total_num) {
				$tables = array_slice($tables, 0, $mod);
			}
			if ($this->getIsSingleDb()) {//单库
				$prefix = explode("_", $this->getDbPrefix());
				$db_key = $prefix[0];
			} else {
				$db_key = substr_replace($this->getDbPrefix(), $i, strlen($this->getDbPrefix()) - strlen($i));
			}
			$this->db_list[$db_key] = $tables;
		}
		cls_shmop::writeArray("init_logic_" . $this->getLogicTable(), $this->db_list);
		if ($this->getIsDebug()) {
			print_r($this->db_list);
		}
	}

	public function setDbPrefix($db_prefix) {
		$this->db_prefix = $db_prefix;
	}

	public function getDbPrefix() {
		return $this->db_prefix;
	}

	public function setTablePrefix($table_prefix) {
		$this->table_prefix = $table_prefix;
	}

	public function getTablePrefix() {
		return $this->table_prefix;
	}

	public function setDbTotalNum($db_total_num) {
		$this->db_total_num = $db_total_num;
	}

	public function getDbTotalNum() {
		return ceil($this->getTableTotalNum() / $this->getOneDbTableNum());
	}

	public function setTableTotalNum($table_total_num) {
		$this->table_total_num = $table_total_num;
	}

	public function getTableTotalNum() {
		return $this->table_total_num;
	}

	public function setOneDbTableNum($one_db_table_num) {
		$this->one_db_table_num = $one_db_table_num;
	}

	public function getOneDbTableNum() {
		return $this->one_db_table_num;
	}

	private function setDbTables($db_tables) {
		$this->db_tables = $db_tables;
	}

	private function getDbTables() {
		return $this->db_tables;
	}

	public function setLogicTable($logic_table) {
		$this->logic_table = $logic_table;
	}

	public function getLogicTable() {
		return $this->logic_table;
	}

	public function setLogicColumn($logic_column) {
		$this->logic_column = $logic_column;
	}

	public function getLogicColumn() {
		return $this->logic_column;
	}

	public function setSelectInLogicColumn($select_in_logic_column) {
		$this->select_in_logic_column = $select_in_logic_column;
	}

	public function getSelectInLogicColumn() {
		return $this->select_in_logic_column;
	}

	public function setIsSingleDb($is_single_db) {
		$this->is_single_db = $is_single_db;
	}

	public function getIsSingleDb() {
		return $this->is_single_db;
	}

	public function setIsDebug($is_debug) {
		$this->is_debug = $is_debug;
	}

	public function getIsDebug() {
		return $this->is_debug;
	}

	public function setLogicColumnFieldType($logic_column_field_type) {
		$this->logic_column_field_type = $logic_column_field_type;
	}

	public function getLogicColumnFieldType() {
		return $this->logic_column_field_type;
	}

	private function getTable($mod) {
		return substr_replace($this->getTablePrefix(), $mod, strlen($this->getTablePrefix()) - strlen($mod));
	}

	public function setDbList($db_list) {
		$this->db_list = $db_list;
	}

	public function getDbList() {
		return $this->db_list;
	}

	protected function getTableMod($logic_column_value) {
		if ($this->getLogicColumnFieldType() && $this->getLogicColumnFieldType() == 'string' && !is_numeric($logic_column_value)) {
			$logic_column_value=cls_dbroute::strToIntKey($logic_column_value);
		}
		return $logic_column_value % $this->getOneDbTableNum();
	}

	protected function getDBMod($logic_column_value) {
		if ($this->getLogicColumnFieldType() && $this->getLogicColumnFieldType() == 'string'  && !is_numeric($logic_column_value)) {
			$logic_column_value=cls_dbroute::strToIntKey($logic_column_value);
		}
		return intval($logic_column_value % $this->getTableTotalNum() / $this->getOneDbTableNum());
	}

    public function getTableName($logic_column_value) {
        $db_name=$this->getDbName($logic_column_value);
        $db_list=$this->getDbList();
        $one_db_tables=$db_list[$db_name];
        $table_index=$this->getTableMod($logic_column_value);
        return $one_db_tables[$table_index];
    }

    abstract function getDbName($logic_column_value);

}

class ModHash extends BaseConfig{//mod hash

	function __construct($config_array = array()){
		parent::__construct($config_array);
	}

	/**
	 * 根据逻辑列的值取数据库名
	 * @param mixed $logic_column_value 逻辑列的值
	 */
	public function getDbName($logic_column_value) {
		if (parent::getIsSingleDb()) { //单库
			$prefix = explode("_", parent::getDbPrefix());
			return $prefix[0];
		}
		$db_index=parent::getDBMod($logic_column_value);
		return substr_replace(parent::getDbPrefix(), $db_index, strlen(parent::getDbPrefix()) - strlen($db_index));
	}

}
/**
 * 
 * 一致性hash算法实现
 * @author longhaisheng
 *
 */
class ConsistentHash extends BaseConfig{

   /** 一致性hash配置字符串 
	* 字符串值为："[0,256]=sc_refund_0000;[256,512]=sc_refund_0001;[512,768]=sc_refund_0002;[768,1024]=sc_refund_0003" 表示：
	* 逻辑列值 在 >=0 用小于256时 会路由到sc_refund_0000库，后面以此类推，如果都不在以上范围，默认库为字符串中配置的第一个库，即sc_refund_0000
	*/
	private $consistent_hash_separate_string;

	/** 一致性hash最大区间值  */
	private $consistent_hash_separate_mod_max_value;

	private $list=array();

    function __construct($config_array = array()){
		parent::__construct($config_array);
		if(isset($config_array['consistent_hash_separate_string'])){
			$this->consistent_hash_separate_string=$config_array['consistent_hash_separate_string'];

        }
        if(isset($config_array['consistent_hash_separate_mod_max_value'])){
            $this->consistent_hash_separate_mod_max_value=$config_array['consistent_hash_separate_mod_max_value'];
        }
        $this->init();
    }

    private function init(){
        $str=$this->getConsistentHashSeparateString();
        $list=explode(";", $str);
        $max=0;
        $i=0;
        foreach ($list as $value) {
            $one_db_config=explode("=", $value);
            $one_db_config[0]=str_replace("[", "", $one_db_config[0]);
            $one_db_config[0]=str_replace("]", "", $one_db_config[0]);
            $start_end_list=explode(",", $one_db_config[0]);
            if($max <=$start_end_list[1]){
                $max=$start_end_list[1];
            }
            $node=new Node();
            $node->setStart($start_end_list[0]);
            $node->setEnd($start_end_list[1]);
            $node->setDbName($one_db_config[1]);
            if($i==0){
            	$node->setDefaultDb(true);
            }
            $i++;
            $this->list[]=$node;
        }
        if($max !=$this->consistent_hash_separate_mod_max_value){
            throw new DBRouteException('一致性hash字符串设置错误');
        }
    }

    public function setConsistentHashSeparateModMaxValue($consistent_hash_separate_mod_max_value) {
        $this->consistent_hash_separate_mod_max_value = $consistent_hash_separate_mod_max_value;
    }

    public function getConsistentHashSeparateModMaxValue() {
        return $this->consistent_hash_separate_mod_max_value;
    }

    public function setConsistentHashSeparateString($consistent_hash_separate_string) {
        $this->consistent_hash_separate_string = $consistent_hash_separate_string;
    }

    public function getConsistentHashSeparateString() {
        return $this->consistent_hash_separate_string;
    }

    public function setList($list) {
		$this->list = $list;
	}

    public function getList() {
		return $this->list;
	}

    public function getDbName($logic_column_value) {
		if (parent::getIsSingleDb()) { //单库
			$prefix = explode("_", parent::getDbPrefix());
			return $prefix[0];
		}
		if (parent::getLogicColumnFieldType() && parent::getLogicColumnFieldType() == 'string'  && !is_numeric($logic_column_value)) {
			$logic_column_value=cls_dbroute::strToIntKey($logic_column_value);
		}

		$mod=intval($logic_column_value % $this->getConsistentHashSeparateModMaxValue());
		$default_db_name=null;
		$db_name=null;
		foreach ($this->getList() as $node){
			if($mod>=$node->getStart() && $mod<$node->getEnd()){
				$db_name= $node->getDbName();
				break;
			}
			
			if($node->getDefaultDb()){
				$default_db_name= $node->getDbName();
			}
		}
		return $db_name?$db_name:$default_db_name;
	}

}

class Node{

	private $start;

	private $end;

	private $db_name;
	
	private $default_db=false;

	public function setEnd($end) {
		$this->end = $end;
	}

	public function getEnd() {
		return $this->end;
	}

	public function setStart($start) {
		$this->start = $start;
	}

	public function getStart() {
		return $this->start;
	}

	public function setDbName($db_name) {
		$this->db_name = $db_name;
	}

	public function getDbName() {
		return $this->db_name;
	}

    public function setDefaultDb($default_db) {
        $this->default_db = $default_db;
    }

    public function getDefaultDb() {
        return $this->default_db;
    }


}
