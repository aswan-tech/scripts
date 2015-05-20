<?php
require_once '/home/cloudpanel/htdocs/www.americanswan.com/current/app/Mage.php';
umask(0);
Mage::app();

//$sku = $_GET['sku'];
$productCollection = Mage::getModel('catalog/product')->getCollection()
                ->addAttributeToSelect('sku')
                ->addAttributeToSelect('gender')
//                ->addAttributeToFilter('type_id', 'configurable')
                ->addAttributeToSort('entity_id', 'DESC');

echo "sku, Gender\n";
foreach($productCollection as $product){
	if($product->getId())
	{
		echo $product->getSku().",";
		echo $product->getGenderValue().",";
		echo "\n";
	}
	else 
	{
		echo "Some Error:";
	}

}


