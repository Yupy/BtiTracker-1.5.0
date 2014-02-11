
    <table class="lista" width="100%">
    <tr>
<?php

   global $CURUSER;

if (!$CURUSER)
   {
       // anonymous=guest
   print("<td class=header align=center>".WELCOME." ".GUEST."\n");
   print("<a href=login.php>(".LOGIN.")</a></td>");
   }
elseif ($CURUSER["uid"]==1)
       // anonymous=guest
    {
   print("<td class=header align=center>".WELCOME." " . $CURUSER["username"] ." \n");
   print("<a href=login.php>(".LOGIN.")</a></td>\n");
    }
else
    {
    print("<td class=header align=center>".WELCOME_BACK." " . $CURUSER["username"] ." \n"); 
	$salty = md5("SomeRandomTextYouWant".$CURUSER['username'].""); 
    print("<a href=logout.php?check_hash=$salty>(".LOGOUT.")</a></td>\n");
    }

print("<td class=header align=center><a href=./>".MNU_INDEX."</a></td>\n");

if ($CURUSER["view_torrents"]=="yes")
    {
    print("<td class=header align=center><a href=torrents.php>".MNU_TORRENT."</a></td>\n");
    print("<td class=header align=center><a href=extra-stats.php>".MNU_STATS."</a></td>\n");
   }
if ($CURUSER["can_upload"]=="yes")
   print("<td class=header align=center><a href=upload.php>".MNU_UPLOAD."</a></td>\n");
if ($CURUSER["view_users"]=="yes")
   print("<td class=header align=center><a href=users.php>".MNU_MEMBERS."</a></td>\n");
if ($CURUSER["view_news"]=="yes")
   print("<td class=header align=center><a href=viewnews.php>".MNU_NEWS."</a></td>\n");
if ($CURUSER["view_forum"]=="yes")
   {
   if ($GLOBALS["FORUMLINK"]=="" || $GLOBALS["FORUMLINK"]=="internal")
      print("<td class=header align=center><a href=forum.php>".MNU_FORUM."</a></td>\n");
   else
       print("<td class=header align=center><a href=$GLOBALS[FORUMLINK] target=_blank>".MNU_FORUM."</a></td>\n");
    }

?>
   </tr>
   </table>