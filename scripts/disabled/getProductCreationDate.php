<?php
$mageFilename = '/mnt/www/Lecom_Magento/app/Mage.php';
require_once $mageFilename;
ini_set('display_errors', 1);
Mage::app();

$productCollection = Mage::getModel('catalog/product')->getCollection()
		->addAttributeToSelect('sku')
		->addAttributeToSelect('created_at')
                ->addAttributeToFilter('type_id', 'configurable')
                ->addAttributeToFilter('status', array('eq' =>  Mage_Catalog_Model_Product_Status::STATUS_ENABLED))
                ->addAttributeToSort('entity_id', 'DESC');

echo "sku,creation date\n";
foreach($productCollection as $product)
{
	echo $product->getSku().','.$product->getCreatedAt()."\n";
}
