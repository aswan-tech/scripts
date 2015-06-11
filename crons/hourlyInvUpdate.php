<?php
require_once '/home/cloudpanel/htdocs/www.americanswan.com/current/shell/abstract.php';
date_default_timezone_set('Asia/Kolkata');

class Hourly_Inventory_Update extends Mage_Shell_Abstract
{
	protected $inboundDir = "/home/cloudpanel/mnt/lecomotf/inbound/inventory/hourly/";
	protected $outboundDir = "/home/cloudpanel/htdocs/www.americanswan.com/current/var/lecom/inbound/inventory/hourly/";
	//on hold stock and skus
	protected $onHoldOrders = array();
	//name of the archived file
	protected $archivedFiles = array();
	protected $logfile = 'hourlysync.log';
	protected $message; 

	public function getOnHoldOrders()
	{
		$orders = Mage::getModel('sales/order')->getCollection()->addFieldToFilter('status', array('pending','COD_Verification_Pending'));
		Mage::log("Total on hold orders - ". count($orders),null,$this->logfile);
		$this->message .= "Total on hold orders ".count($orders)."<br/>";

		foreach($orders as $order)
		{
		        $allItems=$order->getAllItems();
                	foreach($allItems as $item)
                        {
			 $itemCount=count($allItems);
                         $type=$item->product_type;
                         if($type=='simple')
				{
                                        if(array_key_exists($item->getSku(), $this->onHoldOrders))
					{
                                                $this->onHoldOrders[$item->getSku()] = (int) ($this->onHoldOrders[$item->getSku()] + $item->qty_ordered);
                                        }else
					{
                                                $this->onHoldOrders[$item->getSku()] = (int) $item->qty_ordered;
                                        }
                                }
                        }
		}
	}
	
       
	public function processInventory()
	{
		$count = 0;
		foreach($this->archivedFiles as $key => $filename)
		{
		$i = 0;
		$blinke = array();
		// store inventory sheet in an array
		if (($handle = fopen($this->outboundDir.$filename, "r")) !== FALSE) {
                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if($i!=0)
		{
			$stock = (int) $data[1];
                        $sku = $data[0];
			$blinke[$sku] = $stock;
	
		}
			$i++;
		}
			fclose($handle);
		}
		Mage::log("Total sku count from $filename = " .count($blinke),null,$this->logfile);
	
		foreach($blinke as $sku=>$stock)
		{
			$fstock = $stock;
                        // calclulate stock by substracting stock for on hold orders
                        if(array_key_exists($sku, $this->onHoldOrders))
                        {
                                Mage::log("On hold stock for $sku is. ".$this->onHoldOrders[$sku],null,$this->logfile);
                                Mage::log("Blinke stock for $sku is. ".$fstock,null,$this->logfile);
                                $fstock = (int) ($fstock - $this->onHoldOrders[$sku]);
				$blinke[$sku] = $fstock;
                                Mage::log("EDIT :: Final stock for $sku is $fstock",null,$this->logfile); 
                        }
			

			//process inventory for the sku
			$product = Mage::getModel('catalog/product')->loadByAttribute('sku',$sku);
			if($product)
		        {
				try { $productId = $product->getIdBySku($sku); }
		                catch(Exception $e)
                		{
                			 Mage::log("Special SKU: $sku not found in magento",null,$this->logfile);
                        		 continue;
		                }

				$stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($productId);
		                $stockItemId = $stockItem->getId();
                		if (!$stockItemId) {
	                        Mage::log("STOCK ID for  SKU: $sku doesnot exist",null,$this->logfile);
        	                continue;
                		} else {
                         	$stock = $stockItem->getData();
                		}
				
				$originalStock = $stock['qty'];
				if($originalStock != $fstock)
                                {
                		if($fstock > 0)
		                {
                		        $stock['qty'] = $fstock;
		                        $stock['is_in_stock'] = '1';
		                        $stock['manage_stock'] = '1';
	               		}
				else
		                {
                		        $stock['qty'] = 0;
                		}
				 foreach($stock as $field => $value) {
	                        $stockItem->setData($field, $value?$value:0);
        		         }
                		try{
                        		$stockItem->save();
					$count++;
		                }
		                catch(Exception $e)
                		{
					Mage::log("cannot save inventory for $sku",null,$this->logfile);
		                        continue;
                		}
		                
				Mage::log("Inventory updated for: $sku with value: $fstock from $originalStock",null,$this->logfile);
                		unset($stockItem);
		                unset($product);
				}
				else
				{
					Mage::log("Inventory not updated for: $sku with value: $fstock from $originalStock",null,$this->logfile);
				}

	
			}	
			else
			{
				 Mage::log("Special SKU: $sku not found in magento",null,$this->logfile);
			}
		}
		}

		$this->message .= "Total $count sku's inventory updated.<br/>";
	}
	
	public function archiveFile()
	{
		$files = array_diff(scandir($this->inboundDir),array('..', '.'));
		$count = count($files);
		if($count == 0) { Mage::log("No file found. Dying",null,$this->logfile);die();}
		foreach($files as $key=>$filename)
		{
			Mage::log($filename." found in inbound location",null,$this->logfile);
			//check if file is csv
			$extension = pathinfo($filename);
			if($extension['extension'] == "csv")
			{
				
				//move the file to outbound directory
				if(rename($this->inboundDir.$filename,$this->outboundDir.$filename)) 
				{
					$this->archivedFiles[] = $filename;	
					Mage::log("File move to outbound location",null,$this->logfile);
				}
				else 
				{	Mage::log("Error moving file $filename",null,$this->logfile);
					continue;
				}
			}
			else
			{
				Mage::log("Not a csv file. Skipping $filename",null,$this->logfile);
				continue;
			}	
		}
	}


	public function sendMail()
	{
    	    	$date = date('d-M-Y');
	        try{

                $config = array('ssl' => 'tls',
                'auth' => 'login',
//                'username' => 'AKIAJ3Z55BLHVVKK6WHQ',
 //               'password' => 'Aik7+O1z7U8XgoMu3sItIG2Yb1CKrn3EnkRxykXvRfZK');


                'username' => 'AKIAJ3Z55BLHVVKK6WHQ',
                'password' => 'Aik7+O1z7U8XgoMu3sItIG2Yb1CKrn3EnkRxykXvRfZK',
                'port' => '587');

                $transport = new Zend_Mail_Transport_Smtp('email-smtp.us-east-1.amazonaws.com', $config);

            $mail = new Zend_Mail();
            $mail->setFrom("service@americanswan.com","Service");
            $mail->addTo("ops@taslc.com");
            $mail->addCc(array("vibhu.aggarwal@taslc.com", "deepak.kumar@taslc.com"));
            $mail->setSubject("Hourly Inventory Report | $date");
            $mail->setBodyHtml($this->message);

            $mail->send($transport);
	    Mage::log("Mail Sent successfully ",null,$this->logfile);	

        	}catch(Exception $e)
	        {
        	    echo $e->getMassage();
	    	   Mage::log("Mail Sending error",null,$this->logfile);	

        	}

	}

	public function run()
	{
		// get on hold orders
		$this->getOnHoldOrders();
		Mage::log("On Hold orders compilation completed",null,$this->logfile);
		
		// move file to ourbound location
		$this->archiveFile();
		Mage::log(count($this->archivedFiles)." files moved to outbound location",null,$this->logfile);	
		
		// process inventory
		$this->message .= "Number of files to be processed - ".count($this->archivedFiles)."<br/>";
		$this->processInventory();
		
		//sned the message stats;	
		$this->sendMail();
        }

}


$shell = new Hourly_Inventory_Update();
$shell->run();
