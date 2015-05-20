<?php 
$mageFilename = '../app/Mage.php';
require_once $mageFilename;
ini_set('display_errors', 1);
Mage::app();
//$productId = 5692;
$productId = Mage::getSingleton('core/app')->getRequest()->getParam('pid');
$tableName1 = 'sales_flat_order';
$tableName2 = 'sales_flat_order_item';   
$read = Mage::getSingleton('core/resource')->getConnection('core_read');
$sql = "select sfo.increment_id, sfoi.order_id, sfoi.created_at, sfoi.qty_ordered, sfo.status, sfo.state, sfo.customer_email from  {$tableName1} sfo , {$tableName2} sfoi where sfoi.order_id = sfo.entity_id and sfoi.product_type ='simple' and sfoi.product_id = '".$productId."'";
$ProInfo = $read->query($sql);
$ProRes = $ProInfo->fetchAll();
echo "<pre>";
print_r($ProRes);
echo "<pre>";

