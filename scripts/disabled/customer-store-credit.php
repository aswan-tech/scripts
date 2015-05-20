<?php 
$mageFilename = '../app/Mage.php';
require_once $mageFilename;
ini_set('display_errors', 1);
Mage::app();
header('Content-Type: text/csv; charset=utf-8');
header("Content-Disposition: attachment; filename=storecreditMobileInfo.csv");
header("Pragma: no-cache");
header("Expires: 0");
$outstream = fopen("php://output", "w");
fputcsv($outstream, array("Mobile", "Store Credit"),chr(44),'"');
$write = Mage::getSingleton('core/resource')->getConnection('core_write');
$collection = Mage::getResourceModel('customer/customer_collection')->addNameToSelect()->addAttributeToSelect('email')->addAttributeToSelect('telephone');
foreach($collection as $val) {	
	$value = $write->query("select * from enterprise_customerbalance where customer_id='".$val['entity_id']."' and amount > '0' ");
	$row = $value->fetch();
	if($row['amount']){	fputcsv($outstream, array($val['telephone'],$row['amount']),chr(44),'"'); }
}
fclose($outstream);
			
			
			

