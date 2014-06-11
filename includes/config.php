<?php
define("MYSQL_EXTEND", 'mysqli');//值为mysqli | mysql_pdo

define("DB_NAME", 'sooch');
define("DB_USER_NAME", 'root');
define("DB_PASSWORD", '123456');
define("DB_HOST", '127.0.0.1');
define("DB_PORT", 3306);
define("SEQUENCE_DEFAULT_STEP", 1000);//序列步长

/************sequence配置*****************/
$sequence_config_array=array();
$sequence_config_array['host']=DB_HOST;//db_host
$sequence_config_array['user_name']=DB_USER_NAME;//db username
$sequence_config_array['pass_word']=DB_PASSWORD;//db pwd
$sequence_config_array['db']=DB_NAME;//db 
$sequence_config_array['port']=DB_PORT;//db port
/*****************************/

/***************order库表配置**************/
$mysql_db_route_array=array();
$mysql_db_route_array['host']=DB_HOST;//db_host
$mysql_db_route_array['user_name']=DB_USER_NAME;//db username
$mysql_db_route_array['pass_word']=DB_PASSWORD;//db pwd
$mysql_db_route_array['port']=DB_PORT;//db port

$mysql_db_route_array['db_prefix']="tt_0000";//数据库前缀，生成类似db_order_0001、db_order_0002......db_order_1023
$mysql_db_route_array['table_prefix']="tt_order_0000";//表名前缀，生成类似order_0001、order_0002......order_1023
$mysql_db_route_array['logic_table']="order";//逻辑表名
$mysql_db_route_array['logic_column']="user_id";//分表的列，执行sql语句时，要传递此参数
$mysql_db_route_array['select_in_logic_column']="user_ids";//select in 查询时时的参数key名
$mysql_db_route_array['table_total_num']=1024;//总表数
$mysql_db_route_array['one_db_table_num']=1024;//每个库里存放的表数

$db_host_array=array();
$db_host_array['tt_0000']=DB_HOST;
$mysql_db_route_array_two['db_hosts']=$db_host_array;//每个库存放在哪个host

$db_read_host_array=array();
$db_read_host_array['tt_0000']=DB_HOST;
$mysql_db_route_array['read_db_hosts']=$db_read_host_array;//每个库存放在哪个host
/*****************************/

/***************cart库表配置**************/
$mysql_db_route_array_two=array();
$mysql_db_route_array_two['host']=DB_HOST;//db_host
$mysql_db_route_array_two['user_name']=DB_USER_NAME;//db username
$mysql_db_route_array_two['pass_word']=DB_PASSWORD;//db pwd
$mysql_db_route_array_two['port']=DB_PORT;//db port

$mysql_db_route_array_two['db_prefix']="db_cart_0000";//数据库前缀，生成类似db_order_0001、db_order_0002......db_order_1023
$mysql_db_route_array_two['table_prefix']="cart_0000";//表名前缀，生成类似order_0001、order_0002......order_1023
$mysql_db_route_array_two['logic_table']="order";//逻辑表名
$mysql_db_route_array_two['logic_column']="user_id";//分表的列，执行sql语句时，要传递此参数
$mysql_db_route_array_two['select_in_logic_column']="user_ids";//select in 查询时时的参数key名
$mysql_db_route_array_two['table_total_num']=1024;//总表数
$mysql_db_route_array_two['one_db_table_num']=128;//每个库里存放的表数

$db_host_array=array();
$db_host_array['db_cart_0000']=DB_HOST;
$db_host_array['db_cart_0001']=DB_HOST;
$db_host_array['db_cart_0002']=DB_HOST;
$db_host_array['db_cart_0003']=DB_HOST;
$db_host_array['db_cart_0004']=DB_HOST;
$db_host_array['db_cart_0005']=DB_HOST;
$db_host_array['db_cart_0006']=DB_HOST;
$db_host_array['db_cart_0007']=DB_HOST;
$mysql_db_route_array_two['db_hosts']=$db_host_array;//每个库存放在哪个host

$db_read_host_array=array();
$db_read_host_array['db_cart_0000']=DB_HOST;
$db_read_host_array['db_cart_0001']=DB_HOST;
$db_read_host_array['db_cart_0002']=DB_HOST;
$db_read_host_array['db_cart_0003']=DB_HOST;
$db_read_host_array['db_cart_0004']=DB_HOST;
$db_read_host_array['db_cart_0005']=DB_HOST;
$db_read_host_array['db_cart_0006']=DB_HOST;
$db_read_host_array['db_cart_0007']=DB_HOST;
$mysql_db_route_array_two['read_db_hosts']=$db_read_host_array;//每个读库存放在哪个host

/*****************************/
