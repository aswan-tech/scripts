<?php 
$mageFilename = '../app/Mage.php';
require_once $mageFilename;
ini_set('display_errors', 1);
Mage::app();
header('Content-Type: text/csv; charset=utf-8');
header("Content-Disposition: attachment; filename=confNameUrl.csv");
header("Pragma: no-cache");
header("Expires: 0");
$outstream = fopen("php://output", "w");
fputcsv($outstream, array("Sku","Name", "Product Url"),chr(44),'"');
$write = Mage::getSingleton('core/resource')->getConnection('core_write');
$collection = Mage::getResourceModel('catalog/product_collection');
Mage::getModel('catalog/layer')->prepareProductCollection($collection);
$collection->addAttributeToFilter('type_id','configurable');
$collection->addStoreFilter();
$store_id = '0';
foreach($collection as $val) {
    $_product = Mage::getModel('catalog/product')->load($val['entity_id']);	
	fputcsv($outstream, array($_product['sku'],$val['name'],$_product['url_path']),chr(44),'"');
}
fclose($outstream);


