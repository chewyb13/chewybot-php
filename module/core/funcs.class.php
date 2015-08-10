<?php
class funcs extends ChewyBot {
	public static function getMicroTime() {
		return microtime(true);
	}

	protected function _cmd_rehash() {
		global $ch3wyb0t;
		$tempsql = "SELECT * FROM settings";
		$tempsets = $ch3wyb0t->_sql->sql('select',$tempsql);
		while ($row = $tempsets->fetchArray()) {
			$ch3wyb0t->data['settings'][$row['setting']] = $row['value'];
			//print_r($row);
		}
		$tempsql = "SELECT * FROM servers";
		$tempservers = $ch3wyb0t->_sql->sql('select',$tempsql);
		$tempsockcount = 1;
		while ($row = $tempservers->fetchArray()) {
			$ch3wyb0t->sdata['cons'][$tempsockcount]['id'] = $row['id'];
			$ch3wyb0t->data['data'][$row['id']]['server']['id'] = $row['id'];
			$ch3wyb0t->data['data'][$row['id']]['server']['servername'] = $row['servername'];
			$ch3wyb0t->data['data'][$row['id']]['server']['address'] = $row['address'];
			$ch3wyb0t->data['data'][$row['id']]['server']['serverport'] = $row['serverport'];
			$ch3wyb0t->data['data'][$row['id']]['server']['serverpass'] = $row['serverpass'];
			$ch3wyb0t->data['data'][$row['id']]['server']['nick'] = $row['nick'];
			$ch3wyb0t->data['data'][$row['id']]['server']['bnick'] = $row['bnick'];
			$ch3wyb0t->data['data'][$row['id']]['server']['nickservpass'] = $row['nickservpass'];
			$ch3wyb0t->data['data'][$row['id']]['server']['botoper'] = $row['botoper'];
			$ch3wyb0t->data['data'][$row['id']]['server']['botoperpass'] = $row['botoperpass'];
			$ch3wyb0t->data['data'][$row['id']]['server']['enabled'] = $row['enabled'];
			$ch3wyb0t->sdata['cons'][$tempsockcount]['connection']['address'] = $row['address'];
			$ch3wyb0t->sdata['cons'][$tempsockcount]['connection']['port'] = $row['serverport'];
			$ch3wyb0t->sdata['cons'][$tempsockcount]['enabled'] = $row['enabled'];
			//print_r($row);
			$tempsockcount += 1;
		}
		$tempsql = "SELECT * FROM channels";
		$tempchans = $ch3wyb0t->_sql->sql('select',$tempsql);
		while ($row = $tempchans->fetchArray()) {
			$ch3wyb0t->data['data'][$row['server']]['chans'][$row['channel']]['id'] = $row['id'];
			$ch3wyb0t->data['data'][$row['server']]['chans'][$row['channel']]['server'] = $row['server'];
			$ch3wyb0t->data['data'][$row['server']]['chans'][$row['channel']]['channel'] = $row['channel'];
			$ch3wyb0t->data['data'][$row['server']]['chans'][$row['channel']]['chanpass'] = $row['chanpass'];
			$ch3wyb0t->data['data'][$row['server']]['chans'][$row['channel']]['chanmodes'] = $row['chanmodes'];
			$ch3wyb0t->data['data'][$row['server']]['chans'][$row['channel']]['chantopic'] = $row['chantopic'];
			$ch3wyb0t->data['data'][$row['server']]['chans'][$row['channel']]['options'] = $row['options'];
			$ch3wyb0t->data['data'][$row['server']]['chans'][$row['channel']]['enabled'] = $row['enabled'];	
			//print_r($row);
		}
	}
	
	protected function _startup() {
		global $ch3wyb0t;
		$ch3wyb0t->sdata['timer']['data'] = array();
		$ch3wyb0t->sdata['timer']['last'] = time() - 10;
		foreach ($ch3wyb0t->sdata['cons'] as $t1 => $t2) {
			if ($t2['enabled'] == 'enabled') {
				$ch3wyb0t->sdata['cons'][$t2['id']]['loggedin'] = array();
				$ch3wyb0t->sdata['cons'][$t2['id']]['queue']['data'] = array();
				$ch3wyb0t->sdata['cons'][$t2['id']]['queue']['last'] = time() - 10;
				$ch3wyb0t->sdata['cons'][$t2['id']]['timer']['data'] = array();
				$ch3wyb0t->sdata['cons'][$t2['id']]['timer']['last'] = time() - 10;
				$ch3wyb0t->sdata['cons'][$t2['id']]['lastcmd'] = '';
				if ($ch3wyb0t->data['data'][$t2['id']]['server']['nickservpass'] != 'NULL') {
					$ch3wyb0t->sdata['cons'][$t2['id']]['identified'] = 0;
				} else {
					$ch3wyb0t->sdata['cons'][$t2['id']]['identified'] = 2;
				}
				$ch3wyb0t->sdata['cons'][$t2['id']]['lastping'] = time();
				$ch3wyb0t->sdata['cons'][$t2['id']]['nick'] = $ch3wyb0t->data['data'][$t2['id']]['server']['nick'];
				$this->_connect($t2['id']);
				$this->_run_queue($t2['id']);
			}
		}
	}

