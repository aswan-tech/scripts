<?php 
$mageFilename = '../app/Mage.php';
require_once $mageFilename;
ini_set('display_errors', 1);
Mage::app();
echo "[" . date('Y-m-d H:i:s') . "] script started... \n";
$write = Mage::getSingleton('core/resource')->getConnection('core_write');
$collection = Mage::getModel('catalog/product')->getCollection();
$collection->addAttributeToFilter('visibility' , Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE);
$store_id = '0';

foreach($collection as $val) {	
	$v = $val['entity_id'];
    $_product = Mage::getModel('catalog/product')->load($val['entity_id']);
    echo '[Product Id:'.$val['entity_id'].']<br>';
    $datacat = $_product->getCategoryIds();	
	foreach( $datacat as $subcatid ) {		
		$parentIds[$v][] = Mage::getModel('catalog/category')->load($subcatid)->getParentId();
	}
	$result = array_merge($datacat, $parentIds[$v]);
	$datacatnew = array_unique($result);
   foreach( $datacatnew as $subcatidnew ) {
        $catdetails = Mage::getModel('catalog/category')->load($subcatidnew);
        $parentArr = $catdetails->getData();
        $subCatName = strtolower($parentArr['name']);	
        $attribute = Mage::getModel('eav/config')->getAttribute('catalog_product', 'gender');
        $attrid = $attribute->getId();
        foreach ( $attribute->getSource()->getAllOptions(false, false) as $option ) {
            $label = strtolower($option['label']);			
            if($subCatName == $label) {
				//echo $val['entity_id'].'==='.$subCatName.'==='.$label.'===>>'.'<br>';
                 $value=$write->query("Select count(*) as count from catalog_product_entity_int where entity_type_id='4' and store_id='".$store_id."' and  entity_id='".$val['entity_id']."' and attribute_id='".$attrid."' and value='".$option['value']."' ");				
                $row = $value->fetch();
                if($row['count']>0){
                    echo "Data already exists. \n";
                } else {
                    $chckvalue=$write->query("Select count(*) as count from catalog_product_entity_int where entity_type_id='4' and store_id='".$store_id."' and  entity_id='".$val['entity_id']."' and attribute_id='".$attrid."' ");
                    $total = $chckvalue->fetch();
                    if($total['count']>0){                        
                        $sql = "update catalog_product_entity_int set value = '".$option['value']."' where entity_type_id='4' and store_id='".$store_id."' and  entity_id='".$val['entity_id']."' and attribute_id='".$attrid."' ";
                    }else {
                        $sql = "insert into catalog_product_entity_int (`entity_type_id`,`attribute_id`,`store_id`,`entity_id`,`value`) values ('4','".$attrid."','".$store_id."','".$val['entity_id']."','".$option['value']."')";
                    }
                    $write->query($sql);
                    echo "Data inserted. \n";
                }		
				
            }			
        }		
    }	 
}
echo "[" . date('Y-m-d H:i:s') . "] script ended... \n";


