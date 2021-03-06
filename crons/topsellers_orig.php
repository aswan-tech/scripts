<?php 
require('/home/cloudpanel/htdocs/www.americanswan.com/current/app/Mage.php');
ini_set('display_errors', 1);
Mage::app();
        $storeId = (int) Mage::app()->getStore()->getId();
        //date range for order collection
        $date =  date("Y-m-d H:i:s");
       $fromDate = date('Y-m-d H:i:s',(strtotime ( '-1 day' , strtotime ( $date) ) ));
       $toDate =  $date; 
        //$fromDate = "2015-01-03 00:00:00";
        //$toDtae = "2015-01-03 23:49:49";
$orders = Mage::getModel('sales/order')->getCollection()
	->addAttributeToSelect('increment_id')
	->addAttributeToSelect('created_at')
    ->addAttributeToFilter('created_at', array('from'=>$fromDate, 'to'=>$toDate));
	//->addAttributeToFilter('status', array('eq' => Mage_Sales_Model_Order::STATE_COMPLETE));
$data = array();
$skuC = array();
 //for each order    
foreach($orders as $order){
	$increment_id = $order->getData('increment_id');
	$orderDetails = Mage::getModel('sales/order')->loadByIncrementId($increment_id); 
	echo $increment_id."\n";
	$ordered_items = $orderDetails->getItemsCollection();
	//for each order item
	foreach($ordered_items as $item){

		if($item->getProductType()=='configurable'){
			$product_id = $item->product_id;
	    	//$product_sku = $item->sku;
	    	$product_name = $item->getName();
	    	$item_qty = (int)$item->getQtyOrdered();
	    	$_product = Mage::getModel('catalog/product')->load($product_id);
		$product_sku = $_product->getData('sku');
	    	$cats = $_product->getCategoryIds();
	    	$product_url = $_product->getProductUrl();
	    	$image_url = $_product->getImageUrl();
	    	$original_price = round($item->getBaseOriginalPrice());
	    	$discount_amount = round($item->getDiscountAmount());
	    	$category_ids = array($cats[0],$cats[1]); // just grab the first id
	    	$category_name = "";$i=1;
	    	foreach($category_ids as $catid){
	    		$category = Mage::getModel('catalog/category')->load($catid);
	    		if($i==1)
	    			$category_name .= $category->getName()." -> ";
	    		else
	    			$category_name .= $category->getName();
	    		$i++;
	    	}
	    	//login to calculate the total qty ordered of the itemwise with in a date range
	    	if(!isset($data[$product_sku])){
	    		$skuC[$product_sku] = $item_qty;
				$data[$product_sku]['count'] = $item_qty;
				$data[$product_sku]['name'] = $product_name;
				$data[$product_sku]['product_url'] = $product_url;
				$data[$product_sku]['category_name'] = $category_name;
				$data[$product_sku]['mrp_value'] = $original_price;
				$data[$product_sku]['discount_amount'] = $discount_amount;
				$data[$product_sku]['final_price'] = $original_price-$discount_amount;
				$data[$product_sku]['image_url'] = $image_url;
			}else{
				$data[$product_sku]['count'] = $data[$product_sku]['count']+$item_qty;
				$data[$product_sku]['mrp_value'] = $data[$product_sku]['mrp_value']+ $original_price;
				$data[$product_sku]['discount_amount'] = $data[$product_sku]['discount_amount']+$discount_amount;
				$data[$product_sku]['final_price'] =  $data[$product_sku]['final_price']+($original_price-$discount_amount);
				$skuC[$product_sku] = $skuC[$product_sku]+$item_qty;
			}

		    

		}
		
	} 
	

}

//sort the array in the desending order
arsort($skuC);
//array chunk of 20 records each
$skuC1 = array_chunk($skuC, 20,true);
$html .= '<table width="100%" border="1" cellspacing="5" cellpadding="0" style="font:12px Arial, Helvetica, sans-serif; color:#666;border:1px solid #ccc;"><tr><th>Product Name</th><th>Sku</th><th>Category Name</th><th>Total Qty</th><th>MRP Value</th><th>Avg. Discount</th> <th>Total Discount</th><th>Final Value</th><th>Product Image</th></tr>';
foreach($skuC1[0] as $k=>$s){
	$discount_percent = round(($data[$k]['discount_amount']*100)/$data[$k]['mrp_value'],2);
		$html.= "<tr><td align='center'>".$data[$k]['name']."</td><td align='center'>".$k."</td><td align='center'>".$data[$k]['category_name']."</td><td align='center'>".$data[$k]['count']."</td><td align='center'>".
				$data[$k]['mrp_value']."</td><td align='center'>".$discount_percent."%</td><td align='center'>".$data[$k]['discount_amount']."</td><td align='center'>".$data[$k]['final_price']."</td><td align='center'><a href='".$data[$k]['product_url']."'><img height='30px' width='30px' src='".$data[$k]['image_url']."'></a></td></tr>";

}
$html .= "</table>";
echo $html."\n";
//email sent code
	try{
		$date = date('d-M-Y');
		$config = array('ssl' => 'tls',
		                'auth' => 'login',
		                'username' => 'AKIAJ3Z55BLHVVKK6WHQ',
		                'password' => 'Aik7+O1z7U8XgoMu3sItIG2Yb1CKrn3EnkRxykXvRfZK');
		$transport = new Zend_Mail_Transport_Smtp('email-smtp.us-east-1.amazonaws.com', $config);
		$mail = new Zend_Mail();
		$mail->setFrom("service@americanswan.com","Report");
		            $mail->addTo("anurag.rajpal@taslc.com");
		            $mail->addCc(array("merc@taslc.com", "ops@taslc.com", "tech@taslc.com", "marketing@taslc.com", "sales@taslc.com" ,"onlinesales@taslc.com", "sourcing@taslc.com", "scm@taslc.com", "crm@taslc.com", "deepti.beri@taslc.com"));
		            $mail->setSubject("Daily Best Seller Report - TOP 20 | $date");
		            $mail->setBodyHtml($html); 
		            $mail->send($transport);
			    echo "mail sent"."\n";
   
    }catch(Exception $e)
    {
        echo $e->getMassage();
  	    echo "mail sending error";          
    }
?>
