<?php
/*
 * BtiTracker v1.5.0 is a php tracker system for BitTorrent, easy to setup and configure.
 * This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
 * Updated and Maintained by Yupy.
 * Copyright (C) 2004-2014 Btiteam.org
 */
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'functions.php');

if (isset($_GET["style"]))
    $style = intval($_GET["style"]);
else
    $style = 1;

if (isset($_GET["returnto"]))
    $url = security::html_safe(urldecode($_GET["returnto"]));
else
    $url = "index.php";

if (isset($_GET["langue"]))
    $langue = intval($_GET["langue"]);
else
    $langue = 1;

dbconn();

// guest don't need to change language!
if (!user::$current || user::$current["uid"] == 1) {
    redirect($url);
    exit;
}

if (isset($_GET["style"]))
    @$db->query("UPDATE users SET style = " . $style . " WHERE id = " . user::$current["uid"]);

if (isset($_GET["langue"]))
    @$db->query("UPDATE users SET language = " . $langue . " WHERE id = " . user::$current["uid"]);

redirect($url);

?>