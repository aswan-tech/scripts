<?php
require_once '/home/cloudpanel/htdocs/www.americanswan.com/current/app/Mage.php';
umask(0);
Mage::app();

$fp = fopen('389_products.csv','r') or die("can't open file");
$i = 1;
while($line = fgetcsv($fp,1024))
{
	$sku = $line[0];
	$sizechartCode = 'gsof';
	if(!empty($sizechartCode))
	{
		$productId = Mage::getModel('catalog/product')->getIdBySku(trim($sku));
		if($productId) {
			$attribute_details = Mage::getSingleton("eav/config")->getAttribute('catalog_product', 'highlighters');
			$optionId = (int)$attribute_details->getSource()->getOptionId($sizechartCode);
			if($optionId) {
				$_resource = Mage::getSingleton('catalog/product')->getResource();
				$optionValue = $_resource->getAttributeRawValue($productId,  'highlighters', Mage::app()->getStore());
				if(!$optionValue) {
						Mage::getSingleton('catalog/product_action')->updateAttributes(array($productId), array('highlighters'=> $optionId), 1);
						echo "Updated [$sku] with $line[1]\n";
				}
				else {
						echo "Already [$sku] with $line[1]\n";
				}
			}
			else {
					echo "Highlighter option [$sizechartCode] not available for $sku\n";
			}
		}
	}
	else
	{
		   echo "Highlighter not present for $sku\n";
	}
	$i++;
}
?>
