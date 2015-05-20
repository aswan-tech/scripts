<?php
require_once '/home/cloudpanel/htdocs/www.americanswan.com/current/shell/abstract.php';

class Warehouse_Shift extends Mage_Shell_Abstract
{
	protected $startDate = "2014-10-11 18:30:00";
	
	protected $orderCollection = array();
	protected $template = 'WAREHOUSE_SHIFT';
	
	public function getOrderCollection()
	{
		$endDate = date('Y-m-d H:i:s');
		$orders = Mage::getModel('sales/order')->getCollection()
		    ->addFieldToFilter('status', array('COD_Verification_Successful','created'))
		    ->addAttributeToFilter('created_at', array(
		    'from' => $this->startDate,
		    'to' => $endDate
		));

		$this->orderCollection = $orders;
	}

	public function sendSms($mobile)
	{
		$helper = Mage::helper('nosql/joker');
		$data['send_to'] = $mobile;
		echo "sending sms to $mobile\n";
		$helper->sendNow($data, 'sms', $this->template);
		return true;
		
	}

	public function sendEmail($email,$name)
	{
		$emailTemplate = Mage::getModel('core/email_template')->load('41');
		$customer =  array();
		$customer['name'] = $name;
		$messageBody = $emailTemplate->getProcessedTemplate($customer);
		$mail = Mage::getModel('core/email')
			 ->setToName($name)
			 ->setToEmail($email)
//			 ->setBcc('tech@taslc.com')
			 ->setBody($messageBody)
			 ->setSubject($emailTemplate['template_subject'])
			 ->setFromEmail('ceo@americanswan.com')
			 ->setFromName('CEO Americanswan')
			 ->setType('html');

		try{
			echo "Sending Mail to $email \n";
			$mail->send();
			//$bccmail->send();
			return true;
		 }
		 catch(Exception $error)
		 {
			 Mage::getSingleton('core/session')->addError($error->getMessage());
			 return false;
		 }
	}

	public function checkSend($orderId)
	{
    		$connection = Mage::getSingleton('core/resource')->getConnection('core_read');
		$sql="select count(*) count from warehouse_shift where order_id='$orderId'";
		$rowsArray = $connection->fetchAll($sql);
		$connection->closeConnection();
    		return $rowsArray[0][count];
	}

	public function updateSend($data)
	{
		$resource = Mage::getSingleton('core/resource');
                $writeConnection = $resource->getConnection('core_write');
                $writeConnection->beginTransaction();
                $writeConnection->insert('warehouse_shift',$data);
                $writeConnection->commit();
                $writeConnection->closeConnection();
	}

	public function run()
        {
		$this->getOrderCollection();
		echo "Total Order count: ".count($this->orderCollection)."\n";

		foreach($this->orderCollection as $order)
		{
 			$data['order_id'] = $order->getIncrementId();
			if($this->checkSend($data['order_id']) > 0)
			{
//				echo "Information already sent for ".$data['order_id']."\n";
			}
			else
			{
 				$data['email'] = $order->getCustomerEmail();
				$data['name'] = $order['customer_firstname'].' '.$order['customer_lastname'];
				$shippingId= $order->getShippingAddressId();
			        $address = Mage::getModel('sales/order_address')->load($shippingId);
	 		        $data['mobile'] = $address['telephone'];
				echo "Sending SMS to ".$data['mobile']." and email to ".$data['email']." for order ".$data['order_id']."\n";
				$this->sendSms($data['mobile']);
				$this->sendEmail($data['email'],$data['name']);
				$this->updateSend($data);
			}	
		}
		
        }

}


$shell = new Warehouse_Shift();
$shell->run();

