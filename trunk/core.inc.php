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
		$this->log = new log("Init of Logging system for main",null,false);
		$this->sql = new sql('init',null);
		$log = $this->log;
		$sql = $this->sql;
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
	
	private function _core_command_cmds($id,$type,$sender,$indata,$rawdata) {
/*def commands(sock,type,user,incom,raw):
	if ((type == 'CNOTE') or (type == 'CMSG')):
		chan = rl(incom[2])
	else:
		chan = 'NULL'
	if (len(incom) >= 4):
		#debug("Enetered Private messages")
		if ((type == 'PNOTE') and (incom[0] == mysockets[sock]['connectaddress'])):
			#debug(sock,"Snotice: {0}".format(incom))
			blarg = 1
		elif ('\x01' in incom[3]):
			ctcp = incom[3:]
			stripcount = len(ctcp)
			while (stripcount):
				stripcount = stripcount - 1	
				ctcp[stripcount] = ctcp[stripcount].strip('\x01')
			if (ctcp[0].upper() == 'ACTION'):
				debug(sock,"Got a Action {0}".format(ctcp[1:]))
			elif (ctcp[0].upper() == 'VERSION'):
				if (len(ctcp) >= 2):
					debug(sock,"Got a CTCP VERSION Response {0}".format(ctcp[1:]))
				else:
					sts(sock,"NOTICE {0} :\x01VERSION Ch3wyB0t Version {1}\x01".format(user,version))
			elif (ctcp[0].upper() == 'PING'):
				if (len(ctcp) >= 2):
					sts(sock,"NOTICE {0} :\x01PING {1}\x01".format(user,ctcp[1]))
				else:
					sts(sock,"NOTICE {0} :\x01PING\x01".format(user))
			elif (ctcp[0].upper() == 'TIME'):
				if (len(ctcp) >= 2):
					debug(sock,"Got a CTCP TIME response {0}".format(ctcp[1:]))
				else:
					currenttime = datetime.datetime.now()
					sts(sock,"NOTICE {0} :\x01TIME {1}\x01".format(user,currenttime.strftime("%a %b %d %I:%M:%S%p %Y")))
			else:
				debug(sock,"Got a unknown CTCP request {0}".format(ctcp))
		elif (incom[3] == '?trigger'):
			buildmsg(sock,'NORMAL',user,chan,'PRIV',"Channel: {0}{2} Private Message: {1}{2}".format(settings['chancom'],settings['pvtcom'],settings['signal']))
		elif (((incom[3] == settings['chancom']+settings['signal']) and ((type == 'CMSG') or (type == 'CNOTE'))) or ((incom[3] == settings['pvtcom']+settings['signal']) and ((type == 'PMSG') or (type == 'PNOTE')))):
			if (len(incom) >= 5):
				if (incom[4].upper() == 'EXIT'):
					if (loggedgetaccess(sock,user,chan,'GLOBAL') >= 6):
						if (len(incom) >= 6):
							output = splitjoiner(incom[5:])
							tempsocks = mysockets.keys()
							for tempsock in tempsocks:
								mysockets[tempsock]['lastcmd'] = 'EXIT'
								sts(tempsock,"QUIT {0}".format(output))
						else:
							tempsocks = mysockets.keys()
							for tempsock in tempsocks:
								mysockets[tempsock]['lastcmd'] = 'EXIT'
								sts(tempsock,"QUIT Ch3wyB0t Version {0} Quitting".format(version))
					else:
						buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
				elif (incom[4].upper() == 'RELOAD'):
					if (loggedgetaccess(sock,user,chan,'GLOBAL') >= 6):
						tempsocks = mysockets.keys()
						debug('NULL',"Value {0} value".format(fulltemp))
						for tempsock in tempsocks:
							mysockets[tempsock]['lastcmd'] = 'RELOAD'
							sts(tempsock,"QUIT Ch3wyB0t Version {0} Reloading".format(version))
					else:
						buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
				elif (incom[4].upper() == 'RAW'):
					if (loggedgetaccess(sock,user,chan,'GLOBAL') >= 7):
						if (len(incom) >= 6):
							output = splitjoiner(raw[5:])
							sts(sock,"{0}".format(output))
							buildmsg(sock,'RAW',user,chan,'PRIV',"Sent {0} to Server".format(output))
						else:
							buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter what you want to send from the bot")
					else:
						buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
				elif (incom[4].upper() == 'RAWDB'):
					if (loggedgetaccess(sock,user,chan,'GLOBAL') >= 7):
						if (len(incom) >= 6):
							output = splitjoiner(raw[5:])
							vals = db.execute(output)
							buildmsg(sock,'RAW',user,chan,'PRIV',"Sent {0} to the database".format(output))
						else:
							buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter what you want to send to the database")
					else:
						buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
				elif (incom[4].upper() == 'QUIT'):
					if (loggedgetaccess(sock,user,chan,'SERVER') >= 6):
						if (len(incom) >= 6):
							mysockets[sock]['lastcmd'] = 'QUIT'
							sts(sock,"QUIT {0}".format(splitjoiner(incom[5:])))
						else:
							mysockets[sock]['lastcmd'] = 'QUIT'
							sts(sock,"QUIT Ch3wyB0t Version {0} Quitting".format(version))
					else:
						buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
				elif (incom[4].upper() == 'REHASH'):
					if (loggedgetaccess(sock,user,chan,'GLOBAL') >= 6):
						buildmsg(sock,'NORMAL',user,chan,'PRIV',"Rehashing...")
						rehash()
						buildmsg(sock,'NORMAL',user,chan,'PRIV',"Rehashing Complete...")
					else:
						buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
				elif (incom[4].upper() == 'SETTINGS'):
					if ((type == 'PMSG') or (type == 'PNOTE')):
						if (loggedgetaccess(sock,user,chan,'GLOBAL') >= 6):
							if (len(incom) >= 6):
								if (incom[5].upper() == 'SET'):
									if (len(incom) >= 7):
										if (len(incom) >= 8):
											sql = "UPDATE settings SET setting = '{0}', value = '{1}' WHERE setting = '{0}'".format(rl(incom[6]),incom[7])
											vals = db.execute(sql)
											settings[rl(incom[6])] = incom[7]
											buildmsg(sock,'NORMAL',user,chan,'PRIV',"You have successfully changed {0} to {1}".format(rl(incom[6]),incom[7]))
										else:
											buildmsg(sock,'ERROR',user,chan,'PRIV',"Missing Setting Value")
									else:
										buildmsg(sock,'ERROR',user,chan,'PRIV',"Missing Setting Name")
								elif (incom[5].upper() == 'LIST'):
									sql = "SELECT * FROM settings"
									records = db.select(sql)
									for record in records:
										buildmsg(sock,'NORMAL',user,chan,'PRIV',"Setting: {0} Value: {1}".format(record[1], record[2]))
								else:
									buildmsg(sock,'ERROR',user,chan,'PRIV',"Error, Use Either Set or List")
							else:
								buildmsg(sock,'ERROR',user,chan,'PRIV',"Error, Use Either Set or List")
						else:
							buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
					else:
						buildmsg(sock,'ERROR',user,chan,'PRIV',"You can not access settings via channel commands")
				elif (incom[4].upper() == 'SERVER'):
					if ((type == 'PMSG') or (type == 'PNOTE')):
						if (loggedgetaccess(sock,user,chan,'GLOBAL') >= 6):
							if (len(incom) >= 6):
								if (incom[5].upper() == 'ADD'):
									blarg = 1
								elif (incom[5].upper() == 'CHG'):
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
								elif (incom[5].upper() == 'LIST'):
									sql = "SELECT * FROM servers"
									records = db.select(sql)
									for record in records:
										if (record[10] == 'enabled'):
											buildmsg(sock,'NORMAL',user,chan,'PRIV',"\x033SID: {0} Server: {1} Address: {2} Port: {3} SPass: {4} Nick: {5} BNick: {6} NSPass: {7} BotOper: {8} BotOperPass: {9}\x03".format(int(record[0]),record[1],record[2],int(record[3]),record[4],record[5],record[6],record[7],record[8],record[9]))
										else:
											buildmsg(sock,'NORMAL',user,chan,'PRIV',"\x034SID: {0} Server: {1} Address: {2} Port: {3} SPass: {4} Nick: {5} BNick: {6} NSPass: {7} BotOper: {8} BotOperPass: {9}\x03".format(int(record[0]),record[1],record[2],int(record[3]),record[4],record[5],record[6],record[7],record[8],record[9]))
									buildmsg(sock,'NORMAL',user,chan,'PRIV',"Color \x033Green\x03 is enabled, Color \x034Red\x03 is disabled")
								else:
									buildmsg(sock,'ERROR',user,chan,'PRIV',"Error, Use Either List, Add, or Chg")
							else:
								buildmsg(sock,'ERROR',user,chan,'PRIV',"Error, Use Either List, Add, or Chg")
						else:
							buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
					else:
						buildmsg(sock,'ERROR',user,chan,'PRIV',"You can not access server via channel commands")
				elif (incom[4].upper() == 'CHANNEL'):
					if ((type == 'PMSG') or (type == 'PNOTE')):
						if (loggedgetaccess(sock,user,chan,'GLOBAL') >= 6):
							if (len(incom) >= 6):
								if (incom[5].upper() == 'CHG'):
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
								elif (incom[5].upper() == 'LIST'):
									sql = "SELECT * FROM channels"
									records = db.select(sql)
									for record in records:
										if (record[7] == 'enabled'):
											buildmsg(sock,'NORMAL',user,chan,'PRIV',"\x033CID: {0} Server: {1} Channel: {2} Pass: {3} Channel Modes: {4} Chan Options: {5}\x03".format(int(record[0]),record[1],record[2],record[3],record[4],record[6]))
											buildmsg(sock,'NORMAL',user,chan,'PRIV',"\x033CID: {0} Topic: {1}\x03".format(int(record[0]),record[5]))
										else:
											buildmsg(sock,'NORMAL',user,chan,'PRIV',"\x034CID: {0} Server: {1} Channel: {2} Pass: {3} Channel Modes: {4} Chan Options: {5}\x03".format(int(record[0]),record[1],record[2],record[3],record[4],record[6]))
											buildmsg(sock,'NORMAL',user,chan,'PRIV',"\x034CID: {0} Topic: {1}\x03".format(int(record[0]),record[5]))
									buildmsg(sock,'NORMAL',user,chan,'PRIV',"Color \x033Green\x03 is enabled, Color \x034Red\x03 is disabled")
								else:
									buildmsg(sock,'ERROR',user,chan,'PRIV',"Error, Use Either List, or Chg")
							else:
								buildmsg(sock,'ERROR',user,chan,'PRIV',"Error, Use Either List, or Chg")
						else:
							buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
					else:
						buildmsg(sock,'ERROR',user,chan,'PRIV',"You can not access channels via channel commands")
				elif (incom[4].upper() == 'USER'):
					if ((type == 'PMSG') or (type == 'PNOTE')):
						if (loggedgetaccess(sock,user,chan,'GLOBAL') >= 6):
							if (len(incom) >= 6):
								if (incom[5].upper() == 'ADD'):
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
								elif (incom[5].upper() == 'CHG'):
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
								elif (incom[5].upper() == 'DEL'):
									#this bit of coding is only gonna be temperary for the time being due to abuse possiblities
									sql = "DELETE FROM users WHERE username = '{0}'".format(rl(incom[6]))
									db.execute(sql)
									buildmsg(sock,'NORMAL',user,chan,'PRIV',"Deleted {0} or attempted to delete from the database".format(rl(incom[6])))
								elif (incom[5].upper() == 'LIST'):
									sql = "SELECT * FROM users"
									records = db.select(sql)
									for record in records:
										buildmsg(sock,'NORMAL',user,chan,'PRIV',"UID: {0} Username: {1} Global: {2} Server: {3} Channel: {4} MsgType: {5}".format(int(record[0]),record[1],record[3],record[4],record[5],record[6]))
								else:
									buildmsg(sock,'ERROR',user,chan,'PRIV',"Error, Use Either List, Add, Del, or Chg")
							else:
								buildmsg(sock,'ERROR',user,chan,'PRIV',"Error, Use Either List, Add, Del, or Chg")
						else:
							buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
					else:
						buildmsg(sock,'ERROR',user,chan,'PRIV',"You can not access users via channel commands")
				elif (incom[4].upper() == 'ACCESS'):
					blarg = 1
				elif (incom[4].upper() == 'USERLIST'):	
					if (loggedgetaccess(sock,user,chan,'SERVER') >= 4):
						sql = "SELECT * FROM users"
						records = db.select(sql)
						buildmsg(sock,'NORMAL',user,chan,'PRIV',"Displaying user list, only showing Usernames atm, do note may be a big ammount of infomation")
						for record in records:
							buildmsg(sock,'NORMAL',user,chan,'PRIV',"Username: {0}".format(record[1]))
					else:
						buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
						
						
				elif (incom[4].upper() == 'MOWNER'):
					if (islogged(sock,user) == 'FALSE'):
						buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
					else:
						if ((type == 'PMSG') or (type == 'PNOTE')):
							if (len(incom) >= 6):
								chan = rl(incom[5])
								passthrough = 1
							else:
								passthrough = 0
								buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter a channel")
						if ((type == 'CMSG') or (type == 'CNOTE')):
							passthrough = 1
						if (passthrough == 1):
							if (getaccess(sock,loggedin[sock][user]['username'],chan,'CHANNEL') >= 5):
								massmodes(sock,user,chan,['ADD','q','ALL'])
							else:
								buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
				elif (incom[4].upper() == 'OWNER'):
					if (islogged(sock,user) == 'FALSE'):
						buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
					else:
						if ((type == 'PMSG') or (type == 'PNOTE')):
							if (len(incom) >= 6):
								chan = rl(incom[5])
								if (len(incom) >= 7):
									data = incom[6:]
									passthrough = 1
								else:
									passthrough = 0
									buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter any nicks")
							else:
								passthrough = 0
								buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter a channel")
						if ((type == 'CMSG') or (type == 'CNOTE')):
							if (len(incom) >= 6):
								data = incom[5:]
								passthrough = 1
							else:
								passthrough = 0
								buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter any nicks")
						if (passthrough == 1):
							if (getaccess(sock,loggedin[sock][user]['username'],chan,'CHANNEL') >= 5):
								massmodes(sock,user,chan,['ADD','q',data])
							else:
								buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
				elif (incom[4].upper() == 'MDEOWNER'):
					if (islogged(sock,user) == 'FALSE'):
						buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
					else:
						if ((type == 'PMSG') or (type == 'PNOTE')):
							if (len(incom) >= 6):
								chan = rl(incom[5])
								passthrough = 1
							else:
								passthrough = 0
								buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter a channel")
						if ((type == 'CMSG') or (type == 'CNOTE')):
							passthrough = 1
						if (passthrough == 1):
							if (getaccess(sock,loggedin[sock][user]['username'],chan,'CHANNEL') >= 5):
								massmodes(sock,user,chan,['REM','q','BC'])
							else:
								buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
				elif (incom[4].upper() == 'DEOWNER'):
					if (islogged(sock,user) == 'FALSE'):
						buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
					else:
						if ((type == 'PMSG') or (type == 'PNOTE')):
							if (len(incom) >= 6):
								chan = rl(incom[5])
								if (len(incom) >= 7):
									data = incom[6:]
									passthrough = 1
								else:
									passthrough = 0
									buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter any nicks")
							else:
								passthrough = 0
								buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter a channel")
						if ((type == 'CMSG') or (type == 'CNOTE')):
							if (len(incom) >= 6):
								data = incom[5:]
								passthrough = 1
							else:
								passthrough = 0
								buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter any nicks")
						if (passthrough == 1):
							if (getaccess(sock,loggedin[sock][user]['username'],chan,'CHANNEL') >= 5):
								massmodes(sock,user,chan,['REM','q',data])
							else:
								buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
				elif (incom[4].upper() == 'OWNERME'):
					if (islogged(sock,user) == 'FALSE'):
						buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
					else:
						if ((type == 'PMSG') or (type == 'PNOTE')):
							if (len(incom) >= 6):
								chan = rl(incom[5])
								passthrough = 1
							else:
								passthrough = 0
								buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter a channel")
						if ((type == 'CMSG') or (type == 'CNOTE')):
							passthrough = 1
						if (passthrough == 1):
							if (getaccess(sock,loggedin[sock][user]['username'],chan,'CHANNEL') >= 5):
								massmodes(sock,user,chan,['ADD','q',user]) #can be ADD or REM
							else:
								buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
				elif (incom[4].upper() == 'DEOWNERME'):
					if (islogged(sock,user) == 'FALSE'):
						buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
					else:
						if ((type == 'PMSG') or (type == 'PNOTE')):
							if (len(incom) >= 6):
								chan = rl(incom[5])
								passthrough = 1
							else:
								passthrough = 0
								buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter a channel")
						if ((type == 'CMSG') or (type == 'CNOTE')):
							passthrough = 1
						if (passthrough == 1):
							if (getaccess(sock,loggedin[sock][user]['username'],chan,'CHANNEL') >= 5):
								massmodes(sock,user,chan,['REM','q',user])
							else:
								buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
				elif (incom[4].upper() == 'MPROTECT'):
					if (islogged(sock,user) == 'FALSE'):
						buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
					else:
						if ((type == 'PMSG') or (type == 'PNOTE')):
							if (len(incom) >= 6):
								chan = rl(incom[5])
								passthrough = 1
							else:
								passthrough = 0
								buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter a channel")
						if ((type == 'CMSG') or (type == 'CNOTE')):
							passthrough = 1
						if (passthrough == 1):
							if (getaccess(sock,loggedin[sock][user]['username'],chan,'CHANNEL') >= 5):
								massmodes(sock,user,chan,['ADD','a','ALL'])
							else:
								buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
				elif (incom[4].upper() == 'PROTECT'):
					if (islogged(sock,user) == 'FALSE'):
						buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
					else:
						if ((type == 'PMSG') or (type == 'PNOTE')):
							if (len(incom) >= 6):
								chan = rl(incom[5])
								if (len(incom) >= 7):
									data = incom[6:]
									passthrough = 1
								else:
									passthrough = 0
									buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter any nicks")
							else:
								passthrough = 0
								buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter a channel")
						if ((type == 'CMSG') or (type == 'CNOTE')):
							if (len(incom) >= 6):
								data = incom[5:]
								passthrough = 1
							else:
								passthrough = 0
								buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter any nicks")
						if (passthrough == 1):
							if (getaccess(sock,loggedin[sock][user]['username'],chan,'CHANNEL') >= 5):
								massmodes(sock,user,chan,['ADD','a',data])
							else:
								buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
				elif (incom[4].upper() == 'MDEPROTECT'):
					if (islogged(sock,user) == 'FALSE'):
						buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
					else:
						if ((type == 'PMSG') or (type == 'PNOTE')):
							if (len(incom) >= 6):
								chan = rl(incom[5])
								passthrough = 1
							else:
								passthrough = 0
								buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter a channel")
						if ((type == 'CMSG') or (type == 'CNOTE')):
							passthrough = 1
						if (passthrough == 1):
							if (getaccess(sock,loggedin[sock][user]['username'],chan,'CHANNEL') >= 5):
								massmodes(sock,user,chan,['REM','a','BC'])
							else:
								buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
				elif (incom[4].upper() == 'DEPROTECT'):
					if (islogged(sock,user) == 'FALSE'):
						buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
					else:
						if ((type == 'PMSG') or (type == 'PNOTE')):
							if (len(incom) >= 6):
								chan = rl(incom[5])
								if (len(incom) >= 7):
									data = incom[6:]
									passthrough = 1
								else:
									passthrough = 0
									buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter any nicks")
							else:
								passthrough = 0
								buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter a channel")
						if ((type == 'CMSG') or (type == 'CNOTE')):
							if (len(incom) >= 6):
								data = incom[5:]
								passthrough = 1
							else:
								passthrough = 0
								buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter any nicks")
						if (passthrough == 1):
							if (getaccess(sock,loggedin[sock][user]['username'],chan,'CHANNEL') >= 5):
								massmodes(sock,user,chan,['REM','a',data])
							else:
								buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
				elif (incom[4].upper() == 'PROTECTME'):
					if (islogged(sock,user) == 'FALSE'):
						buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
					else:
						if ((type == 'PMSG') or (type == 'PNOTE')):
							if (len(incom) >= 6):
								chan = rl(incom[5])
								passthrough = 1
							else:
								passthrough = 0
								buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter a channel")
						if ((type == 'CMSG') or (type == 'CNOTE')):
							passthrough = 1
						if (passthrough == 1):
							if (getaccess(sock,loggedin[sock][user]['username'],chan,'CHANNEL') >= 4):
								massmodes(sock,user,chan,['ADD','a',user]) #can be ADD or REM
							else:
								buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
				elif (incom[4].upper() == 'DEPROTECTME'):
					if (islogged(sock,user) == 'FALSE'):
						buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
					else:
						if ((type == 'PMSG') or (type == 'PNOTE')):
							if (len(incom) >= 6):
								chan = rl(incom[5])
								passthrough = 1
							else:
								passthrough = 0
								buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter a channel")
						if ((type == 'CMSG') or (type == 'CNOTE')):
							passthrough = 1
						if (passthrough == 1):
							if (getaccess(sock,loggedin[sock][user]['username'],chan,'CHANNEL') >= 4):
								massmodes(sock,user,chan,['REM','a',user])
							else:
								buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
				elif (incom[4].upper() == 'MOP'):
					if (islogged(sock,user) == 'FALSE'):
						buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
					else:
						if ((type == 'PMSG') or (type == 'PNOTE')):
							if (len(incom) >= 6):
								chan = rl(incom[5])
								passthrough = 1
							else:
								passthrough = 0
								buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter a channel")
						if ((type == 'CMSG') or (type == 'CNOTE')):
							passthrough = 1
						if (passthrough == 1):
							if (getaccess(sock,loggedin[sock][user]['username'],chan,'CHANNEL') >= 3):
								massmodes(sock,user,chan,['ADD','o','ALL'])
							else:
								buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
				elif (incom[4].upper() == 'OP'):
					if (islogged(sock,user) == 'FALSE'):
						buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
					else:
						if ((type == 'PMSG') or (type == 'PNOTE')):
							if (len(incom) >= 6):
								chan = rl(incom[5])
								if (len(incom) >= 7):
									data = incom[6:]
									passthrough = 1
								else:
									passthrough = 0
									buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter any nicks")
							else:
								passthrough = 0
								buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter a channel")
						if ((type == 'CMSG') or (type == 'CNOTE')):
							if (len(incom) >= 6):
								data = incom[5:]
								passthrough = 1
							else:
								passthrough = 0
								buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter any nicks")
						if (passthrough == 1):
							if (getaccess(sock,loggedin[sock][user]['username'],chan,'CHANNEL') >= 3):
								massmodes(sock,user,chan,['ADD','o',data])
							else:
								buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
				elif (incom[4].upper() == 'MDEOP'):
					if (islogged(sock,user) == 'FALSE'):
						buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
					else:
						if ((type == 'PMSG') or (type == 'PNOTE')):
							if (len(incom) >= 6):
								chan = rl(incom[5])
								passthrough = 1
							else:
								passthrough = 0
								buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter a channel")
						if ((type == 'CMSG') or (type == 'CNOTE')):
							passthrough = 1
						if (passthrough == 1):
							if (getaccess(sock,loggedin[sock][user]['username'],chan,'CHANNEL') >= 3):
								massmodes(sock,user,chan,['REM','o','BC'])
							else:
								buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
				elif (incom[4].upper() == 'DEOP'):
					if (islogged(sock,user) == 'FALSE'):
						buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
					else:
						if ((type == 'PMSG') or (type == 'PNOTE')):
							if (len(incom) >= 6):
								chan = rl(incom[5])
								if (len(incom) >= 7):
									data = incom[6:]
									passthrough = 1
								else:
									passthrough = 0
									buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter any nicks")
							else:
								passthrough = 0
								buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter a channel")
						if ((type == 'CMSG') or (type == 'CNOTE')):
							if (len(incom) >= 6):
								data = incom[5:]
								passthrough = 1
							else:
								passthrough = 0
								buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter any nicks")
						if (passthrough == 1):
							if (getaccess(sock,loggedin[sock][user]['username'],chan,'CHANNEL') >= 3):
								massmodes(sock,user,chan,['REM','o',data])
							else:
								buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
				elif (incom[4].upper() == 'OPME'):
					if (islogged(sock,user) == 'FALSE'):
						buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
					else:
						if ((type == 'PMSG') or (type == 'PNOTE')):
							if (len(incom) >= 6):
								chan = rl(incom[5])
								passthrough = 1
							else:
								passthrough = 0
								buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter a channel")
						if ((type == 'CMSG') or (type == 'CNOTE')):
							passthrough = 1
						if (passthrough == 1):
							if (getaccess(sock,loggedin[sock][user]['username'],chan,'CHANNEL') >= 3):
								massmodes(sock,user,chan,['ADD','o',user]) #can be ADD or REM
							else:
								buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
				elif (incom[4].upper() == 'DEOPME'):
					if (islogged(sock,user) == 'FALSE'):
						buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
					else:
						if ((type == 'PMSG') or (type == 'PNOTE')):
							if (len(incom) >= 6):
								chan = rl(incom[5])
								passthrough = 1
							else:
								passthrough = 0
								buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter a channel")
						if ((type == 'CMSG') or (type == 'CNOTE')):
							passthrough = 1
						if (passthrough == 1):
							if (getaccess(sock,loggedin[sock][user]['username'],chan,'CHANNEL') >= 3):
								massmodes(sock,user,chan,['REM','o',user])
							else:
								buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
				elif (incom[4].upper() == 'MHALFOP'):
					if (islogged(sock,user) == 'FALSE'):
						buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
					else:
						if ((type == 'PMSG') or (type == 'PNOTE')):
							if (len(incom) >= 6):
								chan = rl(incom[5])
								passthrough = 1
							else:
								passthrough = 0
								buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter a channel")
						if ((type == 'CMSG') or (type == 'CNOTE')):
							passthrough = 1
						if (passthrough == 1):
							if (getaccess(sock,loggedin[sock][user]['username'],chan,'CHANNEL') >= 3):
								massmodes(sock,user,chan,['ADD','h','ALL'])
							else:
								buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
				elif (incom[4].upper() == 'HALFOP'):
					if (islogged(sock,user) == 'FALSE'):
						buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
					else:
						if ((type == 'PMSG') or (type == 'PNOTE')):
							if (len(incom) >= 6):
								chan = rl(incom[5])
								if (len(incom) >= 7):
									data = incom[6:]
									passthrough = 1
								else:
									passthrough = 0
									buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter any nicks")
							else:
								passthrough = 0
								buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter a channel")
						if ((type == 'CMSG') or (type == 'CNOTE')):
							if (len(incom) >= 6):
								data = incom[5:]
								passthrough = 1
							else:
								passthrough = 0
								buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter any nicks")
						if (passthrough == 1):
							if (getaccess(sock,loggedin[sock][user]['username'],chan,'CHANNEL') >= 3):
								massmodes(sock,user,chan,['ADD','h',data])
							else:
								buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
				elif (incom[4].upper() == 'MDEHALFOP'):
					if (islogged(sock,user) == 'FALSE'):
						buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
					else:
						if ((type == 'PMSG') or (type == 'PNOTE')):
							if (len(incom) >= 6):
								chan = rl(incom[5])
								passthrough = 1
							else:
								passthrough = 0
								buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter a channel")
						if ((type == 'CMSG') or (type == 'CNOTE')):
							passthrough = 1
						if (passthrough == 1):
							if (getaccess(sock,loggedin[sock][user]['username'],chan,'CHANNEL') >= 3):
								massmodes(sock,user,chan,['REM','h','BC'])
							else:
								buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
				elif (incom[4].upper() == 'DEHALFOP'):
					if (islogged(sock,user) == 'FALSE'):
						buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
					else:
						if ((type == 'PMSG') or (type == 'PNOTE')):
							if (len(incom) >= 6):
								chan = rl(incom[5])
								if (len(incom) >= 7):
									data = incom[6:]
									passthrough = 1
								else:
									passthrough = 0
									buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter any nicks")
							else:
								passthrough = 0
								buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter a channel")
						if ((type == 'CMSG') or (type == 'CNOTE')):
							if (len(incom) >= 6):
								data = incom[5:]
								passthrough = 1
							else:
								passthrough = 0
								buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter any nicks")
						if (passthrough == 1):
							if (getaccess(sock,loggedin[sock][user]['username'],chan,'CHANNEL') >= 3):
								massmodes(sock,user,chan,['REM','h',data])
							else:
								buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
				elif (incom[4].upper() == 'HALFOPME'):
					if (islogged(sock,user) == 'FALSE'):
						buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
					else:
						if ((type == 'PMSG') or (type == 'PNOTE')):
							if (len(incom) >= 6):
								chan = rl(incom[5])
								passthrough = 1
							else:
								passthrough = 0
								buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter a channel")
						if ((type == 'CMSG') or (type == 'CNOTE')):
							passthrough = 1
						if (passthrough == 1):
							if (getaccess(sock,loggedin[sock][user]['username'],chan,'CHANNEL') >= 2):
								massmodes(sock,user,chan,['ADD','h',user]) #can be ADD or REM
							else:
								buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
				elif (incom[4].upper() == 'DEHALFOPME'):
					if (islogged(sock,user) == 'FALSE'):
						buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
					else:
						if ((type == 'PMSG') or (type == 'PNOTE')):
							if (len(incom) >= 6):
								chan = rl(incom[5])
								passthrough = 1
							else:
								passthrough = 0
								buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter a channel")
						if ((type == 'CMSG') or (type == 'CNOTE')):
							passthrough = 1
						if (passthrough == 1):
							if (getaccess(sock,loggedin[sock][user]['username'],chan,'CHANNEL') >= 2):
								massmodes(sock,user,chan,['REM','h',user])
							else:
								buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
				elif (incom[4].upper() == 'MVOICE'):
					if (islogged(sock,user) == 'FALSE'):
						buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
					else:
						if ((type == 'PMSG') or (type == 'PNOTE')):
							if (len(incom) >= 6):
								chan = rl(incom[5])
								passthrough = 1
							else:
								passthrough = 0
								buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter a channel")
						if ((type == 'CMSG') or (type == 'CNOTE')):
							passthrough = 1
						if (passthrough == 1):
							if (getaccess(sock,loggedin[sock][user]['username'],chan,'CHANNEL') >= 2):
								massmodes(sock,user,chan,['ADD','v','ALL'])
							else:
								buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
				elif (incom[4].upper() == 'VOICE'):
					if (islogged(sock,user) == 'FALSE'):
						buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
					else:
						if ((type == 'PMSG') or (type == 'PNOTE')):
							if (len(incom) >= 6):
								chan = rl(incom[5])
								if (len(incom) >= 7):
									data = incom[6:]
									passthrough = 1
								else:
									passthrough = 0
									buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter any nicks")
							else:
								passthrough = 0
								buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter a channel")
						if ((type == 'CMSG') or (type == 'CNOTE')):
							if (len(incom) >= 6):
								data = incom[5:]
								passthrough = 1
							else:
								passthrough = 0
								buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter any nicks")
						if (passthrough == 1):
							if (getaccess(sock,loggedin[sock][user]['username'],chan,'CHANNEL') >= 2):
								massmodes(sock,user,chan,['ADD','v',data])
							else:
								buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
				elif (incom[4].upper() == 'MDEVOICE'):
					if (islogged(sock,user) == 'FALSE'):
						buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
					else:
						if ((type == 'PMSG') or (type == 'PNOTE')):
							if (len(incom) >= 6):
								chan = rl(incom[5])
								passthrough = 1
							else:
								passthrough = 0
								buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter a channel")
						if ((type == 'CMSG') or (type == 'CNOTE')):
							passthrough = 1
						if (passthrough == 1):
							if (getaccess(sock,loggedin[sock][user]['username'],chan,'CHANNEL') >= 2):
								massmodes(sock,user,chan,['REM','v','BC'])
							else:
								buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
				elif (incom[4].upper() == 'DEVOICE'):
					if (islogged(sock,user) == 'FALSE'):
						buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
					else:
						if ((type == 'PMSG') or (type == 'PNOTE')):
							if (len(incom) >= 6):
								chan = rl(incom[5])
								if (len(incom) >= 7):
									data = incom[6:]
									passthrough = 1
								else:
									passthrough = 0
									buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter any nicks")
							else:
								passthrough = 0
								buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter a channel")
						if ((type == 'CMSG') or (type == 'CNOTE')):
							if (len(incom) >= 6):
								data = incom[5:]
								passthrough = 1
							else:
								passthrough = 0
								buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter any nicks")
						if (passthrough == 1):
							if (getaccess(sock,loggedin[sock][user]['username'],chan,'CHANNEL') >= 2):
								massmodes(sock,user,chan,['REM','v',data])
							else:
								buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
				elif (incom[4].upper() == 'VOICEME'):
					if (islogged(sock,user) == 'FALSE'):
						buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
					else:
						if ((type == 'PMSG') or (type == 'PNOTE')):
							if (len(incom) >= 6):
								chan = rl(incom[5])
								passthrough = 1
							else:
								passthrough = 0
								buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter a channel")
						if ((type == 'CMSG') or (type == 'CNOTE')):
							passthrough = 1
						if (passthrough == 1):
							if (getaccess(sock,loggedin[sock][user]['username'],chan,'CHANNEL') >= 1):
								massmodes(sock,user,chan,['ADD','v',user]) #can be ADD or REM
							else:
								buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
				elif (incom[4].upper() == 'DEVOICEME'):
					if (islogged(sock,user) == 'FALSE'):
						buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
					else:
						if ((type == 'PMSG') or (type == 'PNOTE')):
							if (len(incom) >= 6):
								chan = rl(incom[5])
								passthrough = 1
							else:
								passthrough = 0
								buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter a channel")
						if ((type == 'CMSG') or (type == 'CNOTE')):
							passthrough = 1
						if (passthrough == 1):
							if (getaccess(sock,loggedin[sock][user]['username'],chan,'CHANNEL') >= 1):
								massmodes(sock,user,chan,['REM','v',user])
							else:
								buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
				elif (incom[4].upper() == 'SAY'):
					if (islogged(sock,user) == 'FALSE'):
						buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
					else:
						if ((type == 'PMSG') or (type == 'PNOTE')):
							if (len(incom) >= 6):
								chan = rl(incom[5])
								if (len(incom) >= 7):
									data = incom[6:]
									passthrough = 1
								else:
									passthrough = 0
									buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter a message")								
							else:
								passthrough = 0
								buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter a channel")
						if ((type == 'CMSG') or (type == 'CNOTE')):
							if (len(incom) >= 6):
								data = incom[5:]
								passthrough = 1
							else:
								passthrough = 0
								buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter a message")
						if (passthrough == 1):
							if (getaccess(sock,loggedin[sock][user]['username'],chan,'CHANNEL') >= 1):
								sts(sock,"PRIVMSG {0} :{1}".format(chan,splitjoiner(data)))
							else:
								buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
				elif (incom[4].upper() == 'ACT'):
					if (islogged(sock,user) == 'FALSE'):
						buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
					else:
						if ((type == 'PMSG') or (type == 'PNOTE')):
							if (len(incom) >= 6):
								chan = rl(incom[5])
								if (len(incom) >= 7):
									data = incom[6:]
									passthrough = 1
								else:
									passthrough = 0
									buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter a action")								
							else:
								passthrough = 0
								buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter a channel")
						if ((type == 'CMSG') or (type == 'CNOTE')):
							if (len(incom) >= 6):
								data = incom[5:]
								passthrough = 1
							else:
								passthrough = 0
								buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter a action")
						if (passthrough == 1):
							if (getaccess(sock,loggedin[sock][user]['username'],chan,'CHANNEL') >= 1):
								sts(sock,"PRIVMSG {0} :\x01ACTION {1}\x01".format(chan,splitjoiner(data)))
							else:
								buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
				elif (incom[4].upper() == 'ACCOUNT'):
					if ((type == 'PMSG') or (type == 'PNOTE')):
						if (islogged(sock,user) == 'TRUE'):
							if (len(incom) >= 6):
								userdetails = pulluser(loggedin[sock][user]['username'])
								if (incom[5].upper() == 'CHGPASS'):
									if (len(incom) >= 7):
										if (len(incom) >= 8):
											tmppass = hashlib.md5()
											tmppass.update(incom[6])
											if (userdata['password'] == tmppass.hexdigest()):
												tmppass2 = hashlib.md5()
												tmppass2.update(incom[7])
												sql = "UPDATE users SET password = '{0}' where id = '{1}'".format(tmppass2.hexdigest(),userdetails['id'])
												vals = db.execute(sql)
												buildmsg(sock,'NORMAL',user,chan,'PRIV',"You have successfully changed your password.")
											else:
												buildmsg(sock,'ERROR',user,chan,'PRIV',"You sure you entered your current password right")
										else:
											buildmsg(sock,'ERROR',user,chan,'PRIV',"Missing New Password")
									else:
										buildmsg(sock,'ERROR',user,chan,'PRIV',"Missing Current Password")
								if (incom[5].upper() == 'MSGTYPE'):
									if (len(incom) >= 7):
										if (incom[6].lower() == 'notice'):
											newtype = 'notice'
										else:
											newtype = 'msg'
										sql = "UPDATE users SET msgtype = '{0}' where id = '{1}'".format(newtype,userdetails['id'])
										vals = db.execute(sql)
										loggedin[sock][user]['msgtype'] = newtype
										buildmsg(sock,'NORMAL',user,chan,'PRIV',"You have successfully changed your message type to {0}".format(newtype))
									else:
										buildmsg(sock,'NORMAL',user,chan,'PRIV',"You have to enter a Message type")
							else:
								buildmsg(sock,'NORMAL',user,chan,'PRIV',"Your Account Details {0}({1})".format(user,loggedin[sock][user]['username']))
								buildmsg(sock,'NORMAL',user,chan,'PRIV',"MSGTYPE: {0}".format(loggedin[sock][user]['msgtype']))
						else:
							buildmsg(sock,'ERROR',user,chan,'PRIV','NOTLOGGED')
					else:
						buildmsg(sock,'ERROR',user,chan,'PRIV',"You can not access your account via channel commands")
				elif (incom[4].upper() == 'LOGOUT'):
					if (islogged(sock,user) == 'TRUE'):
						buildmsg(sock,'NORMAL',user,chan,'PRIV',"You have been logged out of {0}".format(loggedin[sock][user]['username']))
						del loggedin[sock][user]
					else:
						buildmsg(sock,'ERROR',user,chan,'PRIV','NOTLOGGED')
				elif (incom[4].upper() == 'LOGIN'):
					if ((type == 'PMSG') or (type == 'PNOTE')):
						if (islogged(sock,user) == 'FALSE'):
							if (len(incom) >= 6):
								if (len(incom) >= 7):
									udata = pulluser(rl(incom[5]))
									if (udata != 'FALSE'):
										tmppass = hashlib.md5()
										tmppass.update(incom[6])
										if (udata['password'] == tmppass.hexdigest()):
											loggedin[sock][user] = {'username': udata['username'], 'msgtype': udata['msgtype'], 'umask': incom[0]}
											buildmsg(sock,'NORMAL',user,chan,'PRIV',"You have successfully logged in as {0}".format(incom[5]))
										else:
											buildmsg(sock,'ERROR',user,chan,'PRIV',"You have failed to login")
									else:
										buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter a valid username")
								else:
									buildmsg(sock,'ERROR',user,chan,'PRIV',"You only entered a username, please enter a password as well")
							else:
								buildmsg(sock,'ERROR',user,chan,'PRIV',"You are missing <username> <password>")
						else:
							buildmsg(sock,'ERROR',user,chan,'PRIV',"You are already LOGGED In as {0}".format(loggedin[sock][user]['username']))
					else:
						buildmsg(sock,'ERROR',user,chan,'PRIV',"You can not log in via channel commands")						
				elif (incom[4].upper() == 'REGISTER'):
					if ((type == 'PMSG') or (type == 'PNOTE')):
						if (islogged(sock,user) == 'FALSE'):
							if (len(incom) >= 6):
								if (len(incom) >=7):
									tmpudata = pulluser(rl(incom[5]))
									if (tmpudata == 'FALSE'):
										tmppass = hashlib.md5()
										tmppass.update(incom[6])
										sql = "INSERT INTO users (username, password, global, server, channel, msgtype) VALUES ('{0}', '{1}', '{2}', '{3}', '{4}', '{5}')".format(rl(incom[5]),tmppass.hexdigest(),'NULL','NULL','NULL','msg')
										blarg = db.insert(sql)										
										loggedin[sock][user] = {'username': rl(incom[5]), 'msgtype': 'msg', 'umask': incom[0]}
										buildmsg(sock,'NORMAL',user,chan,'PRIV',"You have successfully registered as {0} and have been auto logged-in".format(incom[5]))
									else:
										buildmsg(sock,'ERROR',user,chan,'PRIV',"The username you entered already exists")
								else:
									buildmsg(sock,'ERROR',user,chan,'PRIV',"You only entered a username, please enter a password as well")
							else:
								buildmsg(sock,'ERROR',user,chan,'PRIV',"You are missing <username> <password>")
						else:
							buildmsg(sock,'ERROR',user,chan,'PRIV','LOGIN')				
					else:
						buildmsg(sock,'ERROR',user,chan,'PRIV',"You can not register via channel commands")
				elif (incom[4].upper() == 'HELP'):
					if ((type == 'PMSG') or (type == 'PNOTE')):
						if (len(incom) >= 6):
							chan = incom[5]
					helpcmd(sock,user,chan,incom)
				elif (incom[4].upper() == 'WHOIS'):
					if ((type == 'PMSG') or (type == 'PNOTE')):
						if (len(incom) >= 6):
							chan = incom[5]
							if (len(incom) >= 7):
								uwho = incom[6]
								passthrough = 1
							else:
								uwho = 'NULL'
								passthrough = 1
						else:
							buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter a channel")
					if ((type == 'CMSG') or (type == 'CNOTE')):
						if (len(incom) >= 6):
							uwho = incom[5]
							passthrough = 1
						else:
							uwho = 'NULL'
							passthrough = 1
					if (passthrough == 1):
						getwhois(sock,user,chan,'WHOIS',uwho)					
				elif (incom[4].upper() == 'WHOAMI'):
					if ((type == 'PMSG') or (type == 'PNOTE')):
						if (len(incom) >= 6):
							chan = rl(incom[5])
							passthrough = 1
						else:
							buildmsg(sock,'ERROR',user,chan,'PRIV',"You didn't enter a channel")
					if ((type == 'CMSG') or (type == 'CNOTE')):
						passthrough = 1
					if (passthrough == 1):	
						getwhois(sock,user,chan,'WHOAMI','NULL')					
				elif (incom[4].upper() == 'VERSION'):
					buildmsg(sock,'NORMAL',user,chan,'PRIV',"Ch3wyB0t Version {0}".format(version))				
				elif (incom[4].upper() == 'TESTCMD'):
					if (loggedgetaccess(sock,user,chan,'GLOBAL') >= 7):
						debug(sock,mysockets[sock])
					else:
						buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
				elif (incom[4].upper() == 'TESTDATA'):
					if (loggedgetaccess(sock,user,chan,'GLOBAL') >= 7):
						debug(sock,mysockets)
					else:
						buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
				elif (incom[4].upper() == 'TEST'):
					if (loggedgetaccess(sock,user,chan,'GLOBAL') >= 7):

						#sts(sock,"MODE :{0}".format(mysockets[sock]['nick']))
						buildmsg(sock,'ERROR',user,chan,'PRIV',"data {0}".format('blarg'))
					else:
						buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESS')
				else:
					debug(sock,incom)
					if ((type == 'CMSG') or (type == 'CNOTE')):
						buildmsg(sock,'ERROR',user,chan,'CHAN',"The command {0} doesn't exist at the momment".format(incom[4]))
					if ((type == 'PMSG') or (type == 'PNOTE')):
						buildmsg(sock,'ERROR',user,chan,'PRIV',"The command {0} doesn't exist at the momment".format(incom[4]))
		else:
			if (type == 'PNOTE'):
				if (user == 'NickServ'):
					if (len(incom) >= 9):
						if ((incom[6] == 'registered') and (incom[8] == 'protected.')):
							if (mysockets[sock]['server']['nickservpass'] != 'NULL'):
								mysockets[sock]['nickserv'] = '1ST'
						elif ((incom[6] == 'NickServ') and (incom[7] == 'IDENTIFY')):
							if ((mysockets[sock]['server']['nickservpass'] != 'NULL') and (mysockets[sock]['nickserv'] == '1ST')):
								del mysockets[sock]['nickserv']
								sts(sock,"PRIVMSG NickServ :IDENTIFY {0}".format(mysockets[sock]['server']['nickservpass']))
								mysockets[sock]['identified'] = 'TRUE'
								autojoinchannels(sock)
				else:
					debug(sock,incom)
			else:
				#buildmsg(sock,'NORMAL',user,chan,'PRIV',output) #types are NORMAL, HELP, ERROR  mtype are PRIV, CHAN
				debug(sock,incom)
	else:
		#blarg = 'TRUE'
		#buildmsg(sock,'ERROR',user,chan,'PRIV',"The command {0} doesn't exist at the momment".format(incom[3]))
		debug(sock,incom)
*/

	}
	
	private function _core_command_help($id,$sender,$chan,$indata) {
/*	
def helpcmd(sock,user,chan,incom):
	buildmsg(sock,'HELP',user,chan,'PRIV',"{0} help system".format(settings['botname']))
	buildmsg(sock,'HELP',user,chan,'PRIV',"If you need help on a certain command go help <command>")
	buildmsg(sock,'HELP',user,chan,'PRIV',"{0}{3} = CHAN, {2}{3} = DCC, {1}{3} = MSG".format(settings['chancom'],settings['pvtcom'],settings['dcccom'],settings['signal']))
	if (len(incom) >= 6):
		if (chan == incom[5]):
			if (len(incom) >= 7):
				hcmds = incom[6:]
				processhelp = 1
			else:
				processhelp = 0
		else:
			hcmds = incom[5:]
			processhelp = 1
	else:
		processhelp = 0
	if (processhelp == 1):
		#debug(sock,len(incom))
		if (hcmds[0].upper() == 'EXIT'):
			if (loggedgetaccess(sock,user,chan,'GLOBAL') >= 6):
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(EXIT)- This Command will cause the bot to exit completely")
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(EXIT)- Command Structure: {0}{1} exit <message>".format(settings['pvtcom'],settings['signal']))
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(EXIT)- Command Structure: {0}{1} exit <message>".format(settings['chancom'],settings['signal']))
			else:
				buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESSHELP')
		elif (hcmds[0].upper() == 'RAW'):
			if (loggedgetaccess(sock,user,chan,'GLOBAL') >= 7):
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(RAW)- This Command is super dangerous as it will send whatever is entered into it")
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(RAW)- Command Structure: {0}{1} raw <data to send>".format(settings['pvtcom'],settings['signal']))
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(RAW)- Command Structure: {0}{1} raw <data to send>".format(settings['chancom'],settings['signal']))
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(RAW)- It is highly recommend you DO NOT use this command")
			else:
				buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESSHELP')
		elif (hcmds[0].upper() == 'RAWDB'):
			if (loggedgetaccess(sock,user,chan,'GLOBAL') >= 7):
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(RAWDB)- This Command is super dangerous as it will send whatever is entered into it")
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(RAWDB)- Command Structure: {0}{1} rawdb <data to send>".format(settings['pvtcom'],settings['signal']))
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(RAWDB)- Command Structure: {0}{1} rawdb <data to send>".format(settings['chancom'],settings['signal']))
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(RAWDB)- It is highly recommend you DO NOT use this command")
			else:
				buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESSHELP')				
		elif (hcmds[0].upper() == 'QUIT'):
			if (loggedgetaccess(sock,user,chan,'SERVER') >= 6):
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(QUIT)- This Command will cause the bot to quit from the current network")
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(QUIT)- Command Structure: {0}{1} quit <message>".format(settings['pvtcom'],settings['signal']))
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(QUIT)- Command Structure: {0}{1} quit <message>".format(settings['chancom'],settings['signal']))
			else:
				buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESSHELP')
		elif (hcmds[0].upper() == 'REHASH'):
			if (loggedgetaccess(sock,user,chan,'GLOBAL') >= 6):
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(REHASH)- This Command will cause the bot to reload from the database")
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(REHASH)- Command Structure: {0}{1} rehash".format(settings['pvtcom'],settings['signal']))
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(REHASH)- Command Structure: {0}{1} rehash".format(settings['chancom'],settings['signal']))
			else:
				buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESSHELP')
		elif (hcmds[0].upper() == 'SETTINGS'):
			if (loggedgetaccess(sock,user,chan,'GLOBAL') >= 6):
				if (len(hcmds) >= 2):
					if (hcmds[1].upper() == 'LIST'):
						buildmsg(sock,'HELP',user,chan,'PRIV',"-(SETTINGS)-(LIST)- This Command will list the values that are currently in the bots settings")
						buildmsg(sock,'HELP',user,chan,'PRIV',"-(SETTINGS)-(LIST)- Command Structure: {0}{1} settings list".format(settings['pvtcom'],settings['signal']))
					elif (hcmds[1].upper() == 'SET'):
						buildmsg(sock,'HELP',user,chan,'PRIV',"-(SETTINGS)-(SET)- This Command will set the value you pick and update both local and the db")
						buildmsg(sock,'HELP',user,chan,'PRIV',"-(SETTINGS)-(SET)- Command Structure: {0}{1} settings set <setting> <value>".format(settings['pvtcom'],settings['signal']))
					else:
						buildmsg(sock,'ERROR',user,chan,'PRIV',"-(SETTINGS)- The help topic account {0} is not in the database".format(hcmds[1]))
				else:
					buildmsg(sock,'HELP',user,chan,'PRIV',"-(SETTINGS)- This Command deals with the bots settings")
					buildmsg(sock,'HELP',user,chan,'PRIV',"-(SETTINGS)- Command Structure: {0}{1} settings [<list>][<set> <setting> <value>]".format(settings['pvtcom'],settings['signal']))
					buildmsg(sock,'HELP',user,chan,'PRIV',"-(SETTINGS)- Topics available: list set")
			else:
				buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESSHELP')

		#loggedgetaccess(sock,user,chan,type)
		elif (hcmds[0].upper() == 'MOWNER'):
			if (loggedgetaccess(sock,user,chan,'CHANNEL') >= 5):
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(MOWNER)- This Command will Owner everyone in <channel>")
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(MOWNER)- Command Structure: {0}{1} MOwner <channel>".format(settings['pvtcom'],settings['signal']))
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(MOWNER)- Command Structure: {0}{1} MOwner".format(settings['chancom'],settings['signal']))
			else:
				buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESSHELP')
		elif (hcmds[0].upper() == 'OWNER'):
			if (loggedgetaccess(sock,user,chan,'CHANNEL') >= 5):
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(OWNER)- This Command will Owner the <nicks> you pick on <channel>")
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(OWNER)- Command Structure: {0}{1} Owner <channel> <nick> [<nick> [<nick>]]".format(settings['pvtcom'],settings['signal']))
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(OWNER)- Command Structure: {0}{1} Owner <nick> [<nick> [<nick>]]".format(settings['chancom'],settings['signal']))
			else:
				buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESSHELP')
		elif (hcmds[0].upper() == 'MDEOWNER'):
			if (loggedgetaccess(sock,user,chan,'CHANNEL') >= 5):
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(MDEOWNER)- This Command will DeOwner everyone in <channel> but the bot and you")
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(MDEOWNER)- Command Structure: {0}{1} MDeOwner <channel>".format(settings['pvtcom'],settings['signal']))
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(MDEOWNER)- Command Structure: {0}{1} MDeOwner".format(settings['chancom'],settings['signal']))
			else:
				buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESSHELP')
		elif (hcmds[0].upper() == 'DEOWNER'):
			if (loggedgetaccess(sock,user,chan,'CHANNEL') >= 5):
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(DEOWNER)- This Command will de-Owner the <nicks> you pick on <channel>")
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(DEOWNER)- Command Structure: {0}{1} DeOwner <channel> <nick> [<nick> [<nick>]]".format(settings['pvtcom'],settings['signal']))
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(DEOWNER)- Command Structure: {0}{1} DeOwner <nick> [<nick> [<nick>]]".format(settings['chancom'],settings['signal']))
			else:
				buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESSHELP')
		elif (hcmds[0].upper() == 'OWNERME'):
			if (loggedgetaccess(sock,user,chan,'CHANNEL') >= 5):
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(OWNERME)- This Command will Owner yourself on <channel>")
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(OWNERME)- Command Structure: {0}{1} OwnerMe <channel>".format(settings['pvtcom'],settings['signal']))
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(OWNERME)- Command Structure: {0}{1} OwnerMe".format(settings['chancom'],settings['signal']))
			else:
				buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESSHELP')
		elif (hcmds[0].upper() == 'DEOWNERME'):
			if (loggedgetaccess(sock,user,chan,'CHANNEL') >= 5):
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(DEOWNERME)- This Command will de-Owner yourself on <channel>")
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(DEOWNERME)- Command Structure: {0}{1} DeOwnerMe <channel>".format(settings['pvtcom'],settings['signal']))
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(DEOWNERME)- Command Structure: {0}{1} DeOwnerMe".format(settings['chancom'],settings['signal']))
			else:
				buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESSHELP')
		elif (hcmds[0].upper() == 'MPROTECT'):
			if (loggedgetaccess(sock,user,chan,'CHANNEL') >= 5):
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(MPROTECT)- This Command will Protect everyone in <channel>")
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(MPROTECT)- Command Structure: {0}{1} MProtect <channel>".format(settings['pvtcom'],settings['signal']))
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(MPROTECT)- Command Structure: {0}{1} MProtect".format(settings['chancom'],settings['signal']))
			else:
				buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESSHELP')
		elif (hcmds[0].upper() == 'PROTECT'):
			if (loggedgetaccess(sock,user,chan,'CHANNEL') >= 5):
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(PROTECT)- This Command will Protect the <nicks> you pick on <channel>")
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(PROTECT)- Command Structure: {0}{1} Protect <channel> <nick> [<nick> [<nick>]]".format(settings['pvtcom'],settings['signal']))
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(PROTECT)- Command Structure: {0}{1} Protect <nick> [<nick> [<nick>]]".format(settings['chancom'],settings['signal']))
			else:
				buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESSHELP')
		elif (hcmds[0].upper() == 'MDEPROTECT'):
			if (loggedgetaccess(sock,user,chan,'CHANNEL') >= 5):
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(MDEPROTECT)- This Command will DeProtect everyone in <channel> but the bot and you")
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(MDEPROTECT)- Command Structure: {0}{1} MDeProtect <channel>".format(settings['pvtcom'],settings['signal']))
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(MDEPROTECT)- Command Structure: {0}{1} MDeProtect".format(settings['chancom'],settings['signal']))
			else:
				buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESSHELP')
		elif (hcmds[0].upper() == 'DEPROTECT'):
			if (loggedgetaccess(sock,user,chan,'CHANNEL') >= 5):
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(DEPROTECT)- This Command will de-Protect the <nicks> you pick on <channel>")
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(DEPROTECT)- Command Structure: {0}{1} DeProtect <channel> <nick> [<nick> [<nick>]]".format(settings['pvtcom'],settings['signal']))
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(DEPROTECT)- Command Structure: {0}{1} DeProtect <nick> [<nick> [<nick>]]".format(settings['chancom'],settings['signal']))
			else:
				buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESSHELP')
		elif (hcmds[0].upper() == 'PROTECTME'):
			if (loggedgetaccess(sock,user,chan,'CHANNEL') >= 4):
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(PROTECTME)- This Command will Protect yourself on <channel>")
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(PROTECTME)- Command Structure: {0}{1} ProtectMe <channel>".format(settings['pvtcom'],settings['signal']))
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(PROTECTME)- Command Structure: {0}{1} ProtectMe".format(settings['chancom'],settings['signal']))
			else:
				buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESSHELP')
		elif (hcmds[0].upper() == 'DEPROTECTME'):
			if (loggedgetaccess(sock,user,chan,'CHANNEL') >= 4):
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(DEPROTECTME)- This Command will de-Protect yourself on <channel>")
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(DEPROTECTME)- Command Structure: {0}{1} DeProtectMe <channel>".format(settings['pvtcom'],settings['signal']))
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(DEPROTECTME)- Command Structure: {0}{1} DeProtectMe".format(settings['chancom'],settings['signal']))
			else:
				buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESSHELP')
		elif (hcmds[0].upper() == 'MOP'):
			if (loggedgetaccess(sock,user,chan,'CHANNEL') >= 3):
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(MOP)- This Command will Op everyone in <channel>")
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(MOP)- Command Structure: {0}{1} MOp <channel>".format(settings['pvtcom'],settings['signal']))
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(MOP)- Command Structure: {0}{1} MOp".format(settings['chancom'],settings['signal']))
			else:
				buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESSHELP')
		elif (hcmds[0].upper() == 'OP'):
			if (loggedgetaccess(sock,user,chan,'CHANNEL') >= 3):
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(OP)- This Command will Op the <nicks> you pick on <channel>")
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(OP)- Command Structure: {0}{1} Op <channel> <nick> [<nick> [<nick>]]".format(settings['pvtcom'],settings['signal']))
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(OP)- Command Structure: {0}{1} Op <nick> [<nick> [<nick>]]".format(settings['chancom'],settings['signal']))
			else:
				buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESSHELP')
		elif (hcmds[0].upper() == 'MDEOP'):
			if (loggedgetaccess(sock,user,chan,'CHANNEL') >= 3):
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(MDEOP)- This Command will DeOp everyone in <channel> but the bot and you")
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(MDEOP)- Command Structure: {0}{1} MDeOp <channel>".format(settings['pvtcom'],settings['signal']))
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(MDEOP)- Command Structure: {0}{1} MDeOp".format(settings['chancom'],settings['signal']))
			else:
				buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESSHELP')
		elif (hcmds[0].upper() == 'DEOP'):
			if (loggedgetaccess(sock,user,chan,'CHANNEL') >= 3):
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(DEOP)- This Command will de-Op the <nicks> you pick on <channel>")
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(DEOP)- Command Structure: {0}{1} DeOp <channel> <nick> [<nick> [<nick>]]".format(settings['pvtcom'],settings['signal']))
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(DEOP)- Command Structure: {0}{1} DeOp <nick> [<nick> [<nick>]]".format(settings['chancom'],settings['signal']))
			else:
				buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESSHELP')
		elif (hcmds[0].upper() == 'OPME'):
			if (loggedgetaccess(sock,user,chan,'CHANNEL') >= 3):
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(OPME)- This Command will Op yourself on <channel>")
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(OPME)- Command Structure: {0}{1} OpMe <channel>".format(settings['pvtcom'],settings['signal']))
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(OPME)- Command Structure: {0}{1} OpMe".format(settings['chancom'],settings['signal']))
			else:
				buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESSHELP')
		elif (hcmds[0].upper() == 'DEOPME'):
			if (loggedgetaccess(sock,user,chan,'CHANNEL') >= 3):
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(DEOPME)- This Command will de-Op yourself on <channel>")
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(DEOPME)- Command Structure: {0}{1} DeOpMe <channel>".format(settings['pvtcom'],settings['signal']))
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(DEOPME)- Command Structure: {0}{1} DeOpMe".format(settings['chancom'],settings['signal']))
			else:
				buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESSHELP')
		elif (hcmds[0].upper() == 'MHALFOP'):
			if (loggedgetaccess(sock,user,chan,'CHANNEL') >= 3):
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(MHALFOP)- This Command will HalfOp everyone in <channel>")
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(MHALFOP)- Command Structure: {0}{1} MHalfOp <channel>".format(settings['pvtcom'],settings['signal']))
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(MHALFOP)- Command Structure: {0}{1} MHalfOp".format(settings['chancom'],settings['signal']))
			else:
				buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESSHELP')
		elif (hcmds[0].upper() == 'HALFOP'):
			if (loggedgetaccess(sock,user,chan,'CHANNEL') >= 3):
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(HALFOP)- This Command will HalfOp the <nicks> you pick on <channel>")
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(HALFOP)- Command Structure: {0}{1} HalfOp <channel> <nick> [<nick> [<nick>]]".format(settings['pvtcom'],settings['signal']))
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(HALFOP)- Command Structure: {0}{1} HalfOp <nick> [<nick> [<nick>]]".format(settings['chancom'],settings['signal']))
			else:
				buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESSHELP')
		elif (hcmds[0].upper() == 'MDEHALFOP'):
			if (loggedgetaccess(sock,user,chan,'CHANNEL') >= 3):
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(MDEHALFOP)- This Command will DeHalfOp everyone in <channel> but the bot and you")
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(MDEHALFOP)- Command Structure: {0}{1} MDeHalfOp <channel>".format(settings['pvtcom'],settings['signal']))
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(MDEHALFOP)- Command Structure: {0}{1} MDeHalfOp".format(settings['chancom'],settings['signal']))
			else:
				buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESSHELP')
		elif (hcmds[0].upper() == 'DEHALFOP'):
			if (loggedgetaccess(sock,user,chan,'CHANNEL') >= 3):
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(DEHALFOP)- This Command will de-HalfOp the <nicks> you pick on <channel>")
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(DEHALFOP)- Command Structure: {0}{1} DeHalfOp <channel> <nick> [<nick> [<nick>]]".format(settings['pvtcom'],settings['signal']))
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(DEHALFOP)- Command Structure: {0}{1} DeHalfOp <nick> [<nick> [<nick>]]".format(settings['chancom'],settings['signal']))
			else:
				buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESSHELP')
		elif (hcmds[0].upper() == 'HALFOPME'):
			if (loggedgetaccess(sock,user,chan,'CHANNEL') >= 2):
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(HALFOPME)- This Command will HalfOp yourself on <channel>")
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(HALFOPME)- Command Structure: {0}{1} HalfOpMe <channel>".format(settings['pvtcom'],settings['signal']))
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(HALFOPME)- Command Structure: {0}{1} HalfOpMe".format(settings['chancom'],settings['signal']))
			else:
				buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESSHELP')
		elif (hcmds[0].upper() == 'DEHALFOPME'):
			if (loggedgetaccess(sock,user,chan,'CHANNEL') >= 2):
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(DEHALFOPME)- This Command will de-HalfOp yourself on <channel>")
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(DEHALFOPME)- Command Structure: {0}{1} DeHalfOpMe <channel>".format(settings['pvtcom'],settings['signal']))
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(DEHALFOPME)- Command Structure: {0}{1} DeHalfOpMe".format(settings['chancom'],settings['signal']))
			else:
				buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESSHELP')
		elif (hcmds[0].upper() == 'MVOICE'):
			if (loggedgetaccess(sock,user,chan,'CHANNEL') >= 2):
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(MVOICE)- This Command will Voice everyone in <channel>")
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(MVOICE)- Command Structure: {0}{1} MVoice <channel>".format(settings['pvtcom'],settings['signal']))
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(MVOICE)- Command Structure: {0}{1} MVoice".format(settings['chancom'],settings['signal']))
			else:
				buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESSHELP')
		elif (hcmds[0].upper() == 'VOICE'):
			if (loggedgetaccess(sock,user,chan,'CHANNEL') >= 2):
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(VOICE)- This Command will voice the <nicks> you pick on <channel>")
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(VOICE)- Command Structure: {0}{1} Voice <channel> <nick> [<nick> [<nick>]]".format(settings['pvtcom'],settings['signal']))
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(VOICE)- Command Structure: {0}{1} Voice <nick> [<nick> [<nick>]]".format(settings['chancom'],settings['signal']))
			else:
				buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESSHELP')
		elif (hcmds[0].upper() == 'MDEVOICE'):
			if (loggedgetaccess(sock,user,chan,'CHANNEL') >= 2):
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(MDEVOICE)- This Command will DeVoice everyone in <channel> but the bot and you")
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(MDEVOICE)- Command Structure: {0}{1} MDeVoice <channel>".format(settings['pvtcom'],settings['signal']))
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(MDEVOICE)- Command Structure: {0}{1} MDeVoice".format(settings['chancom'],settings['signal']))
			else:
				buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESSHELP')
		elif (hcmds[0].upper() == 'DEVOICE'):
			if (loggedgetaccess(sock,user,chan,'CHANNEL') >= 2):
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(DEVOICE)- This Command will de-voice the <nicks> you pick on <channel>")
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(DEVOICE)- Command Structure: {0}{1} DeVoice <channel> <nick> [<nick> [<nick>]]".format(settings['pvtcom'],settings['signal']))
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(DEVOICE)- Command Structure: {0}{1} DeVoice <nick> [<nick> [<nick>]]".format(settings['chancom'],settings['signal']))
			else:
				buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESSHELP')
		elif (hcmds[0].upper() == 'VOICEME'):
			if (loggedgetaccess(sock,user,chan,'CHANNEL') >= 1):
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(VOICEME)- This Command will voice yourself on <channel>")
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(VOICEME)- Command Structure: {0}{1} VoiceMe <channel>".format(settings['pvtcom'],settings['signal']))
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(VOICEME)- Command Structure: {0}{1} VoiceMe".format(settings['chancom'],settings['signal']))
			else:
				buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESSHELP')
		elif (hcmds[0].upper() == 'DEVOICEME'):
			if (loggedgetaccess(sock,user,chan,'CHANNEL') >= 1):
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(DEVOICEME)- This Command will de-voice yourself on <channel>")
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(DEVOICEME)- Command Structure: {0}{1} DeVoiceMe <channel>".format(settings['pvtcom'],settings['signal']))
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(DEVOICEME)- Command Structure: {0}{1} DeVoiceMe".format(settings['chancom'],settings['signal']))
			else:
				buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESSHELP')
		elif (hcmds[0].upper() == 'SAY'):
			if (loggedgetaccess(sock,user,chan,'CHANNEL') >= 1):
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(SAY)- This command will cause the bot to say a message on a channel")
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(SAY)- Command Structure: {0}{1} say <channel> <message>".format(settings['pvtcom'],settings['signal']))
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(SAY)- Command Structure: {0}{1} say <message>".format(settings['chancom'],settings['signal']))
			else:
				buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESSHELP')		
		elif (hcmds[0].upper() == 'ACT'):
			if (loggedgetaccess(sock,user,chan,'CHANNEL') >= 1):
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(ACT)- This command will cause the bot to do a action on a channel")
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(ACT)- Command Structure: {0}{1} act <channel> <action>".format(settings['pvtcom'],settings['signal']))
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(ACT)- Command Structure: {0}{1} act <action>".format(settings['chancom'],settings['signal']))
			else:
				buildmsg(sock,'ERROR',user,chan,'PRIV','NOACCESSHELP')
		elif (hcmds[0].upper() == 'ACCOUNT'):
			if (islogged(sock,user) == 'TRUE'):
				if (len(hcmds) >= 2):
					if (hcmds[1].upper() == 'CHGPASS'):
						buildmsg(sock,'HELP',user,chan,'PRIV',"-(ACCOUNT)-(CHGPASS)- This Command will allow you to change your password")
						buildmsg(sock,'HELP',user,chan,'PRIV',"-(ACCOUNT)-(CHGPASS)- Command Structure: {0}{1} account chgpass <old pass> <new pass>".format(settings['pvtcom'],settings['signal']))
					elif (hcmds[1].upper() == 'MSGTYPE'):
						buildmsg(sock,'HELP',user,chan,'PRIV',"-(ACCOUNT)-(MSGTYPE)- This Command will allow you to change your Message Type")
						buildmsg(sock,'HELP',user,chan,'PRIV',"-(ACCOUNT)-(MSGTYPE)- Command Structure: {0}{1} account msgtype <notice/msg>".format(settings['pvtcom'],settings['signal']))
					else:
						buildmsg(sock,'ERROR',user,chan,'PRIV',"-(ACCOUNT)- The help topic account {0} is not in the database".format(hcmds[1]))
				else:
					buildmsg(sock,'HELP',user,chan,'PRIV',"-(ACCOUNT)- This Command will allow the user to do some modifications to their account")
					buildmsg(sock,'HELP',user,chan,'PRIV',"-(ACCOUNT)- Command Structure: {0}{1} account <chgpass/msgtype>".format(settings['pvtcom'],settings['signal']))
					buildmsg(sock,'HELP',user,chan,'PRIV',"-(ACCOUNT)- Topics available: chgpass msgtype")
			else:
				buildmsg(sock,'ERROR',user,chan,'PRIV','NOTLOGGED')
		elif (hcmds[0].upper() == 'LOGOUT'):
			if (islogged(sock,user) == 'TRUE'):
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(LOGOUT)- This Command will logout from the bot, this is the only command that works with users that is allowed in channel")
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(LOGOUT)- Command Structure: {0}{1} logout".format(settings['pvtcom'],settings['signal']))
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(LOGOUT)- Command Structure: {0}{1} logout".format(settings['pvtcom'],settings['signal']))
			else:
				buildmsg(sock,'ERROR',user,chan,'PRIV','NOTLOGGED')
		elif (hcmds[0].upper() == 'LOGIN'):
			if (islogged(sock,user) == 'FALSE'):
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(LOGIN)- This Command will login to the bot, should the username and password be right")
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(LOGIN)- Command Structure: {0}{1} login <username> <password>".format(settings['pvtcom'],settings['signal']))
			else:
				buildmsg(sock,'ERROR',user,chan,'PRIV','LOGGED')
		elif (hcmds[0].upper() == 'REGISTER'):
			if (islogged(sock,user) == 'FALSE'):
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(REGISTER)- This Command will register a user to the bot if that username doesn't already exists")
				buildmsg(sock,'HELP',user,chan,'PRIV',"-(REGISTER)- Command Structure: {0}{1} register <username> <password>".format(settings['pvtcom'],settings['signal']))
			else:
				buildmsg(sock,'ERROR',user,chan,'PRIV','LOGGED')		
		elif (hcmds[0].upper() == 'HELP'):
			buildmsg(sock,'HELP',user,chan,'PRIV',"-(HELP)- This Command Displays The Help System and Certain Command information")
			buildmsg(sock,'HELP',user,chan,'PRIV',"-(HELP)- Command Structure: {0}{1} help <channel> <topic>".format(settings['pvtcom'],settings['signal']))
			#buildmsg(sock,'HELP',user,chan,'PRIV',"-(HELP)- Command Structure: {0}{1} help <channel> <topic>".format(settings['dcccom'],settings['signal']))
			buildmsg(sock,'HELP',user,chan,'PRIV',"-(HELP)- Command Structure: {0}{1} help <topic>".format(settings['chancom'],settings['signal']))
		elif (hcmds[0].upper() == 'WHOIS'):
			buildmsg(sock,'HELP',user,chan,'PRIV',"-(WHOIS)- This Command will send you a whois on the <nick> you choose")
			buildmsg(sock,'HELP',user,chan,'PRIV',"-(WHOIS)- Command Structure: {0}{1} whois <channel> <nick>".format(settings['pvtcom'],settings['signal']))
			#buildmsg(sock,'HELP',user,chan,'PRIV',"-(WHOIS)- Command Structure: {0}{1} whois <channel> <nick>".format(settings['dcccom'],settings['signal']))
			buildmsg(sock,'HELP',user,chan,'PRIV',"-(WHOIS)- Command Structure: {0}{1} whois <nick>".format(settings['chancom'],settings['signal']))
		elif (hcmds[0].upper() == 'WHOAMI'):
			buildmsg(sock,'HELP',user,chan,'PRIV',"-(WHOAMI)- This Command will send you a whois on your current logged in user account")
			buildmsg(sock,'HELP',user,chan,'PRIV',"-(WHOAMI)- Command Structure: {0}{1} whoami <channel>".format(settings['pvtcom'],settings['signal']))
			#buildmsg(sock,'HELP',user,chan,'PRIV',"-(WHOAMI)- Command Structure: {0}{1} whoami <channel>".format(settings['dcccom'],settings['signal']))
			buildmsg(sock,'HELP',user,chan,'PRIV',"-(WHOAMI)- Command Structure: {0}{1} whoami".format(settings['chancom'],settings['signal']))		
		else:
			buildmsg(sock,'ERROR',user,chan,'PRIV',"The help topic {0} is not in the database".format(hcmds[0]))
	else:
		buildmsg(sock,'HELP',user,chan,'PRIV',"The bot has the following Commands Available")
		if (islogged(sock,user) == 'TRUE'):
			if (loggedgetaccess(sock,user,chan,'GLOBAL') >= 7):
				buildmsg(sock,'HELP',user,chan,'PRIV',"Creator Level Access (7) Only (Due to dangerous level to bot and system):")
				buildmsg(sock,'HELP',user,chan,'PRIV',"Raw Rawdb")
			if (loggedgetaccess(sock,user,chan,'CHANNEL') >= 6):
				#Master & Creator Commands 6/7 Global, 6 Server, 6 Channel
				buildmsg(sock,'HELP',user,chan,'PRIV',"Master Level Access (6):")
				if (loggedgetaccess(sock,user,chan,'GLOBAL') >= 6):
					buildmsg(sock,'HELP',user,chan,'PRIV',"Exit Rehash Settings") #Server User
				if (loggedgetaccess(sock,user,chan,'SERVER') >= 6):
					buildmsg(sock,'HELP',user,chan,'PRIV',"Quit") #Channel
				if (loggedgetaccess(sock,user,chan,'CHANNEL') >= 6):
					blarg = 1 #don't think there is gonna be any of these
			if (loggedgetaccess(sock,user,chan,'CHANNEL') >= 5):
				#Owner Commands - 5 Global, 6 Server, 5 Channel
				buildmsg(sock,'HELP',user,chan,'PRIV',"Owner Level Access (5):")
				if (loggedgetaccess(sock,user,chan,'GLOBAL') >= 5):
					blarg = 1
				if (loggedgetaccess(sock,user,chan,'SERVER') >= 5):
					blarg = 1
				if (loggedgetaccess(sock,user,chan,'CHANNEL') >= 5):
					buildmsg(sock,'HELP',user,chan,'PRIV',"MOwner Owner MDeOwner DeOwner Ownerme DeOwnerme")
					buildmsg(sock,'HELP',user,chan,'PRIV',"MProtect Protect MDeProtect DeProtect")
			if (loggedgetaccess(sock,user,chan,'CHANNEL') >= 4):
				#Protected Commands - 4 Global, 4 Server, 4 Channel
				buildmsg(sock,'HELP',user,chan,'PRIV',"Protected Level Access (4):")
				if (loggedgetaccess(sock,user,chan,'GLOBAL') >= 4):
					blarg = 1
				if (loggedgetaccess(sock,user,chan,'SERVER') >= 4):
					blarg = 1
				if (loggedgetaccess(sock,user,chan,'CHANNEL') >= 4):
					buildmsg(sock,'HELP',user,chan,'PRIV',"Protectme DeProtectme")
					#Access Protectme DeProtectme
			if (loggedgetaccess(sock,user,chan,'CHANNEL') >= 3):
				#Op Commands - 3 Global, 3 Server, 3 Channel
				buildmsg(sock,'HELP',user,chan,'PRIV',"Op Level Access (3):")
				buildmsg(sock,'HELP',user,chan,'PRIV',"MOp Op MDeOp DeOp Opme DeOpme")
				buildmsg(sock,'HELP',user,chan,'PRIV',"MHalfop Halfop MDeHalfop DeHalfop")
			if (loggedgetaccess(sock,user,chan,'CHANNEL') >= 2):
				#Half-Op Commands - 2 Global, 2 Server, 2 Channel
				buildmsg(sock,'HELP',user,chan,'PRIV',"Half-Op Level Access (2):")
				buildmsg(sock,'HELP',user,chan,'PRIV',"Halfopme DeHalfopme MVoice Voice MDeVoice DeVoice")
				#channel Kick Ban
			if (loggedgetaccess(sock,user,chan,'CHANNEL') >= 1):
				#Voice Commands - 1 Global, 1 Server, 1 Channel
				buildmsg(sock,'HELP',user,chan,'PRIV',"Voice Level Access (1):")
				buildmsg(sock,'HELP',user,chan,'PRIV',"Voiceme DeVoiceme Say Act")		
			#Logged in with - 0 Global, 0 Server, 0 Channel
			buildmsg(sock,'HELP',user,chan,'PRIV',"Logged In Access (0):")
			buildmsg(sock,'HELP',user,chan,'PRIV',"Account Logout")
		else :
			#Logged out with - 0 Global, 0 Server, 0 Channel
			buildmsg(sock,'HELP',user,chan,'PRIV',"Logged out Access (0):")
			buildmsg(sock,'HELP',user,chan,'PRIV',"Login Register")
		#Anyone Commands - 0 global, 0 server, 0 channel
		buildmsg(sock,'HELP',user,chan,'PRIV',"Anyone Can Access (0):")
		buildmsg(sock,'HELP',user,chan,'PRIV',"Help Whoami Whois")
		buildmsg(sock,'HELP',user,chan,'PRIV',"Pvt Command: {0}{2} help <channel> <topic>, Channel Command: {1}{2} help <topic>".format(settings['pvtcom'],settings['chancom'],settings['signal']))
	buildmsg(sock,'HELP',user,chan,'PRIV',"End Of {0} help system".format(settings['botname']))
*/	
	}
	
	private function _core_parse_data($id,$data) {
		$this->_screen($id,'in',$data);
		$indata = explode(" ",$data);
		$rawdata = explode(" ",$data);
		$indata = str_replace(":","",$indata);
		$sender = explode("!",$indata[0]);
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
					$this->sdata['cons'][$id]['connection']['address'] = $indata[3];
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
					$this->sdata['cons'][$id]['connectaddress'] = $indata[3];
					$this->sdata['cons'][$id]['sversion'] = $indata[4];
					$this->sdata['cons'][$id]['connectumodes'] = $indata[5];
					$this->sdata['cons'][$id]['connectcmodes'] = $indata[6];
					//$this->_core_modeprocessor_user($id,'umode',$indata[5]);
					break;
				}
				case '005': {
					//$this->_sprint($id." Numeric 005 - map",'debug',false);
					/*			#debug(sock,"Numeric 005 - map")
					i = 0
					while (i < len(incom)):
						tmpdata = incom[i].split('=')
						#if (tmpdata[0] == 'UHNAMES'):
						if (tmpdata[0] == 'MAXCHANNELS'): mysockets[sock]['maxchannels'] = int(tmpdata[1])
						elif (tmpdata[0] == 'CHANLIMIT'): mysockets[sock]['chanlimit'] = tmpdata[1]
						elif (tmpdata[0] == 'MAXLIST'): mysockets[sock]['maxlist'] = tmpdata[1]
						elif (tmpdata[0] == 'NICKLEN'): mysockets[sock]['nicklen'] = int(tmpdata[1])
						elif (tmpdata[0] == 'CHANNELLEN'): mysockets[sock]['channellen'] = int(tmpdata[1])
						elif (tmpdata[0] == 'TOPICLEN'): mysockets[sock]['topiclen'] = int(tmpdata[1])
						elif (tmpdata[0] == 'KICKLEN'): mysockets[sock]['kicklen'] = int(tmpdata[1])
						elif (tmpdata[0] == 'AWAYLEN'): mysockets[sock]['awaylen'] = int(tmpdata[1])
						elif (tmpdata[0] == 'MAXTARGETS'): mysockets[sock]['maxtargets'] = int(tmpdata[1])
						elif (tmpdata[0] == 'MODES'): mysockets[sock]['modespl'] = int(tmpdata[1])
						elif (tmpdata[0] == 'CHANTYPES'): mysockets[sock]['chantypes'] = tmpdata[1]
						elif (tmpdata[0] == 'PREFIX'): mysockets[sock]['prefix'] = tmpdata[1]
						elif (tmpdata[0] == 'CHANMODES'): mysockets[sock]['chanmodes'] = tmpdata[1]
						elif (tmpdata[0] == 'EXTBAN'): mysockets[sock]['extban'] = tmpdata[1]
						else: blarg = 1
						i = i + 1*/
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
						//$this->_core_modeprocessor_user($id,'umode',$indata[3]);
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
						//$this->_core_modeprocessor_user($id,'oflags','+'.$indata[7]);
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
					//$this->_core_modeprocessor_chan($id,$sender,$indata[3],$indata[4]);
					//if (chanmodes($id,$indata[3])) != 'NULL') {
					//	$this->_core_sts($id,"MODE ".$indata[3]." ".chanmodes($id,$indata[3]));
					//}
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
					/*
						try: mysockets[sock]['channels'][rl(incom[4])]
						except: mysockets[sock]['channels'][rl(incom[4])] = dict()
						tmpdata = incom[5:]
						#debug(sock,tmpdata)
						i = 0
						while (i < len(tmpdata)):
							if (tmpdata[i] == ''): break
							try: mysockets[sock]['channels'][rl(incom[4])]['users']
							except:	mysockets[sock]['channels'][rl(incom[4])]['users'] = dict()
							if (tmpdata[i][0] == '~'):
								tmpuser = tmpdata[i][1:]
								tmpmode = 'FOP'
							elif (tmpdata[i][0] == '&'):
								tmpuser = tmpdata[i][1:]
								tmpmode = 'SOP'
							elif (tmpdata[i][0] == '@'):
								tmpuser = tmpdata[i][1:]
								tmpmode = 'OP'
							elif (tmpdata[i][0] == '%'):
								tmpuser = tmpdata[i][1:]
								tmpmode = 'HOP'
							elif (tmpdata[i][0] == '+'):
								tmpuser = tmpdata[i][1:]
								tmpmode = 'VOICE'
							else:
								tmpuser = tmpdata[i]
								tmpmode = 'REGULAR'
							try: mysockets[sock]['channels'][rl(incom[4])]['users'][tmpuser]
							except: mysockets[sock]['channels'][rl(incom[4])]['users'][tmpuser] = dict()
							mysockets[sock]['channels'][rl(incom[4])]['users'][tmpuser]['inchan'] = 'TRUE'
							mysockets[sock]['channels'][rl(incom[4])]['users'][tmpuser][tmpmode] = 'TRUE'
							i = i + 1
					*/
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
					unset($this->sdata['cons'][$id]['chans'][$indata[2]]['users'][$indata[3]);
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
						//$this->_core_modeprocessor_user($id,'umode',$indata[3]);
					} else {
						//$this->_core_modeprocessor_chan($id,$indata[2],$indata[3]);
						//if ($this->_core_chanmodes($id,$indata[2],$indata[3]) != 'NULL') {
						//	$this->_core_sts($id,"MODE ".$indata[2]." ".$this->_core_chanmodes($id,$indata[2]));
						//}
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
						$this->_core_command_cmds($id,'PNOTE',$sender,$indata,$rawdata);
					} else {
						$this->_core_command_cmds($id,'CNOTE',$sender,$indata,$rawdata);
					}
					break;
				}
				case 'PRIVMSG': {
					if ($indata[2] == $this->sdata['cons'][$id]['nick']) {
						$this->_core_command_cmds($id,'PMSG',$sender,$indata,$rawdata);
					} else {
						$this->_core_command_cmds($id,'CMSG',$sender,$indata,$rawdata);
					}
					break;
				}
				default: {
					$this->_sprint($id." ".print_r($indata),'debug',false);
					$this->_sprint($id." Unknown feature at this momment",'debug',false);
					break;
				}
			}
		} else {
			$this->_sprint("Unknown Length of data",'debug',false);
		}
		return;
