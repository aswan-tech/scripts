<?php
ini_set('display_errors', 1);
set_time_limit(0);
ini_set('memory_limit', '-1');
require '/home/cloudpanel/htdocs/www.americanswan.com/current/app/Mage.php';
Mage::app('default');

$fromDate = date('Y-m-d', strtotime('-10 day')).' 00:00:00';
$toDate = date('Y-m-d').' 23:59:59';

$dateFromFormat = date("d M Y", strtotime($fromDate));
$toFromFormat = date("d M Y", strtotime($toDate));
//echo "<pre>";

$orders = Mage::getModel('sales/order')->getCollection()
										->addAttributeToFilter('created_at', array('from'=>$fromDate, 'to'=>$toDate))
										->addAttributeToFilter('status', array('neq'=>array('Pending')))
										->addAttributeToFilter('status', array('neq'=>array('COD Verification Pending')))
										->addAttributeToFilter('status', array('neq'=>array('Cancelled')));
										//->setPageSize(100);
										//->addAttributeToSort('created_at', 'desc');
										//->setCurPage(1)->load();
//echo $orders->getSelect();
//die;

$data = array();
$ordersArr = array();
										
foreach($orders as $order) {
	$ordered_items = $order->getAllItems();
	//echo "<br>===============".$order->getIncrementId()."===============<br>";
	foreach($ordered_items as $item) {
		if( $item->getProductType() == 'configurable' ) {
			//print_r($item->getData());
			$productMrp = (int)$item->getQtyOrdered() * (int)$item->getData('product_mrp');
			$originalPrice = (int)$item->getQtyOrdered() * (int)$item->getData('original_price');
			
			$product = Mage::getModel('catalog/product')->load($item->getProductId());
			$department = $product->getAttributeText('product_department');
			$category = $product->getAttributeText('product_category');
			$category_new = str_replace(array(" ", "'", "-"),"", $category);
			
			if(empty($category)) { $category_new = $department; $category = $department; }
			
			//echo "[SKU: ".$item->getSku().", Qty:".(int)$item->getQtyOrdered()." , OfferPrice: ".(int)$item->getData('original_price').", Depart: ".$department.", Cat: ".$category."]<br>";
			$catalogDiscount = (int)$originalPrice-$productMrp;
			$catalogDiscount = str_replace("-", "", $catalogDiscount);
			
			$catalogDiscountPercentage = (($catalogDiscount * 100) / $productMrp);
			
			$ordersArr[$department]['data'][$category_new]['category'] = $category;
			$ordersArr[$department]['data'][$category_new]['orders'][$order->getIncrementId()] = $order->getIncrementId();
			$ordersArr[$department]['data'][$category_new]['qty'][] = (int)$item->getQtyOrdered();
			$ordersArr[$department]['data'][$category_new]['saleMrp'][] = (int)$productMrp;
			$ordersArr[$department]['data'][$category_new]['originalPrice'][] = (int)$originalPrice;
			$ordersArr[$department]['data'][$category_new]['couponDiscountValue'][] = (int)$item->getData('discount_amount');
			$ordersArr[$department]['data'][$category_new]['catalogDiscValue'][] = (int)$catalogDiscount;
			$ordersArr[$department]['data'][$category_new]['catalogDisPercentage'][] = (int)$catalogDiscountPercentage;
		}
	}
}

/*
 * export in csv
 */ 
 
