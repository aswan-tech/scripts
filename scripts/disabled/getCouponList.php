<?php
require_once '../app/Mage.php';
umask(0);
Mage::app();
$ruleId = "1288";
$coupons = Mage::getModel('salesrule/coupon')->addFieldToFilter($ruleId, 'rule_id');
var_dump($coupons);

