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
// You shouldn't need to edit anything below this point what so ever
$CORE['info']['botauthor'] = "chewyb_13 @ Servers irc.chewynet.co.uk:6667 & HellRisingSun.BounceMe.Net:7202";
$CORE['info']['helpchans'] = "#chewybot @ Servers irc.chewynet.co.uk:6667 & HellRisingSun.BounceMe.Net:7202";
$CORE['info']['botauthoremail'] = "chewyb13@gmail.com";
$CORE['info']['bugtracker'] = "http://code.google.com/p/chewybot-php/issues/list";
$CORE['info']['sourcecode'] = "https://chewybot-php.googlecode.com/svn/trunk/ chewybot-php-read-only";
$CORE['info']['version'] = "0.0.1.8";
$CORE['debug'] = true;
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