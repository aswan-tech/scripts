<?php
ini_set('display_errors', 1);
set_time_limit(0);
ini_set('memory_limit', '-1');
require '/home/cloudpanel/htdocs/www.americanswan.com/current/app/Mage.php';
umask( 0 );
Mage::app();

$fromDate = '2014-04-01 00:00:00';
$toDate = '2014-12-31 23:59:59';


$orders = Mage::getModel('sales/order')->getCollection()
										 ->addAttributeToFilter('created_at', array('from'=>$fromDate, 'to'=>$toDate));
										//->setPageSize(100)
										//->setCurPage(1);

echo '"Order ID","Billing Address","Shippping Address"'."\n";							

//echo "<pre>";
$shippingAddress = '';
$billingAddress = '';
foreach($orders as $order) {
	$shippingAddress = $order->getShippingAddress()->getFirstname()." ".$order->getShippingAddress()->getLastname().", ";
	$shippingAddress .= $order->getShippingAddress()->getStreetFull().", ".$order->getShippingAddress()->getRegion().", ";
	$shippingAddress .= $order->getShippingAddress()->getCity().", ".$order->getShippingAddress()->getPostcode().", ";
	$shippingAddress .= $order->getShippingAddress()->getTelephone().", ";
	$shippingAddress .= ($order->getShippingAddress()->getCountry_id() == 'IN' ? 'India' : $order->getShippingAddress()->getCountry_id());
	
	$billingAddress = $order->getBillingAddress()->getFirstname()." ".$order->getBillingAddress()->getLastname().", ";
	$billingAddress .= $order->getBillingAddress()->getStreetFull().", ".$order->getBillingAddress()->getRegion().", ";
	$billingAddress .= $order->getBillingAddress()->getCity().", ".$order->getBillingAddress()->getPostcode().", ";
	$billingAddress .= $order->getBillingAddress()->getTelephone().", ";
	$billingAddress .= ($order->getBillingAddress()->getCountry_id() == 'IN' ? 'India' : $order->getShippingAddress()->getCountry_id());
	
	echo '"'.$order->getIncrementId().'","'.$shippingAddress.'","'.$billingAddress.'"'."\n";
}

?>
