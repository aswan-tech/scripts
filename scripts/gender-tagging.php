<?php
require_once '/home/cloudpanel/htdocs/www.americanswan.com/current/app/Mage.php';
umask(0);
Mage::app();//->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

//men - 6
//women - 8

$department = array('men'=> '6','women' => '8'); 

foreach($department as $dept => $value)
{
echo "Loading products for department $dept \n";

$collection = Mage::getModel('catalog/category')->load($value)
		->getProductCollection()
                ->addAttributeToSelect('*')
                ->addAttributeToSort('entity_id', 'DESC');

if($value == 6) $update = '320';
if($value == 8) $update = '319';

$count = 0;

foreach($collection as $product)
{
        echo $product->getId();
	if($product->getGender() == $update)
	{
		echo " already updated. \n";
	}
	else
	{
		Mage::getSingleton('catalog/product_action')->updateAttributes(array($product->getId()), array('gender'=> $update), 1);
		echo " updated \n";
		$count ++;
	}

		
}

echo $count. " products tagged for department  $dept \n";
}
