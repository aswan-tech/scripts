<?php

ini_set("display_errors", 1);
include '/home/cloudpanel/htdocs/www.americanswan.com/current/app/Mage.php';
Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

//mens topwear - 344
$men_cat = array('Casual Shirts'=>'86','T-shirts'=>'101','Polos'=>'94','PullOvers'=>'100','Sweat Shirts'=>'99','Blazers'=>'92','Jackets'=>'83');
//womens topwear - 349 	
$women_cat = array('Casual Shirts'=>'125','Dresses'=>'251','T-shirts'=>'146','Polos'=>'138','PullOvers'=>'144','Sweat Shirts'=>'143','Jackets'=>'133');

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
	if(in_array('344',$cats)) echo "already present \n";
	else
	{
	 $count++;
	 $cats[] = '344'; 
	 $product->setCategoryIds($cats);
	 echo "saving category for ".$product->getSku()."\n"; 
	 $product->save();
	}
}
echo $count. " products updated for $category \n ";
}
