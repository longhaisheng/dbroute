<?php
/**
 * dbroute sql解析类，可操作分表的sql,也可操作未分表的sql
 * 对开发者透明,sql中的参数以前后#绑写,详见方法注释
 * @author longhaisheng qq:87188524
 *
 */
class cls_dbroute {

	/** DB路由解析器  */
	private $dbParse;

	/** 连接配置数组  */
	private $config_array;

	/** 是否使用mysqli扩展，默认为true*/
	private $use_mysqli_extend=true;

	/** 数据库连接 cls_mysqli 类的数组*/
	private $connections = array();

    /** 数据库解析器字符串类型 */
    private $db_hash_type;
	
	public function __construct($db_route_array=array()){
		global $default_config_array;
		if ($db_route_array) {
			$this->config_array = $db_route_array;
		} else {
			$this->config_array = $default_config_array;
		}

        $this->db_hash_type='mod_hash';
        if(isset($this->config_array['db_hash_type'])){
            $this->db_hash_type=$this->config_array['db_hash_type'];
        }
        if($this->db_hash_type==='consistent_hash'){//一致性hash
		    $this->dbParse=new ConsistentHash($this->config_array);
        }
        if($this->db_hash_type==='virtual_hash'){//虚拟节点hash
            $this->dbParse=new VirtualHash($this->config_array);
        }
        if($this->db_hash_type==='mod_hash'){//mod Hash
            $this->dbParse=new ModHash($this->config_array);
        }
	}

	private function setDbParse($parse) {
		$this->dbParse = $parse;
	}

	public function getDbParse() {
		return $this->dbParse;
	}

    public function setDbHashType($hash_type) {
        $this->db_hash_type = $hash_type;
    }

    public function getDbHashType() {
        return $this->db_hash_type;
    }

