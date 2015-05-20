<?php 
$mageFilename = '/home/cloudpanel/htdocs/www.americanswan.com/current/app/Mage.php';
require_once $mageFilename;
ini_set('display_errors', 1);
Mage::app();
$date =  date("Y-m-d H:i:s");
$fromDate = date('Y-m-d H:i:s',(strtotime ( '-90 day' , strtotime ( $date) ) ));
$toDate =  date('Y-m-d H:i:s',(strtotime ( '-3 day' , strtotime ( $date) ) ));;
$orders = Mage::getModel('sales/order')->getCollection()
    ->addFieldToFilter('status', array('pending','COD_Verification_Pending'))
	//->addFieldToFilter('increment_id', 'AS050215775609')
    ->addAttributeToFilter('created_at', array('from' => $fromDate, 'to' => $toDate, 'date' => true,))
    ->addAttributeToSelect('*');
foreach($orders as $order) {
    $increment_id = $order->getData('increment_id');
    $status = $order->getData('status');
    $orderModel = Mage::getModel('sales/order');
    $orderModel->loadByIncrementId($increment_id);
    try{
        //$orderModel->setStatus('canceled');
		$orderModel->setState('canceled', 'canceled', 'Order has been cancelled by script.', FALSE);
        $orderModel->save();
        echo "Order No:".$increment_id. " Status: ".$status." Status changed: canceled"."\n";
    }catch (Exception $e) { 
       echo "Order No:".$increment_id. " Status: ".$status." Unable to change status"."\n";
    }
}
?>
