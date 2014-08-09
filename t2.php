<?php
header("Content-Type: text/html; charset=utf-8");
require(dirname(__FILE__) . '/includes/init.php');
require(dirname(__FILE__) . '/includes/ao/OrderAO.php');


$userModel=new UserModel();
//$userModel->insert();
//print_r($userModel->getRow('abc291901'));
//print_r($userModel->getOne('abc291901'));
//print_r($userModel->update('abc291901','new_pwd'));
//print_r($userModel->queryAll('abc'));
print_r($userModel->queryAllByIn('abc'));
//print_r($userModel->transactionTest('user_name'));
//print_r($userModel->delete('abc291901'));
//die;

