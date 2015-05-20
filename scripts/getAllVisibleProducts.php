<?php

//error_reporting(E_ALL);
ini_set("display_errors", 1);
include '/home/cloudpanel/htdocs/www.americanswan.com/current/app/Mage.php';
Mage::app();


$collection = Mage::getModel('catalog/product')->getCollection()->addAttributeToSelect('*');
Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($collection);
Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($collection);
Mage::getSingleton('catalog/product_status')->addSaleableFilterToCollection($collection);
Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($collection);
echo("entity_id,sku,type,visibility,inventory_in_stock,status,parent_id,stock,name,url_path,department,catagories\n");
$count = 0;
foreach($collection as $product) {
	$cnames = array();
        $dnames = array();
        $category = "";
        $department = "";
        $cats = $product->getCategoryIds();
        foreach ($cats as $category_id) {
                $_cat = Mage::getModel('catalog/category')->load($category_id) ;
                if ($_cat->getLevel() > 2) {
                        $cnames[] = $_cat->getName();
                }
                if ($_cat->getLevel() == 2) {
                        $dnames[] = $_cat->getName();
                }
        }
        if (count($cnames) > 0) {
                $category = implode(",", $cnames);
        }
        if (count($dnames) > 0) {
                $department = implode(",", $dnames);
        }
	$qtyStock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product)->getQty();
        echo ("\"".$product->getData("entity_id")."\",\"".$product->getData("sku")."\",\"".$product->getData("type_id")."\",\"".$product->getData("visibility")."\",\"".$product->getData("inventory_in_stock")."\",\"".$product->getData("status")."\",\"\",\"".$qtyStock."\",\"".$product->getData("name")."\",\"".$product->getData("url_path")."\",\"".$department."\",\"".$category."\"\n");
        if ($qtyStock > 0) {
        	$count++;
        }
	if ($product->getTypeId() == "configurable"){
		$childProducts = Mage::getModel('catalog/product_type_configurable')->getUsedProducts(null,$product);
		$inStock = false;
		foreach($childProducts as $child) {
    			$qtyStock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($child)->getQty();
			 echo ($child->getData("entity_id").",".$child->getData("sku").",".$child->getData("type_id").",".$child->getData("visibility").",".$child->getData("inventory_in_stock").",".$child->getData("status").",".$product->getData("entity_id").",".$qtyStock.",".$child->getData("name").",".$child->getData("url_path")."\n");
			if ($qtyStock > 0) {
                        	$inStock = true;
                	}
		}
		if ($inStock) {
			$count++;
		}
	}
}

echo("Total visible product on Catalog: " . $count);

