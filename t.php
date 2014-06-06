<?php
header("Content-Type: text/html; charset=utf-8");
require(dirname(__FILE__) . '/includes/init.php');

//$sql="select id from order where user_id=:user_id0";
//$s=stristr($sql, ":"."user_id0");
//echo $s;
//die;


//echo stristr("Hello user_id_0,user_id_1!","user_id_0");

$mop=new cls_sequence();
//$num=$mop->nextValue('user');
//echo $num."<br>";
//die;

$order=new OrderModel();

$result=$order->queryAllByIn();
print_r($result);
die;
$res=$order->queryAll();
print_r($res);
die;

$result=$order->getAll();
echo "getAll<br>";
print_r($result);

$res=$order->queryAll();
echo "queryAll<br>";
print_r($res);

$id=$order->insert();
echo "insert<br>";
print_r($id);

$result=$order->getRow(599);
echo "getRow<br>";
print_r($result);

$result=$order->getOne(1);
echo "getOne<br>";
print_r($result);

$result=$order->delete(583,10);
echo "delete<br>";
print_r($result);

$result=$order->update(584,10);
echo "update<br>";
print_r($result);

