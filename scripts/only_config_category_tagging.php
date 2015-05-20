<?php
require_once '/home/cloudpanel/htdocs/www.americanswan.com/current/app/Mage.php';
umask(0);
Mage::app();
$write = Mage::getSingleton('core/resource')->getConnection('core_write');

$_productCollection = Mage::getResourceModel('catalog/product_collection')->addAttributeToFilter('type_id', array('eq' => 'configurable'));
$i=1;
foreach ($_productCollection as $product) {
	$i++;
}

echo $i;
exit;
$collection = Mage::getModel('catalog/product')->getCollection()
                ->addAttributeToSelect('sku')
                ->addAttributeToFilter('type_id', array('eq' => 'configurable'))
                ->addAttributeToSort('entity_id', 'DESC');

echo "Sku\n";
$count = 0;
$catId = 335;
foreach ($collection as $product) {
	$sku = $product->getSku();
//	$clean_sku = str_replace("'","",$sku);
	$product_id = Mage::getModel('catalog/product')->getIdBySku($sku);
	if($product_id){
		$sql = "select count(category_id) as category_id from  catalog_category_product where category_id='".$catId."' and product_id='".$product_id."'";				
		$countInfo = $write->query($sql);
		$countRes = $countInfo->fetch();
		if($countRes['category_id'] == 0){
			$write->query("insert into catalog_category_product values ('".$catId."','".$product_id."','1')");
			echo "[$sku] :: Data inserted!\n";
		}
		else{
//			echo "[$sku] :: Already inserted!\n";
		}
	}
	else{
		echo "[$sku], ";
	}		
//	echo "R:{$count}\n";
	$count ++;
}
echo $count;
?>

