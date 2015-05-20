<?php 
$mageFilename = '/home/cloudpanel/htdocs/www.americanswan.com/current/app/Mage.php';
require_once $mageFilename;
ini_set('display_errors', 1);
Mage::app();

//calculating total orders in past 24 hours
$time = time();
$to = date('Y-m-d H:i:s', $time);
$lastTime = $time - 86400; // 60*60*24
$from = date('Y-m-d H:i:s', $lastTime);
$order_items = Mage::getResourceModel('sales/order_item_collection')
    ->addAttributeToSelect('product_id')
    ->addAttributeToSelect('product_type')
    ->addAttributeToFilter('created_at', array('from' => $from, 'to' => $to))
    ->addAttributeToSort('created_at', 'DESC')
    ->load();

$skuList = array();

foreach($order_items as $order)
{
	$productType = $order->getData('product_type');
	if($productType == "configurable")
	{
		$sku = Mage::getModel('catalog/product')->load($order->getProductId())->getSku();
		if(array_key_exists($sku,$skuList)) $skuList[$sku] = $skuList[$sku] + 1;
		else $skuList[$sku] = 1;
	}
}

$write = Mage::getSingleton('core/resource')->getConnection('core_write');
//$fname = '/home/ubuntu/scripts_magento/crons/category.csv';		
$fname = '/home/ubuntu/scripts_magento/crons/test.csv';		
$fp = fopen($fname,'r') or die("can't open file");

while($csv_line = fgetcsv($fp,1024)) {
	$catId = trim($csv_line[0]);
	$query = "SELECT e.sku, e.entity_id, c.category_id,cped.value AS launch_date,DATEDIFF(cped.value,'2013-02-01') as days, count( distinct pr.child_id ) AS totSimpleProd
			FROM catalog_product_entity AS e
			INNER JOIN catalog_category_product as c ON (e.entity_id = c.product_id)	
			INNER JOIN catalog_product_relation AS pr ON ( e.entity_id = pr.parent_id )
			INNER JOIN cataloginventory_stock_item AS st ON ( pr.child_id = st.product_id )
			INNER JOIN catalog_product_entity_datetime AS cped ON ( cped.entity_id = e.entity_id )
			AND c.category_id = '$catId'
			AND st.is_in_stock = '1'
			AND cped.attribute_id =  '93'
			AND e.type_id = 'configurable'
			GROUP BY e.sku
			ORDER BY days DESC";					
	$result = $write->query($query);
	
		$count=0;
		$pp = 12;		
	while($data = $result->fetch()) {

		$clean_sku = str_replace("'","",$data['sku']);
		$pId = isset($data['entity_id']) ? $data['entity_id'] : 0;
		$catId = isset($data['category_id']) ? $data['category_id'] : 0;
		$days= isset($data['days']) ? $data['days'] : 0;
		$simple = isset($data['totSimpleProd']) ? $data['totSimpleProd'] : 0;
		$orderCount = isset($skuList['sku']) ? $skuList['sku'] : 0;
		if($simple >= 6) $simple = 5;	
		if($days == 0) echo date('Y-m-d H:i:s')." ".$clean_sku."with no launch date \n";

		//algorithm for calculating score
		//$score = round(($days- 1)/(850-1)*100*0.60 + ($simple-1)/(5-1)*100*0.25 + ($ordercount-0)/(50-0)*100*0.15)+ $pp;
		$score = round(($days- 1)/(850-1)*100*0.60 + ($simple-1)/(5-1)*100*0.40)  + $pp;
		$pp--;	
		if(!empty($clean_sku) && $pId > 0 && $catId > 0) {
		$sql = "UPDATE catalog_category_product SET position = '".$score."' WHERE product_id ='".$pId."' AND category_id = '$catId'";
		$write->query($sql);
		$count++;		
		}
	}
	echo "Total $count products updated for $csv_line[1]($csv_line[0]) \n";
}

//indexing
echo date('Y-m-d H:i:s')." Starting Indexing \n";
$process = Mage::getModel('index/process')->load('6');
$process->reindexAll();
echo date('Y-m-d H:i:s')."Indexing End \n";
