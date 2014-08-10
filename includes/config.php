<?php
define("MYSQL_EXTEND", 'mysqli'); //操作数据方式：值为 mysqli | mysql_pdo 推荐使用mysqli方式操作
define("IS_DEBUG", false); //调试模式,生产环境配置为false

define("DB_NAME", 'mmall');
define("DB_USER_NAME", 'root');
define("DB_PASSWORD", '123456');
define("DB_HOST", '127.0.0.1');
define("DB_PORT", 3306);
define("SEQUENCE_DEFAULT_STEP", 1000); //序列步长
define("TRANSACTION_READ_MASTER", true); //事务中 select 是否读主库


/************sequence表及未分库的表配置*****************************************************************************************************/
$default_config_array = array();
$default_config_array['host'] = DB_HOST; //db_host
$default_config_array['user_name'] = DB_USER_NAME; //db username
$default_config_array['pass_word'] = DB_PASSWORD; //db pwd
$default_config_array['db'] = DB_NAME; //db
$default_config_array['port'] = DB_PORT; //db port
/***************************************************************************************************************************************/

/***************order库表配置 (单库多表)*********************************************************************************************************/
$order_dbroute_single_config = array();
$order_dbroute_single_config['host'] = DB_HOST; //db_host
$order_dbroute_single_config['user_name'] = DB_USER_NAME; //db username
$order_dbroute_single_config['pass_word'] = DB_PASSWORD; //db pwd
$order_dbroute_single_config['port'] = DB_PORT; //db port

$order_dbroute_single_config['db_prefix'] = DB_NAME; //单库可以配置为 mmall_0000 也可配置成实际数据库名mmall,见意配置为实际数据库名
$order_dbroute_single_config['table_prefix'] = "order_0000"; //表名前缀，生成类似order_0000、order_0001......order_1023
$order_dbroute_single_config['logic_table'] = "sc_order"; //逻辑表名不能为sql关键字
$order_dbroute_single_config['logic_column'] = "user_id"; //分表的列，执行sql语句时，要传递此参数
$order_dbroute_single_config['logic_column_field_type'] = "int"; //分表的列值的类型
$order_dbroute_single_config['select_in_logic_column'] = "user_ids"; //select in 查询时时的参数key名
$order_dbroute_single_config['table_total_num'] = 64; //总表数
$order_dbroute_single_config['one_db_table_num'] = 64; //每个库里存放的表数

$master_db_host_array = array();
$master_db_host_array['mmall'] = DB_HOST;
$order_dbroute_single_config['db_hosts'] = $master_db_host_array; //每个主库存放在哪个host

$slave_read_host_array = array();
$slave_read_host_array['mmall'] = DB_HOST . "," . DB_HOST;
$order_dbroute_single_config['read_db_hosts'] = $slave_read_host_array; //每个读库存放在哪个host,根据key为‘read_db_hosts’判断是否有读写分离配置
/***************************************************************************************************************************************/

/***************order_goods库表配置(单库多表)*************************************************************************************************/
$order_goods_dbroute_single_config = array();
$order_goods_dbroute_single_config['host'] = DB_HOST; //db_host
$order_goods_dbroute_single_config['user_name'] = DB_USER_NAME; //db username
$order_goods_dbroute_single_config['pass_word'] = DB_PASSWORD; //db pwd
$order_goods_dbroute_single_config['port'] = DB_PORT; //db port

$order_goods_dbroute_single_config['db_prefix'] = DB_NAME; //数据库前缀，生成类似mmall_0000、mmall_0001...mmall_1023
$order_goods_dbroute_single_config['table_prefix'] = "order_goods_0000"; //表名前缀，生成类似order_goods_0000、order_goods_0001...order_goods_1023
$order_goods_dbroute_single_config['logic_table'] = "sc_order_goods"; //逻辑表名不能为sql关键字
$order_goods_dbroute_single_config['logic_column'] = "user_id"; //分表的列，执行sql语句时，要传递此参数
$order_goods_dbroute_single_config['logic_column_field_type'] = "int"; //分表的列值的类型
$order_goods_dbroute_single_config['select_in_logic_column'] = "user_ids"; //select in 查询时时的参数key名
$order_goods_dbroute_single_config['table_total_num'] = 64; //总表数
$order_goods_dbroute_single_config['one_db_table_num'] = 64; //每个库里存放的表数

