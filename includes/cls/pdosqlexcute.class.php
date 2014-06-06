<?php

class cls_pdosqlexcute implements cls_idb
{
    private $connection;

    private $read_connection;

    private $connect_array;

    private $has_read_db = false;

    public function __construct($db_name)
    {
        if (!$this->connect_array) {
            global $mysql_db_route_array;
            $this->connect_array = $mysql_db_route_array;
            $this->connect_array['db'] = $db_name;
        }
        if (!$this->has_read_db) {
            $this->has_read_db = isset($this->connect_array['read_host']);
        }
    }

    public function getAll($sql, $params = array())
    {
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetchAll();
        $stmt->closeCursor();

        return $result;
    }

    /**
     * 执行SQL
     * @param $sql
     * @param array $params
     * @return int|mixed
     */
    private function query($sql, $params = array())
    {
        $condition = $this->format($sql, $params);
        $stmt = $this->prepare($condition['sql']);
        try {
            $stmt->execute($condition['params']);

            return $stmt;
        } catch (PDOException $e) {
            echo "Error in : " . $e->getMessage();
            if ($stmt) {
                $stmt->closeCursor();
            }

            return null;
        }
    }

    /**
     * 格式化SQL和参数
     * @param $sql
     * @param array $params
     * @return array
     */
    private function format($sql, $params = array())
    {
        $sql = $this->formatSql($sql);
        $params = $this->formatParams($params);
        $condition = array('sql' => $sql, 'params' => $params);

        return $condition;
    }

    private function formatSql($sql)
    {
        return preg_replace('/#(\w+)#/', ':$1', $sql);
    }

    private function formatParams(&$params = array())
    {
        $result = array();
        foreach ($params as $k => $v) {
            $result[':' . $k] = $v;
        }
        $params = $result;

        return $params;
    }

    /**
     * 预处理SQL语句
     * @param $sql
     * @return mixed
     */
    private function prepare($sql)
    {
        // 判断SQL语句是否为读
        if ($this->has_read_db && preg_match('/^select\s/i', $sql)) {
            $db = $this->getReadConnection();
        } else {
            $db = $this->getMasterConnection();
        }
        $stmt = $db->prepare($sql);

        return $stmt;
    }

    /**
     * 初始化读库连接
     */
    private function getReadConnection()
    {
        if (!$this->read_connection) {
            $connect_array = $this->connect_array;
            // 载入读库配置
            $connect_array['host'] = $this->connect_array['read_host'];

            return $this->read_connection = $this->getDbConnection($connect_array);
        }

        return $this->read_connection;
    }

    /**
     * 获取PDO连接对象
     * @param $connect_array
     * @return PDO
     */
    private function getDbConnection($connect_array)
    {
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
            echo "Database Connect Error : " . $e->getMessage();
        }
    }

    /**
     * 初始化主库连接
     */
    private function getMasterConnection()
    {
        if (!$this->connection) {
            return $this->connection = $this->getDbConnection($this->connect_array);
        }

        return $this->connection;
    }

    public function __destruct()
    {
        $this->closeConnection();
    }

    /**
     * 释放PDO连接
     */
    public function closeConnection()
    {
        $this->connection = null;
        $this->read_connection = null;
    }

    /**
     * @param $sql "delete from order where id=#id# and user_id=#user_id#"
     * @param array $params array('id'=>123,'user_id'=>10)
     * @param bool $return_affected_rows
     * @return int
     */
    public function delete($sql, $params = array(), $return_affected_rows = true)
    {
        return $this->update($sql, $params, $return_affected_rows);
    }

    /**
     * @param $sql "update order set order_num=#order_num#,order_sn=#order_sn# where id=#id# and user_id=#user_id#"
     * @param array $params array('order_num'=>3,'order_sn'=>'sn123456','id'=>1,'user_id'=>10)
     * @param bool $return_affected_rows
     * @return int
     */
    public function update($sql, $params = array(), $return_affected_rows = true)
    {
        $stmt = $this->query($sql, $params);
        $rowCount = 0;
        if ($return_affected_rows) {
            $rowCount = $stmt->rowCount();
        }
        $stmt->closeCursor();

        return $rowCount;
    }

    /**
     * @param $sql "insert order(order_id,order_sn,user_id) values (#order_id#,#order_sn#,#user_id#)"
     * @param array $batch_params (array(array('order_id'=>1,'order_sn'=>'password1','user_id'=>10),array('order_id'=>2,'order_sn'=>'password2','user_id'=>10)......))
     * @param int $batch_num 不见意超过50,默认为20
     * @internal param array $logic_params 分表物理列名数组，如根据user_id分表的，此可传 array('user_id'=>10)
     * @return int
     */
    public function batchExecutes($sql, $batch_params = array(), $batch_num = 20)
    {
        $affectedRows = 0;

        // 格式化SQL和参数
        $sql = $this->formatSql($sql);
        array_walk($batch_params, array($this, 'formatParams'));

        // 预处理SQL
        $stmt = $this->prepare($sql);

        // 参数分块每块20条
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

    public function begin($params = array())
    {
        $this->getMasterConnection();
        $this->connection->setAttribute(PDO::ATTR_AUTOCOMMIT, 0);

        return $this->connection->beginTransaction();
    }

    public function commit($params = array())
    {
        $return = $this->connection->commit();
        $this->connection->setAttribute(PDO::ATTR_AUTOCOMMIT, 1);

        return $return;
    }

    /**
     * 插入数据
     * @param $sql
     * @param array $params
     * @param bool $return_insert_id
     * @return int
     */
    public function insert($sql, $params = array(), $return_insert_id = true)
    {
        $stmt = $this->query($sql, $params);
        if (!$stmt) {
            return;
        }
        $insertId = 0;
        if ($return_insert_id) {
            $insertId = $stmt->lastInsertId;
        }
        $stmt->closeCursor();

        return $insertId;
    }

    /**
     * @param $sql "select  order_id,order_sn from order where user_id=#user_id# "
     * @param array $params
     * @internal param array $bind_params array('user_id'=>10)
     * @return array
     */
    public function getRow($sql, $params = array())
    {
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
    public function getOne($sql, $params = array())
    {
        return $this->getColumn($sql, $params);
    }

    /**
     * @param $sql "select count(1) as count_num from order where user_id=#user_id# "
     * @param array $params
     * @internal param array $bind_params array('user_id'=>100)
     * @return int
     */
    public function getColumn($sql, $params = array())
    {
        $stmt = $this->query($sql, $params);
        $column = $stmt->fetchColumn();
        $stmt->closeCursor();

        return $column;
    }

    public function rollBack($params = array())
    {
        $return = $this->connection->rollBack();
        $this->connection->setAttribute(PDO::ATTR_AUTOCOMMIT, 1);

        return $return;
    }
}