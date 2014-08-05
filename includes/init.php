<?php
define('ROOT_PATH', str_replace('includes/init.php', '', str_replace('\\', '/', __FILE__)));
ini_set('memory_limit', -1);
require (ROOT_PATH . 'includes/config.php');
include (ROOT_PATH . 'includes/base/autoload.class.php');

date_default_timezone_set('PRC');
autoloader::init();

if(defined("MYSQL_EXTEND") && MYSQL_EXTEND == 'mysql_pdo'){
	$db_mysqli=new cls_pdosqlexecute();
}else{
	$db_mysqli=new cls_sqlexecute();
}

ob_start();
?>
