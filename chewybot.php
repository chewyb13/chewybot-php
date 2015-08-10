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
$CORE['conf']['db'] = './database/chewydb.db';
$CORE['conf']['bindip'] = false;
$CORE['conf']['bindedip'] = '192.168.2.16';
$CORE['conf']['console_colorize'] = true;
$CORE['debug'] = true;
// You shouldn't need to edit anything below this point what so ever
$CORE['info']['botauthor'] = "chewyb_13 @ Server irc.exchat.net";
$CORE['info']['helpchans'] = "#chewybot @ Server irc.exchat.net";
$CORE['info']['botauthoremail'] = "chewyb13@gmail.com";
$CORE['info']['bugtracker'] = "https://github.com/chewyb13/chewybot-php/issues";
$CORE['info']['sourcecode'] = "https://github.com/chewyb13/chewybot-php.git";
$CORE['info']['longversion']['major'] = 0;
$CORE['info']['longversion']['minor'] = 0;
$CORE['info']['longversion']['revision'] = 1;
$CORE['info']['longversion']['build'] = 15;
$CORE['info']['version'] = '"'.$CORE['info']['longversion']['major'].'.'.$CORE['info']['longversion']['minor'].'.'.$CORE['info']['longversion']['revision'].'.'.$CORE['info']['longversion']['build'].'"';
//$CORE['info']['version'] = "0.0.1.15";

require ("./module/core/defines.inc.php");
// You really shouldn't need to edit anything below this point unless you are wanting to help with development
if (php_uname('s') === "Windows NT") {
	$CORE['OS'] = 'WINDOWS';
} else {
	$CORE['OS'] = php_uname('s');
}
if (file_exists('./chewybot.pid')) {
	$pid = getmypid();
	$old = file_get_contents('./chewybot.pid');
	$fp = fopen('./chewybot.pid','w');
	if ($CORE['OS'] != 'WINDOWS') {
		if (exec('ps -p '.$old)) {
			exec('kill -9 '.$old);
			fwrite($fp,$pid);
		} else {
			fwrite($fp,$pid);
		}
	}
} else {
	$pid = getmypid();
	$fp = fopen('./chewybot.pid','w');
	fwrite($fp,$pid);
}
if ($CORE['debug'] == true) { error_reporting(E_ALL); }
// | E_STRICT
require ('core.inc.php');
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