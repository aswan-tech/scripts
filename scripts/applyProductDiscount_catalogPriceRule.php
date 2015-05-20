<?php 
$mageFilename = '/home/cloudpanel/htdocs/www.americanswan.com/current/app/Mage.php';
require_once $mageFilename;
ini_set('display_errors', 1);
Mage::app();
$script_start = microtime(true);
echo "[" . date('Y-m-d H:i:s') . "] script started... \n";
$discount_options = array();
$discount_options[706] = 0;
$discount_options[507] = 5;
$discount_options[508] = 10;
$discount_options[509] = 15;
$discount_options[510] = 20;
$discount_options[511] = 25;
$discount_options[512] = 30;
$discount_options[513] = 35;
$discount_options[514] = 40;
$discount_options[515] = 45;
$discount_options[652] = 47;
$discount_options[516] = 50;
$discount_options[517] = 55;
$discount_options[518] = 60;
$discount_options[519] = 65;
$discount_options[800] = 68;
$discount_options[505] = 70;
$discount_options[801] = 72;
$discount_options[506] = 75;

$write = Mage::getSingleton('core/resource')->getConnection('core_write');
$read = Mage::getSingleton('core/resource')->getConnection('core_read');
if(isset($_GET['argument1']) || isset($argv[1])) {
    if ($_GET) {
        $ruleIds = $_GET['argument1'];
    } else {
        $ruleIds = $argv[1];
    }
} else {
    echo("ERROR:: Can't find arguments.\n");
    die();
}
$ruleIds = explode(",", $ruleIds);

echo("Script Started......\n");
echo("\n\nResetting all discuount attribute to \"No Discount\" (0) for all products.\n\n");
$sql = "UPDATE `catalog_product_entity_int` SET value=706 WHERE `attribute_id`=220;";
$write->query($sql);

$catalog_rules = array();
foreach($ruleIds as $ruleId) {
    $ruleId = trim($ruleId);
    $catalog_rule = Mage::getModel('catalogrule/rule')->load((int)$ruleId);
    
    if (is_null($catalog_rule)) {
        echo("ERROR:: Wrong rule id: " . $ruleId . ".\n");
        die();
    }
    if ($catalog_rule->getData('is_active') != 1) {
        echo("ERROR:: Catalog rule, rule id " . $ruleId . " is not active.\n");
        die();
    }
    if ($catalog_rule->getData('from_date') != "" && 
            strtotime($catalog_rule->getData('from_date')) > strtotime(date('Y-m-d'))) {
        echo("ERROR:: Catalog rule, rule id " . $ruleId . " from date is a future date.\n");
        die();
    }
    if ($catalog_rule->getData('to_date') != "" && 
            strtotime($catalog_rule->getData('to_date')) < strtotime(date('Y-m-d' . ' 23:59:59'))) {
        echo("ERROR:: Catalog rule, rule id " . $ruleId . " to date has been passed.\n");
        die();
    }
    if ($catalog_rule->getData('simple_action') != 'by_percent') {
        echo("ERROR:: Catalog rule, rule id " . $ruleId . " is not set for percent discount.\n");
        die();
    }
    $discount = $catalog_rule->getData('discount_amount');
    if (!in_array($discount, $discount_options)) {
        echo("ERROR:: Discount value " . $discount . " is not defined in discount options.\n");
        die();
    }
    $catalog_rules[$ruleId] = $catalog_rule;
}
$wait = 500;

foreach($catalog_rules as $ruleId => $catalog_rule) {
    echo ("\n\n\n\nUpdating rule id: " . $ruleId . "\n\n");
    $discount_id = array_search($catalog_rule->getData('discount_amount'), $discount_options);
    $productIds = $catalog_rule->getMatchingProductIds();
    $allProductIds = array();

    echo("Fetching all products for " . count($productIds) . " products\n");

    for($p = 0; true; $p++) {
        $from = $wait * $p;
        $pIds = array_slice($productIds, $from, $wait);
        if (count($pIds) <= 0) {
            break;
        }
        foreach($pIds as $pId) {
            $allProductIds[] = $pId;
        }
        $pIdsSql = "'" . implode("','", $pIds) . "'";
        $sql = "SELECT product_id FROM catalog_product_super_link WHERE parent_id IN (".$pIdsSql.");";
        $result = $read->fetchAll($sql);
        foreach($result as $row) {
            $allProductIds[] = $row['product_id'];
        }
        if (count($pIds) <= 0 || count($pIds) < $wait) {
            break;
        }
    }
	
    $productIds = array_unique($allProductIds);

    echo("Running for " . count($productIds) . " products\n");
    $wait = 1;
    for($p = 0; $p < count($productIds); $p++) {
        $pId = $productIds[$p];
        try {
            $sql = "SELECT * FROM catalog_product_entity_int WHERE entity_id = '".$pId."' AND `attribute_id`=220;";
            $result = $read->fetchAll($sql);
            if (count($result) > 0) {
                $sql = "UPDATE `catalog_product_entity_int` SET value='".$discount_id."' WHERE `attribute_id`=220 AND entity_id IN (".$pId.")";
                $write->query($sql);
                echo ($p . " of " . count($productIds) . " : Updated product id " .$pId . "\n");
            } else {
                $sql = "INSERT INTO `catalog_product_entity_int` (entity_type_id, attribute_id, store_id, entity_id, value) VALUES('4', '220', '0', '".$pId."', '".$discount_id."');";
                $write->query($sql);
                echo ($p . " of " . count($productIds) . " : Inserted product id " .$pId . "\n");
            }
        } catch (Exception $ex) {
            echo $sql . "\nCaught exception: ",  $ex->getMessage(), "\n";
        }
    }
}
echo "success\n";
$time_elapsed = microtime(true) - $script_start;
echo "[" . date('Y-m-d H:i:s') . "] script ended. Time taken: " . (round($time_elapsed / 60)) . " minut(s) \n";
