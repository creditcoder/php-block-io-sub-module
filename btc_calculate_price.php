<?php
define('CRYPTOMV2_INSTALLED',TRUE);
ob_start();
session_start();
error_reporting(0);
if(file_exists("./install.php")) {
	header("Location: ./install.php");
} 
include("../configs/bootstrap.php");
include("../includes/bootstrap.php");
include(getLanguage($settings['url'],null,2));
$amount = protect($_GET['amount']);
$currency = protect($_GET['currency']);
$ad_type = protect($_GET['ad_type']);
if($ad_type == "buy_bitcoin" or $ad_type == "sell_bitcoin") {
	if(is_numeric($amount)) {
		if($currency == "USD") {
			$btcprice = get_current_bitcoin_price();
			$com = $amount;
			$com2 = ($btcprice * $com) / 100;
			$amm = $btcprice - $com2;
			echo 'Your Bitcoin price: '.ceil($amm).' '.$currency;
		} else {
			$btcprice = get_current_bitcoin_price();
			$com = $amount;
			$com2 = ($btcprice * $com) / 100;
			$com3 = $btcprice - $com2;
			$amm = currencyConvertor($com3,"USD",$currency);
			echo 'Your Bitcoin price: '.ceil($amm).' '.$currency.' ('.$com3.' USD)';
		}
	}
} elseif($ad_type == "buy_litecoin" or  $ad_type == "sell_litecoin") {
	if(is_numeric($amount)) {
		if($currency == "USD") {
			$btcprice = get_current_litecoin_price();
			$com = $amount;
			$com2 = ($btcprice * $com) / 100;
			$amm = $btcprice - $com2;
			echo 'Your Litecoin price: '.ceil($amm).' '.$currency;
		} else {
			$btcprice = get_current_litecoin_price();
			$com = $amount;
			$com2 = ($btcprice * $com) / 100;
			$com3 = $btcprice - $com2;
			$amm = currencyConvertor($com3,"USD",$currency);
			echo 'Your Litecoin price: '.ceil($amm).' '.$currency.' ('.$com3.' USD)';
		}
	}

} elseif($ad_type == "buy_dogecoin" or $ad_type == "sell_dogecoin") {
	if(is_numeric($amount)) {
		if($currency == "USD") {
			$btcprice = get_current_dogecoin_price();
			$com = $amount;
			$com2 = ($btcprice * $com) / 100;
			$amm = $btcprice - $com2;
			echo 'Your Dogecoin price: '.$amm.' '.$currency;
		} else {
			$btcprice = get_current_dogecoin_price();
			$com = $amount;
			$com2 = ($btcprice * $com) / 100;
			$com3 = $btcprice - $com2;
			$amm = currencyConvertor($com3,"USD",$currency);
			echo 'Your Dogecoin price: '.$amm.' '.$currency.' ('.$com3.' USD)';
		}
	}
} else {
	echo 'Error';
}
?>