	protected function _array_stripchr($in,$chr) {
		global $ch3wyb0t;
		$stripcount = count($in);
		while ($stripcount) {
			$stripcount -= 1;
			$in[$stripcount] = str_replace(chr($chr),'',$in[$stripcount]);
		}
		return $in;
	}
	
	protected function _array_join($in,$o,$j) {
		global $ch3wyb0t;
		return implode($j,$this->_array_rearrange($in,$o));
	}
	
	protected function _array_rearrange($in,$o) {
		global $ch3wyb0t;
		$i = 0;
		$out = array();
		while ($o < count($in)) {
			$out[$i] = $in[$o];
			$o += 1;
			$i += 1;
		}
		return $out;
	}
	
	protected function _chanmodes($id,$chan) {
		global $ch3wyb0t;
		$foutput = '';
		foreach ($ch3wyb0t->data['data'][$id]['chans'] as $t1 => $t2) {
			if ($t2['channel'] == $chan) {
				if ($ch3wyb0t->data['data'][$id]['chans'][$chan]['chanmodes'] != 'NULL') {
					//$tmpimodes 
				
				
				
				
				} else {
					return 'NULL';
				}
			}
		}
		return 'NULL';
	
	/*
def chanmodes(sock,chan):
	channels = mysockets[sock]['chans'].keys()
	foutput = ''
	for channel in channels:
		if (channel == chan):
			if (mysockets[sock]['chans'][chan]['chanmodes'] != 'NULL'):
				tmpmodes = mysockets[sock]['channels'][chan]['modes'].keys()
				tmpimodes = mysockets[sock]['chans'][chan]['chanmodes']
				mydata = mysockets[sock]['channels'][chan]['users'][mysockets[sock]['nick']]
				isop = 'FALSE'
				try:
					if (mydata['FOP'] == 'TRUE'):
						isop = 'TRUE'
				except:
					isop = 'FALSE'
				try:
					if (mydata['SOP'] == 'TRUE'):
						isop = 'TRUE'
				except:
					isop = 'FALSE'
				try:
					if (mydata['OP'] == 'TRUE'):
						isop = 'TRUE'
				except:
					isop = 'FALSE'
				try:
					if (mysockets[sock]['isoper'] == 'TRUE'):
						isop = 'TRUE'
				except:
					isop = 'FALSE'
				if (isop == 'TRUE'):
					data = tmpimodes.split(' ')
					i = 0
					pos = 1
					output = ''
					output2 = ''
					mode = 'SUB'
					while (i < len(data[0])):
						if ((data[0][i] == '+') or (data[0][i] == '-') or (data[0][i] == '(') or (data[0][i] == ')')):
							if (data[0][i] == '+'): mode = 'ADD'
							else: mode = 'SUB'
							if ((data[0][i] == '+') or (data[0][i] == '-')):
								output = output+data[0][i]
						else:
							if (data[0][i] == 'l'): tmpmode = 'LIMIT'
							elif (data[0][i] == 'k'): tmpmode = 'CHANPASS'
							elif (data[0][i] == 'f'): tmpmode = 'FLOOD'
							elif (data[0][i] == 'j'): tmpmode = 'JOIN'
							elif (data[0][i] == 'L'): tmpmode = 'LINK'
							elif (data[0][i] == 'B'): tmpmode = 'BANLINK'	
							else: tmpmode = data[0][i]
							if (mode == 'ADD'):
								if ((tmpmode == 'LIMIT') or (tmpmode == 'LINK') or (tmpmode == 'BANLINK') or (tmpmode == 'CHANPASS') or (tmpmode == 'FLOOD') or (tmpmode == 'JOIN')):
									output = output+data[0][i]
									output2 = output2+data[pos]+' '
									pos = pos + 1
								else:
									output = output+tmpmode
							if (mode == 'SUB'):
								if ((tmpmode == 'LIMIT') or (tmpmode == 'LINK') or (tmpmode == 'BANLINK') or (tmpmode == 'CHANPASS') or (tmpmode == 'FLOOD') or (tmpmode == 'JOIN')):
									output = output+data[0][i]
									output2 = output2+data[pos]+' '
									pos = pos + 1
								else:
									output = output+tmpmode
						i = i + 1
					output = output.strip(' ')
					if (len(output) >= 2):
						output2 = output2.rstrip()
						foutput = output+' '+output2
						foutput = foutput.rstrip()
						return foutput
					else:
						return 'NULL'
				else:
					return 'NULL'
			else:
				return 'NULL'
	return 'NULL'				
	*/
	}
	