/*
		
def splitjoiner(data):
	outcounti = 0
	output = ''
	while (outcounti != len(data)):
		output = output+data[outcounti]+" "
		outcounti = outcounti + 1
	output = output.rstrip()
	return output
		
def buildmsg(sock,type,user,chan,uctype,message):
	#sock = server($1) type = messagetype($4) uctype = priv/chan($2) user/chan = sendto($3) message = message($5-)
	if (uctype == 'PRIV'): 
		sendto = user
		if (islogged(sock,user) == 'TRUE'):
			userdata = pulluser(loggedin[sock][user]['username'])
			msgtype = userdata['msgtype']
		else: msgtype = "msg"
	else: 
		sendto = chan
		msgtype = "msg"
	if (msgtype == "msg"): msgoutput = "PRIVMSG"
	if (msgtype == "notice"): msgoutput = "NOTICE"
	if (type == 'RAW'): mtoutput = "-(RAW)-"
	elif (type == 'BLOG'): mtoutput = "-(CBOT)-(LOG)-"
	elif (type == 'ELOG'): mtoutput = "-(CBOT)-(ERROR-LOG)-"
	elif (type == 'RELAY'): mtoutput = "*"
	elif (type == 'NORMAL'): mtoutput = "-(CBOT)-"
	elif (type == 'HELP'): mtoutput = "-(CBOT)-(HELP)-"
	elif (type == 'ERROR'): 
		mtoutput = "-(CBOT)-(ERROR)-"
		if (message == 'LOGIN'): message = "You are already Logged in."
		elif (message == 'PASSPROB'): message = "There was a problem with changing your password"
		elif (message == 'LOGGED'):	message = "You are Logged in."
		elif (message == 'NOTLOGGED'): message = "You are not Logged in."
		elif (message == 'NOACCESS'): message = "You either have no access to this command or you are not Logged in."
		elif (message == 'NOACCESSHELP'): message = "You do not have access to read help on this command."
		else: message = message
	else: mtoutput = "-(CBOT)-"
	sts(sock,"{0} {1} :{2}4,1{3}{2} {4}".format(msgoutput,sendto,chr(3),mtoutput,message))			

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
def massmodes(sock,user,chan,modes):
	modespl = mysockets[sock]['modespl']
	tmpusers = deque()
	if (len(mysockets[sock]['channels'][chan]['users']) > 0):
		tmplogged = 'FALSE'
		tmpkeys = mysockets[sock]['channels'][chan]['users'].keys()
		for tmpkey in tmpkeys:
			if (modes[2] == 'ALL'):
				tmpusers.append(tmpkey)
			elif (modes[2] == 'BC'):
				if (modes[0] == 'ADD'):	tmpusers.append(tmpkey)
				else:
					if (tmpkey == user): tmplogged = 'TRUE'
					elif (tmpkey == mysockets[sock]['nick']): tmplogged = 'TRUE'
					else: tmpusers.append(tmpkey)
			else:
				for tmpmode in modes[2]:
					if (tmpkey == tmpmode): tmpusers.append(tmpkey)
			
	i = 0
	outputmode = ''
	while (i != modespl):
		outputmode = outputmode+modes[1]
		i = i + 1
	outputmode = outputmode.rstrip()
	if (modes[0] == 'ADD'): omode = '+'
	else: omode = '-'
	i = l = 0
	output = ''
	length = len(tmpusers)
	while (i != length):
		output = output+tmpusers.popleft()+" "
		l = l + 1
		if (l == modespl):
			output = output.rstrip()
			sts(sock,"MODE {0} {1}{2} {3}".format(chan,omode,outputmode,output))
			output = ''
			l = 0
		i = i + 1
	output = output.rstrip()
	sts(sock,"MODE {0} {1}{2} {3}".format(chan,omode,outputmode,output))		
		
def modeprocessor_chan(sock,user,chan,data):
	try: mysockets[sock]['channels'][chan]['modes']
	except: mysockets[sock]['channels'][chan]['modes'] = dict()
	i = 0
	pos = 1
	mode = 'SUB'
	while (i < len(data[0])):
		if ((data[0][i] == '+') or (data[0][i] == '-') or (data[0][i] == '(') or (data[0][i] == ')')):
			if (data[0][i] == '+'): mode = 'ADD'
			else: mode = 'SUB'
		else:
			if (data[0][i] == 'q'): tmpmode = 'FOP'
			elif (data[0][i] == 'a'): tmpmode = 'SOP'
			elif (data[0][i] == 'o'): tmpmode = 'OP'
			elif (data[0][i] == 'h'): tmpmode = 'HOP'
			elif (data[0][i] == 'v'): tmpmode = 'VOICE'
			elif (data[0][i] == 'e'): tmpmode = 'EXCEPT'
			elif (data[0][i] == 'I'): tmpmode = 'INVEX'
			elif (data[0][i] == 'b'): tmpmode = 'BAN'
			elif (data[0][i] == 'l'): tmpmode = 'LIMIT'
			elif (data[0][i] == 'k'): tmpmode = 'CHANPASS'
			elif (data[0][i] == 'f'): tmpmode = 'FLOOD'
			elif (data[0][i] == 'j'): tmpmode = 'JOIN'
			elif (data[0][i] == 'L'): tmpmode = 'LINK'
			elif (data[0][i] == 'B'): tmpmode = 'BANLINK'	
			else: tmpmode = data[0][i]
			if (mode == 'ADD'):
				if ((tmpmode == 'FOP') or (tmpmode == 'SOP') or (tmpmode == 'OP') or (tmpmode == 'HOP') or (tmpmode == 'VOICE') or (tmpmode == 'EXCEPT') or (tmpmode == 'INVEX') or (tmpmode == 'BAN') or (tmpmode == 'LIMIT') or (tmpmode == 'LINK') or (tmpmode == 'BANLINK') or (tmpmode == 'CHANPASS') or (tmpmode == 'FLOOD') or (tmpmode == 'JOIN')):
					if ((tmpmode == 'FOP') or (tmpmode == 'SOP') or (tmpmode == 'OP') or (tmpmode == 'HOP') or (tmpmode == 'VOICE')):
						try: mysockets[sock]['channels'][chan]['users'][data[pos]]
						except:	mysockets[sock]['channels'][chan]['users'][data[pos]] = dict()
						mysockets[sock]['channels'][chan]['users'][data[pos]][tmpmode] = 'TRUE'
					else:
						try: mysockets[sock]['channels'][chan][tmpmode]
						except:	mysockets[sock]['channels'][chan][tmpmode] = dict()
						if ((tmpmode == 'BAN') or (tmpmode == 'EXCEPT') or (tmpmode == 'INVEX')): mysockets[sock]['channels'][chan][tmpmode][data[pos]] = 'TRUE'
						else: mysockets[sock]['channels'][chan][tmpmode] = data[pos]
					pos = pos + 1
				else:
					try: mysockets[sock]['channels'][chan]['modes']
					except:	mysockets[sock]['channels'][chan]['modes'] = dict()
					mysockets[sock]['channels'][chan]['modes'][tmpmode] = 'TRUE'
			if (mode == 'SUB'):
				if ((tmpmode == 'FOP') or (tmpmode == 'SOP') or (tmpmode == 'OP') or (tmpmode == 'HOP') or (tmpmode == 'VOICE') or (tmpmode == 'EXCEPT') or (tmpmode == 'INVEX') or (tmpmode == 'BAN') or (tmpmode == 'LIMIT') or (tmpmode == 'LINK') or (tmpmode == 'BANLINK') or (tmpmode == 'CHANPASS') or (tmpmode == 'FLOOD') or (tmpmode == 'JOIN')):
					if ((tmpmode == 'FOP') or (tmpmode == 'SOP') or (tmpmode == 'OP') or (tmpmode == 'HOP') or (tmpmode == 'VOICE')):
						try: mysockets[sock]['channels'][chan]['users'][data[pos]]
						except:	mysockets[sock]['channels'][chan]['users'][data[pos]] = dict()
						mysockets[sock]['channels'][chan]['users'][data[pos]][tmpmode] = 'FALSE'
					else:
						try: mysockets[sock]['channels'][chan][tmpmode]
						except:	mysockets[sock]['channels'][chan][tmpmode] = dict()
						if ((tmpmode == 'BAN') or (tmpmode == 'EXCEPT') or (tmpmode == 'INVEX')): del mysockets[sock]['channels'][chan][tmpmode][data[pos]] 
						else: del mysockets[sock]['channels'][chan][tmpmode]
					pos = pos + 1
				else:
					try: mysockets[sock]['channels'][chan]['modes']
					except: mysockets[sock]['channels'][chan]['modes'] = dict()
					mysockets[sock]['channels'][chan]['modes'][tmpmode] = 'FALSE'
		i = i + 1	
		
def modeprocessor_user(sock,type,data):
	i = 0
	mode = 'SUB'
	while (i < len(data)):
		#debug(sock,incom[3][i])
		if ((data[i] == '+') or (data[i] == '-') or (data[i] == '(') or (data[i] == ')')):
			if (data[i] == '+'): mode = 'ADD'
			else: mode = 'SUB'
		else:
			if (mode == 'ADD'):
				try: mysockets[sock][type]
				except:	mysockets[sock][type] = dict()
				mysockets[sock][type][data[i]] = 'TRUE'
			if (mode == 'SUB'):
				try: mysockets[sock][type]
				except:	mysockets[sock][type] = dict()
				mysockets[sock][type][data[i]] = 'FALSE'
		i = i + 1		

def getwhois(sock,user,chan,mode,otheruser):
	if (otheruser == 'NULL'):
		if (islogged(sock,user) == 'TRUE'): userdata = pulluser(loggedin[sock][user]['username'])
		else: userdata = pulluser(user)
		tmpuinfo = user
	else:
		if (islogged(sock,otheruser) == 'TRUE'): userdata = pulluser(loggedin[sock][otheruser]['username'])
		else: userdata = pulluser(otheruser)
		tmpuinfo = otheruser
	if (userdata == 'FALSE'): 
		tmpusername = "GUEST"
		tmpglobaccess = 0
		tmpservaccess = 0
		tmpchanaccess = 0
		tmpmsgtype = "msg"
	else:
		tmpusername = userdata['username']
		tmpglobaccess = getglobaccess(userdata)
		tmpservaccess = getservaccess(sock,userdata)
		tmpchanaccess = getchanaccess(sock,chan,userdata)
		tmpmsgtype = userdata['msgtype']
	if (mode == 'WHOIS'):
		buildmsg(sock,'NORMAL',user,chan,'PRIV',"Your Bot Whois on {0}".format(tmpuinfo))
		buildmsg(sock,'NORMAL',user,chan,'PRIV',"Nick(Username): {0} ({1})".format(tmpuinfo,tmpusername))
		buildmsg(sock,'NORMAL',user,chan,'PRIV',"Global: {0} Server: {1} Channel: {2}".format(wordaccess(tmpglobaccess),wordaccess(tmpservaccess),wordaccess(tmpchanaccess)))
		buildmsg(sock,'NORMAL',user,chan,'PRIV',"End of Your Bot Whois on {0}".format(tmpuinfo))
	if (mode == 'WHOAMI'):
		buildmsg(sock,'NORMAL',user,chan,'PRIV',"Your Bot Whois")
		buildmsg(sock,'NORMAL',user,chan,'PRIV',"Nick(Username): {0} ({1})".format(tmpuinfo,tmpusername))
		buildmsg(sock,'NORMAL',user,chan,'PRIV',"Your Global Access: {0}".format(wordaccess(tmpglobaccess)))
		buildmsg(sock,'NORMAL',user,chan,'PRIV',"Your Server Access: {0}".format(wordaccess(tmpservaccess)))
		buildmsg(sock,'NORMAL',user,chan,'PRIV',"Your Channel Access: {0}".format(wordaccess(tmpchanaccess)))
		buildmsg(sock,'NORMAL',user,chan,'PRIV',"Your Current MsgType: {0}".format(tmpmsgtype))
		buildmsg(sock,'NORMAL',user,chan,'PRIV',"End of Your Bot Whois")	



*/		
	
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
		
		
		
		} else {
			$return = 0;
		}
		return $return;
	/*
	def getchanaccess(sock,chan,data):
	if (data['channel'] != 'NULL'):
		tmpdata = data['channel']
		tmpdata = tmpdata.split(chr(37))
		for tmpdata2 in tmpdata:
			tmpdata2 = tmpdata2.split(chr(124))
			if (tmpdata2[0] == mysockets[sock]['server']['servername']):
				tmpdata3 = tmpdata2[1:]
				for tmpdata4 in tmpdata3:
					tmpdata4 = tmpdata4.split(chr(126))
					if (tmpdata4[0] == chan): tmpchanaccess = tmpdata4[1]
	*/
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
		$tempsql = "SELECT * FROM users WHERE username = '".$user."'";
		$tempuser = $this->sql->sql('select',$tempsql);
		if (count($tempuser) == 0) {
			$return = false;
		} else {
			foreach ($tempuser as $t1 => $t2) {
				$return = {'id'=>$t2['id'],'username'=>$t2['username'],'password'=>$t2['password'],'global'=>$t2['global'],'server'=>$t2['server'],'channel'=>$t2['channel'],'msgtype'=>$t2['msgtype']};
				$this->data['user'][$t2['username']] = {'id'=>$t2['id'],'username'=>$t2['username'],'password'=>$t2['password'],'global'=>$t2['global'],'server'=>$t2['server'],'channel'=>$t2['channel'],'msgtype'=>$t2['msgtype']};
			}
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
		socket_write($this->sdata['cons'][$id]['socket'],$data);
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
		$this->sdata['cons'][$id]['socket'] = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
		socket_connect($this->sdata['cons'][$id]['socket'],$this->sdata['cons'][$id]['connection']['address'],$this->sdata['cons'][$id]['connection']['port']);
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
					$tempdata = socket_read($this->sdata['cons'][$t2['id']]['socket'],10240);
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
			sleep(0.5);
			//$this->_sprint("after sleep",'debug',false);
		}
	}
	
	public function startup() {
		global $CORE;
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
		print_r($this->sdata);
	}
	
/*	public function _func($function,$val1=null,$val2=null,$val3=null,$val4=null,$val5=null,$val6=null) {
		switch ($function) {
			case 'logging':
				$return = $this->log->_sprint($val1,$val2,$val3);
				break;
			case 'sql':
				$return = $this->sql->sql($val1,$val2);
				break;
			default:
				$return = $this->_func('logging',"Unknown Function",'error',false);
				break;
		}
		return $return;
	}*/
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