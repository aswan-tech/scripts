
<?php
require_once '/home/cloudpanel/htdocs/www.americanswan.com/current/app/Mage.php';
umask(0);
Mage::app();
function sendMail($message)
{
    $date = date('d-M-Y');
    $filename = "live-product-report-".date('Y-m-d').'.csv';
    try{

        $config = array('ssl' => 'tls',
                'auth' => 'login',
		'username' => 'AKIAJ3Z55BLHVVKK6WHQ',
                                        'password' => 'Aik7+O1z7U8XgoMu3sItIG2Yb1CKrn3EnkRxykXvRfZK',
                                        'port' => '587');
                //'username' => '',
                //'password' => 'Aik7+O1z7U8XgoMu3sItIG2Yb1CKrn3EnkRxykXvRfZK');
        $transport = new Zend_Mail_Transport_Smtp('email-smtp.us-east-1.amazonaws.com', $config);

            $mail = new Zend_Mail();
            $mail->setFrom("service@americanswan.com","Report");
            $mail->addTo("anurag.rajpal@taslc.com");
            $mail->addCc(array("vibhu.aggarwal@taslc.com", "merc@taslc.com", "ops@taslc.com", "tech@taslc.com", "marketing@taslc.com", "sales@taslc.com" ,"onlinesales@taslc.com", "sourcing@taslc.com", "scm@taslc.com", "crm@taslc.com", "deepti.beri@taslc.com"));
            $mail->setSubject("Live Products Report | $date");
            $mail->setBodyHtml($message); 
        
            // this is for to set the file format
        $mail->setType(Zend_Mime::MULTIPART_RELATED);
            $content = file_get_contents("/tmp/$filename");
            $at = new Zend_Mime_Part($content);
            
            $at->type        = 'application/csv';
            $at->disposition = Zend_Mime::DISPOSITION_INLINE;
            $at->encoding    = Zend_Mime::ENCODING_8BIT;
            $at->filename    = $filename;
            $mail->addAttachment($at);
            $mail->send($transport);
	    echo "Mail Sent Successfully"."\n";
        }catch(Exception $e)
        {
            echo $e->getMessage();      
        }
}

$department = array('Men'=> '6','Women' => '8','Accessories' => '3','Footwear' => '4');

$men_map = array('101'=>'T-Shirts','86'=>'Casual Shirts','94'=>'Polos','100'=>'Pull Overs','92'=>'Blazers','87'=>'Casual Trousers','88'=>'Denim','83'=>'Jacquets','96'=>'Shorts','98'=>'SweatPants','99'=>'Sweat Shirts','103'=>'Track Pants');

$women_map = array('125'=>'Casual Shirts','146'=>'T-Shirts','138'=>'Polos','144'=>'Pull Overs','133'=>'Jackets','126'=>'Casual Trousers','127'=>'Denim','142'=>'Sweat Pants','143'=>'Sweat Shirt','148'=>'Track Pants','251'=>'Dress','253'=>'Short/Skirts','371' => 'Jump Suit','372' => 'Pajama','392' => 'Leggings & Stocking');

$accessories_map =  array('236'=>'Belts-men','237'=>'Belts-women','23'=>'Bags','19'=>'Socks-men','242'=>'Socks-women','239'=>'Jewellery','234'=>'Sunglasses-men','235'=>'Sunglasses-women','25'=>'Wallets','241'=>'Watches-men','26'=>'Watches-women','17'=>'Scarves',);

$footwear_map = array('34' => 'Flipflops', '37' => 'Sandals', '185'=> 'MenShoes', '186'=>'WomenShoes');

$onHoldOrders = array();
$onHoldSkus = array();
function getOnHoldOrders() {
    $onHoldOrders = array();
    $orders = Mage::getModel('sales/order')->getCollection()->addFieldToFilter('status', array('pending','COD_Verification_Pending'));
    foreach($orders as $order) {
        $allItems = $order->getAllItems();
        foreach($allItems as $item) {
            $type = $item->product_type;
            if($type == 'configurable') {
                $ConfigProd = Mage::getModel('catalog/product')->load($item->getData('product_id'));
                $itemsinstock = 0;
                $childProducts = $ConfigProd->getTypeInstance(true)->getUsedProducts ( null, $ConfigProd);
                foreach ($childProducts as $simple) {
                    $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($simple)->getQty();
                    $stock = round($stock,2);
                    $itemsinstock+= $stock;
                }
                if ($itemsinstock <= 0) {
                    $onHoldSkus[] = $ConfigProd->getSku();
                }
                if(array_key_exists($ConfigProd->getSku(), $onHoldOrders)) {
                    $onHoldOrders[$ConfigProd->getSku()] = (int) ($onHoldOrders[$ConfigProd->getSku()] + $item->qty_ordered);
                } else {
                    $onHoldOrders[$ConfigProd->getSku()] = (int) $item->qty_ordered;
                }
		echo "Sku:".$ConfigProd->getSku()." Qty:".$onHoldOrders[$ConfigProd->getSku()]."\n";
                
            }
        }
    }
    return array($onHoldOrders, $onHoldSkus);
}

