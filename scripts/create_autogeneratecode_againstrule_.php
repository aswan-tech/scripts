<?php
set_time_limit(0);
require_once '/home/cloudpanel/htdocs/www.americanswan.com/current/app/Mage.php';
umask(0);
$app = Mage::app('default');
$total = 200000;
$no_of_loops = $total/50000;
$each = array();
for($i=1;$i<=$no_of_loops;$i++){
   $each[$i] ='Coupon'.$i.'.txt'; 
}
foreach($each as $k=>$file_name){
      $rule_id = '10411';
      $rule = Mage::getModel('salesrule/rule')->load($rule_id);
      $generator = Mage::getModel('salesrule/coupon_massgenerator');
      $parameters = array(
          'count'=>50000,
          'format'=>'alphanumeric',
          'dash_every_x_characters'=>'',
          'prefix'=>'',
          'suffix'=>'',
          'length'=>10
      );

      if( !empty($parameters['format']) ){
        switch( strtolower($parameters['format']) ){
          case 'alphanumeric':
          case 'alphanum':
            $generator->setFormat( Mage_SalesRule_Helper_Coupon::COUPON_FORMAT_ALPHANUMERIC );
            break;
          case 'alphabetical':
          case 'alpha':
            $generator->setFormat( Mage_SalesRule_Helper_Coupon::COUPON_FORMAT_ALPHABETICAL );
            break;
          case 'numeric':
          case 'num':
            $generator->setFormat( Mage_SalesRule_Helper_Coupon::COUPON_FORMAT_NUMERIC );
            break;
        }
      }

      $generator->setDash( !empty($parameters['dash_every_x_characters'])? (int) $parameters['dash_every_x_characters'] : 0);
      $generator->setLength( !empty($parameters['length'])? (int) $parameters['length'] : 9);
      $generator->setPrefix( !empty($parameters['prefix'])? $parameters['prefix'] : '');
      $generator->setSuffix( !empty($parameters['suffix'])? $parameters['suffix'] : '');

      // Set the generator, and coupon type so it's able to generate
      $rule->setCouponCodeGenerator($generator);
      $rule->setCouponType( Mage_SalesRule_Model_Rule::COUPON_TYPE_AUTO )->save();

      // Get as many coupons as you required
        $count = !empty($parameters['count'])? (int) $parameters['count'] : 1;
        if(file_exists("/tmp/".$file_name) =='1'){
          $file_name = 'Coupon'.rand(5000,4000).'.txt';
        }
          
        for( $i = 0; $i < $count; $i++ ){
          $coupon = $rule->acquireCoupon();
          $code = $coupon->getCode();
          file_put_contents('/tmp/'.$file_name,$code."\n",FILE_APPEND);
          echo $code."\n";
        }

        echo "Iteration complete: ".$file_name."  Completed"."\n";
}

?>
