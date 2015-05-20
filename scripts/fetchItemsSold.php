<?php
require_once('/home/cloudpanel/htdocs/www.americanswan.com/current/app/Mage.php');
ini_set("error_reporting",E_ALL);
ini_set("display_errors",true);
umask(0);

Mage::app('admin');

// Some sort of SKU counter variable
$sku_counter = array();

// Create order collection object
$collection = Mage::getModel('sales/order')->addFieldFilter('created_at', array('from' => '2014-11-10', 'to' => '2014-11-11'));

echo count(($collection));
die();


// Iterate it for displaying results
foreach ($collection as $order) {
    foreach ($order->getAllItems() as $item) {
	echo $item.": ";
        if (!isset($sku_counter[$item->getSku()])) {
            $sku_counter[$item->getSku()] = 0;
        }
        $sku_counter[$item->getSku()] += (float) $item->getQtyOrdered();
	
    }
}
?>
