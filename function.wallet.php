<?php
if(!defined('CRYPTOMV2_INSTALLED')){
    header("HTTP/1.0 404 Not Found");
	exit;
}

function walletinfo($uid,$value,$network) {
	global $db;
	$query = $db->query("SELECT * FROM crypto_users_addresses WHERE uid='$uid' and network='$network'");
	$row = $query->fetch_assoc();
	return $row[$value];
}	

function tradeinfo($tid,$value) {
	global $db;
	$query = $db->query("SELECT * FROM crypto_trades WHERE id='$tid'");
	$row = $query->fetch_assoc();
	return $row[$value];
}	

function adinfo($aid,$value) {
	global $db;
	$query = $db->query("SELECT * FROM crypto_ads WHERE id='$aid'");
	$row = $query->fetch_assoc();
	return $row[$value];
}	

function calculate_txfee($network,$amount,$uid,$recipient) {
		global $db, $settings;
		$uaddress = walletinfo($uid,"address",$network);
		$lid = walletinfo($uid,"lid",$network);
		$license_query = $db->query("SELECT * FROM crypto_blockio_licenses WHERE id='$lid' ORDER BY id");
		$license = $license_query->fetch_assoc();
		$apiKey = $license['license'];
		$pin = $license['secret_pin'];
		$version = 2; // the API version
		$block_io = new BlockIo($apiKey, $pin, $version);
		$amounts = "$amount";
		$addresses = "$recipient";
		try {
			$netfee = $block_io->get_network_fee_estimate(array('amounts' => $amounts, 'to_addresses' => $addresses));
			$fee = $netfee->data->estimated_network_fee;
			return $fee;
		} catch (Exception $e) {
			return '0.0001';
		}
}

function StdClass2array($class)
{
    $array = array();

    foreach ($class as $key => $item)
    {
            if ($item instanceof StdClass) {
                    $array[$key] = StdClass2array($item);
            } else {
                    $array[$key] = $item;
            }
    }

    return $array;
}

/**
 * Uses BlocIo to generate a new bitcoin wallet
 * 
 * @todo Fix this
 */
function btc_generate_address($username) {
	global $db, $settings;
	$license_query = $db->query("SELECT * FROM crypto_blockio_licenses WHERE network='Bitcoin' and default_license='1' ORDER BY id");
	if($license_query->num_rows>0) {
		$license = $license_query->fetch_assoc();
		$user_query = $db->query("SELECT * FROM crypto_users WHERE username='$username'");
		$user = $user_query->fetch_assoc();
		$label = 'usr_'.$username;
		$apiKey = $license['license'];
		$pin = $license['secret_pin'];
		$version = 2; // the API version
		$block_io = new BlockIo($apiKey, $pin, $version);
		$new_address = $block_io->get_new_address(array('label' => $label));
                                
		if($new_address->status == "success") {
			$addr = $new_address->data->address;
			$time = time();
			$insert = $db->query("INSERT crypto_users_addresses (uid,network,label,address,lid,available_balance,pending_received_balance,status,created) VALUES ('$user[id]','Bitcoin','$label','$addr','$license[id]','0.00000000','0.00000000','1','$time')");
			$update = $db->query("UPDATE crypto_blockio_licenses UPDATE addresses=addresses+1 WHERE id='$license[id]'");
		}
	}
}

function ltc_generate_address($username) {
	global $db, $settings;
	$license_query = $db->query("SELECT * FROM crypto_blockio_licenses WHERE network='Litecoin' and default_license='1' ORDER BY id");
	if($license_query->num_rows>0) {
		$license = $license_query->fetch_assoc();
		$user_query = $db->query("SELECT * FROM crypto_users WHERE username='$username'");
		$user = $user_query->fetch_assoc();
		$label = 'usr_'.$username;
		$apiKey = $license['license'];
		$pin = $license['secret_pin'];
		$version = 2; // the API version
		$block_io = new BlockIo($apiKey, $pin, $version);
		$new_address = $block_io->get_new_address(array('label' => $label));
		if($new_address->status == "success") {
			$addr = $new_address->data->address;
			$time = time();
			$insert = $db->query("INSERT crypto_users_addresses (uid,network,label,address,lid,available_balance,pending_received_balance,status,created) VALUES ('$user[id]','Litecoin','$label','$addr','$license[id]','0.00000000','0.00000000','1','$time')");
			$update = $db->query("UPDATE crypto_blockio_licenses UPDATE addresses=addresses+1 WHERE id='$license[id]'");
		}
	}
}

