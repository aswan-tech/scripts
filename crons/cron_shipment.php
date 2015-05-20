<?php

/**
 * Magento Cron script 
 *
 * This cron script is to be scheduled to read shipment data from remote server and create shipments.
 *
 * @category    HCL
 * @package     HCL_Fulfillment
 * @author	Pawan Prakash Gupta
 * @author_id	51405591
 * @company	HCL Technologies
 * @created Wednesday, June 20, 2012
 * @copyright	Four cross media
 */
 
require '/home/cloudpanel/htdocs/www.americanswan.com/current/app/Mage.php';

if (!Mage::isInstalled()) {
    echo "Application is not installed yet, please complete install wizard first.";
    exit;
}

// Only for urls
// Don't remove this
$_SERVER['SCRIPT_NAME'] = str_replace(basename(__FILE__), 'index.php', $_SERVER['SCRIPT_NAME']);
$_SERVER['SCRIPT_FILENAME'] = str_replace(basename(__FILE__), 'index.php', $_SERVER['SCRIPT_FILENAME']);

Mage::app('admin')->setUseSessionInUrl(false);

Mage::log('Order Shipment cron started', Zend_Log::DEBUG, 'fulfillment');

$shipmentModel = Mage::getModel('fulfillment/shipment');

try {
	$processModel = Mage::getModel('fulfillment/process');
	
	$startTime = $processModel->getCurrentDateTime();
	//Set Cron status to 'Processing'
	Mage::getModel('logger/cron')->updateCron("order_shipment", "Processing", $startTime, "", "Scheduled cron started");
	
	//Add message to logger
	Mage::getModel('logger/logger')->saveLogger("order_shipment", "Information", __FILE__, "Order Shipment cron started");
	
	//Run orders shipment function
    $shipmentModel->otfshipping();
	
	$finishTime = $processModel->getCurrentDateTime();
		
	if (!$shipmentModel->hasException) {
		//Set Cron status to 'Finished'
		$summary = $shipmentModel->getShortProcessSummary();
		Mage::getModel('logger/cron')->updateCron("order_shipment", "Finished", "", $finishTime, "Scheduled cron completed successfully. ". $summary);
		
		//Add message to logger
		Mage::getModel('logger/logger')->saveLogger("order_shipment", "Success", __FILE__, "Order Shipment cron completed successfully");
	} else {
		$summaryShort = $shipmentModel->getShortProcessSummary() . " \n";
		//Set Cron status to 'Exception'
		Mage::getModel('logger/cron')->updateCron("order_shipment", "Failed", "", $finishTime, "Scheduled cron failed. ". $summaryShort . $shipmentModel->exceptionMessage);
		
		$summaryDesc = $shipmentModel->getDetailProcessSummary();
		$summaryDesc = "<p>" . $summaryDesc . "</p>";
		//Add message to logger
		Mage::getModel('logger/logger')->saveLogger("order_shipment", "Failure", __FILE__, "Order Shipment cron failed" . $summaryDesc );
		
		//Send Notification Mail
		$processModel->notify("order_shipment", "Order Shipment cron failed" . $summaryDesc);
	}
} catch (Exception $e) {
    //Mage::printException($e);
	$errmsg = $e->getMessage() . "\n".$e->getTraceAsString();
	
	$summaryDesc = $shipmentModel->getDetailProcessSummary();
	$summaryDesc = "<p>" . $summaryDesc . "</p>";	
	
	Mage::log($summaryDesc . $errmsg, Zend_Log::ERR, 'fulfillment');	
		
	//Add message to logger
	Mage::getModel('logger/logger')->saveLogger("order_shipment", "Exception", __FILE__, $summaryDesc . $errmsg);
	Mage::getModel('logger/logger')->saveLogger("order_shipment", "Failure", __FILE__, "Order Shipment cron failed");
	
	$processModel = Mage::getModel('fulfillment/process');
	
	//Set Cron status to 'Failed'
	$summaryShort = $cancelModel->getShortProcessSummary() . " \n";
	$errTime = $processModel->getCurrentDateTime();
	Mage::getModel('logger/cron')->updateCron("order_shipment", "Failed", "", $errTime, "Scheduled cron failed. ".  $summaryShort. $errmsg);
	
	//Send Notification Mail
	$processModel->notify("order_shipment", $summaryDesc . $errmsg);
}

Mage::log('Order Shipment cron finished', Zend_Log::DEBUG, 'fulfillment');
