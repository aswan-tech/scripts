<?php
ini_set('display_errors', 1);
set_time_limit(0);
ini_set('memory_limit', '-1');
require '/home/cloudpanel/htdocs/www.americanswan.com/current/app/Mage.php';
Mage::app();


$orderIncrementId = "";
$orders = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);

foreach($orders as $order) {
	foreach($productArr as $product) {
		echo '"'.$order->getIncrementId().'","'.$order->getData('created_at').'","'.$product['sku'].'","'.$product['qty'].'","'.$order->getStatus().'"'."\n";	
	}
}
?>