function doge_generate_address($username) {
	global $db, $settings;
	$license_query = $db->query("SELECT * FROM crypto_blockio_licenses WHERE network='Dogecoin' and default_license='1' ORDER BY id");
	if($license_query->num_rows>0) {
		$license = $license_query->fetch_assoc();
		$user_query = $db->query("SELECT * FROM crypto_users WHERE username='$username'");
		$user = $user_query->fetch_assoc();
		$label = 'usr_'.$username;
		$apiKey = $license['license'];
		$pin = $license['secret_pin'];
		$version = 2; // the API version
		$block_io = new BlockIo($apiKey, $pin, $version);
		$new_address = $block_io->get_new_address(array('label' => $label));
		if($new_address->status == "success") {
			$addr = $new_address->data->address;
			$time = time();
			$insert = $db->query("INSERT crypto_users_addresses (uid,network,label,address,lid,available_balance,pending_received_balance,status,created) VALUES ('$user[id]','Dogecoin','$label','$addr','$license[id]','0.00000000','0.00000000','1','$time')");
			$update = $db->query("UPDATE crypto_blockio_licenses UPDATE addresses=addresses+1 WHERE id='$license[id]'");
		}
	}
}

function btc_update_balance($uid) {
	global $db, $settings;
	$get_address = $db->query("SELECT * FROM crypto_users_addresses WHERE uid='$uid' and network='Bitcoin'");
	if($get_address->num_rows>0) {
		$get = $get_address->fetch_assoc();
		$license_query = $db->query("SELECT * FROM crypto_blockio_licenses WHERE id='$get[lid]' ORDER BY id");
		$license = $license_query->fetch_assoc();
		$user_address = $get['address'];
		$apiKey = $license['license'];
		$pin = $license['secret_pin'];
		$version = 2; // the API version
		$block_io = new BlockIo($apiKey, $pin, $version);
		$balance = $block_io->get_address_balance(array('addresses' => $user_address));
		if($balance->status == "success") {
			$time = time();
			$available_balance = $balance->data->available_balance;
			$pending_received_balance = $balance->data->pending_received_balance;
			$update = $db->query("UPDATE crypto_users_addresses SET available_balance='$available_balance',pending_received_balance='$pending_received_balance' WHERE id='$get[id]' and uid='$uid'");
		}
	}
}

function ltc_update_balance($uid) {
	global $db, $settings;
	$get_address = $db->query("SELECT * FROM crypto_users_addresses WHERE uid='$uid' and network='Litecoin'");
	if($get_address->num_rows>0) {
		$get = $get_address->fetch_assoc();
		$license_query = $db->query("SELECT * FROM crypto_blockio_licenses WHERE id='$get[lid]' ORDER BY id");
		$license = $license_query->fetch_assoc();
		$user_address = $get['address'];
		$apiKey = $license['license'];
		$pin = $license['secret_pin'];
		$version = 2; // the API version
		$block_io = new BlockIo($apiKey, $pin, $version);
		$balance = $block_io->get_address_balance(array('addresses' => $user_address));
		if($balance->status == "success") {
			$time = time();
			$available_balance = $balance->data->available_balance;
			$pending_received_balance = $balance->data->pending_received_balance;
			$update = $db->query("UPDATE crypto_users_addresses SET available_balance='$available_balance',pending_received_balance='$pending_received_balance' WHERE id='$get[id]' and uid='$uid'");
		}
	}
}

function doge_update_balance($uid) {
	global $db, $settings;
	$get_address = $db->query("SELECT * FROM crypto_users_addresses WHERE uid='$uid' and network='Dogecoin'");
	if($get_address->num_rows>0) {
		$get = $get_address->fetch_assoc();
		$license_query = $db->query("SELECT * FROM crypto_blockio_licenses WHERE id='$get[lid]' ORDER BY id");
		$license = $license_query->fetch_assoc();
		$user_address = $get['address'];
		$apiKey = $license['license'];
		$pin = $license['secret_pin'];
		$version = 2; // the API version
		$block_io = new BlockIo($apiKey, $pin, $version);
		$balance = $block_io->get_address_balance(array('addresses' => $user_address));
		if($balance->status == "success") {
			$time = time();
			$available_balance = $balance->data->available_balance;
			$pending_received_balance = $balance->data->pending_received_balance;
			$update = $db->query("UPDATE crypto_users_addresses SET available_balance='$available_balance',pending_received_balance='$pending_received_balance' WHERE id='$get[id]' and uid='$uid'");
		}
	}
}

