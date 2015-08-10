<?
class berror extends ChewyBot {

	public function berror($message="Init of Error Logging system",$dtype=null,$log=false) {
		global $ch3wyb0t;
		return $ch3wyb0t->_log->_sprint($message,$dtype,$log);
	}

}
?>