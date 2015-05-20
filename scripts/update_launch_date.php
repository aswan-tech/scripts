<?php 

$mageFilename = '/home/cloudpanel/htdocs/www.americanswan.com/current/app/Mage.php';
require_once $mageFilename;
ini_set('display_errors', 1);
Mage::app();
//$date =  date("Y-m-d H:i:s");
//$fromDate = date('Y-m-d H:i:s',(strtotime ( '-1 day' , strtotime ( $date) ) ));
//$toDate =  $date; 
 $_productCollection = Mage::getResourceModel('catalog/product_collection')
            ->addAttributeToSelect('*')
            ->addFieldToFilter('status',1)
            ->addAttributeToFilter('type_id','configurable');
foreach($_productCollection as $p){
	$product_id = $p->getId();
	$updated_at = $p->getData('updated_at');
	echo 'Id: '.$p->getId().' Status: '.$p->getData('status').' Updated_at: '.$p->getData('updated_at')."\n";
	$product = Mage::getModel('catalog/product')->load($product_id);
	try{
		$launch_date = $product->getLaunchDate();
		if($launch_date)
			 echo 'Id: '.$p->getId().' Status: '.$p->getData('status').' Updated_at: '.$p->getData('updated_at').' Launch date:'.$launch_date.' No need to change launch date of the product'."\n";
		else{
				$product->setLaunchDate($updated_at);
				$product->getResource()->saveAttribute($product, 'launch_date');
				echo "Updated Successfully"."\n";
			}
		
	}
	catch(Exception $e){
		echo 'Id: '.$p->getId().' Status: '.$p->getData('status').' Updated_at: '.$p->getData('updated_at').' Unable to updated the product'."\n";
	}
}            

