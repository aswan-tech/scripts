<?php
ini_set('display_errors', 1);
set_time_limit(0);
ini_set('memory_limit', '-1');
require '/home/cloudpanel/htdocs/www.americanswan.com/current/app/Mage.php';
umask( 0 );
Mage::app('default');
$filePath = "/tmp/sale_report_coupon_wise.csv";

$fromDate = date('Y-m-d', strtotime('-20 day')).' 00:00:00';
$toDate = date('Y-m-d').' 23:59:59';

$dateFromFormat = date("d M Y", strtotime($fromDate));
$toFromFormat = date("d M Y", strtotime($toDate));

$orders = Mage::getModel('sales/order')->getCollection()
										 ->addAttributeToFilter('created_at', array('from'=>$fromDate, 'to'=>$toDate))
										 ->addAttributeToFilter('status', array('neq'=>array('Pending')))
										 ->addAttributeToFilter('status', array('neq'=>array('COD Verification Pending')))
										 ->addAttributeToFilter('status', array('neq'=>array('Cancelled')));
										//->setPageSize(10)
										//->setCurPage(1);

							
$header = array("EMAIL","Order ID","Sale Qty","Coupon Code","Coupon Discount","Shopping Cart Price Rule (Coupon Description)","Reward Points","Rewards Points Value","Store Credit","Gift Voucher Code","Gift voucher value","Sale Value (MRP)","Sale Value (Offer Price)", "Total Discount Value", "Total Discount(%)", "Amount to Customer",	"COD", "Prepaid");										

//echo "<pre>";
$data = array();
$mailData = '';
$fp = fopen($filePath, 'w');
fputcsv($fp, $header);

