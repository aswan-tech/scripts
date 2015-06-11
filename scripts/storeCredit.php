<?php 
$mageFilename = '/home/cloudpanel/htdocs/www.americanswan.com/current/app/Mage.php';
require_once $mageFilename;
ini_set('display_errors', 1);
Mage::app();
$comment = 'This is a comment that will appear in the credit update history';

$fp = fopen('/tmp/customer.csv','r') or die("can't open file");
while($line = fgetcsv($fp,1024))
{
       $email = trim($line[0]);
       #$amount = trim($line[1]);
       $amount = 200;
       if($email){
			$customer = Mage::getModel("customer/customer");
			$customer->setWebsiteId(Mage::app()->getWebsite()->getId());
			$customer->loadByEmail($email); 
			$customer_id =  $customer->getId();
			if($customer_id){
				$_balance = Mage::getModel('enterprise_customerbalance/balance');
	            $_balance->setCustomerId($customer_id)
	                ->setWebsiteId(1)
	                ->loadByCustomer();
	            try{
	            	$_balance->setAmountDelta($amount)
	                ->setUpdatedActionAdditionalInfo($comment)
	                ->setHistoryAction(1)
	                ->save();
	                echo "Saved the store credit agaginst email:".$email.":".$amount."\n";
	            }   catch(Exception $e){
	            	echo "Catch Block:No customer with this email:".$email."\n";
	            } 
			}
			else{
				echo "No customer with this email:".$email."\n";
       		}            
       }
       else {
		echo "Email not exists"."\n";
	}
}
?>

