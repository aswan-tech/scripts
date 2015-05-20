<?php
require_once( '../app/Mage.php' );
Mage::app();

$appId = '71a82a9cfb17475cbd3d7fe40bfe2cd0';
$data = json_decode( file_get_contents( 'http://openexchangerates.org/api/latest.json?app_id=' . $appId ), true );

    // All bases are USD
$exchangeRates = array();
$neededCurrencies = array( 'INR', 'USD', 'EUR', 'GBP' );
$newBase = $data['rates']['INR'];

foreach( $data['rates'] as $curr => $val ) {
    if( in_array( $curr, $neededCurrencies ) ) {
        $exchangeRates[ $curr ] = round( $val / $newBase, 11 );
    }
}

foreach( $exchangeRates as $c => $v ) {
    $currData[] = array( 'currency_from' => 'INR', 'currency_to' => $c, 'rate' => $v );
}
$adapter = Mage::getSingleton('core/resource')->getConnection('core_write');
$adapter->insertOnDuplicate( 'directory_currency_rate' , $currData, array('rate') );
