<?php 
//inventory wise sequecning - not in use 

$mageFilename = '/home/cloudpanel/htdocs/www.americanswan.com/current/app/Mage.php';
require_once $mageFilename;
ini_set('display_errors', 1);
Mage::app();

echo "[" . date('Y-m-d H:i:s') . "] script started... <br>";
$write = Mage::getSingleton('core/resource')->getConnection('core_write');
$path = Mage::getBaseDir('media').DS.'category_products'.DS;
echo $catId = Mage::getSingleton('core/app')->getRequest()->getParam('catid');
$catId = 340;
//$fname = $path.$catId.'_products.csv';		
$fname = '262_products.csv';

$fp = fopen($fname,'r') or die("can't open file");
$i=1;

while($csv_line = fgetcsv($fp,1024)) {
	$sku = trim($csv_line[0]);
#echo "\nSKU=".$sku;continue;
	$clean_sku = str_replace("'","",$sku);

	$query = "SELECT e.sku, e.entity_id, count( pr.child_id ) AS totSimpleProd
				FROM catalog_product_entity AS e
				INNER JOIN catalog_category_product as c ON (e.entity_id = c.product_id)	
				INNER JOIN catalog_product_relation AS pr ON ( e.entity_id = pr.parent_id )
				INNER JOIN cataloginventory_stock_item AS st ON ( pr.child_id = st.product_id )
				WHERE st.is_in_stock = '1'
				AND c.category_id = '$catId'
				AND e.sku = '$clean_sku'
				AND e.type_id = 'configurable'
				GROUP BY e.entity_id
				ORDER BY `totSimpleProd` DESC
			";					

	$result = $write->query($query);

	while($data = $result->fetch()) {

//		echo "<pre>";print_r($data);		

		$pId = isset($data['entity_id']) ? $data['entity_id'] : 0;

		if($pId) {
			$sql = "UPDATE catalog_category_product SET position = '".$data['totSimpleProd']."' WHERE product_id ='".$pId."' 
 AND category_id = '$catId'";
			$write->query($sql);
			echo "sku: ".$clean_sku.", Prdid=".$pId."===>Data updated!:position = ".$data['totSimpleProd']."\n";
			$i++;
		}
	}
}



echo 'success';

echo "[" . date('Y-m-d H:i:s') . "] script ended... \n";

/*php-scripts/product_sequencing.php?catid=257*/


