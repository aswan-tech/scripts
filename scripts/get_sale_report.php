<?php
ini_set('display_errors', 1);
set_time_limit(0);
ini_set('memory_limit', '-1');
require '/home/cloudpanel/htdocs/www.americanswan.com/current/app/Mage.php';
Mage::app();
$fromDate = '2013-01-01';
$toDate = '2014-12-30';

//$fromDate = date('Y-m-d H:i:s', strtotime($fromDate));
//$toDate = date('Y-m-d H:i:s', strtotime($toDate));

$orders = Mage::getModel('sales/order')->getCollection()
				 ->addAttributeToFilter('created_at', array('from'=>$fromDate, 'to'=>$toDate));
		//		->setPageSize(200)
		//		->setCurPage(1);
echo '"OrderNumber","OrderDate","ItemSKU","OrderQty","LatestStatus"'."\n";								
$data = array();
foreach($orders as $order) {
	$productArr = array();
	$ordered_items = $order->getAllItems();
	foreach($ordered_items as $item) {
		$productArr[$item->getSku()] = array('sku'=>$item->getSku(), 'qty'=>(int)$item->getQtyOrdered());		
	}
	
	foreach($productArr as $product) {
		echo '"'.$order->getIncrementId().'","'.$order->getData('created_at').'","'.$product['sku'].'","'.$product['qty'].'","'.$order->getStatus().'"'."\n";	
	}
}
?>