	protected function _massmodes($id,$user,$chan,$modes) {
		global $ch3wyb0t;
		//$this->_sprint("Modes ".print_r($modes,true),'debug',false);
		$modespl = $ch3wyb0t->sdata['cons'][$id]['modespl'];
		$tmpusers = array();
		if (count($ch3wyb0t->sdata['cons'][$id]['chans'][$chan]['users']) > 0) {
			//$this->_sprint("Users Listing: ".print_r($this->sdata['cons'][$id]['chans'][$chan]['users'],true),'debug',false);
			foreach ($ch3wyb0t->sdata['cons'][$id]['chans'][$chan]['users'] as $t1 => $t2) {
				//$this->_sprint("User Details: T1 ".$t1." T2 ".print_r($t2,true),'debug',false);
				if ($modes[2] == 'ALL') {
					array_push($tmpusers,$t1);
				}	elseif ($modes[2] == 'BC') {
					if ($modes[0] == 'ADD') {
						array_push($tmpusers,$t1);
					} else {
						if ($ch3wyb0t->_core_islogged($id,$t1) == false) {
							array_push($tmpusers,$t1);
						}
					}
				} else {
					$tmpmodes = explode(" ",$modes[2]);
					foreach ($tmpmodes as $tmpmode) {
						//$this->_sprint("TMPMODE: ".$tmpmode,'debug',false);
						if ($tmpmode == $t1) {
							array_push($tmpusers,$t1);
						}
					}
				}
				//$this->_sprint("TmpUsers: ".print_r($tmpusers,true),'debug',false);
			}
		}
		
		$i = 0;
		$outputmode = '';
		while ($i != $modespl) {
			$outputmode .= $modes[1];
			$i += 1;
		}
		//$this->_sprint($outputmode,'debug',false);
		if ($modes[0] == 'ADD') {
			$outmode = '+';
		} else {
			$outmode = '-';
		}
		$l = 0;
		$output = '';
		foreach ($tmpusers as $t1 => $t2) {
			//$this->_sprint("T1: ".$t1." T2: ".$t2,'debug',false);
			$output = $output.$t2." ";
			$l += 1;
			if ($l == $modespl) {
				//$this->_sprint($output,'debug',false);
				$this->_sts($id,"MODE ".$chan." ".$outmode.$outputmode." ".$output);
				$l = 0;
				$output = '';
			}
		}
		//$this->_sprint($output,'debug',false);
		$this->_sts($id,"MODE ".$chan." ".$outmode.$outputmode." ".$output);
	}	
	
