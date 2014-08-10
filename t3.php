<?php
header("Content-Type: text/html; charset=utf-8");
require(dirname(__FILE__) . '/includes/init.php');
require(dirname(__FILE__) . '/includes/ao/RefundAO.php');

$refundAO = new RefundAO();
print_r($refundAO->testTransaction(20));



