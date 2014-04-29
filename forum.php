<?php
require("include/functions.php");
require("include/config.php");

  dbconn();

  standardheader('Forum');


  if (!$CURUSER || $CURUSER["view_forum"]!="yes")
     stderr(ERROR,NOT_AUTHORIZED." ".MNU_FORUM);

  if (isset($_GET["action"])) $action = $_GET["action"];
    else $action = "";

  function catch_up()
  {
    global $CURUSER;

    $userid = intval($CURUSER["uid"]);

    $res = run_query("SELECT id, lastpost FROM topics") or sqlerr(__FILE__, __LINE__);

    while ($arr = mysqli_fetch_assoc($res))
    {
      $topicid = $arr["id"];

      $postid = $arr["lastpost"];

      $r = run_query("SELECT id,lastpostread FROM readposts WHERE userid=$userid and topicid=$topicid") or sqlerr(__FILE__, __LINE__);

      if (mysqli_num_rows($r) == 0)
        run_query("INSERT INTO readposts (userid, topicid, lastpostread) VALUES($userid, $topicid, $postid)") or sqlerr(__FILE__, __LINE__);

      else
      {
        $a = mysqli_fetch_assoc($r);

        if ($a["lastpostread"] < $postid)
          run_query("UPDATE readposts SET lastpostread=$postid WHERE id=" . $a["id"]) or sqlerr(__FILE__, __LINE__);
      }
    }
  }

  //-------- Returns the forum ID of a topic, or false on error

  function get_topic_forum($topicid)
  {
    $res = run_query("SELECT forumid FROM topics WHERE id=$topicid") or sqlerr(__FILE__, __LINE__);

    if (mysqli_num_rows($res) != 1)
      return false;

    $arr = mysqli_fetch_row($res);

    return $arr[0];
  }

  //-------- Returns the ID of the last post of a forum

  function update_topic_last_post($topicid)
  {
    $res = run_query("SELECT id FROM posts WHERE topicid=$topicid ORDER BY id DESC LIMIT 1") or sqlerr(__FILE__, __LINE__);

    $arr = mysqli_fetch_row($res) or die("No post found");

    $postid = $arr[0];

    run_query("UPDATE topics SET lastpost=$postid WHERE id=$topicid") or sqlerr(__FILE__, __LINE__);
  }

  function get_forum_last_post($forumid)
  {
    $res = run_query("SELECT lastpost FROM topics WHERE forumid=$forumid ORDER BY lastpost DESC LIMIT 1") or sqlerr(__FILE__, __LINE__);

    $arr = mysqli_fetch_row($res);

    $postid = $arr[0];

    if ($postid)
      return $postid;

    else
      return 0;
  }

  //-------- Inserts a quick jump menu

  function insert_quick_jump_menu($currentforum = 0)
  {
    global $CURUSER;
    print("<p align=center><form method=get action=? name=\"quickjump\">\n");

    print(QUICK_JUMP.": ");

    print("<select name=forumid onchange=\"location.href=this.options[this.selectedIndex].value\">\n");

    $res = run_query("SELECT id,name,minclassread FROM forums ORDER BY sort,name") or sqlerr(__FILE__, __LINE__);

    while ($arr = mysqli_fetch_assoc($res))
    {
      if ($CURUSER["id_level"] >= $arr["minclassread"])
        print("<option value=forum.php?action=viewforum&forumid=" . $arr["id"] . ($currentforum == $arr["id"] ? " selected>" : ">") . htmlsafechars(unesc($arr["name"])) . "</option>\n");
    }

    print("</select>\n");

    print("</form>\n</p>");

  }

  //-------- Inserts a compose frame

  function insert_compose_frame($id, $newtopic = true, $quote = false)
  {
    global $maxsubjectlength, $CURUSER;

    if ($newtopic)
    {
      $res = run_query("SELECT name FROM forums WHERE id=$id") or sqlerr(__FILE__, __LINE__);

      $arr = mysqli_fetch_assoc($res) or die(BAD_FORUM_ID);

      $forumname = htmlsafechars(unesc($arr["name"]));

      block_begin(WORD_NEW." ".TOPIC." ".IN." <a href=?action=viewforum&forumid=$id>$forumname</a> ".FORUM);
    }
    else
    {
      $res = run_query("SELECT * FROM topics WHERE id=$id") or sqlerr(__FILE__, __LINE__);

      $arr = mysqli_fetch_assoc($res) or stderr(ERROR,FORUM_ERROR.TOPIC_NOT_FOUND);

      $subject = htmlsafechars(unesc($arr["subject"]));

      block_begin(REPLY." ".TOPIC.": <a href=?action=viewtopic&topicid=$id>$subject</a>");
    }

    begin_frame();

    print("<form method=post name=compose action=?action=post>\n");

    if ($newtopic)
      print("<input type=hidden name=forumid value=$id>\n");

    else
      print("<input type=hidden name=topicid value=$id>\n");

    begin_table();

    if ($newtopic)
      print("<tr><td class=rowhead>".SUBJECT."</td>" .
        "<td align=left style='padding: 0px'><input type=text size=50 maxlength=$maxsubjectlength name=subject " .
        "style='border: 0px; height: 19px'></td></tr>\n");

    if ($quote)
    {
       $postid = 0+$_GET["postid"];
       if (!is_valid_id($postid))
         die;

       $res = run_query("SELECT posts.*, users.username FROM posts INNER JOIN users ON posts.userid = users.id WHERE posts.id=$postid") or sqlerr(__FILE__, __LINE__);

       if (mysqli_num_rows($res) != 1)
         stderr(ERROR,ERR_NO_POST_WITH_ID."$postid.");

       $arr = mysqli_fetch_assoc($res);
    }

    print("<tr><td class=rowhead>".BODY."</td><td align=left style='padding: 0px'>");
    textbbcode("compose","body",($quote?(("[quote=".htmlsafechars($arr["username"])."]".htmlsafechars(unesc($arr["body"]))."[/quote]")):""));
    print("<tr><td colspan=2 align=center><input type=submit class=btn value='".FRM_CONFIRM."'></td></tr>\n");
    print("</td></tr>");
    end_table();

    print("</form>\n");

    end_frame();

    //------ Get 10 last posts if this is a reply

    if (!$newtopic)
    {
      $postres = run_query("SELECT * FROM posts WHERE topicid=$id ORDER BY id DESC LIMIT 10") or sqlerr(__FILE__, __LINE__);

      begin_frame(LAST_10_POSTS,true);

      while ($post = mysqli_fetch_assoc($postres))
      {
        //-- Get poster details

        $userres = run_query("SELECT * FROM users WHERE id=" . $post["userid"] . " LIMIT 1") or sqlerr(__FILE__, __LINE__);

        $user = mysqli_fetch_assoc($userres);

        $avatar = ($user["avatar"] && $user["avatar"] != "" ? htmlsafechars($user["avatar"]) : "");

        begin_table(true);

        print("<tr valign=top><td width=150 align=center style='padding: 0px'>#" . $post["id"] . " by " . $user["username"] . "<br />" . get_date_time($post["added"]) . ($avatar!="" ? "<br /><img width=80 src=$avatar>" : "").
          "</td><td class=comment>" . format_comment($post["body"]) . "</td></tr><br>\n");

        end_table();

      }

      end_frame();

    }
if (!isset($forumid)) $forumid = 0;
  insert_quick_jump_menu($forumid);

  block_end();

  }

  //-------- Global variables

  $maxsubjectlength = 40;
  $postsperpage = intval($CURUSER["postsperpage"]);
    if (!$postsperpage) $postsperpage = 15;

  //-------- Action: New topic

  if ($action == "newtopic")
  {
    $forumid = 0+$_GET["forumid"];

    if (!is_valid_id($forumid))
      die;

    insert_compose_frame($forumid);

    stdfoot();

    die;
  }

  //-------- Action: Post

  if ($action == "post")
  {
    $forumid = isset($_POST["forumid"])?intval($_POST["forumid"]):false;
    $topicid = isset($_POST["topicid"])?intval($_POST["topicid"]):false;

    if (!is_valid_id($forumid) && !is_valid_id($topicid))
      stderr(ERROR,ERR_FORUM_TOPIC);

    $newtopic = $forumid > 0;

    $subject = isset($_POST["subject"])?($_POST["subject"]):false;

    if ($newtopic)
    {
      $subject = trim($subject);

      if (!$subject)
        stderr(ERROR, ERR_SUBJECT);

      if (strlen($subject) > $maxsubjectlength)
        stderr(ERROR,SUBJECT_MAX_CHAR." $maxsubjectlength ".CHARACTERS);
    }
    else
      $forumid = get_topic_forum($topicid) or die(ERR_TOPIC_ID);

    //------ Make sure sure user has write access in forum

    $arr = Forum::get_forum_access_levels($forumid) or die(BAD_FORUM_ID);

    if ($CURUSER["id_level"] < $arr["write"] || ($newtopic && $CURUSER["id_level"] < $arr["create"]))
      stderr(ERROR,ERR_PERM_DENIED);

    $body = trim($_POST["body"]);

    if ($body == "")
      stderr(ERROR,ERR_NO_BODY);

    $userid = intval($CURUSER["uid"]);

    if ($newtopic)
    {
      //---- Create topic

      $subject = sqlesc(htmlsafechars($subject));

      run_query("UPDATE forums SET topiccount=topiccount+1 WHERE id=$forumid");

      run_query("INSERT INTO topics (userid, forumid, subject) VALUES($userid, $forumid, $subject)") or sqlerr(__FILE__, __LINE__);

      $topicid = ((is_null($___mysqli_res = mysqli_insert_id($GLOBALS["___mysqli_ston"]))) ? false : $___mysqli_res) or stderr(ERROR,ERR_NO_TOPIC_ID);
    }
    else
    {
      //---- Make sure topic exists and is unlocked

      $res = run_query("SELECT * FROM topics WHERE id=$topicid") or sqlerr(__FILE__, __LINE__);

      $arr = mysqli_fetch_assoc($res) or die(ERR_TOPIC_ID_NA);

      if ($arr["locked"] == 'yes' && $CURUSER["edit_forum"] != "yes")
        stderr(ERROR,ERR_TOPIC_LOCKED);

      //---- Get forum ID

      $forumid = $arr["forumid"];
    }

    //------ Insert post

    $added = "UNIX_TIMESTAMP()";

    $body = sqlesc($body);

    run_query("INSERT INTO posts (topicid, userid, added, body) " .
    "VALUES($topicid, $userid, $added, $body)") or sqlerr(__FILE__, __LINE__);

    $postid = ((is_null($___mysqli_res = mysqli_insert_id($GLOBALS["___mysqli_ston"]))) ? false : $___mysqli_res) or die(ERR_POST_ID_NA);

    //------ Update topic last post

    update_topic_last_post($topicid);

    run_query("UPDATE forums SET postcount=postcount+1 WHERE id=$forumid");

    //------ All done, redirect user to the post

    //---- Get reply count

    $postsperpage = intval($CURUSER["postsperpage"]);

    $res = run_query("SELECT COUNT(*) FROM posts WHERE topicid=$topicid") or sqlerr(__FILE__, __LINE__);

    $arr = mysqli_fetch_row($res);

    $posts = $arr[0];

    $tpages = floor($posts / $postsperpage);

    if ($tpages * $postsperpage != $posts)
      ++$tpages;

    for ($i = 1; $i <= $tpages; ++$i)
    $headerstr = "forum.php?action=viewtopic&topicid=$topicid&page=$i";


    if ($newtopic)
      redirect($headerstr);

    else
      redirect("$headerstr#$postid");

    die;
  }

  //-------- Action: View topic

  if ($action == "viewtopic")
  {
    $topicid = 0+$_GET["topicid"];

    if (isset($_GET["page"]))
        {
        if (substr($_GET["page"],0,4)=="last")
            $page=htmlsafechars($_GET["page"]);
        else
            $page = max(1,$_GET["page"]);
        }
    else $page = '';

    if (!is_valid_id($topicid))
      die;

    $userid = intval($CURUSER["uid"]);

    //------ Get topic info

    $res = run_query("SELECT * FROM topics WHERE id=$topicid") or sqlerr(__FILE__, __LINE__);

    $arr = mysqli_fetch_assoc($res) or stderr(ERROR,FORUM_ERROR.TOPIC_NOT_FOUND);

    $locked = ($arr["locked"] == 'yes');
    $subject = htmlsafechars(unesc($arr["subject"]));
    $sticky = $arr["sticky"] == "yes";
    $forumid = $arr["forumid"];

    //------ Update hits column

    run_query("UPDATE topics SET views = views + 1 WHERE id=$topicid") or sqlerr(__FILE__, __LINE__);

    //------ Get forum

    $res = run_query("SELECT * FROM forums WHERE id=$forumid") or sqlerr(__FILE__, __LINE__);

    $arr = mysqli_fetch_assoc($res) or die("Forum = NULL");

    $forum = htmlsafechars(unesc($arr["name"]));

    if ($CURUSER["id_level"] < $arr["minclassread"])
        stderr(ERROR,ERR_LEVEL_CANT_VIEW);

    if ($CURUSER["id_level"] < $arr["minclasswrite"])
       $usercan_write=false;
    else
        $usercan_write=true;
    //------ Get post count

    $res = run_query("SELECT COUNT(*) FROM posts WHERE topicid=$topicid") or sqlerr(__FILE__, __LINE__);

    $arr = mysqli_fetch_row($res);

    $postcount = $arr[0];

    //------ Make page menu

    $pagemenu = "<p align=center>\n";

    $perpage = $postsperpage;

    $pages = ceil($postcount / $perpage);

    if ($page && $page[0] == "p")
    {
        $findpost = substr($page, 1);
        $res = run_query("SELECT id FROM posts WHERE topicid=$topicid ORDER BY added") or sqlerr(__FILE__, __LINE__);
        $i = 1;
        while ($arr = mysqli_fetch_row($res))
        {
          if ($arr[0] == $findpost)
            break;
          ++$i;
        }
        $page = ceil($i / $perpage);
      }

    if (substr($page,0,4) == "last")
      $page = $pages;
    else
    {
      if($page < 1)
        $page = 1;
      elseif ($page > $pages)
        $page = $pages;
    }

    $offset = $page * $perpage - $perpage;

    for ($i = 1; $i <= $pages; ++$i)
    {
      if ($i == $page)
        $pagemenu .= "<font class=gray><b>$i</b></font>\n";

      else
        $pagemenu .= "<a href=?action=viewtopic&topicid=$topicid&page=$i><b>$i</b></a>\n";
    }

    if ($page == 1)
      $pagemenu .= "<br><font class=gray><b>&lt;&lt; ".PREVIOUS."</b></font>";

    else
      $pagemenu .= "<br><a href=?action=viewtopic&topicid=$topicid&page=" . ($page - 1) .
        "><b>&lt;&lt; ".PREVIOUS."</b></a>";

    $pagemenu .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";

    if ($page == $pages)
      $pagemenu .= "<font class=gray><b>".NEXT." &gt;&gt;</b></font></p>\n";

    else
      $pagemenu .= "<a href=?action=viewtopic&topicid=$topicid&page=" . ($page + 1) .
        "><b>".NEXT." &gt;&gt;</b></a></p>\n";

    //------ Get posts

    $res = run_query("SELECT * FROM posts WHERE topicid=$topicid ORDER BY id LIMIT $offset,$perpage") or sqlerr(__FILE__, __LINE__);

    block_begin("<a href=forum.php>".FORUMS."</a> &gt; <a href=?action=viewforum&forumid=$forumid>$forum</a>");

    print("<table width=100%>\n<tr><td class=header>".VIEW_TOPIC.": $subject</td></tr>\n<tr><td>");

    print($pagemenu);

    print("<a name=\"top\" />\n");
    //------ Print table

    //begin_main_frame();

    begin_frame();

    $pc = mysqli_num_rows($res);

    $pn = 0;

    $r = run_query("SELECT lastpostread FROM readposts WHERE userid=" . intval($CURUSER["uid"]) . " AND topicid=$topicid") or sqlerr(__FILE__, __LINE__);

    $a = mysqli_fetch_row($r);

    $lpr = $a[0];

    if (!$lpr)
      run_query("INSERT INTO readposts (userid, topicid) VALUES($userid, $topicid)") or sqlerr(__FILE__, __LINE__);

    while ($arr = mysqli_fetch_assoc($res))
    {
      ++$pn;

      $postid = $arr["id"];

      $posterid = $arr["userid"];

      $added = get_date_time($arr["added"]) . "<br>(" . (get_elapsed_time(($arr["added"]))) . " ago)";

      //---- Get poster details

      $res2 = run_query("SELECT username, level, avatar, name, flagpic FROM users INNER JOIN users_level ON users.id_level=users_level.id LEFT JOIN countries ON users.flag = countries.id WHERE users.id=$posterid") or sqlerr(__FILE__, __LINE__);

      $arr2 = mysqli_fetch_assoc($res2);
      
      if (($user_stats = $Memcached->get_value('forum::user::stats::'.$posterid)) === false) {
           $stats_sql = $db->execute('SELECT uploaded, downloaded FROM users WHERE id = '.$db->escape_string($posterid)) or $db->display_errors();

           $user_stats = $db->fetch_assoc($stats_sql);

           $user_stats['uploaded'] = (float)$user_stats['uploaded'];
           $user_stats['downloaded'] = (float)$user_stats['downloaded'];
           $Memcached->cache_value('forum::user::stats::'.$posterid, $user_stats, 3600);
      }

      $postername = $arr2["username"];

      if ($postername == "")
      {
        $by = "unknown[$posterid]";

        $avatar = "";
      }
      else
      {
        $avatar = ($arr2["avatar"] && $arr2["avatar"] != "" ? htmlsafechars($arr2["avatar"]) : "");
        $title = $arr2["level"];

        $flag = $arr2['name'];
        if (!$flag || $flag=="")
           $flag="unknown";
        $flagpic = $arr2["flagpic"];
        if (!$flagpic || $flagpic=="")
           $flagpic="unknown.gif";

        if ( intval( $user_stats['downloaded']) > 0 )
               {
                $ratio = number_format($user_stats['uploaded'] / $user_stats['downloaded'], 2);
               }
               else
               {
                $ratio = '&infin;';
               }

        $sql = run_query("SELECT * FROM posts INNER JOIN users ON posts.userid = users.id WHERE users.id = " . $posterid);
        $posts = 0+@mysqli_num_rows($sql);

        $by = "<a href=userdetails.php?id=$posterid><b>$postername</b></a> ($title)";
      }

      print("<a name=$postid />\n");

      if ($pn == $pc)
      {
        print("<a name=last />\n");
        if ($postid > $lpr)
          run_query("UPDATE readposts SET lastpostread=$postid WHERE userid=$userid AND topicid=$topicid") or sqlerr(__FILE__, __LINE__);
      }

      print("<table width=100% class=lista border=0 cellspacing=0 cellpadding=0><tr><td class=header align=right>&nbsp;");

      if ((!$locked || $CURUSER["edit_forum"] == "yes") && $usercan_write)
        print("<a href=?action=quotepost&topicid=$topicid&postid=$postid><b>".image_or_link($STYLEPATH."/f_quote.png","","[".QUOTE."]")."</b></a>");

      if ((intval($CURUSER["uid"]) == $posterid && !$locked) || $CURUSER["edit_forum"] == "yes")
        print(" - <a href=?action=editpost&postid=$postid><b>".image_or_link($STYLEPATH."/f_edit.png","","[".EDIT."]")."</b></a>");

      if ($CURUSER["delete_forum"] == "yes")
        print(" - <a href=?action=deletepost&postid=$postid&forumid=$forumid><b>".image_or_link($STYLEPATH."/f_delete.png","","[".DELETE."]")."</b></a>");

      print("&nbsp;</td></tr>");
      print("</table>\n");

      begin_table(true);

      $body = format_comment($arr["body"]);

      if (is_valid_id($arr['editedby']))
      {
        $res2 = run_query("SELECT username FROM users WHERE id=$arr[editedby]");
        if (mysqli_num_rows($res2) == 1)
        {
          $arr2 = mysqli_fetch_assoc($res2);
          $body .= "<p><font size=1 class=small>".LAST_EDITED_BY." <a href=userdetails.php?id=$arr[editedby]><b>$arr2[username]</b></a> at ".get_date_time($arr['editedat'])."</font></p>\n";
        }
      }

      print("<tr valign=top><td width=150 align=center style='padding: 0px'>" .
        "$by <br />$added" . ($avatar ? "<br /><img width=80 src=\"$avatar\"><br />" : "<br />").
        RATIO . ": $ratio <br />" .
        POSTS . ": $posts <br />".image_or_link("images/flag/$flagpic","",$flag) ."&nbsp;&nbsp;&nbsp;&nbsp;".
        (intval($CURUSER["uid"])>1?"<a href=usercp.php?do=pm&action=edit&uid=".intval($CURUSER["uid"])."&what=new&to=".urlencode($postername).">".image_or_link("$STYLEPATH/pm.png","",PM)."</a>":"").
        "<br /><br />" .
        "</td><td>$body</td></tr>\n");
      print("<tr><td class=header align=right colspan=2><a href=#top><img src=images/top.gif border=0 alt='Top'></a></td></tr>");

      end_table();
      print("<br />\n");
    }

    //------ Mod options

      if ($CURUSER["edit_forum"] == "yes")
      {
        attach_frame();

        $res = run_query("SELECT id,name,minclasswrite FROM forums ORDER BY sort,name") or sqlerr(__FILE__, __LINE__);
        print("<table class=lista cellspacing=0 cellpadding=0>\n");

        print("<form method=post action=?action=setsticky>\n");
        print("<input type=hidden name=topicid value=$topicid>\n");
        print("<input type=hidden name=returnto value=$_SERVER[REQUEST_URI]>\n");
        print("<tr><td class=lista align=right>".STICKY.":</td>\n");
        print("<td class=lista><input type=radio name=sticky value='yes' " . ($sticky ? " checked" : "") . "> ".YES." <input type=radio name=sticky value='no' " . (!$sticky ? " checked" : "") . "> ".NO."\n");
        print("<input type=submit value='Set'></td></tr>");
        print("</form>\n");

        print("<form method=post action=?action=setlocked>\n");
        print("<input type=hidden name=topicid value=$topicid>\n");
        print("<input type=hidden name=returnto value=$_SERVER[REQUEST_URI]>\n");
        print("<tr><td class=lista align=right>".LOCKED.":</td>\n");
        print("<td class=lista><input type=radio name=locked value='yes' " . ($locked ? " checked" : "") . "> ".YES." <input type=radio name=locked value='no' " . (!$locked ? " checked" : "") . "> ".NO."\n");
        print("<input type=submit value='Set'></td></tr>");
        print("</form>\n");

        print("<form method=post action=?action=renametopic>\n");
        print("<input type=hidden name=topicid value=$topicid>\n");
        print("<input type=hidden name=returnto value=$_SERVER[REQUEST_URI]>\n");
        print("<tr><td class=lista align=right>".RENAME_TOPIC.":</td><td class=lista><input type=text name=subject size=60 maxlength=$maxsubjectlength value=\"" . htmlspecialchars($subject) . "\">\n");
        print("<input type=submit value='Okay'></td></tr>");
        print("</form>\n");

        print("<form method=post action=?action=movetopic&topicid=$topicid>\n");
        print("<tr><td class=lista>&nbsp;".MOVE_THREAD."</td><td class=lista><select name=forumid>");

        while ($arr = mysqli_fetch_assoc($res))
          if ($arr["id"] != $forumid && $CURUSER["id_level"] >= $arr["minclasswrite"])
            print("<option value=" . $arr["id"] . ">" . htmlsafechars(unesc($arr["name"])) . "\n");

        print("</select> <input type=submit value='Okay'></form></td></tr>\n");
        print("<tr><td class=lista align=right>".DELETE_TOPIC."&nbsp;</td><td class=lista>\n");
        print("<form method=get action=forum.php>\n");
        print("<input type=hidden name=action value=deletetopic>\n");
        print("<input type=hidden name=topicid value=$topicid>\n");
        print("<input type=hidden name=forumid value=$forumid>\n");
        print("<input type=checkbox name=sure value=1>".IM_SURE);
        print("<input type=submit value='Okay'>\n");
        print("</form>\n");
        print("</td></tr>\n");
        print("</table>\n");
      }

    print("</td></tr>\n</table>\n");

    end_frame();

    print($pagemenu);

    if ($locked && $CURUSER["edit_forum"] != "yes")
        print("<p>".TOPIC_LOCKED."</p>\n");

    else
    {
        $arr = Forum::get_forum_access_levels($forumid) or die;

        if ($CURUSER["id_level"] < $arr["write"])
          print("<p><i>".ERR_LEVEL_CANT_POST."</i></p>\n");

        else
          $maypost = true;
      }

      //------ "View unread" / "Add reply" buttons

      print("<p align=center><table class=main border=0 cellspacing=0 cellpadding=0><tr>\n");
      print("<td class=embedded><form method=get action=?>\n");
      print("<input type=hidden name=action value=viewunread>\n");
      print("<input type=submit value='".VIEW_UNREAD."' class=btn>\n");
      print("</form></td>\n");

    if ($maypost)
    {
      print("<td class=embedded style='padding-left: 10px'><form method=get action=?>\n");
      print("<input type=hidden name=action value=reply>\n");
      print("<input type=hidden name=topicid value=$topicid>\n");
      print("<input type=submit value='".ADD_REPLY."' class=btn>\n");
      print("</form></td>\n");
    }
    print("</tr></table></p>\n");

    insert_quick_jump_menu($forumid);

    block_end();

    stdfoot();

    die;
  }

  //-------- Action: Quote

    if ($action == "quotepost")
    {
        $topicid = 0+$_GET["topicid"];

        if (!is_valid_id($topicid))
            stderr(ERROR,ERR_TOPIC_ID."$topicid.");

    insert_compose_frame($topicid, false, true);

    stdfoot();

    die;
  }

  //-------- Action: Reply

  if ($action == "reply")
  {
    $topicid = 0+$_GET["topicid"];

    if (!is_valid_id($topicid))
      die;

    insert_compose_frame($topicid, false);

    stdfoot();

    die;
  }

  //-------- Action: Move topic

  if ($action == "movetopic")
  {
    $forumid = 0 + $_POST["forumid"];

    $topicid = 0 + $_GET["topicid"];

    if (!is_valid_id($forumid) || !is_valid_id($topicid) || $CURUSER["edit_forum"] != "yes")
      die;

    $res = @run_query("SELECT minclasswrite FROM forums WHERE id=$forumid") or sqlerr(__FILE__, __LINE__);

    if (mysqli_num_rows($res) != 1)
      stderr(ERROR,ERR_FORUM_NOT_FOUND);

    $arr = mysqli_fetch_row($res);

    if ($CURUSER["id_level"] < $arr[0])
      die;

    $res = @run_query("SELECT subject,forumid FROM topics WHERE id=$topicid") or sqlerr(__FILE__, __LINE__);

    if (mysqli_num_rows($res) != 1)
      stderr(ERROR,TOPIC_NOT_FOUND);

    $arr = mysqli_fetch_assoc($res);

    if ($arr["forumid"] != $forumid)
      @run_query("UPDATE topics SET forumid=$forumid WHERE id=$topicid") or sqlerr(__FILE__, __LINE__);

    // modifying count topics & post
    $res=@run_query("SELECT id FROM posts WHERE topicid=$topicid") or sqlerr(__FILE__, __LINE__);
    $numposts=@mysqli_num_rows($res);
    $res=@run_query("SELECT id FROM topics WHERE forumid=$forumid AND id=$topicid") or sqlerr(__FILE__, __LINE__);
    $numtopics=@mysqli_num_rows($res);
    run_query("UPDATE forums SET topiccount=topiccount-$numtopics, postcount=postcount-$numposts WHERE id=".$arr["forumid"]);
    run_query("UPDATE forums SET topiccount=topiccount+$numtopics, postcount=postcount+$numposts WHERE id=$forumid");

    // Redirect to forum page

    redirect("forum.php?action=viewforum&forumid=$forumid");

    die;
  }

  //-------- Action: Delete topic

  if ($action == "deletetopic")
  {
    $topicid = 0+$_GET["topicid"];
    $forumid = 0+$_GET["forumid"];

    if (!is_valid_id($topicid) || $CURUSER["delete_forum"] != "yes")
      die;

    $sure = $_GET["sure"];

    if (!$sure)
    {
      stderr(ERROR,ERR_DELETE_TOPIC."<a href=?action=deletetopic&topicid=$topicid&sure=1&forumid=$forumid>".HERE." </a> ".IF_YOU_ARE_SURE."<br />");
    }

    run_query("DELETE FROM topics WHERE id=$topicid") or sqlerr(__FILE__, __LINE__);
    $numtopic=mysqli_affected_rows($GLOBALS["___mysqli_ston"]);
    run_query("DELETE FROM posts WHERE topicid=$topicid") or sqlerr(__FILE__, __LINE__);
    $numposts=mysqli_affected_rows($GLOBALS["___mysqli_ston"]);

    run_query("UPDATE forums SET topiccount=topiccount-$numtopic,postcount=postcount-$numposts WHERE id=$forumid");

    redirect("forum.php?action=viewforum&forumid=$forumid");

    die;
  }

  //-------- Action: Edit post

  if ($action == "editpost")
  {
    $postid = 0+$_GET["postid"];

    if (!is_valid_id($postid))
      die;

    $res = run_query("SELECT * FROM posts WHERE id=$postid") or sqlerr(__FILE__, __LINE__);

        if (mysqli_num_rows($res) != 1)
            stderr(ERROR,ERR_NO_POST_WITH_ID." $postid.");

        $arr = mysqli_fetch_assoc($res);

    $res2 = run_query("SELECT locked FROM topics WHERE id = " . $arr["topicid"]) or sqlerr(__FILE__, __LINE__);
        $arr2 = mysqli_fetch_assoc($res2);

        if (mysqli_num_rows($res) != 1)
            stderr(ERROR,ERR_NO_TOPIC_POST_ID." $postid.");

        $locked = ($arr2["locked"] == 'yes');

    if ((intval($CURUSER["uid"]) != $arr["userid"] || $locked) && $CURUSER["edit_forum"] != "yes")
      stderr(ERROR,ERR_PERM_DENIED);

    if ($_SERVER['REQUEST_METHOD'] == 'POST')
    {
        $body = $_POST['body'];

        if ($body == "")
          stderr(ERROR,ERR_BODY_EMPTY);

      $body = sqlesc($body);

      $editedat = sqlesc(time());

      run_query("UPDATE posts SET body=$body, editedat=$editedat, editedby=".intval($CURUSER["uid"])." WHERE id=$postid") or sqlerr(__FILE__, __LINE__);

        $returnto = $_POST["returnto"];

            if ($returnto != "")
            {
                $returnto .= "#$postid";
                redirect("$returnto");
            }
            else
                stderr(SUCCESS,SUC_POST_SUC_EDIT);
    }

    block_begin(EDIT_POST."\n");

    print("<form name=edit method=post action=?action=editpost&postid=$postid>\n");
    print("<input type=hidden name=returnto value=\"" . htmlsafechars($_SERVER["HTTP_REFERER"]) . "\">\n");

    print("<p align=center><table border=1 cellspacing=1>\n");

    print("<tr><td>".BODY."</td><td align=center>\n");
    textbbcode("edit","body",htmlsafechars(unesc($arr["body"])));
    print("</td></tr>\n");
    print("<tr><td align=center colspan=2><input type=submit value='".FRM_CONFIRM."' class=btn></td></tr>\n");
    print("</table>\n</p>");
    print("</form>\n");

    block_end();
    stdfoot();

    die;
  }

  //-------- Action: Delete post

  if ($action == "deletepost")
  {
    $postid = 0+$_GET["postid"];
    $forumid = 0+$_GET["forumid"];

    if (isset($_GET["sure"]) && $_GET["sure"])
    $sure = $_GET["sure"];
    else $sure = "";

    if ($CURUSER["delete_forum"] != "yes" || !is_valid_id($postid))
      die;

    //------- Get topic id

    $res = run_query("SELECT topicid FROM posts WHERE id=$postid") or sqlerr(__FILE__, __LINE__);

    $arr = mysqli_fetch_row($res) or stderr(ERROR,ERR_POST_NOT_FOUND);

    $topicid = $arr[0];

    //------- We can not delete the post if it is the only one of the topic

    $res = run_query("SELECT COUNT(*) FROM posts WHERE topicid=$topicid") or sqlerr(__FILE__, __LINE__);

    $arr = mysqli_fetch_row($res);

    if ($arr[0] < 2)
      stderr(ERROR,ERR_POST_UNIQUE." <a href=?action=deletetopic&topicid=$topicid&sure=1&forumid=$forumid>".ERR_POST_UNIQUE_2."</a> ".ERR_POST_UNIQUE_3);

    //------- Get the id of the last post before the one we're deleting

    $res = run_query("SELECT id FROM posts WHERE topicid=$topicid AND id < $postid ORDER BY id DESC LIMIT 1") or sqlerr(__FILE__, __LINE__);
        if (mysqli_num_rows($res) == 0)
            $redirtopost = "";
        else
        {
            $arr = mysqli_fetch_row($res);

            //---- Get reply count

            $perpage = intval($CURUSER["topicsperpage"]);

            $res2 = run_query("SELECT COUNT(*) FROM posts WHERE id <= $arr[0] AND topicid=$topicid") or sqlerr(__FILE__, __LINE__);

            $arr2 = mysqli_fetch_row($res2);

            $posts = $arr2[0];

            $tpages = floor($posts / $postsperpage);

            if ($tpages * $postsperpage != $posts)
                ++$tpages;

            for ($i = 1; $i <= $tpages; ++$i)
                $redirtopost = "&page=$i#$arr[0]";
        }

    //------- Make sure we know what we do :-)

    if (!$sure)
    {
      stderr(ERROR,ERR_DELETE_POST." <a href=?action=deletepost&postid=$postid&sure=1&forumid=$forumid>".HERE."</a> ".IF_YOU_ARE_SURE."<br />");
    }

    //------- Delete post

    run_query("DELETE FROM posts WHERE id=$postid") or sqlerr(__FILE__, __LINE__);
    $numposts=mysqli_affected_rows($GLOBALS["___mysqli_ston"]);

    run_query("UPDATE forums SET postcount=postcount-$numposts WHERE id=$forumid");

    //------- Update topic

    update_topic_last_post($topicid);

    redirect("forum.php?action=viewtopic&topicid=$topicid$redirtopost");

    die;
  }

  //-------- Action: Lock topic

  if ($action == "locktopic")
  {
    $forumid = 0+$_GET["forumid"];
    $topicid = 0+$_GET["topicid"];
    $page = (isset($_GET["page"])?max(1,$_GET["page"]):"");

    if (!is_valid_id($topicid) || $CURUSER["edit_forum"] != "yes")
      die;

    run_query("UPDATE topics SET locked='yes' WHERE id=$topicid") or sqlerr(__FILE__, __LINE__);

    redirect("forum.php?action=viewforum&forumid=$forumid&page=$page");

    die;
  }

  //-------- Action: Unlock topic

  if ($action == "unlocktopic")
  {
    $forumid = 0+$_GET["forumid"];

    $topicid = 0+$_GET["topicid"];

    $page = (isset($_GET["page"])?max(1,$_GET["page"]):"");

    if (!is_valid_id($topicid) || $CURUSER["edit_forum"] != "yes")
      die;

    run_query("UPDATE topics SET locked='no' WHERE id=$topicid") or sqlerr(__FILE__, __LINE__);

    redirect("forum.php?action=viewforum&forumid=$forumid&page=$page");

    die;
  }

  //-------- Action: Set locked on/off

  if ($action == "setlocked")
  {
    $topicid = 0 + $_POST["topicid"];

    if (!$topicid || $CURUSER["edit_forum"] != "yes")
      die;

    $locked = sqlesc($_POST["locked"]);
    run_query("UPDATE topics SET locked=$locked WHERE id=$topicid") or sqlerr(__FILE__, __LINE__);

    redirect("$_POST[returnto]");

    die;
  }

  //-------- Action: Set sticky on/off

  if ($action == "setsticky")
  {
    $topicid = 0 + $_POST["topicid"];

    if (!topicid || $CURUSER["edit_forum"] != "yes")
      die;

    $sticky = sqlesc($_POST["sticky"]);
    run_query("UPDATE topics SET sticky=$sticky WHERE id=$topicid") or sqlerr(__FILE__, __LINE__);

    redirect("$_POST[returnto]");

    die;
  }

  //-------- Action: Rename topic

  if ($action == 'renametopic')
  {
    if ($CURUSER["edit_forum"] != "yes")
      die;

    $topicid = 0+$_POST['topicid'];

    if (!is_valid_id($topicid))
      die;

    $subject = $_POST['subject'];

    if ($subject == '')
      stderr(ERROR,ERR_ENTER_NEW_TITLE);

    $subject = sqlesc($subject);

    run_query("UPDATE topics SET subject=$subject WHERE id=$topicid") or sqlerr();

    $returnto = $_POST['returnto'];

    if ($returnto)
      redirect("$returnto");
    die;
  }

  //-------- Action: View forum

  if ($action == "viewforum")
  {
    $forumid = 0+$_GET["forumid"];

    if (!is_valid_id($forumid))
      die;

    if (isset($_GET["page"]) && $_GET["page"])
    $page = max(1,$_GET["page"]);
    else $page = '';

    $userid = intval($CURUSER["uid"]);

    //------ Get forum name

    $res = run_query("SELECT name, minclassread FROM forums WHERE id=$forumid") or sqlerr(__FILE__, __LINE__);

    $arr = mysqli_fetch_assoc($res) or die;

    $forumname = htmlsafechars(unesc($arr["name"]));

    if ($CURUSER["id_level"] < $arr["minclassread"])
      die(ERR_NOT_PERMITED);

    //------ Page links

    //------ Get topic count

    $perpage = intval($CURUSER["topicsperpage"]);
    if (!$perpage) $perpage = 20;

    $res = run_query("SELECT COUNT(*) FROM topics WHERE forumid=$forumid") or sqlerr(__FILE__, __LINE__);

    $arr = mysqli_fetch_row($res);

    $num = $arr[0];

    if ($page == 0)
      $page = 1;

    $first = ($page * $perpage) - $perpage + 1;

    $last = $first + $perpage - 1;

    if ($last > $num)
      $last = $num;

    $pages = floor($num / $perpage);

    if ($perpage * $pages < $num)
      ++$pages;

    //------ Build menu

    $menu = "<p align=center><b>\n";

    $lastspace = false;

    for ($i = 1; $i <= $pages; ++$i)
    {
        if ($i == $page)
        $menu .= "<font class=gray>$i</font>\n";

      elseif ($i > 3 && ($i < $pages - 2) && ($page - $i > 3 || $i - $page > 3))
        {
            if ($lastspace)
              continue;

          $menu .= "... \n";

            $lastspace = true;
        }

      else
      {
        $menu .= "<a href=?action=viewforum&forumid=$forumid&page=$i>$i</a>\n";

        $lastspace = false;
      }
      if ($i < $pages)
        $menu .= "</b>|<b>\n";
    }

    $menu .= "<br>\n";

    if ($page == 1)
      $menu .= "<font class=gray>&lt;&lt; ".PREVIOUS."</font>";

    else
      $menu .= "<a href=?action=viewforum&forumid=$forumid&page=" . ($page - 1) . ">&lt;&lt; ".PREVIOUS."</a>";

    $menu .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";

    if ($last == $num)
      $menu .= "<font class=gray>".NEXT." &gt;&gt;</font>";

    else
      $menu .= "<a href=?action=viewforum&forumid=$forumid&page=" . ($page + 1) . ">".NEXT." &gt;&gt;</a>";

    $menu .= "</b></p>\n";

    $offset = $first - 1;

    //------ Get topics data

    $topicsres = run_query("SELECT * FROM topics WHERE forumid=$forumid ORDER BY sticky, lastpost DESC LIMIT $offset,$perpage") or
      stderr(ERROR,ERR_SQL_ERR.((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)));

    $numtopics = mysqli_num_rows($topicsres);

    block_begin("<a href=forum.php>".FORUMS."</a> &gt; $forumname\n");

    if ($numtopics > 0)
    {
      print($menu);

      print("<table class=\"lista\" width=\"100%\" border=\"1\" cellspacing=\"0\" cellpadding=\"5\" bordercolor=\"#FFFFFF\">");

      print("<tr><td  class=header align=left>".TOPIC."</td><td class=header align=center width=15%>".REPLIES."</td><td class=header align=center width=15%>".VIEWS."</td>\n" .
        "<td  class=header align=center width=15%>".AUTHOR."</td><td class=header align=center width=15%>".LASTPOST."</td>\n");

      print("</tr>\n");

      while ($topicarr = mysqli_fetch_assoc($topicsres))
      {
        $topicid = $topicarr["id"];

        $topic_userid = $topicarr["userid"];

        $topic_views = $topicarr["views"];

        $views = number_format($topic_views);

        $locked = $topicarr["locked"] == "yes";

        $sticky = $topicarr["sticky"] == "yes";

        //---- Get reply count

        $res = run_query("SELECT COUNT(*) FROM posts WHERE topicid=$topicid") or sqlerr(__FILE__, __LINE__);

        $arr = mysqli_fetch_row($res);

        $posts = $arr[0];

        $replies = intval( $posts - 1);

        $tpages = floor($posts / $postsperpage);

        if ($tpages * $postsperpage != $posts)
          ++$tpages;

        if ($tpages > 1)
        {
          $topicpages = " (<img src=images/multipage.gif>";

          for ($i = 1; $i <= $tpages; ++$i)
            $topicpages .= " <a href=?action=viewtopic&topicid=$topicid&page=$i>$i</a>";

          $topicpages .= ")";
        }
        else
          $topicpages = "";

        //---- Get userID and date of last post

        $res = run_query("SELECT * FROM posts WHERE topicid=$topicid ORDER BY id DESC LIMIT 1") or sqlerr(__FILE__, __LINE__);

        $arr = mysqli_fetch_assoc($res);

        $lppostid = 0 + $arr["id"];

        $lpuserid = 0 + $arr["userid"];

        $lpadded = "<nobr>" . get_date_time($arr["added"]) . "</nobr>";

        //------ Get name of last poster

        $res = run_query("SELECT * FROM users WHERE id=$lpuserid") or sqlerr(__FILE__, __LINE__);

        if (mysqli_num_rows($res) == 1)
        {
          $arr = mysqli_fetch_assoc($res);

          $lpusername = "<a href=userdetails.php?id=$lpuserid><b>$arr[username]</b></a>";
        }
        else
          $lpusername = "unknown[$topic_userid]";

        //------ Get author

        $res = run_query("SELECT username FROM users WHERE id=$topic_userid") or sqlerr(__FILE__, __LINE__);

        if (mysqli_num_rows($res) == 1)
        {
          $arr = mysqli_fetch_assoc($res);

          $lpauthor = "<a href=userdetails.php?id=$topic_userid><b>$arr[username]</b></a>";
        }
        else
          $lpauthor = "unknown[$topic_userid]";

        //---- Print row

        $r = run_query("SELECT lastpostread FROM readposts WHERE userid=$userid AND topicid=$topicid") or sqlerr(__FILE__, __LINE__);

        $a = mysqli_fetch_row($r);

        $new = !$a || $lppostid > $a[0];

        $topicpic = ($locked ? ($new ? "lockednew" : "locked") : ($new ? "unlockednew" : "unlocked"));

        $subject = ($sticky ? STICKY.": " : "") . "<a href=?action=viewtopic&topicid=$topicid><b>" .
        htmlsafechars(unesc($topicarr["subject"])) . "</b></a>$topicpages";
        print("<tr><td align=left class=lista><table border=0 cellspacing=0 cellpadding=0><tr>" .
        "<td class=embedded style='padding-right: 5px'>" . image_or_link("$STYLEPATH/$topicpic.png","",$topicpic) .
        "</td><td class=embedded align=left>\n" .
        "$subject</td></tr></table></td><td class=lista align=center>$replies</td>\n" .
        "<td class=lista align=center>$views</td><td class=lista align=center>$lpauthor</td>\n" .
        "<td class=lista align=center>$lpadded<br>by&nbsp;$lpusername</td>\n");

        print("</tr>\n");
      } // while

      print("</table>\n");

      print($menu);

    } // if
    else
      print("<p align=center>".NO_TOPIC."</p>\n");

    print("<p><table class=main border=0 cellspacing=5 cellpadding=5><tr valing=center>\n");

    print("<td class=embedded>".image_or_link("$STYLEPATH/unlockednew.png","style='margin-right: 5px'","unlockednew:")."&nbsp;</td><td class=embedded>".WORD_NEW." ".POST."</td>\n");
    print("<td class=embedded>".image_or_link("$STYLEPATH/locked.png","style='margin-left: 10px; margin-right: 5px'","locked:").
    "&nbsp;</td><td class=embedded>".LOCKED." ".TOPIC."</td>\n");

    print("</tr></table></p>\n");

    $arr = Forum::get_forum_access_levels($forumid) or die;

    $maypost = $CURUSER["id_level"] >= $arr["write"] && $CURUSER["id_level"] >= $arr["create"];

    if (!$maypost)
      print("<p><i>".ERR_CANT_START_TOPICS."</i></p>\n");

    print("<p align=center><table border=0 class=main cellspacing=0 cellpadding=0><tr>\n");

    print("<td class=embedded><form method=get action=?><input type=hidden " .
    "name=action value=viewunread><input type=submit value='".VIEW_UNREAD."' class=btn></form></td>\n");

    if ($maypost)
      print("<td class=embedded><form method=get action=?><input type=hidden " .
      "name=action value=newtopic><input type=hidden name=forumid " .
      "value=$forumid><input type=submit value='".WORD_NEW." ".TOPIC."' class=btn style='margin-left: 10px'></form></td>\n");

    print("</tr></table></p>\n");

    insert_quick_jump_menu($forumid);

    block_end();

    stdfoot();

    die;
  }

  //-------- Action: View unread posts

  if ($action == "viewunread")
  {
    $userid = intval($CURUSER["uid"]);

    $maxresults = 25;

    $res = run_query("SELECT id, forumid, subject, lastpost FROM topics ORDER BY lastpost") or sqlerr(__FILE__, __LINE__);

    block_begin(TOPIC_UNREAD_POSTS);

    $n = 0;

    $uc = $CURUSER["id_level"];

    while ($arr = mysqli_fetch_assoc($res))
    {
      $topicid = $arr['id'];

      $forumid = $arr['forumid'];

      //---- Check if post is read
      $r = run_query("SELECT lastpostread FROM readposts WHERE userid=$userid AND topicid=$topicid") or sqlerr(__FILE__, __LINE__);

      $a = mysqli_fetch_row($r);

      if ($a && $a[0] == $arr['lastpost'])
        continue;

      //---- Check access & get forum name
      $r = run_query("SELECT name, minclassread FROM forums WHERE id=$forumid") or sqlerr(__FILE__, __LINE__);

      $a = mysqli_fetch_assoc($r);

      if ($uc < $a['minclassread'])
        continue;

      ++$n;

      if ($n > $maxresults)
        break;

      $forumname = $a['name'];

      if ($n == 1)
      {
        print("<table width=100% class=lista border=1 bordercolor=#FFFFFF cellspacing=0 cellpadding=5>\n");

        print("<tr><td class=header align=left>Topic</td><td class=header align=left>".MNU_FORUM."</td></tr>\n");
      }
    //---- Get reply count

    $postsperpage = intval($CURUSER["postsperpage"]);

    $rescount = run_query("SELECT COUNT(*) FROM posts WHERE topicid=$topicid") or sqlerr(__FILE__, __LINE__);

    $arrcount = mysqli_fetch_row($rescount);

    $posts = $arrcount[0];

    $tpages = floor($posts / $postsperpage);

    if ($tpages * $postsperpage != $posts)
      ++$tpages;

    $e = 1;
    while ($e < $tpages)
    {
        $e++;
    }

    print("<tr><td class=lista align=left><table border=0 cellspacing=0 cellpadding=0><tr><td class=embedded>" .
    image_or_link("$STYLEPATH/unlockednew.png","style='margin-right: 5px'","unlockednew")."</td><td class=embedded>" .
    "<a href=?action=viewtopic&topicid=$topicid&page=$e#last><b>" . htmlsafechars(unesc($arr["subject"])) .
    "</b></a></td></tr></table></td><td class=lista align=left><a href=?action=viewforum&amp;forumid=$forumid><b>$forumname</b></a></td></tr>\n");

    }
    if ($n > 0)
    {
      print("</table>\n");

      if ($n > $maxresults)
        print("<p>".MORE_THAN." $maxresults ".MORE_THAN_2." $maxresults.</p>\n");

      print("<p align=center><a href=?catchup><b>".CATCHUP."</b></a></p>\n");
    }
    else
      print("<p align=center><b>".NO_TOPIC."</b></p>");

    block_end();

    stdfoot();

    die;
  }

