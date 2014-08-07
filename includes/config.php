<?php
define("MYSQL_EXTEND", 'mysqli');//操作数据方式：值为 mysqli | mysql_pdo 推荐使用mysqli方式操作
define("IS_DEBUG", false);//调试模式,生产环境配置为false

define("DB_NAME", 'sooch');
define("DB_USER_NAME", 'root');
define("DB_PASSWORD", '123456');
define("DB_HOST", '127.0.0.1');
define("DB_PORT", 3306);
define("SEQUENCE_DEFAULT_STEP", 1000);//序列步长
define("TRANSACTION_READ_MASTER", true);//事务中 select 是否读主库

/************sequence表及未分库的表配置*****************************************************************************************************/
$default_config_array=array();
$default_config_array['host']=DB_HOST;//db_host
$default_config_array['user_name']=DB_USER_NAME;//db username
$default_config_array['pass_word']=DB_PASSWORD;//db pwd
$default_config_array['db']=DB_NAME;//db 
$default_config_array['port']=DB_PORT;//db port
/***************************************************************************************************************************************/

/***************order库表配置  单库*********************************************************************************************************/
$mysql_db_route_array=array();
$mysql_db_route_array['host']=DB_HOST;//db_host
$mysql_db_route_array['user_name']=DB_USER_NAME;//db username
$mysql_db_route_array['pass_word']=DB_PASSWORD;//db pwd
$mysql_db_route_array['port']=DB_PORT;//db port

$mysql_db_route_array['db_prefix']="mmall_0000";//单库可以配置为 mmall_0000 也可配置成实际数据库名mmall
$mysql_db_route_array['table_prefix']="tt_order_0000";//表名前缀，生成类似order_0000、order_0001......order_1023
$mysql_db_route_array['logic_table']="order";//逻辑表名
$mysql_db_route_array['logic_column']="user_id";//分表的列，执行sql语句时，要传递此参数
$mysql_db_route_array['select_in_logic_column']="user_ids";//select in 查询时时的参数key名
$mysql_db_route_array['table_total_num']=1024;//总表数
$mysql_db_route_array['one_db_table_num']=1024;//每个库里存放的表数

$db_host_array=array();
$db_host_array['mmall']=DB_HOST;
$mysql_db_route_array['db_hosts']=$db_host_array;//每个实库存放在哪个host

$db_read_host_array=array();
$db_read_host_array['mmall']=DB_HOST;
$mysql_db_route_array['read_db_hosts']=$db_read_host_array;//每个读库存放在哪个host,根据key为‘read_db_hosts’判断是否有读写分离配置
/***************************************************************************************************************************************/

/***************order库表配置(多库)********************************************************************************************************/
$mysql_db_route_array_two=array();
$mysql_db_route_array_two['host']=DB_HOST;//db_host
$mysql_db_route_array_two['user_name']=DB_USER_NAME;//db username
$mysql_db_route_array_two['pass_word']=DB_PASSWORD;//db pwd
$mysql_db_route_array_two['port']=DB_PORT;//db port

$mysql_db_route_array_two['db_prefix']="mmall_0000";//数据库前缀，生成类似db_order_0000、db_order_0001......db_order_1023
$mysql_db_route_array_two['table_prefix']="order_0000";//表名前缀，生成类似order_0000、order_0001......order_1023
$mysql_db_route_array_two['logic_table']="order";//逻辑表名
$mysql_db_route_array_two['logic_column']="user_id";//分表的列，执行sql语句时，要传递此参数
$mysql_db_route_array_two['select_in_logic_column']="user_ids";//select in 查询时时的参数key名
$mysql_db_route_array_two['table_total_num']=1024;//总表数
$mysql_db_route_array_two['one_db_table_num']=128;//每个库里存放的表数

