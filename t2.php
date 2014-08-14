<?php
header("Content-Type: text/html; charset=utf-8");
require(dirname(__FILE__) . '/includes/init.php');
require(dirname(__FILE__) . '/includes/ao/OrderAO.php');



$a=cls_sqlexecute::getInstance('mmall_0000',$user_multiple_dbroute_config);
$b=cls_sqlexecute::getInstance('mmall_0001',$user_multiple_dbroute_config);
$c=cls_sqlexecute::getInstance('mmall_0002',$user_multiple_dbroute_config);

print_r($a->getList());
$c=cls_sqlexecute::getInstance('mmall_0003',$user_multiple_dbroute_config);
print_r($a->getList());

die;
$userModel = new UserModel();
print_r($userModel->insert());
echo "====";
die;
$m = new RefundInfoModel();
print_r($m->insert(10, 1));
die;
print_r($userModel->getTableNameBytUserName("abc713807"));
//print_r($userModel->getRow('abc291901'));
//print_r($userModel->getOne('abc291901'));
//print_r($userModel->update('abc291901','new_pwd'));
//print_r($userModel->queryAll('abc'));
//print_r($userModel->queryAllByIn('abc'));
//print_r($userModel->transactionTest('user_name'));
//print_r($userModel->delete('abc291901'));
//die;

