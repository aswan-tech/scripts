<?php
require_once '/home/cloudpanel/htdocs/www.americanswan.com/current/app/Mage.php';
umask(0);
Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

//men - 6
//women - 8

$department = array('men'=> '6','women' => '8','footwear'=>'4','home'=>'5','accessories'=>'3','beauty'=>'7'); 
$attr = array('6'=> '390','8' => '391','4'=>'388','5'=>'389','3'=>'385','7'=>'386'); 

foreach($department as $dept => $value)
{
echo "Loading products for department $dept \n";

$collection = Mage::getModel('catalog/category')->load($value)
		->getProductCollection()
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('type_id', 'configurable')
                ->addAttributeToSort('entity_id', 'DESC');



$count = 0;

foreach($collection as $product)
{
        echo $product->getId();
	if($product->getProductDepartment() == $attr[$value])
	{
		echo " already updated. \n";
	}
	else
	{
		Mage::getSingleton('catalog/product_action')->updateAttributes(array($product->getId()), array('product_department'=> $attr[$value]), 1);
		echo " updated \n";
		$count ++;
	}

		
}

echo $count. " products tagged for department  $dept \n";
}
