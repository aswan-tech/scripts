<?php
set_time_limit(0);
require_once '/home/cloudpanel/htdocs/www.americanswan.com/current/app/Mage.php';
require_once '/home/cloudpanel/htdocs/www.americanswan.com/current/includes/config.php';

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
	$attr_brand = 'brand';
	$attr_cat='product_category';
	$attr_bestseller = 'inchoo_seller_product';
        foreach ($_products as $key => $_product) {
                Mage::app()->setCurrentStore(Mage_Core_Model_App::DISTRO_STORE_ID);
                Mage::app()->loadAreaPart(Mage_Core_Model_App_Area::AREA_FRONTEND, Mage_Core_Model_App_Area::PART_EVENTS);
                $product = Mage::getModel('catalog/product')->load($_product->getEntityId());
                if ($product->isSaleable()){
                /* product data */

                $confProd = Mage::getModel('catalog/product_type_configurable');
                $confAtts = $confProd->getConfigurableAttributesAsArray($product);
                $childProducts = $confProd->getUsedProducts(null,$product);
                $totalQty = '';$attr_bestsellervalue=null;
		$brand=null; $attr_category=null;
                foreach($childProducts as $child) {
                        $qty = $child->getStockItem()->getQty();
                        $totalQty = $totalQty + $qty;
                 }
                if($totalQty > 2){
                        $attributes_data = $product->getAttributes();
			foreach ($attributes_data as $attr) {    
				$attributeCode = $attr->getAttributeCode();
				if ($attributeCode == $attr_brand) 
					$brand = $attr->getFrontend()->getValue($product);
				else if ($attributeCode == $attr_cat) 
					$attr_category = $attr->getFrontend()->getValue($product);
				else if ($attributeCode == $attr_bestseller) 
				$attr_bestsellervalue = $attr->getFrontend()->getValue($product); 
				else continue;
			}
			
			if($attr_category!=null)
				$_productdata['title'] = "American Swan".' '.ucfirst(strtolower($attr_category)).' - '.$product->getName();
			else
				$_productdata['title'] = "American Swan".' - '.$product->getName();
				
                        $url = Mage::getBaseUrl().$product->getUrlPath();
                        $_productdata['link'] = $url;
                       $_productdata['description'] = 'Buy '.ucfirst(strtolower($brand)).' '.ucfirst(strtolower($attr_category)).' Online- '.$product->getDescription()." Shop Online Now!";
                        $_productdata['g:id'] = $product->getSku();
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

                        $_productdata['g:price'] = round(number_format($fprice, 2, null, ''));


                        if($product->getIsInStock() == 1){
                                $_productdata['g:availability'] = 'In Stock';
                        }else{
                                $_productdata['g:availability'] = 'Out Of Stock';
                        }
                        if($product->getPrice() == $fprice){
                                $_productdata['g:custom_label_0'] = 'New arrivals';
                        }else{
                                $_productdata['g:custom_label_0'] = 'Sale';
                        }
                        
						if($attr_bestsellervalue == 'Yes') {
							$_productdata['g:custom_label_1'] = 'Best Seller';
						}

                        $_productdata['g:image_link'] = Mage::helper('catalog/image')->init($product, 'image')->__toString();
                        $cats = $product->getCategoryIds();
                        $_catname = array();
                        $_parentcatname = array();
                        foreach ($cats as $category_id) {
                                $_cat = Mage::getModel('catalog/category')->load($category_id);
                                if($_cat->getLevel() == 3){
                                        $_catname[] = strtolower($_cat->getName());
                                }

                                if($_cat->getLevel() == 2){
                                        $_parentcatname[] = strtolower($_cat->getName());
                                }
                        }
/*
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
*/
                        if(in_array('men', $_parentcatname) || in_array('women', $_parentcatname)) {
                                $_productdata['g:google_product_category'] = 'Apparel & Accessories > Clothing';
                                $_productdata['g:product_type'] = 'Apparel & Accessories > Clothing > '.$product->getAttributeText('product_department').' '.$product->getAttributeText('product_category') ;
                        }
                        else if(in_array('footwear', $_parentcatname)) {
                                $_productdata['g:google_product_category'] = 'Apparel & Accessories > Shoes';
                                $_productdata['g:product_type'] = 'Apparel & Accessories > Shoes > '.$product->getAttributeText('product_department').' '.$product->getAttributeText('product_category') ;
                        }
                        else if(in_array('accessories', $_parentcatname)) {
                                $_productdata['g:google_product_category'] = 'Apparel & Accessories > Clothing Accessories';
                                $_productdata['g:product_type'] = 'Apparel & Accessories > Clothing Accessories > '.$product->getAttributeText('product_category');
                        }
                        else if(in_array('home', $_parentcatname)) {
                                $_productdata['g:google_product_category'] = 'Home & Garden > Linens & Bedding for Home Products';
                                $_productdata['g:product_type'] = 'Home & Garden > Linens & Bedding for Home Products > '.$product->getName();
                        }
                        else if(in_array('beauty', $_parentcatname)) {
                                $_productdata['g:google_product_category'] = 'Health & Beauty > Personal Care for Beauty Products';
                                $_productdata['g:product_type'] = 'Health & Beauty > Personal Care for Beauty Products > '.$product->getName();
                        }
                        else {
                                $_productdata['g:google_product_category'] = 'Apparel & Accessories > Clothing Accessories';
                                $_productdata['g:product_type'] = 'Apparel & Accessories > Clothing Accessories > '.$product->getAttributeText('product_department').' '.$product->getAttributeText('product_category');
                        }
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
                }
        }
        $XML =  $doc->saveXML();
        echo $XML;
        fclose($fp);
}catch(exception $e){
        pr("Exception Occured during XML Generation : ".$e);
}
/*scripts/googlePromotionFeed.php?catid=105*/
?>
