<?php
require_once '/home/cloudpanel/htdocs/www.americanswan.com/current/app/Mage.php';
umask(0);
Mage::app();

$from = "2014-10-16 18:30:00";
$to = "2014-10-17 00:00:00";

$collection = Mage::getModel('sales/order')->getCollection();
                        $collection->addAttributeToFilter('created_at', array(
                            'from' => $from,
                            'to' => $to
                     ));

foreach($collection->getData() as $item){
echo  $increment_id = $item['increment_id'];
 $order = Mage::getModel('sales/order')->loadByIncrementId($increment_id);
   $orderItems = $order->getAllVisibleItems();
   foreach($orderItems as $orderitem){
var_dump($orderitem->getData());
}
die;

}
