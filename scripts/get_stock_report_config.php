<?php
ini_set('display_errors', 1);
set_time_limit(0);
ini_set('memory_limit', '-1');
require '/home/cloudpanel/htdocs/www.americanswan.com/current/app/Mage.php';
Mage::app();

$type_id = 'configurable';
//$type_id = 'simple';

$db = Mage::getSingleton('core/resource')->getConnection('core_write');
$collection = Mage::getModel('catalog/product')->getCollection()
							->addAttributeToSelect('*')
							//->setPageSize(200)
							//->setCurPage(1)
							->addAttributeToFilter('type_id', array('eq' => $type_id));

//$count = $collection->getSize();
$filename = $type_id."_stock.csv";

echo ("\"ProductName\",\"Category\",\"Subcategories\",\"ConfigSKU\",\"SimpleSKU\",\"Color\",\"Colors\",\"Gender\",\"Brand\",\"Collections\",\"Season\",\"Size\",\"EAN\",\"Price\",\"SpecialPrice\",\"Discount\",\"AvaiableStock\",\"Department\",\"Category\",\"sizechart\"\n");
	
	$dataArr = array();												
	foreach ($collection as $product) {
		$_product = Mage::getModel('catalog/product')->load($product['entity_id']);
		$product_department = $_product->getAttributeText('product_department');
		$product_category = $_product->getAttributeText('product_category');
		$sizechart = $_product->getAttributeText('sizechart');
		//get category and sub category
		$categoryIds = $_product->getCategoryIds();
		$CatArr = array();	
		$subCatArr = array();
		foreach($categoryIds as $key=>$catId) {
			$category = Mage::getModel('catalog/category')->setStoreId(Mage::app()->getStore()->getId())->load($catId);
			if($key == 0) {
				$CatArr[] = $category->getName();
			}
			else {
				$subCatArr[] = $category->getName();
			}
		}
		
		$dataArr['name'] = $_product->getName();
		$dataArr['catname'] = implode($CatArr, ", " );
		$dataArr['SubCategory'] = implode($subCatArr, ", " );
		
		if($product->getTypeId() == 'configurable') {
			$dataArr['configSKU'] = $_product->getSku();
			$dataArr['simpleSKU'] = null;
		}
		else if($product->getTypeId() == 'simple') {
			$parentIds = Mage::getResourceSingleton('catalog/product_type_configurable')
							  ->getParentIdsByChild($_product->getId());
			$__product = Mage::getModel('catalog/product')->load($parentIds[0]);
			
			$dataArr['configSKU'] = $__product->getSku();
			$dataArr['simpleSKU'] = $_product->getSku();
		}
		
		// color
		$colorAttr = $_product->setStoreFilter(0)->getResource()->getAttribute("color");
		$_options = $colorAttr->getSource()->getAllOptions(true, true);
		foreach($_options as $option) {
			if ($option['value'] == $_product->getData('color')) {
				$color_label = $option['label'];
			}
		}
		
		// colors		
		$colorsAttr = $_product->setStoreFilter(0)->getResource()->getAttribute("colors");		
		$_options = $colorsAttr->getSource()->getAllOptions(true, true);
		
		foreach($_options as $option) {
			if ($option['value'] == $_product->getData('colors')) {
				$colors_label = $option['label'];
			}
		}
		
		// size
		$sizeAttr = $_product->setStoreFilter(0)->getResource()->getAttribute("size");
		$_options = $sizeAttr->getSource()->getAllOptions(true, true);
		
		foreach($_options as $option) {
			if ($option['value'] == $_product->getData('size')) {
				$size_label = $option['label'];
			}
		}
		
		// brand
		$brandAttr = $_product->setStoreFilter(0)->getResource()->getAttribute("brand");		
		$_options = $brandAttr->getSource()->getAllOptions(true, true);
		
		foreach($_options as $option) {
			if ($option['value'] == $_product->getData('brand')) {
				$brand_label = $option['label'];
			}
		}
		
		// gender
		$genderAttr = $_product->setStoreFilter(0)->getResource()->getAttribute("gender");
		$_options = $genderAttr->getSource()->getAllOptions(true, true);
		
		foreach($_options as $option) {
			if ($option['value'] == $_product->getData('gender')) {
				$gender_label = $option['label'];
			}
		}
				
		// season
		$seasonAttr = $_product->setStoreFilter(0)->getResource()->getAttribute("season");
		$_options = $seasonAttr->getSource()->getAllOptions(true, true);
		
		foreach($_options as $option) {
			if ($option['value'] == $_product->getData('season')) {
				$season_label = $option['label'];
			}
		}
		
		// collections		
		$collectionsAttr = $_product->setStoreFilter(0)->getResource()->getAttribute("collections");		
		$_options = $collectionsAttr->getSource()->getAllOptions(true, true);
		
		foreach($_options as $option) {
			if ($option['value'] == $_product->getData('collections')) {
				$collections_label = $option['label'];
			}
		}	
	
		// discount
		$discountAttr = $_product->setStoreFilter(0)->getResource()->getAttribute("discount");
		$_options = $discountAttr->getSource()->getAllOptions(true, true);
		foreach($_options as $option) {
			if ($option['value'] == $_product->getData('discount')) {
				$discount_label = $option['label'];
			}
		}
	
		$dataArr['color'] = $color_label;
		$dataArr['colors'] = (is_array($colors_label) ? $colors_label[0] : $colors_label);
		$dataArr['gender'] = $gender_label;
		$dataArr['brand'] = $brand_label;
		$dataArr['collections'] = $collections_label;
		$dataArr['season'] = $season_label;
		$dataArr['size'] = $size_label;
		$dataArr['ean'] = $_product->getEan();		
		$dataArr['price'] = (int)$_product->getPrice();
		$dataArr['specialprice'] = (int)$_product->getSpecialPrice();
		$dataArr['stock'] = (int)$_product->getStockItem()->getQty();
		$dataArr['department'] = $product_department;
		$dataArr['category'] = $product_category;
		$dataArr['sizechart'] = $sizechart;
		
		echo '"'.$dataArr['name'].'","'.$dataArr['catname'].'","'.$dataArr['SubCategory'].'","'.$dataArr['configSKU'].'","'.$dataArr['simpleSKU'].'","'.$dataArr['color'].'","'.$dataArr['colors'].'","'.$dataArr['gender'].'","'.$dataArr['brand'].'","'.$dataArr['collections'].'","'.$dataArr['season'].'","'.$dataArr['size'].'","'.$dataArr['ean'].'","'.$dataArr['price'].'","'.$dataArr['specialprice'].'","'.$discount_label.'","'.$dataArr['stock'].'","'.$dataArr['department'].'","'.$dataArr['category'].'","'.$dataArr['sizechart'].'"'."\n";
	}
?>
