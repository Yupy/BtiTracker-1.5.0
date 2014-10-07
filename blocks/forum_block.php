<?php
/*
* BtiTracker v1.5.0 is a php tracker system for BitTorrent, easy to setup and configure.
* This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
* Updated and Maintained by Yupy.
* Copyright (C) 2004-2014 Btiteam.org
*/

global $db;

if (!user::$current || user::$current["view_forum"] == "no") {
    // do nothing
} else {
    block_begin(FORUM_INFO);
    
    $res = $db->query("SELECT COUNT(*) AS topic_total FROM topics");
    if ($res) {
        $row    = $res->fetch_array(MYSQLI_BOTH);
        $topics = (int)$row['topic_total'];

        $res1 = $db->query("SELECT COUNT(*) AS post_total FROM posts");
        if ($res1) {
            $row   = $res1->fetch_array(MYSQLI_BOTH);
            $posts = (int)$row['post_total'];
            if ($posts > 0)
                $posts_avg = number_format(($topics / $posts) * 100, 0);
            else
                $posts_avg = 0;
        }
    } else {
        $topics    = 0;
        $posts     = 0;
        $posts_avg = 0;
    }
    
    print("<table cellpadding='4' cellspacing='1' width='100%'>\n<tr><td class='lista'>\n");
    print("<table width='100%' cellspacing='2' cellpading='2'>\n");
    
    print("<tr><td>" . TOPICS . ":</td><td align='right'>" . number_format($topics) . "</td></tr>\n");
    print("<tr><td>" . POSTS . ":</td><td align='right'>" . number_format($posts) . "</td></tr>\n");
    print("<tr><td>" . TOPICS . "/" . POSTS . ":</td><td align='right'>" . $posts_avg . " %</td></tr>\n");
    
    print("</table>\n</td></tr>\n");
    
    if ($topics > 0) {
        if (isset($GLOBALS["block_forumlimit"]))
            $limit = "LIMIT " . (int)$GLOBALS["block_forumlimit"];
        else
            $limit = "LIMIT 5";
        
        $tres = $db->query("SELECT topics.id, topics.subject, topics.lastpost FROM topics INNER JOIN forums ON forums.id = topics.forumid WHERE forums.minclassread <= " . user::$current["id_level"] . " ORDER BY lastpost DESC " . $limit);
        
        while ($trow = $tres->fetch_array(MYSQLI_BOTH)) {
            $lpres = $db->query("SELECT p.added, p.userid, u.username, u.id_level, prefixcolor, suffixcolor FROM posts p, users u INNER JOIN users_level ON u.id_level = users_level.id WHERE p.userid = u.id AND p.topicid = " . (int)$trow['id'] . " ORDER BY p.added");
            
			while ($lprow = $lpres->fetch_array(MYSQLI_BOTH)) {
                $last_post_userid = (int)$lprow['userid'];
                $last_poster     = security::html_safe($lprow['username']);
                $last_post_time   = get_date_time($lprow['added']);
                
                $pcolor = unesc($lprow["prefixcolor"]);
                $scolor = unesc($lprow["suffixcolor"]);
                
            }
            
            if ($trow['lastpost'])
                print("<tr><td class='lista'><b><a href='forum.php?action=viewtopic&amp;topicid=" . (int)$trow['id'] . "&amp;page=last#" . (int)$trow['lastpost'] . "'>" . security::html_safe(unesc($trow['subject'])) . "</a></b><br />" . LAST_POST_BY . " <a href='userdetails.php?id=" . $last_post_userid . "'>" . $pcolor . $last_poster . $scolor . "</a><br />On " . $last_post_time . "</td></tr>\n");
            else
                print("<tr><td class='lista'><b><a href='forum.php?action=viewtopic&amp;topicid=" . (int)$trow['id'] . "&amp;page=last'>" . security::html_safe(unesc($trow['subject'])) . "</a></b><br />" . LAST_POST_BY . " <a href='userdetails.php?id=" . $last_post_userid . "'>" . $pcolor . $last_poster . $scolor . "</a><br />On " . $last_post_time . "</td></tr>\n");
        }
    } else {
        print("<tr><td class='lista'>" . NO_TOPIC . "</td></tr>\n");
    }
    
    print("</table>\n");
    
    block_end();
    
} // end if user can view

?>