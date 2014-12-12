<?php
//You shouldn't have to ever touch this file, unless you want to help with production of the bot
class ChewyBot {
	//Variables for within the bot
	/*public $db = false;
	public $sql = false;
	public $log = false;
	public $data = false;
	public $sdata = false;*/
	
	private function _loadCoreFiles() {
		include ('./module/core/logging.class.php');
		include ('./module/core/sql.class.php');
		include ('./module/core/funcs.class.php');
		include ('./module/core/timers.class.php');
		include ('./module/core/queue.class.php');
		include ('./module/core/error.class.php');
		$this->funcs = new funcs();
		$this->log = new log("Init of Logging system for main",null,false);
		$this->sql = new sql('init',null);
		$log = $this->log;
		$sql = $this->sql;
		$funcs = $this->funcs;
	}	
	
	public function initsetup() {
		global $CORE;
		$this->_check();
		$this->_loadCoreFiles();
		
		
		if (file_exists($CORE['conf']['db'])) {
			$this->_sprint("Database exists, gonna check database structure",'regular',false);
			$this->sql->sql('database_check_structure',null);
		} else {
			$this->_sprint("Database missing, gotta regenerate the database",'error',false);
			$this->sql->sql('database_build_database',null);
		}
		//$this->_db_Builddatabase();
	}
	
