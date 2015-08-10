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
		global $ch3wyb0t;
		include ('./module/core/logging.class.php');
		include ('./module/core/sql.class.php');
		include ('./module/core/funcs.class.php');
		include ('./module/core/timers.class.php');
		include ('./module/core/queue.class.php');
		include ('./module/core/error.class.php');
		$ch3wyb0t->_log = new log("Init of Logging system for main",null,false);
		$ch3wyb0t->_core = new funcs();
		$ch3wyb0t->_sql = new sql('init',null);
		$ch3wyb0t->_berror = new berror("Init of Error logging system for main",null,false);
	}	
	
	public function initsetup() {
		global $CORE;
		global $ch3wyb0t;
		$ch3wyb0t->_check();
		$ch3wyb0t->_loadCoreFiles();
		
		
		if (file_exists($CORE['conf']['db'])) {
			$ch3wyb0t->_log->_sprint("Database exists, gonna check database structure",'regular',false);
			$ch3wyb0t->_sql->sql('database_check_structure',null);
		} else {
			$ch3wyb0t->_log->_sprint("Database missing, gotta regenerate the database",'error',false);
			$ch3wyb0t->_sql->sql('database_build_database',null);
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
	

	
	protected function _core_command_cmds($id,$type,$user,$indata,$rawdata,$address) {
		global $ch3wyb0t;
		global $CORE;
		if (($type == 'CNOTE') or ($type == 'CMSG')) {
			$chan = $indata[2];
		} 
		else {
			$chan = 'NULL';
		}
		if (count($indata) >= 4) {
			#$ch3wyb0t->_log->_sprint($id." Entered Private Messages",'debug',false);
			if (($type == 'PNOTE') and ($indata[0] == $ch3wyb0t->sdata['cons'][$id]['connectaddress'])) {
				#$ch3wyb0t->_log->_sprint($id." SNOTICE: ".print_r($indata,true),'debug',false);
				$blarg = true;
			} 
			elseif (strstr($indata[3],chr(1))) { // '\x01'
				$ctcp = $ch3wyb0t->_core->_array_rearrange($indata,3);
				$ctcp = $ch3wyb0t->_core->_array_stripchr($ctcp,1);
				if ($ctcp[0] == 'ACTION') {
					$ch3wyb0t->_log->_sprint($id." Got a Action ".print_r($ch3wyb0t->_core->_array_rearrange($ctcp,1),true),'debug',false);
				} 
				elseif ($ctcp[0] == 'VERSION') {
					if (count($ctcp) >= 2) {
						$ch3wyb0t->_log->_sprint($id." Got a CTCP VERSION Response ".print_r($ch3wyb0t->_core->_array_rearrange($ctcp,1),true),'debug',false);
					} 
					else {
						$ch3wyb0t->_core->_sts($id,"NOTICE ".$user." :".chr(1)."VERSION Ch3wyB0t Version ".VERSION_MAJOR.".".VERSION_MINOR.".".VERSION_REVISION.".".VERSION_BUILD.chr(1));
					}
				} 
				elseif ($ctcp[0] == 'PING') {
					if (count($ctcp) >= 2) {
						$ch3wyb0t->_core->_sts($id,"NOTICE ".$user." :".chr(1)."PING ".$ctcp[1].chr(1));
					} 
					else {
						$ch3wyb0t->_core->_sts($id,"NOTICE ".$user." :".chr(1)."PING".chr(1));
					}
				} 
				elseif ($ctcp[0] == 'TIME') {
					if (count($ctcp) >= 2) {
						$ch3wyb0t->_log->_sprint($id." Got a CTCP TIME Response ".print_r($ch3wyb0t->_core->_array_rearrange($ctcp,1),true),'debug',false);
					} 
					else {
						$ch3wyb0t->_core->_sts($id,"NOTICE ".$user." :".chr(1)."TIME ".date("D M d h:i:sA Y").chr(1));
					}
				} 
				else {
					$ch3wyb0t->_log->_sprint($id." Got a Unknown CTCP request ".print_r($ch3wyb0t->_core->_array_rearrange($ctcp,1),true),'debug',false);
				}
			} 
			elseif ($indata[3] == '?trigger') {
				$ch3wyb0t->_core->_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"Channel :".$ch3wyb0t->data['settings']['chancom'].$ch3wyb0t->data['settings']['signal']." Private Message: ".$ch3wyb0t->data['settings']['pvtcom'].$ch3wyb0t->data['settings']['signal']);
			} 
			elseif ((($indata[3] == $ch3wyb0t->data['settings']['chancom'].$ch3wyb0t->data['settings']['signal']) and (($type == 'CMSG') or ($type == 'CNOTE'))) or (($indata[3] == $ch3wyb0t->data['settings']['pvtcom'].$ch3wyb0t->data['settings']['signal']) and (($type == 'PMSG') or ($type == 'PNOTE')))) {
				if (count($indata) >= 5) {
					switch(strtoupper($indata[4])) {
						case 'USERLIST': {
							if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'SERVER') >= 4) {
								$records = $ch3wyb0t->_sql->sql('select',"SELECT * FROM users");
								$ch3wyb0t->_core->_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"Displaying user list, only showing Usernames atm, do note may be a big ammount of information");
								while ($row = $records->fetchArray()) {
									$ch3wyb0t->_core->_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"Username: ".$row['username']);
									//$ch3wyb0t->_log->_sprint(print_r($row),true);
								}
							} else {
								$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'EXIT': {
							if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'GLOBAL') >= 6) {
								if (count($indata) >= 6) {
									$tmpoutput = $ch3wyb0t->_core->_array_join($indata,5," ");
									$quitmsg = "QUIT ".$tmpoutput;
								} else { 
									$quitmsg = "QUIT Ch3wyB0t Version ".VERSION_MAJOR.".".VERSION_MINOR.".".VERSION_REVISION.".".VERSION_BUILD." Quitting";
								}
								foreach ($ch3wyb0t->sdata['cons'] as $t1 => $t2) {
									if ($t2['enabled'] == 'enabled') {
										$ch3wyb0t->sdata['cons'][$t2['id']]['lastcmd'] = 'EXIT';
										$ch3wyb0t->_core->_sts($t2['id'],$quitmsg);
									}
								}
							} else {
								$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'RELOAD': {
							if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'GLOBAL') >= 6) {
								foreach ($ch3wyb0t->sdata['cons'] as $t1 => $t2) {
									if ($t2['enabled'] == 'enabled') {
										$ch3wyb0t->sdata['cons'][$t2['id']]['lastcmd'] = 'RELOAD';
										$ch3wyb0t->_core->_sts($t2['id'],"QUIT Ch3wyB0t Version ".VERSION_MAJOR.".".VERSION_MINOR.".".VERSION_REVISION.".".VERSION_BUILD." Reloading");
									}
								}
							} else {
								$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'RAW': {
							if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'GLOBAL') >= 7) {
								if (count($indata) >= 6) {
									$tmpoutput = $ch3wyb0t->_core->_array_join($rawdata,5," ");
									$ch3wyb0t->_core->_sts($id,$tmpoutput);
									$ch3wyb0t->_core->_buildmsg($id,'RAW',$user,$chan,'PRIV',"Sent ".$tmpoutput." to Server");
								} else {
									$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter what you want to send to the bot");
								}
							} else {
								$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'RAWDB': {
							if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'GLOBAL') >= 7) {
								if (count($indata) >= 6) {
									$tmpoutput = $ch3wyb0t->_core->_array_join($rawdata,5," ");
									//$ch3wyb0t->_sql->sql('execute',$tmpoutput);
									$ch3wyb0t->_core->_buildmsg($id,'RAW',$user,$chan,'PRIV',"Sent ".$tmpoutput." to the database");
								} else {
									$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter what you want to send to the database");
								}
							} else {
								$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'QUIT': {
							if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'SERVER') >= 6) {
								$ch3wyb0t->sdata['cons'][$id]['lastcmd'] = 'QUIT';
								if (count($indata) >= 6) {
									$ch3wyb0t->_core->_sts($id,"QUIT ".$ch3wyb0t->_core->_array_join($indata,5," "));
								} else {
									$ch3wyb0t->_core->_sts($id,"QUIT Ch3wyB0t Version ".VERSION_MAJOR.".".VERSION_MINOR.".".VERSION_REVISION.".".VERSION_BUILD." Quitting");
								}
							} else {
								$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'REHASH': {
							if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'GLOBAL') >= 6) {
								$ch3wyb0t->_core->_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"Rehashing...");
								$ch3wyb0t->_core->_cmd_rehash();
								$ch3wyb0t->_core->_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"Rehashing Complete...");
							} else {
								$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'SETTINGS': {
							if (($type == 'PMSG') or ($type == 'PNOTE')) {
								if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'GLOBAL') >= 6) {
									if (count($indata) >= 6) {
										switch(strtoupper($indata[5])) {
											case 'SET': {
												if (count($indata) >= 7) {
													if (count($indata) >= 8) {
														$ch3wyb0t->_sql->sql('update',"UPDATE settings SET setting = '".strtolower($indata[6])."', value = '".$indata[7]."' where setting = '".strtolower($indata[6])."'");
														$ch3wyb0t->data['settings'][strtolower($indata[6])] = $indata[7];
														$ch3wyb0t->_core->_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"You have successfully changed ".strtolower($indata[6])." to ".$indata[7]);
													} else {
														$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"Missing Setting Value");
													}
												} else {
													$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"Missing Setting Name");
												}
												break;
											} 
											case 'LIST': {
												$records = $ch3wyb0t->_sql->sql('select',"SELECT * FROM settings");
												while ($row = $records->fetchArray()) {
													$ch3wyb0t->_core->_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"Setting: ".$row['setting']." Value: ".$row['value']);
												}
												break;
											}
											default: {
												$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"Error, Use Either Set or List");
												break;
											}
										}
									} else {
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"Error, Use Either Set or List");
									}
								} else {
									$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
								}
							} else {
								$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You can not access settings via channel commands");
							}
							break;
						}
						case 'SERVER': {
							if (($type == 'PMSG') or ($type == 'PNOTE')) {
								if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'GLOBAL') >= 6) {
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
												$records = $ch3wyb0t->_sql->sql('select',"SELECT * FROM servers");
												while ($row = $records->fetchArray()) {
													if ($row['enabled'] == 'enabled') {
														$ch3wyb0t->_core->_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"\x033SID: ".$row['id']." Server: ".$row['servername']." Address: ".$row['address']." Port: ".$row['serverport']." SPass: ".$row['serverpass']." Nick: ".$row['nick']." BNick: ".$row['bnick']." NSPass: ".$row['nickservpass']." BotOper: ".$row['botoper']." BotOperPass: ".$row['botoperpass']."\x03");
													} else {
														$ch3wyb0t->_core->_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"\x034SID: ".$row['id']." Server: ".$row['servername']." Address: ".$row['address']." Port: ".$row['serverport']." SPass: ".$row['serverpass']." Nick: ".$row['nick']." BNick: ".$row['bnick']." NSPass: ".$row['nickservpass']." BotOper: ".$row['botoper']." BotOperPass: ".$row['botoperpass']."\x03");
													}
												}
												$ch3wyb0t->_core->_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"Color \x033Green\x03 is enabled, Color \x034Red\x03 is disabled");
												break;
											}
											default: {
												$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"Error, Use Either List, Add, or Chg");
												break;
											}
										}
									} else {
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"Error, Use Either List, Add, or Chg");
									}
								} else {
									$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
								}
							} else {
								$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You can not access server via channel commands");
							}
							break;
						}
						case 'CHANNEL': {
							if (($type == 'PMSG') or ($type == 'PNOTE')) {
								if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'SERVER') >= 6) {
									if (count($indata) >= 6) {
										switch(strtoupper($indata[5])) {
											case 'ADD': {
												if (count($indata) >= 7) {
													$ch3wyb0t->_sql->sql('insert',"INSERT INTO channels (server, channel, chanpass, chanmodes, chantopic, options, enabled) VALUES (".$id.", ".$indata[6].", NULL, NULL, NULL, NULL, enabled)");
													$ch3wyb0t->_core->_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"You have successfully created channel '".$indata[6]."'");
													$ch3wyb0t->_core->_cmd_rehash(); 
													$ch3wyb0t->_core->_joinchan($id,$indata[6]);
												} else {
													$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You are missing <channel>");
												}												
												break;
											}
											case 'CHG': {
											  if (count($indata) >= 7) {
											  	if (count($indata) >= 8) {
											  	  switch(strtoupper($indata[7])) {
											  		  case 'SERVER': {
											  		  	
											  		  	break;
											  		  }
											  		  case 'CHANNEL': {
											  		  	
											  		  	break;
											  		  }
											  		  case 'CHANPASS': {
											  		  	
											  		  	break;
											  		  }
											  		  case 'CHANMODES': {
											  		  	
											  		  	break;
											  		  }
											  		  case 'CHANTOPIC': {
											  		  	
											  		  	break;
											  		  }
											  		  case 'OPTIONS': {
											  		  	
											  		  	break;
											  		  }
											  		  case 'ENABLED': {
																if (count($indata) >= 9) {
																	$ch3wyb0t->_sql->sql('update',"UPDATE channels SET enabled = '".strtolower($indata[8])."' where id = '".$indata[6]."'");
																	$ch3wyb0t->_core->_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"You have successfully changed the enabled status for CID '".$indata[6]."' to '".strtolower($indata[8])."'");
																	if ($indata[8] == 'enabled') {
																		$ch3wyb0t->_core->_cmd_rehash();
																		$records = $ch3wyb0t->_sql->sql('select',"SELECT * FROM channels where id = '".$indata[6]."'");
																		while ($row = $records->fetchArray()) {
																			$ch3wyb0t->_core->_joinchan($id,$row['channel']);
																		}
																	}
																} else {
																	$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"Error, Choose either enabled or disabled");
																}
											  		  	break;
											  		  }											  		
											  		  default: {
											  			  $ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You must choose from Server, Channel, Chanpass, Chanmodes, Chantopic, Options, Enabled");
											  			  break;
											  		  }
											  	  }
											  	
											    }
											    else {
											    	$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You must choose from Server, Channel, Chanpass, Chanmodes, Chantopic, Options, Enabled");
											    }
											  	
											  }
											  else {
											    $ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"Error, Missing CID number, please check channel list again");
											  }
											} 
											case 'DEL': {
												if (count($indata) >= 7) {
													$ch3wyb0t->_sql->sql('update',"UPDATE channels SET enabled = 'disabled' where id = '".$indata[6]."'");
													$ch3wyb0t->_core->_cmd_rehash();
													$records = $ch3wyb0t->_sql->sql('select',"SELECT * FROM channels where id = '".$indata[6]."'");
													while ($row = $records->fetchArray()) {
														$ch3wyb0t->_core->_sts($id,"PART :".$row['channel']);
													}
												}
												else {
													$this-_core_buildmsg($id,'ERROR',$user,$chan,'PRIV',"Error, Missing CID number, please check channel list again");
												}
												break;
											}
											case 'LIST': {
												$records = $ch3wyb0t->_sql->sql('select',"SELECT * FROM channels where server = '".$id."'");
												while ($row = $records->fetchArray()) {
													if ($row['enabled'] == 'enabled') {
														$ch3wyb0t->_core->_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"\x033CID: ".$row['id']." Server: ".$row['server']." Channel: ".$row['channel']." Pass: ".$row['chanpass']." Channel Modes: ".$row['chanmodes']." Chan Options: ".$row['options']."\x03");
														$ch3wyb0t->_core->_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"\x033CID: ".$row['id']." Topic: ".$row['chantopic']."\x03"); 
													} else {
														$ch3wyb0t->_core->_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"\x034CID: ".$row['id']." Server: ".$row['server']." Channel: ".$row['channel']." Pass: ".$row['chanpass']." Channel Modes: ".$row['chanmodes']." Chan Options: ".$row['options']."\x03");
														$ch3wyb0t->_core->_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"\x034CID: ".$row['id']." Topic: ".$row['chantopic']."\x03");
													} 
												}
												$ch3wyb0t->_core->_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"Color \x033Green\x03 is enabled, Color \x034Red\x03 is disabled");
												break;
											}
											default: {
												$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"Error, Use Either List, Add, Chg, or Del");
												break;
											}
										}
									} else {
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"Error, Use Either List, Add, Chg, or Del");
									}
								} else {
									$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
								}
							} else {
								$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You can not access channel via channel commands");
							}
							break;
						}
						case 'USER': {
							if (($type == 'PMSG') or ($type == 'PNOTE')) {
								if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'GLOBAL') >= 6) {
									if (count($indata) >= 6) {
										switch(strtoupper($indata[5])) {
											case 'ADD': {
												if (count($indata) >= 7) {
													if (count($indata) >= 8) {
														$tmpudata = $ch3wyb0t->_core->_pulluser(strtolower($indata[6]));
														if ($tmpudata == 'FALSE') {
															$tmppass = md5($indata[7]);
															$ch3wyb0t->_sql->sql('insert',"INSERT INTO users (username, password, global, server, channel, msgtype) VALUES (".strtolower($indata[6]).", ".$tmppass.", NULL, NULL, NULL, msg)");
															$ch3wyb0t->_core->_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"You have successfully created '".strtolower($indata[6])."' with the password '".$incom[7]."'");
														} else {
															$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"The username you entered already exists");
														}
													} else {
														$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You only entered a username, please enter a password as well");
													}
												} else {
													$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You are missing <username> <password>");
												}
												break;
											}
											case 'CHG': {
												if (count($indata) >= 7) {
													if (count($indata) >= 8) {
														switch (strtoupper($indata[7])) {
															case 'PASS': {
																if (count($indata) >= 9) {
																	$tmppass = md5($indata[8]);
																	$ch3wyb0t->_sql->sql('update',"UPDATE users SET password = '".$tmppass2."' where id = '".strtolower($indata[6])."'");
																	$ch3wyb0t->_core->_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"You have successfully changed the password for '".strtolower($indata[6])."'");
																} else {
																	$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"Error, Use format <username> PASS <newpass>");
																}
																break;
															}
															case 'MSGTYPE': {
																if (count($indata) >= 9) {
																	if (strtolower($indata[8]) == 'notice') {
																		$newtype = 'notice';
																	} else {
																		$newtype = 'msg';
																	}
																	$ch3wyb0t->_sql->sql('update',"UPDATE users SET msgtype = '".$newtype."' where id = '".strtolower($indata[6])."'");
																	if ($ch3wyb0t->_core->_islogged($id,strtolower($indata[6])) == true) {
																		$ch3wyb0t->sdata['cons'][$id]['loggedin'][strtolower($indata[6])]['msgtype'] = $newtype;
																	}
																	$ch3wyb0t->_core->_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"You have successfully changed the message type for '".strtolower($indata[6])."' to '".$newtype."'");
																} else {
																	$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"Error, Use format <username> MSGTYPE <notice/msg>");
																}
																break;
															}
															default: {
																$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"Error, Use Either Pass, MsgType");
																break;
															}
														}
													} else {
														$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"Error, Use Either Pass, MsgType");
													}
												} else {
													$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"Missing Username");
												}
												break;
											} 
											case 'DEL': {
												#this bit of coding is only gonna be temperary for the time being due to abuse possiblities
												$ch3wyb0t->_sql->sql('execute',"DELETE FROM users WHERE username = '".strtolower($indata[6])."'");
												$ch3wyb0t->_core->_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"Deleted ".strtolower($indata[6])." or attempted to delete from the database");
												break;
											}
											case 'LIST': {
												$records = $ch3wyb0t->_sql->sql('select',"SELECT * FROM users");
												while ($row = $records->fetchArray()) {
													$ch3wyb0t->_core->_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"UID: ".$row['id']." Username: ".$row['username']." Global: ".$row['global']." Server: ".$row['server']." Channel: ".$row['channel']." MsgType: ".$row['msgtype']);
												}
												break;
											}
											default: {
												$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"Error, Use Either List, Add, Del, or Chg");
												break;
											}
										}
									} else {
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"Error, Use Either List, Add, Del, or Chg");
									}
								} else {
									$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
								}
							} else {
								$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You can not access user via channel commands");
							}
							break;
						}
						case 'ACCESS': {
							$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"Command Access under construction");
							break;
						}
						case 'ACCOUNT': {
							if (($type == 'PMSG') or ($type == 'PNOTE')) {
								if ($ch3wyb0t->_core->_islogged($id,$user) == true) {
									if (count($indata) >= 6) {
										$userdetail = $ch3wyb0t->_core->_pulluser($ch3wyb0t->sdata['cons'][$id]['loggedin'][$user]['username']);
										if (strtoupper($indata[5]) == 'CHGPASS') {
											if (count($indata) >= 7) {
												if (count($indata) >= 8) {
													$tmppass = md5($indata[6]);
													if ($userdetail['password'] == $tmppass) {
														$tmppass2 = md5($indata[7]);
														$ch3wyb0t->_sql->sql('update',"UPDATE users SET password = '".$tmppass2."' where id = '".$userdetail['id']."'");
														$ch3wyb0t->sdata['cons'][$id]['loggedin'][$user]['password'] = $tmppass2;
														$ch3wyb0t->_core->_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"You have successfully changed your password.");
													} else {
														$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You sure you entered your current password right?");
													}
												} else {
													$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"Missing New Password");
												}
											} else {
												$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"Missing Current Password");
											}
										}
										if (strtoupper($indata[5]) == 'MSGTYPE') {
											if (count($indata) >= 7) {
												if (strtolower($indata[6]) == 'notice') {
													$newtype = 'notice';
												} else {
													$newtype = 'msg';
												}
												$ch3wyb0t->_sql->sql('update',"UPDATE users SET msgtype = '".$newtype."' where id = '".$userdetail['id']."'");
												$ch3wyb0t->sdata['cons'][$id]['loggedin'][$user]['msgtype'] = $newtype;
												$ch3wyb0t->_core->_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"You have successfully changed your message type to ".$newtype);
											} else {
												$ch3wyb0t->_core->_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"You have to enter a Message type");
											}
										}
									} else {
										$ch3wyb0t->_core->_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"Your account details ".$user."(".$ch3wyb0t->sdata['cons'][$id]['loggedin'][$user]['username'].")");
										$ch3wyb0t->_core->_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"MSGTYPE: ".$ch3wyb0t->sdata['cons'][$id]['loggedin'][$user]['msgtype']);
									}
								} else {
									$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOTLOGGED');
								}
							} else {
								$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You can not access your account via channel commands");
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
									$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
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
								$ch3wyb0t->_core->_get_whois($id,$user,$chan,'WHOIS',$uwho);
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
									$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
								}
							}
							if (($type == 'CMSG') or ($type == 'CNOTE')) {
								$passthrough = true;
							}
							if ($passthrough == true) {
								$ch3wyb0t->_core->_get_whois($id,$user,$chan,'WHOAMI','NULL');
							}
							break;
						}
						case 'LOGOUT': {
							if ($ch3wyb0t->_core->_islogged($id,$user) == true) {
								$ch3wyb0t->_core->_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"You have been logged out of ".$ch3wyb0t->sdata['cons'][$id]['loggedin'][$user]['username']);
								unset($ch3wyb0t->sdata['cons'][$id]['loggedin'][$user]);
							} else {
								$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOTLOGGED');
							}
							break;
						}
						case 'LOGIN': {
							if (($type == 'PMSG') or ($type == 'PNOTE')) {
								if ($ch3wyb0t->_core->_islogged($id,$user) == false) {
									if (count($indata) >= 6) {
										if (count($indata) >= 7) {
											$tmpudata = $ch3wyb0t->_core->_pulluser($indata[5]);
											if ($tmpudata != false) {
												$tmppass = md5($indata[6]);
												if ($tmpudata['password'] == $tmppass) {
													$ch3wyb0t->sdata['cons'][$id]['loggedin'][$user] = ['username'=>$tmpudata['username'],'msg'=>$tmpudata['msgtype'],'umask'=>$indata[0]];
													$ch3wyb0t->_core->_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"You have successfully logged in as ".strtolower($indata[5]));
												} else {
													$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You have failed to login");
												}
											} else {
												$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a valid username");
											}
										} else {
											$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You only entered a username, please enter a password as well");
										}
									} else {
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You are missing <username> <password>");
									}
								} else {
									$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You are already LOGGED In as ".$ch3wyb0t->sdata['cons'][$id]['loggedin'][$user]['username']);
								}
							} else {
								$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You can not log in via channel commands");
							}
							break;
						}
						case 'REGISTER': {
							if (($type == 'PMSG') or ($type == 'PNOTE')) {
								if ($ch3wyb0t->_core->_islogged($id,$user) == false) {
									if (count($indata) >= 6) {
										if (count($indata) >= 7) {
											$tmpudata = $ch3wyb0t->_core->_pulluser($indata[5]);
											if ($tmpudata == false) {
												$tmppass = md5($indata[6]);
												$ch3wyb0t->_sql->sql('insert',"INSERT INTO users (username, password, global, server, channel, msgtype) VALUES (".strtolower($indata[5]).", ".$tmppass.", NULL, NULL, NULL, msg)");
												$ch3wyb0t->sdata['cons'][$id]['loggedin'][$user] = ['username'=>strtolower($indata[5]),'msg'=>'msg','umask'=>$indata[0]];
												$ch3wyb0t->_core->_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"You have successfully registered as ".strtolower($indata[5])." and have been auto logged-in");
											} else {
												$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"The username you entered already exists");
											}
										} else {
											$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You only entered a username, please enter a password as well");
										}
									} else {
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You are missing <username> <password>");
									}
								} else {
									$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','LOGIN');
								}
							} else {
								$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You can not register via channel commands");
							}
							break;
						}
						case 'VERSION': {
							$ch3wyb0t->_core->_buildmsg($id,'NORMAL',$user,$chan,'PRIV',"Ch3wyB0t Version ".VERSION_MAJOR.".".VERSION_MINOR.".".VERSION_REVISION.".".VERSION_BUILD);
							break;
						}
						case 'HELP': {
							if (($type == 'PMSG') or ($type == 'PNOTE')) {
								if (count($indata) >= 6) {
									$chan = $indata[5];
								}
							}
							$ch3wyb0t->_core->_command_help($id,$user,$chan,$indata);
							break;
						}
						case 'MOWNER': {
							if ($ch3wyb0t->_core->_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										$passthrough = true;
									} else {
										$passthrough = false;
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									$passthrough = true;
								}
								if ($passthrough == true) {
									if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 5) {
										$ch3wyb0t->_core->_massmodes($id,$user,$chan,['ADD','q','ALL']);
									} else {
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'OWNER': {
							if ($ch3wyb0t->_core->_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										if (count($indata) >= 7) {
											$tmpdata = $ch3wyb0t->_core->_array_join($indata,6," ");
											$passthrough = true;
										} else {
											$passthrough = false;
											$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter any nicks");
										}
									} else {
										$passthrough = false;
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									if (count($indata) >= 6) {
										$tmpdata = $ch3wyb0t->_core->_array_join($indata,5," ");
										$passthrough = true;
									} else {
										$passthrough = false;
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter any nicks");
									}
								}
								if ($passthrough == true) {
									if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 5) {
										$ch3wyb0t->_core->_massmodes($id,$user,$chan,['ADD','q',$tmpdata]);
									} else {
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'MDEOWNER': {
							if ($ch3wyb0t->_core->_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										$passthrough = true;
									} else {
										$passthrough = false;
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									$passthrough = true;
								}
								if ($passthrough == true) {
									if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 5) {
										$ch3wyb0t->_core->_massmodes($id,$user,$chan,['REM','q','BC']);
									} else {
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'DEOWNER': {
							if ($ch3wyb0t->_core->_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										if (count($indata) >= 7) {
											$tmpdata = $ch3wyb0t->_core->_array_join($indata,6," ");
											$passthrough = true;
										} else {
											$passthrough = false;
											$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter any nicks");
										}
									} else {
										$passthrough = false;
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									if (count($indata) >= 6) {
										$tmpdata = $ch3wyb0t->_core->_array_join($indata,5," ");
										$passthrough = true;
									} else {
										$passthrough = false;
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter any nicks");
									}
								}
								if ($passthrough == true) {
									if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 5) {
										$ch3wyb0t->_core->_massmodes($id,$user,$chan,['REM','q',$tmpdata]);
									} else {
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'OWNERME': {
							if ($ch3wyb0t->_core->_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										$passthrough = true;
									} else {
										$passthrough = false;
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									$passthrough = true;
								}
								if ($passthrough == true) {
									if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 5) {
										$ch3wyb0t->_core->_massmodes($id,$user,$chan,['ADD','q',$user]);
									} else {
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'DEOWNERME': {
							if ($ch3wyb0t->_core->_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										$passthrough = true;
									} else {
										$passthrough = false;
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									$passthrough = true;
								}
								if ($passthrough == true) {
									if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 5) {
										$ch3wyb0t->_core->_massmodes($id,$user,$chan,['REM','q',$user]);
									} else {
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'MPROTECT': {
							if ($ch3wyb0t->_core->_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										$passthrough = true;
									} else {
										$passthrough = false;
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									$passthrough = true;
								}
								if ($passthrough == true) {
									if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 5) {
										$ch3wyb0t->_core->_massmodes($id,$user,$chan,['ADD','a','ALL']);
									} else {
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'PROTECT': {
							if ($ch3wyb0t->_core->_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										if (count($indata) >= 7) {
											$tmpdata = $ch3wyb0t->_core->_array_join($indata,6," ");
											$passthrough = true;
										} else {
											$passthrough = false;
											$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter any nicks");
										}
									} else {
										$passthrough = false;
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									if (count($indata) >= 6) {
										$tmpdata = $ch3wyb0t->_core->_array_join($indata,5," ");
										$passthrough = true;
									} else {
										$passthrough = false;
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter any nicks");
									}
								}
								if ($passthrough == true) {
									if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 5) {
										$ch3wyb0t->_core->_massmodes($id,$user,$chan,['ADD','a',$tmpdata]);
									} else {
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'MDEPROTECT': {
							if ($ch3wyb0t->_core->_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										$passthrough = true;
									} else {
										$passthrough = false;
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									$passthrough = true;
								}
								if ($passthrough == true) {
									if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 5) {
										$ch3wyb0t->_core->_massmodes($id,$user,$chan,['REM','a','BC']);
									} else {
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'DEPROTECT': {
							if ($ch3wyb0t->_core->_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										if (count($indata) >= 7) {
											$tmpdata = $ch3wyb0t->_core->_array_join($indata,6," ");
											$passthrough = true;
										} else {
											$passthrough = false;
											$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter any nicks");
										}
									} else {
										$passthrough = false;
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									if (count($indata) >= 6) {
										$tmpdata = $ch3wyb0t->_core->_array_join($indata,5," ");
										$passthrough = true;
									} else {
										$passthrough = false;
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter any nicks");
									}
								}
								if ($passthrough == true) {
									if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 5) {
										$ch3wyb0t->_core->_massmodes($id,$user,$chan,['REM','a',$tmpdata]);
									} else {
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'PROTECTME': {
							if ($ch3wyb0t->_core->_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										$passthrough = true;
									} else {
										$passthrough = false;
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									$passthrough = true;
								}
								if ($passthrough == true) {
									if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 4) {
										$ch3wyb0t->_core->_massmodes($id,$user,$chan,['ADD','a',$user]);
									} else {
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'DEPROTECTME': {
							if ($ch3wyb0t->_core->_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										$passthrough = true;
									} else {
										$passthrough = false;
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									$passthrough = true;
								}
								if ($passthrough == true) {
									if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 4) {
										$ch3wyb0t->_core->_massmodes($id,$user,$chan,['REM','a',$user]);
									} else {
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'MOP': {
							if ($ch3wyb0t->_core->_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										$passthrough = true;
									} else {
										$passthrough = false;
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									$passthrough = true;
								}
								if ($passthrough == true) {
									if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 3) {
										$ch3wyb0t->_core->_massmodes($id,$user,$chan,['ADD','o','ALL']);
									} else {
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'OP': {
							if ($ch3wyb0t->_core->_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										if (count($indata) >= 7) {
											$tmpdata = $ch3wyb0t->_core->_array_join($indata,6," ");
											$passthrough = true;
										} else {
											$passthrough = false;
											$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter any nicks");
										}
									} else {
										$passthrough = false;
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									if (count($indata) >= 6) {
										$tmpdata = $ch3wyb0t->_core->_array_join($indata,5," ");
										$passthrough = true;
									} else {
										$passthrough = false;
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter any nicks");
									}
								}
								if ($passthrough == true) {
									if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 3) {
										$ch3wyb0t->_core->_massmodes($id,$user,$chan,['ADD','o',$tmpdata]);
									} else {
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'MDEOP': {
							if ($ch3wyb0t->_core->_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										$passthrough = true;
									} else {
										$passthrough = false;
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									$passthrough = true;
								}
								if ($passthrough == true) {
									if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 3) {
										$ch3wyb0t->_core->_massmodes($id,$user,$chan,['REM','o','BC']);
									} else {
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'DEOP': {
							if ($ch3wyb0t->_core->_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										if (count($indata) >= 7) {
											$tmpdata = $ch3wyb0t->_core->_array_join($indata,6," ");
											$passthrough = true;
										} else {
											$passthrough = false;
											$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter any nicks");
										}
									} else {
										$passthrough = false;
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									if (count($indata) >= 6) {
										$tmpdata = $ch3wyb0t->_core->_array_join($indata,5," ");
										$passthrough = true;
									} else {
										$passthrough = false;
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter any nicks");
									}
								}
								if ($passthrough == true) {
									if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 3) {
										$ch3wyb0t->_core->_massmodes($id,$user,$chan,['REM','o',$tmpdata]);
									} else {
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'OPME': {
							if ($ch3wyb0t->_core->_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										$passthrough = true;
									} else {
										$passthrough = false;
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									$passthrough = true;
								}
								if ($passthrough == true) {
									if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 3) {
										$ch3wyb0t->_core->_massmodes($id,$user,$chan,['ADD','o',$user]);
									} else {
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'DEOPME': {
							if ($ch3wyb0t->_core->_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										$passthrough = true;
									} else {
										$passthrough = false;
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									$passthrough = true;
								}
								if ($passthrough == true) {
									if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 3) {
										$ch3wyb0t->_core->_massmodes($id,$user,$chan,['REM','o',$user]);
									} else {
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'MHALFOP': {
							if ($ch3wyb0t->_core->_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										$passthrough = true;
									} else {
										$passthrough = false;
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									$passthrough = true;
								}
								if ($passthrough == true) {
									if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 3) {
										$ch3wyb0t->_core->_massmodes($id,$user,$chan,['ADD','h','ALL']);
									} else {
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'HALFOP': {
							if ($ch3wyb0t->_core->_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										if (count($indata) >= 7) {
											$tmpdata = $ch3wyb0t->_core->_array_join($indata,6," ");
											$passthrough = true;
										} else {
											$passthrough = false;
											$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter any nicks");
										}
									} else {
										$passthrough = false;
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									if (count($indata) >= 6) {
										$tmpdata = $ch3wyb0t->_core->_array_join($indata,5," ");
										$passthrough = true;
									} else {
										$passthrough = false;
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter any nicks");
									}
								}
								if ($passthrough == true) {
									if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 3) {
										$ch3wyb0t->_core->_massmodes($id,$user,$chan,['ADD','h',$tmpdata]);
									} else {
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'MDEHALFOP': {
							if ($ch3wyb0t->_core->_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										$passthrough = true;
									} else {
										$passthrough = false;
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									$passthrough = true;
								}
								if ($passthrough == true) {
									if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 3) {
										$ch3wyb0t->_core->_massmodes($id,$user,$chan,['REM','h','BC']);
									} else {
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'DEHALFOP': {
							if ($ch3wyb0t->_core->_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										if (count($indata) >= 7) {
											$tmpdata = $ch3wyb0t->_core->_array_join($indata,6," ");
											$passthrough = true;
										} else {
											$passthrough = false;
											$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter any nicks");
										}
									} else {
										$passthrough = false;
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									if (count($indata) >= 6) {
										$tmpdata = $ch3wyb0t->_core->_array_join($indata,5," ");
										$passthrough = true;
									} else {
										$passthrough = false;
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter any nicks");
									}
								}
								if ($passthrough == true) {
									if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 3) {
										$ch3wyb0t->_core->_massmodes($id,$user,$chan,['REM','h',$tmpdata]);
									} else {
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'HALFOPME': {
							if ($ch3wyb0t->_core->_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										$passthrough = true;
									} else {
										$passthrough = false;
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									$passthrough = true;
								}
								if ($passthrough == true) {
									if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 2) {
										$ch3wyb0t->_core->_massmodes($id,$user,$chan,['ADD','h',$user]);
									} else {
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'DEHALFOPME': {
							if ($ch3wyb0t->_core->_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										$passthrough = true;
									} else {
										$passthrough = false;
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									$passthrough = true;
								}
								if ($passthrough == true) {
									if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 2) {
										$ch3wyb0t->_core->_massmodes($id,$user,$chan,['REM','h',$user]);
									} else {
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'MVOICE': {
							if ($ch3wyb0t->_core->_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										$passthrough = true;
									} else {
										$passthrough = false;
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									$passthrough = true;
								}
								if ($passthrough == true) {
									if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 2) {
										$ch3wyb0t->_core->_massmodes($id,$user,$chan,['ADD','v','ALL']);
									} else {
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'VOICE': {
							if ($ch3wyb0t->_core->_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										if (count($indata) >= 7) {
											$tmpdata = $ch3wyb0t->_core->_array_join($indata,6," ");
											$passthrough = true;
										} else {
											$passthrough = false;
											$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter any nicks");
										}
									} else {
										$passthrough = false;
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									if (count($indata) >= 6) {
										$tmpdata = $ch3wyb0t->_core->_array_join($indata,5," ");
										$passthrough = true;
									} else {
										$passthrough = false;
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter any nicks");
									}
								}
								if ($passthrough == true) {
									if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 2) {
										$ch3wyb0t->_core->_massmodes($id,$user,$chan,['ADD','v',$tmpdata]);
									} else {
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'MDEVOICE': {
							if ($ch3wyb0t->_core->_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										$passthrough = true;
									} else {
										$passthrough = false;
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									$passthrough = true;
								}
								if ($passthrough == true) {
									if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 2) {
										$ch3wyb0t->_core->_massmodes($id,$user,$chan,['REM','v','BC']);
									} else {
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'DEVOICE': {
							if ($ch3wyb0t->_core->_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										if (count($indata) >= 7) {
											$tmpdata = $ch3wyb0t->_core->_array_join($indata,6," ");
											$passthrough = true;
										} else {
											$passthrough = false;
											$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter any nicks");
										}
									} else {
										$passthrough = false;
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									if (count($indata) >= 6) {
										$tmpdata = $ch3wyb0t->_core->_array_join($indata,5," ");
										$passthrough = true;
									} else {
										$passthrough = false;
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter any nicks");
									}
								}
								if ($passthrough == true) {
									if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 2) {
										$ch3wyb0t->_core->_massmodes($id,$user,$chan,['REM','v',$tmpdata]);
									} else {
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'VOICEME': {
							if ($ch3wyb0t->_core->_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										$passthrough = true;
									} else {
										$passthrough = false;
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									$passthrough = true;
								}
								if ($passthrough == true) {
									if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 1) {
										$ch3wyb0t->_core->_massmodes($id,$user,$chan,['ADD','v',$user]);
									} else {
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'DEVOICEME': {
							if ($ch3wyb0t->_core->_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										$passthrough = true;
									} else {
										$passthrough = false;
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									$passthrough = true;
								}
								if ($passthrough == true) {
									if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 1) {
										$ch3wyb0t->_core->_massmodes($id,$user,$chan,['REM','v',$user]);
									} else {
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'SAY': {
							if ($ch3wyb0t->_core->_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										if (count($indata) >= 7) {
											$passthrough = true;
											$tmpoutput = $ch3wyb0t->_core->_array_join($indata,6," ");
										} else {
											$passthrough = false;
											$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a message");
										}
									} else {
										$passthrough = false;
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									if (count($indata) >= 6) { 
										$passthrough = true;
										$tmpoutput = $ch3wyb0t->_core->_array_join($indata,5," ");
									} else {
										$passthrough = false;
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a message");
									}
								}
								if ($passthrough == true) {
									if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 1) {
										$ch3wyb0t->_core->_sts($id,"PRIVMSG ".$chan." :".$tmpoutput);
									} else {
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'ACT': {
							if ($ch3wyb0t->_core->_islogged($id,$user)) {
								if (($type == 'PMSG') or ($type == 'PNOTE')) {
									if (count($indata) >= 6) {
										$chan = $indata[5];
										if (count($indata) >= 7) {
											$passthrough = true;
											$tmpoutput = $ch3wyb0t->_core->_array_join($indata,6," ");
										} else {
											$passthrough = false;
											$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a action");
										}
									} else {
										$passthrough = false;
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a channel");
									}
								}
								if (($type == 'CMSG') or ($type == 'CNOTE')) {
									if (count($indata) >= 6) { 
										$passthrough = true;
										$tmpoutput = $ch3wyb0t->_core->_array_join($indata,5," ");
									} else {
										$passthrough = false;
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"You didn't enter a action");
									}
								}
								if ($passthrough == true) {
									if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 1) {
										$ch3wyb0t->_core->_sts($id,"PRIVMSG ".$chan." :\x01ACTION ".$tmpoutput."\x01");
									} else {
										$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
									}
								}
							} else {
								$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'TESTCMD': {
							if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'GLOBAL') >= 7) {
								$ch3wyb0t->_log->_sprint($id." ".print_r($ch3wyb0t->sdata,true),'debug',false);
							} else {
								$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'TESTDATA': {
							if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'GLOBAL') >= 7) {
								$ch3wyb0t->_log->_sprint($id." ".print_r($ch3wyb0t->data,true),'debug',false);
							} else {
								$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						case 'TEST': {
							if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'GLOBAL') >= 7) {
								#$ch3wyb0t->_core->_sts($id,"MODE :".$ch3wyb0t->sdata['cons'][$id]['nick']);
								//$ch3wyb0t->_core->_massmodes($id,$user,$chan,['ADD','v','chewy Channel_Bot chewyb_13']);
								//$ch3wyb0t->_core->_massmodes($id,$user,$chan,['ADD','v','BC']);
								$ch3wyb0t->_core->_massmodes($id,$user,$chan,['ADD','v','ALL']);
								//$ch3wyb0t->_core->_massmodes($id,$user,$chan,['REM','v','chewy Channel_Bot chewyb_13']);
								//$ch3wyb0t->_core->_massmodes($id,$user,$chan,['REM','v','BC']);
								$ch3wyb0t->_core->_massmodes($id,$user,$chan,['REM','v','ALL']);

								$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"data blarg");
							} else {
								$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESS');
							}
							break;
						}
						default: {
							$ch3wyb0t->_log->_sprint($id." c1 ".print_r($indata,true),'debug',false);
							if (($type == 'CMSG') or ($type == 'CNOTE')) {
								$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'CHAN',"The command ".$indata[4]." doesn't exist at the momment");
							}
							if (($type == 'PMSG') or ($type == 'PNOTE')) {
								$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"The command ".$indata[4]." doesn't exist at the momment");
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
								if ($ch3wyb0t->data['data'][$id]['server']['nickservpass'] != 'NULL') {
									$ch3wyb0t->sdata['cons'][$id]['identified'] = 1;
								}
							} elseif (($indata[6] == 'NickServ') and ($indata[7] == 'IDENTIFY')) {
								if (($ch3wyb0t->data['data'][$id]['server']['nickservpass'] != 'NULL') and ($ch3wyb0t->sdata['cons'][$id]['identified'] == 1)) {
									$ch3wyb0t->sdata['cons'][$id]['identified'] = 2;
									$ch3wyb0t->_core->_sts($id,"PRIVMSG NickServ :IDENTIFY ".$ch3wyb0t->data['data'][$id]['server']['nickservpass']);
									$ch3wyb0t->_core->_autojoinchans($id);
								}
							}
						}
					} 
					else {
						$ch3wyb0t->_log->_sprint($id." n1 ".print_r($indata,true),'debug',false);
					}
				}
				else {
					#$ch3wyb0t->_core->_buildmsg($id,'NORMAL',$user,$chan,'PRIV',$output) #types are NORMAL,HELP,ERROR, mtype are PRIV,CHAN
					$ch3wyb0t->_log->_sprint($id." b1 ".print_r($indata,true),'debug',false);
				}
			}
		} 
		else {
			#$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"The command ".$indata[3]." doesn't exist at the momment");
			$ch3wyb0t->_log->_sprint($id." d1 ".print_r($indata,true),'debug',false);
		}
	}
	
	protected function _core_command_help($id,$user,$chan,$indata) {
		global $ch3wyb0t;
		$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',$ch3wyb0t->data['settings']['botname']." help system");
		$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"If you need help on a certain command go help <command>");
		$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',$ch3wyb0t->data['settings']['chancom'].$ch3wyb0t->data['settings']['signal']." = CHAN, ".$ch3wyb0t->data['settings']['dcccom'].$ch3wyb0t->data['settings']['signal']." = DCC, ".$ch3wyb0t->data['settings']['pvtcom'].$ch3wyb0t->data['settings']['signal']." = MSG");
		if (count($indata) >= 6) {
			if ($chan == $indata[5]) {
				if (count($indata) >= 7) {
					$hcmds = $ch3wyb0t->_core->_array_rearrange($indata,6);
					$processhelp = true;
				} else {
					$processhelp = false;
				}
			} else {
				$hcmds = $ch3wyb0t->_core->_array_rearrange($indata,5);
				$processhelp = true;
			}
		} else {
			$processhelp = false;
		}
		if ($processhelp == true) {
			switch(strtoupper($hcmds[0])) {
				case 'RAW': {
					if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'GLOBAL') >= 7) {
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(RAW)- This Command is super dangerous as it will whatever is entered into it to the server");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(RAW)- Command Structure: ".$ch3wyb0t->data['settings']['pvtcom'].$ch3wyb0t->data['settings']['signal']." raw <data to send>");
						#$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(RAW)- Command Structure: ".$ch3wyb0t->data['settings']['dcccom'].$ch3wyb0t->data['settings']['signal']." raw <data to send>");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(RAW)- Command Structure: ".$ch3wyb0t->data['settings']['chancom'].$ch3wyb0t->data['settings']['signal']." raw <data to send>");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(RAW)- \x034It is highly recommended you DO NOT use this command\x03");
					} else {
						$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'RAWDB': {
					if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'GLOBAL') >= 7) {
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(RAWDB)- This Command is super dangerous as it will whatever is entered into it to the database");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(RAWDB)- Command Structure: ".$ch3wyb0t->data['settings']['pvtcom'].$ch3wyb0t->data['settings']['signal']." rawdb <data to send>");
						#$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(RAWDB)- Command Structure: ".$ch3wyb0t->data['settings']['dcccom'].$ch3wyb0t->data['settings']['signal']." rawdb <data to send>");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(RAWDB)- Command Structure: ".$ch3wyb0t->data['settings']['chancom'].$ch3wyb0t->data['settings']['signal']." rawdb <data to send>");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(RAWDB)- \x034It is highly recommended you DO NOT use this command\x03");
					} else {
						$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'EXIT': {
					if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'GLOBAL') >= 6) {
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(EXIT)- This command will cause the bot to exit completely");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(EXIT)- Command Structure: ".$ch3wyb0t->data['settings']['pvtcom'].$ch3wyb0t->data['settings']['signal']." exit [<message>]");
						#$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(EXIT)- Command Structure: ".$ch3wyb0t->data['settings']['dcccom'].$ch3wyb0t->data['settings']['signal']." exit [<message>]");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(EXIT)- Command Structure: ".$ch3wyb0t->data['settings']['chancom'].$ch3wyb0t->data['settings']['signal']." exit [<message>]");
					} else {
						$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'RELOAD': {
					if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'GLOBAL') >= 6) {
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(RELOAD)- This command will cause the bot to exit so it can restart fresh");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(RELOAD)- Command Structure: ".$ch3wyb0t->data['settings']['pvtcom'].$ch3wyb0t->data['settings']['signal']." reload");
						#$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(RELOAD)- Command Structure: ".$ch3wyb0t->data['settings']['dcccom'].$ch3wyb0t->data['settings']['signal']." reload");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(RELOAD)- Command Structure: ".$ch3wyb0t->data['settings']['chancom'].$ch3wyb0t->data['settings']['signal']." reload");
					} else {
						$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'QUIT': {
					if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'GLOBAL') >= 6) {
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(QUIT)- This command will cause the bot to quit from the current network");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(QUIT)- Command Structure: ".$ch3wyb0t->data['settings']['pvtcom'].$ch3wyb0t->data['settings']['signal']." quit [<message>]");
						#$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(QUIT)- Command Structure: ".$ch3wyb0t->data['settings']['dcccom'].$ch3wyb0t->data['settings']['signal']." quit [<message>]");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(QUIT)- Command Structure: ".$ch3wyb0t->data['settings']['chancom'].$ch3wyb0t->data['settings']['signal']." quit [<message>]");
					} else {
						$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'REHASH': {
					if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'GLOBAL') >= 6) {
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(REHASH)- This command will cause the bot to reload from the database");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(REHASH)- Command Structure: ".$ch3wyb0t->data['settings']['pvtcom'].$ch3wyb0t->data['settings']['signal']." rehash");
						#$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(REHASH)- Command Structure: ".$ch3wyb0t->data['settings']['dcccom'].$ch3wyb0t->data['settings']['signal']." rehash");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(REHASH)- Command Structure: ".$ch3wyb0t->data['settings']['chancom'].$ch3wyb0t->data['settings']['signal']." rehash");
					} else {
						$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'SETTINGS': {
					if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'GLOBAL') >= 6) {
						if (count($hcmds) >= 2) {
							switch (strtoupper($hcmds[1])) {
								case 'LIST': {
									$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(SETTINGS)-(LIST)- This Command list the values that are currently in the bots settings");
									$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(SETTINGS)-(LIST)- Command Structure: ".$ch3wyb0t->data['settings']['pvtcom'].$ch3wyb0t->data['settings']['signal']." settings list");
									break;
								} 
								case 'SET': {
									$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(SETTINGS)-(SET)- This Command will set the value you pick and update both local and the db");
									$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(SETTINGS)-(SET)- Command Structure: ".$ch3wyb0t->data['settings']['pvtcom'].$ch3wyb0t->data['settings']['signal']." settings set <setting> <value>");
									break;
								}
								default: {
									$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(SETTINGS)- The help topic settings ".$hcmds[1]." is not in the database");
									break;
								}
							}
						} else {
							$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(SETTINGS)- This Command deals with the bot's settings");
							$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(SETTINGS)- Command Structure: ".$ch3wyb0t->data['settings']['pvtcom'].$ch3wyb0t->data['settings']['signal']." settings [<list>][<set> <setting> <value>]");
							$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(SETTINGS)- Topics available: list set");
						}
					} else {
						$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'MOWNER': {
					if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 5) {
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MOWNER)- This command will Owner everyone in <channel>");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MOWNER)- Command Structure: ".$ch3wyb0t->data['settings']['pvtcom'].$ch3wyb0t->data['settings']['signal']." MOwner <channel>");
						#$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MOWNER)- Command Structure: ".$ch3wyb0t->data['settings']['dcccom'].$ch3wyb0t->data['settings']['signal']." MOwner <channel>");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MOWNER)- Command Structure: ".$ch3wyb0t->data['settings']['chancom'].$ch3wyb0t->data['settings']['signal']." MOwner");
					} else {
						$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'OWNER': {
					if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 5) {
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(OWNER)- This command will Owner the <nicks> you pick on <channel>");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(OWNER)- Command Structure: ".$ch3wyb0t->data['settings']['pvtcom'].$ch3wyb0t->data['settings']['signal']." Owner <channel> <nick> [<nick> [<nick>]]");
						#$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(OWNER)- Command Structure: ".$ch3wyb0t->data['settings']['dcccom'].$ch3wyb0t->data['settings']['signal']." Owner <channel> <nick> [<nick> [<nick>]]");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(OWNER)- Command Structure: ".$ch3wyb0t->data['settings']['chancom'].$ch3wyb0t->data['settings']['signal']." Owner <nick> [<nick> [<nick>]]");
					} else {
						$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'MDEOWNER': {
					if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 5) {
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MDEOWNER)- This command will De-Owner everyone in <channel> but the bot and you");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MDEOWNER)- Command Structure: ".$ch3wyb0t->data['settings']['pvtcom'].$ch3wyb0t->data['settings']['signal']." MDeOwner <channel>");
						#$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MDEOWNER)- Command Structure: ".$ch3wyb0t->data['settings']['dcccom'].$ch3wyb0t->data['settings']['signal']." MDeOwner <channel>");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MDEOWNER)- Command Structure: ".$ch3wyb0t->data['settings']['chancom'].$ch3wyb0t->data['settings']['signal']." MDeOwner");
					} else {
						$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'DEOWNER': {
					if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 5) {
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEOWNER)- This command will De-Owner the <nicks> you pick on <channel>");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEOWNER)- Command Structure: ".$ch3wyb0t->data['settings']['pvtcom'].$ch3wyb0t->data['settings']['signal']." DeOwner <channel> <nick> [<nick> [<nick>]]");
						#$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEOWNER)- Command Structure: ".$ch3wyb0t->data['settings']['dcccom'].$ch3wyb0t->data['settings']['signal']." DeOwner <channel> <nick> [<nick> [<nick>]]");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEOWNER)- Command Structure: ".$ch3wyb0t->data['settings']['chancom'].$ch3wyb0t->data['settings']['signal']." DeOwner <nick> [<nick> [<nick>]]");
					} else {
						$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'OWNERME': {
					if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 5) {
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(OWNERME)- This command will Owner yourself on <channel>");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(OWNERME)- Command Structure: ".$ch3wyb0t->data['settings']['pvtcom'].$ch3wyb0t->data['settings']['signal']." OwnerMe <channel>");
						#$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(OWNERME)- Command Structure: ".$ch3wyb0t->data['settings']['dcccom'].$ch3wyb0t->data['settings']['signal']." OwnerMe <channel>");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(OWNERME)- Command Structure: ".$ch3wyb0t->data['settings']['chancom'].$ch3wyb0t->data['settings']['signal']." OwnerMe");
					} else {
						$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'DEOWNERME': {
					if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 5) {
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEOWNERME)- This command will de-Owner yourself on <channel>");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEOWNERME)- Command Structure: ".$ch3wyb0t->data['settings']['pvtcom'].$ch3wyb0t->data['settings']['signal']." DeOwnerMe <channel>");
						#$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEOWNERME)- Command Structure: ".$ch3wyb0t->data['settings']['dcccom'].$ch3wyb0t->data['settings']['signal']." DeOwnerMe <channel>");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEOWNERME)- Command Structure: ".$ch3wyb0t->data['settings']['chancom'].$ch3wyb0t->data['settings']['signal']." DeOwnerMe");
					} else {
						$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'MPROTECT': {
					if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 5) {
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MPROTECT)- This command will Protect everyone in <channel>");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MPROTECT)- Command Structure: ".$ch3wyb0t->data['settings']['pvtcom'].$ch3wyb0t->data['settings']['signal']." MProtect <channel>");
						#$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MPROTECT)- Command Structure: ".$ch3wyb0t->data['settings']['dcccom'].$ch3wyb0t->data['settings']['signal']." MProtect <channel>");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MPROTECT)- Command Structure: ".$ch3wyb0t->data['settings']['chancom'].$ch3wyb0t->data['settings']['signal']." MProtect");
					} else {
						$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'PROTECT': {
					if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 5) {
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(PROTECT)- This command will Protect the <nicks> you pick on <channel>");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(PROTECT)- Command Structure: ".$ch3wyb0t->data['settings']['pvtcom'].$ch3wyb0t->data['settings']['signal']." Protect <channel> <nick> [<nick> [<nick>]]");
						#$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(PROTECT)- Command Structure: ".$ch3wyb0t->data['settings']['dcccom'].$ch3wyb0t->data['settings']['signal']." Protect <channel> <nick> [<nick> [<nick>]]");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(PROTECT)- Command Structure: ".$ch3wyb0t->data['settings']['chancom'].$ch3wyb0t->data['settings']['signal']." Protect <nick> [<nick> [<nick>]]");
					} else {
						$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'MDEPROTECT': {
					if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 5) {
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MDEPROTECT)- This command will De-Protect everyone in <channel> but the bot and you");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MDEPROTECT)- Command Structure: ".$ch3wyb0t->data['settings']['pvtcom'].$ch3wyb0t->data['settings']['signal']." MDeProtect <channel>");
						#$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MDEPROTECT)- Command Structure: ".$ch3wyb0t->data['settings']['dcccom'].$ch3wyb0t->data['settings']['signal']." MDeProtect <channel>");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MDEPROTECT)- Command Structure: ".$ch3wyb0t->data['settings']['chancom'].$ch3wyb0t->data['settings']['signal']." MDeProtect");
					} else {
						$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'DEPROTECT': {
					if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 5) {
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEPROTECT)- This command will De-Protect the <nicks> you pick on <channel>");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEPROTECT)- Command Structure: ".$ch3wyb0t->data['settings']['pvtcom'].$ch3wyb0t->data['settings']['signal']." DeProtect <channel> <nick> [<nick> [<nick>]]");
						#$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEPROTECT)- Command Structure: ".$ch3wyb0t->data['settings']['dcccom'].$ch3wyb0t->data['settings']['signal']." DeProtect <channel> <nick> [<nick> [<nick>]]");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEPROTECT)- Command Structure: ".$ch3wyb0t->data['settings']['chancom'].$ch3wyb0t->data['settings']['signal']." DeProtect <nick> [<nick> [<nick>]]");
					} else {
						$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'PROTECTME': {
					if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 4) {
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(PROTECTME)- This command will Protect yourself on <channel>");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(PROTECTME)- Command Structure: ".$ch3wyb0t->data['settings']['pvtcom'].$ch3wyb0t->data['settings']['signal']." ProtectMe <channel>");
						#$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(PROTECTME)- Command Structure: ".$ch3wyb0t->data['settings']['dcccom'].$ch3wyb0t->data['settings']['signal']." ProtectMe <channel>");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(PROTECTME)- Command Structure: ".$ch3wyb0t->data['settings']['chancom'].$ch3wyb0t->data['settings']['signal']." ProtectMe");
					} else {
						$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'DEPROTECTME': {
					if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 4) {
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEPROTECTME)- This command will de-Protect yourself on <channel>");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEPROTECTME)- Command Structure: ".$ch3wyb0t->data['settings']['pvtcom'].$ch3wyb0t->data['settings']['signal']." DeProtectMe <channel>");
						#$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEPROTECTME)- Command Structure: ".$ch3wyb0t->data['settings']['dcccom'].$ch3wyb0t->data['settings']['signal']." DeProtectMe <channel>");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEPROTECTME)- Command Structure: ".$ch3wyb0t->data['settings']['chancom'].$ch3wyb0t->data['settings']['signal']." DeProtectMe");
					} else {
						$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'MOP': {
					if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 3) {
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MOP)- This command will Op everyone in <channel>");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MOP)- Command Structure: ".$ch3wyb0t->data['settings']['pvtcom'].$ch3wyb0t->data['settings']['signal']." MOp <channel>");
						#$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MOP)- Command Structure: ".$ch3wyb0t->data['settings']['dcccom'].$ch3wyb0t->data['settings']['signal']." MOp <channel>");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MOP)- Command Structure: ".$ch3wyb0t->data['settings']['chancom'].$ch3wyb0t->data['settings']['signal']." MOp");
					} else {
						$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'OP': {
					if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 3) {
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(OP)- This command will Op the <nicks> you pick on <channel>");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(OP)- Command Structure: ".$ch3wyb0t->data['settings']['pvtcom'].$ch3wyb0t->data['settings']['signal']." Op <channel> <nick> [<nick> [<nick>]]");
						#$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(OP)- Command Structure: ".$ch3wyb0t->data['settings']['dcccom'].$ch3wyb0t->data['settings']['signal']." Op <channel> <nick> [<nick> [<nick>]]");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(OP)- Command Structure: ".$ch3wyb0t->data['settings']['chancom'].$ch3wyb0t->data['settings']['signal']." Op <nick> [<nick> [<nick>]]");
					} else {
						$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'MDEOP': {
					if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 3) {
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MDEOP)- This command will De-Op everyone in <channel> but the bot and you");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MDEOP)- Command Structure: ".$ch3wyb0t->data['settings']['pvtcom'].$ch3wyb0t->data['settings']['signal']." MDeOp <channel>");
						#$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MDEOP)- Command Structure: ".$ch3wyb0t->data['settings']['dcccom'].$ch3wyb0t->data['settings']['signal']." MDeOp <channel>");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MDEOP)- Command Structure: ".$ch3wyb0t->data['settings']['chancom'].$ch3wyb0t->data['settings']['signal']." MDeOp");
					} else {
						$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'DEOP': {
					if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 3) {
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEOP)- This command will De-Op the <nicks> you pick on <channel>");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEOP)- Command Structure: ".$ch3wyb0t->data['settings']['pvtcom'].$ch3wyb0t->data['settings']['signal']." DeOp <channel> <nick> [<nick> [<nick>]]");
						#$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEOP)- Command Structure: ".$ch3wyb0t->data['settings']['dcccom'].$ch3wyb0t->data['settings']['signal']." DeOp <channel> <nick> [<nick> [<nick>]]");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEOP)- Command Structure: ".$ch3wyb0t->data['settings']['chancom'].$ch3wyb0t->data['settings']['signal']." DeOp <nick> [<nick> [<nick>]]");
					} else {
						$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'OPME': {
					if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 3) {
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(OPME)- This command will Op yourself on <channel>");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(OPME)- Command Structure: ".$ch3wyb0t->data['settings']['pvtcom'].$ch3wyb0t->data['settings']['signal']." OpMe <channel>");
						#$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(OPME)- Command Structure: ".$ch3wyb0t->data['settings']['dcccom'].$ch3wyb0t->data['settings']['signal']." OpMe <channel>");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(OPME)- Command Structure: ".$ch3wyb0t->data['settings']['chancom'].$ch3wyb0t->data['settings']['signal']." OpMe");
					} else {
						$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'DEOPME': {
					if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 3) {
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEOPME)- This command will de-Op yourself on <channel>");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEOPME)- Command Structure: ".$ch3wyb0t->data['settings']['pvtcom'].$ch3wyb0t->data['settings']['signal']." DeOpMe <channel>");
						#$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEOPME)- Command Structure: ".$ch3wyb0t->data['settings']['dcccom'].$ch3wyb0t->data['settings']['signal']." DeOpMe <channel>");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEOPME)- Command Structure: ".$ch3wyb0t->data['settings']['chancom'].$ch3wyb0t->data['settings']['signal']." DeOpMe");
					} else {
						$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'MHALFOP': {
					if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 3) {
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MHALFOP)- This command will HalfOp everyone in <channel>");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MHALFOP)- Command Structure: ".$ch3wyb0t->data['settings']['pvtcom'].$ch3wyb0t->data['settings']['signal']." MHalfOp <channel>");
						#$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MHALFOP)- Command Structure: ".$ch3wyb0t->data['settings']['dcccom'].$ch3wyb0t->data['settings']['signal']." MHalfOp <channel>");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MHALFOP)- Command Structure: ".$ch3wyb0t->data['settings']['chancom'].$ch3wyb0t->data['settings']['signal']." MHalfOp");
					} else {
						$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'HALFOP': {
					if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 3) {
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(HALFOP)- This command will HalfOp the <nicks> you pick on <channel>");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(HALFOP)- Command Structure: ".$ch3wyb0t->data['settings']['pvtcom'].$ch3wyb0t->data['settings']['signal']." HalfOp <channel> <nick> [<nick> [<nick>]]");
						#$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(HALFOP)- Command Structure: ".$ch3wyb0t->data['settings']['dcccom'].$ch3wyb0t->data['settings']['signal']." HalfOp <channel> <nick> [<nick> [<nick>]]");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(HALFOP)- Command Structure: ".$ch3wyb0t->data['settings']['chancom'].$ch3wyb0t->data['settings']['signal']." HalfOp <nick> [<nick> [<nick>]]");
					} else {
						$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'MDEHALFOP': {
					if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 3) {
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MDEHALFOP)- This command will De-HalfOp everyone in <channel> but the bot and you");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MDEHALFOP)- Command Structure: ".$ch3wyb0t->data['settings']['pvtcom'].$ch3wyb0t->data['settings']['signal']." MDeHalfOp <channel>");
						#$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MDEHALFOP)- Command Structure: ".$ch3wyb0t->data['settings']['dcccom'].$ch3wyb0t->data['settings']['signal']." MDeHalfOp <channel>");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MDEHALFOP)- Command Structure: ".$ch3wyb0t->data['settings']['chancom'].$ch3wyb0t->data['settings']['signal']." MDeHalfOp");
					} else {
						$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'DEHALFOP': {
					if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 3) {
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEHALFOP)- This command will de-HalfOp the <nicks> you pick on <channel>");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEHALFOP)- Command Structure: ".$ch3wyb0t->data['settings']['pvtcom'].$ch3wyb0t->data['settings']['signal']." DeHalfOp <channel> <nick> [<nick> [<nick>]]");
						#$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEHALFOP)- Command Structure: ".$ch3wyb0t->data['settings']['dcccom'].$ch3wyb0t->data['settings']['signal']." DeHalfOp <channel> <nick> [<nick> [<nick>]]");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEHALFOP)- Command Structure: ".$ch3wyb0t->data['settings']['chancom'].$ch3wyb0t->data['settings']['signal']." DeHalfOp <nick> [<nick> [<nick>]]");
					} else {
						$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'HALFOPME': {
					if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 2) {
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(HALFOPME)- This command will HalfOp yourself on <channel>");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(HALFOPME)- Command Structure: ".$ch3wyb0t->data['settings']['pvtcom'].$ch3wyb0t->data['settings']['signal']." HalfOpMe <channel>");
						#$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(HALFOPME)- Command Structure: ".$ch3wyb0t->data['settings']['dcccom'].$ch3wyb0t->data['settings']['signal']." HalfOpMe <channel>");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(HALFOPME)- Command Structure: ".$ch3wyb0t->data['settings']['chancom'].$ch3wyb0t->data['settings']['signal']." HalfOpMe");
					} else {
						$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'DEHALFOPME': {
					if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 2) {
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEHALFOPME)- This command will de-HalfOp yourself on <channel>");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEHALFOPME)- Command Structure: ".$ch3wyb0t->data['settings']['pvtcom'].$ch3wyb0t->data['settings']['signal']." DeHalfOpMe <channel>");
						#$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEHALFOPME)- Command Structure: ".$ch3wyb0t->data['settings']['dcccom'].$ch3wyb0t->data['settings']['signal']." DeHalfOpMe <channel>");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEHALFOPME)- Command Structure: ".$ch3wyb0t->data['settings']['chancom'].$ch3wyb0t->data['settings']['signal']." DeHalfOpMe");
					} else {
						$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'MVOICE': {
					if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 2) {
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MVOICE)- This command will Voice everyone in <channel>");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MVOICE)- Command Structure: ".$ch3wyb0t->data['settings']['pvtcom'].$ch3wyb0t->data['settings']['signal']." MVoice <channel>");
						#$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MVOICE)- Command Structure: ".$ch3wyb0t->data['settings']['dcccom'].$ch3wyb0t->data['settings']['signal']." MVoice <channel>");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MVOICE)- Command Structure: ".$ch3wyb0t->data['settings']['chancom'].$ch3wyb0t->data['settings']['signal']." MVoice");
					} else {
						$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'VOICE': {
					if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 2) {
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(VOICE)- This command will Voice the <nicks> you pick on <channel>");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(VOICE)- Command Structure: ".$ch3wyb0t->data['settings']['pvtcom'].$ch3wyb0t->data['settings']['signal']." Voice <channel> <nick> [<nick> [<nick>]]");
						#$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(VOICE)- Command Structure: ".$ch3wyb0t->data['settings']['dcccom'].$ch3wyb0t->data['settings']['signal']." Voice <channel> <nick> [<nick> [<nick>]]");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(VOICE)- Command Structure: ".$ch3wyb0t->data['settings']['chancom'].$ch3wyb0t->data['settings']['signal']." Voice <nick> [<nick> [<nick>]]");
					} else {
						$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'MDEVOICE': {
					if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 2) {
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MDEVOICE)- This command will De-Voice everyone in <channel> but the bot and you");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MDEVOICE)- Command Structure: ".$ch3wyb0t->data['settings']['pvtcom'].$ch3wyb0t->data['settings']['signal']." MDeVoice <channel>");
						#$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MDEVOICE)- Command Structure: ".$ch3wyb0t->data['settings']['dcccom'].$ch3wyb0t->data['settings']['signal']." MDeVoice <channel>");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(MDEVOICE)- Command Structure: ".$ch3wyb0t->data['settings']['chancom'].$ch3wyb0t->data['settings']['signal']." MDeVoice");
					} else {
						$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'DEVOICE': {
					if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 2) {
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEVOICE)- This command will de-voice the <nicks> you pick on <channel>");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEVOICE)- Command Structure: ".$ch3wyb0t->data['settings']['pvtcom'].$ch3wyb0t->data['settings']['signal']." DeVoice <channel> <nick> [<nick> [<nick>]]");
						#$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEVOICE)- Command Structure: ".$ch3wyb0t->data['settings']['dcccom'].$ch3wyb0t->data['settings']['signal']." DeVoice <channel> <nick> [<nick> [<nick>]]");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEVOICE)- Command Structure: ".$ch3wyb0t->data['settings']['chancom'].$ch3wyb0t->data['settings']['signal']." DeVoice <nick> [<nick> [<nick>]]");
					} else {
						$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'VOICEME': {
					if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 1) {
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(VOICEME)- This command will voice yourself on <channel>");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(VOICEME)- Command Structure: ".$ch3wyb0t->data['settings']['pvtcom'].$ch3wyb0t->data['settings']['signal']." VoiceMe <channel>");
						#$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(VOICEME)- Command Structure: ".$ch3wyb0t->data['settings']['dcccom'].$ch3wyb0t->data['settings']['signal']." VoiceMe <channel>");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(VOICEME)- Command Structure: ".$ch3wyb0t->data['settings']['chancom'].$ch3wyb0t->data['settings']['signal']." VoiceMe");
					} else {
						$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'DEVOICEME': {
					if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 1) {
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEVOICEME)- This command will de-voice yourself on <channel>");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEVOICEME)- Command Structure: ".$ch3wyb0t->data['settings']['pvtcom'].$ch3wyb0t->data['settings']['signal']." DeVoiceMe <channel>");
						#$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEVOICEME)- Command Structure: ".$ch3wyb0t->data['settings']['dcccom'].$ch3wyb0t->data['settings']['signal']." DeVoiceMe <channel>");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(DEVOICEME)- Command Structure: ".$ch3wyb0t->data['settings']['chancom'].$ch3wyb0t->data['settings']['signal']." DeVoiceMe");
					} else {
						$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'SAY': {
					if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 1) {
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(SAY)- This command will cause the bot to say a message on a channel");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(SAY)- Command Structure: ".$ch3wyb0t->data['settings']['pvtcom'].$ch3wyb0t->data['settings']['signal']." say <channel> <message>");
						#$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(SAY)- Command Structure: ".$ch3wyb0t->data['settings']['dcccom'].$ch3wyb0t->data['settings']['signal']." say <channel> <message>");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(SAY)- Command Structure: ".$ch3wyb0t->data['settings']['chancom'].$ch3wyb0t->data['settings']['signal']." say <message>");
					} else {
						$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'ACT': {
					if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 1) {
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(ACT)- This command will cause the bot to do a action on a channel");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(ACT)- Command Structure: ".$ch3wyb0t->data['settings']['pvtcom'].$ch3wyb0t->data['settings']['signal']." act <channel> <action>");
						#$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(ACT)- Command Structure: ".$ch3wyb0t->data['settings']['dcccom'].$ch3wyb0t->data['settings']['signal']." act <channel> <action>");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(ACT)- Command Structure: ".$ch3wyb0t->data['settings']['chancom'].$ch3wyb0t->data['settings']['signal']." act <action>");
					} else {
						$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOACCESSHELP');
					}
					break;
				}
				case 'ACCOUNT': {
					if ($ch3wyb0t->_core->_islogged($id,$user) == true) {
						if (count($hcmds) >= 2) {
							switch (strtoupper($hcmds[1])) {
								case 'CHGPASS': {
									$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(ACCOUNT)-(CHGPASS)- This Command will allow you to change your password");
									$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(ACCOUNT)-(CHGPASS)- Command Structure: ".$ch3wyb0t->data['settings']['pvtcom'].$ch3wyb0t->data['settings']['signal']." account chgpass <old pass> <new pass>");
									break;
								} 
								case 'MSGTYPE': {
									$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(ACCOUNT)-(MSGTYPE)- This Command will allow you to change your Message Type");
									$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(ACCOUNT)-(MSGTYPE)- Command Structure: ".$ch3wyb0t->data['settings']['pvtcom'].$ch3wyb0t->data['settings']['signal']." account msgtype <notice/msg>");
									break;
								}
								default: {
									$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(ACCOUNT)- The help topic account ".$hcmds[1]." is not in the database");
									break;
								}
							}
						} else {
							$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(ACCOUNT)- This Command will allow the user to do some modificatios to their account");
							$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(ACCOUNT)- Command Structure: ".$ch3wyb0t->data['settings']['pvtcom'].$ch3wyb0t->data['settings']['signal']." account <chgpass/msgtype>");
							$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(ACCOUNT)- Topics available: chgpass msgtype");
						}
					} else {
						$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOTLOGGED');
					}
					break;
				}
				case 'LOGOUT': {
					if ($ch3wyb0t->_core->_islogged($id,$user) == true) {
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(LOGOUT)- This Command will logout from the bot, this is the only command that works with users that is allowed in channel");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(LOGOUT)- Command Structure: ".$ch3wyb0t->data['settings']['pvtcom'].$ch3wyb0t->data['settings']['signal']." logout");
						//$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(LOGOUT)- Command Structure: ".$ch3wyb0t->data['settings']['dcccom'].$ch3wyb0t->data['settings']['signal']." logout");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(LOGOUT)- Command Structure: ".$ch3wyb0t->data['settings']['chancom'].$ch3wyb0t->data['settings']['signal']." logout");
					} else {
						$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','NOTLOGGED');
					}
					break;
				}
				case 'LOGIN': {
					if ($ch3wyb0t->_core->_islogged($id,$user) == false) {
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(LOGIN)- This Command will login to the bot, should the username and password be right");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(LOGIN)- Command Structure: ".$ch3wyb0t->data['settings']['pvtcom'].$ch3wyb0t->data['settings']['signal']." login <username> <password>");
					} else {
						$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','LOGGED');
					}
					break;
				}
				case 'REGISTER': {
					if ($ch3wyb0t->_core->_islogged($id,$user) == false) {
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(REGISTER)- This Command will register a user tot he bot if that username doesn't already exists");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(REGISTER)- Command Structure: ".$ch3wyb0t->data['settings']['pvtcom'].$ch3wyb0t->data['settings']['signal']." register <username> <password>");
					} else {
						$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV','LOGGED');
					}
					break;
				}
				case 'HELP': {
					$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(HELP)- This Command Displays The Help System and Certain Command information");
					$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(HELP)- Command Structure: ".$ch3wyb0t->data['settings']['pvtcom'].$ch3wyb0t->data['settings']['signal']." help <channel> <topic>");
					#$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(HELP)- Command Structure: ".$ch3wyb0t->data['settings']['dcccom'].$ch3wyb0t->data['settings']['signal']." help <channel> <topic>");
					$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(HELP)- Command Structure: ".$ch3wyb0t->data['settings']['chancom'].$ch3wyb0t->data['settings']['signal']." help <topic>");
					break;
				}
				case 'WHOIS': {
					$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(WHOIS)- This Command will send you a whois on the <nick> you choose");
					$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(WHOIS)- Command Structure: ".$ch3wyb0t->data['settings']['pvtcom'].$ch3wyb0t->data['settings']['signal']." whois <channel> <nick>");
					#$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(WHOIS)- Command Structure: ".$ch3wyb0t->data['settings']['dcccom'].$ch3wyb0t->data['settings']['signal']." whois <channel> <nick>");
					$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(WHOIS)- Command Structure: ".$ch3wyb0t->data['settings']['chancom'].$ch3wyb0t->data['settings']['signal']." whois <nick>");
					break;
				}
				case 'WHOAMI': {
					$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(WHOAMI)- This Command will send you a whois on your current logged in user account");
					$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(WHOAMI)- Command Structure: ".$ch3wyb0t->data['settings']['pvtcom'].$ch3wyb0t->data['settings']['signal']." whoami <channel>");
					#$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(WHOAMI)- Command Structure: ".$ch3wyb0t->data['settings']['dcccom'].$ch3wyb0t->data['settings']['signal']." whoami <channel>");
					$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"-(WHOAMI)- Command Structure: ".$ch3wyb0t->data['settings']['chancom'].$ch3wyb0t->data['settings']['signal']." whoami");
					break;
				}
				default: {
					$ch3wyb0t->_core->_buildmsg($id,'ERROR',$user,$chan,'PRIV',"The help topic ".$hcmds[0]." is not in the database");
					break;
				}
			}			
		} else {
			$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"The bot has the following Commands Available");
			if ($ch3wyb0t->_core->_islogged($id,$user) == true) {
				if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'GLOBAL') >= 7) {
					$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"Creator Level Access (7) Only (Due to dangerous level to bot and system):");
					$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"Raw Rawdb");
				}
				if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 6) {
					#Master & Creator Commands 6/7 Global, 6 Server, 6 Channel
					$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"Master Level Access (6):");
					if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'GLOBAL') >= 6) {
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"Exit Rehash Settings Server User");
					}
					if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'SERVER') >= 6) {
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"Quit Channel");
					}
					if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 6) {
						#nothing atm
					}
				}
				if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 5) {
					#Owner Commands - 5 Global, 5 Server, 5 CHANNEL
					$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"Owner Level Access (5):");
					if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'GLOBAL') >= 5) {
						#nothing atm
					}
					if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'SERVER') >= 5) {
						#nothing atm
					}
					if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 5) {
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"MOwner Owner MDeOwner DeOwner Ownerme DeOwnerme");
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"MProtect Protect MDeProtect DeProtect");
					}
				}
				if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 4) {
					#Protected Commands - 4 Global, 4 Server, 4 CHANNEL
					$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"Protected Level Access (4):");
					if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'GLOBAL') >= 4) {
						#nothing atm
					}
					if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'SERVER') >= 4) {
						#nothing atm
					}
					if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 4) {
						$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"Protectme DeProtectme"); #Access
					}
				}
				if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 3) {
					#Op Commands - 3 Global, 3 Server, 3 CHANNEL
					$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"Op Level Access (3):");
					$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"MOp Op MDeOp Opme DeOpme");
					$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"MHalfop Halfop MDeHalfop DeHalfop");
				}
				if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 2) {
					#Half-Op Commands - 2 Global, 2 Server, 2 CHANNEL
					$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"Half-Op Level Access (2):");
					$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"Halfopme DeHalfopme MVoice Voice MDeVoice DeVoice");
					#Channel Kick Ban
				}
				if ($ch3wyb0t->_core->_get_access_logged($id,$user,$chan,'CHANNEL') >= 1) {
					#Voice Commands - 1 Global, 1 Server, 1 CHANNEL
					$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"Voice Level Access (1):");
					$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"Voiceme DeVoiceme Say Act");
				}
				#Logged in with - 0 Global, 0 Server, 0 CHANNEL
				$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"Logged In Access (0):");
				$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"Account Logout");
			} else {
				#Logged out with - 0 Global, 0 Server, 0 Channel
				$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"Logged out Access (0):");
				$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"Login Register");
			}
			#Anyone Commands - 0 Global, 0 Server, 0 Channel
			$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"Anyone Can Access (0):");
			$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"Help Whoami Whois");
			$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"Pvt Command: ".$ch3wyb0t->data['settings']['pvtcom'].$ch3wyb0t->data['settings']['signal'].", Channel Command: ".$ch3wyb0t->data['settings']['chancom'].$ch3wyb0t->data['settings']['signal']);
		}
		$ch3wyb0t->_core->_buildmsg($id,'HELP',$user,$chan,'PRIV',"End of ".$ch3wyb0t->data['settings']['botname']." help system");
	}
	
	protected function _core_parse_data($id,$data) {
		global $ch3wyb0t;
		$ch3wyb0t->_log->_screen($id,'in',$data);
		$indata = explode(" ",$data);
		$rawdata = explode(" ",$data);
		$indata = str_replace(":","",$indata);
		$address = explode("!",$indata[0]);
		$sender = $address[0];
		if ($indata[0] == 'PING') {
			$ch3wyb0t->_core->_sts($id,"PONG :".$indata[1]);
			$ch3wyb0t->sdata['cons'][$id]['lastping'] = time();
		} elseif ($indata[0] == 'ERROR') {
			if (($ch3wyb0t->sdata['cons'][$id]['lastcmd'] == 'QUIT') or ($ch3wyb0t->sdata['cons'][$id]['lastcmd'] == 'EXIT') or ($ch3wyb0t->sdata['cons'][$id]['lastcmd'] == 'RELOAD')) {
				socket_close($ch3wyb0t->sdata['cons'][$id]['socket']);
				$ch3wyb0t->sdata['cons'][$id]['enabled'] = 'disabled';
			} else {
				socket_close($ch3wyb0t->sdata['cons'][$id]['socket']);
				$ch3wyb0t->_core->_connect($id);
				$ch3wyb0t->sdata['cons'][$id]['lastping'] = time();
			}
		} elseif (count($indata) >= 2) {
			switch ($indata[1]) {
				//Start the numerics
				case '001': {
					//$ch3wyb0t->_log->_sprint($id." Numeric 001 - Welcome to server",'debug',false);
					$ch3wyb0t->sdata['cons'][$id]['networkname'] = $indata[6];
					$ch3wyb0t->sdata['cons'][$id]['connectumask'] = $indata[9];
					$ch3wyb0t->_core->_sts($id,"MODE ".$ch3wyb0t->sdata['cons'][$id]['nick']." +B");
					$ch3wyb0t->_core->_operupcheck($id);
					if ($ch3wyb0t->sdata['cons'][$id]['identified'] == 2) {
						$ch3wyb0t->_core->_autojoinchans($id);
					}
					break;
				}
				case '002': {
					//$ch3wyb0t->_log->_sprint($id." Numeric 002 - host is server and version",'debug',false);
					break;
				}
				case '003': {
					//$ch3wyb0t->_log->_sprint($id." Numeric 003 - created",'debug',false);
					break;
				}
				case '004': {
					//$ch3wyb0t->_log->_sprint($id." Numeric 004 - server var usermode charmod");
					$ch3wyb0t->sdata['cons'][$id]['connection']['address'] = $indata[3];
					$ch3wyb0t->sdata['cons'][$id]['connectaddress'] = $indata[3];
					$ch3wyb0t->sdata['cons'][$id]['sversion'] = $indata[4];
					$ch3wyb0t->sdata['cons'][$id]['connectumodes'] = $indata[5];
					$ch3wyb0t->sdata['cons'][$id]['connectcmodes'] = $indata[6];
					//$ch3wyb0t->_core->_modeprocessor_user($id,'umode',$indata[5]);
					break;
				}
				case '005': {
					//$ch3wyb0t->_log->_sprint($id." Numeric 005 - map",'debug',false);
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
							case $ch3wyb0t->sdata['cons'][$id]['nick']: {
								break;
							}
							case $ch3wyb0t->sdata['cons'][$id]['connection']['address']: {
								break;
							}
							case 'UHNAMES': {
								$ch3wyb0t->sdata['cons'][$id]['uhnames'] = true;
								break;
							}
							case 'MAXCHANNELS': {
								$ch3wyb0t->sdata['cons'][$id]['maxchannels'] = $tmpdata[1];
								break;
							}
							case 'CHANLIMIT': {
								$ch3wyb0t->sdata['cons'][$id]['chanlimit'] = $tmpdata[1];
								break;
							}
							case 'MAXLIST': {
								$ch3wyb0t->sdata['cons'][$id]['maxlist'] = $tmpdata[1];
								break;
							}
							case 'NICKLEN': {
								$ch3wyb0t->sdata['cons'][$id]['nicklen'] = $tmpdata[1];
								break;
							}
							case 'CHANNELLEN': {
								$ch3wyb0t->sdata['cons'][$id]['channellen'] = $tmpdata[1];
								break;
							}
							case 'TOPICLEN': {
								$ch3wyb0t->sdata['cons'][$id]['topiclen'] = $tmpdata[1];
								break;
							}
							case 'KICKLEN': {
								$ch3wyb0t->sdata['cons'][$id]['kicklen'] = $tmpdata[1];
								break;
							}
							case 'AWAYLEN': {
								$ch3wyb0t->sdata['cons'][$id]['awaylen'] = $tmpdata[1];
								break;
							}
							case 'MAXTARGETS': {
								$ch3wyb0t->sdata['cons'][$id]['maxtargets'] = $tmpdata[1];
								break;
							}
							case 'MODES': {
								$ch3wyb0t->sdata['cons'][$id]['modespl'] = $tmpdata[1];
								break;
							}
							case 'CHANTYPES': {
								$ch3wyb0t->sdata['cons'][$id]['chantypes'] = $tmpdata[1];
								break;
							}
							case 'PREFIX': {
								$ch3wyb0t->sdata['cons'][$id]['prefix'] = $tmpdata[1];
								break;
							}
							case 'CHANMODES': {
								$ch3wyb0t->sdata['cons'][$id]['chanmodes'] = $tmpdata[1];
								break;
							}
							case 'EXTBAN': {
								$ch3wyb0t->sdata['cons'][$id]['extban'] = $tmpdata[1];
								break;
							}
							case 'WATCH': {
								$ch3wyb0t->sdata['cons'][$id]['watch'] = $tmpdata[1];
								break;
							}
							case 'WATCHOPTS': {
								$ch3wyb0t->sdata['cons'][$id]['watchopts'] = $tmpdata[1];
								break;
							}
							case 'NAMESX': {
								$ch3wyb0t->sdata['cons'][$id]['namesx'] = true;
								break;
							}
							case 'SAFELIST': {
								$ch3wyb0t->sdata['cons'][$id]['safelist'] = true;
								break;
							}
							case 'HCN': {
								$ch3wyb0t->sdata['cons'][$id]['hcn'] = true;
								break;
							}
							case 'WALLCHOPS': {
								$ch3wyb0t->sdata['cons'][$id]['wallchops'] = true;
								break;
							}
							case 'SILENCE': {
								$ch3wyb0t->sdata['cons'][$id]['silence'] = $tmpdata[1];
								break;
							}
							case 'NETWORK': {
								$ch3wyb0t->sdata['cons'][$id]['network'] = $tmpdata[1];
								break;
							}
							case 'CASEMAPPING': {
								$ch3wyb0t->sdata['cons'][$id]['casemapping'] = $tmpdata[1];
								break;
							}
							case 'ELIST': {
								$ch3wyb0t->sdata['cons'][$id]['elist'] = $tmpdata[1];
								break;
							}
							case 'STATUSMSG': {
								$ch3wyb0t->sdata['cons'][$id]['statusmsg'] = $tmpdata[1];
								break;
							}
							case 'EXCEPTS': {
								$ch3wyb0t->sdata['cons'][$id]['excepts'] = true;
								break;
							}
							case 'INVEX': {
								$ch3wyb0t->sdata['cons'][$id]['invex'] = true;
								break;
							}
							case 'CMDS': {
								$ch3wyb0t->sdata['cons'][$id]['cmds'] = $tmpdata[1];
								break;
							}
							default: {
								$ch3wyb0t->_log->_sprint($id." Numeric 005 unknown ".print_r($tmpdata,true),'debug',false);
								break;
							}
						}
						$i += 1;
					}
					break;
				}
				
				case '007': {
					//$ch3wyb0t->_log->_sprint($id." Numeric 007 - end of map",'debug',false);
					break;
				}
				case '008': {
					//$ch3wyb0t->_log->_sprint($id." Numeric 008 - num - server notice mask",'debug',false);
					if ($indata[2] == $ch3wyb0t->sdata['cons'][$id]['nick']) {
						//$ch3wyb0t->_core->_modeprocessor_user($id,'smask',$indata[6]);
						$blarg = 1;
					}
					break;
				}
				case '010': {
					//$ch3wyb0t->_log->_sprint($id." Numeric 010 - JumpServer",'debug',false);
					$ch3wyb0t->sdata['cons'][$id]['connection']['address'] = $indata[3];
					$ch3wyb0t->sdata['cons'][$id]['connection']['port'] = $indata[4];
					break;
				}
				
				case '211': {
					$ch3wyb0t->_log->_sprint($id." Numeric 211 - connection sendq sentmsg sentbyte recdmsg recdbyte :open",'debug',false);
					break;
				}
				case '212': {
					$ch3wyb0t->_log->_sprint($id." Numeric 212 - command uses bytes",'debug',false);
					break;
				}
				case '213': {
					$ch3wyb0t->_log->_sprint($id." Numeric 213 - C address * server port class",'debug',false);
					break;
				}
				case '214': {
					$ch3wyb0t->_log->_sprint($id." Numeric 214 - N address * server port class",'debug',false);
					break;
				}
				case '215': {
					$ch3wyb0t->_log->_sprint($id." Numeric 215 - I ipmask * hostmask port class",'debug',false);
					break;
				}
				case '216': {
					$ch3wyb0t->_log->_sprint($id." Numeric 216 - k address * username details",'debug',false);
					break;
				}
				case '217': {
					$ch3wyb0t->_log->_sprint($id." Numeric 217 - P port ?? ??",'debug',false);
					break;
				}
				case '218': { 
					$ch3wyb0t->_log->_sprint($id." Numeric 218 - Y class ping freq maxconnect sendq",'debug',false);
					break;
				}
				case '219': {
					//$ch3wyb0t->_log->_sprint($id." Numeric 219 - End of /stats report",'debug',false);
					break;
				}
				
				case '221': {
					if ($indata[2] == $ch3wyb0t->sdata['cons'][$id]['nick']) {
						$ch3wyb0t->_core->_modeprocessor_user($id,'umode',$indata[3]);
					}
					break;
				}
				case '222': {
					$ch3wyb0t->_log->_sprint($id." Numeric 222 - mask :comment",'debug',false);
					break;
				}
				case '223': {
					$ch3wyb0t->_log->_sprint($id." Numeric 223 - E hostmask * username ?? ??",'debug',false);
					break;
				}
				case '224': {
					$ch3wyb0t->_log->_sprint($id." Numeric 224 - D ipmask * username ?? ??",'debug',false);
					break;
				}
				
				case '241': {
					$ch3wyb0t->_log->_sprint($id." Numeric 241 - L address * server ?? ??",'debug',false);
					break;
				}
				case '242': {
					$ch3wyb0t->_log->_sprint($id." Numeric 242 - :Server Up num days, time",'debug',false);
					break;
				}
				case '243': {
					if ($indata[6] == $ch3wyb0t->data['data'][$id]['server']['botoper']) {
						$ch3wyb0t->_core->_modeprocessor_user($id,'oflags','+'.$indata[7]);
					}
					break;
				}
				case '244': {
					$ch3wyb0t->_log->_sprint($id." Numeric 244 - H address * server ?? ??",'debug',false);
					break;
				}
				
				case '247': {
					$ch3wyb0t->_log->_sprint($id." Numeric 247 - G address timestamp :reason",'debug',false);
					break;
				}
				case '248': {
					$ch3wyb0t->_log->_sprint($id." Numeric 248 - U host * ?? ?? ??",'debug',false);
					break;
				}
				case '249': {
					$ch3wyb0t->_log->_sprint($id." Numeric 249 - :info",'debug',false);
					break;
				}
				case '250': {
					//$ch3wyb0t->_log->_sprint($id." Numeric 250 - highest connection count",'false',false);
					break;
				}
				case '251': {
					//$ch3wyb0t->_log->_sprint($id." Numeric 251 - there are x users online",'debug',false);
					break;
				}
				case '252': {
					//$ch3wyb0t->_log->_sprint($id." Numeric 252 - number of operators",'debug',false);
					break;
				}
				case '253': {
					//$ch3wyb0t->_log->_sprint($id." Numeric 253 - number of unknown connections",'debug',false);
					break;
				}
				case '254': {
					//$ch3wyb0t->_log->_sprint($id." Numeric 254 - number of channels",'debug',false);
					break;
				}
				case '255': {
					//$ch3wyb0t->_log->_sprint($id." Numeric 255 - have x clients and x servers",'debug',false);
					break;
				}
				case '256': {
					$ch3wyb0t->_log->_sprint($id." Numeric 256 - :Administrative info about server",'debug',false);
					break;
				}
				case '257': {
					$ch3wyb0t->_log->_sprint($id." Numeric 257 - :info",'debug',false);
					break;
				}
				case '258': {
					$ch3wyb0t->_log->_sprint($id." Numeric 258 - :info",'debug',false);
					break;
				}
				case '259': {
					$ch3wyb0t->_log->_sprint($id." Numeric 259 - :info",'debug',false);
					break;
				}
				
				case '263': {
					$ch3wyb0t->_log->_sprint($id." Numeric 263 - :Server load is temporarily too heavy. Please wait a while and try again",'debug',false);
					break;
				}
				
				case '265': {
					//$ch3wyb0t->_log->_sprint($id." Numeric 265 - :Current local users: curr Max: max",'debug',false);
					break;
				}
				case '266': {
					//$ch3wyb0t->_log->_sprint($id." Numeric 266 - :Current global users: curr Max: max",'debug',false);
					break;
				}
				
				case '271': {
					$ch3wyb0t->_log->_sprint($id." Numeric 271 - nick mask",'debug',false);
					break;
				}
				case '272': {
					$ch3wyb0t->_log->_sprint($id." Numeric 272 - nick :End of Silence List",'debug',false);
					break;
				}
				
				case '280': {
					$ch3wyb0t->_log->_sprint($id." Numeric 280 - address timestamp reason",'debug',false);
					break;
				}
				case '281': {
					$ch3wyb0t->_log->_sprint($id." Numeric 281 - :End of G-Line List",'debug',false);
					break;
				}
				
				case '290': {
					$ch3wyb0t->_log->_sprint($id." Numeric 290 - :num ***** topic *****",'debug',false);
					break;
				}
				case '291': {
					$ch3wyb0t->_log->_sprint($id." Numeric 291 - :text",'debug',false);
					break;
				}
				case '292': {
					$ch3wyb0t->_log->_sprint($id." Numeric 292 - : ***** Go to #dalnethelp if you have any further questions *****",'debug',false);
					break;
				}
				case '293': {
					$ch3wyb0t->_log->_sprint($id." Numeric 293 - :text",'debug',false);
					break;
				}
				case '294': {
					$ch3wyb0t->_log->_sprint($id." Numeric 294 - :Your help-request has been forwared to Help Operators",'debug',false);
					break;
				}
				
				case '298': {
					$ch3wyb0t->_log->_sprint($id." Numeric 298 - nick :Nickname conflict has been resolved",'debug',false);
					break;
				}
				
				case '301': {
					$ch3wyb0t->_log->_sprint($id." Numeric 301 - nick :away",'debug',false);
					break;
				}
				case '302': {
					$ch3wyb0t->_log->_sprint($id." Numeric 302 - :userhosts",'debug',false);
					break;
				}
				case '303': {
					$ch3wyb0t->_log->_sprint($id." Numeric 303 - :nicknames",'debug',false);
					break;
				}
				case '304': {
					$ch3wyb0t->_log->_sprint($id." Numeric 304 - Unknown Raw Code",'debug',false);
					break;
				}
				case '305': {
					$ch3wyb0t->_log->_sprint($id." Numeric 305 - :You are no longer marked as being away",'debug',false);
					break;
				}
				case '306': {
					$ch3wyb0t->_log->_sprint($id." Numeric 306 - :You have been marked as being away",'debug',false);
					break;
				}
				case '307': {
					$ch3wyb0t->_log->_sprint($id." Numeric 307 - :userips",'debug',false);
					break;
				}
				
				case '310': {
					$ch3wyb0t->_log->_sprint($id." Numeric 310 - nick :looks very helpful",'debug',false);
					break;
				}
				case '311': {
					$ch3wyb0t->_log->_sprint($id." Numeric 311 - nick username address * :info",'debug',false);
					break;
				}
				case '312': {
					$ch3wyb0t->_log->_sprint($id." Numeric 312 - nick server :info",'debug',false);
					break;
				}
				case '313': {
					$ch3wyb0t->_log->_sprint($id." Numeric 313 - nick :is an IRC Operator",'debug',false);
					break;
				}
				case '314': {
					$ch3wyb0t->_log->_sprint($id." Numeric 314 - nick username address * :info",'debug',false);
					break;
				}
				case '315': {
					$ch3wyb0t->_log->_sprint($id." Numeric 315 - request :End of /WHO list",'debug',false);
					break;
				}
				
				case '317': {
					$ch3wyb0t->_log->_sprint($id." Numeric 317 - nick seconds signon :info",'debug',false);
					break;
				}
				case '318': {
					$ch3wyb0t->_log->_sprint($id." Numeric 318 - request :End of /WHOIS list.",'debug',false);
					break;
				}
				case '319': {
					$ch3wyb0t->_log->_sprint($id." Numeric 319 - nick :channels",'debug',false);
					break;
				}
				
				case '321': {
					$ch3wyb0t->_log->_sprint($id." Numeric 321 - Channel :Users Name",'debug',false);
					break;
				}
				case '322': {
					$ch3wyb0t->_log->_sprint($id." Numeric 322 - channel users :topic",'debug',false);
					break;
				}
				case '323': {
					$ch3wyb0t->_log->_sprint($id." Numeric 323 - :End of /LIST",'debug',false);
					break;
				}
				case '324': {
					$ch3wyb0t->_core->_modeprocessor_chan($id,$sender,$indata[3],$indata[4]);
					if ($ch3wyb0t->_core->_chanmodes($id,$indata[3]) != 'NULL') {
						$ch3wyb0t->_core->_sts($id,"MODE ".$indata[3]." ".$ch3wyb0t->_core->_chanmodes($id,$indata[3]));
					}
					break;
				}
				
				case '328': {
					$ch3wyb0t->_log->_sprint($id." Numeric 328 - channel :url",'debug',false);
					break;
				}
				case '329': {
					//$ch3wyb0t->_log->_sprint($id." Numeric 329 - Channel Creation time",'debug',false);
					//-1-> :chewy.chewynet.co.uk 329 ^chewy_god^ #home 1280495592
					//DEBUG: ChewyNet ['chewy.chewynet.co.uk', '329', '^chewy_god^', '#home', '1280495592']
					break;
				}
				
				case '331': {
					$ch3wyb0t->_log->_sprint($id." Numeric 331 - No topic is set",'debug',false);
					break;
				}
				case '332': {
					$ch3wyb0t->_log->_sprint($id." Numeric 332 - Topic",'debug',false);
					break;
				}
				case '333': {
					$ch3wyb0t->_log->_sprint($id." Numeric 333 - Nickname time",'debug',false);
					break;
				}
				
				case '340': {
					$ch3wyb0t->_log->_sprint($id." Numeric 340 - nick :nickname=+user@IP.address",'debug',false);
					break;
				}
				case '341': {
					$ch3wyb0t->_log->_sprint($id." Numeric 341 - nick channel",'debug',false);
					break;
				}
				
				case '346': {
					//DEBUG: ChewyNet ['chewy.chewynet.co.uk', '346', '^chewy_god^', '#home,' 'doe!*@*', 'chewyb_13', '1280533501']
					//$ch3wyb0t->_log->_sprint($id." Numeric 346 - Channel Invex List",'debug',false);
					$ch3wyb0t->sdata['cons'][$id]['chans'][$indata[3]]['INVEX'][$indata[4]] = true;
					break;
				}
				case '347': {
					//$ch3wyb0t->_log->_sprint($id." Numeric 347 - End of Channel Invex List",'debug',false);
					break;
				}
				case '348': {
					//DEBUG: ChewyNet ['chewy.chewynet.co.uk', '348', '^chewy_god^', '#home', 'blond!*@*', 'chewyb_13', '1280533501']
					//$ch3wyb0t->_log->_sprint($id." Numeric 348 - Channel Exception List",'debug',false);
					$ch3wyb0t->sdata['cons'][$id]['chans'][$indata[3]]['EXCEPT'][$indata[4]] = true;
					break;
				}
				case '349': {
					//$ch3wyb0t->_log->_sprint($id." Numeric 349 - End of Channel Exception List",'debug',false);
					break;
				}
				
				case '351': {
					$ch3wyb0t->_log->_sprint($id." Numeric 351 - version.debug server :info");
					break;
				}
				case '352': {
					$ch3wyb0t->_log->_sprint($id." Numeric 352 - channel username address server nick flags :hops info",'debug',false);
					break;
				}
				case '353': {
					//$ch3wyb0t->_log->_sprint($id." Numeric 353 - Names",'debug',false);
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
						$ch3wyb0t->sdata['cons'][$id]['chans'][$indata[4]]['users'][$tmpuser]['inchan'] = true;
						$ch3wyb0t->sdata['cons'][$id]['chans'][$indata[4]]['users'][$tmpuser][$tmpmode] = true;
						$i += 1;
					}
					break;
				}
				
				case '364': {
					$ch3wyb0t->_log->_sprint($id." Numeric 364 - server hub :hops info",'debug',false);
					break;
				}
				case '365': {
					$ch3wyb0t->_log->_sprint($id." Numeric 365 - mask :End of /LINKS list.",'debug',false);
					break;
				}
				case '366': {
					//$ch3wyb0t->_log->_sprint($id." Numeric 366 - End of Names",'debug',false);
					break;
				}
				case '367': {
					//DEBUG: ChewyNet ['chewy.chewynet.co.uk', '367', '^chewy_god^', '#home', 'blarg!*@*', 'chewyb_13', '1280533501']
					//$ch3wyb0t->_log->_sprint($id." Numeric 367 - Channel Ban List",'debug',false);
					$ch3wyb0t->sdata['cons'][$id]['chans'][$indata[3]]['BAN'][$indata[4]] = true;
					break;
				}
				case '368': {
					//$ch3wyb0t->_log->_sprint($id." Numeric 368 - End of Channel Ban List",'debug',false);
					break;
				}
				case '369': {
					$ch3wyb0t->_log->_sprint($id." Numeric 369 - request :End of WHOWAS",'debug',false);
					break;
				}
				
				case '371': {
					$ch3wyb0t->_log->_sprint($id." Numeric 371 - :info",'debug',false);
					break;
				}
				case '372': {
					//$ch3wyb0t->_log->_sprint($id." Numeric 372 - MOTD info",'debug',false);
					break;
				}
				
				case '374': {
					$ch3wyb0t->_log->_sprint($id." Numeric 374 - :End of /INFO list.",'debug',false);
					break;
				}
				case '375': {
					//$ch3wyb0t->_log->_sprint($id." Numeric 375 - server motd",'debug',false);
					break;
				}
				case '376': {
					//$ch3wyb0t->_log->_sprint($id." Numeric 376 - end of motd",'debug',false);
					break;
				}
				case '377': {
					$ch3wyb0t->_log->_sprint($id." Numeric 377 - info",'debug',false);
					break;
				}
				case '378': {
					$ch3wyb0t->_log->_sprint($id." Numeric 378 - info",'debug',false);
					break;
				}
				
				case '381': {
					if ($ch3wyb0t->sdata['cons'][$id]['isoper'] == 1) {
						$ch3wyb0t->sdata['cons'][$id]['isoper'] = 2;
						$ch3wyb0t->_core->_sts($id,"STATS O");
					}
					break;
				}
				case '382': {
					$ch3wyb0t->_log->_sprint($id." Numeric 382 - file :Rehashing",'debug',false);
					break;
				}
				
				case '391': {
					$ch3wyb0t->_log->_sprint($id." Numeric 391 - server :time",'debug',false);
					break;
				}
				
				case '401': {
					$ch3wyb0t->_log->_sprint($id." Numeric 401 - No such nick",'debug',false);
					break;
				}
				case '402': {
					$ch3wyb0t->_log->_sprint($id." Numeric 402 - server :No such server",'debug',false);
					break;
				}
				case '403': {
					$ch3wyb0t->_log->_sprint($id." Numeric 403 - No such channel",'debug',false);
					break;
				}
				case '404': {
					$ch3wyb0t->_log->_sprint($id." Numeric 404 - channel :Cannot send to channel",'debug',false);
					break;
				}
				case '405': {
					$ch3wyb0t->_log->_sprint($id." Numeric 405 - channel :You have joined too many channels",'debug',false);
					break;
				}
				case '406': {
					$ch3wyb0t->_log->_sprint($id." Numeric 406 - nickname :There was no such nickname",'debug',false);
					break;
				}
				case '407': {
					$ch3wyb0t->_log->_sprint($id." Numeric 407 - target :Duplicate recipients. No message delivered",'debug',false);
					break;
				}
				case '408': { 
					$ch3wyb0t->_log->_sprint($id." Numeric nickname #channel :You cannot use colors on this chanenl. Not sent: text",'debug',false);
					break;
				}
				case '409': {
					$ch3wyb0t->_log->_sprint($id." Numeric 409 - :No origin specified",'debug',false);
					break;
				}
				
				case '411': {
					$ch3wyb0t->_log->_sprint($id." Numeric 411 - :No recipient given (command)",'debug',false);
					break;
				}
				case '412': {
					$ch3wyb0t->_log->_sprint($id." Numeric 412 - :No text to send",'debug',false);
					break;
				}
				case '413': {
					$ch3wyb0t->_log->_sprint($id." Numeric 413 - mask :No toplevel domain specified",'debug',false);
					break;
				}
				case '414': {
					$ch3wyb0t->_log->_sprint($id." Numeric 414 - mask :Wildcard in toplevel Domain",'debug',false);
					break;
				}
				
				case '416': {
					$ch3wyb0t->_log->_sprint($id." Numeric 416 - command :Too many lines in the output, restrict your query",'debug',false);
					break;
				}
				
				case '421': {
					$ch3wyb0t->_log->_sprint($id." Numeric 421 - command :Unknown command",'debug',false);
					break;
				}
				case '422': {
					//$ch3wyb0t->_log->_sprint($id." Numeric 422 - MOTD missing",'debug',false');
					break;
				}
				case '423': {
					$ch3wyb0t->_log->_sprint($id." Numeric 423 - server :No administrative info available",'debug',false);
					break;
				}
				
				case '431': {
					$ch3wyb0t->_log->_sprint($id." Numeric 431 - :No nickname given",'debug',false);
					break;
				}
				case '432': {
					$ch3wyb0t->_log->_sprint($id." Numeric 432 - nickname :Erroneus Nickname",'debug',false);
					break;
				}
				case '433': {
					if ($indata[3] == $ch3wyb0t->sdata['cons'][$id]['nick']) {
						switch ($ch3wyb0t->sdata['cons'][$id]['nick']) {
							case $ch3wyb0t->data['data'][$id]['server']['nick']: {
								$ch3wyb0t->_core->_sts($id,"NICK ".$ch3wyb0t->data['data'][$id]['server']['bnick']);
								$ch3wyb0t->sdata['cons'][$id]['nick'] = $ch3wyb0t->data['data'][$id]['server']['bnick'];
								break;
							}
							case $ch3wyb0t->data['data'][$id]['server']['bnick']: {
								$ch3wyb0t->_core->_sts($id,"NICK ".$ch3wyb0t->data['data'][$id]['server']['nick']);
								$ch3wyb0t->sdata['cons'][$id]['nick'] = $ch3wyb0t->data['data'][$id]['server']['nick'];
								break;
							}
						}
					}
					break;
				}
				
				case '436': {
					$ch3wyb0t->_log->_sprint($id." Numeric 436 - nickname :Nickname collision KILL",'debug',false);
					break;
				}
				case '437': {
					$ch3wyb0t->_log->_sprint($id." Numeric 437 - channel :Cannot change nickname while banned on channel",'debug',false);
					break;
				}
				case '438': {
					$ch3wyb0t->_log->_sprint($id." Numeric 438 - nick :Nick change too fast. Please wait sec seconds.",'debug',false);
					break;
				}
				case '439': {
					$ch3wyb0t->_log->_sprint($id." Numeric 439 - target :Target change too fast. Please wait sec seconds.",'debug',false);
					break;
				}
				
				case '441': {
					$ch3wyb0t->_log->_sprint($id." Numeric 441 - nickname channel :They aren't on that channel",'debug',false);
					break;
				}
				case '442': {
					$ch3wyb0t->_log->_sprint($id." Numeric 442 - You are not on that channel",'debug',false);
					break;
				}
				case '443': {
					$ch3wyb0t->_log->_sprint($id." Numeric 443 - nickname channel :is already on channel",'debug',false);
					break;
				}
				
				case '445': {
					$ch3wyb0t->_log->_sprint($id." Numeric 445 - :SUMMON has been disabled",'debug',false);
					break;
				}
				case '446': {
					$ch3wyb0t->_log->_sprint($id." Numeric 446 - :USERS has been disabled",'debug',false);
					break;
				}
				
				case '451': {
					$ch3wyb0t->_log->_sprint($id." Numeric 451 - command :Register first.",'debug',false);
					break;
				}
				
				case '455': {
					$ch3wyb0t->_log->_sprint($id." Numeric 455 - :Your username ident contained the invalid character(s) chars and has been changed to new. Please use only the characters 0-9 a-z A-Z _ - or . in your username. Your username is the part before the @ in your email address.",'debug',false);
					break;
				}
				
				case '461': {
					$ch3wyb0t->_log->_sprint($id." Numeric 461 - command :Not enough parameters",'debug',false);
					break;
				}
				case '462': {
					$ch3wyb0t->_log->_sprint($id." Numeric 462 - :You may not reregister",'debug',false);
					break;
				}
				
				case '467': {
					$ch3wyb0t->_log->_sprint($id." Numeric 467 - channel :Channel key already set",'debug',false);
					break;
				}
				case '468': {
					$ch3wyb0t->_log->_sprint($id." Numeric 468 - channel :Only servers can change that mode",'debug',false);
					break;
				}
				
				case '471': {
					//$ch3wyb0t->_log->_sprint($id." Numeric 471 - channel :Cannot join channel (+l)",'debug',false);
					if ($ch3wyb0t->sdata['cons'][$id]['identified'] == 2) {
						$ch3wyb0t->_core->_sts($id,"PRIVMSG ChanServ :INVITE ".$indata[3]);
						$ch3wyb0t->_core->_joinchan($id,$indata[3]);
					} elseif ($ch3wyb0t->sdata['cons'][$id]['isoper'] == 2) {
						$ch3wyb0t->_core->_sts($id,"SAJOIN ".$ch3wyb0t->sdata['cons'][$id]['nick']." ".$indata[3]);
					}
					break;
				}
				case '472': {
					$ch3wyb0t->_log->_sprint($id." Numeric 472 - char :is unknown mode char to me",'debug',false);
					break;
				}
				case '473': {
					//$ch3wyb0t->_log->_sprint($id." Numeric 473 - channel :Cannot join channel (+i)",'debug',false);
					if ($ch3wyb0t->sdata['cons'][$id]['identified'] == 2) {
						$ch3wyb0t->_core->_sts($id,"PRIVMSG ChanServ :INVITE ".$indata[3]);
						$ch3wyb0t->_core->_joinchan($id,$indata[3]);
					} elseif ($ch3wyb0t->sdata['cons'][$id]['isoper'] == 2) {
						$ch3wyb0t->_core->_sts($id,"SAJOIN ".$ch3wyb0t->sdata['cons'][$id]['nick']." ".$indata[3]);
					}
					break;
				}
				case '474': {
					//$ch3wyb0t->_log->_sprint($id." Numeric 474 - channel :Cannot join channel (+b)",'debug',false);
					if ($ch3wyb0t->sdata['cons'][$id]['identified'] == 2) {
						$ch3wyb0t->_core->_sts($id,"PRIVMSG ChanServ :UNBAN ".$indata[3]);
						$ch3wyb0t->_core->_joinchan($id,$indata[3]);
					} elseif ($ch3wyb0t->sdata['cons'][$id]['isoper'] == 2) {
						$ch3wyb0t->_core->_sts($id,"SAJOIN ".$ch3wyb0t->sdata['cons'][$id]['nick']." ".$indata[3]);
					}
					break;
				}
				case '475': {
					//$ch3wyb0t->_log->_sprint($id." Numeric 475 - channel :Cannot join channel (+k)",'debug',false);
					if ($ch3wyb0t->sdata['cons'][$id]['identified'] == 2) {
						$ch3wyb0t->_core->_sts($id,"PRIVMSG ChanServ :INVITE ".$indata[3]);
						$ch3wyb0t->_core->_joinchan($id,$indata[3]);
					} elseif ($ch3wyb0t->sdata['cons'][$id]['isoper'] == 2) {
						$ch3wyb0t->_core->_sts($id,"SAJOIN ".$ch3wyb0t->sdata['cons'][$id]['nick']." ".$indata[3]);
					}
					break;
				}
				
				case '477': {
					$ch3wyb0t->_log->_sprint($id." Numeric 477 - channel :You need a registered nick to join that channel.",'debug',false);
					break;
				}
				case '478': {
					$ch3wyb0t->_log->_sprint($id." Numeric 478 - channel ban :Channel ban/ignore list is full",'debug',false);
					break;
				}
				
				case '481': {
					$ch3wyb0t->_log->_sprint($id." Numeric 481 - :Permission Denied- You're not an IRC operator",'debug',false);
					break;
				}
				case '482': {
					$ch3wyb0t->_log->_sprint($id." Numeric 482 - channel :You're not a channel operator",'debug',false);
					break;
				}
				case '483': {
					$ch3wyb0t->_log->_sprint($id." Numeric 483 - :You can't kill a server!",'debug',false);
					break;
				}
				case '484': {
					$ch3wyb0t->_log->_sprint($id." Numeric 484 - nick channel :Cannot kill, kick or deop chanenl service",'debug',false);
					break;
				}
				case '485': {
					$ch3wyb0t->_log->_sprint($id." Numeric 485 - channel :Cannot join channel (reason)",'debug',false);
					break;
				}
				
				case '491': {
					$ch3wyb0t->_log->_sprint($id." Numeric 491 - :No O-lines for your host",'debug',false);
					break;
				}
				
				case '499': {
					//$ch3wyb0t->_log->_sprint($id." Numeric 499 - Not Owner of the channel",'debug',false);
					break;
				}
				
				case '501': {
					$ch3wyb0t->_log->_sprint($id." Numeric 501 - :Unknown MODE flag",'debug',false);
					break;
				}
				case '502': {
					$ch3wyb0t->_log->_sprint($id." Numeric 502 - :Cant change mode for other users",'debug',false);
					break;
				}
				
				case '510': {
					$ch3wyb0t->_log->_sprint($id." Numeric 510 - :You must resolve the nickname conflict before you can proceed",'debug',false);
					break;
				}
				case '511': {
					$ch3wyb0t->_log->_sprint($id." Numeric 511 - mask :Your silence list is full",'debug',false);
					break;
				}
				case '512': {
					$ch3wyb0t->_log->_sprint($id." Numeric 512 - address :No such gline",'debug',false);
					break;
				}
				case '513': {
					$ch3wyb0t->_log->_sprint($id." Numeric 513 - If you can't connect, type /QUOTE PONG code or /PONG code",'debug',false);
					break;
				}
				
				case '600': {
					$ch3wyb0t->_log->_sprint($id." Numeric 600 - nick userid host time :logged offline",'debug',false);
					break;
				}
				case '601': {
					$ch3wyb0t->_log->_sprint($id." Numeric 601 - nick userid host time :logged online",'debug',false);
					break;
				}
				case '602': {
					$ch3wyb0t->_log->_sprint($id." Numeric 602 - nick userid host time :stopped watching",'debug',false);
					break;
				}
				case '603': {
					$ch3wyb0t->_log->_sprint($id." Numeric 603 - :You have mine and are on other WATCH entries",'debug',false);
					break;
				}
				case '604': {
					$ch3wyb0t->_log->_sprint($id." Numeric 604 - nick userid host time :is online",'debug',false);
					break;
				}
				case '605': {
					$ch3wyb0t->_log->_sprint($id." Numeric 605 - nick userid host time :is offline",'debug',false);
					break;
				}
				case '606': {
					$ch3wyb0t->_log->_sprint($id." Numeric 606 - :nicklist",'debug',false);
					break;
				}
				
				case '972': {
					//$ch3wyb0t->_log->_sprint($id." Numeric 972 - Can't kick user due to +q",'debug',false);
					break;
				}
				
				//Start Normal
				
				case 'JOIN': {
					$ch3wyb0t->sdata['cons'][$id]['chans'][$indata[2]]['users'][$sender]['inchan'] = true;
					if ($sender == $ch3wyb0t->sdata['cons'][$id]['nick']) {
						if ($ch3wyb0t->_core->_checkchan($id,$indata[2]) == false) {
							$ch3wyb0t->_core->_sts($id,"PART ".$indata[2]." :Not supposed to be in here");
						} else {
							$ch3wyb0t->_core->_sts($id,"MODE ".$indata[2]);
							$ch3wyb0t->_core->_sts($id,"MODE ".$indata[2]." +b");
							$ch3wyb0t->_core->_sts($id,"MODE ".$indata[2]." +e");
							$ch3wyb0t->_core->_sts($id,"MODE ".$indata[2]." +I");
							$ch3wyb0t->_core->_sts($id,"NAMES ".$indata[2]);
						}
						
					} else {
						//$ch3wyb0t->_log->_sprint($id." ".$sender." joined channel ".$indata[2]);
						if ($ch3wyb0t->_core->_islogged($id,$sender) == true) {
							if (strlen($ch3wyb0t->sdata['cons'][$id]['tempdata']) > 0) {
								if ($ch3wyb0t->sdata['cons'][$id]['tempdata'][$sender] == 'UHCHANGE') {
									$ch3wyb0t->sdata['cons'][$id]['loggedin'][$sender]['umask'] = $indata[0];
								}
								unset($ch3wyb0t->sdata['cons'][$id]['tempdata'][$sender]);
							}
						}
					}
					break;				
				}
				case 'PART': {
					unset($ch3wyb0t->sdata['cons'][$id]['chans'][$indata[2]]['users'][$sender]['inchan']);
					if ($sender == $ch3wyb0t->sdata['cons'][$id]['nick']) {
						if ($ch3wyb0t->_core->_checkchan($id,$indata[2]) == true) {
							$ch3wyb0t->_core->_joinchan($id,$indata[2]);
						} else {
							unset($ch3wyb0t->sdata['cons'][$id]['chans'][$indata[2]]);
							//$ch3wyb0t->_log->_sprint($id." I Parted channel ".$indata[2]);
						}
					} else {
						//$ch3wyb0t->_log->_sprint($id." ".$sender." parted channel ".$indata[2]);
						if ($ch3wyb0t->_core->_islogged($id,$sender) == true) {
							if (count($indata) >= 8) {
								if (($indata[3] == 'Rejoining') and ($indata[4] == 'because') and ($indata[5] == 'of') and ($indata[6] == 'user@host') and ($indata[7] == 'change')) {
									$ch3wyb0t->sdata['cons'][$id]['tempdata'][$sender] = 'UHCHANGE';
								}
							}
						}
					}
					break;
				}
				case 'QUIT': {
					if ($sender == $ch3wyb0t->sdata['cons'][$id]['nick']) {
						$ch3wyb0t->_log->_sprint("I Quit ".$ch3wyb0t->data['data'][$id]['server']['servername'],'debug',false);
					} else {
						if ($ch3wyb0t->_core->_islogged($id,$sender) == true) {
							$ch3wyb0t->_log->_sprint("Auto-Logout for ".$sender."(".$ch3wyb0t->sdata['cons'][$id]['loggedin'][$sender]['username'].")",'notice',true);
							unset($ch3wyb0t->sdata['cons'][$id]['loggedin'][$sender]);
						}
					}
					if (count($ch3wyb0t->sdata['cons'][$id]['chans']) > 0) {
						foreach ($ch3wyb0t->sdata['cons'][$id]['chans'] as $t1 => $t2) {
							if (count($t2['users']) > 0) {
								foreach ($t2['users'] as $t3 => $t4) {
									if ($t4 == $sender) {
										unset($ch3wyb0t->sdata['cons'][$id]['chans'][$t1]['users'][$sender]);
									}
								}
							}
						}
					}
					break;
				}
				case 'KICK': {
					if ($indata[3] == $ch3wyb0t->sdata['cons'][$id]['nick']) {
						if ($ch3wyb0t->_core->_checkchan($id,$indata[2]) == true) {
							$ch3wyb0t->_core->_joinchan($id,$indata[2]);
						}
					} else {
						$ch3wyb0t->_log->_sprint("Another user was kicked from ".$indata[2]." on ".$ch3wyb0t->data['data'][$id]['server']['servername'],'debug',false);
					}
					unset($ch3wyb0t->sdata['cons'][$id]['chans'][$indata[2]]['users'][$indata[3]]);
					break;
				}
				case 'TOPIC': {
					$ch3wyb0t->_log->_sprint($id." TOPIC",'debug',false);
					break;
				}
				case 'WALLOPS': {
					$ch3wyb0t->_log->_sprint($id." WALLOPS",'debug',false);
					break;
				}
				case 'INVITE': {
					$ch3wyb0t->_log->_sprint($id." INVITE",'debug',false);
					break;
				}
				case 'MODE': {
					if ($indata[2] == $ch3wyb0t->sdata['cons'][$id]['nick']) {
						$ch3wyb0t->_core->_modeprocessor_user($id,'umode',$indata[3]);
					} else {
						$ch3wyb0t->_core->_modeprocessor_chan($id,$sender,$indata[2],$indata[3]);
						if ($ch3wyb0t->_core->_chanmodes($id,$indata[2],$indata[3]) != 'NULL') {
							$ch3wyb0t->_core->_sts($id,"MODE ".$indata[2]." ".$ch3wyb0t->_core->_chanmodes($id,$indata[2]));
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
					if ($indata[2] == $ch3wyb0t->sdata['cons'][$id]['nick']) {
						$this->_core_command_cmds($id,'PNOTE',$sender,$indata,$rawdata,$address);
					} else {
						$this->_core_command_cmds($id,'CNOTE',$sender,$indata,$rawdata,$address);
					}
					break;
				}
				case 'PRIVMSG': {
					if ($indata[2] == $ch3wyb0t->sdata['cons'][$id]['nick']) {
						$this->_core_command_cmds($id,'PMSG',$sender,$indata,$rawdata,$address);
					} else {
						$this->_core_command_cmds($id,'CMSG',$sender,$indata,$rawdata,$address);
					}
					break;
				}
				default: {
					$ch3wyb0t->_log->_sprint($id." ".print_r($indata,true),'debug',false);
					$ch3wyb0t->_log->_sprint($id." Unknown feature at this momment",'debug',false);
					break;
				}
			}
		} else {
			$ch3wyb0t->_log->_sprint("Unknown Length of data",'debug',false);
		}
		return;
	}
	
	public function startup() {
		global $CORE;
		global $ch3wyb0t;
		//connect to sql db
		if (!$ch3wyb0t->_sql->db) {
			$ch3wyb0t->_sql->sql('database_connect',null);
		}
		$ch3wyb0t->_log->_sprint("Pulling Key Data from the database",'regular');
		$ch3wyb0t->_core->_cmd_rehash();
		$ch3wyb0t->_log->_sprint("Finished Loading All Key Data from the database",'regular');
		$ch3wyb0t->_log->_sprint("Bot is now starting up, gonna start connections and head right on in.",'regular');
		$ch3wyb0t->_core->_startup();
		$ch3wyb0t->_core->_main_process();
		
/*		$ch3wyb0t->_log->_sprint("Test of Error Output",'error');
		$ch3wyb0t->_log->_sprint("Test of Alert Output",'alert');
		$ch3wyb0t->_log->_sprint("Test of Warning Output",'warning');
		$ch3wyb0t->_log->_sprint("Test of Notice Output",'notice');
		$ch3wyb0t->_log->_sprint("Test of Debug Output",'debug');
		$ch3wyb0t->_log->_sprint("Test of Regular Output",'regular');*/
		//print_r($ch3wyb0t->sdata);
	}
	
	protected function _sprint($message,$dtype=null,$log=false) {
		global $ch3wyb0t;
		$ch3wyb0t->_log->_sprint("Something is still using old call",$dtype,$log);
		return $ch3wyb0t->_log->_sprint($message,$dtype,$log);
	}
	protected function _screen($id,$type,$text) {
		global $ch3wyb0t;
		$ch3wyb0t->_log->_screen($id,$type,"Old Call");
		return $ch3wyb0t->_log->_screen($id,$type,$text);
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