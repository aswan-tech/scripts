<?php
require_once '/mnt/www/Lecom_Magento/app/Mage.php';
umask(0);
Mage::app();

$productCollection = Mage::getModel('catalog/product')->getCollection()
                ->addAttributeToSelect('sku')
                ->addAttributeToSelect('color')
               // ->addAttributeToFilter('type_id', 'configurable')
              //  ->addAttributeToFilter('status', array('eq' =>  Mage_Catalog_Model_Product_Status::STATUS_ENABLED))
                ->addAttributeToSort('entity_id', 'DESC');

echo "sku,Color,Color Code\n";
foreach($productCollection as $product)
{
        echo $product->getSku().','.$product->getColorValue().','.$product->getColor()."\n";

}