	public function getDBAndTableName($params=array()){
		$logic_column_value = $this->get_logic_column_value($params);
		$table_name=$this->getDbParse()->getTableName($logic_column_value);
		$db_name=$this->get_db_name($params);
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
	 * @param array $params 只能为一唯数组，非日期分表必须包括分表的列名 array('user_id'=>100)
	 */
	private function decorate($sql, $params = array()) {
		$logicTable = $this->getDbParse()->getLogicTable();
        $db = null;
        if ($logicTable) {
            $logic_col_value = $this->get_logic_column_value($params);
			$array['db_name'] = $this->get_db_name($params);
			$array['sql'] = $this->getNewSql($sql, $logic_col_value);
		} else {
			$array['db_name'] = $this->config_array['db'];
			$array['sql'] = $sql;
		}
		$array['params'] = $params;
		$this->setDBConn($array['db_name']);
		return $array;
	}

    private function get_logic_column_value($params) {
        $logic_col = $this->getDbParse()->getTableLogicColumn();
        $date_table = $this->getDbParse()->getTableNameType();
        $logic_col_value = $params[$logic_col];
        if ($date_table && $date_table == 'date') {
            $logic_col_value = $logic_col_value ? $logic_col_value : '';
            return $logic_col_value;
        } else {
            if (!isset($params[$logic_col])) {
                throw new DBRouteException("error params ,it must have key " . $logic_col);
            }
            return $logic_col_value;
        }
    }

    private function get_db_name($params) {
        $db_logic_column = $this->getDbParse()->getDbLogicColumn();
        if($db_logic_column){
	        if (!isset($params[$db_logic_column])) {
	            throw new DBRouteException("error params ,it must have key " . $db_logic_column);
	        }
	        $db_logic_column_value = $params[$db_logic_column];
        }else{
	        $logic_col = $this->getDbParse()->getTableLogicColumn();
			if (!isset($params[$logic_col])) {
				throw new DBRouteException("error params ,it must have key " . $logic_col);
			}
			$db_logic_column_value=$params[$logic_col];
        }
        return $this->getDbParse()->getDbName($db_logic_column_value);
    }

    private function getNewSql($sql,$logic_column_value='') {
		$date_table=$this->getDbParse()->getTableNameType();
		if(!isset($date_table) && $date_table !='date' &&  empty($logic_column_value)){
			throw new DBRouteException("非日期分表必须要有逻辑列的值 ");
		}
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
				throw new DBRouteException("setConnection error,must have params ");
			}
			$db = $this->get_db_name($params);
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
	 * 只支持分表的表,不包括日期分表
	 * 支持分表列in查询，此方法一般会查多个库表,主要根据in条件
	 * select in 查询，只支持in，不支持分表列的大于等于 |小于等于| between...and 操作
	 * @param string $sql select id,user_id,order_sn,add_time from order where id>#id# and user_id in(#user_ids#) limit 0,30  user_ids为config.php中的select_in_logic_column
	 * @param array $params（key为:size|sort_field|sort_order|及当前类中select_in_logic_column的值）  key为select_in_logic_column 的值为数组 具体参见 OrderModel类中的方法
	 */
	public function selectByIn($sql, $params = array()) {
		$logicTable = $this->getDbParse()->getLogicTable();
		$tableNameType = $this->getDbParse()->getTableNameType();
		if (!$logicTable) {
			throw new DBRouteException("非逻辑表不支持此方法");
		}
		if (isset($tableNameType) && $tableNameType=='date') {
			throw new DBRouteException("日期分表不支持此方法");
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
		
		$tableLogicColumn=$this->getDbParse()->getTableLogicColumn();
		$dbLogicColumn=$this->getDbParse()->getDbLogicColumn();
		

		$db_param_list = array();//每个数据库中 余数(余一个库中的表数)相同的值数组
		foreach ($in_param_arr as $key => $value) {
			if ($this->getDbParse()->getLogicColumnFieldType() == 'string' && is_string($value) && !is_numeric($value)) {
				$value = self::strToIntKey($value);
				$in_param_arr[$key]=$value;
			} 
			$mod=$this->getDbParse()->getTableMod($value);
			if($dbLogicColumn && $dbLogicColumn==$tableLogicColumn){//分库列与分表列相同
				$db_name = $this->getDbParse()->getDbName($value);
				$db_param_list[$db_name][$mod][]=$value;
			}else{
				$all_dbs=array_keys($this->getDbParse()->getDbList());
				foreach ($all_dbs as $db_name){
					$this->setDBConn($db_name);
					$db_param_list[$db_name][$mod][]=$value;
				}
			}
		}

		$merge_result = array();
		foreach ($db_param_list as $db_name => $one_db_mod_values) {
			$this->setDBConn($db_name);
			$mod_db_name=array();
			foreach ($one_db_mod_values as $mod=>$value_array){
				$in_value_arrays = array();
				$in_params = array();
				$in_value_arrays[$mod] = array();
				$in_params[$mod] = array();
				foreach ($value_array as $key => $val) {
					$in_value_arrays[$mod][] = '#p_' . $key . '_v#';
					$in_params[$mod]['p_' . $key . "_v"] = $val;
					$mod_db_name[$mod]['table_name']=$this->getDbParse()->getTableName($val,$db_name);//同一库中余数相同的,肯定定位至同一个表中
				}
				foreach ($params as $k => $v) {
					$in_params[$mod][$k] = $v;
				}
			
				foreach ($in_value_arrays as $mod => $val) {
					$table_name=$mod_db_name[$mod]['table_name'];
					$new_sql = str_ireplace("#" . $select_in_logic_column . "#", implode(',', array_values($val)), $sql);
					$first_pos = stripos($new_sql, " " . $logicTable . " ");
					if (!$first_pos) {
						throw new DBRouteException("error sql in " . $sql);
					}
					$new_sql = substr_replace($new_sql, " " . $table_name . " ", $first_pos, strlen(" " . $logicTable . " "));
					//echo $new_sql.'=>'.$db_name."<br>";
					$result = $this->getDbConnnection($db_name)->getAll($new_sql, $in_params[$mod]);
					if ($result) {
						foreach ($result as $row) {
							$merge_result[] = $row;
						}
					}
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
	 * 只支持分表的表,不包括日期分表
	 * 访问所有库表 不见意使用此方法
	 * @param string $sql select user_id,order_sn,add_time from order where id >1000 and id<10000 limit 0,20 order by add_time desc
	 * @param array $params 参数 size、sort_filed、sort_order(0:asc,1:desc) 需设置  不能设置逻辑列的值
	 */
	public function queryResultFromAllDbTables($sql, $params = array()) {
		$logicTable = $this->getDbParse()->getLogicTable();
		$tableNameType = $this->getDbParse()->getTableNameType();
		if (!$logicTable) {//非逻辑表不支持
			throw new DBRouteException("非逻辑表不支持此方法");
		}
		if (isset($tableNameType) &&$tableNameType=='date') {//日期分表不支持
			throw new DBRouteException("日期分表不支持此方法");
		}
		$size = isset($params['size']) ? $params['size'] : 20;
		$sort_filed = isset($params['sort_filed']) ? $params['sort_filed'] : '';
		$sort_order = isset($params['sort_order']) ? $params['sort_order'] : 1;

		unset($params['size']);
		unset($params['sort_filed']);
		unset($params['sort_order']);

		$logic_col = $this->getDbParse()->getTableLogicColumn();
		if (isset($params[$logic_col])) {
			throw new DBRouteException("error params ,it must not have key " . $logic_col);
		}
		
		$merge_result = array();
		$db_tables =$this->getDbParse()->getDbList();
		foreach ($db_tables as $db_name=>$tables){
			$new_sql=null;
			foreach ($tables as $table_name){
				$first_pos = stripos($sql, " " . $logicTable . " ");
				if (!$first_pos) {
					throw new DBRouteException("error sql in " . $sql);
				}
				$new_sql = substr_replace($sql, " " . $table_name . " ", $first_pos, strlen(" " . $logicTable . " "));
				$this->setDBConn($db_name);
				$result = $this->getDbConnnection($db_name)->getAll($new_sql, $params);
				if ($result) {
					foreach ($result as $row) {
						$merge_result[] = $row;
					}
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

class DBRouteException extends Exception {

	public function __construct($message) {
		$this->message = $message;
	}

}
/**
 * 
 * 单个分库分表配置项基础抽象类
 * @author longhaisheng
 *
 */
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
	private $db_tables = array();//此属性暂时不用

	/** 逻辑表名 */
	private $logic_table;

	/** 分表的逻辑列名 */
	private $table_logic_column;

    /** 分库的逻辑列名 */
    private $db_logic_column;

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

    private $table_name_type;

    private $table_name_date_logic_string;

	private $db_list=array();

	protected function __construct($config_array = array()) {
		if(empty($config_array)) echo 'BaseConfig init error ';
		$this->config_array = $config_array;
        if(isset($this->config_array['table_name_type'])){
            $this->setTableNameType($this->config_array['table_name_type']);
        }
        if(isset($this->config_array['table_name_date_logic_string'])){
            $this->setTableNameDateLogicString($this->config_array['table_name_date_logic_string']);
        }
		if (isset($this->config_array['logic_table']) && isset($this->config_array['table_logic_column'])) {
			$this->setDbPrefix($this->config_array['db_prefix']);
			$this->setTablePrefix($this->config_array['table_prefix']);
			$this->setLogicTable($this->config_array['logic_table']);
			$this->setTableLogicColumn($this->config_array['table_logic_column']);
            if(isset($this->config_array['db_logic_column'])){
			    $this->setDbLogicColumn($this->config_array['db_logic_column']);
            }else{
                $this->setDbLogicColumn($this->config_array['table_logic_column']);
            }
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
			if ($this->getIsDebug()) {
				print_r($this->db_list);
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

	public function setTableLogicColumn($table_logic_column) {
		$this->table_logic_column = $table_logic_column;
	}

	public function getTableLogicColumn() {
		return $this->table_logic_column;
	}

    public function setDbLogicColumn($db_logic_column) {
        $this->db_logic_column = $db_logic_column;
    }

    public function getDbLogicColumn() {
        return $this->db_logic_column;
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

    public function setTableNameDateLogicString($table_name_date_logic_string) {
        $this->table_name_date_logic_string = $table_name_date_logic_string;
    }

    public function getTableNameDateLogicString() {
        return $this->table_name_date_logic_string;
    }

    public function setTableNameType($table_name_type) {
        $this->table_name_type = $table_name_type;
    }

    public function getTableNameType() {
        return $this->table_name_type;
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

	public function getTableMod($logic_column_value) {
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

    public function getTableName($logic_column_value,$db_name='') {
        if($this->getTableNameType()=='date' && $this->getTableNameDateLogicString()){
            if($this->getTableNameDateLogicString()=='yyyy'){
                $suffix=date("Y");
            }
            if($this->getTableNameDateLogicString()=='yyyyMM'){
                $suffix=date("Ym");
            }
            if($this->getTableNameDateLogicString()=='yyyyMMdd'){
                $suffix=date("Ymd");
            }
            if($this->getTableNameDateLogicString()=='MMdd'){
                $suffix=date("md");
            }
            if($this->getTableNameDateLogicString()=='MM'){
                $suffix=date("m");
            }
            if($this->getTableNameDateLogicString()=='dd'){
                $suffix=date("d");
            }
            return substr_replace($this->getTablePrefix(), $suffix, strlen($this->getTablePrefix()) - 4);
        }
        if(empty($db_name)){
        	$db_name=$this->getDbName($logic_column_value);
        }
        $db_list=$this->getDbList();
        $one_db_tables=$db_list[$db_name];
        $table_index=$this->getTableMod($logic_column_value);
        return $one_db_tables[$table_index];
    }

    abstract function getDbName($logic_column_value);

}

/**
 * 
 * 取模hash
 * @author longhaisheng
 *
 */
class ModHash extends BaseConfig{
	
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
 * 一致性hash算法实现(分段hash)
 * @author longhaisheng
 *
 */
class ConsistentHash extends BaseConfig{

   /** 一致性hash配置字符串 
	* 字符串值为："[0,256]=sc_refund_0000;[256,512]=sc_refund_0001;[512,768]=sc_refund_0002;[768,1024]=sc_refund_0003" 表示：
	* 逻辑列值 mod Hash最大区间值之后 在 >=0 && <256时 会路由到sc_refund_0000库，后面以此类推，如果都不在以上范围，默认库为字符串中配置的第一个库，即sc_refund_0000,迁移时
	* 可将[0,256]重新划分为[0,128]=sc_refund_0000和[128,256]=sc_refund_0005
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
            	$node->setIsDefaultDb(true);
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
			
			if($node->getIsDefaultDb()){
				$default_db_name= $node->getDbName();
			}
		}
		return $db_name?$db_name:$default_db_name;
	}

}
/**
 * 
 * 虚拟节点hash算法实现
 * @author longhaisheng
 *
 */
class VirtualHash extends BaseConfig{

	/** 虚拟节点个数 */
	private $virtual_db_node_number=64;
	
	/** hash算法类实例 */
    private $hash;

    function __construct($config_array = array()){
        parent::__construct($config_array);
        if(isset($config_array['virtual_db_node_number'])){
            $this->virtual_db_node_number=$config_array['virtual_db_node_number'];
            $this->hash=new cls_flexihash(new Flexihash_Crc32Hasher(),$this->virtual_db_node_number);

        }
        $this->initialize();
    }

    private function initialize(){//将实际数据库结点对应至圆上
        $total_table_num=parent::getDbTotalNum();
        for($i=0;$i<$total_table_num;$i++){
            $db_name= substr_replace(parent::getDbPrefix(), $i, strlen(parent::getDbPrefix()) - strlen($i));
            $this->hash->addTarget($db_name);
        }
    }

    public function getDbName($logic_column_value) {
        if (parent::getIsSingleDb()) { //单库
            $prefix = explode("_", parent::getDbPrefix());
            return $prefix[0];
        }
        if (parent::getLogicColumnFieldType() && parent::getLogicColumnFieldType() == 'string'  && !is_numeric($logic_column_value)) {
            $logic_column_value=cls_dbroute::strToIntKey($logic_column_value);
        }
        return $this->hash->lookup($logic_column_value);
    }

    public function setVirtualDbNodeNumber($virtual_db_node_number) {
        $this->virtual_db_node_number = $virtual_db_node_number;
    }

    public function getVirtualDbNodeNumber() {
        return $this->virtual_db_node_number;
    }

}

class Node{

	/** 节点开始值 */
    private $start;

	/** 节点结束值 */
    private $end;

	/** 节点段中的数据库名 */
    private $db_name;

	/** 节点中的数据库名是否是默认db */
    private $is_default_db=false;
    
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

    public function setIsDefaultDb($default_db) {
        $this->is_default_db = $default_db;
    }

    public function getIsDefaultDb() {
        return $this->is_default_db;
    }

}
