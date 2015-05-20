<?php

/**
 * Magento Cron script 
 *
 * This cron script is to be scheduled to generate order to fulfill feeds.
 *
 * @category    HCL
 * @package     HCL_Fulfillment
 * @author	Pawan Prakash Gupta
 * @author_id	51405591
 * @company	HCL Technologies
 * @created Monday, June 4, 2012
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

Mage::log('Order Fulfillment cron started', Zend_Log::DEBUG, 'fulfillment');

try {
	$processModel = Mage::getModel('fulfillment/process');
	
	$startTime = $processModel->getCurrentDateTime();
	//Set Cron status to 'Processing'
	Mage::getModel('logger/cron')->updateCron("order_fulfillment", "Processing", $startTime, "", "Scheduled cron started.");
	
	//Add message to logger
	Mage::getModel('logger/logger')->saveLogger("order_fulfillment", "Information", __FILE__, "Order Fulfillment cron started");
	
	//Run feed generation function
	$otfModel = Mage::getModel('fulfillment/otf');
    $otfModel->otffeed();
	
	//Set Cron status to 'Finished'
	$finishTime = $processModel->getCurrentDateTime();
	$summary = $otfModel->getOrdersProcessedSummary();
	Mage::getModel('logger/cron')->updateCron("order_fulfillment", "Finished", "", $finishTime, "Scheduled cron completed successfully. ". $summary );
	
	//Add message to logger
	Mage::getModel('logger/logger')->saveLogger("order_fulfillment", "Success", __FILE__, "Order Fulfillment cron completed successfully ". $summary);
} catch (Exception $e) {
    //Mage::printException($e);
	$errmsg = $e->getMessage() . "\n".$e->getTraceAsString();
	Mage::log($errmsg, Zend_Log::ERR, 'fulfillment');	
		
	//Add message to logger
	Mage::getModel('logger/logger')->saveLogger("order_fulfillment", "Exception", __FILE__, $errmsg);
	Mage::getModel('logger/logger')->saveLogger("order_fulfillment", "Failure", __FILE__, "Order Fulfillment cron failed");
	
	$processModel = Mage::getModel('fulfillment/process');
	
	//Set Cron status to 'Failed'
	$errTime = $processModel->getCurrentDateTime();
	Mage::getModel('logger/cron')->updateCron("order_fulfillment", "Failed", "", $errTime, "Scheduled cron failed. ". $errmsg);
	
	//Send Notification Mail
	$processModel->notify("order_fulfillment", $errmsg);
}

Mage::log('Order Fulfillment cron finished', Zend_Log::DEBUG, 'fulfillment');
