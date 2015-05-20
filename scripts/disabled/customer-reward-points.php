<?php 
$mageFilename = '../app/Mage.php';
require_once $mageFilename;
ini_set('display_errors', 1);
Mage::app();
header('Content-Type: text/csv; charset=utf-8');
header("Content-Disposition: attachment; filename=rewardPointsMobileInfo.csv");
header("Pragma: no-cache");
header("Expires: 0");
$outstream = fopen("php://output", "w");
fputcsv($outstream, array("Email","Mobile", "Reward Points"),chr(44),'"');
$write = Mage::getSingleton('core/resource')->getConnection('core_write');
$collection = Mage::getResourceModel('customer/customer_collection')->addNameToSelect()->addAttributeToSelect('email')->addAttributeToSelect('telephone');
foreach($collection as $val) {	
	$value = $write->query("select customer_points_usable from rewards_customer_index_points where customer_id='".$val['entity_id']."' ");
	$row = $value->fetch();
	if($row['customer_points_usable']){	fputcsv($outstream, array($val['email'], $val['telephone'],$row['customer_points_usable']),chr(44),'"'); }
}
fclose($outstream);
			
			
			

