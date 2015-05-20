<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);
set_time_limit(0);
require_once '../app/Mage.php';
require_once '../includes/config.php';
umask(0);
$app = Mage::app('default');
$custid = 3112;
$customer = Mage::getModel('customer/customer')->load($custid);
$quote = Mage::getModel('sales/quote')->loadByCustomer($customer);
$token = md5(rand(0,9999999));
$url = Mage::getModel('core/url')->setStore($store)->getUrl('',array('_nosid'=>true)).'ajax/index/loadquote?id='.$quote->getEntityId().'&token='.$token;


$email = $quote->getCustomerEmail();
$_productcount = count($quote->getAllVisibleItems());

$name = $quote->getCustomerFirstname().' '.$quote->getCustomerLastname();
echo $templateId = Mage::getStoreConfig(Ebizmarts_AbandonedCart_Model_Config::EMAIL_TEMPLATE_XML_PATH).'llllllllllllllll';
$vars = array('customer_name'=>$name,'quote'=>$quote,'quote_id'=>$quote->getId(),'recovery_url'=>$url,'product_count'=>$_productcount,'products'=>$quote->getAllVisibleItems());

echo "<pre>";
//print_r($quote);
print_r($vars);
echo "</pre>";
