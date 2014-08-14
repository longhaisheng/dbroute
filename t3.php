<?php
header("Content-Type: text/html; charset=utf-8");
require(dirname(__FILE__) . '/includes/init.php');
require(dirname(__FILE__) . '/includes/ao/RefundAO.php');



$m = new RefundInfoModel();
print_r($m->insert(10, 1));
print_r($m->selectByIn());
print_r($m->queryAllFromTable());
die;
$refundAO = new RefundAO();

//function getmod($id){
//	return $id % 32;
//}

//for ($i = 0; $i < 64; $i++) {
//	$m=getmod($i);
//	$node=$m%4;
//	$node2=$m%16;
//	$node3=$i%16;
//	echo $i."=>".$node."=>".$node2."=>$node3<br>";
//}
//die; 


//print_r($refundAO->testTransaction(20));

//$hash = new VirtualHash($sc_refund_info_virtual_multiple_dbroute_config);
//print_r($hash->getVirtualDbHostList());
//die;
echo "++++++++++++++++ Virtual HASH++++++++++++++++++<br>";
//$hash=new cls_flexihash();
//
//
//$hash->addTargets(array('cache-1', 'cache-2', 'cache-3'));// bulk add
//// simple lookup
//print_r($hash->lookup('object-a')); // "cache-1"
//print_r($hash->lookup('object-b')); // "cache-2"
//// add and remove
//$hash->addTarget('cache-4')->removeTarget('cache-1');
//// lookup with next-best fallback (for redundant writes)
//print_r($hash->lookupList('object', 2)); // ["cache-2", "cache-4"]
//// remove cache-2, expect object to hash to cache-4
//$hash->removeTarget('cache-2');
//print_r($hash->lookup('object')); // "cache-4"

//$hash->addTarget("sooch_0000");
//$hash->addTarget("sooch_0001");
//$hash->addTarget("sooch_0002");
//$hash->addTarget("sooch_0003");


/*$array = array();
for ($i = 0; $i < 50000; $i++) {
    $db = $hash->getDbName($i);
    $t = $hash->getTableName($i);
    $array[] = $t;
    echo "=>" . $i . "=>" . $db . "=>" . $t . "<br>";
    echo $i."=>" . $db . "=>" . $t . "<br>";
}*/
echo "++++++++++++++++++++++++++++++++++++++++++++++++++<br>";
$hash = new VirtualHash($sc_refund_info_virtual_multiple_dbroute_config);
echo "++++++++++++++++ CONSISTENT HASH++++++++++++++++++<br>";
$array=array();
for ($i = 0; $i < 1024; $i++) {
    $db = $hash->getDbName($i);
    $t = $hash->getTableName($i);
    $array[]=$t;
    echo $i . "=>" . $db . "=>" . $t . "<br>";
}
echo "++++++++++++++++++++++++++++++++++++++++++++++++++<br>";
$ar=array_unique($array);
$ss=asort($ar);
print_r($ar);
die;


echo "+++++++++++++++++ MOD HASH +++++++++++++++++++++++<br>";

$hash = new ModHash($sc_refund_multiple_dbroute_config);
for ($i = 0; $i < 1024; $i++) {
    $db = $hash->getDbName($i);
    $t = $hash->getTableName($i);
    echo $i . "=>" . $db . "=>" . $t . "<br>";
}
die;




