<?php 
$mageFilename = '../app/Mage.php';
require_once $mageFilename;
ini_set('display_errors', 1);
Mage::app();
header('Content-Type: text/csv; charset=utf-8');
header("Content-Disposition: attachment; filename=productTaxInfo.csv");
header("Pragma: no-cache");
header("Expires: 0");
$outstream = fopen("php://output", "w");
fputcsv($outstream, array("ProductType", "Sku", "Name", "TaxClass"),chr(44),'"'); 
$collection = Mage::getModel('catalog/product')->getCollection()
            ->addAttributeToSelect('name')
			->addAttributeToSelect('tax_class_id')
            ->joinAttribute('visibility', 'catalog_product/visibility', 'entity_id', null, 'inner', 1);
foreach ($collection as $_product) {
	fputcsv($outstream, array($_product['type_id'],$_product['sku'],$_product['name'], $_product->getAttributeText('tax_class_id')),chr(44),'"');
}
fclose($outstream);
























/*header('Content-Type: text/csv; charset=utf-8');
header("Content-Disposition: attachment; filename=tax123.csv");
header("Pragma: no-cache");
header("Expires: 0"); 
$line = array();
$fp = fopen('tax.csv','w') or die("can't open file");

foreach($collection as $_product) {
	$line = array ($_product['type_id'],$_product['sku'],$_product['name'],$_product->getAttributeText('tax_class_id'));

	if($i < 9){
		echo 'qqq';
		fputcsv($fp, $line."\n");		
		}
	$i++;
	}*/

