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
    $_product = Mage::getModel('catalog/product')->load($val['entity_id']);
    echo '[Product Id:'.$val['entity_id'].']<br>';
    $datacat = $_product->getCategoryIds();
    $ctnamearray = array();
    foreach( $datacat as $subcatid ) {
        $catdetails = Mage::getModel('catalog/category')->load($subcatid);
        $parentArr = $catdetails->getData();
        $subCatName = strtolower($parentArr['name']);
        $attribute = Mage::getModel('eav/config')->getAttribute('catalog_product', 'product_category');
        $attrid = $attribute->getId();
        foreach ( $attribute->getSource()->getAllOptions(false, false) as $option ) {
            $label = strtolower($option['label']);
			
            if($subCatName == $label) {
				//echo $subCatName.'==>'.$label.'<br>';
				
                $value=$write->query("Select count(*) as count from catalog_product_entity_varchar where entity_type_id='4' and store_id='".$store_id."' and  entity_id='".$val['entity_id']."' and attribute_id='".$attrid."' and value='".$option['value']."' ");
                $row = $value->fetch();
                if($row['count']>0){
                    echo "Data already exists. \n";
                } else {
                    $chckvalue=$write->query("Select count(*) as count from catalog_product_entity_varchar where entity_type_id='4' and store_id='".$store_id."' and  entity_id='".$val['entity_id']."' and attribute_id='".$attrid."' ");
                    $total = $chckvalue->fetch();
                    if($total['count']>0){
                        $oldVal = $write->query("Select `value` from catalog_product_entity_varchar where entity_type_id='4' and store_id='".$store_id."' and  entity_id='".$val['entity_id']."' and attribute_id='".$attrid."' ");
                        $olddata = $oldVal->fetch();
                        $a = explode(',', $olddata['value']);
                        $b = $option['value'];
                        if(in_array($b, $a)){
                            $finalval = $olddata['value'];
                        } else {
                            $finalval = $olddata['value'].','.$option['value'];
                        }
                        $sql = "update catalog_product_entity_varchar set value = '".$finalval."' where entity_type_id='4' and store_id='".$store_id."' and  entity_id='".$val['entity_id']."' and attribute_id='".$attrid."' ";
                    }else {
                        $sql = "insert into catalog_product_entity_varchar (`entity_type_id`,`attribute_id`,`store_id`,`entity_id`,`value`) values ('4','".$attrid."','".$store_id."','".$val['entity_id']."','".$option['value']."')";
                    }
                    $write->query($sql);
                    echo "Data inserted. \n";
                }
            }
        }
    }
}
echo "[" . date('Y-m-d H:i:s') . "] script ended... \n";

