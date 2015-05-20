<?php
require_once '/home/cloudpanel/htdocs/www.americanswan.com/current/app/Mage.php';
umask(0);
Mage::app();

$color_map = array('Yellow' => '734','White'=>'733','Silver'=>'732','Red'=>'731','Purple'=>'730','Pink'=>'729','Orange'=>'728','Multi'=>'727','Grey'=>'726','Green'=>'725','Brown'=>'724','Blue'=>'723','Black'=>'722','Beige'=>'721');

$fp = fopen('colors_final.csv','r') or die("can't open file");
while($line = fgetcsv($fp,1024)) 
{
	$sku = $line[0];
	$pcolor = ucfirst(strtolower($line[1]));
	$scolor = ucfirst(strtolower($line[2]));
	if(isset($pcolor))
	{
		//$product = Mage::getModel('catalog/product')->loadBySku($sku);
		$id = Mage::getModel('catalog/product')->getIdBySku(trim($sku));
		$color = Mage::getResourceModel('catalog/product')->getAttributeRawValue($id, 'colors',1);

		if(isset($scolor)) $check = $color_map[$pcolor].",".$color_map[$scolor] ;
		else $check =  $color_map[$pcolor];

		if($check != $color)
		{
			Mage::getSingleton('catalog/product_action')->updateAttributes(array($id), array('colors'=> $color_map[$pcolor].','.$color_map[$scolor]), 1);
			echo "Updated $sku with $line[1] , $line[2]\n";
		}
		else
		{
			echo "Already Updated for $sku, skipping\n";
		}
	}
	else
	{
		echo "Color not present for $sku\n";
	}

}
