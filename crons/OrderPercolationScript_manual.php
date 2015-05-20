<?php
require '/home/cloudpanel/htdocs/www.americanswan.com/current/app/Mage.php';

ini_set('display_errors', 1);
Mage::app();

$today = date("Ymd");
$logfile="OrderPercolation.log";

$filename = "otfStatus_".$today."*.xml";
$filepath = "/mnt/lecomotf/inbound/orduploadstatus";

Mage::log("\n\n\n\n\nstarting OrderPercolation script----".date("Y-m-d H:i:s"), null, $logfile);
$write = Mage::getSingleton('core/resource')->getConnection('core_write');
foreach (glob($filepath."/".$filename) as $file) {
         Mage::log("Processing file: " .$file, null, $logfile);

	$xmlDoc = new DOMDocument();
        $xmlDoc->load($file);

        $orders = new SimpleXMLElement($xmlDoc->saveXML());

        foreach($orders->Order as $order){
                if (substr($order->OrderNumber,0,2) == "AS" && $order->status=="Failed"){
			Mage::log("FAILURE: " . $order->OrderNumber, null, $logfile);
                        $OrderNumber = $order->OrderNumber;
                        $_order = Mage::getModel('sales/order')->load($OrderNumber, 'increment_id');
                        //$_order->setData('sent_to_erp', '0');
                        //$_order->save();
			$entityId = $_order->getId();		
			if($entityId) {
                                $write->query("UPDATE sales_flat_order SET sent_to_erp = 0 WHERE entity_id = '{$entityId}' LIMIT 1");
                       		Mage::log("Status updated... " . $order->OrderNumber, null, $logfile);
			 }
                        else{
				Mage::log("Error while udpating status... " . $order->OrderNumber, null, $logfile);
                        }
	$to = "anil.kumar@taslc.com";
   	$subject = "Order percolation issue: ".$order->OrderNumber;
  	 $message = "Order not percolated: ".$order->OrderNumber;
  	 $header = "From:anil.kumar@taslc.com \r\n";
  	 mail ($to,$subject,$message,$header);

                     
   //end of script

                } else {
                	Mage::log("SUCCESS: " . $order->OrderNumber, null, $logfile);
                }
        }
//Move the file to ./status_files_processed
	$f = explode("/", $file);
        rename($file, $filepath."/processed_files/" . $f[count($f) - 1]);
	Mage::log("moving file to: ".$filepath."/processed_files/" . $f[count($f) - 1], null, $logfile);
//end move file code

}
Mage::log("\n\n\n\n\nending OrderPercolation script----".date("Y-m-d H:i:s"), null, $logfile);
?>

