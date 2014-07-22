<?php
/**
 * mysqli 操作类
 * @author longhaisheng(longhaisheng20@163.com,QQ:87188524)
 */
class cls_sqlexecute implements cls_idb{

	/** 主库链接 */
	private $connection;
	
	/** 读库链接  */
	private $read_connection;

	/** db链接字符串数组 */
	private $connect_array=array();

	/** 是否有读库 */
	private $has_read_db;
	
	/** 此次操作是否有事务 */
	private $this_operation_have_transaction=false;

	public function __construct($db_name,$default_config_array=array()) {
		if(empty($this->connect_array)){
			global $mysql_db_route_array;
			if($default_config_array){
				$this->connect_array=$default_config_array;
			}else{
				$this->connect_array=$mysql_db_route_array;
			}
			$this->connect_array['db']=$db_name;
		}
		if(isset($this->connect_array['read_db_hosts'])){
			$this->has_read_db=true;
		}
	}

	private function init(){
		if ($this->connection === null) {
			$connect_array=$this->connect_array;
			$db_host_array=isset($this->connect_array['db_hosts'])?$this->connect_array['db_hosts']:array();
			if($db_host_array){
				$db=$connect_array['db'];
				$host=$db_host_array[$db];
			}else{
				$host=$connect_array['host'];
			}

			$this->connection = new mysqli($host, $connect_array['user_name'], $connect_array['pass_word'], $connect_array['db'],$connect_array['port']);
			if (mysqli_connect_errno()) {
				echo("Database Connect Error : " . mysqli_connect_error($this->connection));
			} else {
				$this->connection->query("SET NAMES 'utf8'");
			}
		}
	}

