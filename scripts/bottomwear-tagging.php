<?php

ini_set("display_errors", 1);
include '/home/cloudpanel/htdocs/www.americanswan.com/current/app/Mage.php';
Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

//mens bottomwear - 345
$men_cat = array('Casual Trousers'=>'87','Denim'=>'88','TrackPants'=>'103','Shorts'=>'96','Sweat Pants'=>'98');
//womens bottomwear - 348 	
$women_cat = array('Casual Trousers'=>'126','Denim'=>'127','TrackPants'=>'148','Shorts'=>'253','Sweat Pants'=>'142','Leggings' => '392');

//run script two times one for menstowear and again for women topwear bu changing the category ids

foreach($men_cat as $category => $catid)
{

$collection = Mage::getModel('catalog/category')->load($catid)
                ->getProductCollection()
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('type_id', 'configurable')
//                ->addAttributeToFilter('status', array('eq' =>  Mage_Catalog_Model_Product_Status::STATUS_ENABLED))
                ->addAttributeToSort('entity_id', 'DESC');

echo "Getting all products for $category\n";
$count = 0;
foreach ($collection as $product)
{
	$cats = $product->getCategoryIds();
	if(in_array('345',$cats)) echo "already present \n";
	else
	{
	 $count++;
	 $cats[] = '345'; 
	 $product->setCategoryIds($cats);
	 echo "saving category for ".$product->getSku()."\n"; 
	 $product->save();
	}
}
echo $count. " products updated for $category \n ";
}
