<?php
header("Content-Type: text/html; charset=utf-8");
require(dirname(__FILE__) . '/includes/init.php');
require(dirname(__FILE__) . '/includes/ao/RefundAO.php');

$refundAO = new RefundAO();
//print_r($refundAO->testTransaction(20));

$hash = new ConsistentHash($sc_refund_info_multiple_dbroute_config);
echo "++++++++++++++++ CONSISTENT HASH++++++++++++++++++<br>";
for ($i = 0; $i < 1024; $i++) {
    $db = $hash->getDbName($i);
    $t = $hash->getTableName($i);
    echo $i . "=>".$db ."=>". $t."<br>";
}
echo "++++++++++++++++++++++++++++++++++++++++++++++++++<br>";
echo "+++++++++++++++++ MOD HASH +++++++++++++++++++++++<br>";

$hash = new ModHash($sc_refund_multiple_dbroute_config);
for ($i = 0; $i < 1024; $i++) {
    $db = $hash->getDbName($i);
    $t = $hash->getTableName($i);
    echo $i . "=>".$db ."=>". $t."<br>";
}
die;
$m = new RefundInfoModel();
print_r($m->selectByIn());
print_r($m->queryAllFromTable());
print_r($m->insert(10,1));
global $sc_refund_info_multiple_dbroute_config;



