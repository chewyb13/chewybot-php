<?php
class sql extends ChewyBot {
	public $db = false;
	
	public function sql($type,$sqlstmt) {
		global $ch3wyb0t;
		switch ($type) {
			case 'init':
				$return = false;
				$ch3wyb0t->_log->_sprint("Init of sql system",null,false);
				break;
			case 'database_check_structure':
				$return = $this->_db_structure();
				break;
			case 'database_build_database':
				$return = $this->_db_Builddatabase();
				break;
			case 'database_connect':
				$return = $this->_db_Connect();
				break;
			case 'select':
				$return = $ch3wyb0t->db->query($sqlstmt);
				break;
			case 'insert':
				//$stmt = $this->db->prepare($sqlstmt);
				//$stmt
				$return = $this->sql('execute',$sqlstmt);
				$return = $ch3wyb0t->db->lastInsertRowID();
				break;
			case 'update':
				//Gonna need coding to process the stmts, lol
				$return = $this->sql('execute',$sqlstmt);
				break;
			case 'delete':
				//why do I even have this here, lol, probably for other people if they want to edit the bot
				$return = $this->sql('execute',$sqlstmt);
				break;
			case 'execute':
				$return = $ch3wyb0t->db->exec($sqlstmt);
				break;
			default:
				$ch3wyb0t->_log->_sprint("Database command Error",'error',false);
				$return = false;
				break;
		}
		return $return;
	}


	private function _db_structure() {
		global $ch3wyb0t;
		/*if (!$this->db)
		{
			$this->_db_Connect();
		}*/
		//function to check the database structure to make sure it is properly built for the script to work properly
		//otherwise we could have a problem with the pre-built sql statements when they are called, gonna so have to
		//use a seperate table in the database to keep track of all insert and updates, not like I'm gonna be programming
		//any delete sql statements into the script for this bot.
		$ch3wyb0t->_log->_sprint("Database Structure Check complete, gonna check if database needs to update",'regular',false);
		$return = $this->_db_update();
		return $return;
	}
	
	private function _db_update() {
		global $ch3wyb0t;
		/*if (!$this->db)
		{
			$this->_db_Connect();
		}*/
		//function for when the database structure changes, if there is ever gonna be any that is!!!
		$ch3wyb0t->_log->_sprint("Database update check complete, Continuing to loading the bot system",'regular',false);
		$return = true;
		return $return;
	}
	
	private function _cmdpromptask($questiontoask,$checkifempty=false) {
		global $ch3wyb0t;
		$blarg = false;
		$value = 'NULL';
		while ($blarg == false) {
			echo $questiontoask." ";
			$handle = fopen("php://stdin","r");
			$line = fgets($handle);
			//echo "\n";
			$tline = trim($line);
			if (empty($tline)) {
				if ($checkifempty == false) {
					$value = 'NULL';
					$blarg = true;
				}
			} else {
				$value = $tline;
				$blarg = true;
			}
		}
		return $value;
	}
	