if ($action == "search")
{
    block_begin(FORUM_SEARCH);
if (isset($_GET["keywords"]) && $_GET["keywords"])
    $keywords = trim($_GET["keywords"]);
else $keywords = '';
    if ($keywords != "")
    {
        $perpage = 50;
        $pagemenu1="";
        $page = (isset($_GET["page"])?max(1, 0 + $_GET["page"]):1);
        $ekeywords = sqlesc($keywords);
        print("<p align=center><b>".SEARCHED_FOR." \"". htmlsafechars($keywords) . "\"</b></p>\n");
        $res = run_query("SELECT COUNT(*) FROM posts WHERE MATCH (body) AGAINST ($ekeywords)") or sqlerr(__FILE__, __LINE__);
        $arr = mysqli_fetch_row($res);
        $hits = 0 + $arr[0];
        if ($hits == 0)
            print("<p align=center><b>".SORRY.", ". NO_TOPIC."!</b></p>");
        else
        {
            $pages = 0 + ceil($hits / $perpage);
            if ($page > $pages) $page = $pages;
            for ($i = 1; $i <= $pages; ++$i)
                if ($page == $i)
                    $pagemenu1 .= "<font class=gray><b>$i</b></font>\n";
                else
                    $pagemenu1 .= "<a href=\"forum.php?action=search&amp;keywords=" . htmlsafechars($keywords) . "&amp;page=$i\"><b>$i</b></a>\n";
            if ($page == 1)
                $pagemenu2 = "<font class=gray><b>&lt;&lt; ".PREVIOUS."</b></font>\n";
            else
                $pagemenu2 = "<a href=\"forum.php?action=search&amp;keywords=" . htmlsafechars($keywords) . "&amp;page=" . ($page - 1) . "\"><b>&lt;&lt; ".PREVIOUS."</b></a>\n";
            $pagemenu2 .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n";
            if ($page == $pages)
                $pagemenu2 .= "<font class=gray><b>".NEXT." &gt;&gt;</b></font>\n";
            else
                $pagemenu2 .= "<a href=\"forum.php?action=search&amp;keywords=" . htmlsafechars($keywords) . "&amp;page=" . ($page + 1) . "\"><b>".NEXT." &gt;&gt;</b></a>\n";
            $offset = ($page * $perpage) - $perpage;
            $res = run_query("SELECT id, topicid,userid,added FROM posts WHERE MATCH (body) AGAINST ($ekeywords) LIMIT $offset,$perpage") or sqlerr(__FILE__, __LINE__);
            $num = mysqli_num_rows($res);
            print("<p align=center>$pagemenu1<br>$pagemenu2</p>");
            print("<table class=lista width=100% border=1 bordercolor=#FFFFFF cellspacing=0 cellpadding=5>\n");
            print("<tr><td class=header>".POST."</td><td class=header align=left>".TOPIC."</td><td class=header align=left>".FORUM."</td><td class=header align=left>".AUTHOR."</td></tr>\n");
            for ($i = 0; $i < $num; ++$i)
            {
                $post = mysqli_fetch_assoc($res);
                $res2 = run_query("SELECT forumid, subject FROM topics WHERE id=$post[topicid]") or
                    sqlerr(__FILE__, __LINE__);
                $topic = mysqli_fetch_assoc($res2);
                $res2 = run_query("SELECT name,minclassread FROM forums WHERE id=$topic[forumid]") or
                    sqlerr(__FILE__, __LINE__);
                $forum = mysqli_fetch_assoc($res2);
                if ($forum["name"] == "" || $forum["minclassread"] > $CURUSER["id_level"])
                {
                    --$hits;
                    continue;
                }
                $res2 = run_query("SELECT username FROM users WHERE id=$post[userid]") or
                    sqlerr(__FILE__, __LINE__);
                $user = mysqli_fetch_assoc($res2);
                if ($user["username"] == "")
                    $user["username"] = "[$post[userid]]";

                //---- Get reply count

                $perpage = intval($CURUSER["topicsperpage"]);

                $res3 = run_query("SELECT COUNT(*) FROM posts WHERE id <= $post[id] AND topicid=$post[topicid]") or sqlerr(__FILE__, __LINE__);

                $arr3 = mysqli_fetch_row($res3);

                $posts = $arr3[0];

                $tpages = floor($posts / $postsperpage);

                if ($tpages * $postsperpage != $posts)
                    ++$tpages;

                $e = 1;
                while ($e < $tpages)
                {
                    $e++;
                }

                print("<tr><td class=lista>$post[id]</td><td class=lista align=left><a href=?action=viewtopic&amp;topicid=$post[topicid]&amp;page=$e#$post[id]><b>" . htmlsafechars($topic["subject"]) . "</b></a></td><td align=left class=lista><a href=?action=viewforum&amp;forumid=$topic[forumid]><b>" . htmlsafechars(unesc($forum["name"])) . "</b></a><td align=left class=lista><a href=userdetails.php?id=$post[userid]><b>$user[username]</b></a><br>".AT." ".get_date_time($post["added"])."</tr>\n");

                }
            print("</table>\n");
            print("<p align=center>$pagemenu2<br>$pagemenu1</p>");
            print("<p>&nbsp;&nbsp;".FOUND." $hits ".POST. ($hits != 1 ? "s" : "") . "</p>");
            print("<p align=center><b>".SEARCH_AGAIN."</b></p>\n");
        }
    }
    print("<center><form method=get action=forum.php?>\n");
    print("<input type=hidden name=action value=search>\n");
    print("<table class=lista border=1 bordercolor=#FFFFFF cellspacing=0 cellpadding=5>\n");
    print("<tr><td class=header>".KEYWORDS."</td><td class=lista align=left><input type=text size=55 name=keywords value=\"" . htmlsafechars($keywords) .
         "\"><br>\n" .
        "<font class=small size=-1>".SEARCH_HELP."</font></td></tr>\n");
    print("<tr><td class=lista align=center colspan=2><input type=submit value='".SEARCH."' class=btn></td></tr>\n");
    print("</table>\n</form></center><br />\n");
    block_end();
    stdfoot();
    die;
}

  //-------- Handle unknown action

  if ($action != "")
    stderr(ERROR,ERR_FORUM_UNKW_ACT." $action.");

  //-------- Default action: View forums

  if (isset($_GET["catchup"]))
    catch_up();

  //-------- Get forums

  $forums_res = run_query("SELECT * FROM forums ORDER BY sort,name") or sqlerr(__FILE__, __LINE__);

  block_begin(FORUMS);

  print("<table class=\"lista\" border=\"1\" width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" bordercolor=\"#FFFFFF\">\n");

  print("<tr><td class=header align=center>".FORUM."</td><td class=header align=center width=15%>".TOPICS."</td>" .
  "<td class=header align=center width=15%>".POSTS."</td>" .
  "<td class=header align=center width=15%>".LASTPOST."</td></tr>\n");

  if (mysqli_num_rows($forums_res) == 0)
     print("<tr><td class=lista colspan=4 align=center>".NO_FORUMS."</td></tr>");

  while ($forums_arr = mysqli_fetch_assoc($forums_res))
  {
    if ($CURUSER["id_level"] < $forums_arr["minclassread"])
      continue;

    $forumid = $forums_arr["id"];

    $forumname = htmlsafechars(unesc($forums_arr["name"]));

    $forumdescription = htmlsafechars(unesc($forums_arr["description"]));

    $topiccount = htmlsafechars($forums_arr["topiccount"]);

    $postcount = htmlsafechars($forums_arr["postcount"]);

    // Find last post ID

    $lastpostid = get_forum_last_post($forumid);

    // Get last post info

    $post_res = run_query("SELECT added,topicid,userid FROM posts WHERE id=$lastpostid") or sqlerr(__FILE__, __LINE__);

    if (mysqli_num_rows($post_res) == 1)
    {
      $post_arr = mysqli_fetch_assoc($post_res) or die(ERR_BAD_LAST_POST);

      $lastposterid = $post_arr["userid"];

      $lastpostdate = get_date_time($post_arr["added"]);

      $lasttopicid = $post_arr["topicid"];

      $user_res = run_query("SELECT username FROM users WHERE id=$lastposterid") or sqlerr(__FILE__, __LINE__);

      $user_arr = mysqli_fetch_assoc($user_res);

      $lastposter = htmlsafechars($user_arr['username']);

      $topic_res = run_query("SELECT subject FROM topics WHERE id=$lasttopicid") or sqlerr(__FILE__, __LINE__);

      $topic_arr = mysqli_fetch_assoc($topic_res);

      $lasttopic = htmlsafechars(unesc($topic_arr['subject']));

    //---- Get reply count

    $postsperpage = (intval($CURUSER["postsperpage"])>0?intval($CURUSER["postsperpage"]):15);

    $res = run_query("SELECT COUNT(*) FROM posts WHERE topicid=$lasttopicid") or sqlerr(__FILE__, __LINE__);

    $arr = mysqli_fetch_row($res);

    $posts = $arr[0];

    $tpages = floor($posts / $postsperpage);

    if ($tpages * $postsperpage != $posts)
      ++$tpages;

      for ($i = 1; $i <= $tpages; ++$i)
        $postpages = " <a href=?action=viewtopic&topicid=$lasttopicid&amp;page=$i#$lastpostid><b>$lasttopic</b></a>";

    $lastpost = "<nobr>$lastpostdate<br>" .
    "by <a href=userdetails.php?id=$lastposterid><b>$lastposter</b></a><br>" .
    "in $postpages</nobr>";


      $r = run_query("SELECT lastpostread FROM readposts WHERE userid=".intval($CURUSER["uid"])." AND topicid=$lasttopicid") or sqlerr(__FILE__, __LINE__);

      $a = mysqli_fetch_row($r);

      if ($a && $a[0] >= $lastpostid)
        $img = "unlocked";
      else
        $img = "unlockednew";
    }
    else
    {
      $lastpost = "N/A";
      $img = "unlocked";
    }

    print("<tr><td class=lista align=left><table border=0 cellspacing=0 cellpadding=0><tr><td class=embedded style='padding-right: 5px'>".
    image_or_link("$STYLEPATH/$img.png","",$img)."</td><td class=embedded><a href=?action=viewforum&forumid=$forumid><b>$forumname</b></a><br>\n" .
    "$forumdescription</td></tr></table></td><td class=lista align=center>$topiccount</td></td><td class=lista align=center>$postcount</td>" .
    "<td class=lista align=center>$lastpost</td></tr>\n");
  }

  print("</table>\n");

  print("<p align=center><a href=?action=search><b>".SEARCH."</b></a> | <a href=?action=viewunread><b>".VIEW_UNREAD."</b></a> | <a href=?catchup><b>".CATCHUP."</b></a></p>");

  block_end();

  stdfoot();
?>
