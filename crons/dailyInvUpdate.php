<?php
require_once '/home/cloudpanel/htdocs/www.americanswan.com/current/shell/abstract.php';
date_default_timezone_set('Asia/Kolkata');

class Daily_Inventory_Update extends Mage_Shell_Abstract
{
	protected $inboundDir = "/home/cloudpanel/mnt/lecomotf/inbound/inventory/daily/";
	protected $outboundDir = "/home/cloudpanel/htdocs/www.americanswan.com/current/var/lecom/inbound/inventory/daily/";
	protected $csvDir = "/home/cloudpanel/htdocs/www.americanswan.com/current/var/lecom/inbound/inventory/final/";
	//on hold stock and skus
	protected $onHoldOrders = array();
	//name of the archived file
	protected $archivedFile ;
	// array for containing all status of inventories from blinke, onhold, mage current and mage final
	protected $blinkeInventory = array();
	protected $logfile = 'invsync.log';

	public function getOnHoldOrders()
	{
		$orders = Mage::getModel('sales/order')->getCollection()->addFieldToFilter('status', array('pending','COD_Verification_Pending'));
		Mage::log("Total on hold orders - ". count($orders),null,$this->logfile);

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
		// store whole blinke inventory sheet in an array
		if (($handle = fopen($this->outboundDir.$this->archivedFile, "r")) !== FALSE) {
                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if($i!=0)
		{
			$stock = (int) $data[1];
                        $sku = $data[0];
			$this->blinkeInventory[$sku]['blinke'] = $stock;
	
		}
			$i++;
		}
			fclose($handle);
		}
		Mage::log("Total sku count from blinke- " .count($this->blinkeInventory),null,$this->logfile);
	
		foreach($this->blinkeInventory as $sku=>$array)
		{
			$fstock = $array['blinke'];
                        // calclulate stock by substracting stock for on hold orders
                        if(array_key_exists($sku, $this->onHoldOrders))
                        {
                                Mage::log("On hold stock for $sku is. ".$this->onHoldOrders[$sku],null,$this->logfile);
				$this->blinkeInventory[$sku]['onHold'] = $this->onHoldOrders[$sku];
                                Mage::log("Blinke stock for $sku is. ".$fstock,null,$this->logfile);
                                $fstock = (int) ($fstock - $this->onHoldOrders[$sku]);
				$this->blinkeInventory[$sku]['finalMagento'] = $fstock;
                                Mage::log("EDIT :: Final stock for $sku is $fstock",null,$this->logfile); 
                        }
			else
			{
				$this->blinkeInventory[$sku]['finalMagento'] = $fstock;
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
				$this->blinkeInventory[$sku]['currentMagento'] = $originalStock;
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
	
	public function archiveFile()
	{
		$files = array_diff(scandir($this->inboundDir),array('..', '.'));
		$count = count($files);
		if($count > 1) { Mage::log("More than 1 files not expected. Dying",null,$this->logfile);die();}
		if($count == 0) { Mage::log("No file found. Dying",null,$this->logfile);die();}
		$filename = $files[2];
		Mage::log($filename." found in inbound location",null,$this->logfile);
		//check if file is csv
		$extension = pathinfo($filename);
		if($extension['extension'] == "csv")
		{
			Mage::log("File is confirmed csv",null,$this->logfile);
			//move the file to outbound directory and rename it.
			$this->archivedFile = "blinke-".date('d-m-Y-H-i-s').".csv"; 
			if(rename($this->inboundDir.$filename,$this->outboundDir.$this->archivedFile)) 
			Mage::log("File move to outbound location",null,$this->logfile);
			else 
			{Mage::log("Error moving file",null,$this->logfile);die();}
		}
		else
		{
			Mage::log("Not a csv file. Dying",null,$this->logfile);
			die();
		}
	}

	public function writecsv()
	{
		$header	= array('sku','Blinke','Onhold','Magento Initial','Magento Final');
		$filename = 'finalInv-'.date('Y-m-d').'.csv';
		$fp = fopen($this->csvDir.$filename, 'w');
		fputcsv($fp, $header);
	        foreach($this->blinkeInventory as $sku=>$array)
		{
			$onhold = isset($array['onHold'])?$array['onHold']:0;
			$line = array($sku,$array['blinke'],$onhold,$array['currentMagento'],$array['finalMagento']);
			fputcsv($fp,$line);
		}
		fclose($fp);
		Mage::log('CSV file created with name - '.$filename,null,$this->logfile);
	}

	public function sendMail()
	{
    	    	$date = date('d-M-Y');
		$filename = 'finalInv-'.date('Y-m-d').'.csv';
		$file = $this->csvDir.$filename;
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
	    $mail->addCc(array("vibhu.aggarwal@taslc.com", "merc@taslc.com", "ops@taslc.com", "tech@taslc.com", "marketing@taslc.com", "sales@taslc.com" ,"onlinesales@taslc.com", "sourcing@taslc.com", "scm@taslc.com", "crm@taslc.com", "deepti.beri@taslc.com"));
            $mail->setSubject("Full Inventory Report | $date");
            $mail->setBodyHtml("PFA the inventory report for $date");

            // this is for to set the file format
            $mail->setType(Zend_Mime::MULTIPART_RELATED);
            $content = file_get_contents($file);
            $at = new Zend_Mime_Part($content);

            $at->type        = 'application/csv';
            $at->disposition = Zend_Mime::DISPOSITION_INLINE;
            $at->encoding    = Zend_Mime::ENCODING_8BIT;
            $at->filename    = $filename;
            $mail->addAttachment($at);
            $mail->send($transport);
	    Mage::log("Mail Sent successfully with attachment".$filename,null,$this->logfile);	

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
		
		// move file to archive location and rename to today's date
		$this->archiveFile();
		Mage::log("Files archived with name ".$this->archivedFile,null,$this->logfile);	
		
		// process inventory
		$this->processInventory();
		Mage::log("Inventory Sync Completed for ".$this->archivedFile,null,$this->logfile);	
	
		// write final csv and mail the attachment
		$this->writecsv();
		
		$this->sendMail();
        }

}


$shell = new Daily_Inventory_Update();
$shell->run();