function btc_admin_get_profit() {
	global $db, $settings;
	$license_query = $db->query("SELECT * FROM crypto_blockio_licenses WHERE default_license='1' and network='Bitcoin' ORDER BY id");
	$license = $license_query->fetch_assoc();
	$user_address = $license['address'];
	$apiKey = $license['license'];
	$pin = $license['secret_pin'];
	$version = 2; // the API version
	$block_io = new BlockIo($apiKey, $pin, $version);
	$balance = $block_io->get_address_balance(array('addresses' => $user_address));
	if($balance->status == "success") {
		$time = time();
		$available_balance = $balance->data->available_balance;
		$pending_received_balance = $balance->data->pending_received_balance;
		return $available_balance.' BTC';
	} else {
		return '0.0000 BTC';
	}
}

function ltc_admin_get_profit() {
	global $db, $settings;
	$license_query = $db->query("SELECT * FROM crypto_blockio_licenses WHERE default_license='1' and network='Litecoin' ORDER BY id");
	$license = $license_query->fetch_assoc();
	$user_address = $license['address'];
	$apiKey = $license['license'];
	$pin = $license['secret_pin'];
	$version = 2; // the API version
	$block_io = new BlockIo($apiKey, $pin, $version);
	$balance = $block_io->get_address_balance(array('addresses' => $user_address));
	if($balance->status == "success") {
		$time = time();
		$available_balance = $balance->data->available_balance;
		$pending_received_balance = $balance->data->pending_received_balance;
		return $available_balance.' LTC';
	} else {
		return '0.0000 LTC';
	}
}

function doge_admin_get_profit() {
	global $db, $settings;
	$license_query = $db->query("SELECT * FROM crypto_blockio_licenses WHERE default_license='1' and network='Dogecoin' ORDER BY id");
	$license = $license_query->fetch_assoc();
	$user_address = $license['address'];
	$apiKey = $license['license'];
	$pin = $license['secret_pin'];
	$version = 2; // the API version
	$block_io = new BlockIo($apiKey, $pin, $version);
	$balance = $block_io->get_address_balance(array('addresses' => $user_address));
	if($balance->status == "success") {
		$time = time();
		$available_balance = $balance->data->available_balance;
		$pending_received_balance = $balance->data->pending_received_balance;
		return $available_balance.' DOGE';
	} else {
		return '0.0000 DOGE';
	}
}

function btc_update_transactions($uid) {
	global $db, $settings;
	$get_address = $db->query("SELECT * FROM crypto_users_addresses WHERE uid='$uid' and network='Bitcoin'");
	if($get_address->num_rows>0) {
		$get = $get_address->fetch_assoc();
		$license_query = $db->query("SELECT * FROM crypto_blockio_licenses WHERE id='$get[lid]' ORDER BY id");
		$license = $license_query->fetch_assoc();
		$apiKey = $license['license'];
		$pin = $license['secret_pin'];
		$version = 2; // the API version
		$block_io = new BlockIo($apiKey, $pin, $version);
		$received = $block_io->get_transactions(array('type' => 'received', 'addresses' => $get[address]));
		if($received->status == "success") {	
			$data = $received->data->txs;
			$dt = StdClass2array($data);
			foreach($dt as $k=>$v) {
				$txid = $v['txid'];
				$time = $v['time'];
				$amounts = $v['amounts_received'];
				$amounts = StdClass2array($amounts);
				foreach($amounts as $a => $b) {
					$recipient = $b['recipient'];
					$amount = $b['amount'];
				} 
				$senders = $v['senders'];
				$senders = StdClass2array($senders);
				foreach($senders as $c => $d) {
					 $sender = $d;
				}
				$confirmations = $v['confirmations'];
					$check = $db->query("SELECT * FROM crypto_users_transactions WHERE uid='$uid' and txid='$txid'");
					if($check->num_rows>0) {
						$update = $db->query("UPDATE crypto_users_transactions SET confirmations='$confirmations' WHERE uid='$uid' and txid='$txid'");
					} else {
						$insert = $db->query("INSERT crypto_users_transactions (uid,network,type,recipient,sender,amount,time,confirmations,txid) VALUES ('$uid','Bitcoin','received','$recipient','$sender','$amount','$time','$confirmations','$txid')");
					}
			}
		}
		$sent = $block_io->get_transactions(array('type' => 'sent', 'addresses' => $get[address]));
		if($sent->status == "success") {	
			$data = $sent->data->txs;
			$dt = StdClass2array($data);
			foreach($dt as $k=>$v) {
				$txid = $v['txid'];
				$time = $v['time'];
				$amounts = $v['amounts_sent'];
				$amounts = StdClass2array($amounts);
				$recipient = '';
				$i=1;
				foreach($amounts as $a => $b) {
					if($i==1) {
						$recipient = $b['recipient'];
						$amount = $b['amount'];
						$i++;
					}
				} 
				$senders = $v['senders'];
				$senders = StdClass2array($senders);
				foreach($senders as $c => $d) {
					 $sender = $d;
				}
				$confirmations = $v['confirmations'];
					$check = $db->query("SELECT * FROM crypto_users_transactions WHERE uid='$uid' and txid='$txid'");
					if($check->num_rows>0) {
						$update = $db->query("UPDATE crypto_users_transactions SET confirmations='$confirmations' WHERE uid='$uid' and txid='$txid'");
					} else {
						$insert = $db->query("INSERT crypto_users_transactions (uid,network,type,recipient,sender,amount,time,confirmations,txid) VALUES ('$uid','Bitcoin','sent','$recipient','$sender','$amount','$time','$confirmations','$txid')");
					}
			}
		}
	}
}

