<?php
//Do not Change anything in here as this file may be overritten from github
//at any point in time.


$CORE['conf']['db'] = './database/chewydb.db';
$CORE['conf']['bindip'] = false;
$CORE['conf']['bindedip'] = '';
$CORE['conf']['console_colorize'] = false;

if (file_exists("./config.php")) {
	require_once ("./config.php");
}

?>