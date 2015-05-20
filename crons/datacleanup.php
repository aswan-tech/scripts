<?php 

require_once "/home/cloudpanel/htdocs/www.americanswan.com/current/app/Mage.php";

require_once "/home/cloudpanel/htdocs/www.americanswan.com/current/includes/config.php";

Mage::app();
	try
	{
		$date = exec('date');

		$data=explode(" ",$date);
		if(isset($data[5])){
		$year = $data[5];
		}
		if(isset($data[1])){
			$month = $data[1];
			if($month == 'Jan')
				$month = '01';
			elseif($month == 'Feb')
				$month = '02';
			elseif($month == 'Mar')
				$month = '03';
			elseif($month == 'Apr')
				$month = '04';
			elseif($month == 'May')
				$month = '05';
			elseif($month == 'Jun')
				$month = '06';
			elseif($month == 'Jul')
				$month = '07';
			elseif($month == 'Aug')
				$month = '08';
			elseif($month == 'Sep')
				$month = '09';
			elseif($month == 'Oct')
				$month = '10';
			elseif($month == 'Nov')
				$month = '11';
			else
				$month = '12';
		}

		if(isset($data[2])){
			if((int)$data[2] > 7){
				$date=$data[2]-7;
			}else{
				if((int)$month > 1){
					$month = (int)$month - 1;
					if((int)$month < 10){
						$month = '0'.$month;
					}
					if((int)$month == 2){
						$date = 28 + ($data[2]-7);
					}elseif((int)$month == 1||(int)$month == 3||(int)$month == 5||(int)$month == 7||(int)$month ==8||(int)$month == 10||(int)$month == 12){
						$date = 31 + ($data[2]-7);
					}else{
						$date = 30 + ($data[2]-7);
					}
				}else{
					$year = (int)$year - 1;
					$month = '12';
					$date = 31 + ($data[2]-7);
				}
			}
		}

		if(isset($data[3])){
			$setTime = $data[3];
		}else{
			$setTime = '0:0:0';
		}

		$newTime=$year.'-'.$month.'-'.$date.' '.$setTime;

		$write = Mage::getSingleton('core/resource')->getConnection('core_write');

		$query = "DELETE FROM fcm_logger WHERE log_time <='$newTime'";

		$write->query($query);
		
	}catch(exception $e){
		Mage::log('Exception occured while cleaning logs for logger table :'.$e);
		Mage::getModel('common/common')->sendErrorNotification("Exception occured while cleaning logs for logger table :".$e);
	}
