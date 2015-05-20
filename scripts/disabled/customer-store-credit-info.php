<?php 
$mageFilename = '../app/Mage.php';
require_once $mageFilename;
ini_set('display_errors', 1);
Mage::app();
header('Content-Type: text/csv; charset=utf-8');
header("Content-Disposition: attachment; filename=storecreditCustomerDetailInfo.csv");
header("Pragma: no-cache");
header("Expires: 0");
$outstream = fopen("php://output", "w");
fputcsv($outstream, array("", "", "", "", "", "","Against Order Detail","","Redeemed Against Order"),chr(44),'"');
fputcsv($outstream, array("Customer Name", "Customer Email","Store Credit Issuing Date","Admin comments", "Store Credit Value", "Store Credit Against Order (No.)", "Store Credit Against Order (Qty)", "Store Credit Against Order (Value)", "Redeemed Against Order (No.)", "Redeemed Against Order (Qty)", "Redeemed Against Order (Value)", "Redeemed Date", "Redeemed  Store Credit", "Pending Store Credit"),chr(44),'"');
$write = Mage::getSingleton('core/resource')->getConnection('core_write');
$collection = Mage::getResourceModel('customer/customer_collection')->addNameToSelect()->addAttributeToSelect('email')->addAttributeToSelect('email');
$dataArr = Array();
foreach($collection as $val) {	
	$valueOrder = $write->query("select * from enterprise_customerbalance ec, enterprise_customerbalance_history ech where ec.customer_id = '".$val['entity_id']."' and ec.balance_id = ech.balance_id");	
	$rowOrder = $valueOrder->fetchAll();	
	if($rowOrder){
		foreach($rowOrder as $valOrder) {
		$dataArr['name'] = $val['name'];
		$dataArr['email'] = $val['email'];
		$dataArr['balance_amount'] = $valOrder['balance_amount'];
		$dataArr['updated_at'] = $valOrder['updated_at'];
		$dataArr['additional_info'] = $valOrder['additional_info'];
		$dataArr['balance_delta'] = $valOrder['balance_delta'];		
		if($valOrder['action'] == 3) {
			$incrementIds = explode("#",$valOrder['additional_info']);
			//echo 'order';
			$value = $write->query("select customer_balance_amount, increment_id , base_subtotal_incl_tax, total_qty_ordered, created_at from sales_flat_order  where increment_id='".$incrementIds[1]."' and customer_id = '".$val['entity_id']."' and customer_balance_amount > '0'");
			$row = $value->fetch();
			$dataArr['customer_balance_amount'] = $row['customer_balance_amount'];
			$dataArr['increment_id'] = $row['increment_id'];
			$dataArr['base_subtotal_incl_tax'] = $row['base_subtotal_incl_tax'];
			$dataArr['total_qty_ordered'] = $row['total_qty_ordered'];
			$dataArr['created_at'] = $row['created_at'];
			$dataArr['create_storecredit_increment_id'] = '';
			$dataArr['against_value'] = '';
			$dataArr['against_qty'] = '';
	
		} elseif($valOrder['action'] == 4) {
			$incrementId = explode("#",$valOrder['additional_info']);
			$incrementUsedId = explode(",",$incrementId[1]);
			//echo "create store credit";
			
			$valueCreate = $write->query("select customer_balance_amount, increment_id , base_subtotal_incl_tax, total_qty_ordered, created_at from sales_flat_order  where increment_id='".$incrementIds[1]."' and customer_id = '".$incrementUsedId[0]."' and customer_balance_amount > '0'");
			$rowCreate = $valueCreate->fetch();
			$dataArr['customer_balance_amount'] = '';
			$dataArr['increment_id'] = '';
			$dataArr['base_subtotal_incl_tax'] = '';
			$dataArr['total_qty_ordered'] = '';
			$dataArr['created_at'] = '';
			$dataArr['create_storecredit_increment_id'] = $incrementUsedId[0];
			$dataArr['against_value'] = $rowCreate['base_subtotal_incl_tax'];
			$dataArr['against_qty'] = $rowCreate['total_qty_ordered'];
		}else {
			//echo 'no order';
			$dataArr['customer_balance_amount'] = '';
			$dataArr['increment_id'] = '';
			$dataArr['base_subtotal_incl_tax'] = '';
			$dataArr['total_qty_ordered'] = '';
			$dataArr['created_at'] = '';
			$dataArr['create_storecredit_increment_id'] = '';
			$dataArr['against_value'] = '';
			$dataArr['against_qty'] = '';

			}
							/*echo "<pre>";
							print_r($dataArr);
							echo "</pre>";*/
							fputcsv($outstream, array($dataArr['name'], $dataArr['email'], $dataArr['updated_at'], $dataArr['additional_info'], $dataArr['balance_amount'], $dataArr['create_storecredit_increment_id'] ,$dataArr['against_qty'] ,$dataArr['against_value'] ,$dataArr['increment_id'] ,$dataArr['total_qty_ordered'] ,$dataArr['base_subtotal_incl_tax'] ,$dataArr['created_at'] ,$dataArr['customer_balance_amount'] ,$dataArr['balance_amount']),chr(44),'"'); 
	
		}
							
			}
}
fclose($outstream);
			
			
			

