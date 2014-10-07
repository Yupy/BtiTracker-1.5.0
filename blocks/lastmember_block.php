<?php
/*
* BtiTracker v1.5.0 is a php tracker system for BitTorrent, easy to setup and configure.
* This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
* Updated and Maintained by Yupy.
* Copyright (C) 2004-2014 Btiteam.org
*/

global $db;

if (!user::$current || user::$current["view_users"] == "no") {
    // do nothing
} else {
    //lastest member
    block_begin("Latest Member");

	$cache_last_member = CACHE_PATH . 'last_member.txt';
    $cache_last_member_expire = 15 * 60;
    if (file_exists($cache_last_member) && is_array(unserialize(file_get_contents($cache_last_member))) && (vars::$timestamp - filemtime($cache_last_member)) < $cache_last_member_expire)
    {
        $a = unserialize(@file_get_contents($cache_last_member));
    } else {
        $a = @$db->query("SELECT id, username FROM users WHERE id_level <> 1 AND id_level <> 2 ORDER BY id DESC LIMIT 1");
	    $a = @$a->fetch_assoc();
		
		$handle = fopen($cache_last_member, "w+");
        fwrite($handle, serialize($a));
        fclose($handle);
    }
	
    if ($a) {
        if (user::$current["view_users"] == "yes")
            $latestuser = "<a href='userdetails.php?id=" . (int)$a["id"] . "'>" . security::html_safe($a["username"]) . "</a>";
        else
            $latestuser = security::html_safe($a['username']);

        echo "<div align='center'>Welcome to our Tracker <br /><b>" . $latestuser . "</b>!</div>\n";
    }
    block_end();
    
} // end if user can view

?>