<?php 
$mageFilename = '/home/cloudpanel/htdocs/www.americanswan.com/current/app/Mage.php';
require_once $mageFilename;
ini_set('display_errors', 1);
Mage::app();
$array = array(array('order_id'=>'AS010615732634','email'=>'jewelblissindia@gmail.com'),
				array('order_id'=>'AS080615741453','email'=>'jarugula353@gmail.com'),
				array('order_id'=>'AS090615741844','email'=>'pgpawar16@gmail.com'),
				array('order_id'=>'AS100615742667','email'=>'rupesh.7kumar@yahoo.co.in'),
				array('order_id'=>'AS110615744826','email'=>'vinivazirani@gmail.com'));
foreach ($array as $data) {
		$increment_id = $data['order_id'];
		$order = Mage::getModel('sales/order')->loadByIncrementId($increment_id);
		if($order){
			//$telephone = $order->getShippingAddress()->getTelephone();
			//echo "Processing for order id :".$increment_id.' Telephone:'.$data['email']."\n";
			$order->setCustomerEmail($data['email']);
			$order->save();
			echo "Processing for order id :".$increment_id." saved email:".$data['email']."\n";
		}else{
			echo "No order exists:".$increment_id."\n";
		}
		
		/*if($telephone !=''){
			$customer = Mage::getModel('customer/customer')
			->getCollection()->addFieldToFilter('telephone',$telephone)
			->setOrder('entity_id', 'DESC')->load();
			if(count($customer)>0){
				foreach ($customer as $cust) {
					if($cust->getEmail() !='')
					{
						$id = $cust->getEntityId();
						$order->setCustomerEmail($cust->getEmail());
						$order->setCustomerId($id);
						try{
							$order->save();
							echo "Order saved with email:".$cust->getEmail()." Customer id:".$id."\n";
						}
						catch(Exception $e){
							echo "Unable to save the order :".$increment_id."\n";
						}
						break;
					}
					
				}
			}
			else continue;
		}	*/
}


?>
