<?php
/*
* BtiTracker v1.5.0 is a php tracker system for BitTorrent, easy to setup and configure.
* This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
* Updated and Maintained by Yupy.
* Copyright (C) 2004-2014 Btiteam.org
*/

block_begin(BLOCK_MENU);

print("<tr><td class='blocklist' align='center'><a href='./'>" . MNU_INDEX . "</a></td></tr>\n");

if (user::$current["view_torrents"] == "yes") {
    print("<tr><td class='blocklist' align='center'><a href='torrents.php'>" . MNU_TORRENT . "</a></td></tr>\n");
    print("<tr><td class='blocklist' align='center'><a href='extra-stats.php'>" . MNU_STATS . "</a></td></tr>\n");
}

if (user::$current["can_upload"] == "yes")
    print("<tr><td class='blocklist' align='center'><a href='upload.php'>" . MNU_UPLOAD . "</a></td>\n");

if (user::$current["view_users"] == "yes")
    print("<tr><td class='blocklist' align='center'><a href='users.php'>" . MNU_MEMBERS . "</a></td></tr>\n");

if (user::$current["view_news"] == "yes")
    print("<tr><td class='blocklist' align='center'><a href='viewnews.php'>" . MNU_NEWS . "</a></td></tr>\n");

if (user::$current["view_forum"] == "yes") {
    if ($GLOBALS["FORUMLINK"] == "" || $GLOBALS["FORUMLINK"] == "internal")
        print("<td class='blocklist' align='center'><a href='forum.php'>" . MNU_FORUM . "</a></td>\n");
    else
        print("<td class='blocklist' align='center'><a href='" . $GLOBALS['FORUMLINK'] . "' target='_blank'>" . MNU_FORUM . "</a></td>\n");
}

if (user::$current["uid"] == 1 || !user::$current)
    print("<tr><td class='blocklist' align='center'><a href='login.php'>" . LOGIN . "</a></td></tr>\n");
else
    $salty = md5("R45eOMs15mNd3yV" . user::$current['username']); 
    print("<tr><td class='blocklist' align='center'><a href='logout.php?check_hash=" . $salty . "'>" . LOGOUT . "</a></td></tr>\n");

block_end();

?>
