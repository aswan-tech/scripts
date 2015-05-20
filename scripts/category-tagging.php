<?php
//not in use

require_once '/home/cloudpanel/htdocs/www.americanswan.com/current/app/Mage.php';
umask(0);
Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

//men - 6
//women - 8

$department = array('men'=> '6','women' => '8','accessories' => '3','footwear' => '4');//,'footwear'=>'4','home'=>'5','accessories'=>'3','beauty'=>'7'); 
//$department = array('foot' => '4');//,'footwear'=>'4','home'=>'5','accessories'=>'3','beauty'=>'7'); 
//casuals shirts - 86, tees - 101, polo - 94, pullovers - 100, blazzers - 92, casuals trousers -87, denim -88, jackets -83, shorts -96, sweatpants - 98, sweatshirts -99, trackpants - 103
$men_map = array('86'=>'415','101'=>'461','94'=>'442','100'=>'444','92'=>'412','87'=>'416','88'=>'398','83'=>'428','96'=>'447','98'=>'458','99'=>'459','103'=>'464');

//top -189, casualshirt -125, teeshirt- 146, polo-138, bottoms- 190,pullovers - 144, jacket -133, casual trousers - 126, denim- 127, sweat pants-142,
//sweat shirt -143 , track pant- 148, dress - 251, shorts/skirts - 253, Leggings & Stockings- 392 
$women_map = array('189'=>'462','125'=>'415','146'=>'461','138'=>'442','190'=>'413','144'=>'444','133'=>'428','126'=>'416','127'=>'398','142'=>'458','143'=>'459','148'=>'464','251'=>'400','253'=>'448','392'=>'877');

//belts-men - 236, belts-women - 237, bags -23, socks-men - 19, socks-women - 242, jwellery - 239, sunglases-men - 234, sunglases-women - 235, wallets - 25, watches-man - 241, watches-women - 26, scarves - 17
$accessories_map =  array('236'=>'410','237'=>'411','23'=>'403','19'=>'450','242'=>'451','239'=>'429','234'=>'456','235'=>'457','25'=>'471','241'=>'472','26'=>'473','17'=>'446');	

//flipflops - 34, sandals -37, menshoes - 185, womenshoes -186
$footwear_map = array('34' => '705','37' => '704', '185'=> '434', '186'=>'475'); 

foreach($department as $dept => $value)
{

if($value == "6") $search_map =  $men_map;
if($value == "8") $search_map =  $women_map;
if($value == "3") $search_map =  $accessories_map;
if($value == "4") $search_map =  $footwear_map;
$catsid = array_keys($search_map);

echo "Loading products for department $dept \n";

$collection = Mage::getModel('catalog/category')->load($value)
		->getProductCollection()
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('type_id', 'configurable')
                ->addAttributeToSort('entity_id', 'DESC');



$count = 0;

foreach($collection as $product)
{
        echo $product->getId(). ",";
	$product_cat =  $product->getProductCategory();
	$categories = $product->getCategoryIds();
	//get sub categories of main category
        $children = Mage::getModel('catalog/category')->getCategories($value);
        foreach($children as $category)
        {
		$ignore_cats = array('178','344','345','179','348','349');
                if($category->getIsActive() && in_array($category->getId(),$categories) && $category->getName()!= "New arrivals" && !in_array($category->getId(),$ignore_cats)) {
			$subcategory =  $category->getId();
			echo $subcategory.",";	
                         
                }
        }
		
	if(in_array($subcategory,$catsid))
	{
		if($product_cat != $search_map[$subcategory])
		{
			Mage::getSingleton('catalog/product_action')->updateAttributes(array($product->getId()), array('product_category'=> $search_map[$subcategory]), 1);
			echo "updated \n";
			$count ++;
		}
		else
		{
			echo "already updated \n";
		}
	}
	else
	{
		echo $subcategory." , SOME SHIT\n";
	}
	
}

echo $count. " products tagged for department  $dept \n";
}
