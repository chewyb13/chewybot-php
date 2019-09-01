#! C:\server\UniServerZ\core\php55\
<?php
/*
	Ch3wyB0t
	This bot is supposed to work as a mutli network irc bot
	There will be very few settings that you can set via editing the file
	As most if not all settings will be stored in the database
	You will need in your php.ini the following extensions
	extension=php_sqlite3.dll
	extension=php_sockets.dll
*/
// Debug Mode
define('BOT_DEBUG', true);
// PID file
define('BOT_PID', "./chewybot.pid");
// OS Type (windows/unix/linux/freebsd/unknown/auto)
define('OS', 'auto');

// You shouldn't need to edit anything below this point what so ever
require_once ("./config.def.php");

require_once ("./module/core/defines.inc.php");
// You really shouldn't need to edit anything below this point unless you are wanting to help with development
if (file_exists(BOT_PID)) {
	$pid = getmypid();
	$old = file_get_contents(BOT_PID);
	$fp = fopen(BOT_PID,'w');
	if (CORE_OS != 'windows') {
		if (exec('ps -p '.$old)) {
			exec('kill -9 '.$old);
			fwrite($fp,$pid);
		} else {
			fwrite($fp,$pid);
		}
	}
} else {
	$pid = getmypid();
	$fp = fopen(BOT_PID,'w');
	fwrite($fp,$pid);
}
if (BOT_DEBUG == true) { error_reporting(E_ALL); }
// | E_STRICT
require_once ('core.inc.php');
$ch3wyb0t = new ChewyBot();
$ch3wyb0t->initsetup();
$ch3wyb0t->startup();
/*$irc = new Vhost();
$irc->check();
$irc->connect();
function restart() {
	global $irc;
	$irc->reboot("Rebooting");
	$dead = exec('chewybot.php &>/dev/null &');
	exit;
	}
*/

?>