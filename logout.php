<?php
/*
* BtiTracker v1.5.0 is a php tracker system for BitTorrent, easy to setup and configure.
* This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
* Updated and Maintained by Yupy.
* Copyright (C) 2004-2014 Btiteam.org
*/
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'include'.DIRECTORY_SEPARATOR.'functions.php');

$check_hash = (isset($_GET['check_hash']) && security::html_safe($_GET['check_hash']));
$salty = md5("R45eOMs15mNd3yV" . user::$current['username']);

if (empty($check_hash)) 
    die("No Hash, your up to no good...");

if ($check_hash != $salty) 
    die("Unsecure Logout, Hash mismatch please contact the Staff !");

logoutcookie();

header("Location: " . vars::$base_url . "/");

?>
