<?php
set_time_limit(0);
require_once '../app/Mage.php';
require_once '../includes/config.php';
umask(0);
$app = Mage::app('default');
$today = date("Ymd");
try{
	
	$myfile = 'yahooFeed.xml';
	$doc  = new DOMDocument('1.0');
	$doc->preserveWhiteSpace = false;
	$doc->formatOutput = true;
	$doc->load($myfile);
	$productsNode = $doc->createElement( "channel" );
	$doc->appendChild( $productsNode );
	$j = 0;
	$currentDate = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
	$currentDate = date("Y-m-d h:m:s", $currentDate);
	$storeId = 0;	
	$_products = Mage::getResourceModel('productreports/product_collection')
                ->addOrderedQtyCustom()
                ->addAttributeToSelect('entity_id','type_id','ordered_qty')
                ->setStoreId($storeId)
                ->addAttributeToFilter('type_id', 'configurable')
                ->addStoreFilter($storeId)
                ->setOrder('ordered_qty', 'desc');
	foreach ($_products as $key => $_product) {
		Mage::app()->setCurrentStore(Mage_Core_Model_App::DISTRO_STORE_ID);
		Mage::app()->loadAreaPart(Mage_Core_Model_App_Area::AREA_FRONTEND, Mage_Core_Model_App_Area::PART_EVENTS);
		$product = Mage::getModel('catalog/product')->load($_product->getEntityId());
		/* product data */
		$_productdata['title'] = $product->getName();			
		$url = Mage::getBaseUrl().$product->getUrlPath();
		$url = str_replace('/scripts/yahooPromotionFeed.php','',$url);
		$_productdata['link'] = $url;
		$_productdata['description'] = $product->getDescription();
		$_productdata['g:id'] = $product->getSku();
		$mpn = 'AS-'.$product->getSku();			
		$specialToDate = $product->getSpecialToDate();
		$specialFromDate = $product->getSpecialFromDate();
		if ($currentDate >= $specialFromDate && ($currentDate < $specialToDate || $specialToDate == "")) {
			$specialprice = $product->getSpecialPrice();
			if(isset($specialprice) && ($specialprice != '')){
				$fprice = $specialprice;
			}
		}else{
			$fprice = $product->getFinalPrice( );
		}
		
		$_productdata['g:price'] = number_format($fprice, 2, null, '');
		
		if($product->getIsInStock() == 1){
			$_productdata['g:availability'] = 'In Stock';
		}else{
			$_productdata['g:availability'] = 'Out Of Stock';
		}
		$_productdata['g:image_link'] = Mage::helper('catalog/image')->init($product, 'image')->__toString();
		$cats = $product->getCategoryIds();
		$_catname = '';
		$_parentcatname = '';
		foreach ($cats as $category_id) {
			$_cat = Mage::getModel('catalog/category')->load($category_id);
			if($_cat->getLevel() == 3){
				$_catname .= $_cat->getName().',';
			}
			
			if($_cat->getLevel() == 2){
				$_parentcatname .= $_cat->getName().',';
			}
		}
		if($_parentcatname != ''){
			$_productdata_category_name = substr($_parentcatname,0,strlen($_parentcatname)-1);
		}else{
			$_productdata_category_name = '';
		}
		if($_catname != ''){
			$_productdata_sub_category_name = substr($_catname,0,strlen($_catname)-1);
		}else{
			$_productdata_sub_category_name = '';
		}
		$_productdata['g:google_product_category'] = $_productdata_category_name.' > '.$_productdata_sub_category_name;
		$_productdata['g:product_type'] = $_productdata_category_name.' > '.$_productdata_sub_category_name.' > '.$product->getName();
		
		$productNode = $doc->createElement( "item" );
		$productsNode->appendChild( $productNode );
		foreach ($_productdata as $tag => $value) {
			$dataTag = $doc->createElement( $tag );
			$productNode->appendChild( $dataTag );
			if($tag == 'g:google_product_category' || $tag == 'g:product_type'){
				$valueTag = $doc->createCDATASection($value);
				$dataTag->appendChild( $valueTag );
			}else{
				$valueTag = $doc->createTextNode($value);
				$dataTag->appendChild( $valueTag );
			}
		}
		$dataShip = $doc->createElement( 'g:shipping' );
		$productNode->appendChild( $dataShip );
		$valueTagservice = $doc->createElement('g:service');
		$dataShip->appendChild( $valueTagservice );			
		$textService = $doc->createTextNode("Standard");
		$valueTagservice->appendChild($textService);
		$valueTagprice = $doc->createElement('g:price');
		$dataShip->appendChild( $valueTagprice );			
		$textPrice = $doc->createTextNode("0.0");
		$valueTagprice->appendChild($textPrice);
		$dataCondition = $doc->createElement( 'g:condition' );
		$productNode->appendChild( $dataCondition );
		$dataConditionValue = $doc->createTextNode("new");
		$dataCondition->appendChild($dataConditionValue);
		$dataBrand = $doc->createElement( 'g:brand' );
		$productNode->appendChild( $dataBrand );
		$dataBrandValue = $doc->createTextNode("American Swan");
		$dataBrand->appendChild($dataBrandValue);
		$dataMpn = $doc->createElement( 'g:mpn' );
		$productNode->appendChild( $dataMpn );
		$dataMpnValue = $doc->createTextNode($mpn);
		$dataMpn->appendChild($dataMpnValue);
		$dataGender = $doc->createElement( 'g:gender' );
		$productNode->appendChild( $dataGender );
		$dataGenderValue = $doc->createTextNode($product->getAttributeText('gender'));
		$dataGender->appendChild($dataGenderValue);
		$_products->removeItemByKey($key);
	}
	$doc->save($myfile);
	echo "Done";
}catch(exception $e){
	pr("Exception Occured during XML Generation : ".$e);
}
/*scripts/yahooPromotionFeed.php?catid=105*/