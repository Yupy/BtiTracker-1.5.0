<?php
/*
* BtiTracker v1.5.0 is a php tracker system for BitTorrent, easy to setup and configure.
* This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
* Updated and Maintained by Yupy.
* Copyright (C) 2004-2014 Btiteam.org
*/
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'include'.DIRECTORY_SEPARATOR.'functions.php');

dbconn();

if (!isset(user::$current)) {
    die();
}

$url = '';

while (list($var, $val) = each($_GET)) {
    $url .= "&" . $var . "=" . $val;
}

if (preg_match("/([<>'\"]|&#039|&#33;|&#34|%27|%22|%3E|%3C|&#x27|&#x22|&#x3E|&#x3C|\.js)/i", $url)) {
    header("Location: http://www.google.ro");
}

$i = strpos($url, "&url=");

if ($i !== false) {
    $url = substr($url, $i + 5);
}

if (substr($url, 0, 4) == "www.") {
    $url = "http://" . $url;
}

if (strlen($url) < 10) {
    die();
}

echo("<html><head><meta http-equiv='refresh' content='3;url=" . $url . "'></head><body>\n");
echo("<div style='width:100%;text-align:center;background: #E9D58F;border: 1px solid #CEAA49;margin: 5px 0 5px 0;padding: 0 5px 0 5px;font-weight: bold;'>Redirecting you to...<br />\n");
echo(security::html_safe($url) . "</div></body></html>\n");

?>
