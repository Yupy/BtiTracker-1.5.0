<?php
/*
* BtiTracker v1.5.0 is a php tracker system for BitTorrent, easy to setup and configure.
* This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
* Updated and Maintained by Yupy.
* Copyright (C) 2004-2014 Btiteam.org
*/

global $db;

if (!user::$current || user::$current["view_torrents"] == "no") {
    // do nothing
} else {
    global $SITENAME;
    
    block_begin(BLOCK_INFO);
    
    $cache_torrents        = CACHE_PATH . 'total_torrents.txt';
    $cache_torrents_expire = 5 * 60;
    
    if (file_exists($cache_torrents) && is_array(unserialize(file_get_contents($cache_torrents))) && (vars::$timestamp - filemtime($cache_torrents)) < $cache_torrents_expire) {
        $torrents = unserialize(@file_get_contents($cache_torrents));
    } else {
        $res = $db->query("SELECT COUNT(*) AS tot FROM namemap");
        if ($res) {
            $row      = $res->fetch_array(MYSQLI_BOTH);
            $torrents = (int)$row["tot"];
        }
	    else
            $torrents = 0;
		
		$handle = fopen($cache_torrents, "w+");
        fwrite($handle, serialize($torrents));
        fclose($handle);
    }
    
    $cache_users        = CACHE_PATH . 'total_users.txt';
    $cache_users_expire = 5 * 60;
    
    if (file_exists($cache_users) && is_array(unserialize(file_get_contents($cache_users))) && (vars::$timestamp - filemtime($cache_users)) < $cache_users_expire) {
        $users = unserialize(@file_get_contents($cache_users));
    } else {
        $res = $db->query("SELECT COUNT(*) AS tot FROM users WHERE id > 1");
        if ($res) {
           $row   = $res->fetch_array(MYSQLI_BOTH);
           $users = (int)$row["tot"];
        } else
            $users = 0;
		
		$handle = fopen($cache_users, "w+");
        fwrite($handle, serialize($users));
        fclose($handle);
    }
    
    $res = $db->query("SELECT SUM(seeds) AS seeds, SUM(leechers) AS leechs FROM summary");
    if ($res) {
        $row      = $res->fetch_array(MYSQLI_BOTH);
        $seeds    = 0 + (int)$row["seeds"];
        $leechers = 0 + (int)$row["leechs"];
    } else {
        $seeds    = 0;
        $leechers = 0;
    }
    
    if ($leechers > 0)
        $percent = number_format(($seeds / $leechers) * 100, 0);
    else
        $percent = number_format($seeds * 100, 0);
    
    $peers = $seeds + $leechers;
	
    $cache_traffic        = CACHE_PATH . 'total_traffic.txt';
    $cache_traffic_expire = 5 * 60;
    
    if (file_exists($cache_traffic) && is_array(unserialize(file_get_contents($cache_traffic))) && (vars::$timestamp - filemtime($cache_traffic)) < $cache_traffic_expire) {
        $row = unserialize(@file_get_contents($cache_traffic));
    } else {
        $res     = $db->query("SELECT SUM(downloaded) AS dled, SUM(uploaded) AS upld FROM users");
        $row     = $res->fetch_array(MYSQLI_BOTH);
	    
		$handle = fopen($cache_traffic, "w+");
        fwrite($handle, serialize($row));
        fclose($handle);
    }
	
    $dled    = 0 + (int)$row["dled"];
    $upld    = 0 + (int)$row["upld"];
    $traffic = misc::makesize($dled + $upld);
	
    
    print("<tr><td class='blocklist' align='center'>\n");
    print("<table width='100%' cellspacing='2' cellpading='2'>\n");
    print("<tr>\n<td colspan='2' align='center'><u>" . unesc($SITENAME) . "</u></td></tr>\n");
    print("<tr><td align='left'>" . MEMBERS . ":</td><td align='right'>" . $users . "</td></tr>\n");
    print("<tr><td align='left'>" . TORRENTS . ":</td><td align='right'>" . $torrents . "</td></tr>\n");
    print("<tr><td align='left'>" . SEEDERS . ":</td><td align='right'>" . $seeds . "</td></tr>\n");
    print("<tr><td align='left'>" . LEECHERS . ":</td><td align='right'>" . $leechers . "</td></tr>\n");
    print("<tr><td align='left'>" . PEERS . ":</td><td align='right'>" . $peers . "</td></tr>\n");
    print("<tr><td align='left'>" . SEEDERS . "/" . LEECHERS . ":</td><td align='right'>" . $percent . "%</td></tr>\n");
    print("<tr><td align='left'>" . TRAFFIC . ":</td><td align='right'>" . $traffic . "</td></tr>\n");
    print("</table>\n</td></tr>");
    block_end();

} // end if user can view

?>