	private function _check() {
		/*
		* This function is used to forcibly unset all unnecessary variables
		* (including php variables) that are not in the configuration.
		* This prevents users from executing random variable code if the
		* variable happens to exist.
		* THIS CLEARS $_GET, $_POST, $_SERVER, $_ENV VARIABLES SO IF THEY
		* ARE NEEDED DO NOT CALL THIS FUNCTION.
		*/
/*		if ($GLOBAL)
		{
			foreach($GLOBAL as $key => $glob)
			{
				if ($GLOBAL[$key] != 'CORE')
				{
					unset($GLOBAL[$key],${$glob});
				}
			}
		}*/
		if (ini_get('max_execution_time') > 0) {
			set_time_limit(0);
		}
	}
	

	
	private function _core_command_cmds($id,$type,$user,$indata,$rawdata,$address) {
		global $CORE;
		if (($type == 'CNOTE') or ($type == 'CMSG')) {
			$chan = $indata[2];
		} 
		else {
			$chan = 'NULL';
		}
		if (count($indata) >= 4) {
			#$this->_sprint($id." Entered Private Messages",'debug',false);
			if (($type == 'PNOTE') and ($indata[0] == $this->sdata['cons'][$id]['connectaddress'])) {
				#$this->_sprint($id." SNOTICE: ".print_r($indata,true),'debug',false);
				$blarg = true;
			} 
			elseif (strstr($indata[3],chr(1))) { // '\x01'
				$ctcp = $this->_core_array_rearrange($indata,3);
				$ctcp = $this->_core_array_stripchr($ctcp,1);
				if ($ctcp[0] == 'ACTION') {
					$this->_sprint($id." Got a Action ".print_r($this->_core_array_rearrange($ctcp,1),true),'debug',false);
				} 
				elseif ($ctcp[0] == 'VERSION') {
					if (count($ctcp) >= 2) {
						$this->_sprint($id." Got a CTCP VERSION Response ".print_r($this->_core_array_rearrange($ctcp,1),true),'debug',false);
					} 
					else {
						$this->_core_sts($id,"NOTICE ".$user." :".chr(1)."VERSION Ch3wyB0t Version ".$CORE['info']['version'].chr(1));
					}
				} 
				elseif ($ctcp[0] == 'PING') {
					if (count($ctcp) >= 2) {
						$this->_core_sts($id,"NOTICE ".$user." :".chr(1)."PING ".$ctcp[1].chr(1));
					} 
					else {
						$this->_core_sts($id,"NOTICE ".$user." :".chr(1)."PING".chr(1));
					}
				} 
				elseif ($ctcp[0] == 'TIME') {
					if (count($ctcp) >= 2) {
						$this->_sprint($id." Got a CTCP TIME Response ".print_r($this->_core_array_rearrange($ctcp,1),true),'debug',false);
					} 
					else {
						$this->_core_sts($id,"NOTICE ".$user." :".chr(1)."TIME ".date("D M d h:i:sA Y").chr(1));
					}
				} 
				else {
					$this->_sprint($id." Got a Unknown CTCP request ".print_r($this->_core_array_rearrange($ctcp,1),true),'debug',false);
				}
			} 
			elseif ($indata[3] == '?trigger') {
				$this->_core_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"Channel :".$this->data['settings']['chancom'].$this->data['settings']['signal']." Private Message: ".$this->data['settings']['pvtcom'].$this->data['settings']['signal']);
			} 
			elseif ((($indata[3] == $this->data['settings']['chancom'].$this->data['settings']['signal']) and (($type == 'CMSG') or ($type == 'CNOTE'))) or (($indata[3] == $this->data['settings']['pvtcom'].$this->data['settings']['signal']) and (($type == 'PMSG') or ($type == 'PNOTE')))) {
				if (count($indata) >= 5) {
					switch(strtoupper($indata[4])) {
						case 'USERLIST': {
							if ($this->_core_get_access_logged($id,$user,$chan,'SERVER') >= 4) {
								$records = $this->sql->sql('select',"SELECT * FROM users");
								$this->_core_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"Displaying user list, only showing Usernames atm, do note may be a big ammount of information");
								while ($row = $records->fetchArray()) {
									$this->_core_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"Username: ".$row['username']);
									//$this->_sprint(print_r($row),true);
								}
							} else {
								$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'EXIT': {
							if ($this->_core_get_access_logged($id,$user,$chan,'GLOBAL') >= 6) {
								if (count($indata) >= 6) {
									$tmpoutput = $this->_core_array_join($indata,5," ");
									$quitmsg = "QUIT ".$tmpoutput;
								} else { 
									$quitmsg = "QUIT Ch3wyB0t Version ".$CORE['info']['version']." Quitting";
								}
								foreach ($this->sdata['cons'] as $t1 => $t2) {
									if ($t2['enabled'] == 'enabled') {
										$this->sdata['cons'][$t2['id']]['lastcmd'] = 'EXIT';
										$this->_core_sts($t2['id'],$quitmsg);
									}
								}
							} else {
								$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'RELOAD': {
							if ($this->_core_get_access_logged($id,$user,$chan,'GLOBAL') >= 6) {
								foreach ($this->sdata['cons'] as $t1 => $t2) {
									if ($t2['enabled'] == 'enabled') {
										$this->sdata['cons'][$t2['id']]['lastcmd'] = 'RELOAD';
										$this->_core_sts($t2['id'],"QUIT Ch3wyB0t Version ".$CORE['info']['version']." Reloading");
									}
								}
							} else {
								$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'RAW': {
							if ($this->_core_get_access_logged($id,$user,$chan,'GLOBAL') >= 7) {
								if (count($indata) >= 6) {
									$tmpoutput = $this->_core_array_join($rawdata,5," ");
									$this->_core_sts($id,$tmpoutput);
									$this->_core_buildmsg($id,'RAW',$user,$chan,'PRIV',"Sent ".$tmpoutput." to Server");
								} else {
									$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter what you want to send to the bot");
								}
							} else {
								$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'RAWDB': {
							if ($this->_core_get_access_logged($id,$user,$chan,'GLOBAL') >= 7) {
								if (count($indata) >= 6) {
									$tmpoutput = $this->_core_array_join($rawdata,5," ");
									//$this->sql->sql('execute',$tmpoutput);
									$this->_core_buildmsg($id,'RAW',$user,$chan,'PRIV',"Sent ".$tmpoutput." to the database");
								} else {
									$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter what you want to send to the database");
								}
							} else {
								$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'QUIT': {
							if ($this->_core_get_access_logged($id,$user,$chan,'SERVER') >= 6) {
								$this->sdata['cons'][$id]['lastcmd'] = 'QUIT';
								if (count($indata) >= 6) {
									$this->_core_sts($id,"QUIT ".$this->_core_array_join($indata,5," "));
								} else {
									$this->_core_sts($id,"QUIT Ch3wyB0t Version ".$CORE['info']['version']." Quitting");
								}
							} else {
								$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'REHASH': {
							if ($this->_core_get_access_logged($id,$user,$chan,'GLOBAL') >= 6) {
								$this->_core_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"Rehashing...");
								$this->_core_cmd_rehash();
								$this->_core_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"Rehashing Complete...");
							} else {
								$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'SETTINGS': {
							if (($type == 'PMSG') or ($type == 'PNOTE')) {
								if ($this->_core_get_access_logged($id,$user,$chan,'GLOBAL') >= 6) {
									if (count($indata) >= 6) {
										switch(strtoupper($indata[5])) {
											case 'SET': {
												if (count($indata) >= 7) {
													if (count($indata) >= 8) {
														$this->sql->sql('update',"UPDATE settings SET setting = '".strtolower($indata[6])."', value = '".$indata[7]."' where setting = '".strtolower($indata[6])."'");
														$this->data['settings'][strtolower($indata[6])] = $indata[7];
														$this->_core_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"You have successfully changed ".strtolower($indata[6])." to ".$indata[7]);
													} else {
														$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"Missing Setting Value");
													}
												} else {
													$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"Missing Setting Name");
												}
												break;
											} 
											case 'LIST': {
												$records = $this->sql->sql('select',"SELECT * FROM settings");
												while ($row = $records->fetchArray()) {
													$this->_core_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"Setting: ".$row['setting']." Value: ".$row['value']);
												}
												break;
											}
											default: {
												$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"Error, Use Either Set or List");
												break;
											}
										}
									} else {
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"Error, Use Either Set or List");
									}
								} else {
									$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
								}
							} else {
								$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You can not access settings via channel commands");
							}
							break;
						}
						case 'SERVER': {
							if (($type == 'PMSG') or ($type == 'PNOTE')) {
								if ($this->_core_get_access_logged($id,$user,$chan,'GLOBAL') >= 6) {
									if (count($indata) >= 6) {
										switch(strtoupper($indata[5])) {
											case 'ADD': {
												
												break;
											}
											case 'CHG': {
												/*
												blarg = 1
												#if (len(incom) >= 7):
													#if (len(incom) >= 8):
														#sql = "UPDATE settings SET setting = '{0}', value = '{1}' WHERE setting = '{0}'".format(rl(incom[6]),incom[7])
														#vals = db.execute(sql)
														#settings[rl(incom[6])] = incom[7]
														#buildmsg(sock,'NORMAL',user,chan,'PRIV',"You have successfully changed {0} to {1}".format(rl(incom[6]),incom[7]))
													#else:
														#buildmsg(sock,'ERROR',user,chan,'PRIV',"Missing Setting Value")
												#else:
													#buildmsg(sock,'ERROR',user,chan,'PRIV',"Missing Setting Name")
													*/
											} 
											case 'LIST': {
												$records = $this->sql->sql('select',"SELECT * FROM servers");
												while ($row = $records->fetchArray()) {
													if ($row['enabled'] == 'enabled') {
														$this->_core_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"\x033SID: ".$row['id']." Server: ".$row['servername']." Address: ".$row['address']." Port: ".$row['serverport']." SPass: ".$row['serverpass']." Nick: ".$row['nick']." BNick: ".$row['bnick']." NSPass: ".$row['nickservpass']." BotOper: ".$row['botoper']." BotOperPass: ".$row['botoperpass']."\x03");
													} else {
														$this->_core_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"\x034SID: ".$row['id']." Server: ".$row['servername']." Address: ".$row['address']." Port: ".$row['serverport']." SPass: ".$row['serverpass']." Nick: ".$row['nick']." BNick: ".$row['bnick']." NSPass: ".$row['nickservpass']." BotOper: ".$row['botoper']." BotOperPass: ".$row['botoperpass']."\x03");
													}
												}
												$this->_core_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"Color \x033Green\x03 is enabled, Color \x034Red\x03 is disabled");
												break;
											}
											default: {
												$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"Error, Use Either List, Add, or Chg");
												break;
											}
										}
									} else {
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"Error, Use Either List, Add, or Chg");
									}
								} else {
									$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
								}
							} else {
								$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You can not access server via channel commands");
							}
							break;
						}
						case 'CHANNEL': {
							if (($type == 'PMSG') or ($type == 'PNOTE')) {
								if ($this->_core_get_access_logged($id,$user,$chan,'GLOBAL') >= 6) {
									if (count($indata) >= 6) {
										switch(strtoupper($indata[5])) {
											case 'ADD': {
												
												break;
											}
											case 'CHG': {
												/*
												if (len(incom) >= 7):
													if (len(incom) >= 8):
														if (incom[7].upper() == 'SERVER'):
															blarg = 1
														elif (incom[7].upper() == 'CHANNEL'):
															blarg = 1
														elif (incom[7].upper() == 'CHANPASS'):
															blarg = 1
														elif (incom[7].upper() == 'CHANMODES'):
															blarg = 1
														elif (incom[7].upper() == 'CHANTOPIC'):
															blarg = 1
														elif (incom[7].upper() == 'OPTIONS'):
															blarg = 1
														elif (incom[7].upper() == 'ENABLED'):
															blarg = 1
														else:
															buildmsg(sock,'ERROR',user,chan,'PRIV',"Error, You must choose from Server, Channel, Chanpass, Chanmodes, Chantopic, Options, Enabled")
													else:
														buildmsg(sock,'ERROR',user,chan,'PRIV',"Error, You must choose from Server, Channel, Chanpass, Chanmodes, Chantopic, Options, Enabled")
												else:
													buildmsg(sock,'ERROR',user,chan,'PRIV',"Error, Missing CID number, please check channel list again")
												#if (len(incom) >= 7):
													#if (len(incom) >= 8):
														#sql = "UPDATE settings SET setting = '{0}', value = '{1}' WHERE setting = '{0}'".format(rl(incom[6]),incom[7])
														#vals = db.execute(sql)
														#settings[rl(incom[6])] = incom[7]
														#buildmsg(sock,'NORMAL',user,chan,'PRIV',"You have successfully changed {0} to {1}".format(rl(incom[6]),incom[7]))
													#else:
														#buildmsg(sock,'ERROR',user,chan,'PRIV',"Missing Setting Value")
												#else:
													#buildmsg(sock,'ERROR',user,chan,'PRIV',"Missing Setting Name")
													*/
											} 
											case 'LIST': {
												$records = $this->sql->sql('select',"SELECT * FROM channels");
												while ($row = $records->fetchArray()) {
													if ($row['enabled'] == 'enabled') {
														$this->_core_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"\x033CID: ".$row['id']." Server: ".$row['server']." Channel: ".$row['channel']." Pass: ".$row['chanpass']." Channel Modes: ".$row['chanmodes']." Chan Options: ".$row['options']."\x03");
														$this->_core_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"\x033CID: ".$row['id']." Topic: ".$row['chantopic']."\x03");
													} else {
														$this->_core_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"\x034CID: ".$row['id']." Server: ".$row['server']." Channel: ".$row['channel']." Pass: ".$row['chanpass']." Channel Modes: ".$row['chanmodes']." Chan Options: ".$row['options']."\x03");
														$this->_core_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"\x034CID: ".$row['id']." Topic: ".$row['chantopic']."\x03");
													}
												}
												$this->_core_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"Color \x033Green\x03 is enabled, Color \x034Red\x03 is disabled");
												break;
											}
											default: {
												$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"Error, Use Either List, Add, or Chg");
												break;
											}
										}
									} else {
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"Error, Use Either List, Add, or Chg");
									}
								} else {
									$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
								}
							} else {
								$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You can not access channel via channel commands");
							}
							break;
						}
						case 'USER': {
							if (($type == 'PMSG') or ($type == 'PNOTE')) {
								if ($this->_core_get_access_logged($id,$user,$chan,'GLOBAL') >= 6) {
									if (count($indata) >= 6) {
										switch(strtoupper($indata[5])) {
											case 'ADD': {
												/*
												if (len(incom) >= 7):
													if (len(incom) >=8):
														tmpudata = pulluser(rl(incom[6]))
														if (tmpudata == 'FALSE'):
															tmppass = hashlib.md5()
															tmppass.update(incom[7])
															sql = "INSERT INTO users (username, password, global, server, channel, msgtype) VALUES ('{0}', '{1}', '{2}', '{3}', '{4}', '{5}')".format(rl(incom[6]),tmppass.hexdigest(),'NULL','NULL','NULL','msg')
															blarg = db.insert(sql)
															buildmsg(sock,'NORMAL',user,chan,'PRIV',"You have successfully created '{0}' with the password '{1}'".format(rl(incom[6]),incom[7]))
														else:
															buildmsg(sock,'ERROR',user,chan,'PRIV',"The username you entered already exists")
													else:
														buildmsg(sock,'ERROR',user,chan,'PRIV',"You only entered a username, please enter a password as well")
												else:
													buildmsg(sock,'ERROR',user,chan,'PRIV',"You are missing <username> <password>")
												*/
												break;
											}
											case 'CHG': {
												/*
												if (len(incom) >= 7):
													if (len(incom) >= 8):
														if (incom[7].upper() == 'PASS'):
															if (len(incom) >= 9):
																tmppass = hashlib.md5()
																tmppass.update(incom[8])
																sql = "UPDATE users SET password = '{0}' where username = '{1}'".format(tmppass.hexdigest(),rl(incom[6]))
																vals = db.execute(sql)
																buildmsg(sock,'NORMAL',user,chan,'PRIV',"You have successfully changed the password for '{0}'".format(rl(incom[6])))													
															else:
																buildmsg(sock,'ERROR',user,chan,'PRIV',"Error, Use format <username> PASS <newpass>")
														elif (incom[7].upper() == 'MSGTYPE'):
															if (len(incom) >= 9):
																if (incom[8].lower() == 'notice'):
																	newtype = 'notice'
																else:
																	newtype = 'msg'
																sql = "UPDATE users SET msgtype = '{0}' where username = '{1}'".format(newtype,rl(incom[6]))
																vals = db.execute(sql)
																if (islogged(sock,rl(incom[6])) == 'TRUE'):
																	loggedin[sock][rl(incom[6])]['msgtype'] = newtype
																buildmsg(sock,'NORMAL',user,chan,'PRIV',"You have successfully changed the message type for '{0}' to '{1}'".format(rl(incom[6]),newtype))
															else:
																buildmsg(sock,'ERROR',user,chan,'PRIV',"Error, Use format <username> MSGTYPE <notice/msg>")
														else:
															buildmsg(sock,'ERROR',user,chan,'PRIV',"Error, Use Either Pass, Msgtype")
													else:
														buildmsg(sock,'ERROR',user,chan,'PRIV',"Error, Use Either Pass, Msgtype")
												else:
													buildmsg(sock,'ERROR',user,chan,'PRIV',"Missing Username")
												*/
												break;
											} 
											case 'DEL': {
												#this bit of coding is only gonna be temperary for the time being due to abuse possiblities
												$this->sql->sql('execute',"DELETE FROM users WHERE username = '".strtolower($indata[6])."'");
												$this->_core_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"Deleted ".strtolower($indata[6])." or attempted to delete from the database");
												break;
											}
											case 'LIST': {
												$records = $this->sql->sql('select',"SELECT * FROM users");
												while ($row = $records->fetchArray()) {
													$this->_core_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"UID: ".$row['id']." Username: ".$row['username']." Global: ".$row['global']." Server: ".$row['server']." Channel: ".$row['channel']." MsgType: ".$row['msgtype']);
												}
												break;
											}
											default: {
												$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"Error, Use Either List, Add, Del, or Chg");
												break;
											}
										}
									} else {
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"Error, Use Either List, Add, Del, or Chg");
									}
								} else {
									$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
								}
							} else {
								$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You can not access user via channel commands");
							}
							break;
						}
						case 'ACCESS': {
							$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"Command Access under construction");
							break;
						}
						case 'ACCOUNT': {
							if (($type == 'PMSG') or ($type == 'PNOTE')) {
								if ($this->_core_islogged($id,$user) == true) {
									if (count($indata) >= 6) {
										$userdetail = $this->_core_pulluser($this->sdata['cons'][$id]['loggedin'][$user]['username']);
										if (strtoupper($indata[5]) == 'CHGPASS') {
											if (count($indata) >= 7) {
												if (count($indata) >= 8) {
													$tmppass = md5($indata[6]);
													if ($userdetail['password'] == $tmppass) {
														$tmppass2 = $indata[7];
														$this->sql->sql('update',"UPDATE users SET password = '".$tmppass2."' where id = '".$userdetail['id']."'");
														$this->sdata['cons'][$id]['loggedin'][$user]['password'] = $tmppass2;
														$this->_core_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"You have successfully changed your password.");
													} else {
														$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You sure you entered your current password right?");
													}
												} else {
													$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"Missing New Password");
												}
											} else {
												$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"Missing Current Password");
											}
										}
										if (strtoupper($indata[5]) == 'MSGTYPE') {
											if (count($indata) >= 7) {
												if (strtolower($indata[6]) == 'notice') {
													$newtype = 'notice';
												} else {
													$newtype = 'msg';
												}
												$this->sql->sql('update',"UPDATE users SET msgtype = '".$newtype."' where id = '".$userdetail['id']."'");
												$this->sdata['cons'][$id]['loggedin'][$user]['msgtype'] = $newtype;
												$this->_core_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"You have successfully changed your message type to ".$newtype);
											} else {
												$this->_core_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"You have to enter a Message type");
											}
										}
									} else {
										$this->_core_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"Your account details ".$user."(".$this->sdata['cons'][$id]['loggedin'][$user]['username'].")");
										$this->_core_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"MSGTYPE: ".$this->sdata['cons'][$id]['loggedin'][$user]['msgtype']);
									}
								} else {
									$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOTLOGGED');
								}
							} else {
								$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You can not access your account via channel commands");
							}
							break;
						}
						case 'WHOIS': {
							$passthrough = false;
							if (($type == 'PMSG') or ($type == 'PNOTE')) {
								if (count($indata) >= 6) {
									$chan = $indata[5];
									if (count($indata) >= 7) {
										$uwho = $indata[6];
										$passthrough = true;
									} else {
										$uwho = 'NULL';
										$passthrough = true;
									}
								} else {
									$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
								}
							}
							if (($type == 'CMSG') or ($type == 'CNOTE')) {
								if (count($indata) >= 6) {
									$uwho = $indata[5];
									$passthrough = true;
								} else {
									$uwho = 'NULL';
									$passthrough = true;
								}
							}
							if ($passthrough == true) {
								$this->_core_get_whois($id,$user,$chan,'WHOIS',$uwho);
							}
							break;
						}
						case 'WHOAMI': {
							$passthrough = false;
							if (($type == 'PMSG') or ($type == 'PNOTE')) {
								if (count($indata) >= 6) {
									$chan = $indata[5];
									$passthrough = true;
								} else {
									$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
								}
							}
							if (($type == 'CMSG') or ($type == 'CNOTE')) {
								$passthrough = true;
							}
							if ($passthrough == true) {
								$this->_core_get_whois($id,$user,$chan,'WHOAMI','NULL');
							}
							break;
						}
						case 'LOGOUT': {
							if ($this->_core_islogged($id,$user) == true) {
								$this->_core_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"You have been logged out of ".$this->sdata['cons'][$id]['loggedin'][$user]['username']);
								unset($this->sdata['cons'][$id]['loggedin'][$user]);
							} else {
								$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOTLOGGED');
							}
							break;
						}
						case 'LOGIN': {
							if (($type == 'PMSG') or ($type == 'PNOTE')) {
								if ($this->_core_islogged($id,$user) == false) {
									if (count($indata) >= 6) {
										if (count($indata) >= 7) {
											$tmpudata = $this->_core_pulluser($indata[5]);
											if ($tmpudata != false) {
												$tmppass = md5($indata[6]);
												if ($tmpudata['password'] == $tmppass) {
													$this->sdata['cons'][$id]['loggedin'][$user] = ['username'=>$tmpudata['username'],'msg'=>$tmpudata['msgtype'],'umask'=>$indata[0]];
													$this->_core_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"You have successfully logged in as ".strtolower($indata[5]));
												} else {
													$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You have failed to login");
												}
											} else {
												$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a valid username");
											}
										} else {
											$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You only entered a username, please enter a password as well");
										}
									} else {
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You are missing <username> <password>");
									}
								} else {
									$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You are already LOGGED In as ".$this->sdata['cons'][$id]['loggedin'][$user]['username']);
								}
							} else {
								$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You can not log in via channel commands");
							}
							break;
						}
						case 'REGISTER': {
							if (($type == 'PMSG') or ($type == 'PNOTE')) {
								if ($this->_core_islogged($id,$user) == false) {
									if (count($indata) >= 6) {
										if (count($indata) >= 7) {
											$tmpudata = $this->_core_pulluser($indata[5]);
											if ($tmpudata == false) {
												$tmppass = md5($indata[6]);
												$this->sql->sql('insert',"INSERT INTO users (username, password, global, server, channel, msgtype) VALUES (".strtolower($indata[5]).", ".$tmppass.", NULL, NULL, NULL, msg)");
												$this->sdata['cons'][$id]['loggedin'][$user] = ['username'=>strtolower($indata[5]),'msg'=>'msg','umask'=>$indata[0]];
												$this->_core_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"You have successfully registered as ".strtolower($indata[5])." and have been auto logged-in");
											} else {
												$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"The username you entered already exists");
											}
										} else {
											$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You only entered a username, please enter a password as well");
										}
									} else {
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You are missing <username> <password>");
									}
								} else {
									$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','LOGIN');
								}
							} else {
								$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You can not register via channel commands");
							}
							break;
						}
						case 'VERSION': {
							$this->_core_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"Ch3wyB0t Version ".$CORE['info']['version']);
							break;
						}
						case 'HELP': {
							if (($type == 'PMSG') or ($type == 'PNOTE')) {
								if (count($indata) >= 6) {
									$chan = $indata[5];
								}
							}
							$this->_core_command_help($id,$user,$chan,$indata);
							break;
						}
						case 'MOWNER': {
							if ($this->_core_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										$passthrough = true;
									} else {
										$passthrough = false;
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									$passthrough = true;
								}
								if ($passthrough == true) {
									if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 5) {
										$this->_core_massmodes($id,$user,$chan,['ADD','q','ALL']);
									} else {
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'OWNER': {
							if ($this->_core_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										if (count($indata) >= 7) {
											$tmpdata = $this->_core_array_join($indata,6," ");
											$passthrough = true;
										} else {
											$passthrough = false;
											$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter any nicks");
										}
									} else {
										$passthrough = false;
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									if (count($indata) >= 6) {
										$tmpdata = $this->_core_array_join($indata,5," ");
										$passthrough = true;
									} else {
										$passthrough = false;
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter any nicks");
									}
								}
								if ($passthrough == true) {
									if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 5) {
										$this->_core_massmodes($id,$user,$chan,['ADD','q',$tmpdata]);
									} else {
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'MDEOWNER': {
							if ($this->_core_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										$passthrough = true;
									} else {
										$passthrough = false;
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									$passthrough = true;
								}
								if ($passthrough == true) {
									if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 5) {
										$this->_core_massmodes($id,$user,$chan,['REM','q','BC']);
									} else {
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'DEOWNER': {
							if ($this->_core_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										if (count($indata) >= 7) {
											$tmpdata = $this->_core_array_join($indata,6," ");
											$passthrough = true;
										} else {
											$passthrough = false;
											$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter any nicks");
										}
									} else {
										$passthrough = false;
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									if (count($indata) >= 6) {
										$tmpdata = $this->_core_array_join($indata,5," ");
										$passthrough = true;
									} else {
										$passthrough = false;
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter any nicks");
									}
								}
								if ($passthrough == true) {
									if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 5) {
										$this->_core_massmodes($id,$user,$chan,['REM','q',$tmpdata]);
									} else {
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'OWNERME': {
							if ($this->_core_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										$passthrough = true;
									} else {
										$passthrough = false;
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									$passthrough = true;
								}
								if ($passthrough == true) {
									if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 5) {
										$this->_core_massmodes($id,$user,$chan,['ADD','q',$user]);
									} else {
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'DEOWNERME': {
							if ($this->_core_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										$passthrough = true;
									} else {
										$passthrough = false;
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									$passthrough = true;
								}
								if ($passthrough == true) {
									if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 5) {
										$this->_core_massmodes($id,$user,$chan,['REM','q',$user]);
									} else {
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'MPROTECT': {
							if ($this->_core_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										$passthrough = true;
									} else {
										$passthrough = false;
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									$passthrough = true;
								}
								if ($passthrough == true) {
									if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 5) {
										$this->_core_massmodes($id,$user,$chan,['ADD','a','ALL']);
									} else {
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'PROTECT': {
							if ($this->_core_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										if (count($indata) >= 7) {
											$tmpdata = $this->_core_array_join($indata,6," ");
											$passthrough = true;
										} else {
											$passthrough = false;
											$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter any nicks");
										}
									} else {
										$passthrough = false;
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									if (count($indata) >= 6) {
										$tmpdata = $this->_core_array_join($indata,5," ");
										$passthrough = true;
									} else {
										$passthrough = false;
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter any nicks");
									}
								}
								if ($passthrough == true) {
									if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 5) {
										$this->_core_massmodes($id,$user,$chan,['ADD','a',$tmpdata]);
									} else {
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'MDEPROTECT': {
							if ($this->_core_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										$passthrough = true;
									} else {
										$passthrough = false;
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									$passthrough = true;
								}
								if ($passthrough == true) {
									if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 5) {
										$this->_core_massmodes($id,$user,$chan,['REM','a','BC']);
									} else {
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'DEPROTECT': {
							if ($this->_core_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										if (count($indata) >= 7) {
											$tmpdata = $this->_core_array_join($indata,6," ");
											$passthrough = true;
										} else {
											$passthrough = false;
											$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter any nicks");
										}
									} else {
										$passthrough = false;
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									if (count($indata) >= 6) {
										$tmpdata = $this->_core_array_join($indata,5," ");
										$passthrough = true;
									} else {
										$passthrough = false;
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter any nicks");
									}
								}
								if ($passthrough == true) {
									if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 5) {
										$this->_core_massmodes($id,$user,$chan,['REM','a',$tmpdata]);
									} else {
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'PROTECTME': {
							if ($this->_core_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										$passthrough = true;
									} else {
										$passthrough = false;
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									$passthrough = true;
								}
								if ($passthrough == true) {
									if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 4) {
										$this->_core_massmodes($id,$user,$chan,['ADD','a',$user]);
									} else {
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'DEPROTECTME': {
							if ($this->_core_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										$passthrough = true;
									} else {
										$passthrough = false;
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									$passthrough = true;
								}
								if ($passthrough == true) {
									if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 4) {
										$this->_core_massmodes($id,$user,$chan,['REM','a',$user]);
									} else {
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'MOP': {
							if ($this->_core_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										$passthrough = true;
									} else {
										$passthrough = false;
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									$passthrough = true;
								}
								if ($passthrough == true) {
									if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 3) {
										$this->_core_massmodes($id,$user,$chan,['ADD','o','ALL']);
									} else {
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'OP': {
							if ($this->_core_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										if (count($indata) >= 7) {
											$tmpdata = $this->_core_array_join($indata,6," ");
											$passthrough = true;
										} else {
											$passthrough = false;
											$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter any nicks");
										}
									} else {
										$passthrough = false;
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									if (count($indata) >= 6) {
										$tmpdata = $this->_core_array_join($indata,5," ");
										$passthrough = true;
									} else {
										$passthrough = false;
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter any nicks");
									}
								}
								if ($passthrough == true) {
									if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 3) {
										$this->_core_massmodes($id,$user,$chan,['ADD','o',$tmpdata]);
									} else {
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'MDEOP': {
							if ($this->_core_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										$passthrough = true;
									} else {
										$passthrough = false;
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									$passthrough = true;
								}
								if ($passthrough == true) {
									if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 3) {
										$this->_core_massmodes($id,$user,$chan,['REM','o','BC']);
									} else {
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'DEOP': {
							if ($this->_core_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										if (count($indata) >= 7) {
											$tmpdata = $this->_core_array_join($indata,6," ");
											$passthrough = true;
										} else {
											$passthrough = false;
											$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter any nicks");
										}
									} else {
										$passthrough = false;
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									if (count($indata) >= 6) {
										$tmpdata = $this->_core_array_join($indata,5," ");
										$passthrough = true;
									} else {
										$passthrough = false;
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter any nicks");
									}
								}
								if ($passthrough == true) {
									if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 3) {
										$this->_core_massmodes($id,$user,$chan,['REM','o',$tmpdata]);
									} else {
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'OPME': {
							if ($this->_core_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										$passthrough = true;
									} else {
										$passthrough = false;
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									$passthrough = true;
								}
								if ($passthrough == true) {
									if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 3) {
										$this->_core_massmodes($id,$user,$chan,['ADD','o',$user]);
									} else {
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'DEOPME': {
							if ($this->_core_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										$passthrough = true;
									} else {
										$passthrough = false;
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									$passthrough = true;
								}
								if ($passthrough == true) {
									if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 3) {
										$this->_core_massmodes($id,$user,$chan,['REM','o',$user]);
									} else {
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'MHALFOP': {
							if ($this->_core_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										$passthrough = true;
									} else {
										$passthrough = false;
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									$passthrough = true;
								}
								if ($passthrough == true) {
									if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 3) {
										$this->_core_massmodes($id,$user,$chan,['ADD','h','ALL']);
									} else {
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'HALFOP': {
							if ($this->_core_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										if (count($indata) >= 7) {
											$tmpdata = $this->_core_array_join($indata,6," ");
											$passthrough = true;
										} else {
											$passthrough = false;
											$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter any nicks");
										}
									} else {
										$passthrough = false;
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									if (count($indata) >= 6) {
										$tmpdata = $this->_core_array_join($indata,5," ");
										$passthrough = true;
									} else {
										$passthrough = false;
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter any nicks");
									}
								}
								if ($passthrough == true) {
									if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 3) {
										$this->_core_massmodes($id,$user,$chan,['ADD','h',$tmpdata]);
									} else {
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'MDEHALFOP': {
							if ($this->_core_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										$passthrough = true;
									} else {
										$passthrough = false;
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									$passthrough = true;
								}
								if ($passthrough == true) {
									if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 3) {
										$this->_core_massmodes($id,$user,$chan,['REM','h','BC']);
									} else {
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'DEHALFOP': {
							if ($this->_core_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										if (count($indata) >= 7) {
											$tmpdata = $this->_core_array_join($indata,6," ");
											$passthrough = true;
										} else {
											$passthrough = false;
											$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter any nicks");
										}
									} else {
										$passthrough = false;
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									if (count($indata) >= 6) {
										$tmpdata = $this->_core_array_join($indata,5," ");
										$passthrough = true;
									} else {
										$passthrough = false;
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter any nicks");
									}
								}
								if ($passthrough == true) {
									if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 3) {
										$this->_core_massmodes($id,$user,$chan,['REM','h',$tmpdata]);
									} else {
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'HALFOPME': {
							if ($this->_core_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										$passthrough = true;
									} else {
										$passthrough = false;
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									$passthrough = true;
								}
								if ($passthrough == true) {
									if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 2) {
										$this->_core_massmodes($id,$user,$chan,['ADD','h',$user]);
									} else {
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'DEHALFOPME': {
							if ($this->_core_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										$passthrough = true;
									} else {
										$passthrough = false;
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									$passthrough = true;
								}
								if ($passthrough == true) {
									if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 2) {
										$this->_core_massmodes($id,$user,$chan,['REM','h',$user]);
									} else {
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'MVOICE': {
							if ($this->_core_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										$passthrough = true;
									} else {
										$passthrough = false;
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									$passthrough = true;
								}
								if ($passthrough == true) {
									if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 2) {
										$this->_core_massmodes($id,$user,$chan,['ADD','v','ALL']);
									} else {
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'VOICE': {
							if ($this->_core_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										if (count($indata) >= 7) {
											$tmpdata = $this->_core_array_join($indata,6," ");
											$passthrough = true;
										} else {
											$passthrough = false;
											$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter any nicks");
										}
									} else {
										$passthrough = false;
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									if (count($indata) >= 6) {
										$tmpdata = $this->_core_array_join($indata,5," ");
										$passthrough = true;
									} else {
										$passthrough = false;
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter any nicks");
									}
								}
								if ($passthrough == true) {
									if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 2) {
										$this->_core_massmodes($id,$user,$chan,['ADD','v',$tmpdata]);
									} else {
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'MDEVOICE': {
							if ($this->_core_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										$passthrough = true;
									} else {
										$passthrough = false;
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									$passthrough = true;
								}
								if ($passthrough == true) {
									if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 2) {
										$this->_core_massmodes($id,$user,$chan,['REM','v','BC']);
									} else {
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'DEVOICE': {
							if ($this->_core_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										if (count($indata) >= 7) {
											$tmpdata = $this->_core_array_join($indata,6," ");
											$passthrough = true;
										} else {
											$passthrough = false;
											$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter any nicks");
										}
									} else {
										$passthrough = false;
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									if (count($indata) >= 6) {
										$tmpdata = $this->_core_array_join($indata,5," ");
										$passthrough = true;
									} else {
										$passthrough = false;
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter any nicks");
									}
								}
								if ($passthrough == true) {
									if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 2) {
										$this->_core_massmodes($id,$user,$chan,['REM','v',$tmpdata]);
									} else {
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'VOICEME': {
							if ($this->_core_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										$passthrough = true;
									} else {
										$passthrough = false;
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									$passthrough = true;
								}
								if ($passthrough == true) {
									if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 1) {
										$this->_core_massmodes($id,$user,$chan,['ADD','v',$user]);
									} else {
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'DEVOICEME': {
							if ($this->_core_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										$passthrough = true;
									} else {
										$passthrough = false;
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									$passthrough = true;
								}
								if ($passthrough == true) {
									if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 1) {
										$this->_core_massmodes($id,$user,$chan,['REM','v',$user]);
									} else {
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'SAY': {
							if ($this->_core_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										if (count($indata) >= 7) {
											$passthrough = true;
											$tmpoutput = $this->_core_array_join($indata,6," ");
										} else {
											$passthrough = false;
											$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a message");
										}
									} else {
										$passthrough = false;
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									if (count($indata) >= 6) { 
										$passthrough = true;
										$tmpoutput = $this->_core_array_join($indata,5," ");
									} else {
										$passthrough = false;
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a message");
									}
								}
								if ($passthrough == true) {
									if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 1) {
										$this->_core_sts($id,"PRIVMSG ".$chan." :".$tmpoutput);
									} else {
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'ACT': {
							if ($this->_core_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										if (count($indata) >= 7) {
											$passthrough = true;
											$tmpoutput = $this->_core_array_join($indata,6," ");
										} else {
											$passthrough = false;
											$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a action");
										}
									} else {
										$passthrough = false;
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									if (count($indata) >= 6) { 
										$passthrough = true;
										$tmpoutput = $this->_core_array_join($indata,5," ");
									} else {
										$passthrough = false;
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a action");
									}
								}
								if ($passthrough == true) {
									if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 1) {
										$this->_core_sts($id,"PRIVMSG ".$chan." :\x01ACTION ".$tmpoutput."\x01");
									} else {
										$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'TESTCMD': {
							if ($this->_core_get_access_logged($id,$user,$chan,'GLOBAL') >= 7) {
								$this->_sprint($id." ".print_r($this->sdata,true),'debug',false);
							} else {
								$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'TESTDATA': {
							if ($this->_core_get_access_logged($id,$user,$chan,'GLOBAL') >= 7) {
								$this->_sprint($id." ".print_r($this->data,true),'debug',false);
							} else {
								$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'TEST': {
							if ($this->_core_get_access_logged($id,$user,$chan,'GLOBAL') >= 7) {
								#$this->_core_sts($id,"MODE :".$this->sdata['cons'][$id]['nick']);
								//$this->_core_massmodes($id,$user,$chan,['ADD','v','chewy Channel_Bot chewyb_13']);
								//$this->_core_massmodes($id,$user,$chan,['ADD','v','BC']);
								$this->_core_massmodes($id,$user,$chan,['ADD','v','ALL']);
								//$this->_core_massmodes($id,$user,$chan,['REM','v','chewy Channel_Bot chewyb_13']);
								//$this->_core_massmodes($id,$user,$chan,['REM','v','BC']);
								$this->_core_massmodes($id,$user,$chan,['REM','v','ALL']);

								$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"data blarg");
							} else {
								$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						default: {
							$this->_sprint($id." c1 ".print_r($indata,true),'debug',false);
							if (($type == 'CMSG') or ($type == 'CNOTE')) {
								$this->_core_buildmsg($id,'ERROR',$user,$chan,'CHAN',"The command ".$indata[4]." doesn't exist at the momment");
							}
							if (($type == 'PMSG') or ($type == 'PNOTE')) {
								$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"The command ".$indata[4]." doesn't exist at the momment");
							}
							break;
						}
					}
				}
			} 
			else {
				if ($type == 'PNOTE') {
					if ($user == 'NickServ') {
						if (count($indata) >= 9) {
							if (($indata[6] == 'registered') and ($indata[8] == 'protected.')) {
								if ($this->data['data'][$id]['server']['nickservpass'] != 'NULL') {
									$this->sdata['cons'][$id]['identified'] = 1;
								}
							} elseif (($indata[6] == 'NickServ') and ($indata[7] == 'IDENTIFY')) {
								if (($this->data['data'][$id]['server']['nickservpass'] != 'NULL') and ($this->sdata['cons'][$id]['identified'] == 1)) {
									$this->sdata['cons'][$id]['identified'] = 2;
									$this->_core_sts($id,"PRIVMSG NickServ :IDENTIFY ".$this->data['data'][$id]['server']['nickservpass']);
									$this->_core_autojoinchans($id);
								}
							}
						}
					} 
					else {
						$this->_sprint($id." n1 ".print_r($indata,true),'debug',false);
					}
				}
				else {
					#$this->_core_buildmsg($id,'NORMAL',$user,$chan,'PRIV',$output) #types are NORMAL,HELP,ERROR, mtype are PRIV,CHAN
					$this->_sprint($id." b1 ".print_r($indata,true),'debug',false);
				}
			}
		} 
		else {
			#$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"The command ".$indata[3]." doesn't exist at the momment");
			$this->_sprint($id." d1 ".print_r($indata,true),'debug',false);
		}
	}
	
	private function _core_command_help($id,$user,$chan,$indata) {
		$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',$this->data['settings']['botname']." help system");
		$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"If you need help on a certain command go help <command>");
		$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',$this->data['settings']['chancom'].$this->data['settings']['signal']." = CHAN, ".$this->data['settings']['dcccom'].$this->data['settings']['signal']." = DCC, ".$this->data['settings']['pvtcom'].$this->data['settings']['signal']." = MSG");
		if (count($indata) >= 6) {
			if ($chan == $indata[5]) {
				if (count($indata) >= 7) {
					$hcmds = $this->_core_array_rearrange($indata,6);
					$processhelp = true;
				} else {
					$processhelp = false;
				}
			} else {
				$hcmds = $this->_core_array_rearrange($indata,5);
				$processhelp = true;
			}
		} else {
			$processhelp = false;
		}
		if ($processhelp == true) {
			switch(strtoupper($hcmds[0])) {
				case 'RAW': {
					if ($this->_core_get_access_logged($id,$user,$chan,'GLOBAL') >= 7) {
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(RAW)- This Command is super dangerous as it will whatever is entered into it to the server");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(RAW)- Command Structure: ".$this->data['settings']['pvtcom'].$this->data['settings']['signal']." raw <data to send>");
						#$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(RAW)- Command Structure: ".$this->data['settings']['dcccom'].$this->data['settings']['signal']." raw <data to send>");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(RAW)- Command Structure: ".$this->data['settings']['chancom'].$this->data['settings']['signal']." raw <data to send>");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(RAW)- \x034It is highly recommended you DO NOT use this command\x03");
					} else {
						$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'RAWDB': {
					if ($this->_core_get_access_logged($id,$user,$chan,'GLOBAL') >= 7) {
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(RAWDB)- This Command is super dangerous as it will whatever is entered into it to the database");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(RAWDB)- Command Structure: ".$this->data['settings']['pvtcom'].$this->data['settings']['signal']." rawdb <data to send>");
						#$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(RAWDB)- Command Structure: ".$this->data['settings']['dcccom'].$this->data['settings']['signal']." rawdb <data to send>");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(RAWDB)- Command Structure: ".$this->data['settings']['chancom'].$this->data['settings']['signal']." rawdb <data to send>");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(RAWDB)- \x034It is highly recommended you DO NOT use this command\x03");
					} else {
						$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'EXIT': {
					if ($this->_core_get_access_logged($id,$user,$chan,'GLOBAL') >= 6) {
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(EXIT)- This command will cause the bot to exit completely");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(EXIT)- Command Structure: ".$this->data['settings']['pvtcom'].$this->data['settings']['signal']." exit [<message>]");
						#$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(EXIT)- Command Structure: ".$this->data['settings']['dcccom'].$this->data['settings']['signal']." exit [<message>]");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(EXIT)- Command Structure: ".$this->data['settings']['chancom'].$this->data['settings']['signal']." exit [<message>]");
					} else {
						$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'RELOAD': {
					if ($this->_core_get_access_logged($id,$user,$chan,'GLOBAL') >= 6) {
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(RELOAD)- This command will cause the bot to exit so it can restart fresh");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(RELOAD)- Command Structure: ".$this->data['settings']['pvtcom'].$this->data['settings']['signal']." reload");
						#$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(RELOAD)- Command Structure: ".$this->data['settings']['dcccom'].$this->data['settings']['signal']." reload");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(RELOAD)- Command Structure: ".$this->data['settings']['chancom'].$this->data['settings']['signal']." reload");
					} else {
						$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'QUIT': {
					if ($this->_core_get_access_logged($id,$user,$chan,'GLOBAL') >= 6) {
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(QUIT)- This command will cause the bot to quit from the current network");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(QUIT)- Command Structure: ".$this->data['settings']['pvtcom'].$this->data['settings']['signal']." quit [<message>]");
						#$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(QUIT)- Command Structure: ".$this->data['settings']['dcccom'].$this->data['settings']['signal']." quit [<message>]");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(QUIT)- Command Structure: ".$this->data['settings']['chancom'].$this->data['settings']['signal']." quit [<message>]");
					} else {
						$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'REHASH': {
					if ($this->_core_get_access_logged($id,$user,$chan,'GLOBAL') >= 6) {
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(REHASH)- This command will cause the bot to reload from the database");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(REHASH)- Command Structure: ".$this->data['settings']['pvtcom'].$this->data['settings']['signal']." rehash");
						#$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(REHASH)- Command Structure: ".$this->data['settings']['dcccom'].$this->data['settings']['signal']." rehash");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(REHASH)- Command Structure: ".$this->data['settings']['chancom'].$this->data['settings']['signal']." rehash");
					} else {
						$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'SETTINGS': {
					if ($this->_core_get_access_logged($id,$user,$chan,'GLOBAL') >= 6) {
						if (count($hcmds) >= 2) {
							switch (strtoupper($hcmds[1])) {
								case 'LIST': {
									$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(SETTINGS)-(LIST)- This Command list the values that are currently in the bots settings");
									$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(SETTINGS)-(LIST)- Command Structure: ".$this->data['settings']['pvtcom'].$this->data['settings']['signal']." settings list");
									break;
								} 
								case 'SET': {
									$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(SETTINGS)-(SET)- This Command will set the value you pick and update both local and the db");
									$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(SETTINGS)-(SET)- Command Structure: ".$this->data['settings']['pvtcom'].$this->data['settings']['signal']." settings set <setting> <value>");
									break;
								}
								default: {
									$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(SETTINGS)- The help topic settings ".$hcmds[1]." is not in the database");
									break;
								}
							}
						} else {
							$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(SETTINGS)- This Command deals with the bot's settings");
							$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(SETTINGS)- Command Structure: ".$this->data['settings']['pvtcom'].$this->data['settings']['signal']." settings [<list>][<set> <setting> <value>]");
							$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(SETTINGS)- Topics available: list set");
						}
					} else {
						$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'MOWNER': {
					if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 5) {
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MOWNER)- This command will Owner everyone in <channel>");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MOWNER)- Command Structure: ".$this->data['settings']['pvtcom'].$this->data['settings']['signal']." MOwner <channel>");
						#$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MOWNER)- Command Structure: ".$this->data['settings']['dcccom'].$this->data['settings']['signal']." MOwner <channel>");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MOWNER)- Command Structure: ".$this->data['settings']['chancom'].$this->data['settings']['signal']." MOwner");
					} else {
						$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'OWNER': {
					if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 5) {
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(OWNER)- This command will Owner the <nicks> you pick on <channel>");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(OWNER)- Command Structure: ".$this->data['settings']['pvtcom'].$this->data['settings']['signal']." Owner <channel> <nick> [<nick> [<nick>]]");
						#$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(OWNER)- Command Structure: ".$this->data['settings']['dcccom'].$this->data['settings']['signal']." Owner <channel> <nick> [<nick> [<nick>]]");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(OWNER)- Command Structure: ".$this->data['settings']['chancom'].$this->data['settings']['signal']." Owner <nick> [<nick> [<nick>]]");
					} else {
						$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'MDEOWNER': {
					if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 5) {
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MDEOWNER)- This command will De-Owner everyone in <channel> but the bot and you");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MDEOWNER)- Command Structure: ".$this->data['settings']['pvtcom'].$this->data['settings']['signal']." MDeOwner <channel>");
						#$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MDEOWNER)- Command Structure: ".$this->data['settings']['dcccom'].$this->data['settings']['signal']." MDeOwner <channel>");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MDEOWNER)- Command Structure: ".$this->data['settings']['chancom'].$this->data['settings']['signal']." MDeOwner");
					} else {
						$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'DEOWNER': {
					if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 5) {
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEOWNER)- This command will De-Owner the <nicks> you pick on <channel>");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEOWNER)- Command Structure: ".$this->data['settings']['pvtcom'].$this->data['settings']['signal']." DeOwner <channel> <nick> [<nick> [<nick>]]");
						#$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEOWNER)- Command Structure: ".$this->data['settings']['dcccom'].$this->data['settings']['signal']." DeOwner <channel> <nick> [<nick> [<nick>]]");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEOWNER)- Command Structure: ".$this->data['settings']['chancom'].$this->data['settings']['signal']." DeOwner <nick> [<nick> [<nick>]]");
					} else {
						$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'OWNERME': {
					if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 5) {
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(OWNERME)- This command will Owner yourself on <channel>");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(OWNERME)- Command Structure: ".$this->data['settings']['pvtcom'].$this->data['settings']['signal']." OwnerMe <channel>");
						#$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(OWNERME)- Command Structure: ".$this->data['settings']['dcccom'].$this->data['settings']['signal']." OwnerMe <channel>");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(OWNERME)- Command Structure: ".$this->data['settings']['chancom'].$this->data['settings']['signal']." OwnerMe");
					} else {
						$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'DEOWNERME': {
					if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 5) {
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEOWNERME)- This command will de-Owner yourself on <channel>");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEOWNERME)- Command Structure: ".$this->data['settings']['pvtcom'].$this->data['settings']['signal']." DeOwnerMe <channel>");
						#$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEOWNERME)- Command Structure: ".$this->data['settings']['dcccom'].$this->data['settings']['signal']." DeOwnerMe <channel>");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEOWNERME)- Command Structure: ".$this->data['settings']['chancom'].$this->data['settings']['signal']." DeOwnerMe");
					} else {
						$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'MPROTECT': {
					if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 5) {
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MPROTECT)- This command will Protect everyone in <channel>");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MPROTECT)- Command Structure: ".$this->data['settings']['pvtcom'].$this->data['settings']['signal']." MProtect <channel>");
						#$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MPROTECT)- Command Structure: ".$this->data['settings']['dcccom'].$this->data['settings']['signal']." MProtect <channel>");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MPROTECT)- Command Structure: ".$this->data['settings']['chancom'].$this->data['settings']['signal']." MProtect");
					} else {
						$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'PROTECT': {
					if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 5) {
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(PROTECT)- This command will Protect the <nicks> you pick on <channel>");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(PROTECT)- Command Structure: ".$this->data['settings']['pvtcom'].$this->data['settings']['signal']." Protect <channel> <nick> [<nick> [<nick>]]");
						#$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(PROTECT)- Command Structure: ".$this->data['settings']['dcccom'].$this->data['settings']['signal']." Protect <channel> <nick> [<nick> [<nick>]]");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(PROTECT)- Command Structure: ".$this->data['settings']['chancom'].$this->data['settings']['signal']." Protect <nick> [<nick> [<nick>]]");
					} else {
						$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'MDEPROTECT': {
					if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 5) {
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MDEPROTECT)- This command will De-Protect everyone in <channel> but the bot and you");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MDEPROTECT)- Command Structure: ".$this->data['settings']['pvtcom'].$this->data['settings']['signal']." MDeProtect <channel>");
						#$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MDEPROTECT)- Command Structure: ".$this->data['settings']['dcccom'].$this->data['settings']['signal']." MDeProtect <channel>");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MDEPROTECT)- Command Structure: ".$this->data['settings']['chancom'].$this->data['settings']['signal']." MDeProtect");
					} else {
						$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'DEPROTECT': {
					if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 5) {
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEPROTECT)- This command will De-Protect the <nicks> you pick on <channel>");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEPROTECT)- Command Structure: ".$this->data['settings']['pvtcom'].$this->data['settings']['signal']." DeProtect <channel> <nick> [<nick> [<nick>]]");
						#$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEPROTECT)- Command Structure: ".$this->data['settings']['dcccom'].$this->data['settings']['signal']." DeProtect <channel> <nick> [<nick> [<nick>]]");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEPROTECT)- Command Structure: ".$this->data['settings']['chancom'].$this->data['settings']['signal']." DeProtect <nick> [<nick> [<nick>]]");
					} else {
						$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'PROTECTME': {
					if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 4) {
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(PROTECTME)- This command will Protect yourself on <channel>");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(PROTECTME)- Command Structure: ".$this->data['settings']['pvtcom'].$this->data['settings']['signal']." ProtectMe <channel>");
						#$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(PROTECTME)- Command Structure: ".$this->data['settings']['dcccom'].$this->data['settings']['signal']." ProtectMe <channel>");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(PROTECTME)- Command Structure: ".$this->data['settings']['chancom'].$this->data['settings']['signal']." ProtectMe");
					} else {
						$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'DEPROTECTME': {
					if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 4) {
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEPROTECTME)- This command will de-Protect yourself on <channel>");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEPROTECTME)- Command Structure: ".$this->data['settings']['pvtcom'].$this->data['settings']['signal']." DeProtectMe <channel>");
						#$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEPROTECTME)- Command Structure: ".$this->data['settings']['dcccom'].$this->data['settings']['signal']." DeProtectMe <channel>");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEPROTECTME)- Command Structure: ".$this->data['settings']['chancom'].$this->data['settings']['signal']." DeProtectMe");
					} else {
						$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'MOP': {
					if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 3) {
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MOP)- This command will Op everyone in <channel>");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MOP)- Command Structure: ".$this->data['settings']['pvtcom'].$this->data['settings']['signal']." MOp <channel>");
						#$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MOP)- Command Structure: ".$this->data['settings']['dcccom'].$this->data['settings']['signal']." MOp <channel>");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MOP)- Command Structure: ".$this->data['settings']['chancom'].$this->data['settings']['signal']." MOp");
					} else {
						$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'OP': {
					if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 3) {
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(OP)- This command will Op the <nicks> you pick on <channel>");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(OP)- Command Structure: ".$this->data['settings']['pvtcom'].$this->data['settings']['signal']." Op <channel> <nick> [<nick> [<nick>]]");
						#$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(OP)- Command Structure: ".$this->data['settings']['dcccom'].$this->data['settings']['signal']." Op <channel> <nick> [<nick> [<nick>]]");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(OP)- Command Structure: ".$this->data['settings']['chancom'].$this->data['settings']['signal']." Op <nick> [<nick> [<nick>]]");
					} else {
						$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'MDEOP': {
					if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 3) {
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MDEOP)- This command will De-Op everyone in <channel> but the bot and you");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MDEOP)- Command Structure: ".$this->data['settings']['pvtcom'].$this->data['settings']['signal']." MDeOp <channel>");
						#$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MDEOP)- Command Structure: ".$this->data['settings']['dcccom'].$this->data['settings']['signal']." MDeOp <channel>");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MDEOP)- Command Structure: ".$this->data['settings']['chancom'].$this->data['settings']['signal']." MDeOp");
					} else {
						$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'DEOP': {
					if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 3) {
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEOP)- This command will De-Op the <nicks> you pick on <channel>");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEOP)- Command Structure: ".$this->data['settings']['pvtcom'].$this->data['settings']['signal']." DeOp <channel> <nick> [<nick> [<nick>]]");
						#$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEOP)- Command Structure: ".$this->data['settings']['dcccom'].$this->data['settings']['signal']." DeOp <channel> <nick> [<nick> [<nick>]]");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEOP)- Command Structure: ".$this->data['settings']['chancom'].$this->data['settings']['signal']." DeOp <nick> [<nick> [<nick>]]");
					} else {
						$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'OPME': {
					if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 3) {
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(OPME)- This command will Op yourself on <channel>");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(OPME)- Command Structure: ".$this->data['settings']['pvtcom'].$this->data['settings']['signal']." OpMe <channel>");
						#$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(OPME)- Command Structure: ".$this->data['settings']['dcccom'].$this->data['settings']['signal']." OpMe <channel>");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(OPME)- Command Structure: ".$this->data['settings']['chancom'].$this->data['settings']['signal']." OpMe");
					} else {
						$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'DEOPME': {
					if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 3) {
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEOPME)- This command will de-Op yourself on <channel>");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEOPME)- Command Structure: ".$this->data['settings']['pvtcom'].$this->data['settings']['signal']." DeOpMe <channel>");
						#$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEOPME)- Command Structure: ".$this->data['settings']['dcccom'].$this->data['settings']['signal']." DeOpMe <channel>");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEOPME)- Command Structure: ".$this->data['settings']['chancom'].$this->data['settings']['signal']." DeOpMe");
					} else {
						$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'MHALFOP': {
					if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 3) {
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MHALFOP)- This command will HalfOp everyone in <channel>");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MHALFOP)- Command Structure: ".$this->data['settings']['pvtcom'].$this->data['settings']['signal']." MHalfOp <channel>");
						#$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MHALFOP)- Command Structure: ".$this->data['settings']['dcccom'].$this->data['settings']['signal']." MHalfOp <channel>");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MHALFOP)- Command Structure: ".$this->data['settings']['chancom'].$this->data['settings']['signal']." MHalfOp");
					} else {
						$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'HALFOP': {
					if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 3) {
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(HALFOP)- This command will HalfOp the <nicks> you pick on <channel>");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(HALFOP)- Command Structure: ".$this->data['settings']['pvtcom'].$this->data['settings']['signal']." HalfOp <channel> <nick> [<nick> [<nick>]]");
						#$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(HALFOP)- Command Structure: ".$this->data['settings']['dcccom'].$this->data['settings']['signal']." HalfOp <channel> <nick> [<nick> [<nick>]]");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(HALFOP)- Command Structure: ".$this->data['settings']['chancom'].$this->data['settings']['signal']." HalfOp <nick> [<nick> [<nick>]]");
					} else {
						$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'MDEHALFOP': {
					if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 3) {
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MDEHALFOP)- This command will De-HalfOp everyone in <channel> but the bot and you");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MDEHALFOP)- Command Structure: ".$this->data['settings']['pvtcom'].$this->data['settings']['signal']." MDeHalfOp <channel>");
						#$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MDEHALFOP)- Command Structure: ".$this->data['settings']['dcccom'].$this->data['settings']['signal']." MDeHalfOp <channel>");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MDEHALFOP)- Command Structure: ".$this->data['settings']['chancom'].$this->data['settings']['signal']." MDeHalfOp");
					} else {
						$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'DEHALFOP': {
					if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 3) {
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEHALFOP)- This command will de-HalfOp the <nicks> you pick on <channel>");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEHALFOP)- Command Structure: ".$this->data['settings']['pvtcom'].$this->data['settings']['signal']." DeHalfOp <channel> <nick> [<nick> [<nick>]]");
						#$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEHALFOP)- Command Structure: ".$this->data['settings']['dcccom'].$this->data['settings']['signal']." DeHalfOp <channel> <nick> [<nick> [<nick>]]");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEHALFOP)- Command Structure: ".$this->data['settings']['chancom'].$this->data['settings']['signal']." DeHalfOp <nick> [<nick> [<nick>]]");
					} else {
						$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'HALFOPME': {
					if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 2) {
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(HALFOPME)- This command will HalfOp yourself on <channel>");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(HALFOPME)- Command Structure: ".$this->data['settings']['pvtcom'].$this->data['settings']['signal']." HalfOpMe <channel>");
						#$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(HALFOPME)- Command Structure: ".$this->data['settings']['dcccom'].$this->data['settings']['signal']." HalfOpMe <channel>");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(HALFOPME)- Command Structure: ".$this->data['settings']['chancom'].$this->data['settings']['signal']." HalfOpMe");
					} else {
						$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'DEHALFOPME': {
					if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 2) {
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEHALFOPME)- This command will de-HalfOp yourself on <channel>");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEHALFOPME)- Command Structure: ".$this->data['settings']['pvtcom'].$this->data['settings']['signal']." DeHalfOpMe <channel>");
						#$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEHALFOPME)- Command Structure: ".$this->data['settings']['dcccom'].$this->data['settings']['signal']." DeHalfOpMe <channel>");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEHALFOPME)- Command Structure: ".$this->data['settings']['chancom'].$this->data['settings']['signal']." DeHalfOpMe");
					} else {
						$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'MVOICE': {
					if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 2) {
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MVOICE)- This command will Voice everyone in <channel>");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MVOICE)- Command Structure: ".$this->data['settings']['pvtcom'].$this->data['settings']['signal']." MVoice <channel>");
						#$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MVOICE)- Command Structure: ".$this->data['settings']['dcccom'].$this->data['settings']['signal']." MVoice <channel>");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MVOICE)- Command Structure: ".$this->data['settings']['chancom'].$this->data['settings']['signal']." MVoice");
					} else {
						$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'VOICE': {
					if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 2) {
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(VOICE)- This command will Voice the <nicks> you pick on <channel>");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(VOICE)- Command Structure: ".$this->data['settings']['pvtcom'].$this->data['settings']['signal']." Voice <channel> <nick> [<nick> [<nick>]]");
						#$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(VOICE)- Command Structure: ".$this->data['settings']['dcccom'].$this->data['settings']['signal']." Voice <channel> <nick> [<nick> [<nick>]]");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(VOICE)- Command Structure: ".$this->data['settings']['chancom'].$this->data['settings']['signal']." Voice <nick> [<nick> [<nick>]]");
					} else {
						$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'MDEVOICE': {
					if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 2) {
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MDEVOICE)- This command will De-Voice everyone in <channel> but the bot and you");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MDEVOICE)- Command Structure: ".$this->data['settings']['pvtcom'].$this->data['settings']['signal']." MDeVoice <channel>");
						#$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MDEVOICE)- Command Structure: ".$this->data['settings']['dcccom'].$this->data['settings']['signal']." MDeVoice <channel>");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MDEVOICE)- Command Structure: ".$this->data['settings']['chancom'].$this->data['settings']['signal']." MDeVoice");
					} else {
						$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'DEVOICE': {
					if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 2) {
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEVOICE)- This command will de-voice the <nicks> you pick on <channel>");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEVOICE)- Command Structure: ".$this->data['settings']['pvtcom'].$this->data['settings']['signal']." DeVoice <channel> <nick> [<nick> [<nick>]]");
						#$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEVOICE)- Command Structure: ".$this->data['settings']['dcccom'].$this->data['settings']['signal']." DeVoice <channel> <nick> [<nick> [<nick>]]");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEVOICE)- Command Structure: ".$this->data['settings']['chancom'].$this->data['settings']['signal']." DeVoice <nick> [<nick> [<nick>]]");
					} else {
						$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'VOICEME': {
					if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 1) {
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(VOICEME)- This command will voice yourself on <channel>");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(VOICEME)- Command Structure: ".$this->data['settings']['pvtcom'].$this->data['settings']['signal']." VoiceMe <channel>");
						#$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(VOICEME)- Command Structure: ".$this->data['settings']['dcccom'].$this->data['settings']['signal']." VoiceMe <channel>");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(VOICEME)- Command Structure: ".$this->data['settings']['chancom'].$this->data['settings']['signal']." VoiceMe");
					} else {
						$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'DEVOICEME': {
					if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 1) {
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEVOICEME)- This command will de-voice yourself on <channel>");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEVOICEME)- Command Structure: ".$this->data['settings']['pvtcom'].$this->data['settings']['signal']." DeVoiceMe <channel>");
						#$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEVOICEME)- Command Structure: ".$this->data['settings']['dcccom'].$this->data['settings']['signal']." DeVoiceMe <channel>");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEVOICEME)- Command Structure: ".$this->data['settings']['chancom'].$this->data['settings']['signal']." DeVoiceMe");
					} else {
						$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'SAY': {
					if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 1) {
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(SAY)- This command will cause the bot to say a message on a channel");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(SAY)- Command Structure: ".$this->data['settings']['pvtcom'].$this->data['settings']['signal']." say <channel> <message>");
						#$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(SAY)- Command Structure: ".$this->data['settings']['dcccom'].$this->data['settings']['signal']." say <channel> <message>");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(SAY)- Command Structure: ".$this->data['settings']['chancom'].$this->data['settings']['signal']." say <message>");
					} else {
						$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'ACT': {
					if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 1) {
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(ACT)- This command will cause the bot to do a action on a channel");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(ACT)- Command Structure: ".$this->data['settings']['pvtcom'].$this->data['settings']['signal']." act <channel> <action>");
						#$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(ACT)- Command Structure: ".$this->data['settings']['dcccom'].$this->data['settings']['signal']." act <channel> <action>");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(ACT)- Command Structure: ".$this->data['settings']['chancom'].$this->data['settings']['signal']." act <action>");
					} else {
						$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'ACCOUNT': {
					if ($this->_core_islogged($id,$user) == true) {
						if (count($hcmds) >= 2) {
							switch (strtoupper($hcmds[1])) {
								case 'CHGPASS': {
									$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(ACCOUNT)-(CHGPASS)- This Command will allow you to change your password");
									$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(ACCOUNT)-(CHGPASS)- Command Structure: ".$this->data['settings']['pvtcom'].$this->data['settings']['signal']." account chgpass <old pass> <new pass>");
									break;
								} 
								case 'MSGTYPE': {
									$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(ACCOUNT)-(MSGTYPE)- This Command will allow you to change your Message Type");
									$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(ACCOUNT)-(MSGTYPE)- Command Structure: ".$this->data['settings']['pvtcom'].$this->data['settings']['signal']." account msgtype <notice/msg>");
									break;
								}
								default: {
									$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(ACCOUNT)- The help topic account ".$hcmds[1]." is not in the database");
									break;
								}
							}
						} else {
							$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(ACCOUNT)- This Command will allow the user to do some modificatios to their account");
							$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(ACCOUNT)- Command Structure: ".$this->data['settings']['pvtcom'].$this->data['settings']['signal']." account <chgpass/msgtype>");
							$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(ACCOUNT)- Topics available: chgpass msgtype");
						}
					} else {
						$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOTLOGGED');
					}
					break;
				}
				case 'LOGOUT': {
					if ($this->_core_islogged($id,$user) == true) {
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(LOGOUT)- This Command will logout from the bot, this is the only command that works with users that is allowed in channel");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(LOGOUT)- Command Structure: ".$this->data['settings']['pvtcom'].$this->data['settings']['signal']." logout");
						//$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(LOGOUT)- Command Structure: ".$this->data['settings']['dcccom'].$this->data['settings']['signal']." logout");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(LOGOUT)- Command Structure: ".$this->data['settings']['chancom'].$this->data['settings']['signal']." logout");
					} else {
						$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOTLOGGED');
					}
					break;
				}
				case 'LOGIN': {
					if ($this->_core_islogged($id,$user) == false) {
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(LOGIN)- This Command will login to the bot, should the username and password be right");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(LOGIN)- Command Structure: ".$this->data['settings']['pvtcom'].$this->data['settings']['signal']." login <username> <password>");
					} else {
						$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','LOGGED');
					}
					break;
				}
				case 'REGISTER': {
					if ($this->_core_islogged($id,$user) == false) {
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(REGISTER)- This Command will register a user tot he bot if that username doesn't already exists");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(REGISTER)- Command Structure: ".$this->data['settings']['pvtcom'].$this->data['settings']['signal']." register <username> <password>");
					} else {
						$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV','LOGGED');
					}
					break;
				}
				case 'HELP': {
					$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(HELP)- This Command Displays The Help System and Certain Command information");
					$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(HELP)- Command Structure: ".$this->data['settings']['pvtcom'].$this->data['settings']['signal']." help <channel> <topic>");
					#$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(HELP)- Command Structure: ".$this->data['settings']['dcccom'].$this->data['settings']['signal']." help <channel> <topic>");
					$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(HELP)- Command Structure: ".$this->data['settings']['chancom'].$this->data['settings']['signal']." help <topic>");
					break;
				}
				case 'WHOIS': {
					$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(WHOIS)- This Command will send you a whois on the <nick> you choose");
					$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(WHOIS)- Command Structure: ".$this->data['settings']['pvtcom'].$this->data['settings']['signal']." whois <channel> <nick>");
					#$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(WHOIS)- Command Structure: ".$this->data['settings']['dcccom'].$this->data['settings']['signal']." whois <channel> <nick>");
					$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(WHOIS)- Command Structure: ".$this->data['settings']['chancom'].$this->data['settings']['signal']." whois <nick>");
					break;
				}
				case 'WHOAMI': {
					$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(WHOAMI)- This Command will send you a whois on your current logged in user account");
					$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(WHOAMI)- Command Structure: ".$this->data['settings']['pvtcom'].$this->data['settings']['signal']." whoami <channel>");
					#$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(WHOAMI)- Command Structure: ".$this->data['settings']['dcccom'].$this->data['settings']['signal']." whoami <channel>");
					$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(WHOAMI)- Command Structure: ".$this->data['settings']['chancom'].$this->data['settings']['signal']." whoami");
					break;
				}
				default: {
					$this->_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"The help topic ".$hcmds[0]." is not in the database");
					break;
				}
			}			
		} else {
			$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"The bot has the following Commands Available");
			if ($this->_core_islogged($id,$user) == true) {
				if ($this->_core_get_access_logged($id,$user,$chan,'GLOBAL') >= 7) {
					$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"Creator Level Access (7) Only (Due to dangerous level to bot and system):");
					$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"Raw Rawdb");
				}
				if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 6) {
					#Master & Creator Commands 6/7 Global, 6 Server, 6 Channel
					$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"Master Level Access (6):");
					if ($this->_core_get_access_logged($id,$user,$chan,'GLOBAL') >= 6) {
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"Exit Rehash Settings Server User");
					}
					if ($this->_core_get_access_logged($id,$user,$chan,'SERVER') >= 6) {
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"Quit Channel");
					}
					if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 6) {
						#nothing atm
					}
				}
				if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 5) {
					#Owner Commands - 5 Global, 5 Server, 5 CHANNEL
					$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"Owner Level Access (5):");
					if ($this->_core_get_access_logged($id,$user,$chan,'GLOBAL') >= 5) {
						#nothing atm
					}
					if ($this->_core_get_access_logged($id,$user,$chan,'SERVER') >= 5) {
						#nothing atm
					}
					if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 5) {
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"MOwner Owner MDeOwner DeOwner Ownerme DeOwnerme");
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"MProtect Protect MDeProtect DeProtect");
					}
				}
				if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 4) {
					#Protected Commands - 4 Global, 4 Server, 4 CHANNEL
					$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"Protected Level Access (4):");
					if ($this->_core_get_access_logged($id,$user,$chan,'GLOBAL') >= 4) {
						#nothing atm
					}
					if ($this->_core_get_access_logged($id,$user,$chan,'SERVER') >= 4) {
						#nothing atm
					}
					if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 4) {
						$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"Protectme DeProtectme"); #Access
					}
				}
				if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 3) {
					#Op Commands - 3 Global, 3 Server, 3 CHANNEL
					$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"Op Level Access (3):");
					$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"MOp Op MDeOp Opme DeOpme");
					$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"MHalfop Halfop MDeHalfop DeHalfop");
				}
				if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 2) {
					#Half-Op Commands - 2 Global, 2 Server, 2 CHANNEL
					$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"Half-Op Level Access (2):");
					$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"Halfopme DeHalfopme MVoice Voice MDeVoice DeVoice");
					#Channel Kick Ban
				}
				if ($this->_core_get_access_logged($id,$user,$chan,'CHANNEL') >= 1) {
					#Voice Commands - 1 Global, 1 Server, 1 CHANNEL
					$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"Voice Level Access (1):");
					$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"Voiceme DeVoiceme Say Act");
				}
				#Logged in with - 0 Global, 0 Server, 0 CHANNEL
				$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"Logged In Access (0):");
				$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"Account Logout");
			} else {
				#Logged out with - 0 Global, 0 Server, 0 Channel
				$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"Logged out Access (0):");
				$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"Login Register");
			}
			#Anyone Commands - 0 Global, 0 Server, 0 Channel
			$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"Anyone Can Access (0):");
			$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"Help Whoami Whois");
			$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"Pvt Command: ".$this->data['settings']['pvtcom'].$this->data['settings']['signal'].", Channel Command: ".$this->data['settings']['chancom'].$this->data['settings']['signal']);
		}
		$this->_core_buildmsg($id,'HELP',$user,$chan,'PRIV',"End of ".$this->data['settings']['botname']." help system");
	}
	
	private function _core_parse_data($id,$data) {
		$this->_screen($id,'in',$data);
		$indata = explode(" ",$data);
		$rawdata = explode(" ",$data);
		$indata = str_replace(":","",$indata);
		$address = explode("!",$indata[0]);
		$sender = $address[0];
		if ($indata[0] == 'PING') {
			$this->_core_sts($id,"PONG :".$indata[1]);
			$this->sdata['cons'][$id]['lastping'] = time();
		} elseif ($indata[0] == 'ERROR') {
			if (($this->sdata['cons'][$id]['lastcmd'] == 'QUIT') or ($this->sdata['cons'][$id]['lastcmd'] == 'EXIT') or ($this->sdata['cons'][$id]['lastcmd'] == 'RELOAD')) {
				socket_close($this->sdata['cons'][$id]['socket']);
				$this->sdata['cons'][$id]['enabled'] = 'disabled';
			} else {
				socket_close($this->sdata['cons'][$id]['socket']);
				$this->_core_connect($id);
				$this->sdata['cons'][$id]['lastping'] = time();
			}
		} elseif (count($indata) >= 2) {
			switch ($indata[1]) {
				//Start the numerics
				case '001': {
					//$this->_sprint($id." Numeric 001 - Welcome to server",'debug',false);
					$this->sdata['cons'][$id]['networkname'] = $indata[6];
					$this->sdata['cons'][$id]['connectumask'] = $indata[9];
					$this->_core_sts($id,"MODE ".$this->sdata['cons'][$id]['nick']." +B");
					$this->_core_operupcheck($id);
					if ($this->sdata['cons'][$id]['identified'] == 2) {
						$this->_core_autojoinchans($id);
					}
					break;
				}
				case '002': {
					//$this->_sprint($id." Numeric 002 - host is server and version",'debug',false);
					break;
				}
				case '003': {
					//$this->_sprint($id." Numeric 003 - created",'debug',false);
					break;
				}
				case '004': {
					//$this->_sprint($id." Numeric 004 - server var usermode charmod");
					$this->sdata['cons'][$id]['connection']['address'] = $indata[3];
					$this->sdata['cons'][$id]['connectaddress'] = $indata[3];
					$this->sdata['cons'][$id]['sversion'] = $indata[4];
					$this->sdata['cons'][$id]['connectumodes'] = $indata[5];
					$this->sdata['cons'][$id]['connectcmodes'] = $indata[6];
					//$this->_core_modeprocessor_user($id,'umode',$indata[5]);
					break;
				}
				case '005': {
					//$this->_sprint($id." Numeric 005 - map",'debug',false);
					$i = 0;
					while ($i < count($indata)) {
						$tmpdata = explode("=",$indata[$i]);
						switch ($tmpdata[0]) {
							case '005': {
								break;
							}
							case 'are': {
								break;
							}
							case 'supported': {
								break;
							}
							case 'by': {
								break;
							}
							case 'this': {
								break;
							}
							case 'server': {
								break;
							}
							case $this->sdata['cons'][$id]['nick']: {
								break;
							}
							case $this->sdata['cons'][$id]['connection']['address']: {
								break;
							}
							case 'UHNAMES': {
								$this->sdata['cons'][$id]['uhnames'] = true;
								break;
							}
							case 'MAXCHANNELS': {
								$this->sdata['cons'][$id]['maxchannels'] = $tmpdata[1];
								break;
							}
							case 'CHANLIMIT': {
								$this->sdata['cons'][$id]['chanlimit'] = $tmpdata[1];
								break;
							}
							case 'MAXLIST': {
								$this->sdata['cons'][$id]['maxlist'] = $tmpdata[1];
								break;
							}
							case 'NICKLEN': {
								$this->sdata['cons'][$id]['nicklen'] = $tmpdata[1];
								break;
							}
							case 'CHANNELLEN': {
								$this->sdata['cons'][$id]['channellen'] = $tmpdata[1];
								break;
							}
							case 'TOPICLEN': {
								$this->sdata['cons'][$id]['topiclen'] = $tmpdata[1];
								break;
							}
							case 'KICKLEN': {
								$this->sdata['cons'][$id]['kicklen'] = $tmpdata[1];
								break;
							}
							case 'AWAYLEN': {
								$this->sdata['cons'][$id]['awaylen'] = $tmpdata[1];
								break;
							}
							case 'MAXTARGETS': {
								$this->sdata['cons'][$id]['maxtargets'] = $tmpdata[1];
								break;
							}
							case 'MODES': {
								$this->sdata['cons'][$id]['modespl'] = $tmpdata[1];
								break;
							}
							case 'CHANTYPES': {
								$this->sdata['cons'][$id]['chantypes'] = $tmpdata[1];
								break;
							}
							case 'PREFIX': {
								$this->sdata['cons'][$id]['prefix'] = $tmpdata[1];
								break;
							}
							case 'CHANMODES': {
								$this->sdata['cons'][$id]['chanmodes'] = $tmpdata[1];
								break;
							}
							case 'EXTBAN': {
								$this->sdata['cons'][$id]['extban'] = $tmpdata[1];
								break;
							}
							case 'WATCH': {
								$this->sdata['cons'][$id]['watch'] = $tmpdata[1];
								break;
							}
							case 'WATCHOPTS': {
								$this->sdata['cons'][$id]['watchopts'] = $tmpdata[1];
								break;
							}
							case 'NAMESX': {
								$this->sdata['cons'][$id]['namesx'] = true;
								break;
							}
							case 'SAFELIST': {
								$this->sdata['cons'][$id]['safelist'] = true;
								break;
							}
							case 'HCN': {
								$this->sdata['cons'][$id]['hcn'] = true;
								break;
							}
							case 'WALLCHOPS': {
								$this->sdata['cons'][$id]['wallchops'] = true;
								break;
							}
							case 'SILENCE': {
								$this->sdata['cons'][$id]['silence'] = $tmpdata[1];
								break;
							}
							case 'NETWORK': {
								$this->sdata['cons'][$id]['network'] = $tmpdata[1];
								break;
							}
							case 'CASEMAPPING': {
								$this->sdata['cons'][$id]['casemapping'] = $tmpdata[1];
								break;
							}
							case 'ELIST': {
								$this->sdata['cons'][$id]['elist'] = $tmpdata[1];
								break;
							}
							case 'STATUSMSG': {
								$this->sdata['cons'][$id]['statusmsg'] = $tmpdata[1];
								break;
							}
							case 'EXCEPTS': {
								$this->sdata['cons'][$id]['excepts'] = true;
								break;
							}
							case 'INVEX': {
								$this->sdata['cons'][$id]['invex'] = true;
								break;
							}
							case 'CMDS': {
								$this->sdata['cons'][$id]['cmds'] = $tmpdata[1];
								break;
							}
							default: {
								$this->_sprint($id." Numeric 005 unknown ".print_r($tmpdata,true),'debug',false);
								break;
							}
						}
						$i += 1;
					}
					break;
				}
				
				case '007': {
					//$this->_sprint($id." Numeric 007 - end of map",'debug',false);
					break;
				}
				case '008': {
					//$this->_sprint($id." Numeric 008 - num - server notice mask",'debug',false);
					if ($indata[2] == $this->sdata['cons'][$id]['nick']) {
						//$this->_core_modeprocessor_user($id,'smask',$indata[6]);
						$blarg = 1;
					}
					break;
				}
				case '010': {
					//$this->_sprint($id." Numeric 010 - JumpServer",'debug',false);
					$this->sdata['cons'][$id]['connection']['address'] = $indata[3];
					$this->sdata['cons'][$id]['connection']['port'] = $indata[4];
					break;
				}
				
				case '211': {
					$this->_sprint($id." Numeric 211 - connection sendq sentmsg sentbyte recdmsg recdbyte :open",'debug',false);
					break;
				}
				case '212': {
					$this->_sprint($id." Numeric 212 - command uses bytes",'debug',false);
					break;
				}
				case '213': {
					$this->_sprint($id." Numeric 213 - C address * server port class",'debug',false);
					break;
				}
				case '214': {
					$this->_sprint($id." Numeric 214 - N address * server port class",'debug',false);
					break;
				}
				case '215': {
					$this->_sprint($id." Numeric 215 - I ipmask * hostmask port class",'debug',false);
					break;
				}
				case '216': {
					$this->_sprint($id." Numeric 216 - k address * username details",'debug',false);
					break;
				}
				case '217': {
					$this->_sprint($id." Numeric 217 - P port ?? ??",'debug',false);
					break;
				}
				case '218': { 
					$this->_sprint($id." Numeric 218 - Y class ping freq maxconnect sendq",'debug',false);
					break;
				}
				case '219': {
					//$this->_sprint($id." Numeric 219 - End of /stats report",'debug',false);
					break;
				}
				
				case '221': {
					if ($indata[2] == $this->sdata['cons'][$id]['nick']) {
						$this->_core_modeprocessor_user($id,'umode',$indata[3]);
					}
					break;
				}
				case '222': {
					$this->_sprint($id." Numeric 222 - mask :comment",'debug',false);
					break;
				}
				case '223': {
					$this->_sprint($id." Numeric 223 - E hostmask * username ?? ??",'debug',false);
					break;
				}
				case '224': {
					$this->_sprint($id." Numeric 224 - D ipmask * username ?? ??",'debug',false);
					break;
				}
				
				case '241': {
					$this->_sprint($id." Numeric 241 - L address * server ?? ??",'debug',false);
					break;
				}
				case '242': {
					$this->_sprint($id." Numeric 242 - :Server Up num days, time",'debug',false);
					break;
				}
				case '243': {
					if ($indata[6] == $this->data['data'][$id]['server']['botoper']) {
						$this->_core_modeprocessor_user($id,'oflags','+'.$indata[7]);
					}
					break;
				}
				case '244': {
					$this->_sprint($id." Numeric 244 - H address * server ?? ??",'debug',false);
					break;
				}
				
				case '247': {
					$this->_sprint($id." Numeric 247 - G address timestamp :reason",'debug',false);
					break;
				}
				case '248': {
					$this->_sprint($id." Numeric 248 - U host * ?? ?? ??",'debug',false);
					break;
				}
				case '249': {
					$this->_sprint($id." Numeric 249 - :info",'debug',false);
					break;
				}
				case '250': {
					//$this->_sprint($id." Numeric 250 - highest connection count",'false',false);
					break;
				}
				case '251': {
					//$this->_sprint($id." Numeric 251 - there are x users online",'debug',false);
					break;
				}
				case '252': {
					//$this->_sprint($id." Numeric 252 - number of operators",'debug',false);
					break;
				}
				case '253': {
					//$this->_sprint($id." Numeric 253 - number of unknown connections",'debug',false);
					break;
				}
				case '254': {
					//$this->_sprint($id." Numeric 254 - number of channels",'debug',false);
					break;
				}
				case '255': {
					//$this->_sprint($id." Numeric 255 - have x clients and x servers",'debug',false);
					break;
				}
				case '256': {
					$this->_sprint($id." Numeric 256 - :Administrative info about server",'debug',false);
					break;
				}
				case '257': {
					$this->_sprint($id." Numeric 257 - :info",'debug',false);
					break;
				}
				case '258': {
					$this->_sprint($id." Numeric 258 - :info",'debug',false);
					break;
				}
				case '259': {
					$this->_sprint($id." Numeric 259 - :info",'debug',false);
					break;
				}
				
				case '263': {
					$this->_sprint($id." Numeric 263 - :Server load is temporarily too heavy. Please wait a while and try again",'debug',false);
					break;
				}
				
				case '265': {
					//$this->_sprint($id." Numeric 265 - :Current local users: curr Max: max",'debug',false);
					break;
				}
				case '266': {
					//$this->_sprint($id." Numeric 266 - :Current global users: curr Max: max",'debug',false);
					break;
				}
				
				case '271': {
					$this->_sprint($id." Numeric 271 - nick mask",'debug',false);
					break;
				}
				case '272': {
					$this->_sprint($id." Numeric 272 - nick :End of Silence List",'debug',false);
					break;
				}
				
				case '280': {
					$this->_sprint($id." Numeric 280 - address timestamp reason",'debug',false);
					break;
				}
				case '281': {
					$this->_sprint($id." Numeric 281 - :End of G-Line List",'debug',false);
					break;
				}
				
				case '290': {
					$this->_sprint($id." Numeric 290 - :num ***** topic *****",'debug',false);
					break;
				}
				case '291': {
					$this->_sprint($id." Numeric 291 - :text",'debug',false);
					break;
				}
				case '292': {
					$this->_sprint($id." Numeric 292 - : ***** Go to #dalnethelp if you have any further questions *****",'debug',false);
					break;
				}
				case '293': {
					$this->_sprint($id." Numeric 293 - :text",'debug',false);
					break;
				}
				case '294': {
					$this->_sprint($id." Numeric 294 - :Your help-request has been forwared to Help Operators",'debug',false);
					break;
				}
				
				case '298': {
					$this->_sprint($id." Numeric 298 - nick :Nickname conflict has been resolved",'debug',false);
					break;
				}
				
				case '301': {
					$this->_sprint($id." Numeric 301 - nick :away",'debug',false);
					break;
				}
				case '302': {
					$this->_sprint($id." Numeric 302 - :userhosts",'debug',false);
					break;
				}
				case '303': {
					$this->_sprint($id." Numeric 303 - :nicknames",'debug',false);
					break;
				}
				case '304': {
					$this->_sprint($id." Numeric 304 - Unknown Raw Code",'debug',false);
					break;
				}
				case '305': {
					$this->_sprint($id." Numeric 305 - :You are no longer marked as being away",'debug',false);
					break;
				}
				case '306': {
					$this->_sprint($id." Numeric 306 - :You have been marked as being away",'debug',false);
					break;
				}
				case '307': {
					$this->_sprint($id." Numeric 307 - :userips",'debug',false);
					break;
				}
				
				case '310': {
					$this->_sprint($id." Numeric 310 - nick :looks very helpful",'debug',false);
					break;
				}
				case '311': {
					$this->_sprint($id." Numeric 311 - nick username address * :info",'debug',false);
					break;
				}
				case '312': {
					$this->_sprint($id." Numeric 312 - nick server :info",'debug',false);
					break;
				}
				case '313': {
					$this->_sprint($id." Numeric 313 - nick :is an IRC Operator",'debug',false);
					break;
				}
				case '314': {
					$this->_sprint($id." Numeric 314 - nick username address * :info",'debug',false);
					break;
				}
				case '315': {
					$this->_sprint($id." Numeric 315 - request :End of /WHO list",'debug',false);
					break;
				}
				
				case '317': {
					$this->_sprint($id." Numeric 317 - nick seconds signon :info",'debug',false);
					break;
				}
				case '318': {
					$this->_sprint($id." Numeric 318 - request :End of /WHOIS list.",'debug',false);
					break;
				}
				case '319': {
					$this->_sprint($id." Numeric 319 - nick :channels",'debug',false);
					break;
				}
				
				case '321': {
					$this->_sprint($id." Numeric 321 - Channel :Users Name",'debug',false);
					break;
				}
				case '322': {
					$this->_sprint($id." Numeric 322 - channel users :topic",'debug',false);
					break;
				}
				case '323': {
					$this->_sprint($id." Numeric 323 - :End of /LIST",'debug',false);
					break;
				}
				case '324': {
					$this->_core_modeprocessor_chan($id,$sender,$indata[3],$indata[4]);
					if ($this->_core_chanmodes($id,$indata[3]) != 'NULL') {
						$this->_core_sts($id,"MODE ".$indata[3]." ".$this->_core_chanmodes($id,$indata[3]));
					}
					break;
				}
				
				case '328': {
					$this->_sprint($id." Numeric 328 - channel :url",'debug',false);
					break;
				}
				case '329': {
					//$this->_sprint($id." Numeric 329 - Channel Creation time",'debug',false);
					//-1-> :chewy.chewynet.co.uk 329 ^chewy_god^ #home 1280495592
					//DEBUG: ChewyNet ['chewy.chewynet.co.uk', '329', '^chewy_god^', '#home', '1280495592']
					break;
				}
				
				case '331': {
					$this->_sprint($id." Numeric 331 - No topic is set",'debug',false);
					break;
				}
				case '332': {
					$this->_sprint($id." Numeric 332 - Topic",'debug',false);
					break;
				}
				case '333': {
					$this->_sprint($id." Numeric 333 - Nickname time",'debug',false);
					break;
				}
				
				case '340': {
					$this->_sprint($id." Numeric 340 - nick :nickname=+user@IP.address",'debug',false);
					break;
				}
				case '341': {
					$this->_sprint($id." Numeric 341 - nick channel",'debug',false);
					break;
				}
				
				case '346': {
					//DEBUG: ChewyNet ['chewy.chewynet.co.uk', '346', '^chewy_god^', '#home,' 'doe!*@*', 'chewyb_13', '1280533501']
					//$this->_sprint($id." Numeric 346 - Channel Invex List",'debug',false);
					$this->sdata['cons'][$id]['chans'][$indata[3]]['INVEX'][$indata[4]] = true;
					break;
				}
				case '347': {
					//$this->_sprint($id." Numeric 347 - End of Channel Invex List",'debug',false);
					break;
				}
				case '348': {
					//DEBUG: ChewyNet ['chewy.chewynet.co.uk', '348', '^chewy_god^', '#home', 'blond!*@*', 'chewyb_13', '1280533501']
					//$this->_sprint($id." Numeric 348 - Channel Exception List",'debug',false);
					$this->sdata['cons'][$id]['chans'][$indata[3]]['EXCEPT'][$indata[4]] = true;
					break;
				}
				case '349': {
					//$this->_sprint($id." Numeric 349 - End of Channel Exception List",'debug',false);
					break;
				}
				
				case '351': {
					$this->_sprint($id." Numeric 351 - version.debug server :info");
					break;
				}
				case '352': {
					$this->_sprint($id." Numeric 352 - channel username address server nick flags :hops info",'debug',false);
					break;
				}
				case '353': {
					//$this->_sprint($id." Numeric 353 - Names",'debug',false);
					$tmpdata = array_slice($indata,5);
					$i = 0;
					while ($i < count($tmpdata)) {
						if ($tmpdata[$i] == '') { break; }
						switch ($tmpdata[$i][0]) {
							case '~': {
								$tmpuser = substr($tmpdata[$i],1);
								$tmpmode = 'FOP';
								break;
							}
							case '&': {
								$tmpuser = substr($tmpdata[$i],1);
								$tmpmode = 'SOP';
								break;
							}
							case '@': {
								$tmpuser = substr($tmpdata[$i],1);
								$tmpmode = 'OP';
								break;
							}
							case '%': {
								$tmpuser = substr($tmpdata[$i],1);
								$tmpmode = 'HOP';
								break;
							}
							case '+': {
								$tmpuser = substr($tmpdata[$i],1);
								$tmpmode = 'VOICE';
								break;
							}
							default: {
								$tmpuser = $tmpdata[$i];
								$tmpmode = 'REGULAR';
								break;
							}
						}
						$this->sdata['cons'][$id]['chans'][$indata[4]]['users'][$tmpuser]['inchan'] = true;
						$this->sdata['cons'][$id]['chans'][$indata[4]]['users'][$tmpuser][$tmpmode] = true;
						$i += 1;
					}
					break;
				}
				
				case '364': {
					$this->_sprint($id." Numeric 364 - server hub :hops info",'debug',false);
					break;
				}
				case '365': {
					$this->_sprint($id." Numeric 365 - mask :End of /LINKS list.",'debug',false);
					break;
				}
				case '366': {
					//$this->_sprint($id." Numeric 366 - End of Names",'debug',false);
					break;
				}
				case '367': {
					//DEBUG: ChewyNet ['chewy.chewynet.co.uk', '367', '^chewy_god^', '#home', 'blarg!*@*', 'chewyb_13', '1280533501']
					//$this->_sprint($id." Numeric 367 - Channel Ban List",'debug',false);
					$this->sdata['cons'][$id]['chans'][$indata[3]]['BAN'][$indata[4]] = true;
					break;
				}
				case '368': {
					//$this->_sprint($id." Numeric 368 - End of Channel Ban List",'debug',false);
					break;
				}
				case '369': {
					$this->_sprint($id." Numeric 369 - request :End of WHOWAS",'debug',false);
					break;
				}
				
				case '371': {
					$this->_sprint($id." Numeric 371 - :info",'debug',false);
					break;
				}
				case '372': {
					//$this->_sprint($id." Numeric 372 - MOTD info",'debug',false);
					break;
				}
				
				case '374': {
					$this->_sprint($id." Numeric 374 - :End of /INFO list.",'debug',false);
					break;
				}
				case '375': {
					//$this->_sprint($id." Numeric 375 - server motd",'debug',false);
					break;
				}
				case '376': {
					//$this->_sprint($id." Numeric 376 - end of motd",'debug',false);
					break;
				}
				case '377': {
					$this->_sprint($id." Numeric 377 - info",'debug',false);
					break;
				}
				case '378': {
					$this->_sprint($id." Numeric 378 - info",'debug',false);
					break;
				}
				
				case '381': {
					if ($this->sdata['cons'][$id]['isoper'] == 1) {
						$this->sdata['cons'][$id]['isoper'] = 2;
						$this->_core_sts($id,"STATS O");
					}
					break;
				}
				case '382': {
					$this->_sprint($id." Numeric 382 - file :Rehashing",'debug',false);
					break;
				}
				
				case '391': {
					$this->_sprint($id." Numeric 391 - server :time",'debug',false);
					break;
				}
				
				case '401': {
					$this->_sprint($id." Numeric 401 - No such nick",'debug',false);
					break;
				}
				case '402': {
					$this->_sprint($id." Numeric 402 - server :No such server",'debug',false);
					break;
				}
				case '403': {
					$this->_sprint($id." Numeric 403 - No such channel",'debug',false);
					break;
				}
				case '404': {
					$this->_sprint($id." Numeric 404 - channel :Cannot send to channel",'debug',false);
					break;
				}
				case '405': {
					$this->_sprint($id." Numeric 405 - channel :You have joined too many channels",'debug',false);
					break;
				}
				case '406': {
					$this->_sprint($id." Numeric 406 - nickname :There was no such nickname",'debug',false);
					break;
				}
				case '407': {
					$this->_sprint($id." Numeric 407 - target :Duplicate recipients. No message delivered",'debug',false);
					break;
				}
				case '408': { 
					$this->_sprint($id." Numeric nickname #channel :You cannot use colors on this chanenl. Not sent: text",'debug',false);
					break;
				}
				case '409': {
					$this->_sprint($id." Numeric 409 - :No origin specified",'debug',false);
					break;
				}
				
				case '411': {
					$this->_sprint($id." Numeric 411 - :No recipient given (command)",'debug',false);
					break;
				}
				case '412': {
					$this->_sprint($id." Numeric 412 - :No text to send",'debug',false);
					break;
				}
				case '413': {
					$this->_sprint($id." Numeric 413 - mask :No toplevel domain specified",'debug',false);
					break;
				}
				case '414': {
					$this->_sprint($id." Numeric 414 - mask :Wildcard in toplevel Domain",'debug',false);
					break;
				}
				
				case '416': {
					$this->_sprint($id." Numeric 416 - command :Too many lines in the output, restrict your query",'debug',false);
					break;
				}
				
				case '421': {
					$this->_sprint($id." Numeric 421 - command :Unknown command",'debug',false);
					break;
				}
				case '422': {
					//$this->_sprint($id." Numeric 422 - MOTD missing",'debug',false');
					break;
				}
				case '423': {
					$this->_sprint($id." Numeric 423 - server :No administrative info available",'debug',false);
					break;
				}
				
				case '431': {
					$this->_sprint($id." Numeric 431 - :No nickname given",'debug',false);
					break;
				}
				case '432': {
					$this->_sprint($id." Numeric 432 - nickname :Erroneus Nickname",'debug',false);
					break;
				}
				case '433': {
					if ($indata[3] == $this->sdata['cons'][$id]['nick']) {
						switch ($this->sdata['cons'][$id]['nick']) {
							case $this->data['data'][$id]['server']['nick']: {
								$this->_core_sts($id,"NICK ".$this->data['data'][$id]['server']['bnick']);
								$this->sdata['cons'][$id]['nick'] = $this->data['data'][$id]['server']['bnick'];
								break;
							}
							case $this->data['data'][$id]['server']['bnick']: {
								$this->_core_sts($id,"NICK ".$this->data['data'][$id]['server']['nick']);
								$this->sdata['cons'][$id]['nick'] = $this->data['data'][$id]['server']['nick'];
								break;
							}
						}
					}
					break;
				}
				
				case '436': {
					$this->_sprint($id." Numeric 436 - nickname :Nickname collision KILL",'debug',false);
					break;
				}
				case '437': {
					$this->_sprint($id." Numeric 437 - channel :Cannot change nickname while banned on channel",'debug',false);
					break;
				}
				case '438': {
					$this->_sprint($id." Numeric 438 - nick :Nick change too fast. Please wait sec seconds.",'debug',false);
					break;
				}
				case '439': {
					$this->_sprint($id." Numeric 439 - target :Target change too fast. Please wait sec seconds.",'debug',false);
					break;
				}
				
				case '441': {
					$this->_sprint($id." Numeric 441 - nickname channel :They aren't on that channel",'debug',false);
					break;
				}
				case '442': {
					$this->_sprint($id." Numeric 442 - You are not on that channel",'debug',false);
					break;
				}
				case '443': {
					$this->_sprint($id." Numeric 443 - nickname channel :is already on channel",'debug',false);
					break;
				}
				
				case '445': {
					$this->_sprint($id." Numeric 445 - :SUMMON has been disabled",'debug',false);
					break;
				}
				case '446': {
					$this->_sprint($id." Numeric 446 - :USERS has been disabled",'debug',false);
					break;
				}
				
				case '451': {
					$this->_sprint($id." Numeric 451 - command :Register first.",'debug',false);
					break;
				}
				
				case '455': {
					$this->_sprint($id." Numeric 455 - :Your username ident contained the invalid character(s) chars and has been changed to new. Please use only the characters 0-9 a-z A-Z _ - or . in your username. Your username is the part before the @ in your email address.",'debug',false);
					break;
				}
				
				case '461': {
					$this->_sprint($id." Numeric 461 - command :Not enough parameters",'debug',false);
					break;
				}
				case '462': {
					$this->_sprint($id." Numeric 462 - :You may not reregister",'debug',false);
					break;
				}
				
				case '467': {
					$this->_sprint($id." Numeric 467 - channel :Channel key already set",'debug',false);
					break;
				}
				case '468': {
					$this->_sprint($id." Numeric 468 - channel :Only servers can change that mode",'debug',false);
					break;
				}
				
				case '471': {
					//$this->_sprint($id." Numeric 471 - channel :Cannot join channel (+l)",'debug',false);
					if ($this->sdata['cons'][$id]['identified'] == 2) {
						$this->_core_sts($id,"PRIVMSG ChanServ :INVITE ".$indata[3]);
						$this->_core_joinchan($id,$indata[3]);
					} elseif ($this->sdata['cons'][$id]['isoper'] == 2) {
						$this->_core_sts($id,"SAJOIN ".$this->sdata['cons'][$id]['nick']." ".$indata[3]);
					}
					break;
				}
				case '472': {
					$this->_sprint($id." Numeric 472 - char :is unknown mode char to me",'debug',false);
					break;
				}
				case '473': {
					//$this->_sprint($id." Numeric 473 - channel :Cannot join channel (+i)",'debug',false);
					if ($this->sdata['cons'][$id]['identified'] == 2) {
						$this->_core_sts($id,"PRIVMSG ChanServ :INVITE ".$indata[3]);
						$this->_core_joinchan($id,$indata[3]);
					} elseif ($this->sdata['cons'][$id]['isoper'] == 2) {
						$this->_core_sts($id,"SAJOIN ".$this->sdata['cons'][$id]['nick']." ".$indata[3]);
					}
					break;
				}
				case '474': {
					//$this->_sprint($id." Numeric 474 - channel :Cannot join channel (+b)",'debug',false);
					if ($this->sdata['cons'][$id]['identified'] == 2) {
						$this->_core_sts($id,"PRIVMSG ChanServ :UNBAN ".$indata[3]);
						$this->_core_joinchan($id,$indata[3]);
					} elseif ($this->sdata['cons'][$id]['isoper'] == 2) {
						$this->_core_sts($id,"SAJOIN ".$this->sdata['cons'][$id]['nick']." ".$indata[3]);
					}
					break;
				}
				case '475': {
					//$this->_sprint($id." Numeric 475 - channel :Cannot join channel (+k)",'debug',false);
					if ($this->sdata['cons'][$id]['identified'] == 2) {
						$this->_core_sts($id,"PRIVMSG ChanServ :INVITE ".$indata[3]);
						$this->_core_joinchan($id,$indata[3]);
					} elseif ($this->sdata['cons'][$id]['isoper'] == 2) {
						$this->_core_sts($id,"SAJOIN ".$this->sdata['cons'][$id]['nick']." ".$indata[3]);
					}
					break;
				}
				
				case '477': {
					$this->_sprint($id." Numeric 477 - channel :You need a registered nick to join that channel.",'debug',false);
					break;
				}
				case '478': {
					$this->_sprint($id." Numeric 478 - channel ban :Channel ban/ignore list is full",'debug',false);
					break;
				}
				
				case '481': {
					$this->_sprint($id." Numeric 481 - :Permission Denied- You're not an IRC operator",'debug',false);
					break;
				}
				case '482': {
					$this->_sprint($id." Numeric 482 - channel :You're not a channel operator",'debug',false);
					break;
				}
				case '483': {
					$this->_sprint($id." Numeric 483 - :You can't kill a server!",'debug',false);
					break;
				}
				case '484': {
					$this->_sprint($id." Numeric 484 - nick channel :Cannot kill, kick or deop chanenl service",'debug',false);
					break;
				}
				case '485': {
					$this->_sprint($id." Numeric 485 - channel :Cannot join channel (reason)",'debug',false);
					break;
				}
				
				case '491': {
					$this->_sprint($id." Numeric 491 - :No O-lines for your host",'debug',false);
					break;
				}
				
				case '499': {
					//$this->_sprint($id." Numeric 499 - Not Owner of the channel",'debug',false);
					break;
				}
				
				case '501': {
					$this->_sprint($id." Numeric 501 - :Unknown MODE flag",'debug',false);
					break;
				}
				case '502': {
					$this->_sprint($id." Numeric 502 - :Cant change mode for other users",'debug',false);
					break;
				}
				
				case '510': {
					$this->_sprint($id." Numeric 510 - :You must resolve the nickname conflict before you can proceed",'debug',false);
					break;
				}
				case '511': {
					$this->_sprint($id." Numeric 511 - mask :Your silence list is full",'debug',false);
					break;
				}
				case '512': {
					$this->_sprint($id." Numeric 512 - address :No such gline",'debug',false);
					break;
				}
				case '513': {
					$this->_sprint($id." Numeric 513 - If you can't connect, type /QUOTE PONG code or /PONG code",'debug',false);
					break;
				}
				
				case '600': {
					$this->_sprint($id." Numeric 600 - nick userid host time :logged offline",'debug',false);
					break;
				}
				case '601': {
					$this->_sprint($id." Numeric 601 - nick userid host time :logged online",'debug',false);
					break;
				}
				case '602': {
					$this->_sprint($id." Numeric 602 - nick userid host time :stopped watching",'debug',false);
					break;
				}
				case '603': {
					$this->_sprint($id." Numeric 603 - :You have mine and are on other WATCH entries",'debug',false);
					break;
				}
				case '604': {
					$this->_sprint($id." Numeric 604 - nick userid host time :is online",'debug',false);
					break;
				}
				case '605': {
					$this->_sprint($id." Numeric 605 - nick userid host time :is offline",'debug',false);
					break;
				}
				case '606': {
					$this->_sprint($id." Numeric 606 - :nicklist",'debug',false);
					break;
				}
				
				case '972': {
					//$this->_sprint($id." Numeric 972 - Can't kick user due to +q",'debug',false);
					break;
				}
				
				//Start Normal
				
				case 'JOIN': {
					$this->sdata['cons'][$id]['chans'][$indata[2]]['users'][$sender]['inchan'] = true;
					if ($sender == $this->sdata['cons'][$id]['nick']) {
						if ($this->_core_checkchan($id,$indata[2]) == false) {
							$this->_core_sts($id,"PART ".$indata[2]." :Not supposed to be in here");
						} else {
							$this->_core_sts($id,"MODE ".$indata[2]);
							$this->_core_sts($id,"MODE ".$indata[2]." +b");
							$this->_core_sts($id,"MODE ".$indata[2]." +e");
							$this->_core_sts($id,"MODE ".$indata[2]." +I");
							$this->_core_sts($id,"NAMES ".$indata[2]);
						}
						
					} else {
						//$this->_sprint($id." ".$sender." joined channel ".$indata[2]);
						if ($this->_core_islogged($id,$sender) == true) {
							if (strlen($this->sdata['cons'][$id]['tempdata']) > 0) {
								if ($this->sdata['cons'][$id]['tempdata'][$sender] == 'UHCHANGE') {
									$this->sdata['cons'][$id]['loggedin'][$sender]['umask'] = $indata[0];
								}
								unset($this->sdata['cons'][$id]['tempdata'][$sender]);
							}
						}
					}
					break;				
				}
				case 'PART': {
					unset($this->sdata['cons'][$id]['chans'][$indata[2]]['users'][$sender]['inchan']);
					if ($sender == $this->sdata['cons'][$id]['nick']) {
						if ($this->_core_checkchan($id,$indata[2]) == true) {
							$this->_core_joinchan($id,$indata[2]);
						} else {
							unset($this->sdata['cons'][$id]['chans'][$indata[2]]);
							//$this->_sprint($id." I Parted channel ".$indata[2]);
						}
					} else {
						//$this->_sprint($id." ".$sender." parted channel ".$indata[2]);
						if ($this->_core_islogged($id,$sender) == true) {
							if (count($indata) >= 8) {
								if (($indata[3] == 'Rejoining') and ($indata[4] == 'because') and ($indata[5] == 'of') and ($indata[6] == 'user@host') and ($indata[7] == 'change')) {
									$this->sdata['cons'][$id]['tempdata'][$sender] = 'UHCHANGE';
								}
							}
						}
					}
					break;
				}
				case 'QUIT': {
					if ($sender == $this->sdata['cons'][$id]['nick']) {
						$this->_sprint("I Quit ".$this->data['data'][$id]['server']['servername'],'debug',false);
					} else {
						if ($this->_core_islogged($id,$sender) == true) {
							$this->_sprint("Auto-Logout for ".$sender."(".$this->sdata['cons'][$id]['loggedin'][$sender]['username'].")",'notice',true);
							unset($this->sdata['cons'][$id]['loggedin'][$sender]);
						}
					}
					if (count($this->sdata['cons'][$id]['chans']) > 0) {
						foreach ($this->sdata['cons'][$id]['chans'] as $t1 => $t2) {
							if (count($t2['users']) > 0) {
								foreach ($t2['users'] as $t3 => $t4) {
									if ($t4 == $sender) {
										unset($this->sdata['cons'][$id]['chans'][$t1]['users'][$sender]);
									}
								}
							}
						}
					}
					break;
				}
				case 'KICK': {
					if ($indata[3] == $this->sdata['cons'][$id]['nick']) {
						if ($this->_core_checkchan($id,$indata[2]) == true) {
							$this->_core_joinchan($id,$indata[2]);
						}
					} else {
						$this->_sprint("Another user was kicked from ".$indata[2]." on ".$this->data['data'][$id]['server']['servername'],'debug',false);
					}
					unset($this->sdata['cons'][$id]['chans'][$indata[2]]['users'][$indata[3]]);
					break;
				}
				case 'TOPIC': {
					$this->_sprint($id." TOPIC",'debug',false);
					break;
				}
				case 'WALLOPS': {
					$this->_sprint($id." WALLOPS",'debug',false);
					break;
				}
				case 'INVITE': {
					$this->_sprint($id." INVITE",'debug',false);
					break;
				}
				case 'MODE': {
					if ($indata[2] == $this->sdata['cons'][$id]['nick']) {
						$this->_core_modeprocessor_user($id,'umode',$indata[3]);
					} else {
						$this->_core_modeprocessor_chan($id,$sender,$indata[2],$indata[3]);
						if ($this->_core_chanmodes($id,$indata[2],$indata[3]) != 'NULL') {
							$this->_core_sts($id,"MODE ".$indata[2]." ".$this->_core_chanmodes($id,$indata[2]));
						}
					}
					break;
				}
				case 'NICK': {
					/*
					if (sender == mysockets[sock]['nick']):
						debug(sock,"My nick changed --- AHHHHHHH")
					else:
						if (islogged(sock,sender) == 'TRUE'):
							tmpmask = incom[0].split('!')
							output = incom[2]+"!"
							output = output+tmpmask[1]
							loggedin[sock][incom[2]] = {'username': loggedin[sock][sender]['username'], 'msgtype': loggedin[sock][sender]['msgtype'], 'umask': output}
							del loggedin[sock][sender]
					tmpmodes = ['FOP', 'SOP', 'OP', 'HOP', 'VOICE', 'inchan']
					if (len(mysockets[sock]['channels']) > 0):
						tmpckeys = mysockets[sock]['channels'].keys()
						for tmpckey in tmpckeys:
							if (len(mysockets[sock]['channels'][tmpckey]['users']) > 0):
								tmpuukeys = mysockets[sock]['channels'][tmpckey]['users'].keys()
								for tmpuukey in tmpuukeys:
									if (tmpuukey == sender):
										if (len(mysockets[sock]['channels'][tmpckey]['users'][sender]) > 0):
											tmpukeys = mysockets[sock]['channels'][tmpckey]['users'][sender].keys()
											try: mysockets[sock]['channels'][tmpckey]['users'][incom[2]]
											except: mysockets[sock]['channels'][tmpckey]['users'][incom[2]] = dict()
											for tmpukey in tmpukeys:
												for tmpmode in tmpmodes:
													if (tmpukey == tmpmode):
														mysockets[sock]['channels'][tmpckey]['users'][incom[2]][tmpmode] = mysockets[sock]['channels'][tmpckey]['users'][sender][tmpmode]
										del mysockets[sock]['channels'][tmpckey]['users'][sender]
					*/
					break;
				}
				case 'NOTICE': {
					if ($indata[2] == $this->sdata['cons'][$id]['nick']) {
						$this->_core_command_cmds($id,'PNOTE',$sender,$indata,$rawdata,$address);
					} else {
						$this->_core_command_cmds($id,'CNOTE',$sender,$indata,$rawdata,$address);
					}
					break;
				}
				case 'PRIVMSG': {
					if ($indata[2] == $this->sdata['cons'][$id]['nick']) {
						$this->_core_command_cmds($id,'PMSG',$sender,$indata,$rawdata,$address);
					} else {
						$this->_core_command_cmds($id,'CMSG',$sender,$indata,$rawdata,$address);
					}
					break;
				}
				default: {
					$this->_sprint($id." ".print_r($indata,true),'debug',false);
					$this->_sprint($id." Unknown feature at this momment",'debug',false);
					break;
				}
			}
		} else {
			$this->_sprint("Unknown Length of data",'debug',false);
		}
		return;
	}

	private function _core_array_stripchr($in,$chr) {
		$stripcount = count($in);
		while ($stripcount) {
			$stripcount -= 1;
			$in[$stripcount] = str_replace(chr($chr),'',$in[$stripcount]);
		}
		return $in;
	}
	
	private function _core_array_join($in,$o,$j) {
		return implode($j,$this->_core_array_rearrange($in,$o));
	}
	
	private function _core_array_rearrange($in,$o) {
		$i = 0;
		$out = array();
		while ($o < count($in)) {
			$out[$i] = $in[$o];
			$o += 1;
			$i += 1;
		}
		return $out;
	}
	
	private function _core_chanmodes($id,$chan) {
		$foutput = '';
		foreach ($this->data['data'][$id]['chans'] as $t1 => $t2) {
			if ($t2['channel'] == $chan) {
				if ($this->data['data'][$id]['chans'][$chan]['chanmodes'] != 'NULL') {
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
	
	private function _core_massmodes($id,$user,$chan,$modes) {
		//$this->_sprint("Modes ".print_r($modes,true),'debug',false);
		$modespl = $this->sdata['cons'][$id]['modespl'];
		$tmpusers = array();
		if (count($this->sdata['cons'][$id]['chans'][$chan]['users']) > 0) {
			//$this->_sprint("Users Listing: ".print_r($this->sdata['cons'][$id]['chans'][$chan]['users'],true),'debug',false);
			foreach ($this->sdata['cons'][$id]['chans'][$chan]['users'] as $t1 => $t2) {
				//$this->_sprint("User Details: T1 ".$t1." T2 ".print_r($t2,true),'debug',false);
				if ($modes[2] == 'ALL') {
					array_push($tmpusers,$t1);
				}	elseif ($modes[2] == 'BC') {
					if ($modes[0] == 'ADD') {
						array_push($tmpusers,$t1);
					} else {
						if ($this->_core_islogged($id,$t1) == false) {
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
				$this->_core_sts($id,"MODE ".$chan." ".$outmode.$outputmode." ".$output);
				$l = 0;
				$output = '';
			}
		}
		//$this->_sprint($output,'debug',false);
		$this->_core_sts($id,"MODE ".$chan." ".$outmode.$outputmode." ".$output);
	}	
	
	private function _core_modeprocessor_chan($id,$user,$chan,$data) {
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
							$this->sdata['cons'][$id]['chans'][$chan]['users'][$data[$pos]][$tmpmode] = true;
						} else {
							if (($tmpmode == 'BAN') or ($tmpmode == 'EXCEPT') or ($tmpmode == 'INVEX')) {
								$this->sdata['cons'][$id]['chans'][$chan][$tmpmode][$data[$pos]] = true;
							} else {
								$this->sdata['cons'][$id]['chans'][$chan][$tmpmode] = $data[$pos];
							}
						}
						$pos = $pos + 1;
					} else {
						$this->sdata['cons'][$id]['chans'][$chan]['modes'][$tmpmode] = true;
					}
				}
				if ($mode == 'SUB') {
					if (($tmpmode == 'FOP') or ($tmpmode == 'SOP') or ($tmpmode == 'OP') or ($tmpmode == 'HOP') or ($tmpmode == 'VOICE') or ($tmpmode == 'EXCEPT') or ($tmpmode == 'INVEX') or ($tmpmode == 'BAN') or ($tmpmode == 'LIMIT') or ($tmpmode == 'LINK') or ($tmpmode == 'BANLINK') or ($tmpmode == 'CHANPASS') or ($tmpmode == 'FLOOD') or ($tmpmode == 'JOIN')) {
						if (($tmpmode == 'FOP') or ($tmpmode == 'SOP') or ($tmpmode == 'OP') or ($tmpmode == 'HOP') or ($tmpmode == 'VOICE')) {
							$this->sdata['cons'][$id]['chans'][$chan]['users'][$data[$pos]][$tmpmode] = false;
						} else {
							if (($tmpmode == 'BAN') or ($tmpmode == 'EXCEPT') or ($tmpmode == 'INVEX')) {
								unset($this->sdata['cons'][$id]['chans'][$chan][$tmpmode][$data[$pos]]);
							} else {
								unset($this->sdata['cons'][$id]['chans'][$chan][$tmpmode]);
							}
						}
						$pos = $pos + 1;
					} else {
						$this->sdata['cons'][$id]['chans'][$chan]['modes'][$tmpmode] = false;
					}
				}
			}
			$i = $i + 1;
		}
	}
	
	private function _core_modeprocessor_user($id,$type,$data) {
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
					$this->sdata['cons'][$id][$type][$data[$i]] = true;
				}
				if ($mode == 'SUB') {
					$this->sdata['cons'][$id][$type][$data[$i]] = false;
				}
			}
			$i = $i + 1;
		}
	
	}
	
	private function _core_get_whois($id,$user,$chan,$mode,$otheruser='NULL') {
		global $ch3wyb0t;
		if ($otheruser == 'NULL') {
			if ($this->_core_islogged($id,$user) == true) {
				$userdata = $this->_core_pulluser($this->sdata['cons'][$id]['loggedin'][$user]['username']);
			} else {
				$userdata = $this->_core_pulluser($user);
			}
			$tmpuinfo = $user;
		} else {
			if ($this->_core_islogged($id,$otheruser) == true) {
				$userdata = $this->_core_pulluser($this->sdata['cons'][$id]['loggedin'][$otheruser]['username']);
			} else {
				$userdata = $this->_core_pulluser($otheruser);
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
			$tmpglobaccess = $this->_core_get_access_global($userdata);
			$tmpservaccess = $this->_core_get_access_server($id,$userdata);
			$tmpchanaccess = $this->_core_get_access_channel($id,$chan,$userdata);
			$tmpoverallaccess = $this->_core_get_access($id,$tmpusername,$chan,'CHANNEL');
			$tmpmsgtype = $userdata['msgtype'];
		}
		if ($mode == 'WHOIS') {
			$this->_core_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"Your Bot Whois on ".$tmpuinfo);
			$this->_core_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"Nick(Username): ".$tmpuinfo." (".$tmpusername.")");
			$this->_core_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"Global: ".$this->_core_wordaccess($tmpglobaccess)." Server: ".$this->_core_wordaccess($tmpservaccess)." Channel: ".$this->_core_wordaccess($tmpchanaccess));
			$this->_core_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"Over-All Access in ".$chan." is ".$this->_core_wordaccess($tmpoverallaccess));
			$this->_core_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"End of Your Bot Whois on ".$tmpuinfo);
		}
		if ($mode == 'WHOAMI') {
			$this->_core_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"Your Bot Whois");
			$this->_core_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"Nick(Username): ".$tmpuinfo." (".$tmpusername.")");
			$this->_core_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"Your Global Access: ".$this->_core_wordaccess($tmpglobaccess));
			$this->_core_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"Your Server Access: ".$this->_core_wordaccess($tmpservaccess));
			$this->_core_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"Your Channel Access: ".$this->_core_wordaccess($tmpchanaccess));
			$this->_core_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"Over-All Access in ".$chan." is ".$this->_core_wordaccess($tmpoverallaccess));
			$this->_core_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"Your Current MsgType: ".$tmpmsgtype);
			$this->_core_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"End of Your Bot Whois");
		}
	}
	
	private function _core_buildmsg($id,$type,$user,$chan,$uctype,$message) {
		global $ch3wyb0t;
		#sock = server($1) type = messagetype(4) uctype = priv/chan($2) user/chan = sendto($3) message = message($5-)
		if ($uctype == 'PRIV') {
			$sendto = $user;
			if ($this->_core_islogged($id,$user) == true) {
				$userdata = $this->_core_pulluser($this->sdata['cons'][$id]['loggedin'][$user]['username']);
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
		$this->_core_sts($id,$msgoutput." ".$sendto." :".chr(3)."4,1".$mtoutput."".chr(3)." ".$message);	
	}
	
	private function _core_get_access($id,$user,$chan,$type) {
		$udata = $this->_core_pulluser($user);
		if ($udata == false) {
			$return = 0;
		} else {
			$tmpglobaccess = $this->_core_get_access_global($udata);
			$tmpservaccess = $this->_core_get_access_server($id,$udata);
			$tmpchanaccess = $this->_core_get_access_channel($id,$chan,$udata);
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
	
	private function _core_get_access_channel($id,$chan,$udata) {
		#Channel ServerName|ChannelName~Access|ChannelName~Access%ServerName|ChannelName~Access|ChannelName~Access
		if ($udata['channel'] != 'NULL') {
			$tmpdata = $udata['channel'];
			$tmpdata = explode(chr(37),$tmpdata);
			foreach ($tmpdata as $t1 => $t2) {
				$t2 = explode(chr(124),$t2);
				if ($t2[0] == $this->data['data'][$id]['server']['servername']) {
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

	private function _core_get_access_server($id,$udata) {
		#Server ServerName~Access%ServerName~Access
		if ($udata['server'] != 'NULL') {
			$tempdata = $udata['server'];
			$tempdata = explode(chr(37),$tempdata);
			foreach ($tempdata as $t1 => $t2) {
				$t2 = explode(chr(126),$t2);
				if ($t2[0] = $this->data['data'][$id]['server']['servername']) {
					$return = $t2[1];
				}
			}
		} else {
			$return = 0;
		}
		return $return;
	}
	
	private function _core_get_access_global($udata) {
		#Global Straight access numbers
		if ($udata['global'] != 'NULL') {
			$return = $udata['global'];
		} else {
			$return = 0;
		}
		return $return;
	}
	
	private function _core_get_access_logged($id,$user,$chan,$type) {
		if ($this->_core_islogged($id,$user) == true) {
			$return = $this->_core_get_access($id,$this->sdata['cons'][$id]['loggedin'][$user]['username'],$chan,$type);
		} else {
			$return = 0;
		}
		return $return;
	}
	
	private function _core_islogged($id,$user) {
		$return = false;
		if (count($this->sdata['cons'][$id]['loggedin']) > 0) {
			foreach ($this->sdata['cons'][$id]['loggedin'] as $t1 => $t2) {
				if ($t1 == $user) {
					$return = true;
				} else {
					$return = false;
				}			
			}
		}
		return $return;
	}
	
	private function _core_pulluser($user) {
		$return = false;
		$user = strtolower($user);
		$tempsql = "SELECT * FROM users WHERE username = '".$user."'";
		$tempudata = $this->sql->sql('select',$tempsql);
		$tempuser = $tempudata->fetchArray();
		if (count($tempuser) == 0) {
			$return = false;
		} else {
			$return = ['id'=>$tempuser['id'],'username'=>$tempuser['username'],'password'=>$tempuser['password'],'global'=>$tempuser['global'],'server'=>$tempuser['server'],'channel'=>$tempuser['channel'],'msgtype'=>$tempuser['msgtype']];
			$this->data['user'][$tempuser['username']] = ['id'=>$tempuser['id'],'username'=>$tempuser['username'],'password'=>$tempuser['password'],'global'=>$tempuser['global'],'server'=>$tempuser['server'],'channel'=>$tempuser['channel'],'msgtype'=>$tempuser['msgtype']];
		}
		return $return;
	}
	
	private function _core_wordaccess($access) {
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

	private function _core_joinchan($id,$chan) {
		foreach ($this->data['data'][$id]['chans'] as $t1 => $t2) {
			if ($t2['channel'] == $chan) {
				if ($t2['chanpass'] == 'NULL') {
					$this->_core_sts($id,"JOIN :".$chan);
				} else {
					$this->_core_sts($id,"JOIN :".$chan." ".$t2['chanpass']);
				}
			}		
		}
		return;
	}
	
	private function _core_checkchan($id,$chan) {
		$return = false;
		foreach ($this->data['data'][$id]['chans'] as $t1 => $t2) {
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
	
	private function _core_autojoinchans($id) {
		foreach ($this->data['data'][$id]['chans'] as $t1 => $t2) {
			if ($this->_core_checkchan($id,$t2['channel']) == true) {
				$this->_core_joinchan($id,$t2['channel']);
			}
		}
		return;
	}
	
	private function _core_operupcheck($id) {
		if ($this->data['data'][$id]['server']['botoper'] != 'NULL') {
			$this->_core_sts($id,"OPER ".$this->data['data'][$id]['server']['botoper']." ".$this->data['data'][$id]['server']['botoperpass']);
			$this->sdata['cons'][$id]['isoper'] = 1;
		}
	}
	
	private function _core_cmd_rehash() {
		$tempsql = "SELECT * FROM settings";
		$tempsets = $this->sql->sql('select',$tempsql);
		while ($row = $tempsets->fetchArray()) {
			$this->data['settings'][$row['setting']] = $row['value'];
			//print_r($row);
		}
		$tempsql = "SELECT * FROM servers";
		$tempservers = $this->sql->sql('select',$tempsql);
		$tempsockcount = 1;
		while ($row = $tempservers->fetchArray()) {
			$this->sdata['cons'][$tempsockcount]['id'] = $row['id'];
			$this->data['data'][$row['id']]['server']['id'] = $row['id'];
			$this->data['data'][$row['id']]['server']['servername'] = $row['servername'];
			$this->data['data'][$row['id']]['server']['address'] = $row['address'];
			$this->data['data'][$row['id']]['server']['serverport'] = $row['serverport'];
			$this->data['data'][$row['id']]['server']['serverpass'] = $row['serverpass'];
			$this->data['data'][$row['id']]['server']['nick'] = $row['nick'];
			$this->data['data'][$row['id']]['server']['bnick'] = $row['bnick'];
			$this->data['data'][$row['id']]['server']['nickservpass'] = $row['nickservpass'];
			$this->data['data'][$row['id']]['server']['botoper'] = $row['botoper'];
			$this->data['data'][$row['id']]['server']['botoperpass'] = $row['botoperpass'];
			$this->data['data'][$row['id']]['server']['enabled'] = $row['enabled'];
			$this->sdata['cons'][$tempsockcount]['connection']['address'] = $row['address'];
			$this->sdata['cons'][$tempsockcount]['connection']['port'] = $row['serverport'];
			$this->sdata['cons'][$tempsockcount]['enabled'] = $row['enabled'];
			//print_r($row);
			$tempsockcount += 1;
		}
		$tempsql = "SELECT * FROM channels";
		$tempchans = $this->sql->sql('select',$tempsql);
		while ($row = $tempchans->fetchArray()) {
			$this->data['data'][$row['server']]['chans'][$row['channel']]['id'] = $row['id'];
			$this->data['data'][$row['server']]['chans'][$row['channel']]['server'] = $row['server'];
			$this->data['data'][$row['server']]['chans'][$row['channel']]['channel'] = $row['channel'];
			$this->data['data'][$row['server']]['chans'][$row['channel']]['chanpass'] = $row['chanpass'];
			$this->data['data'][$row['server']]['chans'][$row['channel']]['chanmodes'] = $row['chanmodes'];
			$this->data['data'][$row['server']]['chans'][$row['channel']]['chantopic'] = $row['chantopic'];
			$this->data['data'][$row['server']]['chans'][$row['channel']]['options'] = $row['options'];
			$this->data['data'][$row['server']]['chans'][$row['channel']]['enabled'] = $row['enabled'];	
			//print_r($row);
		}
	}
	
	private function _core_sts($id,$data) {
		array_push($this->sdata['cons'][$id]['queue']['data'],$data."\n\r");
		return;
	}
	
	private function _core_send($id,$data) {
		@socket_write($this->sdata['cons'][$id]['socket'],$data);
		/*if (socket_last_error($this->sdata['cons'][$id]['socket'])) {
			$this->_sprint("Couldn't send data to ".$this->data['data'][$id]['server']['servername']." Error String: ".socket_strerror(socket_last_error($this->sdata['cons'][$id]['socket'])),'error',false);
			socket_clear_error($this->sdata['cons'][$id]['socket']);
		}*/
		return;
	}
	
	private function _core_run_timer($id) {
		$this->sdata['cons'][$id]['timer']['last'] = time();
		return;
	}
	
	private function _core_run_globtimer() {
		$this->sdata['timer']['last'] = time();
		return;
	}
	
	private function _core_run_queue($id) {
		$tempnow = time();
		$i = 0;
		$queuelimit = $this->data['settings']['msgqueue'];
		$msginterval = $this->data['settings']['msginterval'];
		if (($this->sdata['cons'][$id]['queue']['last'] + $msginterval) < $tempnow) {
			while ($i != $queuelimit) {
				if (count($this->sdata['cons'][$id]['queue']['data']) != 0) {
					$tempdata = array_shift($this->sdata['cons'][$id]['queue']['data']);
					//datasend
					$this->_core_send($id,$tempdata);
					$this->_screen($id,'out',trim($tempdata));
					$i += 1;
				} else {
					$i = $queuelimit;
				}
				$this->sdata['cons'][$id]['queue']['last'] = time();
			}
		}
	}

	private function _core_connect($id) {
		global $CORE;
		$this->_sprint("Attempting to connect to ".$this->data['data'][$id]['server']['servername'],'debug',false);
		$this->sdata['cons'][$id]['lastcmd'] = '';
		if ($this->data['data'][$id]['server']['nickservpass'] != 'NULL') {
			$this->sdata['cons'][$id]['identified'] = 0;
		} else {
			$this->sdata['cons'][$id]['identified'] = 2;
		}
		$this->sdata['cons'][$id]['isoper'] = false;
		$this->sdata['cons'][$id]['lastping'] = time();
		$this->sdata['cons'][$id]['socket'] = @socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
		if (socket_last_error($this->sdata['cons'][$id]['socket'])) {
			$this->_sprint("Couldn't create socket for ".$this->data['data'][$id]['server']['servername']." Error String: ".socket_strerror(socket_last_error($this->sdata['cons'][$id]['socket'])),'error',false);
			socket_clear_error($this->sdata['cons'][$id]['socket']);
		}
		if ($CORE['conf']['bindip'] == true) {
			socket_bind($this->sdata['cons'][$id]['socket'],$CORE['conf']['bindedip']);
			if (socket_last_error($this->sdata['cons'][$id]['socket'])) {
				$this->_sprint("Couldn't bind socket for ".$this->data['data'][$id]['server']['servername']." Error String: ".socket_strerror(socket_last_error($this->sdata['cons'][$id]['socket'])),'error',false);
				socket_clear_error($this->sdata['cons'][$id]['socket']);
			}
		}
		@socket_connect($this->sdata['cons'][$id]['socket'],$this->sdata['cons'][$id]['connection']['address'],$this->sdata['cons'][$id]['connection']['port']);
		if (socket_last_error($this->sdata['cons'][$id]['socket'])) {
			$this->_sprint("Couldn't connect to ".$this->data['data'][$id]['server']['servername']." Error String: ".socket_strerror(socket_last_error($this->sdata['cons'][$id]['socket'])),'error',false);
			socket_clear_error($this->sdata['cons'][$id]['socket']);
		}
		socket_set_nonblock($this->sdata['cons'][$id]['socket']);
		//timeout setting here
		if ($this->data['data'][$id]['server']['serverpass'] != 'NULL') {
			$this->_core_sts($id,"PASS ".$this->data['data'][$id]['server']['serverpass']);
		}
		$this->_core_sts($id,"NICK ".$this->sdata['cons'][$id]['nick']);
		$this->_core_sts($id,"USER ".$this->data['settings']['botname']." 0 ".$this->data['data'][$id]['server']['address']." :Ch3wyB0t Version ".$CORE['info']['version']);
		return;
	}

	private function _core_startup() {
		$this->sdata['timer']['data'] = array();
		$this->sdata['timer']['last'] = time() - 10;
		foreach ($this->sdata['cons'] as $t1 => $t2) {
			if ($t2['enabled'] == 'enabled') {
				$this->sdata['cons'][$t2['id']]['loggedin'] = array();
				$this->sdata['cons'][$t2['id']]['queue']['data'] = array();
				$this->sdata['cons'][$t2['id']]['queue']['last'] = time() - 10;
				$this->sdata['cons'][$t2['id']]['timer']['data'] = array();
				$this->sdata['cons'][$t2['id']]['timer']['last'] = time() - 10;
				$this->sdata['cons'][$t2['id']]['lastcmd'] = '';
				if ($this->data['data'][$t2['id']]['server']['nickservpass'] != 'NULL') {
					$this->sdata['cons'][$t2['id']]['identified'] = 0;
				} else {
					$this->sdata['cons'][$t2['id']]['identified'] = 2;
				}
				$this->sdata['cons'][$t2['id']]['lastping'] = time();
				$this->sdata['cons'][$t2['id']]['nick'] = $this->data['data'][$t2['id']]['server']['nick'];
				$this->_core_connect($t2['id']);
				$this->_core_run_queue($t2['id']);
			}
		}
	}
	
	private function _core_main_process() {
		global $ch3wyb0t;
		while (true) {
			$numsocks = 0;
			$numdisabled = 0;
			if (count($this->sdata['timer']['data']) != 0) { $this->_core_run_globtimer(); }
			foreach ($this->sdata['cons'] as $t1 => $t2) {
				$numsocks += 1;
				if ($t2['enabled'] == 'enabled') {
					$tempnow = time();
					if (($this->sdata['cons'][$t2['id']]['lastping'] + 600) < $tempnow) {
						socket_close($this->sdata['cons'][$t2['id']]['socket']);
						$this->_core_connect($t2['id']);
					}
					if (count($this->sdata['cons'][$t2['id']]['queue']['data']) != 0) { $this->_core_run_queue($t2['id']); }
					if (count($this->sdata['cons'][$t2['id']]['timer']['data']) != 0) { $this->_core_run_timer($t2['id']); }
					//$this->_sprint("Before data read",'debug',false);
					$tempdata = @socket_read($this->sdata['cons'][$t2['id']]['socket'],10240);
					//$this->_sprint("After data read",'debug',false);
					if (strlen($tempdata) >= 1) {
						$tempdata = str_replace("\r","",$tempdata);
						//$this->_sprint(print_r($tempdata),'debug',false);
						$tempdata = explode("\n",$tempdata);
						//$this->_sprint(print_r($tempdata),'debug',false);
						foreach ($tempdata as $t3 => $t4) {
							if ($t4 != '') {
								$this->_core_parse_data($t2['id'],$t4);
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
	
	public function startup() {
		global $CORE;
		global $ch3wyb0t;
		//connect to sql db
		if (!$this->sql->db) {
			$this->sql->sql('database_connect',null);
		}
		$this->_sprint("Pulling Key Data from the database",'regular');
		$this->_core_cmd_rehash();
		$this->_sprint("Finished Loading All Key Data from the database",'regular');
		$this->_sprint("Bot is now starting up, gonna start connections and head right on in.",'regular');
		$this->_core_startup();
		$this->_core_main_process();
		
/*		$this->_sprint("Test of Error Output",'error');
		$this->_sprint("Test of Alert Output",'alert');
		$this->_sprint("Test of Warning Output",'warning');
		$this->_sprint("Test of Notice Output",'notice');
		$this->_sprint("Test of Debug Output",'debug');
		$this->_sprint("Test of Regular Output",'regular');*/
		//print_r($this->sdata);
	}
	
	protected function _sql($type,$val) {
		return $this->sql->sql($type,$val);
	}
	protected function _sprint($message,$dtype=null,$log=false) {
		return $this->log->_sprint($message,$dtype,$log);
	}
	protected function _screen($id,$type,$text) {
		return $this->log->_screen($id,$type,$text);
	}
		
}
/*echo "Are you sure you want to do this?  Type 'yes' to continue: ";
$handle = fopen ("php://stdin","r");
$line = fgets($handle);
if(trim($line) != 'yes') {
    echo "ABORTING!\n";
    exit;
}
echo "\n";
echo "Thank you, continuing...\n";



if(defined('STDIN') )
  echo("Running from CLI");
else
  echo("Not Running from CLI");
*/




?>