<?php
/*
* BtiTracker v1.5.0 is a php tracker system for BitTorrent, easy to setup and configure.
* This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
* Updated and Maintained by Yupy.
* Copyright (C) 2004-2014 Btiteam.org
*/
$BASEPATH = dirname(__FILE__);

require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'include'.DIRECTORY_SEPARATOR.'defines.php');
require($BASEPATH . "/include/config.php");
require($BASEPATH . "/include/common.php");
# protection against sql injection, xss attack
require_once($BASEPATH . "/include/crk_protection.php");

// controll if client can handle gzip
if ($GZIP_ENABLED)
{
    if (stristr($_SERVER["HTTP_ACCEPT_ENCODING"], "gzip") && extension_loaded('zlib') && ini_get("zlib.output_compression") == 0)
    {
        if (ini_get('output_handler') != 'ob_gzhandler')
        {
           ob_start("ob_gzhandler");
        } else {
            ob_start();
        }
    } else {
        ob_start();
    }
}
// end gzip controll

error_reporting(0);

// connect to db
if ($GLOBALS["persist"])
    $db = new mysqli($dbhost, $dbuser, $dbpass, $database);
else
    $db = new mysqli($dbhost, $dbuser, $dbpass, $database);
/*
* This is the "official" OO way to do it,
* BUT $connect_error was broken until PHP 5.2.9 and 5.3.0.
*/
if ($db->connect_error)
{
	die('Connect Error (' . $db->connect_errno . ') ' . $db->connect_error);
}

if (isset($_GET["pid"]))
    $pid = $db->real_escape_string($_GET["pid"]);
else
    $pid = '';

if (strpos($pid, "?"))
{
    $tmp = substr($pid, strpos($pid, "?"));
    $pid = substr($pid, 0, strpos($pid, "?"));
    $tmpname = substr($tmp, 1, strpos($tmp, "=") - 1);
    $tmpvalue = substr($tmp, strpos($tmp, "=") + 1);
    $_GET[$tmpname] = $tmpvalue;
}

$usehash = false;

$pid = AddSlashes($pid);

// if private announce turned on and PID empty string or not send by client
if (($pid == "" || !$pid) && $PRIVATE_SCRAPE)
    show_error("Sorry. Private scrape is ON and PID system is required");


if (isset($_GET["info_hash"]))
{
    if ($pid != "")
        $qryStr = substr($_SERVER["QUERY_STRING"], strlen("?pid=" . $pid . ""));
    else
        $qryStr = $_SERVER["QUERY_STRING"];

    // support for multi-scrape
    // more info @ http://wiki.depthstrike.com/index.php/P2P:Programming:Trackers:PHP:Multiscrape
    foreach (explode("&", $qryStr) as $item)
    {
        if (substr($item, 0, 10) == "info_hash=")
        {
            $ihash = urldecode(substr($item, 10));

            if (strlen($ihash) == 20)
                $ihash = bin2hex($ihash);
            else if (strlen($ihash) == 40)
                if (!verifyHash($ihash))
				    continue;
                else
                    continue;

            $newmatches[]=$ihash;
        }
    }

    if (get_magic_quotes_gpc())
        $info_hash = stripslashes(join($newmatches, "','"));
    else
        $info_hash = join($newmatches, "','");

    $info_hash = strtolower("('" . $info_hash . "')");
    $usehash = true;
}

if ($usehash)
    $query = $db->query("SELECT info_hash, filename FROM namemap WHERE external = 'no' AND info_hash IN " . $info_hash);
else
    $query = $db->query("SELECT info_hash, filename FROM namemap WHERE external = 'no'");

$namemap = array();
while ($row = $query->fetch_row())
    $namemap[$row[0]] = $row[1];

if ($usehash)
    $query = $db->query("SELECT summary.info_hash, summary.seeds, summary.leechers, summary.finished FROM summary LEFT JOIN namemap ON namemap.info_hash = summary.info_hash WHERE namemap.external = 'no' AND summary.info_hash IN " . $info_hash) or show_error("Database error. Cannot complete request.");
else
    $query = $db->query("SELECT summary.info_hash, summary.seeds, summary.leechers, summary.finished FROM summary LEFT JOIN namemap ON namemap.info_hash = summary.info_hash WHERE namemap.external = 'no' ORDER BY summary.info_hash") or show_error("Database error. Cannot complete request.");


$result = "d5:filesd";

while ($row = $query->fetch_row())
{
    $hash = hex2bin($row[0]);
    $result .= "20:" . $hash . "d";
    $result .= "8:completei" . $row[1] . "e";
    $result .= "10:downloadedi" . $row[3] . "e";
    $result .= "10:incompletei" . $row[2] . "e";

    if (isset($namemap[$row[0]]))
        $result .= "4:name" . strlen($namemap[$row[0]]) . ":" . $namemap[$row[0]];

    $result .= "e";
}

$result .= "ee";

echo $result;

$db->close();

?>
