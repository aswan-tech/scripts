<?php 
$mageFilename = '/mnt/www/Lecom_Magento/app/Mage.php';
require_once $mageFilename;
ini_set('display_errors', 1);
Mage::app();

echo "[" . date('Y-m-d H:i:s') . "] script started... <br>";
$write = Mage::getSingleton('core/resource')->getConnection('core_write');

//while($csv_line = fgetcsv($fp,1024)) {
	$catId = "262";//trim($csv_line[0]);
	echo "\n****************** category id : {$catId} , CatName: {$csv_line[1]} **************** \n";
	$query = "SELECT e.created_at,e.sku, e.entity_id, c.category_id, count( pr.child_id ) AS totSimpleProd
			FROM catalog_product_entity AS e
			INNER JOIN catalog_category_product as c ON (e.entity_id = c.product_id)	
			INNER JOIN catalog_product_relation AS pr ON ( e.entity_id = pr.parent_id )
			INNER JOIN cataloginventory_stock_item AS st ON ( pr.child_id = st.product_id )
			WHERE st.is_in_stock = '1'
			AND c.category_id = '$catId'
			AND e.type_id = 'configurable'
			GROUP BY e.entity_id
		";					
	$result = $write->query($query);
	
	while($data = $result->fetch()) {

			
		$clean_sku = str_replace("'","",$data['sku']);
		$pId = isset($data['entity_id']) ? $data['entity_id'] : 0;
		$catId = isset($data['category_id']) ? $data['category_id'] : 0;
		
		if(!empty($clean_sku) && $pId > 0 && $catId > 0) {
			$sql = "UPDATE catalog_category_product SET position = '".$data['totSimpleProd']."' WHERE product_id ='".$pId."' 
AND category_id = '$catId'";
			$write->query($sql);
		}
	}
//}

echo 'success';

echo "[" . date('Y-m-d H:i:s') . "] script ended... \n";