$master_order_goods_dbs = array();
$master_order_goods_dbs['mmall'] = DB_HOST;
$order_goods_dbroute_single_config['db_hosts'] = $master_order_goods_dbs; //每个库存放在哪个host

$slave_order_goods_dbs = array();
$slave_order_goods_dbs['mmall'] = DB_HOST . "," . DB_HOST;
$order_goods_dbroute_single_config['read_db_hosts'] = $slave_order_goods_dbs; //每个读库存放在哪个host,根据key为‘read_db_hosts’判断是否有读写分离配置
/******************************************************************************************************************************************/

/***************用户库表配置(多库多表)********************************************************************************************************/
$user_multiple_dbroute_config = array();
$user_multiple_dbroute_config['host'] = DB_HOST; //db_host
$user_multiple_dbroute_config['user_name'] = DB_USER_NAME; //db username
$user_multiple_dbroute_config['pass_word'] = DB_PASSWORD; //db pwd
$user_multiple_dbroute_config['port'] = DB_PORT; //db port

$user_multiple_dbroute_config['db_prefix'] = "mmall_0000"; //数据库前缀，生成类似mmall__0000、mmall__0001......mmall__1023
$user_multiple_dbroute_config['table_prefix'] = "user_0000"; //表名前缀，生成类似user_0000、user_0001......user_1023
$user_multiple_dbroute_config['logic_table'] = "sc_user"; //逻辑表名不能为sql关键字
$user_multiple_dbroute_config['logic_column'] = "user_name"; //分表的列，执行sql语句时，要传递此参数
$user_multiple_dbroute_config['logic_column_field_type'] = "string"; //分表的列值的类型
$user_multiple_dbroute_config['select_in_logic_column'] = "user_names"; //select in 查询时时的参数key名
$user_multiple_dbroute_config['table_total_num'] = 64; //总表数
$user_multiple_dbroute_config['one_db_table_num'] = 16; //每个库里存放的表数

$master_customer_multiple_dbs = array();
$master_customer_multiple_dbs['mmall_0000'] = DB_HOST;
$master_customer_multiple_dbs['mmall_0001'] = DB_HOST;
$master_customer_multiple_dbs['mmall_0002'] = DB_HOST;
$master_customer_multiple_dbs['mmall_0003'] = DB_HOST;
$user_multiple_dbroute_config['db_hosts'] = $master_customer_multiple_dbs; //每个读库存放在哪个host

$slave_customer_multiple_dbs = array();
$slave_customer_multiple_dbs['mmall_0000'] = DB_HOST . "," . DB_HOST; //多个读库可以使用逗号分隔，系统随机读其中一台，如果配置了读写分离
$slave_customer_multiple_dbs['mmall_0001'] = DB_HOST . "," . DB_HOST;
$slave_customer_multiple_dbs['mmall_0002'] = DB_HOST . "," . DB_HOST;
$slave_customer_multiple_dbs['mmall_0003'] = DB_HOST . "," . DB_HOST;
$user_multiple_dbroute_config['read_db_hosts'] = $slave_customer_multiple_dbs; //每个读库存放在哪个host,根据key为‘read_db_hosts’判断是否有读写分离配置
/**************************************************************************************************************************************/

/***************用户退款库表配置(多库)*************************************************************************************************/
$sc_refund_multiple_dbroute_config = array();
$sc_refund_multiple_dbroute_config['host'] = DB_HOST; //db_host
$sc_refund_multiple_dbroute_config['user_name'] = DB_USER_NAME; //db username
$sc_refund_multiple_dbroute_config['pass_word'] = DB_PASSWORD; //db pwd
$sc_refund_multiple_dbroute_config['port'] = DB_PORT; //db port

$sc_refund_multiple_dbroute_config['db_prefix'] = "sc_refund_0000"; //数据库前缀，生成类似sc_refund_0000、sc_refund_0001...sc_refund_1023
$sc_refund_multiple_dbroute_config['table_prefix'] = "refund_0000"; //表名前缀，生成类似refund_0000、refund_0001...refund_1023
$sc_refund_multiple_dbroute_config['logic_table'] = "sc_refund"; //逻辑表名不能为sql关键字
$sc_refund_multiple_dbroute_config['logic_column'] = "user_id"; //分表的列，执行sql语句时，要传递此参数
$sc_refund_multiple_dbroute_config['logic_column_field_type'] = "int"; //分表的列值的类型
$sc_refund_multiple_dbroute_config['select_in_logic_column'] = "user_ids"; //select in 查询时时的参数key名
$sc_refund_multiple_dbroute_config['table_total_num'] = 64; //总表数
$sc_refund_multiple_dbroute_config['one_db_table_num'] = 16; //每个库里存放的表数