if(count($ordersArr)) {
	$header = array("Department", "Sub-Category","Total Orders","Sale Qty","Total Sale (MRP)","Avg Product Discount %","Sale on Offer Price","Product Discount Value","Avg Coupon Discount %","Coupon Discount Value","Amount to Customer","Avarage Order Qty","Avarage Order Value");

$filePath = "/tmp/sale_report_department_wise.csv";
$output = fopen($filePath, 'w');
fputcsv($output, $header);
$mainData = '';	
foreach($ordersArr as $deptName=>$deptDataArr) {
	
	$deptNameNew = $deptName;
	$totalOrders = 0;
	$totalQty = 0;
	$totalSaleMrp = 0;
	$totalAvgProdDiscPercentage = 0;
	$totalSaleOnOfferPrice = 0;
	$totalProductDiscountValue = 0;
	$totalAvgCouponDiscPercentage = 0;
	$totalCouponDiscValue = 0;
	$totalAmountToCustomer = 0;
	$totalAvgOrderedQty = 0;
	$totalAvgOrderedValue = 0;
	$i = 0;
	foreach($deptDataArr['data'] as $categoryArr ) {
		
		$orders = count($categoryArr['orders']);	
		$totQty = getTotalQty($categoryArr['qty']);
		$grandValueMRP = getGrandMrp($categoryArr['saleMrp']); // GVM
		
		/*
		 * AVERAGE MRP
		 * avg_mrp = total_mrp / total_qty
		 */
		 
		 $avgMrp =  ($grandValueMRP / $totQty);
		 
		 /*
		  * AVERAGE CATALOG DISCOUNT
		  * 
		  * avg_catalog_discount = sum_of_catalog_discount / total_qty
		  */ 
		 $totalCatalogDiscount = getTotalCatalogDiscount($categoryArr['catalogDisPercentage']);
		 $avgCatalogDiscount = $totalCatalogDiscount / $totQty;
		 
		 /*
		  * AVERAGE CATALOG DISCOUNT IN PERCENTAGE
		  * 
		  * avg_catalog_discount_in_percentage = ( avg_catalog_discount * 100 ) / grand_value_mrp
		  */ 
		
		$avgCatalogDisInPercentage = (($avgCatalogDiscount * 100 ) / $grandValueMRP);
		
		/*
		 * OFFER PRICE / MRP
		 * 
		 * offer_mrp = grand_value_mrp - sum_of_catalog_discount_value
		 */ 
		$totalCatalogDiscValue = getTotalCatalogDiscountValue($categoryArr['catalogDiscValue']);
		$offerMrp = $grandValueMRP - $totalCatalogDiscValue;
		
		/*
		 * AVERAGE COUPON DISCOUNT VALUE
		 * 
		 * avg_coupon_discount_value = sum_of_coupon_discount_value / total_qty
		 */
		 
		 $totCouponDiscValue = getTotalCouponDiscValue($categoryArr['couponDiscountValue']);
		 $avgCouponDiscountValue = $totCouponDiscValue / $totQty;
		 
		 /*
		  * AVERAGE COUPON DISCOUNT VALUE IN PERCENTAGE
		  * 
		  * avg_coupon_disc_value_in_percentage = (avg_coupon_discount_value * 100 ) / offer_mrp
		  */  
		
		$avgCouponDiscValueInPercentage = (($avgCouponDiscountValue *100) / $offerMrp); 
				
		/*
		 * AMOUNT TO CUSTOMER
		 * 
		 * amount_to_customer = sum_of_original_price - sum_coupon_discount_value
		 */ 
		$totOriginalPrice = getTotalOriginalPrice($categoryArr['originalPrice']);
		$amountToCustomer = ( $totOriginalPrice - $totCouponDiscValue);
		
		/*
		 * AVERAGE SELLING PRICE
		 * 
		 * avg_selling_price = amount_to_customer / total_qty
		 */   
		 
		 $avgSellingPrice = ($amountToCustomer / $totQty);
		 
		/*
		* AVERAGE ORDERED QUANTITY
		* avg_ordered_qty = sale_qty / total_orders
		*/ 
		$avgOrderedQty = ($totQty / $orders);
		
		
		/*
		 */
		 
		$totalOrders = $totalOrders + $orders;
		$totalQty = $totalQty + $totQty;
		$totalGrandValueMRP = $totalGrandValueMRP + $grandValueMRP;		
		$totalAvgMrp = $totalAvgMrp + $avgMrp;
		$totalAvgCatalogDisInPercentage = $totalAvgCatalogDisInPercentage + $avgCatalogDisInPercentage;
		$totalAvgCatalogDiscountValue = $totalAvgCatalogDiscountValue + $avgCatalogDiscount;
		$totalOfferMrp = $totalOfferMrp + $offerMrp;
		$totalAvgCouponDiscountValue = $totalAvgCouponDiscountValue + $avgCouponDiscountValue;
		$totalAvgCouponDiscValueInPercentage = $totalAvgCouponDiscValueInPercentage + $avgCouponDiscValueInPercentage;
		$totalAmountToCustomer = $totalAmountToCustomer + $amountToCustomer;
		$totalAvgSellingPrice = $totalAvgSellingPrice + $avgSellingPrice;
		$totalAvgOrderedQty = $totalAvgOrderedQty + $avgOrderedQty;
		
		if($i > 0) { $deptName = ''; }
		
		$categoryDataArr['department'] = $deptName;
		$categoryDataArr['category'] = $categoryArr['category'];
		$categoryDataArr['totalOrders'] = $orders;
		$categoryDataArr['saleQty'] = $totQty;
		$categoryDataArr['gvm'] = $grandValueMRP;
		$categoryDataArr['avgMrp'] = $avgMrp;
		$categoryDataArr['avgCatalogDisInPercentage'] = $avgCatalogDisInPercentage;
		$categoryDataArr['avgCatalogDiscount'] = $avgCatalogDiscount;
		$categoryDataArr['offerMrp'] = $offerMrp;
		$categoryDataArr['avgCouponDiscountValue'] = $avgCouponDiscountValue;
		$categoryDataArr['avgCouponDiscValueInPercentage'] = $avgCouponDiscValueInPercentage;
		$categoryDataArr['amountToCustomer'] = $amountToCustomer;
		$categoryDataArr['avgSellingPrice'] = $avgSellingPrice;
		$categoryDataArr['avgOrderQty'] = $avgOrderedQty;
		
		fputcsv($output, $categoryDataArr);
		//print_r($categoryArr);
		//print_r($categoryDataArr);
		
		$mainData .= '<tr style="font-weight:Normal; font-size:11px;">
					<td height="30">'.$deptName.'</td>
					<td>'.$categoryArr['category'].'</td>
					<td>'.$orders.'</td>
					<td>'.$totQty.'</td>
					<td>'.number_format($grandValueMRP, 2).'</td>
					<td>'.number_format($avgMrp, 2).'</td>
					<td>'.number_format($avgCatalogDisInPercentage,2).'</td>
					<td>'.number_format($avgCatalogDiscount,2).'</td>
					<td>'.number_format($offerMrp, 2).'</td>
					<td>'.number_format($avgCouponDiscountValue, 2).'</td>
					<td>'.number_format($avgCouponDiscValueInPercentage, 2).'</td>
					<td>'.number_format($amountToCustomer, 2).'</td>
					<td>'.number_format($avgSellingPrice,2).'</td>
					<td>'.number_format($avgOrderedQty,2).'</td>
				</tr>';
		$i++;
	}
		
	$deptTotalRow = array($deptNameNew." Total ", null, $totalOrders, $totalQty, $totalGrandValueMRP, $totalAvgMrp, $totalAvgCatalogDisInPercentage, $totalAvgCatalogDiscountValue, $totalOfferMrp, $totalAvgCouponDiscountValue, $totalAmountToCustomer, $totalAvgSellingPrice, $totalAvgOrderedQty);
	
	$mainData .= '<tr style="font-weight:bold; font-size:12px; color:#fff; background:#002060; ">
					<td colspan="2">'.$deptNameNew." TOTAL ".'</td>					
					<td height="30">'.$totalOrders.'</td>
					<td>'.$totalQty.'</td>
					<td>'.number_format($totalGrandValueMRP, 2).'</td>
					<td>'.number_format($totalAvgMrp, 2).'</td>
					<td>'.number_format($totalAvgCatalogDisInPercentage, 2).'</td>
					<td>'.number_format($totalAvgCatalogDiscountValue, 2).'</td>
					<td>'.number_format($totalOfferMrp, 2).'</td>
					<td>'.number_format($totalAvgCouponDiscountValue, 2).'</td>
					<td>'.number_format($totalAvgCouponDiscValueInPercentage, 2).'</td>
					<td>'.number_format($totalAmountToCustomer, 2).'</td>
					<td>'.number_format($totalAvgSellingPrice, 2).'</td>
					<td>'.number_format($totalAvgOrderedQty, 2).'</td>
				</tr>';
				
	fputcsv($output, $deptTotalRow);
}
fclose($output);
}

