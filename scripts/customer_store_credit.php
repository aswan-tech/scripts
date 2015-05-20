<?php
ini_set('display_errors', 1);
set_time_limit(0);
ini_set('memory_limit', '-1');
require '/home/cloudpanel/htdocs/www.americanswan.com/current/app/Mage.php';

Mage::app();

echo '"Customer Email","Date","Balance Amount","Action","Additional Info"'."\n";	
$core_read = Mage::getSingleton('core/resource')->getConnection('core_read');

//$sql = "select c.email,cb.amount from customer_entity as c inner join enterprise_customerbalance as cb ON c.entity_id = cb.customer_id where cb.amount > 0";
$sql = "select c.email,cbh.* from customer_entity as c 
inner join enterprise_customerbalance as cb ON c.entity_id = cb.customer_id
left join enterprise_customerbalance_history as cbh ON cbh.balance_id = cb.balance_id
where cbh.updated_at IS NOT NULL and ( cbh.updated_at between '2015-04-01 00:00:00' and '2015-04-30 23:59:59' )
order by cbh.updated_at asc";
$result = $core_read->query($sql);
$customers = $result->fetchAll();
foreach($customers as $data) {
	$action = "";
	if($data['action'] == '1') {
		$action = "Updated";
	}
	else if($data['action'] == '2') {
		$action = "Created";
	}
	else if($data['action'] == '3') {
		$action = "Used";
	}
	else if($data['action'] == '4') {
		$action = "Refunded";
	}
	else if($data['action'] == '5') {
		$action = "Reverted";
	}
	
	echo '"'.$data['email'].'","'.$data['updated_at'].'","'.$data['balance_amount'].'","'.$action.'","'.$data['additional_info'].'"'."\n";
	
}
die;
?>
