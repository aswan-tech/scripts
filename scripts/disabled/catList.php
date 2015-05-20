<?php 
$mageFilename = '../app/Mage.php';
require_once $mageFilename;
ini_set('display_errors', 1);
Mage::app();
$categories = Mage::getModel('catalog/category') ->getCollection()->addAttributeToSelect('*')->addIsActiveFilter()->addOrderField('name');
foreach($categories as $category) {	
echo "<pre>";
echo $category['entity_id'].'<===>'.$category['name'];
echo "</pre>";
}


