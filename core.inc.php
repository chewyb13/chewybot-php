<?php
//You shouldn't have to ever touch this file, unless you want to help with production of the bot
class ChewyBot {
	//Variables for within the bot
	private $db = false;
	private $data = false;
	
	private function _dbConnect()
	{
		global $CORE;
		$this->db = new SQLite3($CORE['conf']['db']);
	}
	public function checkdb()
	{
		global $CORE;
		if (file_exists($CORE['conf']['db'])) {
			$this->_sprint("Database exists, continuing to load",false,false);
		} else {
			$this->_sprint("Database missing, gotta regenerate the database",true,false);
		}
	}
	private function _sql()
	{
		//empty temp while i build my checkdb function, add as needed
	}
	/*
	 * This function is used to forcibly unset all unnecessary variables
	 * (including php variables) that are not in the configuration.
	 * This prevents users from executing random variable code if the
	 * variable happens to exist.
	 * THIS CLEARS $_GET, $_POST, $_SERVER, $_ENV VARIABLES SO IF THEY
	 * ARE NEEDED DO NOT CALL THIS FUNCTION.
	 */
	public function check()
	{
/*		if ($GLOBAL)
		{
			foreach ($GLOBAL as $key => $glob)
			{
				if ($GLOBAL[$key] != 'CORE')
				{
					unset($GLOBAL[$key],${$glob});
				}
			}
		}*/
		if (ini_get('max_execution_time') > 0)
		{
			set_time_limit(0);
		}
	}

	public function startup()
	{
		global $_CONF;
		//connect to sql db
		if (!$this->db)
		{
			$this->_dbConnect();
		}
		var_dump($this->db);
		//$results = $this->db->query("SELECT id, setting, value FROM Settings");
		//while ($row = $results->fetchArray()) {
		//	var_dump($row);
		//}
		//actually connect to the server and send greet commands
/*		if ($_CONF['connect'] == 'fopen')
		{
			$this->irc = $this->fopen->connect($_CONF['servIP'], $_CONF['port']);
		}
		elseif ($_CONF['connect'] == 'socket')
		{
			$this->irc = $this->socket->connect($_CONF['servIP'], $_CONF['port']);
		}
		else
		{
			$this->_logging("Invalid connection type.  Must be fopen or socket");
			exit;
		}*/
	}
	private function _sprint($message,$error=false,$log=false)
	{
		$output = '';
		if ($error == true) {
			$output .= 'Error: ';
		}
		$output .= $message;
		echo $output."\n";
		if ($log == true) {
			$blarg = null;
		}	
	}




}
/*echo "Are you sure you want to do this?  Type 'yes' to continue: ";
$handle = fopen ("php://stdin","r");
$line = fgets($handle);
if(trim($line) != 'yes'){
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