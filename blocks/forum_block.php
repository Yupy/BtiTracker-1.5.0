<?php

global $CURUSER, $db, $Memcached;

if (!$CURUSER || $CURUSER["view_forum"] == "no")
{
    // do nothing
} else {

    block_begin(FORUM_INFO);

	       $totaltopics_key = "Total::Topics::";
    if (($topics = $Memcached->get_value($totaltopics_key)) == false)
	{
        $res = $db->execute("SELECT COUNT(*) AS topic_total FROM topics") or $db->display_errors();

        if ($res)
        {
            $row = $db->fetch_array($res);
            $topics = $row['topic_total'];
	    }
	    $Memcached->cache_value($totaltopics_key, $topics, 3200);
    }

	if ($res)
	{
		    $totalposts_key = "Total::Posts::";
        if (($posts = $Memcached->get_value($totalposts_key)) == false)
	    {
            $res1 = $db->execute("SELECT COUNT(*) AS post_total FROM posts") or $db->display_errors();

            if ($res1)
            {
                $row = $db->fetch_array($res1);
                $posts = $row['post_total'];
            }
			$Memcached->cache_value($totalposts_key, $posts, 3200);
	    }
		
		if ($posts>0)
            $posts_avg = number_format(($topics/$posts) * 100, 0);
        else
            $posts_avg = 0;

    } else {
        $topics = 0;
        $posts = 0;
        $posts_avg = 0;
    }

    print("<table cellpadding='4' cellspacing='1' width='100%'>\n<tr><td class='lista'>\n");
    print("<table width='100%' cellspacing='2' cellpading='2'>\n");

    print("<tr><td>" . TOPICS . ":</td><td align='right'>" . number_format($topics) . "</td></tr>\n");
    print("<tr><td>" . POSTS . ":</td><td align='right'>" . number_format($posts) . "</td></tr>\n");
    print("<tr><td>" . TOPICS . "/" . POSTS . ":</td><td align='right'>" . $posts_avg . " %</td></tr>\n");

    print("</table>\n</td></tr>\n");

    if ( $topics > 0 )
    {
        if (isset($GLOBALS["block_forumlimit"]))
            $limit = "LIMIT " . $GLOBALS["block_forumlimit"];
        else
            $limit = "LIMIT 5";
        $tres = $db->execute("SELECT topics.id, topics.subject, topics.lastpost FROM topics INNER JOIN forums ON forums.id = topics.forumid WHERE forums.minclassread <= ".(int)$CURUSER["id_level"]." ORDER BY lastpost DESC ".$limit) or $db->display_errors();

        while ($trow = $db->fetch_array($tres))
        {
            $lpres = $db->execute("SELECT p.added, p.userid, u.username, u.id_level, prefixcolor, suffixcolor
                FROM posts p, users u INNER JOIN users_level ON u.id_level = users_level.id
                WHERE p.userid = u.id
                AND p.topicid = " . (int)$trow['id'] ." ORDER BY p.added") or $db->display_errors();

            while ($lprow = $db->fetch_array($lpres))
            {
                $last_post_userid = (int)$lprow['userid'];
                $last_poster = htmlsafechars($lprow['username']);
                $last_post_time = get_date_time($lprow['added']);
                $pcolor = unesc($lprow["prefixcolor"]);
                $scolor = unesc($lprow["suffixcolor"]);
            }

            if ($trow['lastpost'])
                print("<tr><td class='lista'><b><a href='forum.php?action=viewtopic&amp;topicid=" . $trow['id'] . "&amp;page=last#" . $trow['lastpost'] . "'>" . htmlsafechars(unesc($trow['subject'])) . "</a></b><br />".LAST_POST_BY." <a href='userdetails.php?id=" . $last_post_userid . "'>" . $pcolor . $last_poster . $scolor ."</a><br />On " . $last_post_time . "</td></tr>\n");
            else
                print("<tr><td class='lista'><b><a href='forum.php?action=viewtopic&amp;topicid=" . $trow['id'] . "&amp;page=last'>" . htmlsafechars(unesc($trow['subject'])) . "</a></b><br />".LAST_POST_BY." <a href='userdetails.php?id=" . $last_post_userid . "'>" . $pcolor . $last_poster . $scolor ."</a><br />On " . $last_post_time . "</td></tr>\n");
        }
    } else {
        print("<tr><td class='lista'>" . NO_TOPIC . "</td></tr>\n");
    }

print("</table>\n");
block_end();

} // end if user can view
?>
