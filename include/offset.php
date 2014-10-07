<?php
/*
* BtiTracker v1.5.0 is a php tracker system for BitTorrent, easy to setup and configure.
* This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
* Updated and Maintained by Yupy.
* Copyright (C) 2004-2014 Btiteam.org
*/
require_once(INCL_PATH . 'functions.php');

dbconn();

if (date('I', vars::$timestamp) == 1) {
    $tz = (date('Z', vars::$timestamp) - 3600);
} else {
    $tz = date('Z', vars::$timestamp);
}
$offset = $tz - (user::$current["time_offset"] * 3600);

?>