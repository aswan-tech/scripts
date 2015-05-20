<?php 
//391_products.csv

$mageFilename = '/home/cloudpanel/htdocs/www.americanswan.com/current/app/Mage.php';
require_once $mageFilename;
ini_set('display_errors', 1);
Mage::app();
echo "[" . date('Y-m-d H:i:s') . "] script started... \n";
$write = Mage::getSingleton('core/resource')->getConnection('core_write');
//$path = Mage::getBaseDir('media').DS.'category_products'.DS;
//echo $catId = Mage::getSingleton('core/app')->getRequest()->getParam('catid');
$catId = 434; 
$fname = $catId.'_products.csv';
$fp = fopen($fname,'r') or die("can't open file");
while($csv_line = fgetcsv($fp,1024)) {
		$sku = trim($csv_line[0]);
		//$clean_sku = str_replace("'","",$sku);
		$product_id = Mage::getModel('catalog/product')->getIdBySku($sku);
		if($product_id){
			$sql = "select count(category_id) as category_id from  catalog_category_product where category_id='".$catId."' and product_id='".$product_id."'";				
			$countInfo = $write->query($sql);
			$countRes = $countInfo->fetch();
			if($countRes['category_id'] == 0){
				$write->query("insert into catalog_category_product values ('".$catId."','".$product_id."','1')");
				echo $sku." - Data inserted! \n";
				}
				else{
				echo $sku." -  Already There! \n";
				}
		}
		else
		{
			echo "$sku - product not found \n";
		}
}
fclose($fp);
echo "[" . date('Y-m-d H:i:s') . "] script ended... \n";
/*scripts/addproductstocategory.php?catid=257*/
