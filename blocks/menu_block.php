<?php
global $CURUSER;

   block_begin(BLOCK_MENU);

   print("<tr><td class=blocklist align=center><a href=./>".MNU_INDEX."</a></td></tr>\n");

   if ($CURUSER["view_torrents"]=="yes")
      {
      print("<tr><td class=blocklist align=center><a href=torrents.php>".MNU_TORRENT."</a></td></tr>\n");
      print("<tr><td class=blocklist align=center><a href=extra-stats.php>".MNU_STATS."</a></td></tr>\n");
      }
   if ($CURUSER["can_upload"]=="yes")
      print("<tr><td class=blocklist align=center><a href=upload.php>".MNU_UPLOAD."</a></td>\n");
   if ($CURUSER["view_users"]=="yes")
      print("<tr><td class=blocklist align=center><a href=users.php>".MNU_MEMBERS."</a></td></tr>\n");
   if ($CURUSER["view_news"]=="yes")
      print("<tr><td class=blocklist align=center><a href=viewnews.php>".MNU_NEWS."</a></td></tr>\n");
   if ($CURUSER["view_forum"]=="yes")
      {
        if ($GLOBALS["FORUMLINK"]=="" || $GLOBALS["FORUMLINK"]=="internal")
           print("<td class=blocklist align=center><a href=forum.php>".MNU_FORUM."</a></td>\n");
        else
            print("<td class=blocklist align=center><a href=$GLOBALS[FORUMLINK] target=_blank>".MNU_FORUM."</a></td>\n");
      }
   if ($CURUSER["uid"]==1 || !$CURUSER)
      print("<tr><td class=blocklist align=center><a href=login.php>".LOGIN."</a></td></tr>\n");
   else
       $salty = md5("SomeRandomTextYouWant".$CURUSER['username'].""); 
       print("<tr><td class=blocklist align=center><a href=logout.php?check_hash=$salty>".LOGOUT."</a></td></tr>\n");

   block_end();
?>