function ltc_update_transactions($uid) {
	global $db, $settings;
	$get_address = $db->query("SELECT * FROM crypto_users_addresses WHERE uid='$uid' and network='Litecoin'");
	if($get_address->num_rows>0) {
		$get = $get_address->fetch_assoc();
		$license_query = $db->query("SELECT * FROM crypto_blockio_licenses WHERE id='$get[lid]' ORDER BY id");
		$license = $license_query->fetch_assoc();
		$apiKey = $license['license'];
		$pin = $license['secret_pin'];
		$version = 2; // the API version
		$block_io = new BlockIo($apiKey, $pin, $version);
		$received = $block_io->get_transactions(array('type' => 'received', 'addresses' => $get[address]));
		if($received->status == "success") {	
			$data = $received->data->txs;
			$dt = StdClass2array($data);
			foreach($dt as $k=>$v) {
				$txid = $v['txid'];
				$time = $v['time'];
				$amounts = $v['amounts_received'];
				$amounts = StdClass2array($amounts);
				foreach($amounts as $a => $b) {
					$recipient = $b['recipient'];
					$amount = $b['amount'];
				} 
				$senders = $v['senders'];
				$senders = StdClass2array($senders);
				foreach($senders as $c => $d) {
					 $sender = $d;
				}
				$confirmations = $v['confirmations'];
					$check = $db->query("SELECT * FROM crypto_users_transactions WHERE uid='$uid' and txid='$txid'");
					if($check->num_rows>0) {
						$update = $db->query("UPDATE crypto_users_transactions SET confirmations='$confirmations' WHERE uid='$uid' and txid='$txid'");
					} else {
						$insert = $db->query("INSERT crypto_users_transactions (uid,network,type,recipient,sender,amount,time,confirmations,txid) VALUES ('$uid','Litecoin','received','$recipient','$sender','$amount','$time','$confirmations','$txid')");
					}
			}
		}
		$sent = $block_io->get_transactions(array('type' => 'sent', 'addresses' => $get[address]));
		if($sent->status == "success") {	
			$data = $sent->data->txs;
			$dt = StdClass2array($data);
			foreach($dt as $k=>$v) {
				$txid = $v['txid'];
				$time = $v['time'];
				$amounts = $v['amounts_sent'];
				$amounts = StdClass2array($amounts);
				$recipient = '';
				$i=0;
				foreach($amounts as $a => $b) {
					if($i==1) {
						$recipient = $b['recipient'];
						$amount = $b['amount'];
						$i++;
					}
				} 
				$senders = $v['senders'];
				$senders = StdClass2array($senders);
				foreach($senders as $c => $d) {
					 $sender = $d;
				}
				$confirmations = $v['confirmations'];
					$check = $db->query("SELECT * FROM crypto_users_transactions WHERE uid='$uid' and txid='$txid'");
					if($check->num_rows>0) {
						$update = $db->query("UPDATE crypto_users_transactions SET confirmations='$confirmations' WHERE uid='$uid' and txid='$txid'");
					} else {
						$insert = $db->query("INSERT crypto_users_transactions (uid,network,type,recipient,sender,amount,time,confirmations,txid) VALUES ('$uid','Litecoin','sent','$recipient','$sender','$amount','$time','$confirmations','$txid')");
					}
			}
		}
	}
}

