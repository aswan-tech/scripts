<?php
set_time_limit(0); 
require_once '/home/cloudpanel/htdocs/www.americanswan.com/current/app/Mage.php';
ini_set('display_errors', 1);
Mage::app();
$fp = fopen('/tmp/order-status-comment'.date('Y-m-d').'.csv', 'w');
$header = array('Order Id', 'Created At','Comment',);
fputcsv($fp, $header);
$date =  date("Y-m-d H:i:s");
$fromDate = date('Y-m-d H:i:s',(strtotime ( '-23 day' , strtotime ( $date) ) ));
$toDate =  date('Y-m-d H:i:s');
 $orders = Mage::getModel('sales/order')->getCollection()
    ->addFieldToFilter('status', array('canceled'))
    ->addAttributeToFilter('created_at', array('from' => $fromDate, 'to' => $toDate, 'date' => true,))
    ->addAttributeToSelect(array('increment_id', 'created_at'));
foreach($orders as $order){
	echo "Process:".$order->getData('increment_id')."\n";
	$increment_id = $order->getData('increment_id');
	$created_at = $order->getData('created_at');
	$orderComments = $order->getAllStatusHistory();
	$comment_html  = '';
	foreach ($orderComments as $comment) {
		$comment_html .= $comment->getData('created_at').':'.$comment->getData('comment')."|";
	}
	$data = array($increment_id,$created_at,$comment_html);
	fputcsv($fp,$data);
}
fclose($fp);


?>
