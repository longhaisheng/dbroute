<?php
header("Content-Type: text/html; charset=utf-8");
require(dirname(__FILE__) . '/includes/init.php');
require(dirname(__FILE__) . '/includes/ao/RefundAO.php');

$refundAO = new RefundAO();
//print_r($refundAO->testTransaction(20));

$m = new RefundInfoModel();
print_r($m->selectByIn());
die;
print_r($m->queryAllFromTable());
print_r($m->insert(10,1));
die;
global $sc_refund_info_multiple_dbroute_config;
$hash = new ConsistentHash($sc_refund_info_multiple_dbroute_config);
for ($i = 0; $i < 1024; $i++) {
    $db = $hash->getDbName($i);
    $t = $hash->getTableName($i);
    echo $i . "=>" . $t."<br>";
}



