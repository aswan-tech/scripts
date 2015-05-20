<?php 
function sendMail($message)
{
    $date = date('d-M-Y');
    $filename = "store-credit-redeem-report-".date('Y-m-d').'.csv';
    try{

        $config = array('ssl' => 'tls',
                'auth' => 'login',
                'username' => 'AKIAJ3Z55BLHVVKK6WHQ',
                'password' => 'Aik7+O1z7U8XgoMu3sItIG2Yb1CKrn3EnkRxykXvRfZK');
        $transport = new Zend_Mail_Transport_Smtp('email-smtp.us-east-1.amazonaws.com', $config);

            $mail = new Zend_Mail();
            $mail->setFrom("service@americanswan.com","Service");
            $mail->addTo("anil.kumar@taslc.com");
            //$mail->addCc(array("onlinesales@taslc.com","marketing@taslc.com","raj.gupta@taslc.com","ops@taslc.com"));
            $mail->addCc(array("onlinesales@taslc.com","marketing@taslc.com","tech@taslc.com","ops@taslc.com"));
            $mail->setSubject("Store Credit Redeem Report | $date");
            $mail->setBodyHtml($message); 
        
            // this is for to set the file format
        $mail->setType(Zend_Mime::MULTIPART_RELATED);
            $content = file_get_contents("/tmp/$filename");
            $at = new Zend_Mime_Part($content);
            
            $at->type        = 'application/csv';
            $at->disposition = Zend_Mime::DISPOSITION_INLINE;
            $at->encoding    = Zend_Mime::ENCODING_8BIT;
            $at->filename    = $filename;
            $mail->addAttachment($at);
            $mail->send($transport);
        echo "mail sent";
   
        }catch(Exception $e)
        {
            echo $e->getMassage();
        echo "mail sending error";          
        }
}
$mageFilename = '/home/cloudpanel/htdocs/www.americanswan.com/current/app/Mage.php';
require_once $mageFilename;
ini_set('display_errors', 1);
Mage::app();
$write = Mage::getSingleton('core/resource')->getConnection('core_write');
$read = Mage::getSingleton('core/resource')->getConnection('core_read');

$fp = fopen('/tmp/store-credit-redeem-report-'.date('Y-m-d').'.csv', 'w');
$header = array('Order No.', 'Order Date','Store Credit Amount','Email','Additional Info');
fputcsv($fp, $header);
$date =  date("Y-m-d H:i:s");
$fromDate = date('Y-m-d H:i:s',(strtotime ( '-1 day' , strtotime ( $date) ) ));
$toDate =  $date;
$sql = "SELECT customer_entity.entity_id AS customer_id, customer_entity.email, history_id, balance_delta, additional_info, enterprise_customerbalance_history.updated_at FROM enterprise_customerbalance, enterprise_customerbalance_history, customer_entity "
            . "WHERE enterprise_customerbalance.balance_id = enterprise_customerbalance_history.balance_id "
            . "AND customer_entity.entity_id = enterprise_customerbalance.customer_id "
            . "AND customer_entity.is_active = 1 "
            . "AND action IN (3) "
            ."AND enterprise_customerbalance_history.updated_at between '$fromDate' and '$toDate' "
            . "ORDER BY history_id DESC;";
$credit_updateds = $read->fetchAll($sql);
foreach($credit_updateds as $credit) {

    $additional_info = $credit['additional_info'];
     $info = $additional_info;
    $order_number = '';
     if (strpos(strtolower($additional_info), "order #") !== FALSE) {
        echo $additional_info."\n";
        $additional_info = trim(substr($additional_info, strpos(strtolower($additional_info), "order #") + 7));
        if (strpos($additional_info, ",") !== FALSE) {
            $order_number = substr($additional_info, 0, strpos($additional_info, ","));
        } else if (strpos($additional_info, ".") !== FALSE) {
            $order_number = substr($additional_info, 0, strpos($additional_info, "."));
        } else {
            $order_number = $additional_info;
        }
    }
    $order_date = '';
    if(isset($order_number) && $order_number!=''){

        $orderdetails = Mage::getModel('sales/order')->loadByIncrementId($order_number);
        $order_date = $orderdetails->getData('created_at');
        echo "Order No: ".$order_number." Order Date:".$order_date." Balance:".$credit['balance_delta']."\n";
        
    }
    $data = array($order_number,$order_date,$credit['balance_delta'],$credit['email'],$info);
    fputcsv($fp, $data);


    

    
}
$message='Store Credit Redeemed Report';
fclose($fp);
sendMail($message);
