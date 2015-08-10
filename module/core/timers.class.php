<?php
class timers extends ChewyBot {
	private $id;
	private $timers = array();
	public function __construct() {
		global $ch3wyb0t;
		$this->time = time();
	}
	public function init($identId) {
		global $ch3wyb0t;
		$this->id = $identId;
	}
	public function addTimer() {
		global $ch3wyb0t;
	
	
	}
	public function delTimer() {
		global $ch3wyb0t;
	
	}
	public function remTimers() {
		global $ch3wyb0t;
	
	}
	public function checkTimers() {
		global $ch3wyb0t;
		if (count($this->timers) != 0) {
			return true;
			$this->timers = time();
		
		}
		else {
			return false;
		}	
	}
	
}
?>