<?php
set_time_limit(0);
require_once '/home/cloudpanel/htdocs/www.americanswan.com/current/app/Mage.php';
Mage::app()->setCurrentStore(Mage_Core_Model_App::DISTRO_STORE_ID);
Mage::app()->loadAreaPart(Mage_Core_Model_App_Area::AREA_FRONTEND, Mage_Core_Model_App_Area::PART_EVENTS);
umask(0);
$app = Mage::app('default');
$core_read = Mage::getSingleton('core/resource')->getConnection('core_read');
$core_write = Mage::getSingleton('core/resource')->getConnection('core_write');
$_parentcatnameArr = array('6'=>'men', '8'=>'women', '4'=>'footwear', '3'=>'accessories', '7'=>'beauty', '5'=>'home');

foreach($_parentcatnameArr as $cat_id=>$catname) {
		$_parentcatname = array($_parentcatnameArr[$cat_id]);
		try{
			$_products = Mage::getModel('catalog/product')->getCollection();
			$_products->addAttributeToSelect(array('name'));
			$_products->getSelect()->join(array('c'=>'catalog_category_product'),'c.product_id = e.entity_id',  array());
			$_products->getSelect()->join(array('pr'=>'catalog_product_relation'),'pr.parent_id = e.entity_id',  array('count( pr.child_id ) AS totSimpleProd'));
			$_products->getSelect()->join(array('st'=>'cataloginventory_stock_item'),'(pr.child_id = st.product_id AND st.is_in_stock = 1)',  array());
			$_products->getSelect()->where("c.category_id = '$cat_id'");
			$_products->getSelect()->group("e.entity_id");
			$_products->getSelect()->having("totSimpleProd >=2");
			$_products->addAttributeToFilter('type_id','configurable');
			
			#echo $_products->getSelect()->__toString();die;
			#var_dump($_products->count());
			#echo "<pre>";
			
			#$taxHelper = Mage::helper('tax');
			
			$query = "update promotion_feed set availability = 'Out Of Stock' where 1;";
			$result = $core_write->query($query);
			
			foreach ($_products as $key => $_product) {
				$product = Mage::getModel('catalog/product')->load($_product->getData('entity_id'));
				$brand = $product->getAttributeText('brand');
				$product_category = $product->getAttributeText('product_category');
				$attr_bestsellervalue = $product->getAttributeText('inchoo_seller_product');
				
				if(isset($_parentcatnameArr[$cat_id]) && !empty($_parentcatnameArr[$cat_id])) {
					$product_department = $_parentcatnameArr[$cat_id];
				}
				else {
					$product_department = $product->getAttributeText('product_department');
				}
				if($product_category!=null) {
					$title = "American Swan".' '.ucfirst(strtolower($product_category)).' - '.$product->getName();
				}
				else {
					$title = "American Swan".' - '.$product->getName();
				}
				
				$link = Mage::getBaseUrl().$product->getUrlPath();
				$description = 'Buy '.ucfirst(strtolower($brand)).' '.ucfirst(strtolower($product_category)).' Online- '.$product->getDescription()." Shop Online Now!";
				
				$fprice = 0;
				/*
				$specialToDate = $product->getSpecialToDate();
				$specialFromDate = $product->getSpecialFromDate();
				
				if ($currentDate >= $specialFromDate && ($currentDate < $specialToDate || $specialToDate != "")) {
					$specialprice = $product->getSpecialPrice();
					if(isset($specialprice) && ($specialprice != '')){
						$fprice = $specialprice;
					}
				}else{
					$_finalPrice = $taxHelper->getPrice($product, $product->getFinalPrice());
					if($_finalPrice){ $fprice = $_finalPrice; }else{ $fprice = $product->getFinalPrice();}
				}
				*/
				
				$fprice = (int)$product->getFinalPrice();
				$price = round(number_format($fprice, 2, null, ''));
				
				$availability = ( $product->getIsInStock() == 1 ? 'In Stock' : 'Out Of Stock');
				$custom_label_0 = ((int)$product->getPrice() == $fprice ? 'New arrivals' : 'Sale' );
						   
				if($attr_bestsellervalue == 'Yes') {
					$custom_label_1 = 'Best Seller';
				}
				
				$image_link = Mage::helper('catalog/image')->init($product, 'image')->__toString();
				
				
				/*$cats = $product->getCategoryIds();
				$_parentcatname = array();
				foreach ($cats as $category_id) {
					$_cat = Mage::getModel('catalog/category')->load($category_id);
					if($_cat->getLevel() == 2){
						$_parentcatname[] = strtolower($_cat->getName());
					}
				}
				*/
				
				if(in_array('men', $_parentcatname) || in_array('women', $_parentcatname)) {
					$google_product_category = 'Apparel & Accessories > Clothing';
					$product_type = 'Apparel & Accessories > Clothing > '.$product_department.' '.$product_category ;
				}
				else if(in_array('footwear', $_parentcatname)) {
					$google_product_category = 'Apparel & Accessories > Shoes';
					$product_type = 'Apparel & Accessories > Shoes > '.$product_department.' '.$product_category ;
				}
				else if(in_array('accessories', $_parentcatname)) {
					$google_product_category = 'Apparel & Accessories > Clothing Accessories';
					$product_type = 'Apparel & Accessories > Clothing Accessories > '.$product_category;
				}
				else if(in_array('home', $_parentcatname)) {
					$google_product_category = 'Home & Garden > Linens & Bedding for Home Products';
					$product_type = 'Home & Garden > Linens & Bedding for Home Products > '.$product->getName();
				}
				else if(in_array('beauty', $_parentcatname)) {
					$google_product_category = 'Health & Beauty > Personal Care for Beauty Products';
					$product_type = 'Health & Beauty > Personal Care for Beauty Products > '.$product->getName();
				}
				else {
					$google_product_category = 'Apparel & Accessories > Clothing Accessories';
					$product_type = 'Apparel & Accessories > Clothing Accessories > '.$product_department.' '.$product_category;
				}
							
				$mpn = 'AS-'.$product->getSku();
				$query = "select feed_id from promotion_feed where sku = '".$product->getSku()."' and category_id = '$cat_id'";
				$result = $core_read->query($query);
				$skuData = $result->fetch();
				$feed_id = (int)$skuData['feed_id'];
				$title = str_replace(array('"', "'"), "", $title);
				$description = str_replace(array('"', "'"), "", $description);
                                
				$data = "category_id = '$cat_id',
								category_name = '$catname',
								feedtype = 'googlePromotionFeed', 
								title = '".stripcslashes($title)."',
								product_url = '$link', 
								`description` = '".stripcslashes($description)."', 
								`sku` = '".$product->getSku()."', 
								`price` = '$price', 
								`availability` = '$availability', 
								`custom_label_0` = '$custom_label_0', 
								`image_link` = '$image_link', 
								`google_product_category` = '$google_product_category', 
								`product_type` = '$product_type', 
								`shipping_service` = 'Standard', 
								`shipping_price` = '0.0', 
								`condition` = 'new', 
								`brand` = '$brand', 
								`mpn` = '".$mpn."', 
								`gender` = '".$product->getAttributeText('gender')."'";
				
				if($feed_id) {
					$query = "UPDATE promotion_feed SET	".$data." WHERE feed_id = '$feed_id'";
					$logText = "Updated";
				}
				else {
					$query = "INSERT INTO promotion_feed SET ".$data."";
					$logText = "Added";
				}
				
				if($core_write->query($query)) {
					Mage::log("SKU: ".$product->getSku()." ".$logText, Zend_Log::ERR, 'googlePromotionFeed.log');
				}
			}
	}
	catch(exception $e){
		Mage::log("SKU: ".$product->getSku()." failed, ".$query, Zend_Log::ERR, 'googlePromotionFeed.log');
		Mage::log("Exception Occured during XML Generation : ".$e, Zend_Log::ERR, 'googlePromotionFeed.log');
	}
}
?>