function getTotalOriginalPrice($OriginalPriceArr) {
	$totOriginalPrice = 0;
	foreach($OriginalPriceArr as $price){
		$totOriginalPrice = $totOriginalPrice + $price;
	}
	return $totOriginalPrice;
}

function getTotalCouponDiscValue($couponDiscountArr) {
	$couponDiscSum = 0;
	foreach($couponDiscountArr as $discountAmount){
		$couponDiscSum = $couponDiscSum + $discountAmount;
	}
	return $couponDiscSum;
}

function getTotalCatalogDiscountValue($catalogDisArr) {
	$totCatalogDiscValue = 0;
	foreach($catalogDisArr as $discount ){
		$totCatalogDiscValue = $totCatalogDiscValue + $discount;
	}
	return $totCatalogDiscValue;
}


function getTotalCatalogDiscount($catalogDisArr) {
	$totCatalogDis = 0;
	foreach($catalogDisArr as $discount ){
		$totCatalogDis = $totCatalogDis + $discount;
	}
	return $totCatalogDis;
}

function getTotalQty($qtyArr) {
	$totQty = 0;
	foreach($qtyArr as $qty ){
		$totQty = $totQty + $qty;
	}
	return $totQty;
}

function getGrandMrp($mrpArr) {
	$mrpSum = 0;
	foreach($mrpArr as $mrp ){
		$mrpSum = $mrpSum + $mrp;
	}
	return $mrpSum;
}

