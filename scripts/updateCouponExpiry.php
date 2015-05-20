<?php

require_once('/home/cloudpanel/htdocs/www.americanswan.com/current/app/Mage.php');
Mage::app();

$ruleid = "1333";
$date = "2015-02-01";
//Load the salesrule coupon collection
$couponCollection = Mage::getModel('salesrule/coupon')->getCollection()->addFieldToFilter('rule_id',$ruleid);
$count = 0;
foreach($couponCollection as $coupon)
{
$old = $coupon->getExpirationDate();
if(strtotime($old) == strtotime($date)) echo "skip, already updated\n";
else {
$coupon->setExpirationDate($date);
$coupon->save();
echo $coupon->getCode() ." updated\n";
}
$count ++;
if($count % 1000 == 0 )
{ echo $count. "coupouns updated\n";
  sleep(2);
}

}
echo "Total ".$count." coupons updated \n";
