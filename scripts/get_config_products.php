<?php
ini_set('display_errors', 1);
set_time_limit(0);
ini_set('memory_limit', '-1');
require '/home/cloudpanel/htdocs/www.americanswan.com/current/app/Mage.php';
Mage::app();

$collection  =  Mage::getModel('catalog/product')->getCollection();
$collection->addAttributeToFilter('type_id','configurable');
echo '"SKU"'."\n";
foreach($collection as $product)
{
	$_product = Mage::getModel('catalog/product')->load($product->getId());
	$attrs  = $_product->getTypeInstance(true)->getConfigurableAttributesAsArray($_product);
	foreach($attrs as $attr) {
	   if(0 == strcmp("size", $attr['attribute_code'])) {
		   $options    = $attr['values'];
		   foreach($options as $option) {
			 if($option['pricing_value'] > 0) {
				 echo '"'.$_product->getSku().'"'."\n";
			 }
		   }
	   }
	}   
}
                
?>
