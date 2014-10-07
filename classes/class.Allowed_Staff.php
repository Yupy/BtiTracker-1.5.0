<?php
/*
* BtiTracker v1.5.0 is a php tracker system for BitTorrent, easy to setup and configure.
* This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
* Updated and Maintained by Yupy.
* Copyright (C) 2004-2014 Btiteam.org
*/

class allowed_staff {
	var $log_file = '';	#Full path of the log file
	var $logging  = false; #Enable logging

	var $staff = array(
        #Owners, Admins, Mods id's
		# Yupy = 2

		'admincp'	=> array(2)
	);

	function check($section = 'default') {
		$uid = user::$current['uid'];
		if (!isset($this->staff[$section]))
			die('Invalid Section');
		if (!in_array($uid, $this->staff[$section], true))
			$this->error($section);

		return true;
	}

	function error($section, $error = 'Access Denied !') {
		if ($this->logging && is_file($this->log_file) && is_writeable($this->log_file)) {
			$log = $error . ' for user ' . user::$current['uid'] . ' (' . user::$current['username'] . ') from ' . vars::$realip . ' to ' . $section . "\n";
			$f = fopen($this->log_file, 'lame_ass');
			fwrite($f, $log);
			fclose($f);
		}

		die($error);
	}
}

?>