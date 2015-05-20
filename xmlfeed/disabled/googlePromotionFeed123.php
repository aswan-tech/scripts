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
	header('Content-Disposition: attachment; filename="'.$catName.$today.'-product-feed-cat.xml"');
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
	foreach ($_products as $key => $product) {
		$product = Mage::getModel('catalog/product')->load($product->getEntityId());
		/* product data */
		$_productdata['title'] = $product->getName();			
		$url = Mage::getBaseUrl().$product->getUrlPath();
		$url = str_replace('/scripts/cpaPromotionFeedCat.php','',$url);
		$_productdata['link'] = $url;
		$_productdata['description'] = $product->getDescription();
		$_productdata['g:id'] = $product->getSku();
		$mpn = 'AS-'.$product->getSku();
		$_productdata['g:price'] = number_format($product->getFinalPrice(), 2, null, '');
		$specialToDate = $product->getSpecialToDate();
		$specialFromDate = $product->getSpecialFromDate();
		if ($currentDate >= $specialFromDate && ($currentDate < $specialToDate || $specialToDate == "")) {
			$specialprice = $product->getSpecialPrice();
			if(isset($specialprice) && ($specialprice != '')){
				$_productdata['special_price'] = $specialprice;
			}
		}
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
	$XML =  $doc->saveXML();
echo $XML;
fclose($fp);
}catch(exception $e){
	pr("Exception Occured during XML Generation : ".$e);
}
/*scripts/googlePromotionFeed.php?catid=105*/