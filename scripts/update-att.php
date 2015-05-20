<?php
require_once '/home/cloudpanel/htdocs/www.americanswan.com/current/app/Mage.php';
umask(0);
Mage::app();

$fp = fopen('season.csv','r') or die("can't open file");
$i = 1;
$att = "season";
while($line = fgetcsv($fp,1024))
{
	$sku = $line[0];
	$code = trim($line[1]);
	if(!empty($code))
	{
		$productId = Mage::getModel('catalog/product')->getIdBySku(trim($sku));
		if($productId) {
			$attribute_details = Mage::getSingleton("eav/config")->getAttribute('catalog_product', $att);
			$optionId = (int)$attribute_details->getSource()->getOptionId($code);
			if($optionId) {
				$_resource = Mage::getSingleton('catalog/product')->getResource();
				$optionValue = $_resource->getAttributeRawValue($productId,  $att, Mage::app()->getStore());
				if(!$optionValue) {
						Mage::getSingleton('catalog/product_action')->updateAttributes(array($productId), array($att=> $optionId), 1);
						echo "Updated [$sku] with $line[1]\n";
				}
				else {
						echo "Already [$sku] with $line[1]\n";
				}
			}
			else {
					echo "some error\n";
			}
		}
	}
	else
	{
		   echo "Att not present for $sku\n";
	}
	$i++;
}
?>
