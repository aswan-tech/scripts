<?php
ini_set('display_errors', 1);
require '/home/cloudpanel/htdocs/www.americanswan.com/current/app/Mage.php';
Mage::app();

$write = Mage::getSingleton('core/resource')->getConnection('core_write');

$filepath = "/tmp/order-7may.csv";

$row = 1;
if (($handle = fopen($filepath, "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {		
		if(!empty($data[0])) {
			$_order = Mage::getModel('sales/order')->load($data[0], 'increment_id');
			$entityId = $_order->getId();		
			if($entityId) {
				$write->query("UPDATE sales_flat_order SET sent_to_erp = 0 WHERE entity_id = '{$entityId}' LIMIT 1");
				echo "OrderID: {$data[0]} updated! \n";
			 }
			else{
				echo "OrderID: {$data[0]} not found! \n";
			}                        
		}
		else{
			echo "Invalid: {$data[0]} number! \n";
		}
		//print_r($data);
    }
    fclose($handle);
}
die;
?>
