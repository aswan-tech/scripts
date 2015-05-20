<?php
require_once '/home/cloudpanel/htdocs/www.americanswan.com/current/app/Mage.php';
umask(0);
Mage::app();//->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

$collection = Mage::getModel('catalog/product')->getCollection()
		//->addAttributeToSelect('product_department')
		//->addAttributeToSelect('product_category')
		->addAttributeToSelect('sku')
		->addAttributeToFilter('type_id', array('eq' => 'configurable'))
		->addAttributeToSort('entity_id', 'DESC');

echo "Sku\n";
$count = 0;
foreach ($collection as $product) {
echo $product->getSku();
#echo $product->getAttributeText('product_department').",";
#echo $product->getAttributeText('product_category');
#echo $count++;
echo "\n";
}
