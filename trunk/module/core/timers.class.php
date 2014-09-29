<?php
class timers{
	private $id;
	private $timers = array();
	public function __construct() {
		$this->time = time();
	}
	public function init($identId) {
		$this->id = $identId;
	}
	public function addTimer() {
	
	
	}
	public function delTimer() {
	
	}
	public function remTimers() {
	
	}
	public function checkTimers() {
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