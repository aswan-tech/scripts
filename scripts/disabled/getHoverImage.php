<?php
require_once '/mnt/www/Lecom_Magento/app/Mage.php';
umask(0);
Mage::app();

$sku = $_GET['sku'];
$product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);

$productCollection = Mage::getModel('catalog/product')->getCollection()
                ->addAttributeToSelect('sku')
                ->addAttributeToSelect('small_image')
                ->addAttributeToFilter('type_id', 'simple')
                ->addAttributeToSort('entity_id', 'DESC');

echo "sku,Image Path\n";
foreach($productCollection as $product){
	if($product->getId())
	{
		echo $product->getSku().",";
		//$imgUrl = Mage::helper('catalog/image')->init($product, 'base_image')->resize(235,250);
		//echo $imgUrl;
		echo "http://static.americanswan.com/Lecom_Magento/media/catalog/product".$product->getSmallImage()."\n";
	}
	else 
	{
		echo "Some Error:";
	}

}