function doge_update_transactions($uid) {
	global $db, $settings;
	$get_address = $db->query("SELECT * FROM crypto_users_addresses WHERE uid='$uid' and network='Dogecoin'");
	if($get_address->num_rows>0) {
		$get = $get_address->fetch_assoc();
		$license_query = $db->query("SELECT * FROM crypto_blockio_licenses WHERE id='$get[lid]' ORDER BY id");
		$license = $license_query->fetch_assoc();
		$apiKey = $license['license'];
		$pin = $license['secret_pin'];
		$version = 2; // the API version
		$block_io = new BlockIo($apiKey, $pin, $version);
		$received = $block_io->get_transactions(array('type' => 'received', 'addresses' => $get[address]));
		if($received->status == "success") {	
			$data = $received->data->txs;
			$dt = StdClass2array($data);
			foreach($dt as $k=>$v) {
				$txid = $v['txid'];
				$time = $v['time'];
				$amounts = $v['amounts_received'];
				$amounts = StdClass2array($amounts);
				foreach($amounts as $a => $b) {
					$recipient = $b['recipient'];
					$amount = $b['amount'];
				} 
				$senders = $v['senders'];
				$senders = StdClass2array($senders);
				foreach($senders as $c => $d) {
					 $sender = $d;
				}
				$confirmations = $v['confirmations'];
					$check = $db->query("SELECT * FROM crypto_users_transactions WHERE uid='$uid' and txid='$txid'");
					if($check->num_rows>0) {
						$update = $db->query("UPDATE crypto_users_transactions SET confirmations='$confirmations' WHERE uid='$uid' and txid='$txid'");
					} else {
						$insert = $db->query("INSERT crypto_users_transactions (uid,network,type,recipient,sender,amount,time,confirmations,txid) VALUES ('$uid','Dogecoin','received','$recipient','$sender','$amount','$time','$confirmations','$txid')");
					}
			}
		}
		$sent = $block_io->get_transactions(array('type' => 'sent', 'addresses' => $get[address]));
		if($sent->status == "success") {	
			$data = $sent->data->txs;
			$dt = StdClass2array($data);
			foreach($dt as $k=>$v) {
				$txid = $v['txid'];
				$time = $v['time'];
				$amounts = $v['amounts_sent'];
				$amounts = StdClass2array($amounts);
				$recipient = '';
				$i=1;
				foreach($amounts as $a => $b) {
					if($i==1) {
						$recipient = $b['recipient'];
						$amount = $b['amount'];
						$i++;
					}
				} 
				$senders = $v['senders'];
				$senders = StdClass2array($senders);
				foreach($senders as $c => $d) {
					 $sender = $d;
				}
				$confirmations = $v['confirmations'];
					$check = $db->query("SELECT * FROM crypto_users_transactions WHERE uid='$uid' and txid='$txid'");
					if($check->num_rows>0) {
						$update = $db->query("UPDATE crypto_users_transactions SET confirmations='$confirmations' WHERE uid='$uid' and txid='$txid'");
					} else {
						$insert = $db->query("INSERT crypto_users_transactions (uid,network,type,recipient,sender,amount,time,confirmations,txid) VALUES ('$uid','Dogecoin','sent','$recipient','$sender','$amount','$time','$confirmations','$txid')");
					}
			}
		}
	}
}

function btc_delete_fee_transactions($uid) {
	global $db, $settings;
	$get_address = $db->query("SELECT * FROM crypto_users_addresses WHERE uid='$uid' and network='Bitcoin'");
	if($get_address->num_rows>0) {
		$get = $get_address->fetch_assoc();
		$license_query = $db->query("SELECT * FROM crypto_blockio_licenses WHERE id='$get[lid]' ORDER BY id");
		$license = $license_query->fetch_assoc();
		$addr = $license['address'];
		$query = $db->query("SELECT * FROM crypto_users_transactions WHERE uid='$uid' and type='sent'");
		if($query->num_rows>0) {
			while($row = $query->fetch_assoc()) {
				if($license['address'] == $row['recipient']) {
					$delete = $db->query("DELETE FROM crypto_users_transactions WHERE id='$row[id]' and uid='$uid'");
				}
			}
		}
	}
}

