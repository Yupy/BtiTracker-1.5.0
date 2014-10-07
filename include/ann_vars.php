<?php
/*
* BtiTracker v1.5.0 is a php tracker system for BitTorrent, easy to setup and configure.
* This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
* Updated and Maintained by Yupy.
* Copyright (C) 2004-2014 Btiteam.org
*/

global $sp_compact, $pid;

$uploaded	= isset($_GET['uploaded']) ? 0 + (int)$_GET['uploaded'] : 0;
$downloaded	= isset($_GET['downloaded']) ? 0 + (int)$_GET['downloaded'] : 0;
$left = isset($_GET['left']) ? 0 + (int)$_GET['left'] : 0;
$iscompact = $sp_compact ? (bool)(0 + $_GET['compact'] == '1') : false;
$port = isset($_GET['port']) ? 0 + (int)$_GET['port'] : 0;
$sp_compact = isset($_GET['compact']) ? true : false;
$pid = AddSlashes($pid);
$agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';

?>