	private function _db_Builddatabase() {
		global $ch3wyb0t;
		/*if (!$this->db)
		{
			$this->_db_Connect();
		}*/
		//Long process of building the database from scratch which will take a good while to do as it's gonna make me go crazy with the stupid amount of lines it will need, gonna paste the rough structure here into the file so i can more easily convert it over
		$ch3wyb0t->_log->_sprint("Okay since we gotta build the database from scratch",'regular',false);
		$ch3wyb0t->_log->_sprint("I have to ask you a few questions to be able to",'regular',false);
		$ch3wyb0t->_log->_sprint("generate the database",'regular',false);
		$ch3wyb0t->_log->_sprint("Questions with a * can't be empty, otherwise",'regular',false);
		$ch3wyb0t->_log->_sprint("they can be a empty response as the field can be",'regular',false);
		$ch3wyb0t->_log->_sprint("a NULL value in the database",'regular',false);
		//prefill some default values for the temp array for settings
		$tempvals = ['settings' => ['botname' => 'NULL', 'chancom' => 'NULL', 'pvtcom' => 'NULL', 'dcccom' => 'NULL'], 'server' => ['name' => 'NULL', 'address' => 'NULL', 'port' => 'NULL', 'pass' => 'NULL', 'nick' => 'NULL', 'bnick' => 'NULL'], 'channel' => ['server' => 'NULL',	'channel' => 'NULL', 'chanmods' => '+nt'], 'user' => ['username' => 'NULL',	'password' => 'NULL']];
		$tempvals['settings']['botname'] = $this->_cmdpromptask("Enter The Bot's Name *:",true);
		$tempvals['settings']['chancom'] = $this->_cmdpromptask("Enter The Channel Command Trigger *:",true);
		$tempvals['settings']['pvtcom'] = $this->_cmdpromptask("Enter The Private Command Trigger *:",true);
		$tempvals['settings']['dcccom'] = $this->_cmdpromptask("Enter The Dcc Command Trigger *:",true);
		$tempvals['server']['name'] = $this->_cmdpromptask("Enter The Server Network Name *:",true);
		$tempvals['server']['address'] = $this->_cmdpromptask("Enter The Server Address *:",true);
		$tempvals['server']['port'] = $this->_cmdpromptask("Enter The Server Port *:",true);
		$tempvals['server']['pass'] = $this->_cmdpromptask("Enter The Server Password:",false);
		$tempvals['server']['nick'] = $this->_cmdpromptask("Enter The Bot's Main Nick for that Server *:",true);
		$tempvals['server']['bnick'] = $this->_cmdpromptask("Enter The Bot's Primary Backup Nick for that Server *:",true);
		$tempvals['server']['nickpass'] = $this->_cmdpromptask("Enter the Bot's Identification Password for that Server:",false);
		$tempvals['server']['botoper'] = $this->_cmdpromptask("Enter The Bot's Oper:",false);
		if ($tempvals['server']['botoper'] == 'NULL') {
			$tempvals['server']['botoperpass'] = 'NULL';
		} else {
			$tempvals['server']['botoperpass'] = $this->_cmdpromptask("Enter The Bot's Oper Password:",false);
			if ($tempvals['server']['botoperpass'] == 'NULL') {
				$tempvals['server']['botoper'] = 'NULL';
				$tempvals['server']['botoperpass'] = 'NULL';
			}
		}
		$tempvals['channel']['server'] = $tempvals['server']['name'];
		$tempvals['channel']['channel'] = $this->_cmdpromptask("Enter the main channel you want the bot to connect to *:",true);
		$tempvals['channel']['chanpass'] = $this->_cmdpromptask("Enter the channel password:",false);
		$tempvals['user']['username'] = $this->_cmdpromptask("Enter your username *:",true);
		$temppass = $this->_cmdpromptask("Enter your password *:",true);
		$tempvals['user']['password'] = md5($temppass);
		
		$this->sql('execute',"CREATE TABLE IF NOT EXISTS settings (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, setting TEXT, value TEXT)");
		$this->sql('execute',"CREATE TABLE IF NOT EXISTS servers (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, servername TEXT, address TEXT, serverport TEXT, serverpass TEXT, nick TEXT, bnick TEXT, nickservpass TEXT, botoper TEXT, botoperpass TEXT, enabled TEXT)");
		$this->sql('execute',"CREATE TABLE IF NOT EXISTS channels (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, server TEXT, channel TEXT, chanpass TEXT, chanmodes TEXT, options TEXT, enabled TEXT)");
		$this->sql('execute',"CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, username TEXT, password TEXT, global TEXT, server TEXT, channel TEXT, msgtype TEXT)");
		$this->sql('execute',"CREATE TABLE IF NOT EXISTS errors (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, nickname TEXT, server TEXT, channel TEXT, username TEXT, timedate TEXT, errortype TEXT, extra TEXT)");
		$this->sql('execute',"CREATE TABLE IF NOT EXISTS botlog (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, nickname TEXT, server TEXT, channel TEXT, username TEXT, timedate TEXT, command TEXT, extra TEXT)");
		$this->sql('execute',"CREATE TABLE IF NOT EXISTS seen (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, nick TEXT, type TEXT, timedate TEXT, extra TEXT)");
			
		$this->sql('insert',"INSERT INTO settings (setting, value) VALUES (botname, ".$tempvals['settings']['botname'].")");
		$this->sql('insert',"INSERT INTO settings (setting, value) VALUES (chancom, ".$tempvals['settings']['chancom'].")");
		$this->sql('insert',"INSERT INTO settings (setting, value) VALUES (pvtcom, ".$tempvals['settings']['pvtcom'].")");
		$this->sql('insert',"INSERT INTO settings (setting, value) VALUES (dcccom, ".$tempvals['settings']['dcccom'].")");
		$this->sql('insert',"INSERT INTO settings (setting, value) VALUES (kcount, 0)");
		$this->sql('insert',"INSERT INTO settings (setting, value) VALUES (bcount, 0)");
		$this->sql('insert',"INSERT INTO settings (setting, value) VALUES (msgqueue, 3)");
		$this->sql('insert',"INSERV INTO settings (setting, value) VALUES (msginterval, 1)");
		$this->sql('insert',"INSERT INTO server (servername, address, serverport, serverpass, nick, bnick, nickservpass, botoper, botoperpass, enabled) VALUES (".$tempvals['server']['name'].", ".$tempvals['server']['address'].", ".$tempvals['server']['port'].", ".$tempvals['server']['pass'].", ".$tempvals['server']['nick'].", ".$tempvals['server']['bnick'].", ".$tempvals['server']['nickservpass'].", ".$tempvals['server']['botoper'].", ".$tempvals['server']['botoperpass'].", enabled)");
		$this->sql('insert',"INSERT INTO channels (server, channel, chanpass, chanmodes, options, enabled) VALUES (".$tempvals['server']['name'].", ".$tempvals['channel']['channel'].", ".$tempvals['channel']['chanpass'].", ".$tempvals['channel']['chanmodes'].", NULL, enabled)");
		$this->sql('insert',"INSERT INTO users (username, password, global, server, channel, msgtype) VALUES (".$tempvals['user']['username'].", ".$tempvals['user']['password'].", 6, NULL, NULL, msg)");
		
		//var_dump($tempvals);
	}
	
	private function _db_Connect() {
		global $CORE;
		global $ch3wyb0t;
		$ch3wyb0t->db = new SQLite3($CORE['conf']['db']);
	}
}
?>