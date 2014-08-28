<?php
/**
 * Created by JetBrains PhpStorm.
 * User: longhaisheng
 * Date: 12-9-22
 * Time: 上午7:30
 * To change this template use File | Settings | File Templates.
 */
class cls_mysql {

    private $mysqlLink;

    private $config_array;

    public function __construct($db_config_array = array()) {
        $this->config_array = $db_config_array;
    }

    /**
     * 执行select 语名查询
     * @param string $sql "select from user where add_id=10"
     * @return array 返回查询的数组
     */
    public function getAll($sql, $fetch_assoc = true) {
        $result = $this->query($sql);
        if ($result) {
            $return_arrays = array();
            if ($fetch_assoc) {
                while ($row = mysql_fetch_assoc($result)) {
                    $return_arrays[] = $row;
                }
            } else {
                while ($row = mysql_fetch_array($result, MYSQL_NUM)) {
                    $return_arrays[] = $row;
                }
            }
            mysql_free_result($result);
            return $return_arrays;
        }
        echo "<font color=red>ERROR SQL FOR:</font>" . $sql;
        return array();
    }

    /**
     * 返回一行数据
     * @param string $sql  "select from user where id=1"
     * @return array
     */
    public function getRow($sql) {
        $result = $this->getAll($sql);
        if ($result) {
            return $result[0];
        }
        return array();
    }

    /**
     * 返回 一列
     * @param string $sql "select count(*) from user where id>10 "
     * @return one column
     */
    public function getColumn($sql) {
        $result = $this->getAll($sql, false);
        if ($result) {
            return $result[0][0];
        }
        return 0;
    }

    /**
     * 执行update|delete语句
     * @param string $sql  "update user set name=null;" |"delete from user where id=1 "
     * @param boolean $num_row
     * @return int 返回受影响的行数
     */
    public function execute($sql, $num_row = true) {
        if ($sql) {
            $this->query($sql);
            if ($num_row) {
                $num = mysql_affected_rows($this->mysqlLink);
                return $num;
            }
        }
        return 0;
    }

    /**
     * 执行insert语句
     * @param string $sql
     * @param int $insert_id
     * @return int 返回插入的主键值
     */
    public function insert($sql, $insert_id = true) {
        if ($sql) {
            $this->query($sql);
            if ($insert_id) {
                $id = mysql_insert_id($this->mysqlLink);
                return $id;
            }
        }
        return 0;
    }

    /**
     * 批量执行insert|update|delete语句
     * @param array $batch_sql array("update user set name='lhs1'","update user set name='lhs2'")
     * @param int $batch_num
     */
    public function batchExecute($batch_sql, $batch_num = 20) {
        if ($batch_sql && is_array($batch_sql)) {
            $sql_list = array_chunk($batch_sql, $batch_num);
            $i = 0;
            foreach ($sql_list as $params) {
                $this->begin();
                foreach ($params as $sql) {
                    if ($this->query($sql)) {
                        $i++;
                    }
                }
                $this->commit();
            }
            return $i;
        }
        return 0;
    }

    private function query($sql) {
        if ($this->mysqlLink == null) {
            $db_config_list = $this->config_array;
            $server=$db_config_list['host'];
            if(isset($db_config_list['port'])){
            	$server=$db_config_list['host'].':'.$db_config_list['port'];
            }
            $this->mysqlLink = mysql_connect($server, $db_config_list['user_name'], $db_config_list['pass_word'], true);
            mysql_select_db($db_config_list['db'], $this->mysqlLink);
            mysql_query("SET character_set_connection=utf8, character_set_results=utf8, character_set_client=binary", $this->mysqlLink);
            //mysql_query("SET NAMES 'utf8'",$this->mysql_link);
        }
        return mysql_query($sql, $this->mysqlLink);
    }

    public function begin() {
        $this->query("BEGIN");
    }

    public function commit() {
        $this->query("COMMIT");
    }

    public function rollBack() {
        $this->query("ROLLBACK");
    }

    public function close() {
        if ($this->mysqlLink != null) {
            mysql_close($this->mysqlLink);
            $this->mysqlLink = null;
        }
    }

    /**
     * @param $selectSql "select id,name,code FROM city where id>10"
     * @param string $url "getCity-{page}.php?id=10"
     * @param int $pageNo 当前页
     * @param int $pageSize 每页记录数
     * @return PageUtil 分页对象
     */
    public function queryWithPage($selectSql, $url = '', $pageNo = 1, $pageSize = 20) {
        $sql = trim($selectSql);
        $start = (intval($pageNo) - 1) * intval($pageSize);
        $start = $start > 0 ? $start : 0;
        $sql = $sql . " limit $start,$pageSize";
        $dataList = $this->getAll($sql);
        $newStr = substr($sql, stripos($sql, "FROM"));
        $orderLength = stripos($newStr, "ORDER ");
        if ($orderLength) {
            $countSql = "SELECT count(1) " . substr($newStr, 0, $orderLength);
        } else {
            $countSql = "SELECT count(1) " . $newStr;
        }
        $totalCount = $this->getColumn($countSql);
        $pageUtil = new cls_page($url, $totalCount, $pageNo, $pageSize);
        $pageUtil->setList($dataList);
        return $pageUtil;
    }

    public function __destruct() {
        $this->close();
    }

}
