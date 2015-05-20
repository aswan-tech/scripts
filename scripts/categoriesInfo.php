<?php
$mageFilename = '/home/cloudpanel/htdocs/www.americanswan.com/current/app/Mage.php';
require_once $mageFilename;
ini_set('display_errors', 1);
Mage::app();
header('Content-Type: text/csv; charset=utf-8');
header("Content-Disposition: attachment; filename=AllCategories.csv");
header("Pragma: no-cache");
header("Expires: 0");
$outstream = fopen("php://output", "w");
fputcsv($outstream, array("CategoryID", "CategoryName","Active","ParentCategoryName"),chr(44),'"');
$category = Mage::getModel('catalog/category');
$tree = $category->getTreeModel();
$tree->load();
$ids = $tree->getCollection()->getAllIds();
if ($ids){
        foreach ($ids as $id) {
                $cat = Mage::getModel('catalog/category')->load($id);
                if($cat->getLevel() >= 2){
                $parentCat = Mage::getModel('catalog/category')->load($cat->getParentId());
                fputcsv($outstream, array($cat->getEntityId(),$cat->getName(),$cat->getIsActive(),$parentCat->getName()),chr(44),'"');
                }
        }
        fclose($outstream);
}
