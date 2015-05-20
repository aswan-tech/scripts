<?php 
//Item master upload utility - incase not working from browser

$mageFilename = '/home/cloudpanel/htdocs/www.americanswan.com/current/app/Mage.php';
require_once $mageFilename;
ini_set('display_errors', 1);
Mage::app();

//$varToDir = Mage::getBaseDir('media').DS.'category_products'.DS;
$varToDir = "/home/ubuntu/scripts_magento/scripts/";
$filename = 'Itmstr_20150502121915_1.csv';
$recordCount = 0;
$totalRecordCount = 0;
define('IMPORT_FILE_NAME', $varToDir . $filename);
$adapter = Mage::getModel('catalog/convert_adapter_productimport');
$filesRead = fopen(IMPORT_FILE_NAME, 'r');
$headersData = fgetcsv($filesRead);
$errors = array();
$errorsSku = array();
$consolidatedMesage = "";
while ($data = fgetcsv($filesRead)) {
	$recordCount++;
	$mergedData = Mage::helper('itemmaster')->fcmImportData($headersData, $data);
	try {
		$adapter->saveRow($mergedData);
		$totalRecordCount++;
	} catch (Exception $e) {
		echo $mergedData['sku']."error in sku \n";
		echo $e->getMessage()."\n";
		$errorsSku[] = $mergedData['sku'];
		$errors[] = $e->getMessage();
		continue;
	}
}
if (count($errors) < 1) {
	echo $successMessage = "Item master products imported successfully." . $totalRecordCount . " out of Total " . $recordCount . " record imported successfully -> " . $filename;
}else{
	foreach ($errorsSku as $skuKey => $skuVal) {
		if (strlen($skuVal) > 0) {
			$skuMsgs .= $skuVal . ", ";
		}
	}
	if (strlen($skuMsgs) > 0) {
		$errorMessage .= 'SKUs not imported are ' . $skuMsgs."\n";
	}
	echo $errorMessage;
}

	
