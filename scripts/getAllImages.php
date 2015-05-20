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

echo "sku,Product Url\n";
foreach($productCollection as $product){
	if($product->getId())
	{
		echo $product->getSku().",";
		echo $product->getProductUrl().",";
		$product->load('media_gallery');
		$mediaGallery = $product->getMediaGalleryImages();
		foreach($mediaGallery as $image)
		{
			echo Mage::helper('catalog/image')->init($product, 'thumbnail',$image->getFile()).",";	
		}
		echo "\n";
	}
	else 
	{
		echo "Some Error:";
	}

}


