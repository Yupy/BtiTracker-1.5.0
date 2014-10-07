<?php
/*
* BtiTracker v1.5.0 is a php tracker system for BitTorrent, easy to setup and configure.
* This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
* Updated and Maintained by Yupy.
* Copyright (C) 2004-2014 Btiteam.org
*/

$clocktype = $GLOBALS["clocktype"];
require_once("addons/clock/clock.php");
block_begin("Clock",1,"center");
clock_display($clocktype);
block_end();

?>