foreach($orders as $order) {
	$paymentMethod = $order->getPayment()->getMethod();
	$ordered_items = $order->getAllItems();
	$productMrp = 0;
	foreach($ordered_items as $item) {
		if($item->getProductType() == 'simple' ) {
			$productMrp = ($productMrp + ((int)$item->getQtyOrdered() * $item->getData('product_mrp')));
		}
	}
	
	$data['emailID'] = $order->getCustomerEmail();
	$data['orderID'] = $order->getIncrementId();
	$data['saleQty'] = (int)$order->getData('total_qty_ordered');
	$data['couponCode'] = (string)$order->getData('coupon_code');
	$data['couponDiscount'] = (int)trim(str_replace("-", "", $order->getDiscountAmount()));
	$data['couponDesc'] = null;
	if(!empty($order->getData('applied_rule_ids'))) {
		$rule =  Mage::getModel('salesrule/rule')->load($order->getData('applied_rule_ids'),'rule_id');
		$data['couponDesc'] = $rule->getDescription();
	}
	
	$data['rewardPoint'] = null;
	$data['rewardPointValue'] = 0;
	$data['storeCredits'] = (int)$order->getData('customer_balance_amount');
	
	$giftCardsArr = unserialize($order->getGiftCards());
	$data['giftVoucherCode'] = isset($giftCardsArr['c']) ? $giftCardsArr['c'] : null;
	$data['giftVoucherValue'] = (int)$order->getGiftCardsAmount();
	
	$data['saleValueMRP'] = $productMrp;
	$data['saleValueOfferPrice'] = round($order->getData('base_subtotal_incl_tax'));
	
	$data['totalDiscountValue'] = round(($data['saleValueMRP'] - $data['saleValueOfferPrice']) + ( $data['couponDiscount'] + $data['rewardPointValue']));
	$data['totalDiscountPercentage'] = round(($data['totalDiscountValue'] * 100) / $data['saleValueMRP']);
	$data['amountToCustomer'] = ( ($data['saleValueOfferPrice']-$data['couponDiscount']) - $data['rewardPointValue'] );
		
	if($paymentMethod == 'ccavenuepay') {
		$data['COD'] = null;
		$data['Prepaid'] = $paymentMethod;
	}
	else if($paymentMethod == 'cashondelivery') {		
		$data['COD'] = $paymentMethod;
		$data['Prepaid'] = null;
	}
	//print_r($data);
	//print_r($order->getData());
	fputcsv($fp, $data);
	$mailData .= '<tr>
						<td style="padding:5px 3px; border-left:1px solid #666666; border-right:1px solid #666666;border-bottom:1px solid #666666; width:71px;">'.$data['emailID'].'</td>
						<td style="padding:5px 3px; border-right:1px solid #666666;border-bottom:1px solid #666666;">'.$data['orderID'].'</td>
						<td style="padding:5px 3px; text-align:right; border-right:1px solid #666666;border-bottom:1px solid #666666;">'.$data['saleQty'].'</td>
						<td style="padding:5px 3px; text-align:right; border-right:1px solid #666666;border-bottom:1px solid #666666;">'.(!empty($data['couponCode']) ? $data['couponCode']: '&nbsp;').'</td>
						<td style="padding:5px 3px; text-align:right; border-right:1px solid #666666;border-bottom:1px solid #666666;">'.$data['couponDiscount'].'</td>
						<td style="padding:5px 3px; text-align:right; border-right:1px solid #666666;border-bottom:1px solid #666666;">'.(!empty($data['rewardPoint']) ? $data['rewardPoint'] : '&nbsp;').'</td>
						<td style="padding:5px 3px; text-align:right; border-right:1px solid #666666;border-bottom:1px solid #666666;">'.$data['rewardPointValue'].'</td>
						<td style="padding:5px 3px; text-align:right; border-right:1px solid #666666;border-bottom:1px solid #666666;">'.$data['storeCredits'].'</td>
						<td style="padding:5px 3px; text-align:right; border-right:1px solid #666666;border-bottom:1px solid #666666;">'.(!empty($data['giftVoucherCode']) ? $data['giftVoucherCode'] : '&nbsp;').'</td>
						<td style="padding:5px 3px; text-align:right; border-right:1px solid #666666;border-bottom:1px solid #666666;">'.$data['giftVoucherValue'].'</td>
						<td style="padding:5px 3px; text-align:right; border-right:1px solid #666666;border-bottom:1px solid #666666;">'.$data['saleValueMRP'].'</td>
						<td style="padding:5px 3px; text-align:right; border-right:1px solid #666666;border-bottom:1px solid #666666;">'.$data['saleValueOfferPrice'].'</td>
						<td style="padding:5px 3px; text-align:right; border-right:1px solid #666666;border-bottom:1px solid #666666;">'.$data['totalDiscountValue'].'</td>
						<td style="padding:5px 3px; text-align:right; border-right:1px solid #666666;border-bottom:1px solid #666666;">'.$data['totalDiscountPercentage'].'</td>
						<td style="padding:5px 3px; text-align:right; border-right:1px solid #666666;border-bottom:1px solid #666666;">'.$data['amountToCustomer'].'</td>
						<td style="padding:5px 3px; text-align:right; border-right:1px solid #666666;border-bottom:1px solid #666666;">'.(!empty($data['COD']) ? $data['COD']: '&nbsp;').'</td>
						<td style="padding:5px 3px; text-align:right; border-right:1px solid #666666;border-bottom:1px solid #666666;">'.(!empty($data['Prepaid']) ? $data['Prepaid'] : '&nbsp;').'</td>								
					</tr>';
}

fclose($fp);

/*
 * Send mail
 */ 