function ltc_delete_fee_transactions($uid) {
	global $db, $settings;
	$get_address = $db->query("SELECT * FROM crypto_users_addresses WHERE uid='$uid' and network='Litecoin'");
	if($get_address->num_rows>0) {
		$get = $get_address->fetch_assoc();
		$license_query = $db->query("SELECT * FROM crypto_blockio_licenses WHERE id='$get[lid]' ORDER BY id");
		$license = $license_query->fetch_assoc();
		$addr = $license['address'];
		$query = $db->query("SELECT * FROM crypto_users_transactions WHERE uid='$uid' and type='sent'");
		if($query->num_rows>0) {
			while($row = $query->fetch_assoc()) {
				if($license['address'] == $row['recipient']) {
					$delete = $db->query("DELETE FROM crypto_users_transactions WHERE id='$row[id]' and uid='$uid'");
				}
			}
		}
	}
}

function doge_delete_fee_transactions($uid) {
	global $db, $settings;
	$get_address = $db->query("SELECT * FROM crypto_users_addresses WHERE uid='$uid' and network='Dogecoin'");
	if($get_address->num_rows>0) {
		$get = $get_address->fetch_assoc();
		$license_query = $db->query("SELECT * FROM crypto_blockio_licenses WHERE id='$get[lid]' ORDER BY id");
		$license = $license_query->fetch_assoc();
		$addr = $license['address'];
		$query = $db->query("SELECT * FROM crypto_users_transactions WHERE uid='$uid' and type='sent'");
		if($query->num_rows>0) {
			while($row = $query->fetch_assoc()) {
				if($license['address'] == $row['recipient']) {
					$delete = $db->query("DELETE FROM crypto_users_transactions WHERE id='$row[id]' and uid='$uid'");
				}
			}
		}
	}
}

function btc_get_bitcoin_prices() {
	global $db, $settings;
	$license_query = $db->query("SELECT * FROM crypto_blockio_licenses WHERE default_license='1' and network='Bitcoin' ORDER BY id");
	if($license_query->num_rows>0) {
		$license = $license_query->fetch_assoc();
		$apiKey = $license['license'];
		$pin = $license['secret_pin'];
		$version = 2; // the API version
		$block_io = new BlockIo($apiKey, $pin, $version);
		$price = $block_io->get_current_price();
		$prices = $price->data->prices;
		$prices = StdClass2array($prices);
		foreach($prices as $k => $v) {
			foreach($v as $a => $b) {
				$rows[$a] = $b;
			}
			$query = $db->query("SELECT * FROM crypto_prices WHERE source='$rows[exchange]' and currency='$rows[price_base]' and network='Bitcoin'");
			if($query->num_rows>0) {
				$update = $db->query("UPDATE crypto_prices SET price='$rows[price]' WHERE source='$rows[exchange]' and network='Bitcoin' and currency='$rows[price_base]'");
			} else {
				$insert = $db->query("INSERT crypto_prices (source,network,price,currency) VALUES ('$rows[exchange]','Bitcoin','$rows[price]','$rows[price_base]')");
			}
		}
	}
}

function ltc_get_litecoin_prices() {
	global $db, $settings;
	$license_query = $db->query("SELECT * FROM crypto_blockio_licenses WHERE default_license='1' and network='Litecoin' ORDER BY id");
	if($license_query->num_rows>0) {
		$license = $license_query->fetch_assoc();
		$apiKey = $license['license'];
		$pin = $license['secret_pin'];
		$version = 2; // the API version
		$block_io = new BlockIo($apiKey, $pin, $version);
		$price = $block_io->get_current_price();
		$prices = $price->data->prices;
		$prices = StdClass2array($prices);
		foreach($prices as $k => $v) {
			foreach($v as $a => $b) {
				$rows[$a] = $b;
			}
			$query = $db->query("SELECT * FROM crypto_prices WHERE source='$rows[exchange]' and currency='$rows[price_base]' and network='Litecoin'");
			if($query->num_rows>0) {
				$update = $db->query("UPDATE crypto_prices SET price='$rows[price]' WHERE source='$rows[exchange]' and network='Litecoin' and currency='$rows[price_base]'");
			} else {
				$insert = $db->query("INSERT crypto_prices (source,network,price,currency) VALUES ('$rows[exchange]','Litecoin','$rows[price]','$rows[price_base]')");
			}
		}
	}
}