	protected function _modeprocessor_chan($id,$user,$chan,$data) {
		global $ch3wyb0t;
		$i = 0;
		$pos = 1;
		$mode = 'SUB';
		while ($i < strlen($data[0])) {
			if (($data[0][$i] == '+') or ($data[0][$i] == '-') or ($data[0][$i] == '(') or ($data[0][$i] == ')')) {
				if ($data[0][$i] == '+') {
					$mode = 'ADD';
				} else {
					$mode = 'SUB';
				}
			} else {
				switch ($data[0][$i]) {
					case 'q': {
						$tmpmode = 'FOP';
						break;
					}
					case 'a': {
						$tmpmode = 'SOP';
						break;
					}
					case 'o': {
						$tmpmode = 'OP';
						break;
					}
					case 'h': {
						$tmpmode = 'HOP';
						break;
					}
					case 'v': {
						$tmpmode = 'VOICE';
						break;
					}
					case 'e': {
						$tmpmode = 'EXCEPT';
						break;
					}
					case 'I': {
						$tmpmode = 'INVEX';
						break;
					}
					case 'b': {
						$tmpmode = 'BAN';
						break;
					}
					case 'l': {
						$tmpmode = 'LIMIT';
						break;
					}
					case 'k': {
						$tmpmode = 'CHANPASS';
						break;
					}
					case 'f': {
						$tmpmode = 'FLOOD';
						break;
					}
					case 'j': {
						$tmpmode = 'JOIN';
						break;
					}
					case 'L': {
						$tmpmode = 'LINK';
						break;
					}
					case 'B': {
						$tmpmode = 'BANLINK';
						break;
					}
					default: {
						$tmpmode = $data[0][$i];
						break;
					}
				}
				if ($mode == 'ADD') {
					if (($tmpmode == 'FOP') or ($tmpmode == 'SOP') or ($tmpmode == 'OP') or ($tmpmode == 'HOP') or ($tmpmode == 'VOICE') or ($tmpmode == 'EXCEPT') or ($tmpmode == 'INVEX') or ($tmpmode == 'BAN') or ($tmpmode == 'LIMIT') or ($tmpmode == 'LINK') or ($tmpmode == 'BANLINK') or ($tmpmode == 'CHANPASS') or ($tmpmode == 'FLOOD') or ($tmpmode == 'JOIN')) {
						if (($tmpmode == 'FOP') or ($tmpmode == 'SOP') or ($tmpmode == 'OP') or ($tmpmode == 'HOP') or ($tmpmode == 'VOICE')) {
							$ch3wyb0t->sdata['cons'][$id]['chans'][$chan]['users'][$data[$pos]][$tmpmode] = true;
						} else {
							if (($tmpmode == 'BAN') or ($tmpmode == 'EXCEPT') or ($tmpmode == 'INVEX')) {
								$ch3wyb0t->sdata['cons'][$id]['chans'][$chan][$tmpmode][$data[$pos]] = true;
							} else {
								$ch3wyb0t->sdata['cons'][$id]['chans'][$chan][$tmpmode] = $data[$pos];
							}
						}
						$pos = $pos + 1;
					} else {
						$ch3wyb0t->sdata['cons'][$id]['chans'][$chan]['modes'][$tmpmode] = true;
					}
				}
				if ($mode == 'SUB') {
					if (($tmpmode == 'FOP') or ($tmpmode == 'SOP') or ($tmpmode == 'OP') or ($tmpmode == 'HOP') or ($tmpmode == 'VOICE') or ($tmpmode == 'EXCEPT') or ($tmpmode == 'INVEX') or ($tmpmode == 'BAN') or ($tmpmode == 'LIMIT') or ($tmpmode == 'LINK') or ($tmpmode == 'BANLINK') or ($tmpmode == 'CHANPASS') or ($tmpmode == 'FLOOD') or ($tmpmode == 'JOIN')) {
						if (($tmpmode == 'FOP') or ($tmpmode == 'SOP') or ($tmpmode == 'OP') or ($tmpmode == 'HOP') or ($tmpmode == 'VOICE')) {
							$ch3wyb0t->sdata['cons'][$id]['chans'][$chan]['users'][$data[$pos]][$tmpmode] = false;
						} else {
							if (($tmpmode == 'BAN') or ($tmpmode == 'EXCEPT') or ($tmpmode == 'INVEX')) {
								unset($ch3wyb0t->sdata['cons'][$id]['chans'][$chan][$tmpmode][$data[$pos]]);
							} else {
								unset($ch3wyb0t->sdata['cons'][$id]['chans'][$chan][$tmpmode]);
							}
						}
						$pos = $pos + 1;
					} else {
						$ch3wyb0t->sdata['cons'][$id]['chans'][$chan]['modes'][$tmpmode] = false;
					}
				}
			}
			$i = $i + 1;
		}
	}
	
	protected function _modeprocessor_user($id,$type,$data) {
		global $ch3wyb0t;
		$i = 0;
		$mode = 'SUB';
		while ($i < strlen($data)) {
			#debug(sock,incom[3][i])
			if (($data[$i] == '+') or ($data[$i] == '-') or ($data[$i] == '(') or ($data[$i] == ')')) {
				if ($data[$i] == '+') { 
					$mode = 'ADD';
				} else {
					$mode = 'SUB';
				}
			} else {
				if ($mode == 'ADD') {
					$ch3wyb0t->sdata['cons'][$id][$type][$data[$i]] = true;
				}
				if ($mode == 'SUB') {
					$ch3wyb0t->sdata['cons'][$id][$type][$data[$i]] = false;
				}
			}
			$i = $i + 1;
		}
	
	}
	
