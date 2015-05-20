<?php
set_time_limit(0);
require_once '../app/Mage.php';
require_once '../includes/config.php';
umask(0);
$app = Mage::app('default');
$cat_id = Mage::getSingleton('core/app')->getRequest()->getParam('catid');
$catName = Mage::getModel('catalog/category')->load($cat_id)->getName();
$today = date("Ymd");
try{
	header("Content-type: text/csv");  
	header("Cache-Control: no-store, no-cache");  
	header('Content-Disposition: attachment; filename="'.$catName.$today.'-product-feed-yahoo.xml"');
	$fp = fopen("php://output", 'w');
	$doc  = new DOMDocument('1.0', 'utf-8');
	$doc->formatOutput = true;
	$rssNode = $doc->createElement( "rss" );
	$doc->appendChild( $rssNode );
	$gna = $doc->createAttribute("xmlns:g");
	$rssNode->appendChild($gna);
	$gnaValue = $doc->createTextNode("http://base.google.com/ns/1.0"); 
	$gna->appendChild($gnaValue);
	$gnaVer = $doc->createAttribute("version");
	$rssNode->appendChild($gnaVer);
	$gnaVerValue = $doc->createTextNode("2.0"); 
	$gnaVer->appendChild($gnaVerValue);
	$productsNode = $doc->createElement( "channel" );
	$rssNode->appendChild( $productsNode );
	$titleNode = $doc->createElement( "title" );
	$productsNode->appendChild( $titleNode );
	$dataTitleValue = $doc->createTextNode($catName);
	$titleNode->appendChild($dataTitleValue);
	$j = 0;
	$currentDate = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
	$currentDate = date("Y-m-d h:m:s", $currentDate);			
	$_products = Mage::getModel('catalog/product')->getCollection()->addAttributeToFilter('type_id','configurable')->addAttributeToFilter('status',1);
	$_products->addCategoryFilter(Mage::getModel('catalog/category')->load($cat_id)); 
	foreach ($_products as $key => $_product) {
		Mage::app()->setCurrentStore(Mage_Core_Model_App::DISTRO_STORE_ID);
		Mage::app()->loadAreaPart(Mage_Core_Model_App_Area::AREA_FRONTEND, Mage_Core_Model_App_Area::PART_EVENTS);
		$product = Mage::getModel('catalog/product')->load($_product->getEntityId());
		/* product data */
		$_productdata['title'] = $product->getName();			
		$url = Mage::getBaseUrl().$product->getUrlPath();
		$url = str_replace('/scripts/cpaPromotionFeedCat.php','',$url);
		$_productdata['link'] = $url;
		//$_productdata['description'] = $product->getDescription();
		$_productdata['g:id'] = $product->getId();
		$mpn = 'AS-'.$product->getSku();	
		$specialToDate = $product->getSpecialToDate();
		$specialFromDate = $product->getSpecialFromDate();
		if ($currentDate >= $specialFromDate && ($currentDate < $specialToDate || $specialToDate != "")) {
			$specialprice = $product->getSpecialPrice();
			if(isset($specialprice) && ($specialprice != '')){
				$fprice = $specialprice;
			}
		}else{
			$_finalPrice = Mage::helper('tax')->getPrice($product, $product->getFinalPrice());
			if($_finalPrice){ $fprice = $_finalPrice; }else{ $fprice = $product->getFinalPrice( );}
		}
		$_productdata['g:price'] = number_format($fprice, 2, null, '');
		$_productdata['strike_off_price'] = number_format($product->getPrice(), 2, null, '');
		$_savePercent = 100 - round(($fprice / $product->getPrice())*100);
		$_productdata['discount_percentage'] = $_savePercent.'%';
		if($product->getIsInStock() == 1){
			$_productdata['g:availability'] = 'In Stock';
		}else{
			$_productdata['g:availability'] = 'Out Of Stock';
		}
		$_productdata['g:image_link'] = Mage::helper('catalog/image')->init($product, 'image')->__toString();
		$cats = $product->getCategoryIds();
		$_catname = '';
		$_parentcatname = '';
		$_catid = '';
		foreach ($cats as $category_id) {
			$_cat = Mage::getModel('catalog/category')->load($category_id);
			if($_cat->getLevel() == 3 ){
				$_catname = $_cat->getName();
				$_catid = $_cat->getId();
			}
			if($_cat->getLevel() == 2 && $category_id == $cat_id){
				$_parentcatname = $_cat->getName();
				$_parentcatid = $_cat->getId();
			}
		}
		
		/*if($_catname != ''){
			$_productdata_sub_category_name = substr($_catname,0,strlen($_catname)-1);
		}else{
			$_productdata_sub_category_name = '';
		}
		if($_catid != ''){
			$_catids = substr($_catid,0,strlen($_catid)-1);
		}else{
			$_catids = '';
		}*/
		
		
		$_productdata['category_id'] = $_parentcatid;
		$_productdata['sub_category_id'] = $_catid;
		$_productdata['category_name'] = $_parentcatname;
		$_productdata['sub_category_name'] = $_catname;
		
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
	$XML =  $doc->saveXML();
echo $XML;
fclose($fp);
}catch(exception $e){
	pr("Exception Occured during XML Generation : ".$e);
}
/*scripts/yahooKomliPromotionFeed.php?catid=4*/