list($onHoldOrders, $onHoldSkus) = getOnHoldOrders();
$message = '
<html><body>
<table width="100%" border="1" cellspacing="0" cellpadding="5" style="font:12px Arial, Helvetica, sans-serif; color:#666;border:1px solid #ccc;max-width:600px">
  <tr style="background: none repeat scroll 0% 0% gray; color: white; font-weight: bold;">
        <td width="25%" height="32" align="center" valign="middle" style="border-right:1px solid #030;"><strong>Department</strong></td>
        <td width="25%" align="center" valign="middle" style="border-right:1px solid #030;"><strong>Category</strong></td>
        <td width="25%" align="center" valign="middle"><strong>Live Products</strong></td>
        <td width="25%" align="center" valign="middle"><strong>Hold Products</strong></td>
  </tr>';

//file to send as attachment
//$fp = fopen('/home/ubuntu/cronlogs/g-files/live-product-report-'.date('Y-m-d').'.csv', 'w');
$fp = fopen('/tmp/live-product-report-'.date('Y-m-d').'.csv', 'w');
$header = array('Product Id', 'Sku', 'Name', 'Url', 'Category', 'Department', 'OnHold Qty', 'Live Qty');
fputcsv($fp, $header);


foreach($department as $dept => $value)
{
// to calculate dept total
$dept_total_live = 0;
$dept_total_hold = 0;

if($value == "6") $search_map =  $men_map;
if($value == "8") $search_map =  $women_map;
if($value == "3") $search_map =  $accessories_map;
if($value == "4") $search_map =  $footwear_map;
$catsid = array_keys($search_map);

foreach($search_map as $catId => $category)
{
    $read = Mage::getSingleton('core/resource')->getConnection('core_read');
    $query = " SELECT cpf.name,cpf.url_path,e.sku, e.entity_id, c.category_id, COUNT( pr.child_id ) AS totSimpleProd, sum(st.qty) as qty FROM catalog_product_entity AS e INNER JOIN catalog_category_product AS c ON ( e.entity_id = c.product_id ) INNER JOIN catalog_product_relation AS pr ON ( e.entity_id = pr.parent_id )  INNER JOIN cataloginventory_stock_item AS st ON ( pr.child_id = st.product_id )   INNER JOIN catalog_product_entity_int AS cped ON ( cped.entity_id = e.entity_id ) INNER JOIN catalog_product_flat_1 As cpf ON ( cpf.entity_id = e.entity_id  ) WHERE c.category_id =  '$catId' AND e.type_id = 'configurable' AND cped.attribute_id =  '96' AND cped.value = '1' GROUP BY e.entity_id"; 

    $result = $read->fetchAll($query);
    $availableQuantity = 0;
    $totalAvailableQtyInCat = 0;
    $totalHoldSkusInCat = 0;
    $totalAvailableSkusInCat = 0;
    foreach($result as $data) {
        $availableQuantity = $data['qty'];
        $onHoldQty = 0;
        if (isset($onHoldOrders[$data['sku']])) {
            $onHoldQty = $onHoldOrders[$data['sku']];
        }
        if ($availableQuantity > 0 || $onHoldQty > 0) {
            $totalAvailableQtyInCat += $availableQuantity;
	    echo "Put in csv : Sku:".$data['sku']." On Hold Qty:".$onHoldQty." Available Qty".$availableQuantity."\n";
            $line = array($data['entity_id'],$data['sku'],$data['name'],"http://www.americanswan.com/".$data['url_path'],$category,$dept,$onHoldQty,$availableQuantity);
            fputcsv($fp,$line);
        }
        if ($availableQuantity > 0) {
            $totalAvailableSkusInCat++;
        } else if (in_array($data['sku'], $onHoldSkus)) {
            $totalHoldSkusInCat++;
        }
    }
    
    $message.='<tr>
    <td width="33%" height="22" align="left" valign="middle" style="padding:0 0 0 5px;border-bottom:1px solid #666">'.$dept.'</td>
    <td width="34%" align="center" valign="middle" style="padding:0 0 0 5px;border-bottom:1px solid #666">'.$category.'</td>
    <td width="33%" align="center" valign="middle" style="padding:0 0 0 5px;border-bottom:1px solid #666">'.$totalAvailableSkusInCat.'</td>
    <td width="33%" align="center" valign="middle" style="padding:0 0 0 5px;border-bottom:1px solid #666">'.$totalHoldSkusInCat.'</td>
    </tr>';
        $dept_total_live += $totalAvailableSkusInCat;  
        $dept_total_hold += $totalHoldSkusInCat; 
}
    $message.='<tr style="background: none repeat scroll 0% 0% lightblue; font-weight: bold;">
        <td width="33%" height="23" colspan="2" align="left" valign="middle" style="border-right:1px solid #030;padding:0 0 0 5px;">'.$dept.' Total</td>
        <td width="33%" align="center" valign="middle" style="padding:0 0 0 5px;">'.$dept_total_live.'</td>
        <td width="33%" align="center" valign="middle" style="padding:0 0 0 5px;">'.$dept_total_hold.'</td>
        </tr>';
        $grand_total_live +=$dept_total_live;
        $grand_total_hold +=$dept_total_hold;  
}
$message.='<tr style="background: none repeat scroll 0% 0% lightblue; font-weight: bold;">
        <td width="33%" height="23" colspan="2" align="left" valign="middle" style="border-right:1px solid #030;padding:0 0 0 5px;">Grand Total</td>
        <td width="33%" align="center" valign="middle" style="padding:0 0 0 5px;">'.$grand_total_live.'</td>
        <td width="33%" align="center" valign="middle" style="padding:0 0 0 5px;">'.$grand_total_hold.'</td>
        </tr>';
$message.='</table></body></html>';
fclose($fp);
//echo($message);
sendMail($message);
