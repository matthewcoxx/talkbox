<?php

	//smf. here. now. 
	require(dirname(__FILE__) . '/../SSI.php');
	
	require_once('tb_inc.php');
	
	$action = $_GET['action'];
	
	//redirect if no action set
	if(empty($action)){
		global $scripturl;
		header("Location: $scripturl");
	}
	
	//set shout vars
	if($action == 'sendshout'){
	
		global $user_info;
		if($modSettings['TalkBox_Disable_Guests']){
			if(!$user_info['is_guest']){
				$tbShout = new sendShout;
				$tbShout->poster_user = $user_info['name'];
				$tbShout->poster_id = $user_info['id'];
				$tbShout->poster_ip = $user_info['ip'];
				$tbShout->poster_message = $_POST['msg'];
				$tbShout->poster_date = time();
				$tbShout->send();
			}
		}
		else {
			$tbShout = new sendShout;
			$tbShout->poster_user = $user_info['name'];
			$tbShout->poster_id = $user_info['id'];
			$tbShout->poster_ip = $user_info['ip'];
			$tbShout->poster_message = $_POST['msg'];
			$tbShout->poster_date = time();
			$tbShout->send();
		}
	}
	
	//lets get em
	if($action == 'getshouts'){
		
		//lets set the get type
		if($_GET['type'] == 'full'){
			$getType = 'full';
		}
		if($_GET['type'] == 'normal'){
			$getType = 'normal';
		}
		
		//start the process
		$tbGrab = new getShout;
		$tbGrab->get_type = $getType;
		$tbGrab->get();
	
	}
	
	//edit a shout
	if($action == 'edit'){
		//clean that stuff up
		$id = addslashes(htmlspecialchars($_POST['id']));
		$msg = censorText(addslashes(htmlspecialchars($_POST['msg'])));
		
		//if is user
		$user = $smcFunc['db_query']('', "SELECT user_id FROM {db_prefix}talkbox WHERE id='$id'",array());
		$row = $smcFunc['db_fetch_assoc']($user);
		
		//if you have the authoritah
		if(allowedTo('talkbox_admin') || $user_info['id'] == $row['user_id']){
			//update it!
			$smcFunc['db_query']('', "UPDATE {db_prefix}talkbox SET message='$msg' WHERE id='$id'",array());
			
			//refresh all shouts
			$tbRefresh = new sendShout;
			$tbRefresh->refresh('2');
		}
		
	}
	
	//delete a shout
	if($action == 'del'){
	
		//clean the id
		$id = addslashes(htmlspecialchars($_POST['id']));
		
		//if is user
		$user = $smcFunc['db_query']('', "SELECT user_id FROM {db_prefix}talkbox WHERE id='$id'",array());
		$row = $smcFunc['db_fetch_assoc']($user);
		
		//if you have the authoritah
		if(allowedTo('talkbox_admin') || $user_info['id'] == $row['user_id']){
			//delete it!
			$smcFunc['db_query']('', "DELETE FROM {db_prefix}talkbox WHERE id='$id'",array());
		
			//refresh all shouts
			$tbRefresh = new sendShout;
			$tbRefresh->refresh('2');
		}
	}
	
	//get a single message
	if($action == 'getmessage'){
		$id = addslashes(htmlspecialchars($_GET['id']));
		
		//grab the message
		$grab = $smcFunc['db_query']('', "SELECT message FROM {db_prefix}talkbox WHERE id='$id'",array());
		$row = $smcFunc['db_fetch_assoc']($grab);
		
		echo $row['message'];
	}
	
	//ban the douche
	if($action == 'ban' && allowedTo('talkbox_admin')){
	
		$user = $_POST['user'];
		$ip = $_POST['ip'];
		
		if($user_info['is_guest'])
			$user = 'guestid='.substr(md5($_POST['ip']), 0, 4);
			
		$smcFunc['db_insert']('', '{db_prefix}talkbox_banned', array(
				'ip' => 'text', 'user' => 'text'
			), array(
				$ip, $user
			),
				array('autopost')
			);
			
		$tbRefresh = new sendShout;
		$tbRefresh->refresh('2');
	}
	
	//unban the douche
	if($action == 'unban' && allowedTo('talkbox_admin')){
		if(!empty($_POST['ip']))
		$ip = $_POST['ip'];
		
		if(!empty($_GET['panel_unban']))
		$ip = $_GET['ip'];
		
		$smcFunc['db_query']('', "DELETE FROM {db_prefix}talkbox_banned WHERE ip='$ip'",array());
		
		$tbRefresh = new sendShout;
		$tbRefresh->refresh('2');
		
		if(!empty($_GET['panel_unban']))
		header('Location: tb_exec.php?action=banned_panel');
		
	}
	
	//banned panel
	if($action == 'banned_panel' && allowedTo('talkbox_admin')){

		echo '
		<html>
		<head>
		<title>'.$txt['tb_banned_panel'].'</title>
		<link rel="stylesheet" type="text/css" href="'.$settings['theme_url'].'/css/index.css?rc2" />
		<style>table {background:white;border-collapse:collapse; tr, td{margin:0; padding:0;border:1px solid black;}</style>
		</head>
		<body>';
		$banned = $smcFunc['db_query']('', "SELECT * FROM {db_prefix}talkbox_banned",array());
		
		echo '<table width="100%" border="1" style="border-style:solid;margin:0;padding:0;">  <thead><tr><th>'.$txt['tb_user'].'</th><th>'.$txt['tb_ip'].'</th><th>'.$txt['tb_unban'].'</th></tr></thead><tbody>';
		while($row = $smcFunc['db_fetch_assoc']($banned)){
			echo '<tr><td>'.$row['user'].'</td><td>'.$row['ip'].'</td><td style="text-align:center;"><a href="tb_exec.php?action=unban&panel_unban=1&ip='.$row['ip'].'" style="color:red;" onclick="if(confirm(\''.$txt['tb_unban'].' '.$row['user'].'?\')) return true; else return false;">'.$txt['tb_unban'].'</a></td>';
		}
		if($smcFunc['db_num_rows']($banned) == 0)
			echo '<tr><td colspan="3" style="text-align:center;">'.$txt['tb_banned_nobody'].'</td><tr>';
		
		echo '</tbody>
		</table>';
		echo '</body>
		</html>';
	
	}
	
	
?>
