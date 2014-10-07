<?php
/*
* BtiTracker v1.5.0 is a php tracker system for BitTorrent, easy to setup and configure.
* This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
* Updated and Maintained by Yupy.
* Copyright (C) 2004-2014 Btiteam.org
*/
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'include'.DIRECTORY_SEPARATOR.'functions.php');
require_once(INCL_PATH . 'BDecode.php');
require_once(INCL_PATH . 'BEncode.php');

dbconn();

if (!user::$current || user::$current["can_download"] == "no") {
    standardheader('Download');
    err_msg(ERROR, NOT_AUTH_DOWNLOAD);
    die();
}

if (ini_get('zlib.output_compression'))
    ini_set('zlib.output_compression', 'Off');

$infohash = $db->real_escape_string($_GET["id"]);
$filepath = $TORRENTSDIR . "/" . $infohash . ".btf";

if (!is_file($filepath) || !is_readable($filepath)) {
    standardheader('Download');
    err_msg(ERROR, CANT_FIND_TORRENT);
    stdfoot();
    die();
}

$f = urldecode($_GET["f"]);

// pid code begin
$result = $db->query("SELECT pid FROM users WHERE id = " . user::$current['uid']);
$row = $result->fetch_assoc();
$pid = $db->real_escape_string($row["pid"]);

if (!$pid) {
    $pid = md5(user::$current['uid'] + user::$current['username'] + user::$current['password'] + user::$current['lastconnect']);
    @$db->query("UPDATE users SET pid = '" . $pid . "' WHERE id = '" . user::$current['uid'] . "'");
}

$result = $db->query("SELECT * FROM namemap WHERE info_hash = '" . $infohash . "'");
$row = $result->fetch_assoc();

if ($row["external"] == "yes" || !$PRIVATE_ANNOUNCE) {
    $fd = fopen($filepath, "rb");
    $alltorrent = fread($fd, filesize($filepath));
    fclose($fd);
    header("Content-Type: application/x-bittorrent");
    header('Content-Disposition: attachment; filename="' . AddSlashes($f) . '"');
    print($alltorrent);
} else {
    $fd = fopen($filepath, "rb");
    $alltorrent = fread($fd, filesize($filepath));
    
    //uTorrent v3.x.x fix
    $alltorrent = preg_replace("/file-mediali(.*?)ee(.*?):/i", "file-mediali0ee$2:", $alltorrent);
    $alltorrent = preg_replace("/file-durationli(.*?)ee(.*?):/i", "file-durationli0ee$2:", $alltorrent);
	
    $array = BDecode($alltorrent);
    fclose($fd);
    $array["announce"] = $BASEURL . "/announce.php?pid=" . $pid;
	
    if (isset($array["announce-list"]) && is_array($array["announce-list"])) {
        for ($i = 0; $i < count($array["announce-list"]); $i++) {
            if (in_array($array["announce-list"][$i][0], $TRACKER_ANNOUNCEURLS)) {
                if (strpos($array["announce-list"][$i][0], "announce.php") === false)
                    $array["announce-list"][$i][0] = trim(str_replace("/announce", "/" . $pid . "/announce", $array["announce-list"][$i][0]));
                else
                    $array["announce-list"][$i][0] = trim(str_replace("/announce.php", "/announce.php?pid=" . $pid . "", $array["announce-list"][$i][0]));
            }
        }
    }
    $alltorrent = BEncode($array);
    
    header("Content-Type: application/x-bittorrent");
    header('Content-Disposition: attachment; filename="' . AddSlashes($f) . '"');
    print($alltorrent);

}

?>