$db_host_array=array();
$db_host_array['mmall_0000']=DB_HOST;
$db_host_array['mmall_0001']=DB_HOST;
$db_host_array['mmall_0002']=DB_HOST;
$db_host_array['mmall_0003']=DB_HOST;
$db_host_array['mmall_0004']=DB_HOST;
$db_host_array['mmall_0005']=DB_HOST;
$db_host_array['mmall_0006']=DB_HOST;
$db_host_array['mmall_0007']=DB_HOST;
$mysql_db_route_array_two['db_hosts']=$db_host_array;//每个读库存放在哪个host

$db_read_host_array=array();
$db_read_host_array['mmall_0000']=DB_HOST.",".DB_HOST;//多个读库可以使用逗号分隔，系统随机读其中一台，如果配置了读写分离
$db_read_host_array['mmall_0001']=DB_HOST;
$db_read_host_array['mmall_0002']=DB_HOST;
$db_read_host_array['mmall_0003']=DB_HOST;
$db_read_host_array['mmall_0004']=DB_HOST;
$db_read_host_array['mmall_0005']=DB_HOST;
$db_read_host_array['mmall_0006']=DB_HOST;
$db_read_host_array['mmall_0007']=DB_HOST;
$mysql_db_route_array_two['read_db_hosts']=$db_read_host_array;//每个读库存放在哪个host,根据key为‘read_db_hosts’判断是否有读写分离配置
/**************************************************************************************************************************************/

/***************order_goods库表配置(多库)*************************************************************************************************/
$mysql_db_route_goods_array=array();
$mysql_db_route_goods_array['host']=DB_HOST;//db_host
$mysql_db_route_goods_array['user_name']=DB_USER_NAME;//db username
$mysql_db_route_goods_array['pass_word']=DB_PASSWORD;//db pwd
$mysql_db_route_goods_array['port']=DB_PORT;//db port

$mysql_db_route_goods_array['db_prefix']="mmall_0000";//数据库前缀，生成类似mmall_0000、mmall_0001...mmall_1023
$mysql_db_route_goods_array['table_prefix']="order_goods_0000";//表名前缀，生成类似order_goods_0000、order_goods_0001...order_goods_1023
$mysql_db_route_goods_array['logic_table']="order_goods";//逻辑表名
$mysql_db_route_goods_array['logic_column']="user_id";//分表的列，执行sql语句时，要传递此参数
$mysql_db_route_goods_array['select_in_logic_column']="user_ids";//select in 查询时时的参数key名
$mysql_db_route_goods_array['table_total_num']=1024;//总表数
$mysql_db_route_goods_array['one_db_table_num']=128;//每个库里存放的表数

$db_goods_host_array=array();
$db_goods_host_array['mmall_0000']=DB_HOST;
$db_goods_host_array['mmall_0001']=DB_HOST;
$db_goods_host_array['mmall_0002']=DB_HOST;
$db_goods_host_array['mmall_0003']=DB_HOST;
$db_goods_host_array['mmall_0004']=DB_HOST;
$db_goods_host_array['mmall_0005']=DB_HOST;
$db_goods_host_array['mmall_0006']=DB_HOST;
$db_goods_host_array['mmall_0007']=DB_HOST;
$mysql_db_route_goods_array['db_hosts']=$db_goods_host_array;//每个库存放在哪个host

$db_goods_read_host_array=array();
$db_goods_read_host_array['mmall_0000']=DB_HOST.",".DB_HOST;
$db_goods_read_host_array['mmall_0001']=DB_HOST;
$db_goods_read_host_array['mmall_0002']=DB_HOST;
$db_goods_read_host_array['mmall_0003']=DB_HOST;
$db_goods_read_host_array['mmall_0004']=DB_HOST;
$db_goods_read_host_array['mmall_0005']=DB_HOST;
$db_goods_read_host_array['mmall_0006']=DB_HOST;
$db_goods_read_host_array['mmall_0007']=DB_HOST;
$mysql_db_route_goods_array['read_db_hosts']=$db_goods_read_host_array;//每个读库存放在哪个host,根据key为‘read_db_hosts’判断是否有读写分离配置
/******************************************************************************************************************************************/
