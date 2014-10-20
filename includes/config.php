<?php
define("MYSQL_EXTEND", 'mysqli'); //操作数据方式：值为 mysqli | mysql_pdo 推荐使用mysqli方式操作
define("IS_DEBUG", false); //调试模式,生产环境配置为false

define("DB_NAME", 'mmall');
define("DB_USER_NAME", 'root');
define("DB_PASSWORD", '123456');
define("DB_HOST", '127.0.0.1');
define("DB_PORT", 3306);
define("SEQUENCE_DEFAULT_STEP", 1000); //序列步长
define("TRANSACTION_READ_MASTER", true); //事务中 select查询 是否读主库
define("APP_NAME", 'mmall');//应用名

/************sequence表及未分库的表配置*****************************************************************************************************/
$default_config_array = array();
$default_config_array['host'] = DB_HOST; //db_host
$default_config_array['user_name'] = DB_USER_NAME; //db username
$default_config_array['pass_word'] = DB_PASSWORD; //db pwd
$default_config_array['db'] = DB_NAME; //db
$default_config_array['port'] = DB_PORT; //db port
$slave_default_config_dbs = array();
$slave_default_config_dbs[DB_NAME] = DB_HOST . "," . DB_HOST; 
$default_config_array['read_db_hosts'] = $slave_default_config_dbs; 
$default_config_array['read_db_arithmetic'] = 'rand'; //读库算法 roll:轮询 or rand:随机
/***************************************************************************************************************************************/

/***************order库表配置 (单库多表)*********************************************************************************************************/
$sc_order_dbroute_single_config = array();
$sc_order_dbroute_single_config['host'] = DB_HOST; //db_host
$sc_order_dbroute_single_config['user_name'] = DB_USER_NAME; //db username
$sc_order_dbroute_single_config['pass_word'] = DB_PASSWORD; //db pwd
$sc_order_dbroute_single_config['port'] = DB_PORT; //db port

$sc_order_dbroute_single_config['db_prefix'] = DB_NAME; //单库可以配置为 mmall_0000 也可配置成实际数据库名mmall,见意配置为实际数据库名如：mmall
$sc_order_dbroute_single_config['table_prefix'] = "order_0000"; //表名前缀，生成类似order_0000、order_0001......order_1023
$sc_order_dbroute_single_config['logic_table'] = "sc_order"; //逻辑表名不能为sql关键字
$sc_order_dbroute_single_config['table_logic_column'] = "user_id"; //分表的列，执行sql语句时，要传递此参数
$sc_order_dbroute_single_config['table_logic_column_type'] = "int"; //分表的列值的类型
$sc_order_dbroute_single_config['select_in_logic_column'] = "user_ids"; //select in 查询时时的参数key名
$sc_order_dbroute_single_config['table_total_num'] = 64; //总表数
$sc_order_dbroute_single_config['one_db_table_num'] = 64; //每个库里存放的表数

$master_db_host_array = array();
$master_db_host_array['mmall'] = DB_HOST; //key为数据库名，value为数据库所在的host,此value只能配置一个IP
$sc_order_dbroute_single_config['db_hosts'] = $master_db_host_array; //每个主库存放在哪个host

