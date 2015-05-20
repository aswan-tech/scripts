<?php
$mageFilename = '/home/cloudpanel/htdocs/www.americanswan.com/current/app/Mage.php';
require_once $mageFilename;
ini_set('display_errors', 1);
Mage::app();

//clearence- 262
$value = 262;

//sort array


$collection = Mage::getModel('catalog/category')->load($value)
                ->getProductCollection()
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('type_id', 'configurable')
                ->addAttributeToFilter('status', array('eq' =>  Mage_Catalog_Model_Product_Status::STATUS_ENABLED))
                ->addAttributeToSort('entity_id', 'DESC');

foreach($collection as $product)
{


}
