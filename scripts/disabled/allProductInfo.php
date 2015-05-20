<?php
$mageFilename = '../app/Mage.php';
require_once $mageFilename;
ini_set('display_errors', 1);
Mage::app();

header('Content-Type: text/csv; charset=utf-8');
header("Content-Disposition: attachment; filename=allProductInfo.csv");
header("Pragma: no-cache");
header("Expires: 0");
$outstream = fopen("php://output", "w");
fputcsv($outstream, array("Name", "Type", "Config Sku", "Simple Sku", "Color", "Size", "Tax Class", "Gender", "Price", "Special Price", "Final Price", "Special Price From Date", "Special Price To Date", "Premium Packaging SKU", "Department", "Categories", "Category Ids", "Stock Availablity", "Qty", "Visibility", "Status", "Item Creation Date", "New Arrival From Date", "New Arrival To Date", "Content", "Image", "Gallery"),chr(44),'"'); 

$collection = Mage::getModel('catalog/product')
						->getCollection()						
						->addAttributeToSelect('*');

$model = Mage::getModel('catalog/product');
$result = array(); 
foreach ($collection as $_product) {
    $product = $model->load( $_product->getId() );	
	$typeId =  $_product->getTypeId();
	$configSku = ""; 
	if ($typeId == "configurable") {
		$configSku = $product->getSku();
	} else if ($typeId == "simple") {
		$simpleProductId = $_product->getId();
		$objConfigProduct = Mage::getModel('catalog/product_type_configurable');
		$arrConfigProductIds = $objConfigProduct->getParentIdsByChild($simpleProductId);
		$configSkuArr = array();
		if (is_array($arrConfigProductIds)) {
			foreach($arrConfigProductIds as $sid) {
				$pr = Mage::getModel('catalog/product')->load($sid);
				$configSkuArr[] = $pr->getSku();
			}
		}
		if (count($configSkuArr) > 0) {
			$configSku = implode(", ", $configSkuArr);
		}
	}
	if ($typeId == "simple") {
		$productSku = $product->getSku();
	} else { $productSku = ''; }
	if($product->getIsInStock() == 1){ $stockstatus = 'Yes'; } else { $stockstatus = 'No'; }
	$qty = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product)->getQty();
	if($qty == 0){
		if($typeId == 'configurable') $qty =  '-';
		else $qty = $qty;
	}else{ $qty = $qty; }
	/*For category name*/	
	if (!empty($product)) {
		$category = "";
		$department = "";
		$cats = $_product->getCategoryIds();
		$catIds = implode( ',' , $cats );
		$cnames = array();
		$dnames = array();
		foreach ($cats as $category_id) {
			$_cat = Mage::getModel('catalog/category')->load($category_id) ;
			if ($_cat->getLevel() > 2) {
				$cnames[] = $_cat->getName();
			}
			if ($_cat->getLevel() == 2) {
				$dnames[] = $_cat->getName();
			}
		}
		if (count($cnames) > 0) {
			$category = implode(",", $cnames);
		}
		if (count($dnames) > 0) {
			$department = implode(",", $dnames);
		}
	}
	/*For content check*/
	$count = 0;
	if($product->getDescription() != ''){ $count++;	}	
	if($product->getShortDescription() != ''){ $count++; }	
	if($product->getAsStylingTip() != ''){ $count++; }	
	if($product->getAboutLecomCollection() != ''){ $count++; }	
	if($product->getDelivery() != ''){ $count++; }	
	if($product->getInfoCare() != ''){ $count++; }	
	if($product->getReturns() != ''){ $count++;	}	
	if($count < 7){ $content = 'No'; } else { $content = 'Yes';	}
	/*For images check*/
	$countImg = 0;
	$imgArr = array('bmp','gif','jpg','jpeg','png','tif');
	$extImg = substr($product->getImage(), (strrpos($product->getImage(), '.')+1));
	$extSmlImg = substr($product->getSmallImage(), (strrpos($product->getSmallImage(), '.')+1));
	$extThumbImg = substr($product->getThumbnail(), (strrpos($product->getThumbnail(), '.')+1));
	$imgUrl = Mage::getBaseDir('media').DS.'catalog'.DS.'product'.DS;
	if($product->getImage()!='' && in_array(strtolower($extImg), $imgArr) && file_exists($imgUrl.$product->getImage())){ $countImg++; }
	if($product->getSmallImage()!='' && in_array(strtolower($extSmlImg), $imgArr) && file_exists($imgUrl.$product->getSmallImage())){ $countImg++; }
	if($product->getThumbnail()!='' && in_array(strtolower($extThumbImg), $imgArr) && file_exists($imgUrl.$product->getThumbnail())){ $countImg++; }
	if($countImg < 3){ $Img = 'No';	} else { $Img = 'Yes'; }
	
	fputcsv($outstream, array($product->getName(), $_product->getTypeId(), $configSku, $productSku, $product->getAttributeText('color'), $product->getAttributeText('size'), $_product->getAttributeText('tax_class_id'), $product->getAttributeText('gender'), number_format($product->getPrice(), 2), number_format($product->getSpecialPrice(), 2), number_format($_product->getFinalPrice(), 2),  $product->getSpecialFromDate(), $product->getSpecialToDate(), $product->getPremiumPackagingSku(), $department, $category, $catIds, $stockstatus, ( int ) $qty, $_product->getAttributeText('visibility'), $_product->getAttributeText('status'), $product->getCreatedAt(), $product->getNewsFromDate(),  $product->getNewsToDate(), $content, $Img, $product->getGallery() ),chr(44),'"');
}
fclose($outstream);

?>