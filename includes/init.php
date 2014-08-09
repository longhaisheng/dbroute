<?php
define('ROOT_PATH', str_replace('includes/init.php', '', str_replace('\\', '/', __FILE__)));
ini_set('memory_limit', -1);
require (ROOT_PATH . 'includes/config.php');
include (ROOT_PATH . 'includes/base/autoload.class.php');

date_default_timezone_set('PRC');
autoloader::init();

$default_oprater_dbroute=new cls_dbroute($default_config_array);
ob_start();
?>