$mailTemplate = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Sales Report State Wise</title>
</head>
<body leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">
<table width="980px" border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
    <td><table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td height="30"></td>
      </tr>      
      <tr>
        <td><img src="http://static.americanswan.com/Lecom_Magento/skin/frontend/enterprise/lecom/images-v3/as-logo-new.png" border="0" alt="American Swan"/></td>
      </tr> 
      <tr>
        <td height="10"></td>
      </tr>            
	  <tr>
        <td valign="top" style="text-align:left;font-weight:normal; font-size:13px; color:#000; font-family:Arial, Helvetica, sans-serif;"><strong>Order Data:</strong> '.$dateFromFormat.' To '.$toFromFormat.'</td>
      </tr>
      <tr>
        <td height="10"></td>
      </tr> 
	  <tr>
		<td valign="top">
			<table width="100%" cellspacing="0" cellpadding="0" style="font-size:12px;font-family:Arial, Helvetica, sans-serif;">
				<tr style="font-weight:bold; font-size:12px; color:#ffffff; font-family:Arial, Helvetica, sans-serif; background:#002060;">
					<td style="padding:0 3px 3px; text-align:left; border-left:1px solid #666666;border-right:1px solid #666666; border-bottom:0 none;  width:77px;">EMAIL</td>
					<td style="padding:0 3px 3px; text-align:left; border-right:1px solid #666666; border-bottom:0 none;">Order ID</td>
					<td style="padding:0 3p 3px; text-align:left; border-right:1px solid #666666; border-bottom:0 none;">Sale Qty</td>
					<td style="padding:0 3px 3px; text-align:left; border-right:1px solid #666666; border-bottom:0 none;">Coupon Code</td>
					<td style="padding:0 3px 3px; text-align:left; border-right:1px solid #666666; border-bottom:0 none;">Coupon Discount</td>
					<td style="padding:0 3px 3px; text-align:left; border-right:1px solid #666666; border-bottom:0 none;">Reward Points</td>
					<td style="padding:0 3px 3px; text-align:left; border-right:1px solid #666666; border-bottom:0 none;">Rewards Points Value</td>
					<td style="padding:0 3px 3px; text-align:left; border-right:1px solid #666666; border-bottom:0 none;">Store Credit</td>
					<td style="padding:0 3px 3px; text-align:left; border-right:1px solid #666666; border-bottom:0 none;">Gift Voucher Code</td>
					<td style="padding:0 3px 3px; text-align:left; border-right:1px solid #666666; border-bottom:0 none;">Gift voucher value</td>
					<td style="padding:0 3px 3px; text-align:left; border-right:1px solid #666666; border-bottom:0 none;">Sale  Value (MRP)</td>
					<td style="padding:0 3px 3px; text-align:left; border-right:1px solid #666666; border-bottom:0 none;">Sale Value (Offer Price)</td>
					<td style="padding:0 3px 3px; text-align:left; border-right:1px solid #666666; border-bottom:0 none;">Total Discount Value</td>
					<td style="padding:0 3px 3px; text-align:left; border-right:1px solid #666666; border-bottom:0 none;">Total Discount %</td>
					<td style="padding:0 3px 3px; text-align:left; border-right:1px solid #666666; border-bottom:0 none;">Amount To Customer</td>
					<td style="padding:0 3px 3px; text-align:left; border-right:1px solid #666666; border-bottom:0 none;">COD</td>
					<td style="padding:0 3px 3px; text-align:left; border-right:1px solid #666666; border-bottom:0 none;">Prepaid</td>
				</tr>'.$mailData.'				
			</table>
		</td>
	  </tr>	 
</table>
</body>
</html>';

try{
	$config = array('ssl' => 'tls',
					'auth' => 'login',
					'username' => 'AKIAJ3Z55BLHVVKK6WHQ',
					'password' => 'Aik7+O1z7U8XgoMu3sItIG2Yb1CKrn3EnkRxykXvRfZK');
	$transport = new Zend_Mail_Transport_Smtp('email-smtp.us-east-1.amazonaws.com', $config);
	$mail = new Zend_Mail();
	$mail->setFrom("service@americanswan.com","American Swan");
	$mail->addTo(array("deepak.kumar@taslc.com"));
	$mail->addCc(array("tech@taslc.com"));
	$mail->setSubject("COUPON WISE SALE REPORT | ".$dateFromFormat." To ".$toFromFormat);
	$mail->setBodyHtml($mailTemplate);		
	//file content is attached
	$attachment = file_get_contents($filePath);
	$mail->createAttachment(
		$attachment,
		Zend_Mime::TYPE_OCTETSTREAM,
		Zend_Mime::DISPOSITION_ATTACHMENT,
		Zend_Mime::ENCODING_BASE64,
		'sale_report_coupon_wise.csv'
	);
	 
	$mail->send($transport);
	echo "mail sent"."\n";

}
catch(Exception $e)
{
	echo $e->getMassage();
	echo "mail sending error";          
}
?>
