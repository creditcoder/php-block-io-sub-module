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
if(checkSession()) {
	$data['status'] = 'error';
	$data['msg'] = '';
	$network = protect($_GET['network']);
	$amount = protect($_GET['amount']);
	$recipient = protect($_GET['recipient']);
	if(empty($network) or empty($amount) or empty($recipient)) {
		$data['status'] = 'error';
		$data['msg'] = '';
	} else {
		if($network == "Bitcoin") {
			$ccode = 'BTC';
			$withdrawal_comission = $settings['bitcoin_withdrawal_comission'];
		} elseif($network == "Litecoin") {
			$ccode = 'LTC';
			$withdrawal_comission = $settings['litecoin_withdrawal_comission'];
		} elseif($network == "Dogecoin") {
			$ccode = 'DOGE';
			$withdrawal_comission = $settings['dogecoin_withdrawal_comission'];
		} else { }
		$uaddress = walletinfo($_SESSION['cm_uid'],"address",$network);
		$lid = walletinfo($_SESSION['cm_uid'],"lid",$network);
		$license_query = $db->query("SELECT * FROM crypto_blockio_licenses WHERE id='$lid' ORDER BY id");
		$license = $license_query->fetch_assoc();
		$apiKey = $license['license'];
		$pin = $license['secret_pin'];
		$version = 2; // the API version
		$block_io = new BlockIo($apiKey, $pin, $version);
		$amounts = "$amount,$withdrawal_comission";
		$addresses = "$recipient,$license[address]";
		try {
			$netfee = $block_io->get_network_fee_estimate(array('amounts' => $amounts, 'to_addresses' => $addresses));
			$fee = $netfee->data->estimated_network_fee;
			$total =  $fee + $withdrawal_comission;
			$data['status'] = 'success';
			$data['msg'] = $lang[transaction_fee].': '.$total.' '.$ccode;
		} catch (Exception $e) {
			//echo $lang[error_30].': 0.000000 BTC';
		}
	}
	echo json_encode($data);
}
?>
