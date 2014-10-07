<?php
/*
 * BtiTracker v1.5.0 is a php tracker system for BitTorrent, easy to setup and configure.
 * This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
 * Updated and Maintained by Yupy.
 * Copyright (C) 2004-2014 Btiteam.org
 */

require_once(INCL_PATH . 'functions.php');
require_once(INCL_PATH . 'BDecode.php');

ignore_user_abort(1);

function escapeURL($info)
{
    $ret = "";
    $i   = 0;
    while (strlen($info) > $i) {
        $ret .= "%" . $info[$i] . $info[$i + 1];
        $i += 2;
    }
    return $ret;
}

function stristr_reverse($haystack, $needle)
{
    $pos = strrpos($haystack, $needle);
    return substr($haystack, 0, $pos);
}

function scrape($url, $infohash = "")
{
    global $db;
	
    if (isset($url)) {
        
        $u          = urldecode($url);
        $extannunce = str_replace("announce", "scrape", $u);
        
        $purl = parse_url($extannunce);
        $port = isset($purl["port"]) ? $purl["port"] : "80";
        $path = isset($purl["path"]) ? $purl["path"] : "/scrape.php";
        $an   = ($purl["scheme"] != "http" ? $purl["scheme"] . "://" : "") . $purl["host"];
        $fd   = @fsockopen($an, $port, $errno, $errstr, 60);
        if ($fd) {
            if ($infohash != "") {
                $ihash     = array();
                $ihash     = explode("','", $infohash);
                $info_hash = "";
                foreach ($ihash as $myihash)
                    $info_hash .= "&info_hash=" . escapeURL($myihash);
                $info_hash = substr($info_hash, 1);
                fputs($fd, "GET " . $path . "?" . $info_hash . " HTTP/1.0\r\nHost: somehost.net\r\n\r\n");
            } else
                fputs($fd, "GET " . $path . " HTTP/1.0\r\nHost: somehost.net\r\n\r\n");
            $stream = "";
            while (!feof($fd)) {
                $stream .= fgets($fd, 4096);
                if (strlen($stream) > 100000) {
                    $ret = $db->query("UPDATE namemap SET lastupdate = NOW() WHERE announce_url = '" . $url . "'" . ($infohash == "" ? "" : " AND namemap.info_hash IN ('" . $infohash . "')"));
                    write_log("FAILED update external torrent " . ($infohash == "" ? "" : "(infohash: " . $infohash . ")") . " from " . $url . " tracker (response too big)", "");
                    @fclose($fd);
                    return;
                }
            }
        } else {
            $ret = $db->query("UPDATE namemap SET lastupdate = NOW() WHERE announce_url = '" . $url . "'" . ($infohash == "" ? "" : " AND namemap.info_hash IN ('" . $infohash . "')"));
            write_log("FAILED update external torrent " . ($infohash == "" ? "" : "(infohash: " . $infohash . ")") . " from " . $url . " tracker (not connectable)", "");
            return;
        }
        @fclose($fd);
        
        
        $stream = utf8::trim(stristr($stream, "d5:files"));
        if (strpos($stream, "d5:files") === false) {
            // if host answer but stream is not valid encoded file try old metod
            // will work only with standard http
            $ihash     = array();
            $ihash     = explode("','", $infohash);
            $info_hash = "";
            foreach ($ihash as $myihash)
                $info_hash .= "&info_hash=" . escapeURL($myihash);
            $info_hash = substr($info_hash, 1);
            $fd        = fopen($extannunce . ($infohash != "" ? "?$info_hash" : ""), "rb");
            if ($fd) {
                while (!feof($fd)) {
                    $stream .= fread($fd, 4096);
                    if (strlen($stream) > 100000) {
                        $ret = $db->query("UPDATE namemap SET lastupdate = NOW() WHERE announce_url = '" . $url . "'" . ($infohash == "" ? "" : " AND namemap.info_hash IN ('" . $infohash . "')"));
                        write_log("FAILED update external torrent " . ($infohash == "" ? "" : "(infohash: " . $infohash . ")") . " from " . $url . " tracker (response too big)", "");
                        @fclose($fd);
                        return;
                    }
                }
            } else {
                $ret = $db->query("UPDATE namemap SET lastupdate = NOW() WHERE announce_url = '" . $url . "'" . ($infohash == "" ? "" : " AND namemap.info_hash IN ('" . $infohash . "')"));
                write_log("FAILED update external torrent " . ($infohash == "" ? "" : "(infohash: " . $infohash . ")") . " from " . $url . " tracker (not connectable)", "");
                return;
            }
        }
        
        $array = BDecode($stream);
        if (!isset($array)) {
            $ret = $db->query("UPDATE namemap SET lastupdate = NOW() WHERE announce_url = '" . $url . "'" . ($infohash == "" ? "" : " AND namemap.info_hash IN ('" . $infohash . "')"));
            write_log("FAILED update external torrent " . ($infohash == "" ? "" : "(infohash: " . $infohash . ")") . " from " . $url . " tracker (not bencode data)", "");
            return;
        }
        if ($array == false) {
            $ret = $db->query("UPDATE namemap SET lastupdate = NOW() WHERE announce_url = '" . $url . "'" . ($infohash == "" ? "" : " AND namemap.info_hash IN ('" . $infohash . "')"));
            write_log("FAILED update external torrent " . ($infohash == "" ? "" : "(infohash: " . $infohash . ")") . " from " . $url . " tracker (not bencode data)", "");
            return;
        }
        if (!isset($array["files"])) {
            $ret = $db->query("UPDATE namemap SET lastupdate = NOW() WHERE announce_url = '" . $url . "'" . ($infohash == "" ? "" : " AND namemap.info_hash IN ('" . $infohash . "')"));
            write_log("FAILED update external " . ($infohash == "" ? "" : "(infohash: " . $infohash . ")") . " torrent from " . $url . " tracker (not bencode data)", "");
            return;
        }
        $files = $array["files"];

        if (!is_array($files)) {
            $ret = $db->query("UPDATE namemap SET lastupdate = NOW() WHERE announce_url = '" . $url . "'" . ($infohash == "" ? "" : " AND namemap.info_hash IN ('" . $infohash . "')"));
            write_log("FAILED update external torrent " . ($infohash == "" ? "" : "(infohash: " . $infohash . ")") . " from " . $url . " tracker (probably deleted torrent(s))", "");
            return;
        }
        foreach ($files as $hash => $data) {
            $seeders  = (int)$data["complete"];
            $leechers = (int)$data["incomplete"];
			
            if (isset($data["downloaded"]))
                $completed = (int)$data["downloaded"];
            else
                $completed = "0";
			
            $torrenthash = bin2hex(stripslashes($hash));
			
            $ret         = $db->query("UPDATE namemap SET lastupdate = NOW(), lastsuccess = NOW() WHERE announce_url = '" . $url . "'" . ($hash == "" ? "" : " AND namemap.info_hash = '" . $torrenthash . "'"));
            $ret         = $db->query("UPDATE summary INNER JOIN namemap ON namemap.info_hash = summary.info_hash SET summary.seeds = " . $seeders . ", summary.leechers = " . $leechers . ", summary.finished = " . $completed . " WHERE summary.info_hash = '" . $torrenthash . "' AND namemap.announce_url = '" . $url . "'");
            if ($db->affected_rows == 1)
                write_log("SUCCESS update external torrent from " . $url . " tracker (infohash: " . $torrenthash . ")", "");
        }
    }
}

?>