function doge_get_dogecoin_prices() {
	global $db, $settings;
	$license_query = $db->query("SELECT * FROM crypto_blockio_licenses WHERE default_license='1' and network='Dogecoin' ORDER BY id");
	if($license_query->num_rows>0) {
		$license = $license_query->fetch_assoc();
		$apiKey = $license['license'];
		$pin = $license['secret_pin'];
		$version = 2; // the API version
		$block_io = new BlockIo($apiKey, $pin, $version);
		$price = $block_io->get_current_price();
		$prices = $price->data->prices;
		$prices = StdClass2array($prices);
		foreach($prices as $k => $v) {
			foreach($v as $a => $b) {
				$rows[$a] = $b;
			}
			$query = $db->query("SELECT * FROM crypto_prices WHERE source='$rows[exchange]' and currency='$rows[price_base]' and network='Dogecoin'");
			if($query->num_rows>0) {
				$update = $db->query("UPDATE crypto_prices SET price='$rows[price]' WHERE source='$rows[exchange]' and network='Dogecoin' and currency='$rows[price_base]'");
			} else {
				$insert = $db->query("INSERT crypto_prices (source,network,price,currency) VALUES ('$rows[exchange]','Dogecoin','$rows[price]','$rows[price_base]')");
			}
		}
	}
}

function getCryptoPrice($coin) {
	$ch = curl_init();
	$url = "https://min-api.cryptocompare.com/data/price?fsym=$coin&tsyms=USD";
	// Disable SSL verification
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	// Will return the response, if false it print the response
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	// Set the url
	curl_setopt($ch, CURLOPT_URL,$url);
	// Execute
	$result=curl_exec($ch);
	// Closing
	curl_close($ch);
	$json = json_decode($result, true);
	if($json['USD']) {
		return $json['USD'];
	} else {
		return '0';
	}
}

function get_current_bitcoin_price() {
	global $db, $settings;
	return getCryptoPrice("BTC");
}

function get_current_litecoin_price() {
	global $db, $settings;
	return getCryptoPrice("LTC");
}

function get_current_dogecoin_price() {
	global $db, $settings;
	return getCryptoPrice("DOGE");
}

function update_activity($uid) {
	global $db;
	$time = time();
	$update = $db->query("UPDATE crypto_users SET time_activity='$time' WHERE id='$uid'");
}

function timeago($time)
{
   $periods = array("second", "minute", "hour", "day", "week", "month", "year", "decade");
   $lengths = array("60","60","24","7","4.35","12","10");

   $now = time();

       $difference     = $now - $time;
       $tense         = "ago";

   for($j = 0; $difference >= $lengths[$j] && $j < count($lengths)-1; $j++) {
       $difference /= $lengths[$j];
   }

   $difference = round($difference);

   if($difference != 1) {
       $periods[$j].= "s";
   }

   return "$difference $periods[$j] ago ";
}
	
function activity_time($uid) {
	global $db, $settings;
	$currenttime = time()-300;
	$onlinetime = idinfo($uid,"time_activity");
	if($onlinetime > $currenttime) {
		return 'Is online';
	} else {
		return 'Last seen '.timeago($onlinetime);
	}
}

function is_online($uid) {
	global $db, $settings;
	$currenttime = time()-300;
	$onlinetime = idinfo($uid,"time_activity");
	if($onlinetime > $currenttime) {
		return 1;
	} else {
		return 0;
	}
}

function convertBTCprice($amount,$currency) {
	if(is_numeric($amount)) {
		if($currency == "USD") {
			$btcprice = get_current_bitcoin_price();
			$com = $amount;
			$com2 = ($btcprice * $com) / 100;
			$amm = $btcprice - $com2;
			return number_format($amm, 2, '.', '');
		} else {
			$btcprice = get_current_bitcoin_price();
			$com = $amount;
			$com2 = ($btcprice * $com) / 100;
			$com3 = $btcprice - $com2;
			$amm = currencyConvertor($com3,"USD",$currency);
			return number_format($amm, 2, '.', '');
		}
	}
}

function convertLTCprice($amount,$currency) {
	if(is_numeric($amount)) {
		if($currency == "USD") {
			$ltcprice = get_current_litecoin_price();
			$com = $amount;
			$com2 = ($ltcprice * $com) / 100;
			$amm = $ltcprice - $com2;
			return ceil($amm);
		} else {
			$ltcprice = get_current_litecoin_price();
			$com = $amount;
			$com2 = ($ltcprice * $com) / 100;
			$com3 = $ltcprice - $com2;
			$amm = currencyConvertor($com3,"USD",$currency);
			return ceil($amm);
		}
	}
}

