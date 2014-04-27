<?php

function catch_up()
{
    global $CURUSER, $db;

    $userid = intval($CURUSER["uid"]);

    $res = $db->execute("SELECT id, lastpost FROM topics") or $db->display_errors();

    while ($arr = $db->fetch_assoc($res))
    {
        $topicid = (int)$arr["id"];
        $postid = (int)$arr["lastpost"];

        $r = $db->execute("SELECT id, lastpostread FROM readposts WHERE userid = ".$userid." AND topicid = ".$topicid) or $db->display_errors();

        if ($db->count_select($r) == 0)
            $db->execute("INSERT INTO readposts (userid, topicid, lastpostread) VALUES(".$userid.", ".$topicid.", ".$postid.")") or $db->display_errors();
        else
        {
            $a = $db->fetch_assoc($r);

            if ($a["lastpostread"] < $postid)
                $db->execute("UPDATE readposts SET lastpostread = ".$postid." WHERE id = ".(int)$a["id"]) or $db->display_errors();
        }
    }
}

function update_topic_last_post($topicid)
{
    global $db;

    $res = $db->execute("SELECT id FROM posts WHERE topicid = ".(int)$topicid." ORDER BY id DESC LIMIT 1") or $db->display_errors();

    $arr = $db->fetch_row($res)
	    or die("No post found !");

    $postid = $arr[0];

    $db->execute("UPDATE topics SET lastpost = ".(int)$postid." WHERE id = ".(int)$topicid) or $db->display_errors();
}

function get_forum_last_post($forumid)
{
    global $db;

    $res = $db->execute("SELECT lastpost FROM topics WHERE forumid = ".(int)$forumid." ORDER BY lastpost DESC LIMIT 1") or $db->display_errors();

    $arr = $db->fetch_row($res);

    $postid = $arr[0];

    if ($postid)
        return $postid;
    else
        return 0;
}

function insert_quick_jump_menu($currentforum = 0)
{
    global $CURUSER, $db;

    print("<p align='center'><form method='get' action='?' name='quickjump'>\n");

    print(QUICK_JUMP.": ");

    print("<select name='forumid' onchange='location.href=this.options[this.selectedIndex].value''>\n");

    $res = $db->execute("SELECT id, name, minclassread FROM forums ORDER BY sort, name") or $db->display_errors();

    while ($arr = $db->fetch_assoc($res))
    {
        if ($CURUSER["id_level"] >= (int)$arr["minclassread"])
            print("<option value='forum.php?action=viewforum&forumid=" . (int)$arr["id"] . ($currentforum == (int)$arr["id"] ? " selected>" : "'>") . htmlsafechars(unesc($arr["name"])) . "</option>\n");
    }

    print("</select>\n");
    print("</form>\n</p>");
}

function insert_compose_frame($id, $newtopic = true, $quote = false)
{
    global $maxsubjectlength, $CURUSER, $db;

    if ($newtopic)
    {
        $res = $db->execute("SELECT name FROM forums WHERE id = ".$db->escape_string($id)) or $db->display_errors();

        $arr = $db->fetch_assoc($res) or die(BAD_FORUM_ID);

        $forumname = htmlsafechars(unesc($arr["name"]));

        block_begin(WORD_NEW." ".TOPIC." ".IN." <a href='?action=viewforum&forumid=".(int)$id."'>".$forumname."</a> ".FORUM);
    } else {
        $res = $db->execute("SELECT * FROM topics WHERE id = ".$db->escape_string($id)) or $db->display_errors();

        $arr = $db->fetch_assoc($res) or stderr(ERROR, FORUM_ERROR.TOPIC_NOT_FOUND);

        $subject = htmlsafechars(unesc($arr["subject"]));

        block_begin(REPLY." ".TOPIC.": <a href='?action=viewtopic&topicid=".(int)$id."'>".$subject."</a>");
    }

    begin_frame();

    print("<form method='post' name='compose' action='?action=post'>\n");

    if ($newtopic)
        print("<input type='hidden' name='forumid' value='".(int)$id."'>\n");
    else
        print("<input type='hidden' name='topicid' value='".(int)$id."'>\n");

    begin_table();

    if ($newtopic)
        print("<tr><td class='header'>".SUBJECT."</td>" .
               "<td align='left'  class='lista' style='padding: 0px'><input type='text' size='50' maxlength='".$maxsubjectlength."' name='subject' " .
               "style='border: 0px; height: 19px'></td></tr>\n");

    if ($quote)
    {
        $postid = 0 + (int)$_GET["postid"];

        if (!is_valid_id($postid))
            die;

        $res = $db->execute("SELECT posts.*, users.username FROM posts INNER JOIN users ON posts.userid = users.id WHERE posts.id = ".$postid) or $db->display_errors();

        if ($db->count_select($res) != 1)
            stderr(ERROR, ERR_NO_POST_WITH_ID." ".$postid.".");

        $arr = $db->fetch_assoc($res);
    }

    print("<tr><td class='header'>".BODY."</td><td align='left' class='lista' style='padding: 0px'>");
           textbbcode("compose", "body", ($quote ? (("[quote=".htmlsafechars($arr["username"])."]".htmlsafechars(unesc($arr["body"]))."[/quote]")) : ''));
    print("<tr><td colspan='2' class='lista' align='center'><input type='submit' class='btn' value='".FRM_CONFIRM."'></td></tr>\n");
    print("</td></tr>");
    end_table();

    print("</form>\n");

    end_frame();

    //------ Get 10 last posts if this is a reply
    if (!$newtopic)
    {
        $postres = $db->execute("SELECT * FROM posts WHERE topicid = ".$db->escape_string($id)." ORDER BY id DESC LIMIT 10") or $db->display_errors();

        begin_frame(LAST_10_POSTS, true);

        while ($post = $db->fetch_assoc($postres))
        {
            //-- Get poster details
            $userres = $db->execute("SELECT * FROM users WHERE id = " . (int)$post["userid"] . " LIMIT 1") or $db->display_errors();

            $user = $db->fetch_assoc($userres);

            $avatar = ($user["avatar"] && $user["avatar"] != '' ? htmlsafechars($user["avatar"]) : '');

            begin_table(true);

            print("<tr valign='top'><td width='150' align='center' class='header' style='padding: 0px'>#" . $post["id"] . " by " . htmlsafechars($user["username"]) . "<br />" . get_date_time($post["added"]) . ($avatar != '' ? "<br /><img width='80' src='".$avatar."'>" : '').
                   "</td><td class='lista'>" . format_comment($post["body"]) . "</td></tr><br>\n");

            end_table();
        }
        end_frame();
    }

    if (!isset($forumid))
	    $forumid = 0;

    insert_quick_jump_menu($forumid);

    block_end();
}

?>