$mailTemplate = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>American Swan</title>
</head>
<body leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">
<table width="900px" border="0" align="center" cellpadding="0" cellspacing="0">
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
			<table width="100%" border="1" cellspacing="2" cellpadding="2" style="font-weight:bold; font-size:12px;">				
				<tr style="font-weight:bold; font-size:12px; color:#fff; background:#002060;">
					<td height="30">Department</td>
					<td>Category</td>
					<td>Total Orders</td>
					<td>Sale Qty</td>
					<td>GMV</td>
					<td>Avg MRP</td>
					<td>Avg Catalog Discount %</td>
					<td>Avg Catalog Value</td>
					<td>Offer Price</td>
					<td>Avg Coupon Discount Value</td>
					<td>Avg Coupon Discount %</td>
					<td>Amount To Customer</td>
					<td>Avg Selling Price</td>
					<td>Avg Order Qty</td>
				</tr>
				'.$mainData.'
			</table>
		</td>
	  </tr>
	  </table>
	  </td>
	  </tr>
</table>
</body>
</html>';
//echo $mailTemplate;die;
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
	$mail->setSubject("CATEGORY WISE SALE REPORT | ".$dateFromFormat." To ".$toFromFormat);
	$mail->setBodyHtml($mailTemplate);		
	//file content is attached
	$attachment = file_get_contents($filePath);
	$mail->createAttachment(
		$attachment,
		Zend_Mime::TYPE_OCTETSTREAM,
		Zend_Mime::DISPOSITION_ATTACHMENT,
		Zend_Mime::ENCODING_BASE64,
		'sale_report_department_wise.csv'
	);
	 
	$mail->send($transport);
	echo "mail sent"."\n";

}
catch(Exception $e)
{
	echo $e->getMassage();
	echo "mail sending error";          
}

die;
?>