$db_refund_host_array = array();
$db_refund_host_array['sc_refund_0000'] = DB_HOST;
$db_refund_host_array['sc_refund_0001'] = DB_HOST;
$db_refund_host_array['sc_refund_0002'] = DB_HOST;
$db_refund_host_array['sc_refund_0003'] = DB_HOST;
$sc_refund_multiple_dbroute_config['db_hosts'] = $db_refund_host_array; //每个库存放在哪个host

$db_refund_read_host_array = array();
$db_refund_read_host_array['sc_refund_0000'] = DB_HOST . "," . DB_HOST;
$db_refund_read_host_array['sc_refund_0001'] = DB_HOST . "," . DB_HOST;
$db_refund_read_host_array['sc_refund_0002'] = DB_HOST . "," . DB_HOST;
$db_refund_read_host_array['sc_refund_0003'] = DB_HOST . "," . DB_HOST;
$sc_refund_multiple_dbroute_config['read_db_hosts'] = $db_refund_read_host_array; //每个读库存放在哪个host,根据key为‘read_db_hosts’判断是否有读写分离配置
/******************************************************************************************************************************************/

/***************用户退款详情库表配置(多库)*************************************************************************************************/
$sc_refund_info_multiple_dbroute_config = array();
$sc_refund_info_multiple_dbroute_config['host'] = DB_HOST; //db_host
$sc_refund_info_multiple_dbroute_config['user_name'] = DB_USER_NAME; //db username
$sc_refund_info_multiple_dbroute_config['pass_word'] = DB_PASSWORD; //db pwd
$sc_refund_info_multiple_dbroute_config['port'] = DB_PORT; //db port

$sc_refund_info_multiple_dbroute_config['db_prefix'] = "sc_refund_0000"; //数据库前缀，生成类似sc_refund_0000、sc_refund_0001...sc_refund_1023
$sc_refund_info_multiple_dbroute_config['table_prefix'] = "refund_info_0000"; //表名前缀，生成类似refund_info_0000、refund_info_0001...refund_info_1023
$sc_refund_info_multiple_dbroute_config['logic_table'] = "sc_refund_info"; //逻辑表名不能为sql关键字
$sc_refund_info_multiple_dbroute_config['logic_column'] = "user_id"; //分表的列，执行sql语句时，要传递此参数
$sc_refund_info_multiple_dbroute_config['logic_column_field_type'] = "int"; //分表的列值的类型
$sc_refund_info_multiple_dbroute_config['select_in_logic_column'] = "user_ids"; //select in 查询时时的参数key名
$sc_refund_info_multiple_dbroute_config['table_total_num'] = 64; //总表数
$sc_refund_info_multiple_dbroute_config['one_db_table_num'] = 16; //每个库里存放的表数

$db_refund_info_host_array = array();
$db_refund_info_host_array['sc_refund_0000'] = DB_HOST;
$db_refund_info_host_array['sc_refund_0001'] = DB_HOST;
$db_refund_info_host_array['sc_refund_0002'] = DB_HOST;
$db_refund_info_host_array['sc_refund_0003'] = DB_HOST;
$sc_refund_info_multiple_dbroute_config['db_hosts'] = $db_refund_info_host_array; //每个库存放在哪个host

$db_refund_info_read_host_array = array();
$db_refund_info_read_host_array['sc_refund_0000'] = DB_HOST . "," . DB_HOST;
$db_refund_info_read_host_array['sc_refund_0001'] = DB_HOST . "," . DB_HOST;
$db_refund_info_read_host_array['sc_refund_0002'] = DB_HOST . "," . DB_HOST;
$db_refund_info_read_host_array['sc_refund_0003'] = DB_HOST . "," . DB_HOST;
$sc_refund_info_multiple_dbroute_config['read_db_hosts'] = $db_refund_info_read_host_array;//每个读库存放在哪个host,根据key为‘read_db_hosts’判断是否有读写分离配置
/******************************************************************************************************************************************/