function convertDOGEprice($amount,$currency) {
	if(is_numeric($amount)) {
		if($currency == "USD") {
			$dogeprice = get_current_dogecoin_price();
			$com = $amount;
			$com2 = ($dogeprice * $com) / 100;
			$amm = $dogeprice - $com2;
			return $amm;
		} else {
			$dogeprice = get_current_dogecoin_price();
			$com = $amount;
			$com2 = ($dogeprice * $com) / 100;
			$com3 = $dogeprice - $com2;
			$amount = urlencode($com3);
			  $from_Currency = urlencode($from_Currency);
			  $to_Currency = urlencode($currency);
			  $get = file_get_contents("https://$from_Currency.mconvert.net/$to_Currency/$amount");
			  $get = explode('<span class="convert-result result">',$result);
				$get = explode("</span>",$get[1]);  
			  $converted_amount = preg_replace("/[^0-9\.]/", null, $get[0]);
			return $converted_amount;
		}
	}
}

function currencyConvertor($amount,$from_Currency,$to_Currency) {

    global $settings;
    
    
    $am = urlencode($amount);
        
        $apiKey = $settings['api_key_currencyconverterapi'];
        
	$prefix = $from_Currency.'_'.$to_Currency;
	$ch = curl_init();
	$url = "https://api.currconv.com/api/v7/convert?apiKey=" . $apiKey . "&q=$prefix&compact=y";
	// Disable SSL verification
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	// Will return the response, if false it print the response
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	// Set the url
	curl_setopt($ch, CURLOPT_URL,$url);
	// Execute
	$result=curl_exec($ch);
	// Closing
	curl_close($ch);
	$json = json_decode($result, true);
	//echo $json[$prefix]['val'];
	$converted_amount = $json[$prefix]['val'];
	if($amount>1 && $from_Currency != "USD") {
		$converted_amount = $amount * $converted_amount;
		return number_format($converted_amount, 2, '.', '');
	} elseif($amount>1 && $to_Currency != "USD") {
		$converted_amount = $amount * $converted_amount;
		return number_format($converted_amount, 2, '.', '');
	} else {
		return number_format($converted_amount, 2, '.', '');
	}
}


function btc_check_expired_trades() {
	global $db, $settings;
	$time = time();
	$query = $db->query("SELECT * FROM crypto_trades WHERE status < 3 and timeout < $time");
	if($query->num_rows>0) {
		while($row = $query->fetch_assoc()) {
			$update = $db->query("UPDATE crypto_trades SET status='6' WHERE id='$row[id]'");
		}
	} 
}


function get_user_balance($uid,$network) {
	global $db, $settings;
	$balance = walletinfo($uid,"available_balance",$network);
	$query = $db->query("SELECT * FROM crypto_trades WHERE uid='$uid' and type LIKE '%sell_%' and status < 3 and network='$network'");
	if($query->num_rows>0) {
		while($row = $query->fetch_assoc()) {
			$balance = $balance-$row['crypto_amount'];
			if($network == "Bitcoin") {
				$balance = $balance-$settings['bitcoin_sell_comission'];
			} elseif($network == "Litecoin") {
				$balance = $balance-$settings['litecoin_sell_comission'];
			} elseif($network == "Dogecoin") {
				$balance = $balance-$settings['dogecoin_sell_comission'];
			} else { }
		}
	}
	$CheckDisputes = $db->query("SELECT * FROM crypto_disputes WHERE network='$network' and trader='$uid' and status != '32'");
	if($CheckDisputes->num_rows>0) {
		$dispute = $CheckDisputes->fetch_assoc();
		if($network == "Bitcoin") {
				$balance = $balance - $dispute['crypto_amount'] - 0.00005;
			} elseif($network == "Litecoin") {
				$balance = $balance - $dispute['crypto_amount'] - 0.0005;
			} elseif($network == "Dogecoin") {
				$balance = $balance - $dispute['crypto_amount'] - 4;
			} else { }
	}
	$query = $db->query("SELECT * FROM crypto_trades WHERE trader='$uid' and type LIKE '%buy_%' and status < 3 and network='$network'");
	if($query->num_rows>0) {
		while($row = $query->fetch_assoc()) {
			$balance = $balance-$row['crypto_amount'];
			if($network == "Bitcoin") {
				$balance = $balance-$settings['bitcoin_buy_comission'];
			} elseif($network == "Litecoin") {
				$balance = $balance-$settings['litecoin_buy_comission'];
			} elseif($network == "Dogecoin") {
				$balance = $balance-$settings['dogecoin_buy_comission'];
			} else { }
		}
	}
	if($network == "Dogecoin") {
		return $balance;
	} else {
	return number_format($balance,8);
	}
}
?>