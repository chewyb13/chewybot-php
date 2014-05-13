<?php
class log extends ChewyBot {

	public function log($message="Init of Logging system",$dtype=null,$log=false) {
		global $ch3wyb0t;
		return $this->_sprint($message,$dtype,$log);
	}
	
	protected function _screen($id,$type,$text) {
		global $ch3wyb0t;
		if ($type == 'in') {
			$output = "-".$id."-> ".$text;
		}
		if ($type == 'out') {
			$output = "<-".$id."- ".$text;
		}
		$this->_sprint($output,'regular',false);
	}

	protected function _sprint($message,$dtype=null,$log=false) {
		global $ch3wyb0t;
		$output = '';
		$htmloutput = '';
		global $CORE;
		$excapechr = "\033";
		/*if ($CORE['os'] == 'WINDOWS') {
			$excapechr = chr(27);
		} else {
			$excapechr = "\033";
		}*/
		switch ($dtype) {
			case 'error':
				$output .= $excapechr."[1;31m".$excapechr."[40mError: ";
				$htmloutput .= '<font color="#FF0000" bgcolor="#000000">Error: ';
				break;
			case 'alert':
				$output .= $excapechr."[1;33m".$excapechr."[40mAlert: ";
				$htmloutput .= '<font color="#FFFF00" bgcolor="#000000">Alert: ';
				break;
			case 'warning':
				$output .= $excapechr."[0;31m".$excapechr."[40mWarning: ";
				$htmloutput .= '<font color="#800000" bgcolor="#000000">Warning: ';
				break;
			case 'notice':
				$output .= $excapechr."[0;36m".$excapechr."[40mNotice: ";
				$htmloutput .= '<font color="#00FFFF" bgcolor="#000000">Notice: ';
				break;
			case 'debug':
				$output .= $excapechr."[0;32m".$excapechr."[40mDebug: ";
				$htmloutput .= '<font color="green" bgcolor="#000000">Debug: ';
				break;
			default:
				$output .= $excapechr."[1;37m".$excapechr."[40m";
				$htmloutput .= '<font color="#FFFFFF" bgcolor="#000000">';
				break;
		}		
		
		$output .= $message;
		$htmloutput .= $message;
		$output .= $excapechr."[0m";
		$htmloutput .= '</font>';
		echo $output."\n";
		if ($log == true) {
			$blarg = null;
		}	
	}
}
?>