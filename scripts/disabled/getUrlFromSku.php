<?php 

$mageFilename = '../app/Mage.php';
require_once $mageFilename;
ini_set('display_errors', 1);
Mage::app();

read skus from file
if (($handle = fopen("sku.csv", "r")) !== FALSE) {
while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
$sku = $data[0];
$product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
echo $sku.",";
if($product)
{
	// if product is simple get CP
	if($product->getTypeId() == 'simple')
	{
		$parentIds = Mage::getResourceSingleton('catalog/product_type_configurable')
                  ->getParentIdsByChild($product->getId());
		$parent = Mage::getModel('catalog/product')->load($parentIds[0]);
		echo $parent->getProductUrl()."\n";
	}
	else
	{
		echo $product->getProductUrl()."\n";
	}
}
else
{
	echo "url not found\n";
}
}
}
