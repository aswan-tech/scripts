<?php 
$mageFilename = '../app/Mage.php';
require_once $mageFilename;
ini_set('display_errors', 1);
Mage::app();
echo "[" . date('Y-m-d H:i:s') . "] script started... \n";
$write = Mage::getSingleton('core/resource')->getConnection('core_write');
$value=$write->query("Select product_id from catalog_category_product");				
$row = $value->fetchAll();
foreach($row as $val) {	
	$pId = $val['product_id'];
	$write->query("UPDATE catalog_category_product SET position = '".$pId."' WHERE product_id ='".$pId."'");
	echo $pId."===>Data inserted!<br>";
}
echo "[" . date('Y-m-d H:i:s') . "] script ended... \n";
