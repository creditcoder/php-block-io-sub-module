<?php
if(!defined('CRYPTOMV2_INSTALLED')){
    header("HTTP/1.0 404 Not Found");
	exit;
}

if(!checkSession()) { 
	$redirect = $settings['url']."login"; 
	header("Location: $redirect");
}
$b = protect($_GET['b']);
if($b == "cancel") {
	$id = protect($_GET['id']);
	$query = $db->query("SELECT * FROM crypto_trades WHERE id='$id' and uid='$_SESSION[cm_uid]' or id='$id' and trader='$_SESSION[cm_uid]'");
	if($query->num_rows==0) { header("Location: $settings[url]"); }
	$row = $query->fetch_assoc();
	$tpl = new Template("templates/".$settings['default_template']."/trade_cancel.tpl",$lang);
	$tpl->set("url",$settings['url']);
	$results = '';
	if($row['status']>1 or $row['status'] == "0") {
		$results = error($lang['error_47']);
	} else {
		$query = $db->query("SELECT * FROM crypto_trades WHERE id='$row[id]'");
		$row = $query->fetch_assoc();
		$update = $db->query("UPDATE crypto_trades SET status='5' WHERE id='$row[id]'");
		$results = info($lang['info_2']);
	}
	$tpl->set("results",$results);
	echo $tpl->output();
} elseif($b == "report") {
	$id = protect($_GET['id']);
	$query = $db->query("SELECT * FROM crypto_trades WHERE id='$id' and uid='$_SESSION[cm_uid]' or id='$id' and trader='$_SESSION[cm_uid]'");
	if($query->num_rows==0) { header("Location: $settings[url]"); }
	$row = $query->fetch_assoc();
	if(tradeinfo($row['id'],"type") == "buy_bitcoin") {
		$adid = tradeinfo($row['id'],"ad_id");
		$pm = adinfo($adid,"payment_method");
		$pm = str_ireplace(" ","-",$pm);
		$adlink = $settings['url']."ad/".$pm."-to-Bitcoin/".$adid;
	} elseif(tradeinfo($row['id'],"type") == "sell_bitcoin") { 
		$adid = tradeinfo($row['id'],"ad_id");
		$pm = adinfo($adid,"payment_method");
		$pm = str_ireplace(" ","-",$pm);
		$adlink = $settings['url']."ad/Bitcoin-to-".$pm."/".$adid;
	} elseif(tradeinfo($row['id'],"type") == "buy_litecoin") {
		$adid = tradeinfo($row['id'],"ad_id");
		$pm = adinfo($adid,"payment_method");
		$pm = str_ireplace(" ","-",$pm);
		$adlink = $settings['url']."ad/".$pm."-to-Litecoin/".$adid;
	} elseif(tradeinfo($row['id'],"type") == "sell_litecoin") { 
		$adid = tradeinfo($row['id'],"ad_id");
		$pm = adinfo($adid,"payment_method");
		$pm = str_ireplace(" ","-",$pm);
		$adlink = $settings['url']."ad/Litecoin-to-".$pm."/".$adid;
	} elseif(tradeinfo($row['id'],"type") == "buy_dogecoin") {
		$adid = tradeinfo($row['id'],"ad_id");
		$pm = adinfo($adid,"payment_method");
		$pm = str_ireplace(" ","-",$pm);
		$adlink = $settings['url']."ad/".$pm."-to-Dogecoin/".$adid;
	} elseif(tradeinfo($row['id'],"type") == "sell_dogecoin") { 
		$adid = tradeinfo($row['id'],"ad_id");
		$pm = adinfo($adid,"payment_method");
		$pm = str_ireplace(" ","-",$pm);
		$adlink = $settings['url']."ad/Dogecoin-to-".$pm."/".$adid;
	} else { }
			
	$network = $row['network'];
	if($network == "Bitcoin") { 
		$ext = 'BTC'; 
	} elseif($network == "Litecoin") {
		$ext = 'LTC';
	} elseif($network == "Dogecoin") {
		$ext = 'DOGE';
	} else { }
	$tpl = new Template("templates/".$settings['default_template']."/trade_report.tpl",$lang);
	$tpl->set("url",$settings['url']);
	$results = '';
	if(isset($_POST['btc_report'])) {
		$content = protect($_POST['content']);
		$time = time();
		if(empty($content)) { $results = error($lang['error_26']); }
		else {
			$insert = $db->query("INSERT crypto_trades_reports (uid,trade_id,content,status,time) VALUES ('$_SESSION[cm_uid]','$row[id]','$content','0','$time')");
			$getreport = $db->query("SELECT * FROM crypto_trades_reports WHERE uid='$_SESSION[cm_uid]' ORDER BY id DESC LIMIT 1");
			$report = $getreport->fetch_assoc();
			$results = success("$lang[success_5] $report[id]");
		}
	}
	$tpl->set("id",$row['id']);
	$tpl->set("ad_id",$row['ad_id']);
	$tpl->set("amount",$row['amount']);
	$tpl->set("currency",adinfo($row['ad_id'],"currency"));
	$tpl->set("crypto_amount",$row['crypto_amount']);
	$tpl->set("adlink",$adlink);
	$tpl->set("status",$status);
	$tpl->set("crypto_price",$row['crypto_price']);
	$tpl->set("session_uid",$_SESSION['cm_uid']);
	$tpl->set("CryptoCode",$ext);
	$tpl->set("results",$results);
	echo $tpl->output();
} elseif($b == "leave-feedback") {
	$id = protect($_GET['id']);
	$query = $db->query("SELECT * FROM crypto_trades WHERE id='$id' and uid='$_SESSION[cm_uid]' or id='$id' and trader='$_SESSION[cm_uid]'");
	if($query->num_rows==0) { header("Location: $settings[url]"); }
	$row = $query->fetch_assoc();
	if($row['status'] !== "7") { $redirect = $settings['url']."account/trades"; header("Location: $redirect"); }
	$check_feedback = $db->query("SELECT * FROM crypto_users_ratings WHERE trade_id='$row[id]' and author='$_SESSION[cm_uid]'");
	if($check_feedback->num_rows>0) {  $redirect = $settings['url']."account/trades"; header("Location: $redirect"); }
			
	$network = $row['network'];
	if($network == "Bitcoin") {
		$ext = 'BTC'; 
	} elseif($network == "Litecoin") {
		$ext = 'LTC';
	} elseif($network == "Dogecoin") {
		$ext = 'DOGE';
	} else { }
	$tpl = new Template("templates/".$settings['default_template']."/trade_leave_feedback.tpl",$lang);
	$tpl->set("url",$settings['url']);
	$results = '';
	$hide_form=0;
	if(isset($_POST['btc_feedback'])) {
		$type = protect($_POST['type']);
		$content = protect($_POST['content']);
		$trade_id = protect($_POST['trade_id']);
		if($row['uid'] !== $_SESSION['cm_uid']) { $uid = $row['uid']; } elseif($row['trader'] !== $_SESSION['cm_uid']) { $uid = $row['trader']; } else { } 
		$time = time();
		$author = $_SESSION['cm_uid'];
		if(empty($type)) { $results = error($lang['error_27']); }
		elseif(empty($content)) { $results = error($lang['error_28']); }
		else {
			$insert = $db->query("INSERT crypto_users_ratings (uid,type,trade_id,comment,author,time) VALUES ('$uid','$type','$row[id]','$content','$author','$time')");
			$results = success($lang['success_6']);
			$hide_form = 1;
		}
	}	
	$tpl->set("id",$row['id']);
	$tpl->set("ad_id",$row['ad_id']);
	$tpl->set("amount",$row['amount']);
	$tpl->set("currency",adinfo($row['ad_id'],"currency"));
	$tpl->set("crypto_amount",$row['crypto_amount']);
	$tpl->set("adlink",$adlink);
	$tpl->set("status",$status);
	$tpl->set("crypto_price",$row['crypto_price']);
	$tpl->set("session_uid",$_SESSION['cm_uid']);
	$tpl->set("CryptoCode",$ext);
	$tpl->set("results",$results);
	echo $tpl->output();
} elseif($b == "process") {
	$id = protect($_GET['id']);
	$query = $db->query("SELECT * FROM crypto_trades WHERE id='$id' and uid='$_SESSION[cm_uid]' or id='$id' and trader='$_SESSION[cm_uid]'");
	if($query->num_rows==0) { header("Location: $settings[url]"); }
	$row = $query->fetch_assoc();
	$minutes = $row['timeout']-time();
	$minutes = $minutes / 60;
	$minutes = ceil($minutes);
	if($minutes < 0) { $minutes = 0; }
						
	if($row['type'] == "sell_bitcoin") {
		if($row['status'] == "0") { 
			$status =  '<span class="text text-info">'.$lang[status_0].'</span>';
		} elseif($row['status'] == "1") {
			$status =  '<span class="text text-info">'.$lang[status_1_1].'</span>';
		} elseif($row['status'] == "2") {
			$status = '<span class="text text-info">'.$lang[status_2_1].'</span>';
		} elseif($row['status'] == "3") {
			$status = '<span class="text text-info">'.$lang[status_3_1].'</span>';
		} elseif($row['status'] == "4") {
			$status = '<span class="text text-danger">'.$lang[status_4].'</span>';
		} elseif($row['status'] == "5") {
			$status = '<span class="text text-danger">'.$lang[status_5].'</span>';
		} elseif($row['status'] == "6") {
			$status = '<span class="text text-danger">'.$lang[status_6].'</span>';
		} elseif($row['status'] == "7") {
			$status = '<span class="text text-success">'.$lang[status_7].'</span>';
		} else {
			$status = '<span class="text text-default">Unknown</span>';
		}
	} elseif($row['type'] == "buy_bitcoin") {
		if($row['status'] == "0") { 
			$status =  '<span class="text text-info">'.$lang[status_0].'</span>';
		} elseif($row['status'] == "1") {
			$status =  '<span class="text text-info">'.$lang[status_1_2].'</span>';
		} elseif($row['status'] == "2") {
			$status = '<span class="text text-info">'.$lang[status_2_2].'</span>';
		} elseif($row['status'] == "3") {
			$status = '<span class="text text-info">'.$lang[status_3_2].'</span>';
		} elseif($row['status'] == "4") {
			$status = '<span class="text text-danger">'.$lang[status_4].'</span>';
		} elseif($row['status'] == "5") {
			$status = '<span class="text text-danger">'.$lang[status_5].'</span>';
		} elseif($row['status'] == "6") {
			$status = '<span class="text text-danger">'.$lang[status_6].'</span>';
		} elseif($row['status'] == "7") {
			$status = '<span class="text text-success">'.$lang[status_7].'</span>';
		} else {
			$status = '<span class="text text-default">Unknown</span>';
		}
	} elseif($row['type'] == "sell_litecoin") {
		if($row['status'] == "0") { 
			$status =  '<span class="text text-info">'.$lang[status_0].'</span>';
		} elseif($row['status'] == "1") {
			$status =  '<span class="text text-info">'.$lang[status_1_1].'</span>';
		} elseif($row['status'] == "2") {
			$status = '<span class="text text-info">'.$lang[status_2_1].'</span>';
		} elseif($row['status'] == "3") {
			$status = '<span class="text text-info">'.$lang[status_3_1_1].'</span>';
		} elseif($row['status'] == "4") {
			$status = '<span class="text text-danger">'.$lang[status_4].'</span>';
		} elseif($row['status'] == "5") {
			$status = '<span class="text text-danger">'.$lang[status_5].'</span>';
		} elseif($row['status'] == "6") {
			$status = '<span class="text text-danger">'.$lang[status_6].'</span>';
		} elseif($row['status'] == "7") {
			$status = '<span class="text text-success">'.$lang[status_7].'</span>';
		} else {
			$status = '<span class="text text-default">Unknown</span>';
		}
	} elseif($row['type'] == "buy_litecoin") {
		if($row['status'] == "0") { 
			$status =  '<span class="text text-info">'.$lang[status_0].'</span>';
		} elseif($row['status'] == "1") {
			$status =  '<span class="text text-info">'.$lang[status_1_2].'</span>';
		} elseif($row['status'] == "2") {
			$status = '<span class="text text-info">'.$lang[status_2_2].'</span>';
		} elseif($row['status'] == "3") {
			$status = '<span class="text text-info">'.$lang[status_3_2_1].'</span>';
		} elseif($row['status'] == "4") {
			$status = '<span class="text text-danger">'.$lang[status_4].'</span>';
		} elseif($row['status'] == "5") {
			$status = '<span class="text text-danger">'.$lang[status_5].'</span>';
		} elseif($row['status'] == "6") {
			$status = '<span class="text text-danger">'.$lang[status_6].'</span>';
		} elseif($row['status'] == "7") {
			$status = '<span class="text text-success">'.$lang[status_7].'</span>';
		} else {
			$status = '<span class="text text-default">Unknown</span>';
		}
	} elseif($row['type'] == "sell_dogecoin") {
		if($row['status'] == "0") { 
			$status =  '<span class="text text-info">'.$lang[status_0].'</span>';
		} elseif($row['status'] == "1") {
			$status =  '<span class="text text-info">'.$lang[status_1_1].'</span>';
		} elseif($row['status'] == "2") {
			$status = '<span class="text text-info">'.$lang[status_2_1].'</span>';
		} elseif($row['status'] == "3") {
			$status = '<span class="text text-info">'.$lang[status_3_1_2].'</span>';
		} elseif($row['status'] == "4") {
			$status = '<span class="text text-danger">'.$lang[status_4].'</span>';
		} elseif($row['status'] == "5") {
			$status = '<span class="text text-danger">'.$lang[status_5].'</span>';
		} elseif($row['status'] == "6") {
			$status = '<span class="text text-danger">'.$lang[status_6].'</span>';
		} elseif($row['status'] == "7") {
			$status = '<span class="text text-success">'.$lang[status_7].'</span>';
		} else {
			$status = '<span class="text text-default">Unknown</span>';
		}
	} elseif($row['type'] == "buy_dogecoin") {
		if($row['status'] == "0") { 
			$status =  '<span class="text text-info">'.$lang[status_0].'</span>';
		} elseif($row['status'] == "1") {
			$status =  '<span class="text text-info">'.$lang[status_1_2].'</span>';
		} elseif($row['status'] == "2") {
			$status = '<span class="text text-info">'.$lang[status_2_2].'</span>';
		} elseif($row['status'] == "3") {
			$status = '<span class="text text-info">'.$lang[status_3_2_2].'</span>';
		} elseif($row['status'] == "4") {
			$status = '<span class="text text-danger">'.$lang[status_4].'</span>';
		} elseif($row['status'] == "5") {
			$status = '<span class="text text-danger">'.$lang[status_5].'</span>';
		} elseif($row['status'] == "6") {
			$status = '<span class="text text-danger">'.$lang[status_6].'</span>';
		} elseif($row['status'] == "7") {
			$status = '<span class="text text-success">'.$lang[status_7].'</span>';
		} else {
			$status = '<span class="text text-default">Unknown</span>';
		}
	} else { }
	
	if(tradeinfo($row['trade_id'],"type") == "buy_bitcoin") {
		$adid = tradeinfo($row['trade_id'],"ad_id");
		$pm = adinfo($adid,"payment_method");
		$pm = str_ireplace(" ","-",$pm);
		$adlink = $settings['url']."ad/".$pm."-to-Bitcoin/".$adid;
	} elseif(tradeinfo($row['trade_id'],"type") == "sell_bitcoin") { 
		$adid = tradeinfo($row['trade_id'],"ad_id");
		$pm = adinfo($adid,"payment_method");
		$pm = str_ireplace(" ","-",$pm);
		$adlink = $settings['url']."ad/Bitcoin-to-".$pm."/".$adid;
	} elseif(tradeinfo($row['trade_id'],"type") == "buy_litecoin") {
		$adid = tradeinfo($row['trade_id'],"ad_id");
		$pm = adinfo($adid,"payment_method");
		$pm = str_ireplace(" ","-",$pm);
		$adlink = $settings['url']."ad/".$pm."-to-Litecoin/".$adid;
	} elseif(tradeinfo($row['trade_id'],"type") == "sell_litecoin") { 
		$adid = tradeinfo($row['trade_id'],"ad_id");
		$pm = adinfo($adid,"payment_method");
		$pm = str_ireplace(" ","-",$pm);
		$adlink = $settings['url']."ad/Litecoin-to-".$pm."/".$adid;
	} elseif(tradeinfo($row['trade_id'],"type") == "buy_dogecoin") {
		$adid = tradeinfo($row['trade_id'],"ad_id");
		$pm = adinfo($adid,"payment_method");
		$pm = str_ireplace(" ","-",$pm);
		$adlink = $settings['url']."ad/".$pm."-to-Dogecoin/".$adid;
	} elseif(tradeinfo($row['trade_id'],"type") == "sell_dogecoin") { 
		$adid = tradeinfo($row['trade_id'],"ad_id");
		$pm = adinfo($adid,"payment_method");
		$pm = str_ireplace(" ","-",$pm);
		$adlink = $settings['url']."ad/Dogecoin-to-".$pm."/".$adid;
	} else { }
	
	if($row['network'] == "Bitcoin") {
		$CryptoCode = 'BTC';
		$buy_comission = $settings['bitcoin_buy_comission'];
		$sell_comission = $settings['bitcoin_sell_comission'];
		$lang_release = $lang['btn_release_bitcoins'];
		$lang_release_info_1 = $lang['are_you_sure_release_bitcoins_1'];
		$lang_release_info_2 = $lang['are_you_sure_release_bitcoins_2'];
		$btn_yes_release = $lang['btn_yes_release_bitcoins'];
		$success_7 = $lang['success_7'];
		$success_8 = $lang['success_8'];
		$success_9 = $lang['success_9'];
	} elseif($row['network'] == "Litecoin") {
		$CryptoCode = 'LTC';
		$buy_comission = $settings['litecoin_buy_comission'];
		$sell_comission = $settings['litecoin_sell_comission'];
		$lang_release = $lang['btn_release_litecoin'];
		$lang_release_info_1 = $lang['are_you_sure_release_litecoin_1'];
		$lang_release_info_2 = $lang['are_you_sure_release_bitcoins_2_1'];
		$btn_yes_release = $lang['btn_yes_release_litecoin'];
		$success_7 = $lang['success_7_1'];
		$success_8 = $lang['success_8_1'];
		$success_9 = $lang['success_9_1'];
	} elseif($row['network'] == "Dogecoin") {
		$CryptoCode = 'DOGE';
		$buy_comission = $settings['dogecoin_buy_comission'];
		$sell_comission = $settings['dogecoin_sell_comission'];
		$lang_release = $lang['btn_release_dogecoin'];
		$lang_release_info_1 = $lang['are_you_sure_release_dogecoin_1'];
		$lang_release_info_2 = $lang['are_you_sure_release_bitcoins_2_2'];
		$btn_yes_release = $lang['btn_yes_release_dogecoin'];
		$success_7 = $lang['success_7_2'];
		$success_8 = $lang['success_8_2'];
		$success_9 = $lang['success_9_2'];
	} else { }
	
	if($row['type'] == "sell_bitcoin" or $row['type'] == "sell_litecoin" or $row['type'] == "sell_dogecoin") {
		if($row['uid'] == $_SESSION['cm_uid']) {
			// Client side when he/she sell bitcoins
			$tpl = new Template("templates/".$settings['default_template']."/trade_sell_client.tpl",$lang);
			$tpl->set("url",$settings['url']);
			$results = '';
			if(isset($_POST['btc_release_bitcoins'])) {
					if($row['released_bitcoins'] == "0") {
						if($minutes !== "0") {
							$form = '<div class="alert alert-warning" style="font-size:18px;"><form action="" method="POST">
								<p>'.$lang_release_info_1.' <a href="'.$settings[url].'user/'.idinfo($row[trader],"username").'">'.idinfo($row[trader],"username").'</a>?</p>
								<small>'.$lang[this_action_can_be_undo].'</small>
								<br/><br/>
								<button type="submit" class="btn btn-success" name="btc_relaese_bitcoins_confirmed"><i class="fa fa-check"></i> '.$btn_yes_release.'</button> 
								<a href="" class="btn btn-danger"><i class="fa fa-times"></i> '.$lang[btn_no].'</a>
							</form>
							</div>';
							$results = $form;
						}
					}	
				}
				
				if(isset($_POST['btc_relaese_bitcoins_confirmed'])) {
					if($minutes !== "0") {
						if($row['released_bitcoins'] !== "1") {
						$update = $db->query("UPDATE crypto_trades SET status='7',released_bitcoins='1' WHERE id='$row[id]'");
						$uaddress = walletinfo($row['uid'],"address",$row['network']);
						$taddress = walletinfo($row['trader'],"address",$row['network']);
						$lid = walletinfo($_SESSION['cm_uid'],"lid",$row['network']);
						$license_query = $db->query("SELECT * FROM crypto_blockio_licenses WHERE id='$lid' ORDER BY id");
						$license = $license_query->fetch_assoc();
						$apiKey = $license['license'];
						$pin = $license['secret_pin'];
						$version = 2; // the API version
						$block_io = new BlockIo($apiKey, $pin, $version);
						$amounts = "$row[crypto_amount],$sell_comission";
						$addresses = "$taddress,$license[address]";
						$withdrawal = $block_io->withdraw_from_addresses(array('amounts' => $amounts, 'from_addresses' => $uaddress, 'to_addresses' => $addresses));
						$results = success($success_7);
						$query = $db->query("SELECT * FROM crypto_trades WHERE id='$row[id]'");
						$row = $query->fetch_assoc();
						}
					}
				}
				
				if(isset($_POST['btc_cancel_trade'])) {
					if($row['status']>1 or $row['status'] == "0") {
						$results = error($lang['error_47']);
					} else {
					$update = $db->query("UPDATE crypto_trades SET status='5' WHERE id='$row[id]'");
					$query = $db->query("SELECT * FROM crypto_trades WHERE id='$row[id]'");
					$row = $query->fetch_assoc();
					$results = info($lang['info_3']);
					}
				}
			$tpl->set("results",$results);
			$lang_trade_info_1 = str_ireplace("%payment_method%",adinfo($row['ad_id'],"payment_method"),$lang['trade_info_1']);
			$tpl->set("lang_trade_info_1",$lang_trade_info_1);
			$tpl->set("id",$row['id']);
			$tpl->set("ad_id",$row['ad_id']);
			$tpl->set("amount",$row['amount']);
			$tpl->set("currency",adinfo($row['ad_id'],"currency"));
			$tpl->set("crypto_amount",$row['crypto_amount']);
			$tpl->set("adlink",$adlink);
			$tpl->set("status",$status);
			$tpl->set("crypto_price",$row['crypto_price']);
			$tpl->set("session_uid",$_SESSION['cm_uid']);
			$tpl->set("CryptoCode",$CryptoCode);
			$buttons = '';
			if($row['status'] < 3) {	
				if($row['released_bitcoins'] == "0") {	
					if($minutes !== "0") { 	
						$buttons = '<button type="submit" class="btn btn-success" name="btc_release_bitcoins"><i class="fa fa-check"></i> '.$lang_release.'</button>
								<button type="submit" class="btn btn-danger" name="btc_cancel_trade" id="btc_cancel_trade"><i class="fa fa-times"></i> '.$lang[btn_cancel_trade].'</button>';
					} 	
				} 	
			} 		
			$tpl->set("buttons",$buttons);
			$leave_feedback_button = '';
			if($row['status'] == "7") {
				$check_feedback = $db->query("SELECT * FROM crypto_users_ratings WHERE trade_id='$row[id]' and author='$_SESSION[cm_uid]'");
				if($check_feedback->num_rows==0) {
					$leave_feedback_button = '<a href="'.$settings[url].'leave-feedback/trade/'.$row[id].'" class="btn btn-info"><i class="fa fa-comment"></i> '.$lang[btn_leave_feedback].'</a>';
				}
			}
			$tpl->set("leave_feedback_button",$leave_feedback_button);
			$trade_expire_status = '';
			if($minutes == 0) {
				$trade_expire_status = $lang['trade_info_4'];
			} else {
				$lang_trade_info_5 = str_ireplace("%minutes%",$minutes,$lang['trade_info_5']);
				$trade_expire_status = $lang_trade_info_5; 
			}
			$tpl->set("trade_expire_status",$trade_expire_status);
			$chat_messages = '';
			$getQuery = $db->query("SELECT * FROM crypto_trades_messages WHERE trade_id='$row[id]' ORDER BY id DESC");
			if($getQuery->num_rows>0) {
				while($get = $getQuery->fetch_assoc()) {
					if($_SESSION['cm_uid'] !== $get['uid']) {
						$update = $db->query("UPDATE crypto_trades_messages SET readed='1' WHERE id='$get[id]'");
					}
					if($get['attachment'] == "1") {
						$filename = basename($get['message']);
						$ctpl = new Template("templates/".$settings['default_template']."/rows/Chat_File.tpl",$lang);
						$ctpl->set("url",$settings['url']);
						$ctpl->set("filename",$filename);
						$ctpl->set("username",idinfo($get['uid'],"username"));
						$ctpl->set("file",$settings[url].$get[message]);
						$ctpl->set("timestamp",timeago($get['time']));
						$chat_messages .= $ctpl->output();
					} else {
						$ctpl = new Template("templates/".$settings['default_template']."/rows/Chat_Message.tpl",$lang);
						$ctpl->set("url",$settings['url']);
						$ctpl->set("message",$get['message']);
						$ctpl->set("username",idinfo($get['uid'],"username"));
						$ctpl->set("timestamp",timeago($get['time']));
						$chat_messages .= $ctpl->output();
					}
				}
			} else {
				$chat_messages = $lang['no_have_messsages'];
			}
			$tpl->set("chat_messages",$chat_messages);
			echo $tpl->output();	
		} elseif($row['trader'] == $_SESSION['cm_uid']) {
			// Trader side when client sell bitcoins
			$tpl = new Template("templates/".$settings['default_template']."/trade_sell_trader.tpl",$lang);
			$tpl->set("url",$settings['url']);
			$tpl->set("CounterpartInfo", LocalTrading::getUserInfoHtml($row['uid'], "buyer"));
			$results = '';
			if(isset($_POST['btc_payment_was_made'])) {
				if($minutes !== "0") {
					$form = '<div class="alert alert-warning" style="font-size:18px;"><form action="" method="POST">
								<p>'.$lang[are_you_sure_made_payment].' <a href="'.$settings[url].'user/'.idinfo($row[uid],"username").'">'.idinfo($row[uid],"username").'</a> '.$lang[with_amount].' '.$row[amount].' '.adinfo($row[ad_id],"currency").'?</p>
											<small>'.$lang[this_action_can_be_undo].'</small>
											<br/><br/>
											<button type="submit" class="btn btn-success" name="btc_pwm_confirmed"><i class="fa fa-check"></i> '.$lang[btn_yes_made_payment].'</button> 
											<a href="" class="btn btn-danger"><i class="fa fa-times"></i> '.$lang[btn_no].'</a>
										</form>
										</div>';
										$results = $form;
				}
			}
							
			if(isset($_POST['btc_pwm_confirmed'])) {
				if($row['status'] !== "2" or $row['status'] < "2") {
								$update = $db->query("UPDATE crypto_trades SET status='2' WHERE id='$row[id]'");
								$query = $db->query("SELECT * FROM crypto_trades WHERE id='$row[id]'");
								$row = $query->fetch_assoc();
								$results = success($success_8);
				}
			}
							
			if(isset($_POST['btc_cancel_trade'])) {
				if($row['status']>1 or $row['status'] == "0") {
									$results = error($lang['error_47']);
				} else {
								$update = $db->query("UPDATE crypto_trades SET status='4' WHERE id='$row[id]'");
								$query = $db->query("SELECT * FROM crypto_trades WHERE id='$row[id]'");
								$row = $query->fetch_assoc();
								$results = info($lang['info_2']);
				}
			}
			$tpl->set("results",$results);
			$tpl->set("id",$row['id']);
			$tpl->set("amount",$row['amount']);
			$tpl->set("currency",adinfo($row['ad_id'],"currency"));
			$tpl->set("crypto_amount",$row['crypto_amount']);
			$tpl->set("ad_id",$row['ad_id']);
			$tpl->set("adlink",$adlink);
			$tpl->set("status",$status);
			$tpl->set("crypto_price",$row['crypto_price']);
			$tpl->set("session_uid",$_SESSION['cm_uid']);
			$tpl->set("CryptoCode",$CryptoCode);
			$lang_trade_info_6 = str_ireplace("%amount%",$row['amount'],$lang['trade_info_6']);
			$lang_trade_info_6 = str_ireplace("%currency%",adinfo($row['ad_id'],"currency"),$lang_trade_info_6);
			$lang_trade_info_6 = str_ireplace("%payment_method%",adinfo($row['ad_id'],"payment_method"),$lang_trade_info_6);
			$tpl->set("lang_trade_info_6",$lang_trade_info_6);
			$tpl->set("payment_instructions",nl2br($row['payment_instructions']));
			$buttons = '';
			if($row['status'] < 3) {	
				if($row['released_bitcoins'] == "0") { 	
					if($row['status'] < 2) { 	
						if($minutes !== "0") {	
							$buttons = '<button type="submit" class="btn btn-success" name="btc_payment_was_made"><i class="fa fa-check"></i> '.$lang[btn_payment_was_made].'</button>
										<button type="submit" class="btn btn-danger" name="btc_cancel_trade" id="btc_cancel_trade"><i class="fa fa-times"></i> '.$lang[btn_cancel_trade].'</button>';
						} 	
					} 
				} 
			} 
			$leave_feedback_button = '';
			if($row['status'] == "7") { 
				$check_feedback = $db->query("SELECT * FROM crypto_users_ratings WHERE trade_id='$row[id]' and author='$_SESSION[cm_uid]'");
				if($check_feedback->num_rows==0) {
					$leave_feedback_button = '<a href="'.$settings[url].'leave-feedback/trade/'.$row[id].'" class="btn btn-info"><i class="fa fa-comment"></i> '.$lang[btn_leave_feedback].'</a>';
				}
			}
			$tpl->set("leave_feedback_button",$leave_feedback_button);
			$tpl->set("buttons",$buttons);
			$trade_expire_status = '';
			if($minutes == 0) {
				$trade_expire_status = $lang['trade_info_4'];
			} else {
				$lang_trade_info_8 = str_ireplace("%minutes%",$minutes,$lang['trade_info_8']);
				$trade_expire_status = $lang_trade_info_8;
			}
			$tpl->set("trade_expire_status",$trade_expire_status);
			$chat_messages = '';
			$getQuery = $db->query("SELECT * FROM crypto_trades_messages WHERE trade_id='$row[id]' ORDER BY id DESC");
			if($getQuery->num_rows>0) {
				while($get = $getQuery->fetch_assoc()) {
					if($_SESSION['cm_uid'] !== $get['uid']) {
						$update = $db->query("UPDATE crypto_trades_messages SET readed='1' WHERE id='$get[id]'");
					}
					if($get['attachment'] == "1") {
						$filename = basename($get['message']);
						$ctpl = new Template("templates/".$settings['default_template']."/rows/Chat_File.tpl",$lang);
						$ctpl->set("url",$settings['url']);
						$ctpl->set("filename",$filename);
						$ctpl->set("username",idinfo($get['uid'],"username"));
						$ctpl->set("file",$settings[url].$get[message]);
						$ctpl->set("timestamp",timeago($get['time']));
						$chat_messages .= $ctpl->output();
					} else {
						$ctpl = new Template("templates/".$settings['default_template']."/rows/Chat_Message.tpl",$lang);
						$ctpl->set("url",$settings['url']);
						$ctpl->set("message",$get['message']);
						$ctpl->set("username",idinfo($get['uid'],"username"));
						$ctpl->set("timestamp",timeago($get['time']));
						$chat_messages .= $ctpl->output();
					}
				}
			} else {
				$chat_messages = $lang['no_have_messsages'];
			}
			$tpl->set("chat_messages",$chat_messages);
			echo $tpl->output();
		} else {
			header("Location: $settings[url]");
		}
	} elseif($row['type'] == "buy_bitcoin" or $row['type'] == "buy_litecoin" or $row['type'] == "buy_dogecoin") {
		if($row['uid'] == $_SESSION['cm_uid']) {
			// Client side when he/she buy bitcoins
			$tpl = new Template("templates/".$settings['default_template']."/trade_buy_client.tpl",$lang);
			$tpl->set("url",$settings['url']);
			$results = '';
			if(isset($_POST['btc_payment_was_made'])) {
				if($minutes !== "0") {
									$form = '<div class="alert alert-warning" style="font-size:18px;"><form action="" method="POST">
											<p>'.$lang[are_you_sure_made_payment_2].' <a href="'.$settings[url].'user/'.idinfo($row[trader],"username").'">'.idinfo($row[trader],"username").'</a> '.$lang[with_amount].' '.$row[amount].' '.adinfo($row[ad_id],"currency").'?</p>
											<small>'.$lang[this_action_can_be_undo].'</small>
											<br/><br/>
											<button type="submit" class="btn btn-success" name="btc_pwm_confirmed"><i class="fa fa-check"></i> '.$lang[btn_yes_made_payment].'</button> 
											<a href="" class="btn btn-danger"><i class="fa fa-times"></i> '.$lang[btn_no].'</a>
										</form>
										</div>';
										$results = $form;
				}
			}
							
			if(isset($_POST['btc_pwm_confirmed'])) {
				if($row['status'] !== "2" or $row['status'] < "2") {
								$update = $db->query("UPDATE crypto_trades SET status='2' WHERE id='$row[id]'");
								$query = $db->query("SELECT * FROM crypto_trades WHERE id='$row[id]'");
								$row = $query->fetch_assoc();
								$results = success($success_9);
				}
			}
							
							
			if(isset($_POST['btc_cancel_trade'])) {
				if($row['status']>1 or $row['status'] == "0") {
									$results = error($lang['error_47']);
				} else {
								$update = $db->query("UPDATE crypto_trades SET status='4' WHERE id='$row[id]'");
								$query = $db->query("SELECT * FROM crypto_trades WHERE id='$row[id]'");
								$row = $query->fetch_assoc();
								$results = info($lang['info_2']);
				}
			}
			$tpl->set("results",$results);
			$tpl->set("id",$row['id']);
			$tpl->set("amount",$row['amount']);
			$tpl->set("currency",adinfo($row['ad_id'],"currency"));
			$tpl->set("crypto_amount",$row['crypto_amount']);
			$tpl->set("adlink",$adlink);
			$tpl->set("ad_id",$row['ad_id']);
			$tpl->set("status",$status);
			$tpl->set("crypto_price",$row['crypto_price']);
			$tpl->set("session_uid",$_SESSION['cm_uid']);
			$tpl->set("CryptoCode",$CryptoCode);
			$lang_trade_info_9 = str_ireplace("%amount%",$row['amount'],$lang['trade_info_9']);
			$lang_trade_info_9 = str_ireplace("%currency%",adinfo($row['ad_id'],"currency"),$lang_trade_info_9);
			$lang_trade_info_9 = str_ireplace("%payment_method%",adinfo($row['ad_id'],"payment_method"),$lang_trade_info_9);
			$tpl->set("lang_trade_info_9",$lang_trade_info_9);
			$tpl->set("payment_instructions",nl2br($row['payment_instructions']));
			$lang_trade_info_10 = str_ireplace("%payment_hash%",$row['payment_hash'],$lang['trade_info_10']);
			$tpl->set("lang_trade_info_10",$lang_trade_info_10);
			$buttons = '';
			if($row['status'] < 3) {
				if($row['released_bitcoins'] == "0") {
					if($row['status'] < 2) { 
						if($minutes !== "0") { 
							$buttons = '<button type="submit" class="btn btn-success" name="btc_payment_was_made"><i class="fa fa-check"></i> '.$lang[btn_payment_was_made].'</button>
										<button type="submit" class="btn btn-danger" name="btc_cancel_trade" id="btc_cancel_trade"><i class="fa fa-times"></i> '.$lang[btn_cancel_trade].'</button>'; 
						} 
					} 
				} 
			} 
			$tpl->set("buttons",$buttons);
			$leave_feedback_button = '';
			if($row['status'] == "7") { 
				$check_feedback = $db->query("SELECT * FROM crypto_users_ratings WHERE trade_id='$row[id]' and author='$_SESSION[cm_uid]'");
				if($check_feedback->num_rows==0) {
					$leave_feedback_button = '<a href="'.$settings[url].'leave-feedback/trade/'.$row[id].'" class="btn btn-info"><i class="fa fa-comment"></i> '.$lang[btn_leave_feedback].'</a>'; 
				}
			}
			$tpl->set("leave_feedback_button",$leave_feedback_button);
			$trade_expire_status = '';
			if($minutes == 0) {
				$trade_expire_status = $lang['trade_info_4']; 
			} else {
				$lang_trade_info_5 = str_ireplace("%minutes%",$minutes,$lang['trade_info_5']);
				$trade_expire_status = $lang_trade_info_5;
			}
			$tpl->set("trade_expire_status",$trade_expire_status);
			$chat_messages = '';
			$getQuery = $db->query("SELECT * FROM crypto_trades_messages WHERE trade_id='$row[id]' ORDER BY id DESC");
			if($getQuery->num_rows>0) {
				while($get = $getQuery->fetch_assoc()) {
					if($_SESSION['cm_uid'] !== $get['uid']) {
						$update = $db->query("UPDATE crypto_trades_messages SET readed='1' WHERE id='$get[id]'");
					}
					if($get['attachment'] == "1") {
						$filename = basename($get['message']);
						$ctpl = new Template("templates/".$settings['default_template']."/rows/Chat_File.tpl",$lang);
						$ctpl->set("url",$settings['url']);
						$ctpl->set("filename",$filename);
						$ctpl->set("username",idinfo($get['uid'],"username"));
						$ctpl->set("file",$settings[url].$get[message]);
						$ctpl->set("timestamp",timeago($get['time']));
						$chat_messages .= $ctpl->output();
					} else {
						$ctpl = new Template("templates/".$settings['default_template']."/rows/Chat_Message.tpl",$lang);
						$ctpl->set("url",$settings['url']);
						$ctpl->set("message",$get['message']);
						$ctpl->set("username",idinfo($get['uid'],"username"));
						$ctpl->set("timestamp",timeago($get['time']));
						$chat_messages .= $ctpl->output();
					}
				}
			} else {
				$chat_messages = $lang['no_have_messsages'];
			}
			$tpl->set("chat_messages",$chat_messages);
			echo $tpl->output();
		} elseif($row['trader'] == $_SESSION['cm_uid']) {
			// Trader side when client buy bitcoins
			$tpl = new Template("templates/".$settings['default_template']."/trade_buy_trader.tpl",$lang);
			$tpl->set("url",$settings['url']);
			$results = '';
			if(isset($_POST['btc_release_bitcoins'])) {
				if($row['released_bitcoins'] == "0") {
					if($minutes !== "0") {
										$form = '<div class="alert alert-warning" style="font-size:18px;"><form action="" method="POST">
											<p>'.$lang_release_info_2.' <a href="'.$settings[url].'user/'.idinfo($row[uid],"username").'">'.idinfo($row[uid],"username").'</a>?</p>
											<small>'.$lang[this_action_can_be_undo].'</small>
											<br/><br/>
											<button type="submit" class="btn btn-success" name="btc_relaese_bitcoins_confirmed"><i class="fa fa-check"></i> '.$btn_yes_release.'</button> 
											<a href="" class="btn btn-danger"><i class="fa fa-times"></i> '.$lang[btn_no].'</a>
										</form>
										</div>';
										$results = $form;
					}
				}	
			}
							
			if(isset($_POST['btc_relaese_bitcoins_confirmed'])) {
				if($minutes !== "0") {
					if($row['released_bitcoins'] !== "1") {
									$update = $db->query("UPDATE crypto_trades SET status='7',released_bitcoins='1' WHERE id='$row[id]'");
									$uaddress = walletinfo($row['uid'],"address",$row['network']);
									$taddress = walletinfo($row['trader'],"address",$row['network']);
									$lid = walletinfo($_SESSION['cm_uid'],"lid",$row['network']);
									$license_query = $db->query("SELECT * FROM crypto_blockio_licenses WHERE id='$lid' ORDER BY id");
									$license = $license_query->fetch_assoc();
									$apiKey = $license['license'];
									$pin = $license['secret_pin'];
									$version = 2; // the API version
									$block_io = new BlockIo($apiKey, $pin, $version);
									$amounts = "$row[crypto_amount],$buy_comission";
									$addresses = "$uaddress,$license[address]";
									$withdrawal = $block_io->withdraw_from_addresses(array('amounts' => $amounts, 'from_addresses' => $taddress, 'to_addresses' => $addresses));
									$results = success($success_7);
									$query = $db->query("SELECT * FROM crypto_trades WHERE id='$row[id]'");
									$row = $query->fetch_assoc();
					}
				}
			}
							
			if(isset($_POST['btc_cancel_trade'])) {
				if($row['status']>1 or $row['status'] == "0") {
									$results = error($lang['error_47']);
				} else {
								$update = $db->query("UPDATE crypto_trades SET status='5' WHERE id='$row[id]'");
								$query = $db->query("SELECT * FROM crypto_trades WHERE id='$row[id]'");
								$row = $query->fetch_assoc();
								$results = info($lang['info_2']);
				}
			}
			$tpl->set("results",$results);
			$tpl->set("id",$row['id']);
			$tpl->set("amount",$row['amount']);
			$tpl->set("currency",adinfo($row['ad_id'],"currency"));
			$tpl->set("ad_id",$row['ad_id']);
			$tpl->set("crypto_amount",$row['crypto_amount']);
			$tpl->set("adlink",$adlink);
			$tpl->set("status",$status);
			$tpl->set("crypto_price",$row['crypto_price']);
			$tpl->set("session_uid",$_SESSION['cm_uid']);
			$tpl->set("CryptoCode",$CryptoCode);
			$lang_trade_info_12 = str_ireplace("%payment_method%",adinfo($row['ad_id'],"payment_method"),$lang['trade_info_12']);
			$tpl->set("lang_trade_info_12",$lang_trade_info_12);
			$lang_trade_info_13 = str_ireplace("%payment_hash%",$row['payment_hash'],$lang['trade_info_13']);
			$tpl->set("lang_trade_info_13",$lang_trade_info_13);
			$buttons = '';
			if($row['status'] < 3) { 
				if($row['released_bitcoins'] == "0") { 
					if($minutes !== "0") { 
						$buttons = '<button type="submit" class="btn btn-success" name="btc_release_bitcoins"><i class="fa fa-check"></i> '.$lang_release.'</button>
										<button type="submit" class="btn btn-danger" name="btc_cancel_trade" id="btc_cancel_trade"><i class="fa fa-times"></i> '.$lang[btn_cancel_trade].'</button>';
					}
				}
			} 
			$tpl->set("buttons",$buttons);
			$leave_feedback_button = '';
			$check_feedback = $db->query("SELECT * FROM crypto_users_ratings WHERE trade_id='$row[id]' and author='$_SESSION[cm_uid]'");
			if($check_feedback->num_rows==0) {
				$leave_feedback_button = '<a href="'.$settings[url].'leave-feedback/trade/'.$row[id].'" class="btn btn-info"><i class="fa fa-comment"></i> '.$lang[btn_leave_feedback].'</a>';
			}
			$tpl->set("leave_feedback_button",$leave_feedback_button);
			$trade_expire_status = '';
			if($minutes == 0) {
				$trade_expire_status = $lang['trade_info_4'];
			} else {
				$lang_trade_info_8 = str_ireplace("%minutes%",$minutes,$lang['trade_info_8']);
				$trade_expire_status = $lang_trade_info_8;
			}
			$tpl->set("trade_expire_status",$trade_expire_status);
			$chat_messages = '';
			$getQuery = $db->query("SELECT * FROM crypto_trades_messages WHERE trade_id='$row[id]' ORDER BY id DESC");
			if($getQuery->num_rows>0) {
				while($get = $getQuery->fetch_assoc()) {
					if($_SESSION['cm_uid'] !== $get['uid']) {
						$update = $db->query("UPDATE crypto_trades_messages SET readed='1' WHERE id='$get[id]'");
					}
					if($get['attachment'] == "1") {
						$filename = basename($get['message']);
						$ctpl = new Template("templates/".$settings['default_template']."/rows/Chat_File.tpl",$lang);
						$ctpl->set("url",$settings['url']);
						$ctpl->set("filename",$filename);
						$ctpl->set("username",idinfo($get['uid'],"username"));
						$ctpl->set("file",$settings[url].$get[message]);
						$ctpl->set("timestamp",timeago($get['time']));
						$chat_messages .= $ctpl->output();
					} else {
						$ctpl = new Template("templates/".$settings['default_template']."/rows/Chat_Message.tpl",$lang);
						$ctpl->set("url",$settings['url']);
						$ctpl->set("message",$get['message']);
						$ctpl->set("username",idinfo($get['uid'],"username"));
						$ctpl->set("timestamp",timeago($get['time']));
						$chat_messages .= $ctpl->output();
					}
				}
			} else {
				$chat_messages = $lang['no_have_messsages'];
			}
			$tpl->set("chat_messages",$chat_messages);
			echo $tpl->output();
		} else {
			header("Location: $settings[url]");
		}
	} else {
		header("Location: $settings[url]");
	}
} else { 
	header("Location: $settings[url]");
}
?>