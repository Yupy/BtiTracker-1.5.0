<table class='lista' width='100%'>
<tr>
<?php
/*
* BtiTracker v1.5.0 is a php tracker system for BitTorrent, easy to setup and configure.
* This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
* Updated and Maintained by Yupy.
* Copyright (C) 2004-2014 Btiteam.org
*/

if (!user::$current) {
    // anonymous=guest
    print("<td class='header' align='center'>" . WELCOME . " " . GUEST . "\n");
    print("<a href='login.php'>(" . LOGIN . ")</a></td>");
} elseif (user::$current["uid"] == 1)
// anonymous=guest
{
    print("<td class='header' align='center'>" . WELCOME . " " . user::$current["username"] . " \n");
    print("<a href='login.php'>(" . LOGIN . ")</a></td>\n");
} else {
    print("<td class='header' align='center'>" . WELCOME_BACK . " " . user::$current["username"] . " \n");
    print("<a href='logout.php'>(" . LOGOUT . ")</a></td>\n");
}

print("<td class='header' align='center'><a href='./'>" . MNU_INDEX . "</a></td>\n");

if (user::$current["view_torrents"] == "yes") {
    print("<td class='header' align='center'><a href='torrents.php'>" . MNU_TORRENT . "</a></td>\n");
    print("<td class='header' align='center'><a href='extra-stats.php'>" . MNU_STATS . "</a></td>\n");
}

if (user::$current["can_upload"] == "yes")
    print("<td class='header' align='center'><a href='upload.php'>" . MNU_UPLOAD . "</a></td>\n");

if (user::$current["view_users"] == "yes")
    print("<td class='header' align='center'><a href='users.php'>" . MNU_MEMBERS . "</a></td>\n");

if (user::$current["view_news"] == "yes")
    print("<td class='header' align='center'><a href='viewnews.php'>" . MNU_NEWS . "</a></td>\n");

if (user::$current["view_forum"] == "yes") {
    if ($GLOBALS["FORUMLINK"] == "" || $GLOBALS["FORUMLINK"] == "internal")
        print("<td class='header' align='center'><a href='forum.php'>" . MNU_FORUM . "</a></td>\n");
    else
        print("<td class='header' align='center'><a href='" . $GLOBALS['FORUMLINK'] . "' target='_blank'>" . MNU_FORUM . "</a></td>\n");
}

?>
</tr>
</table>