	private function init_read_connection(){
		if ($this->has_read_db && $this->read_connection === null) {
			$connect_array=$this->connect_array;

			$db_read_host_array=isset($this->connect_array['read_db_hosts'])?$this->connect_array['read_db_hosts']:array();
			if($db_read_host_array){
				$db=$connect_array['db'];
				$host_str=$db_read_host_array[$db];
				$host_array=explode(",", $host_str);
				$num=rand(0,count($host_array)-1);
				$host=$host_array[$num];
			}
			$this->read_connection = new mysqli($host, $connect_array['user_name'], $connect_array['pass_word'], $connect_array['db'],$connect_array['port']);
			if (mysqli_connect_errno()) {
				echo("Database Connect Error : " . mysqli_connect_error($this->read_connection));
			} else {
				$this->read_connection->query("SET NAMES 'utf8'");
			}
		}
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
			$insert_id=$stmt->insert_id;
			$stmt->close();
			return $insert_id;
		}
		if($stmt!=null){
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
			$affected_rows=$stmt->affected_rows;
			$stmt->close();
			return $affected_rows;
		}
		if($stmt!=null){
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
		$affected_rows=0;
		if ($batch_params && is_array($batch_params)) {
			$this->init();
			$new_batch_params=array();
			$new_sql='';
			foreach ($batch_params as $ps) {
				$result=$this->replaceSql($sql,$ps);
				$new_batch_params[]=$result['params'];
				if(empty($new_sql)){
					$new_sql=$result['sql'];
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
					$affected_rows= $affected_rows + $stmt->affected_rows;
				}
			}
			if($stmt != null){
				$stmt->close();
			}
			if($this->connection != null){
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
			$bind_result[] = &${$field};//http://www.php.net/manual/zh/language.variables.variable.php
		}
		$this->bindResult($stmt, $bind_result);
		$result_list = array();
		$i = 0;
		while ($stmt->fetch()) {//http://cn2.php.net/manual/zh/mysqli-stmt.bind-result.php
			foreach ($fields_list as $field) {
				$result_list[$i][$field] = ${$field};
			}
			$i++;
		}
		if($stmt!=null){
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
		$result=$this->replaceSql($sql,$params);
		$transaction_read_master=false;//事务中的读操作是否读主库
		if(defined("TRANSACTION_READ_MASTER")){
			$transaction_read_master=TRANSACTION_READ_MASTER;
		}
		
		if($this->this_operation_have_transaction && $transaction_read_master){//有事务操作并且事务中select配置成操作主库,事务中select查询走主库
			$this->init();
			$stmt = $this->connection->prepare($result['sql']);
		}else{
			if($this->has_read_db && (stristr($sql, "select ") || stristr($sql, "SELECT ")) ){//有读库配置并且是 select 查询 走读库
				$this->init_read_connection();
				$stmt = $this->read_connection->prepare($result['sql']);
			}else{
				$this->init();
				$stmt = $this->connection->prepare($result['sql']);
			}
		}
		if(!$stmt){
			throw new Exception("error sql in ".$sql);
		}
		
		$params = $this->get_bind_params($result['params']);
		$this->bindParameters($stmt, $params);

		if ($stmt->execute()) {
			return $stmt;
		} else {
			throw new Exception("Error in : " . mysqli_error($this->connection));
			if($stmt!=null){
				$stmt->close();
			}
			return 0;
		}
	}

	private function get_bind_params($bind_params) {
		if ($bind_params && is_array($bind_params)) {
			ksort($bind_params);
			$param_key = "";
			foreach ($bind_params as $key => $value) {
				$type = gettype($value);
				if ($type === "integer") {
					$param_key .= "i";
				} else if ($type === "double") {
					$param_key .= "d";
				} else if ($type === "string") {
					$param_key .= "s";
				} else {
					$param_key .= "b";
				}
			}
			array_unshift($bind_params, $param_key); //在数组最前面插入一条数据
			return $bind_params;
		}
		return array();
	}

	private function bindParameters($stmt, $bind_params = array()) {
		if ($bind_params) {
			call_user_func_array(array($stmt, "bind_param"), $this->refValues($bind_params));
		}
	}

	private function bindResult($stmt, $bind_result_fields = array()) {
		call_user_func_array(array($stmt, "bind_result"), $bind_result_fields);
	}

	private function refValues($arr){
		if (strnatcmp(phpversion(),'5.3') >= 0){ //Reference is required for PHP 5.3+
			$refs = array();
			foreach($arr as $key => $value){
				$refs[$key] = &$arr[$key];
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


	private function replaceSql($sql,$object=array()){
		$matchSql=$this->iteratePropertyReplaceByArray($sql, $object);
		$sql=$matchSql['sql'];
		$map=$matchSql['match_property'];
		$params=array();
		if($object){
			foreach ($object as $key=>$value){
				if(!stripos($sql, ":".$key)){
					throw new Exception(" array key: $key not in sql:".$sql);
				}else{
					$sql=str_ireplace(":".$key, "?", $sql);
					foreach ($map as $k=>$v){
						if(strtolower($v) === strtolower("#$key#")){
							$params[$k]=$value;
							break;
						}
					}
				}
			}
		}
		$return_array=array('sql'=>$sql,'params'=>$params);
		return $return_array;
	}

	private function iteratePropertyReplaceByArray($sql,$array){
		preg_match_all("/(#)(.*?)(#)/", $sql, $match);
		if($match){
			$match=$match[0];
		}
		$matchSql=array();
		$matchSql['match_property']=$match;
		if($array){
			foreach ($array as $key=>$value){
				if(stristr($sql, $key)){
					$sql=str_ireplace("#$key#", ":$key", $sql);
				}
			}
		}
		$matchSql['sql']=$sql;
		return $matchSql;
	}

	public function begin() {
		$this->init();
		$this->this_operation_have_transaction=true;
		$this->connection->autocommit(false);//关闭本次数据库连接的自动命令提交事务模式
	}

	public function commit() {
		$this->connection->commit();//提交事务后，打开本次数据库连接的自动命令提交事务模式
		$this->this_operation_have_transaction=false;
		$this->connection->autocommit(true);
	}

	public function rollBack() {
		$this->connection->rollback();//回滚事务后，打开本次数据库连接的自动命令提交事务模式
		$this->this_operation_have_transaction=false;
		$this->connection->autocommit(true);
	}

	public function closeConnection(){
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

