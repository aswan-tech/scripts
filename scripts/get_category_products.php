<?php 
$mageFilename = '/home/cloudpanel/htdocs/www.americanswan.com/current/app/Mage.php';
require_once $mageFilename;
ini_set('display_errors', 1);
Mage::app();
$script_start = microtime(true);

$read = Mage::getSingleton('core/resource')->getConnection('core_read');
if(isset($_GET['argument1']) || isset($argv[1])) {
    if ($_GET) {
        $ruleIds = $_GET['argument1'];
    } else {
        $ruleIds = $argv[1];
    }
} else {
    echo("ERROR:: Can't find arguments.\n");
    die();
}
$categoryIds = explode(",", $ruleIds);
echo("\"sku\",\"category_id\",\"gender\"\n");
foreach($categoryIds as $categoryId) {
	$category = Mage::getModel('catalog/category')->load($categoryId);
	$products = Mage::getModel('catalog/product')
    		->getCollection()
    		->addCategoryFilter($category)
    		->load();
	foreach($products as $product) {
		$_product = Mage::getModel('catalog/product')->load($product->getData('entity_id'));
		if ($_product->getTypeId() != "configurable") {
			continue;
		}
		$gender = $_product->getData('gender');
		if (trim($gender) == "") {
			$conf = Mage::getModel('catalog/product_type_configurable')->setProduct($_product);
			$simple_collection = $conf->getUsedProductCollection()->addAttributeToSelect('gender')->addFilterByRequiredOptions();
			foreach ($simple_collection as $simple) {
				$gender = $simple->getAttributeText('gender');
				if (trim($gender) != ""){
					break;	
				}
			}
		}
		echo("\"" . $_product->getData('sku') . "\",\"" . $categoryId . "\",\"" . $gender . "\"\n");
	}
}
