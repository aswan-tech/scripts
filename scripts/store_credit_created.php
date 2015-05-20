<?php 
function sendMail($message)
{
    $date = date('d-M-Y');
    $filename = "store-credit-created-report-".date('Y-m-d').'.csv';
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
            $mail->setSubject("Store Credit Created Report | $date");
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

$fp = fopen('/tmp/store-credit-created-report-'.date('Y-m-d').'.csv', 'w');
$header = array('Order No.', 'Order Date','Store Credit Amount','Email');
fputcsv($fp, $header);
$date =  date("Y-m-d H:i:s");
$fromDate = date('Y-m-d H:i:s',(strtotime ( '-1 day' , strtotime ( $date) ) ));
$toDate =  $date; 
$credit_updateds = Mage::getResourceModel('sales/order_creditmemo_collection')
        ->addAttributeToFilter('created_at', array(
        'from' => $fromDate,
        'to' => $toDate,
        'date' => true,
        ));


foreach($credit_updateds as $credit) {
    $credit_memo_id = $credit->getData('increment_id');
    $total = $credit->getData('grand_total');
    $order_id = $credit->getData('order_id');
    try{
       $order = Mage::getModel('sales/order')->load($order_id);
        $order_date = $order->getData('created_at');
        $increment_id =  $order->getData('increment_id');
        $email = $order->getCustomerEmail();
    }
    catch (Exception $e) { 
        echo "Order_id: ".$order_id." Unable to Load"."\n";
    }
    
    echo "Order_id: ".$order_id." Order Date:".$order_date." Increment_id:".$increment_id." Total:".$total."\n";
    $data = array($increment_id,$order_date,$total,$email);
    fputcsv($fp, $data);

}
$message='Store Credit Created Report';
fclose($fp);
sendMail($message);