$slave_read_host_array = array();
$slave_read_host_array['mmall'] = DB_HOST . "," . DB_HOST; //key为数据库名，value为slave库所在的host列表，多台用英文半角逗号分隔开，系统随机读其中一台，如果下面read_db_hosts配置了
$sc_order_dbroute_single_config['read_db_hosts'] = $slave_read_host_array; //dbroute根据key为‘read_db_hosts’是否设置了值 判断是否有读写分离配置
/***************************************************************************************************************************************/
//
///***************order_goods库表配置(单库多表)*************************************************************************************************/
//$sc_order_goods_dbroute_single_config = array();
//$sc_order_goods_dbroute_single_config['host'] = DB_HOST; //db_host
//$sc_order_goods_dbroute_single_config['user_name'] = DB_USER_NAME; //db username
//$sc_order_goods_dbroute_single_config['pass_word'] = DB_PASSWORD; //db pwd
//$sc_order_goods_dbroute_single_config['port'] = DB_PORT; //db port
//
//$sc_order_goods_dbroute_single_config['db_prefix'] = DB_NAME; //数据库前缀，生成类似mmall_0000、mmall_0001...mmall_1023
//$sc_order_goods_dbroute_single_config['table_prefix'] = "order_goods_0000"; //表名前缀，生成类似order_goods_0000、order_goods_0001...order_goods_1023
//$sc_order_goods_dbroute_single_config['logic_table'] = "sc_order_goods"; //逻辑表名不能为sql关键字
//$sc_order_goods_dbroute_single_config['table_logic_column'] = "user_id"; //分表的列，执行sql语句时，要传递此参数
//$sc_order_goods_dbroute_single_config['table_logic_column_type'] = "int"; //分表的列值的类型
//$sc_order_goods_dbroute_single_config['select_in_logic_column'] = "user_ids"; //select in 查询时时的参数key名
//$sc_order_goods_dbroute_single_config['table_total_num'] = 64; //总表数
//$sc_order_goods_dbroute_single_config['one_db_table_num'] = 64; //每个库里存放的表数
//
//$master_sc_order_goods_dbs = array();
//$master_sc_order_goods_dbs['mmall'] = DB_HOST; //key为数据库名，value为主库所在的host，此value只能配置一个IP
//$sc_order_goods_dbroute_single_config['db_hosts'] = $master_sc_order_goods_dbs; //每个主库存放在哪个host
//
//$slave_sc_order_goods_dbs = array();
//$slave_sc_order_goods_dbs['mmall'] = DB_HOST . "," . DB_HOST; //key为数据库名，value为slave库所在的host列表，多台用英文半角逗号分隔开，系统随机读其中一台，如果下面read_db_hosts配置了
//$sc_order_goods_dbroute_single_config['read_db_hosts'] = $slave_sc_order_goods_dbs; //dbroute根据key为‘read_db_hosts’是否设置了值 判断是否有读写分离配置
///******************************************************************************************************************************************/
//
///***************用户库表配置(多库多表)********************************************************************************************************/
//$user_multiple_dbroute_config = array();
//$user_multiple_dbroute_config['host'] = DB_HOST; //db_host
//$user_multiple_dbroute_config['user_name'] = DB_USER_NAME; //db username
//$user_multiple_dbroute_config['pass_word'] = DB_PASSWORD; //db pwd
//$user_multiple_dbroute_config['port'] = DB_PORT; //db port
//
//$user_multiple_dbroute_config['db_prefix'] = "mmall_0000"; //数据库前缀，生成类似mmall_0000、mmall_0001......mmall_1023
//$user_multiple_dbroute_config['table_prefix'] = "user_0000"; //表名前缀，生成类似user_0000、user_0001......user_1023
//$user_multiple_dbroute_config['logic_table'] = "sc_user"; //逻辑表名不能为sql关键字
//$user_multiple_dbroute_config['table_logic_column'] = "user_name"; //分表的列，执行sql语句时，要传递此参数
//$user_multiple_dbroute_config['table_logic_column_type'] = "string"; //分表的列值的类型
//$user_multiple_dbroute_config['select_in_logic_column'] = "user_names"; //select in 查询时时的参数key名
//$user_multiple_dbroute_config['table_total_num'] = 64; //总表数
//$user_multiple_dbroute_config['one_db_table_num'] = 16; //每个库里存放的表数
//
//$master_sc_user_multiple_dbs = array();
//$master_sc_user_multiple_dbs['mmall_0000'] = DB_HOST; //key为数据库名，value为数据库所在的host，此value只能配置一个IP
//$master_sc_user_multiple_dbs['mmall_0001'] = DB_HOST;
//$master_sc_user_multiple_dbs['mmall_0002'] = DB_HOST;
//$master_sc_user_multiple_dbs['mmall_0003'] = DB_HOST;
//$user_multiple_dbroute_config['db_hosts'] = $master_sc_user_multiple_dbs; //每个主库存放在哪个host
//
//$slave_sc_user_multiple_dbs = array();
//$slave_sc_user_multiple_dbs['mmall_0000'] = DB_HOST . "," . DB_HOST; //key为数据库名，value为slave库所在的host列表，多台用英文半角逗号分隔开，系统随机读其中一台，如果下面read_db_hosts配置了
//$slave_sc_user_multiple_dbs['mmall_0001'] = DB_HOST . "," . DB_HOST;
//$slave_sc_user_multiple_dbs['mmall_0002'] = DB_HOST . "," . DB_HOST;
//$slave_sc_user_multiple_dbs['mmall_0003'] = DB_HOST . "," . DB_HOST;
//$user_multiple_dbroute_config['read_db_hosts'] = $slave_sc_user_multiple_dbs; //dbroute根据key为‘read_db_hosts’是否设置了值 判断是否有读写分离配置
///**************************************************************************************************************************************/
//
///***************用户退款库表配置(多库)*************************************************************************************************/
//$sc_refund_multiple_dbroute_config = array();
//$sc_refund_multiple_dbroute_config['host'] = DB_HOST; //db_host
//$sc_refund_multiple_dbroute_config['user_name'] = DB_USER_NAME; //db username
//$sc_refund_multiple_dbroute_config['pass_word'] = DB_PASSWORD; //db pwd
//$sc_refund_multiple_dbroute_config['port'] = DB_PORT; //db port
//
//$sc_refund_multiple_dbroute_config['db_prefix'] = "sc_refund_0000"; //数据库前缀，生成类似sc_refund_0000、sc_refund_0001...sc_refund_1023
//$sc_refund_multiple_dbroute_config['table_prefix'] = "refund_0000"; //表名前缀，生成类似refund_0000、refund_0001...refund_1023
//$sc_refund_multiple_dbroute_config['logic_table'] = "sc_refund"; //逻辑表名不能为sql关键字
//$sc_refund_multiple_dbroute_config['table_logic_column'] = "user_id"; //分表的列，执行sql语句时，要传递此参数
//$sc_refund_multiple_dbroute_config['table_logic_column_type'] = "int"; //分表的列值的类型
//$sc_refund_multiple_dbroute_config['select_in_logic_column'] = "user_ids"; //select in 查询时时的参数key名
//$sc_refund_multiple_dbroute_config['table_total_num'] = 64; //总表数
//$sc_refund_multiple_dbroute_config['one_db_table_num'] = 16; //每个库里存放的表数
//
//$master_sc_refund_multiple_dbs = array();
//$master_sc_refund_multiple_dbs['sc_refund_0000'] = DB_HOST; //key为数据库名，value为数据库所在的host，此value只能配置一个IP
//$master_sc_refund_multiple_dbs['sc_refund_0001'] = DB_HOST;
//$master_sc_refund_multiple_dbs['sc_refund_0002'] = DB_HOST;
//$master_sc_refund_multiple_dbs['sc_refund_0003'] = DB_HOST;
//$sc_refund_multiple_dbroute_config['db_hosts'] = $master_sc_refund_multiple_dbs; //每个主库存放在哪个host
//
//$slave_sc_refund__multiple_dbs = array();
//$slave_sc_refund__multiple_dbs['sc_refund_0000'] = DB_HOST . "," . DB_HOST; //key为数据库名，value为slave库所在的host列表，多台用英文半角逗号分隔开，系统随机读其中一台，如果下面read_db_hosts配置了
//$slave_sc_refund__multiple_dbs['sc_refund_0001'] = DB_HOST . "," . DB_HOST;
//$slave_sc_refund__multiple_dbs['sc_refund_0002'] = DB_HOST . "," . DB_HOST;
//$slave_sc_refund__multiple_dbs['sc_refund_0003'] = DB_HOST . "," . DB_HOST;
//$sc_refund_multiple_dbroute_config['read_db_hosts'] = $slave_sc_refund__multiple_dbs; //dbroute根据key为‘read_db_hosts’是否设置了值 判断是否有读写分离配置
///******************************************************************************************************************************************/
//
///***************用户退款详情库表配置(多库)*************************************************************************************************/
//$sc_refund_info_multiple_dbroute_config = array();
//$sc_refund_info_multiple_dbroute_config['host'] = DB_HOST; //db_host
//$sc_refund_info_multiple_dbroute_config['user_name'] = DB_USER_NAME; //db username
//$sc_refund_info_multiple_dbroute_config['pass_word'] = DB_PASSWORD; //db pwd
//$sc_refund_info_multiple_dbroute_config['port'] = DB_PORT; //db port
//
//$sc_refund_info_multiple_dbroute_config['db_prefix'] = "sc_refund_0000"; //数据库前缀，生成类似sc_refund_0000、sc_refund_0001...sc_refund_1023
//$sc_refund_info_multiple_dbroute_config['table_prefix'] = "refund_info_0000"; //表名前缀，生成类似refund_info_0000、refund_info_0001...refund_info_1023
//$sc_refund_info_multiple_dbroute_config['logic_table'] = "sc_refund_info"; //逻辑表名不能为sql关键字
//$sc_refund_info_multiple_dbroute_config['table_logic_column'] = "user_id"; //分表的列，执行sql语句时，要传递此参数
//$sc_refund_info_multiple_dbroute_config['table_logic_column_type'] = "int"; //分表的列值的类型
//$sc_refund_info_multiple_dbroute_config['db_logic_column'] = "user_id"; //分库的列，执行sql语句时，要传递此参数
//$sc_refund_info_multiple_dbroute_config['db_logic_column_type'] = "int"; //分库的列值的类型，如不设置此值将同table_logic_column_type的值
//$sc_refund_info_multiple_dbroute_config['select_in_logic_column'] = "user_ids"; //select in 查询时时的参数key名
//$sc_refund_info_multiple_dbroute_config['table_total_num'] = 64; //总表数
//$sc_refund_info_multiple_dbroute_config['one_db_table_num'] = 16; //每个库里存放的表数
//$sc_refund_info_multiple_dbroute_config['consistent_hash_separate_string'] = "[0,256]=sc_refund_0000;[256,512]=sc_refund_0001;[512,768]=sc_refund_0002;[768,1024]=sc_refund_0003";//一致性hash字符串区间
////$sc_refund_info_multiple_dbroute_config['consistent_hash_separate_mod_max_value'] =1024;//一致性hash最大区间值
////$sc_refund_info_multiple_dbroute_config['consistent_hash_one_db_one_table'] =true;//区间是否是是一库一表
////$sc_refund_info_multiple_dbroute_config['virtual_db_node_number'] = 64; //虚拟节点数目 虚拟hash算法实现以此key为判断
////$sc_refund_info_multiple_dbroute_config['db_hash_type'] ='virtual_hash';//可为  consistent_hash(必需设置key:consistent_hash_separate_string及consistent_hash_separate_mod_max_value) ||virtual_hash(必需设置key:virtual_db_node_number) ||mod_hash ，如果不设置，则默认为 mod_hash
//
//$master_sc_refund_info_multiple_dbs = array();
//$master_sc_refund_info_multiple_dbs['sc_refund_0000'] = DB_HOST; //key为数据库名，value为数据库所在的host，此value只能配置一个IP
//$master_sc_refund_info_multiple_dbs['sc_refund_0001'] = DB_HOST;
//$master_sc_refund_info_multiple_dbs['sc_refund_0002'] = DB_HOST;
//$master_sc_refund_info_multiple_dbs['sc_refund_0003'] = DB_HOST;
//$sc_refund_info_multiple_dbroute_config['db_hosts'] = $master_sc_refund_info_multiple_dbs; //每个主库存放在哪个host
//
//$slave_sc_refund_info_multiple_dbs = array();
//$slave_sc_refund_info_multiple_dbs['sc_refund_0000'] = DB_HOST . "," . DB_HOST; //key为数据库名，value为slave库所在的host列表，多台用英文半角逗号分隔开，系统随机读其中一台，如果下面read_db_hosts配置了
//$slave_sc_refund_info_multiple_dbs['sc_refund_0001'] = DB_HOST . "," . DB_HOST;
//$slave_sc_refund_info_multiple_dbs['sc_refund_0002'] = DB_HOST . "," . DB_HOST;
//$slave_sc_refund_info_multiple_dbs['sc_refund_0003'] = DB_HOST . "," . DB_HOST;
//$sc_refund_info_multiple_dbroute_config['read_db_hosts'] = $slave_sc_refund_info_multiple_dbs;//dbroute根据key为‘read_db_hosts’是否设置了值 判断是否有读写分离配置
///******************************************************************************************************************************************/
//
///***************按时间方式分库分表,时间分库支持(支持库表都按时间分 , 库按逻辑列分表按时间分, 库按时间分表按逻辑列分)*************************************************************************************************/
//$monthofyear_multiple_dbroute_config = array();
//$monthofyear_multiple_dbroute_config['host'] = DB_HOST; //db_host
//$monthofyear_multiple_dbroute_config['user_name'] = DB_USER_NAME; //db username
//$monthofyear_multiple_dbroute_config['pass_word'] = DB_PASSWORD; //db pwd
//$monthofyear_multiple_dbroute_config['port'] = DB_PORT; //db port
//
//$monthofyear_multiple_dbroute_config['db_prefix'] = "sc_refund_info_0000"; //数据库前缀，生成类似sc_refund_0000、sc_refund_0001...sc_refund_1023
//$monthofyear_multiple_dbroute_config['table_prefix'] = "refund_info_0000"; //表名前缀，生成类似refund_info_0000、refund_info_0001...refund_info_1023
//$monthofyear_multiple_dbroute_config['logic_table'] = "sc_refund_info"; //逻辑表名不能为sql关键字,用户月支付详情
//$monthofyear_multiple_dbroute_config['table_logic_column'] = "user_id"; //分表的列，执行sql语句时，要传递此参数,日期分表时不用设置
//$monthofyear_multiple_dbroute_config['table_logic_column_type'] = "int"; //分表的列值的类型,日期分表时不用设置
////$monthofyear_multiple_dbroute_config['db_logic_column'] = "user_id"; //分库的列，执行sql语句时，要传递此参数,日期分库时不用设置
////$monthofyear_multiple_dbroute_config['db_logic_column_type'] = "int"; //分库的列值的类型，如不设置此值将同table_logic_column_type的值,日期分库时不用设置
//
//$monthofyear_multiple_dbroute_config['select_in_logic_column'] = "user_ids"; //select in 查询时时的参数key名
//$monthofyear_multiple_dbroute_config['table_total_num'] = 48; //总表数,如果按照日期分库,此值可不设置
//$monthofyear_multiple_dbroute_config['one_db_table_num'] = 12; //每个库里存放的表数
//$monthofyear_multiple_dbroute_config['is_date_db'] = true; //是否按时间分库
//$monthofyear_multiple_dbroute_config['db_name_date_logic_string'] = 'year@@@2009_2020'; //时间分库表达式可为year(year@@@2009_2020),2009_2020表示起始年份,为year时必须配置 ||month(月01,02...12) ||day(天:0...31)||week(星期日:0,星期一:1...);
//$monthofyear_multiple_dbroute_config['is_date_table'] = true; //是否按时间分表
//$monthofyear_multiple_dbroute_config['table_name_date_logic_string'] = 'week@@@'; //按时间分表 year_month_day(year_month_day@@@20140501)@@@后表示开始年月日 || year_and_month(year_and_month@@@201405)@@@后表示开始年月 || year(year@@@2010)@@@后表示开始年份 ||day(天:01...31) ||month(月01...12) ||month_and_day(月日)||week(星期日:00,星期一:01...);
//
//$master_sc_refund_info_multiple_dbs = array();
//$master_sc_refund_info_multiple_dbs['sc_refund_0000'] = DB_HOST; //key为数据库名，value为数据库所在的host，此value只能配置一个IP
//$monthofyear_multiple_dbroute_config['db_hosts'] = $master_sc_refund_info_multiple_dbs; //每个主库存放在哪个host
//
//$slave_sc_refund_info_multiple_dbs = array();
//$slave_sc_refund_info_multiple_dbs['sc_refund_0000'] = DB_HOST . "," . DB_HOST; //key为数据库名，value为slave库所在的host列表，多台用英文半角逗号分隔开，系统随机读其中一台，如果下面read_db_hosts配置了
//$monthofyear_multiple_dbroute_config['read_db_hosts'] = $slave_sc_refund_info_multiple_dbs;//dbroute根据key为‘read_db_hosts’是否设置了值 判断是否有读写分离配置
/******************************************************************************************************************************************/