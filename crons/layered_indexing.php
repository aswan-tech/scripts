<?php
$mageFilename = '/home/cloudpanel/htdocs/www.americanswan.com/current/app/Mage.php';
require_once $mageFilename;
ini_set('display_errors', 1);
Mage::app();

echo date('Y-m-d H:i:s')." Layered navigation indexing start\n";
$process = Mage::getModel('index/process')->load('1');
$process->reindexAll();

echo date('Y-m-d H:i:s')." Layered navigation indexing finished\n";
