<?php
	//lets go to english class :)
	require('languages/english.php');
	
	//check banned function
	function tb_banned($ip){
		global $smcFunc;
		$check = $smcFunc['db_query']('', "SELECT * FROM {db_prefix}talkbox_banned WHERE ip='$ip'",array());
		$return = $smcFunc['db_num_rows']($check);
		if(!empty($return))
			return true;
		else 
			return false;
	}
	
	//see if they are banned from the TalkBox or the Forum
	if(is_not_banned() || tb_banned($user_info['ip'])){
		die($txt['tb_banned'].'<br>');
		exit();
	}
	
	
	
	
	
	
	
	
	
	
	//whitespace nothing to see here..................
	//sendShout class
	
	
	
	
	
	
	
	
	
	//send shout class
	class sendShout {
	
		var $poster_user;
		var $poster_ip;
		var $poster_message;
		var $poster_date;
		var $refresh_type = '1';
		
		function send(){
		
			global $db_prefix, $smcFunc, $user_info;
			
			if($user_info['is_guest'])
			$this->poster_user = 'guestid='.substr(md5($user_info['ip']), 0, 4);
			
			//lets clean everything
			$this->clean();
			
			//lets run the commands
			$this->commands();
			
			//lets finally send it
			$smcFunc['db_insert']('', '{db_prefix}talkbox', array(
				'user_id' => 'int', 'user' => 'string-25', 'ip' => 'string-16', 'date' => 'text', 'message' => 'text'
			), array(
				$this->poster_id, $this->poster_user, $this->poster_ip, $this->poster_date, $this->poster_message
			),
				array('autopost')
			);
			
			$this->refresh($this->refresh_type);
			
		}
		
		//lets check for commands
		function commands(){
		
			global $db_prefix, $smcFunc, $txt;
			
			//clear command
			if($this->poster_message == '/clear' && allowedTo('talkbox_admin')){
				$smcFunc['db_query']('', "TRUNCATE {db_prefix}talkbox",array());
				$this->poster_message = '<span class="tb_clear">'.$txt['tb_clear_message'].'</span>';
				$this->poster_user = $txt['tb_bot'];
				$this->poster_ip = '1.3.3.7';
				//clear tb only
				$this->clear('1');
			}
			
            //other commands
		
		}
		
		//lets clean it all up
		function clean(){
		
			//ewwwwww someone needs a bath!
			//$this->poster_message = addslashes($this->poster_message);
			$this->poster_message = htmlspecialchars($this->poster_message);
			$this->poster_message = censorText($this->poster_message);
			
		}
		
		function clear($settings){
			
			//clear tb only
			if($settings == '1'){
				$this->refresh_type = '2';
			}		
			

		}
		
		function refresh($settings){
			
			//normal refresh
			if($settings == '1'){
				$refresh = fopen('refresh.txt', 'w');
				fwrite($refresh, $this->poster_date);
				fclose($refresh);
			}
			
			//full refresh
			if($settings == '2'){
				$refresh = fopen('refresh.txt', 'w');
				fwrite($refresh, 'clear');
				fclose($refresh);
			}
			
			
		}
	
	}
	
	
	
	
	
	
	
	
	
	//whitespace nothing to see here..................
	//getShout class
	
	
	
	
	
	
	
	
	class getShout {
		
		var $get_offset;
		var $get_type;
		var $get_query;
		
		function get(){
			
			//lets setup everything for the get
			$this->setup();
			
			global $db_prefix, $smcFunc, $user_info, $user_settings, $scripturl, $txt;
			$grab = $smcFunc['db_query']('', "SELECT * FROM {db_prefix}talkbox ORDER BY id ASC ".$this->get_query,array());
			
			
			while($row = $smcFunc['db_fetch_assoc']($grab)){
				
				//date stamp
				$d = mktime(date("h")+$user_settings['time_offset']);
				$d = date('h', $d);
				
				//lets setup the shout vars
				$shoutDate = date($d.':i:s m-d-Y', $row['date']);
				$shoutId = $row['id'];
				$shoutUser = $row['user'];
				$shoutUserId = $row['user_id'];
				$shoutMessage = parse_bbc($row['message']);
				$shoutIp = $row['ip'];
				$shoutUserProfile = $scripturl.'?action=profile;u='.$shoutUserId;
				$shoutUserLink = '<a href="'.$scripturl.'?action=profile;u='.$shoutUserId.'">'.$shoutUser.'</a>';
				$shoutFunctions = '';
				$beforeShout = '<div class="posts windowbg">';
				$afterShout = '</div>';
				$beforeMessage = '<span class="message">';
				$afterMessage = '</span>';
				$beforeUser = '<span class="username" title='.$shoutDate.'>';
				$afterUser = '</span>: ';
				$beforeDate = '<span class="date">[';
				$afterDate = ']</span> ';
				$beforeFunctions = '<span class="functions">';
				$afterFunctions = '</span>';
				
				global $modSettings;
				if(!empty($modSettings['TalkBox_Disable_Date'])){
					$beforeDate = '';
					$afterDate = '';
					$shoutDate = '';
				}
				
				//setup ban link depending wether banned or not
				if(allowedTo('talkbox_admin') && tb_banned($shoutIp))
					$shoutBan = '<a href="javascript:tbUnban(\''.$shoutIp.'\', \''.$shoutUser.'\')"><span style="color:red;">['.$txt['tb_unban'].']</span></a>';
				else
					$shoutBan = '<a href="javascript:tbBan(\''.$shoutIp.'\', \''.$shoutUser.'\')">['.$txt['tb_ban'].']</a>';
				
				//admin ban - edit - del
				if(allowedTo('talkbox_admin') && $user_info['id'] != $shoutUserId){
					$shoutUserLink = '<a href="'.$scripturl.'?action=profile;area=tracking;sa=ip;searchip='.$shoutIp.'">'.$shoutUser.'</a>';
					$shoutFunctions = $shoutBan.' <a href="javascript:tbEdit(\''.$shoutId.'\')">['.$txt['tb_edit'].']</a> <a href="javascript:tbDel(\''.$shoutId.'\')">['.$txt['tb_del'].']</a>';
				}
				
				//setup edit/del links for normal users
				if($user_info['id'] == $shoutUserId && empty($modSettings['TalkBox_Disable_Functions'])){
					$shoutFunctions = '<a href="javascript:tbEdit(\''.$shoutId.'\')">['.$txt['tb_edit'].']</a> <a href="javascript:tbDel(\''.$shoutId.'\')">['.$txt['tb_del'].']</a>';
				}
				
				if($user_info['is_guest'])
					$shoutFunctions = '';
				
				//echo all that stuff
				echo $beforeShout.$beforeDate.$shoutDate.$afterDate.$beforeUser.$shoutUserLink.$afterUser.$beforeMessage.$shoutMessage.$afterMessage.$beforeFunctions.$shoutFunctions.$afterFunctions.$afterShout;
				
			}
			
		}
		
		function setup(){
		
			global $db_prefix, $smcFunc;
			
			//lets get the number of shouts
			$result = $smcFunc['db_query']('', "SELECT id FROM {db_prefix}talkbox",array());
			$limit = $smcFunc['db_num_rows']($result);
			
			//if no session var is set
			if(empty($_SESSION['get_offset'])){
				$_SESSION['get_offset'] = $limit;
			}
			
			//lets set the vars for the query
			$this->get_limit = $limit;
			$this->get_offset = $_SESSION['get_offset'];
			
			$_SESSION['get_offset'] = $limit;
			
			//setup query for normal get
			if($this->get_type == 'normal'){
				$this->get_query = 'LIMIT '.$this->get_limit.' OFFSET '.$this->get_offset;
			}
			
			//setup query for full get
			if($this->get_type == 'full'){
				$this->get_query = '';
			}
			
		}
		
	}
	
	
	
	
	
	
	
	
	//whitespace nothing to see here.................. 
	//loadSmileys() and printSmileys() Big thanks to pongsak 
	
	
	
	
	
	
	
	function loadSmileys(){

		global $context, $settings, $user_info, $txt, $modSettings, $db_prefix, $smcFunc;

		// Initialize smiley array... if not loaded before.
		if(empty($context['smileys'])){
			$context['smileys'] = array(
			'postform' => array(),
			'popup' => array(),
			);

		// Load smileys.
			if($user_info['smiley_set'] != 'none'){
				if(($temp = cache_get_data('posting_smileys', 480)) == null){
			
					$request = $smcFunc['db_query']('', '
					SELECT code, filename, description, smiley_row, hidden
					FROM {db_prefix}smileys
					WHERE hidden IN (0, 2)
					ORDER BY smiley_row, smiley_order',
					array());
				
					while($row = $smcFunc['db_fetch_assoc']($request)){
						$row['filename'] = htmlspecialchars($row['filename']);
						$row['description'] = htmlspecialchars($row['description']);
						$context['smileys'][empty($row['hidden']) ? 'postform' : 'popup'][$row['smiley_row']]['smileys'][] = $row;
					}
					$smcFunc['db_free_result']($request);

					cache_put_data('posting_smileys', $context['smileys'], 480);
				}
				else
					$context['smileys'] = $temp;
			}

			// Clean house... add slashes to the code for javascript.
			foreach(array_keys($context['smileys']) as $location){
				foreach($context['smileys'][$location] as $j => $row){
					$n = count($context['smileys'][$location][$j]['smileys']);
					for($i = 0; $i < $n; $i++){
						$context['smileys'][$location][$j]['smileys'][$i]['code'] = addslashes($context['smileys'][$location][$j]['smileys'][$i]['code']);
						$context['smileys'][$location][$j]['smileys'][$i]['js_description'] = addslashes($context['smileys'][$location][$j]['smileys'][$i]['description']);
					}
					$context['smileys'][$location][$j]['smileys'][$n - 1]['last'] = true;
				}
				if(!empty($context['smileys'][$location]))
					$context['smileys'][$location][count($context['smileys'][$location]) - 1]['last'] = true;
			}
	
		}
	
		$settings['smileys_url'] = $modSettings['smileys_url'] . '/' . $user_info['smiley_set'];
	
	}

	function printSmileys($a,$b){

		global $context, $txt, $settings, $boardurl;

		loadLanguage('Post');

		// Now start printing all of the smileys.
		if(!empty($context['smileys']['postform'])){
			// Show each row of smileys ;).
			foreach ($context['smileys']['postform'] as $smiley_row){
				foreach ($smiley_row['smileys'] as $smiley){
				$sm = $smiley['code'];

				echo "
					<a href=\"javascript:void(0);\" onclick=\"replaceText('{$sm}', document.forms.{$a}.{$b}); return false;\">";
				echo '
					<img src="', $settings['smileys_url'], '/', $smiley['filename'], '" align="bottom" alt="', $smiley['description'], '" title="', $smiley['description'], '" /></a>';}

				// If this isn't the last row, show a break.
				if (empty($smiley_row['last']))
					echo '<br />';
			}
		}
	
	}
	
		
	
	
	
	
	
	
	
	
	//whitespace nothing to see here..................
	//loadBBC()





	
	
// BBC basic functionality
function loadBBC(){
global $settings, $user_info, $sourcedir, $txt;

	echo '
	<a class="bbc" href="javascript:void(0);" onclick="surroundText(\'[b]\', \'[/b]\', document.forms.talkbox.shout); return false;"><img src="'.$settings['images_url'].'/bbc/bold.gif" alt="'.$txt['bold'].'" title="'.$txt['bold'].'" /></a>&nbsp;&nbsp;
	<a class="bbc" href="javascript:void(0);" onclick="surroundText(\'[i]\', \'[/i]\', document.forms.talkbox.shout); return false;"><img src="'.$settings['images_url'].'/bbc/italicize.gif" alt="'.$txt['italic'].'" title="'.$txt['italic'].'" /></a>&nbsp;&nbsp;
	<a class="bbc" href="javascript:void(0);" onclick="surroundText(\'[u]\', \'[/u]\', document.forms.talkbox.shout); return false;"><img src="'.$settings['images_url'].'/bbc/underline.gif" alt="'.$txt['underline'].'" title="'.$txt['underline'].'" /></a>&nbsp;&nbsp;
	<a class="bbc" href="javascript:void(0);" onclick="surroundText(\'[s]\', \'[/s]\', document.forms.talkbox.shout); return false;"><img src="'.$settings['images_url'].'/bbc/strike.gif" alt="'.$txt['strike'].'" title="'.$txt['strike'].'" /></a>&nbsp;&nbsp;
	<a class="bbc" href="javascript:void(0);" onclick="surroundText(\'[url]\', \'[/url]\', document.forms.talkbox.shout); return false;"><img src="'.$settings['images_url'].'/bbc/url.gif" alt="'.$txt['hyperlink'].'" title="'.$txt['hyperlink'].'" /></a>&nbsp;&nbsp;';

	// Font color options
	echo '
	<div class="select">
	 <select onchange="surroundText(\'[color=\'+this.options[this.selectedIndex].value+\']\', \'[/color]\', document.forms.talkbox.shout); this.selectedIndex = 0;" style="width:10em;height:1.9em;margin-top:-38px;">
	 <option value="" selected="selected">', $txt['change_color'], '</option>
	 <option value="black" style="color:black">', $txt['black'], '</option>
	 <option value="maroon" style="color:maroon">', $txt['maroon'], '</option>
	 <option value="red" style="color:red">', $txt['red'], '</option>
	 <option value="purple" style="color:purple">', $txt['purple'], '</option>
	 <option value="pink" style="color:fuchsia">', $txt['pink'], '</option>
	 <option value="green" style="color:green">', $txt['green'], '</option>
	 <option value="limegreen" style="color:limegreen">', $txt['lime_green'], '</option>
	 <option value="navy" style="color:navy">', $txt['navy'], '</option>
	 <option value="blue" style="color:blue">', $txt['blue'], '</option>
	 <option value="teal" style="color:teal">', $txt['teal'], '</option>
	 </select>';

	// Font face
	echo '
	 <select onchange="surroundText(\'[font=\'+this.options[this.selectedIndex].value+\']\', \'[/font]\', document.forms.talkbox.shout); this.selectedIndex = 0;" style="width:8em;height:1.9em;">
	 <option value="" selected="selected">'.$txt['font_face'].'</option>
	 <option value="Arial" style="font-family:arial">Arial</option>
	 <option value="Arial Black" style="font-family:arial black">Arial Black</option>
	 <option value="Comic Sans MS" style="font-family:comic sans ms">Comic Sans MS</option>
	 <option value="Courier New" style="font-family:courier new">Courier New</option>
	 <option value="Georgia" style="font-family:georgia">Georgia</option>
	 <option value="Times New Roman" style="font-family:times new roman">Times New Roman</option>
	 <option value="Tahoma" style="font-family:tahoma">Tahoma</option>
	 <option value="Verdana" style="font-family:verdana">Verdana</option>
	 <option value="Trebuchet MS" style="font-family:trebuchet ms">Trebuchet MS</option>
	 <option value="Impact" style="font-family:impact">Impact</option>
	 </select>';

	// Font size
	echo '
	 <select onchange="surroundText(\'[size=\'+this.options[this.selectedIndex].value+\']\', \'[/size]\', document.forms.talkbox.shout); this.selectedIndex = 0;" style="width:8em;height:1.9em;">
	 <option value="" selected="selected">'.$txt['font_size'].'</option>
	 <option value="10pt" style="font-size:8px">8pt</option>
	 <option value="12pt" style="font-size:10px">10pt</option>
	 <option value="14pt" style="font-size:12px">12pt</option>
	 <option value="16pt" style="font-size:14px">14pt</option>
	 </select>
	</div>';
}
	
	
		
	
	
	
	
	
	
	
	
	//whitespace nothing to see here..................
	
	
	
	
	
	
	
	
?>