	protected function _get_whois($id,$user,$chan,$mode,$otheruser='NULL') {
		global $ch3wyb0t;
		if ($otheruser == 'NULL') {
			if ($this->_islogged($id,$user) == true) {
				$userdata = $this->_pulluser($this->sdata['cons'][$id]['loggedin'][$user]['username']);
			} else {
				$userdata = $this->_pulluser($user);
			}
			$tmpuinfo = $user;
		} else {
			if ($this->_islogged($id,$otheruser) == true) {
				$userdata = $this->_pulluser($this->sdata['cons'][$id]['loggedin'][$otheruser]['username']);
			} else {
				$userdata = $this->_pulluser($otheruser);
			}
			$tmpuinfo = $otheruser;
		}
		if ($userdata == false) {
			$tmpusername = "GUEST";
			$tmpglobaccess = 0;
			$tmpservaccess = 0;
			$tmpchanaccess = 0;
			$tmpoverallaccess = 0;
			$tmpmsgtype = "msg";
		} else {
			$tmpusername = $userdata['username'];
			$tmpglobaccess = $this->_get_access_global($userdata);
			$tmpservaccess = $this->_get_access_server($id,$userdata);
			$tmpchanaccess = $this->_get_access_channel($id,$chan,$userdata);
			$tmpoverallaccess = $this->_get_access($id,$tmpusername,$chan,'CHANNEL');
			$tmpmsgtype = $userdata['msgtype'];
		}
		if ($mode == 'WHOIS') {
			$this->_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"Your Bot Whois on ".$tmpuinfo);
			$this->_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"Nick(Username): ".$tmpuinfo." (".$tmpusername.")");
			$this->_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"Global: ".$this->_wordaccess($tmpglobaccess)." Server: ".$this->_wordaccess($tmpservaccess)." Channel: ".$this->_wordaccess($tmpchanaccess));
			$this->_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"Over-All Access in ".$chan." is ".$this->_wordaccess($tmpoverallaccess));
			$this->_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"End of Your Bot Whois on ".$tmpuinfo);
		}
		if ($mode == 'WHOAMI') {
			$this->_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"Your Bot Whois");
			$this->_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"Nick(Username): ".$tmpuinfo." (".$tmpusername.")");
			$this->_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"Your Global Access: ".$this->_wordaccess($tmpglobaccess));
			$this->_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"Your Server Access: ".$this->_wordaccess($tmpservaccess));
			$this->_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"Your Channel Access: ".$this->_wordaccess($tmpchanaccess));
			$this->_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"Over-All Access in ".$chan." is ".$this->_wordaccess($tmpoverallaccess));
			$this->_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"Your Current MsgType: ".$tmpmsgtype);
			$this->_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"End of Your Bot Whois");
		}
	}
	
	protected function _buildmsg($id,$type,$user,$chan,$uctype,$message) {
		global $ch3wyb0t;
		#sock = server($1) type = messagetype(4) uctype = priv/chan($2) user/chan = sendto($3) message = message($5-)
		if ($uctype == 'PRIV') {
			$sendto = $user;
			if ($this->_islogged($id,$user) == true) {
				$userdata = $this->_pulluser($this->sdata['cons'][$id]['loggedin'][$user]['username']);
				$msgtype = $userdata['msgtype'];
			} else {
				$msgtype = 'msg';
			}
		} else {
			$sendto = $chan;
			$msgtype = 'msg';
		}
		if ($msgtype == 'msg') { $msgoutput = "PRIVMSG"; }
		if ($msgtype == 'notice') { $msgoutput = "NOTICE"; }
		switch ($type) {
			case 'RAW': {
				$mtoutput = "-(RAW)-";
				break;
			}
			case 'BLOG': {
				$mtoutput = "-(CBOT)-(LOG)-";
				break;
			}
			case 'ELOG': {
				$mtoutput = "-(CBOT)-(ERROR-LOG)-";
				break;
			}
			case 'RELAY': {
				$mtoutput = "*";
				break;
			}
			case 'NORMAL': {
				$mtoutput = "-(CBOT)-";
				break;
			}
			case 'HELP': {
				$mtoutput = "-(CBOT)-(HELP)-";
				break;
			}
			case 'ERROR': {
				$mtoutput = "-(CBOT)-(ERROR)-";
				switch($message) {
					case 'LOGIN': {
						$message = "You are already Logged in.";
						break;
					}
					case 'PASSPROB': {
						$message = "There was a problem with changing your password";
						break;
					}
					case 'LOGGED': {
						$message = "You are Logged in.";
						break;
					}
					case 'NOTLOGGED': {
						$message = "You are not Logged in.";
						break;
					}
					case 'NOACCESS': {
						$message = "You either have no access to this command or you are not Logged in.";
						break;
					}
					case 'NOACCESSHELP': {
						$message = "You do not have access to read help on this command.";
						break;
					}
					default: {
						$message = $message;
						break;
					}
				}
				break;
			}
			default: {
				$mtoutput = "-(CBOT)-";
				break;
			}
		}
		$this->_sts($id,$msgoutput." ".$sendto." :".chr(3)."4,1".$mtoutput."".chr(3)." ".$message);	
	}
	
	protected function _core_get_access($id,$user,$chan,$type) {
		global $ch3wyb0t;
		$udata = $this->_pulluser($user);
		if ($udata == false) {
			$return = 0;
		} else {
			$tmpglobaccess = $this->_get_access_global($udata);
			$tmpservaccess = $this->_get_access_server($id,$udata);
			$tmpchanaccess = $this->_get_access_channel($id,$chan,$udata);
			if ($type == 'GLOBAL') {
				$return = $tmpglobaccess;
			}
			if ($type == 'SERVER') {
				$return = $tmpglobaccess;
				$tmpaccess = $tmpglobaccess;
				if ($tmpaccess < $tmpservaccess) {
					$return = $tmpservaccess;
				}
			}
			if ($type == 'CHANNEL') {
				$return = $tmpglobaccess;
				$tmpaccess = $tmpglobaccess;
				if ($tmpaccess < $tmpservaccess) {
					$return = $tmpservaccess;
					$tmpaccess = $tmpservaccess;
				}
				if ($tmpaccess < $tmpchanaccess) {
					$return = $tmpchanaccess;
				}
			}
		}
		return $return;
	}
	
	protected function _get_access_channel($id,$chan,$udata) {
		global $ch3wyb0t;
		#Channel ServerName|ChannelName~Access|ChannelName~Access%ServerName|ChannelName~Access|ChannelName~Access
		if ($udata['channel'] != 'NULL') {
			$tmpdata = $udata['channel'];
			$tmpdata = explode(chr(37),$tmpdata);
			foreach ($tmpdata as $t1 => $t2) {
				$t2 = explode(chr(124),$t2);
				if ($t2[0] == $ch3wyb0t->data['data'][$id]['server']['servername']) {
					foreach ($t2 as $t3 => $t4) {
						$t4 = explode(chr(126),$t4);
						if ($t4[0] == $chan) {
							$return = $t4[1];
						}					
					}
				}
			}
		} else {
			$return = 0;
		}
		return $return;
	}

	protected function _get_access_server($id,$udata) {
		global $ch3wyb0t;
		#Server ServerName~Access%ServerName~Access
		if ($udata['server'] != 'NULL') {
			$tempdata = $udata['server'];
			$tempdata = explode(chr(37),$tempdata);
			foreach ($tempdata as $t1 => $t2) {
				$t2 = explode(chr(126),$t2);
				if ($t2[0] = $ch3wyb0t->data['data'][$id]['server']['servername']) {
					$return = $t2[1];
				}
			}
		} else {
			$return = 0;
		}
		return $return;
	}
	
	protected function _get_access_global($udata) {
		global $ch3wyb0t;
		#Global Straight access numbers
		if ($udata['global'] != 'NULL') {
			$return = $udata['global'];
		} else {
			$return = 0;
		}
		return $return;
	}
	
	protected function _get_access_logged($id,$user,$chan,$type) {
		global $ch3wyb0t;
		if ($this->_islogged($id,$user) == true) {
			$return = $this->_get_access($id,$ch3wyb0t->sdata['cons'][$id]['loggedin'][$user]['username'],$chan,$type);
		} else {
			$return = 0;
		}
		return $return;
	}
	
	protected function _islogged($id,$user) {
		global $ch3wyb0t;
		$return = false;
		if (count($ch3wyb0t->sdata['cons'][$id]['loggedin']) > 0) {
			foreach ($ch3wyb0t->sdata['cons'][$id]['loggedin'] as $t1 => $t2) {
				if ($t1 == $user) {
					$return = true;
				} else {
					$return = false;
				}			
			}
		}
		return $return;
	}
	
	protected function _pulluser($user) {
		global $ch3wyb0t;
		$return = false;
		$user = strtolower($user);
		$tempsql = "SELECT * FROM users WHERE username = '".$user."'";
		$tempudata = $ch3wyb0t->_sql->sql('select',$tempsql);
		$tempuser = $tempudata->fetchArray();
		if (count($tempuser) == 0) {
			$return = false;
		} else {
			$return = ['id'=>$tempuser['id'],'username'=>$tempuser['username'],'password'=>$tempuser['password'],'global'=>$tempuser['global'],'server'=>$tempuser['server'],'channel'=>$tempuser['channel'],'msgtype'=>$tempuser['msgtype']];
			$ch3wyb0t->data['user'][$tempuser['username']] = ['id'=>$tempuser['id'],'username'=>$tempuser['username'],'password'=>$tempuser['password'],'global'=>$tempuser['global'],'server'=>$tempuser['server'],'channel'=>$tempuser['channel'],'msgtype'=>$tempuser['msgtype']];
		}
		return $return;
	}
	
	protected function _wordaccess($access) {
		global $ch3wyb0t;
		switch ($access) {
			case '7': {
				$return = "Creator(7)";
				break;
			}
			case '6': {
				$return = "Master(6)";
				break;
			}
			case '5': {
				$return = "Owner(5)";
				break;
			}
			case '4': {
				$return = "Protected(4)";
				break;
			}
			case '3': {
				$return = "OP(3)";
				break;
			}
			case '2': {
				$return = "Half-Op(2)";
				break;
			}
			case '1': {
				$return = "Voice(1)";
				break;
			}
			default: {
				$return = "No Access(0)";
				break;
			}
		}
		return $return;
	}

	protected function _joinchan($id,$chan) {
		global $ch3wyb0t;
		foreach ($ch3wyb0t->data['data'][$id]['chans'] as $t1 => $t2) {
			if ($t2['channel'] == $chan) {
				if ($t2['chanpass'] == 'NULL') {
					$this->_sts($id,"JOIN :".$chan);
				} else {
					$this->_sts($id,"JOIN :".$chan." ".$t2['chanpass']);
				}
			}		
		}
		return;
	}
	
	protected function _checkchan($id,$chan) {
		global $ch3wyb0t;
		$return = false;
		foreach ($ch3wyb0t->data['data'][$id]['chans'] as $t1 => $t2) {
			if ($t2['channel'] == $chan) {
				if ($t2['enabled'] == 'enabled') {
					$return = true;
				} else {
					$return = false;
				}
			}
		}
		return $return;
	}
	
	protected function _autojoinchans($id) {
		global $ch3wyb0t;
		foreach ($ch3wyb0t->data['data'][$id]['chans'] as $t1 => $t2) {
			if ($this->_checkchan($id,$t2['channel']) == true) {
				$this->_joinchan($id,$t2['channel']);
			}
		}
		return;
	}
	
	protected function _operupcheck($id) {
		global $ch3wyb0t;
		if ($ch3wyb0t->data['data'][$id]['server']['botoper'] != 'NULL') {
			$this->_sts($id,"OPER ".$ch3wyb0t->data['data'][$id]['server']['botoper']." ".$ch3wyb0t->data['data'][$id]['server']['botoperpass']);
			$ch3wyb0t->sdata['cons'][$id]['isoper'] = 1;
		}
	}
	
	protected function _sts($id,$data) {
		global $ch3wyb0t;
		array_push($ch3wyb0t->sdata['cons'][$id]['queue']['data'],$data."\n\r");
		return;
	}
	
	protected function _send($id,$data) {
		global $ch3wyb0t;
		@socket_write($ch3wyb0t->sdata['cons'][$id]['socket'],$data);
		/*if (socket_last_error($this->sdata['cons'][$id]['socket'])) {
			$this->_sprint("Couldn't send data to ".$this->data['data'][$id]['server']['servername']." Error String: ".socket_strerror(socket_last_error($this->sdata['cons'][$id]['socket'])),'error',false);
			socket_clear_error($this->sdata['cons'][$id]['socket']);
		}*/
		return;
	}
	
	protected function _run_timer($id) {
		global $ch3wyb0t;
		$ch3wyb0t->sdata['cons'][$id]['timer']['last'] = time();
		return;
	}
	
	protected function _run_globtimer() {
		global $ch3wyb0t;
		$ch3wyb0t->sdata['timer']['last'] = time();
		return;
	}
	
	protected function _run_queue($id) {
		global $ch3wyb0t;
		$tempnow = time();
		$i = 0;
		$queuelimit = $ch3wyb0t->data['settings']['msgqueue'];
		$msginterval = $ch3wyb0t->data['settings']['msginterval'];
		if (($ch3wyb0t->sdata['cons'][$id]['queue']['last'] + $msginterval) < $tempnow) {
			while ($i != $queuelimit) {
				if (count($ch3wyb0t->sdata['cons'][$id]['queue']['data']) != 0) {
					$tempdata = array_shift($ch3wyb0t->sdata['cons'][$id]['queue']['data']);
					//datasend
					$this->_send($id,$tempdata);
					$ch3wyb0t->_log->_screen($id,'out',trim($tempdata));
					$i += 1;
				} else {
					$i = $queuelimit;
				}
				$ch3wyb0t->sdata['cons'][$id]['queue']['last'] = time();
			}
		}
	}

	protected function _connect($id) {
		global $ch3wyb0t;
		global $CORE;
		$ch3wyb0t->_log->_sprint("Attempting to connect to ".$ch3wyb0t->data['data'][$id]['server']['servername'],'debug',false);
		$ch3wyb0t->sdata['cons'][$id]['lastcmd'] = '';
		if ($ch3wyb0t->data['data'][$id]['server']['nickservpass'] != 'NULL') {
			$ch3wyb0t->sdata['cons'][$id]['identified'] = 0;
		} else {
			$ch3wyb0t->sdata['cons'][$id]['identified'] = 2;
		}
		$ch3wyb0t->sdata['cons'][$id]['isoper'] = false;
		$ch3wyb0t->sdata['cons'][$id]['lastping'] = time();
		$ch3wyb0t->sdata['cons'][$id]['socket'] = @socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
		if (socket_last_error($ch3wyb0t->sdata['cons'][$id]['socket'])) {
			$ch3wyb0t->_log->_sprint("Couldn't create socket for ".$ch3wyb0t->data['data'][$id]['server']['servername']." Error String: ".socket_strerror(socket_last_error($ch3wyb0t->sdata['cons'][$id]['socket'])),'error',false);
			socket_clear_error($ch3wyb0t->sdata['cons'][$id]['socket']);
		}
		if ($CORE['conf']['bindip'] == true) {
			socket_bind($ch3wyb0t->sdata['cons'][$id]['socket'],$CORE['conf']['bindedip']);
			if (socket_last_error($ch3wyb0t->sdata['cons'][$id]['socket'])) {
				$ch3wyb0t->_log->_sprint("Couldn't bind socket for ".$ch3wyb0t->data['data'][$id]['server']['servername']." Error String: ".socket_strerror(socket_last_error($ch3wyb0t->sdata['cons'][$id]['socket'])),'error',false);
				socket_clear_error($ch3wyb0t->sdata['cons'][$id]['socket']);
			}
		}
		@socket_connect($ch3wyb0t->sdata['cons'][$id]['socket'],$ch3wyb0t->sdata['cons'][$id]['connection']['address'],$ch3wyb0t->sdata['cons'][$id]['connection']['port']);
		if (socket_last_error($ch3wyb0t->sdata['cons'][$id]['socket'])) {
			$ch3wyb0t->_log->_sprint("Couldn't connect to ".$ch3wyb0t->data['data'][$id]['server']['servername']." Error String: ".socket_strerror(socket_last_error($ch3wyb0t->sdata['cons'][$id]['socket'])),'error',false);
			socket_clear_error($ch3wyb0t->sdata['cons'][$id]['socket']);
		}
		socket_set_nonblock($ch3wyb0t->sdata['cons'][$id]['socket']);
		//timeout setting here
		$this->_sts($id,"CAP LS");
		
		if ($ch3wyb0t->data['data'][$id]['server']['serverpass'] != 'NULL') {
			$this->_sts($id,"PASS ".$ch3wyb0t->data['data'][$id]['server']['serverpass']);
		}
		$this->_sts($id,"NICK ".$ch3wyb0t->sdata['cons'][$id]['nick']);
		$this->_sts($id,"USER ".$ch3wyb0t->data['settings']['botname']." 0 ".$ch3wyb0t->data['data'][$id]['server']['address']." :Ch3wyB0t Version ".VERSION_MAJOR.".".VERSION_MINOR.".".VERSION_REVISION.".".VERSION_BUILD);
		//$this->_sts($id,"CAP REQ :userhost-in-names");
		$this->_sts($id,"CAP END");
		return;
	}
	
	protected function _main_process() {
		global $ch3wyb0t;
		while (true) {
			$numsocks = 0;
			$numdisabled = 0;
			if (count($ch3wyb0t->sdata['timer']['data']) != 0) { $this->_run_globtimer(); }
			foreach ($ch3wyb0t->sdata['cons'] as $t1 => $t2) {
				$numsocks += 1;
				if ($t2['enabled'] == 'enabled') {
					$tempnow = time();
					if (($ch3wyb0t->sdata['cons'][$t2['id']]['lastping'] + 600) < $tempnow) {
						socket_close($ch3wyb0t->sdata['cons'][$t2['id']]['socket']);
						$this->_connect($t2['id']);
					}
					if (count($ch3wyb0t->sdata['cons'][$t2['id']]['queue']['data']) != 0) { $this->_run_queue($t2['id']); }
					if (count($ch3wyb0t->sdata['cons'][$t2['id']]['timer']['data']) != 0) { $this->_run_timer($t2['id']); }
					//$this->_sprint("Before data read",'debug',false);
					$tempdata = @socket_read($ch3wyb0t->sdata['cons'][$t2['id']]['socket'],10240);
					//$this->_sprint("After data read",'debug',false);
					if (strlen($tempdata) >= 1) {
						$tempdata = str_replace("\r","",$tempdata);
						//$this->_sprint(print_r($tempdata),'debug',false);
						$tempdata = explode("\n",$tempdata);
						//$this->_sprint(print_r($tempdata),'debug',false);
						foreach ($tempdata as $t3 => $t4) {
							if ($t4 != '') {
								$ch3wyb0t->_core_parse_data($t2['id'],$t4);
							}
						}
					}
				} else {
					$numdisabled += 1;
				}
				//$this->_sprint($numsocks,'debug',false);
			}
			//$this->_sprint("before sleep",'debug',false);
			if ($numsocks == $numdisabled) { break; }
			sleep(1); //0.5
			//$this->_sprint("after sleep",'debug',false);
		}
	}

}
?>