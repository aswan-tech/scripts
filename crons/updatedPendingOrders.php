<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);
require '/home/cloudpanel/htdocs/www.americanswan.com/current/app/Mage.php';
Mage::app();

$write = Mage::getSingleton('core/resource')->getConnection('core_write');

$arrResult = array();
$arrLines = file('Order-Percolation-Data.csv');
foreach($arrLines as $line) {
	$arrResult[] = explode( ',', $line);
}

foreach ($arrLines as $entityId){
			if($entityId) {
				$write->query("UPDATE sales_flat_order SET sent_to_erp = 1 WHERE entity_id = '{$entityId}' LIMIT 1");
				echo "<br>ORDER ID : {$entityId} - changed status: sent to dc";
			}
			else{
				echo "<br>Error: ORDER ID : {$entityId} - SENT TO DC - status is not changed";
			}
}
?>
