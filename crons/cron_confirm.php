<?php

/**
 * Magento Cron script 
 *
 * This cron script is to be scheduled to read the order confirmation feeds and update the data model.
 *
 * @category    HCL
 * @package     HCL_Fulfillment
 * @author	Pawan Prakash Gupta
 * @author_id	51405591
 * @company	HCL Technologies
 * @created Monday, June 11, 2012
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

Mage::log('Order Confirmation cron started', Zend_Log::DEBUG, 'fulfillment');

$confModel = Mage::getModel('fulfillment/confirmation');

try {
	$processModel = Mage::getModel('fulfillment/process');
	
	$startTime = $processModel->getCurrentDateTime();
	//Set Cron status to 'Processing'
	Mage::getModel('logger/cron')->updateCron("order_confirm", "Processing", $startTime, "", "Scheduled cron started");
	
	//Add message to logger
	Mage::getModel('logger/logger')->saveLogger("order_confirm", "Information", __FILE__, "Order Confirmation cron started");
	
	//Run orders confirmation function
    $confModel->otfconfirm();
	
	$finishTime = $processModel->getCurrentDateTime();
	
	if (!$confModel->hasException) {
		//Set Cron status to 'Finished'
		$summary = $confModel->getShortProcessSummary();
		Mage::getModel('logger/cron')->updateCron("order_confirm", "Finished", "", $finishTime, "Scheduled cron completed successfully. ". $summary);
		
		//Add message to logger
		Mage::getModel('logger/logger')->saveLogger("order_confirm", "Success", __FILE__, "Order Confirmation cron completed successfully");
	} else {
		$summaryShort = $confModel->getShortProcessSummary() . " \n";
		//Set Cron status to 'Exception'
		Mage::getModel('logger/cron')->updateCron("order_confirm", "Failed", "", $finishTime, "Scheduled cron failed. ". $summaryShort . $confModel->exceptionMessage);
		
		$summaryDesc = $confModel->getDetailProcessSummary();
		$summaryDesc = "<p>" . $summaryDesc . "</p>";	
		//Add message to logger
		Mage::getModel('logger/logger')->saveLogger("order_confirm", "Failure", __FILE__, "Order Confirmation cron failed." . $summaryDesc );
		
		//Send Notification Mail
		$processModel->notify("order_confirm",  "Order Confirmation cron failed." . $summaryDesc);
	}
} catch (Exception $e) {
    //Mage::printException($e);
	$errmsg = $e->getMessage() . "\n".$e->getTraceAsString();
		
	$summaryDesc = $confModel->getDetailProcessSummary();
	$summaryDesc = "<p>" . $summaryDesc . "</p>";	

	Mage::log($summaryDesc . $errmsg, Zend_Log::ERR, 'fulfillment');	
	
	//Add message to logger
	Mage::getModel('logger/logger')->saveLogger("order_confirm", "Exception", __FILE__, $summaryDesc . $errmsg);
	Mage::getModel('logger/logger')->saveLogger("order_confirm", "Failure", __FILE__, "Order Confirmation cron failed");
	
	$processModel = Mage::getModel('fulfillment/process');
	
	//Set Cron status to 'Failed'
	$summaryShort = $confModel->getShortProcessSummary() . " \n";
	$errTime = $processModel->getCurrentDateTime();
	Mage::getModel('logger/cron')->updateCron("order_confirm", "Failed", "", $errTime, "Scheduled cron failed. ". $summaryShort. $errmsg);
	
	//Send Notification Mail
	$processModel->notify("order_confirm",  $summaryDesc . $errmsg);
}

Mage::log('Order Confirmation cron finished', Zend_Log::DEBUG, 'fulfillment');
