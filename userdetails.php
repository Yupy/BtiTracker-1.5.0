<?php
require_once ("include/functions.php");
require_once ("include/config.php");

dbconn();

standardheader('User Details');

block_begin(USER_DETAILS);

$id=intval(0+$_GET["id"]);
if (!isset($_GET["returnto"])) $_GET["returnto"] = "";
$link=rawurlencode($_GET["returnto"]);

if ($CURUSER["view_users"]!="yes")
   {
       err_msg(ERROR,NOT_AUTHORIZED." ".MEMBERS);
       block_end();
       stdfoot();
       die();
   }
if ($id>1) {
   $res=run_query("SELECT users.avatar,users.email,users.cip,users.username,users.downloaded,users.uploaded,UNIX_TIMESTAMP(users.joined) as joined,UNIX_TIMESTAMP(users.lastconnect) as lastconnect,users_level.level, users.flag, countries.name, countries.flagpic, users.pid, users.time_offset FROM users INNER JOIN users_level ON users_level.id=users.id_level LEFT JOIN countries ON users.flag=countries.id WHERE users.id=$id");
   $num=mysqli_num_rows($res);
   if ($num==0)
      {
       err_msg(ERROR,BAD_ID);
       block_end();
       stdfoot();
       die();
       }
   else {
        $row=mysqli_fetch_array($res);
      }
}
else
      {
       err_msg(ERROR,BAD_ID);
       block_end();
       stdfoot();
       die();
       }

$utorrents = intval($CURUSER["torrentsperpage"]);

print("<table class=lista width=100%>\n");
print("<tr>\n<td class=header>".USER_NAME."</td>\n<td class=lista>".unesc($row["username"])."&nbsp;&nbsp;&nbsp;");
if ($CURUSER["uid"]>1 && $id!=$CURUSER["uid"])
   print("<a href=usercp.php?do=pm&action=edit&uid=".$CURUSER["uid"]."&what=new&to=".urlencode(unesc($row["username"])).">".image_or_link("$STYLEPATH/pm.png","","PM")."</a>\n");
if ($CURUSER["edit_users"]=="yes" && $id!=$CURUSER["uid"])
  print("\n&nbsp;&nbsp;&nbsp<a href=account.php?act=mod&uid=$id&returnto=userdetails.php?id=$id>".image_or_link("$STYLEPATH/edit.png","",EDIT)."</a>");
if ($CURUSER["delete_users"]=="yes" && $id!=$CURUSER["uid"])
  print("\n&nbsp;&nbsp;&nbsp<a onclick=\"return confirm('".AddSlashes(DELETE_CONFIRM)."')\" href=account.php?act=del&uid=$id&returnto=users.php>".image_or_link("$STYLEPATH/delete.png","",DELETE)."</a>");
print("</td>");
if ($row["avatar"] && $row["avatar"]!="")
   print("<td class=lista align=center valign=middle rowspan=4><img border=0 src=".htmlsafechars($row["avatar"])." /></td>");
print("</tr>");
if ($CURUSER["edit_users"]=="yes" || $CURUSER["admin_access"]=="yes")
{
  print("<tr>\n<td class=\"header\">".EMAIL."</td>\n<td class=\"lista\"><a href=\"mailto:".$row["email"]."\">".$row["email"]."</a></td></tr>\n");
  print("<tr>\n<td class=\"header\">".LAST_IP."</td>\n<td class=\"lista\">".($row["cip"])."</td></tr>\n");
  print("<tr>\n<td class=\"header\">".USER_LEVEL."</td>\n<td class=\"lista\">$row[level]</td></tr>\n");
  $colspan=" colspan=2";
}
else
{
  print("<tr>\n<td class=\"header\">".USER_LEVEL."</td>\n<td class=\"lista\">$row[level]</td></tr>\n");
  $colspan="";
}
print("<tr>\n<td class=\"header\">".USER_JOINED."</td>\n<td class=lista$colspan>".($row["joined"]==0 ? "N/A" : get_date_time($row["joined"]))."</td></tr>\n");
print("<tr>\n<td class=\"header\">".USER_LASTACCESS."</td>\n<td class=lista$colspan>".($row["lastconnect"]==0 ? "N/A" : get_date_time($row["lastconnect"]))."</td></tr>\n");
// flag hack
print("<tr>\n<td class=\"header\">".PEER_COUNTRY."</td>\n<td class=\"lista\" colspan=\"2\">".($row["flag"]==0 ? "":unesc($row['name']))."&nbsp;&nbsp;<img src=images/flag/".(!$row["flagpic"] || $row["flagpic"]==""?"unknown.gif":$row["flagpic"])." alt=\"".($row["flag"]==0 ? "unknown":unesc($row['name']))."\" /></td></tr>\n");
// user's local time
if (date('I',time())==1) {
    $tzu=(date('Z',time())-3600);
} else {
    $tzu=date('Z',time());
}
$offsetu=$tzu-($row["time_offset"]*3600);
print("<tr>\n<td class=\"header\">".USER_LOCAL_TIME."</td>\n<td class=\"lista\" colspan=\"2\">".date("d/m/Y H:i:s",time()-$offsetu)."&nbsp;(GMT".($row["time_offset"]>0?" +".$row["time_offset"]:($row["time_offset"]==0?"":" ".$row["time_offset"])).")</td></tr>\n");
// end user's local time
print("<tr>\n<td class=\"header\">".DOWNLOADED."</td>\n<td class=\"lista\" colspan=\"2\">".makesize($row["downloaded"])."</td></tr>\n");
print("<tr>\n<td class=\"header\">".UPLOADED."</td>\n<td class=\"lista\" colspan=\"2\">".makesize($row["uploaded"])."</td></tr>\n");
if (intval($row["downloaded"])>0)
 {
   $sr = $row["uploaded"]/$row["downloaded"];
   if ($sr >= 4)
     $s = "images/smilies/thumbsup.gif";
   else if ($sr >= 2)
     $s = "images/smilies/grin.gif";
   else if ($sr >= 1)
     $s = "images/smilies/smile1.gif";
   else if ($sr >= 0.5)
     $s = "images/smilies/noexpression.gif";
   else if ($sr >= 0.25)
     $s = "images/smilies/sad.gif";
   else
     $s = "images/smilies/thumbsdown.gif";
  $ratio=number_format($sr,2)."&nbsp;&nbsp;<img src=$s>";
 }
else
   $ratio="&infin;";

print("<tr>\n<td class=\"header\">".RATIO."</td>\n<td class=\"lista\" colspan=\"2\">$ratio</td></tr>\n");
// Only show if forum is internal
if ( $GLOBALS["FORUMLINK"] == '' || $GLOBALS["FORUMLINK"] == 'internal' )
   {
   $sql = run_query("SELECT * FROM posts INNER JOIN users ON posts.userid = users.id WHERE users.id = " . $id);
   $posts = mysqli_num_rows($sql);
   $memberdays = max(1, round( ( time() - $row['joined'] ) / 86400 ));
   $posts_per_day = number_format(round($posts / $memberdays,2),2);
   print("<tr>\n<td class=\"header\"><b>".FORUM." ".POSTS.":</b></td>\n<td class=\"lista\" colspan=\"2\">" . $posts . " &nbsp; [" . sprintf(POSTS_PER_DAY, $posts_per_day) . "]</td></tr>\n");
}
print("</table>");

block_begin(UPLOADED." ".MNU_TORRENT);
$resuploaded = run_query("SELECT namemap.info_hash FROM namemap INNER JOIN summary ON namemap.info_hash=summary.info_hash WHERE uploader=$id AND namemap.anonymous = \"false\" ORDER BY data DESC");
$numtorrent=mysqli_num_rows($resuploaded);
if ($numtorrent>0)
   {
   list($pagertop, $pagerbottom, $limit) = pager(($utorrents==0?15:$utorrents), $numtorrent, $_SERVER["PHP_SELF"]."?id=$id&");
   print("$pagertop");
   $resuploaded = run_query("SELECT namemap.info_hash, namemap.filename, UNIX_TIMESTAMP(namemap.data) as added, namemap.size, summary.seeds, summary.leechers, summary.finished FROM namemap INNER JOIN summary ON namemap.info_hash=summary.info_hash WHERE uploader=$id AND namemap.anonymous = \"false\" ORDER BY data DESC $limit");
}
?>
<TABLE width="100%" class="lista">
<!-- Column Headers  -->
<TR>
<TD align="center" class="header"><?php echo FILE; ?></TD>
<TD align="center" class="header"><?php echo ADDED; ?></TD>
<TD align="center" class="header"><?php echo SIZE; ?></TD>
<TD align="center" class="header"><?php echo SHORT_S; ?></TD>
<TD align="center" class="header"><?php echo SHORT_L; ?></TD>
<TD align="center" class="header"><?php echo SHORT_C; ?></TD>
</TR>
<?php
if ($resuploaded && mysqli_num_rows($resuploaded)>0)
   {
   while ($rest=mysqli_fetch_array($resuploaded))
         {
            print("\n<tr>\n<td class=\"lista\"><a href=details.php?id=".$rest{"info_hash"}.">".unesc($rest["filename"])."</td>");
            include("include/offset.php");
            print("\n<td class=\"lista\" align=\"center\">".date("d/m/Y",$rest["added"]-$offset)."</td>");
            print("\n<td class=\"lista\" align=\"center\">".makesize($rest["size"])."</td>");
            print("\n<td align=\"center\" class=\"".linkcolor($rest["seeds"])."\"><a href=peers.php?id=".$rest{"info_hash"}.">".$rest["seeds"]."</td>");
            print("\n<td align=\"center\" class=\"".linkcolor($rest["leechers"])."\"><a href=peers.php?id=".$rest{"info_hash"}.">".$rest["leechers"]."</td>");
            if ($rest["finished"]>0)
               print("\n<td align=\"center\" class=\"lista\"><a href=torrent_history.php?id=".$rest["info_hash"].">" . $rest["finished"] . "</a></td>");
            else
                print ("\n<td align=\"center\" class=\"lista\">---</td>");
        }
        print("\n</table>");
   }
else
    {
    print("<tr>\n<td class=\"lista\" align=\"center\" colspan=\"6\">".NO_TORR_UP_USER."</td>\n</tr>\n</table>");
    }
block_end(); // end uploaded torrents

// active torrents begin - hack by petr1fied - modified by Lupin 20/10/05
block_begin("Active torrents");
?>
<TABLE width="100%" class="lista">
<!-- Column Headers  -->
<TR>
<TD align="center" class="header"><?php echo FILE; ?></TD>
<TD align="center" class="header"><?php echo SIZE; ?></TD>
<TD align="center" class="header"><?php echo PEER_STATUS; ?></TD>
<TD align="center" class="header"><?php echo DOWNLOADED; ?></TD>
<TD align="center" class="header"><?php echo UPLOADED; ?></TD>
<TD align="center" class="header"><?php echo RATIO; ?></TD>
<TD align="center" class="header">S</TD>
<TD align="center" class="header">L</TD>
<TD align="center" class="header">C</TD>
</TR>
<?php

if ($PRIVATE_ANNOUNCE)
    $anq=run_query("SELECT peers.ip FROM peers INNER JOIN namemap ON namemap.info_hash = peers.infohash INNER JOIN summary ON summary.info_hash = peers.infohash
                WHERE peers.pid='".$row["pid"]."'");
else
    $anq=run_query("SELECT peers.ip FROM peers INNER JOIN namemap ON namemap.info_hash = peers.infohash INNER JOIN summary ON summary.info_hash = peers.infohash
                WHERE peers.ip='".($row["cip"])."'");

if (mysqli_num_rows($anq)>0)
   {
    list($pagertop, $pagerbottom, $limit) = pager(($utorrents==0?15:$utorrents), mysqli_num_rows($anq), $_SERVER["PHP_SELF"]."?id=$id&",array("pagename" => "activepage"));
    if ($PRIVATE_ANNOUNCE)
        $anq=run_query("SELECT peers.ip, peers.infohash, namemap.filename, namemap.size, peers.status, peers.downloaded, peers.uploaded, summary.seeds, summary.leechers, summary.finished
                    FROM peers INNER JOIN namemap ON namemap.info_hash = peers.infohash INNER JOIN summary ON summary.info_hash = peers.infohash
                    WHERE peers.pid='".$row["pid"]."' ORDER BY peers.status DESC $limit");
    else
        $anq=run_query("SELECT peers.ip, peers.infohash, namemap.filename, namemap.size, peers.status, peers.downloaded, peers.uploaded, summary.seeds, summary.leechers, summary.finished
                    FROM peers INNER JOIN namemap ON namemap.info_hash = peers.infohash INNER JOIN summary ON summary.info_hash = peers.infohash
                    WHERE peers.ip='".($row["cip"])."' ORDER BY peers.status DESC $limit");
    print("<div align=\"center\">$pagertop</div>");
    while ($torlist = mysqli_fetch_object($anq))
        {
         if ($torlist->ip !="")
           {
                 print("\n<tr>\n<td class=\"lista\"><a href=\"details.php?id=".$torlist->infohash."\">".unesc($torlist->filename)."</td>");
                 print("\n<td class=\"lista\" align=\"center\">".makesize($torlist->size)."</td>");
                 print("\n<td align=\"center\" class=\"lista\">".unesc($torlist->status)."</td>");
                 print("\n<td align=\"center\" class=\"lista\">".makesize($torlist->downloaded)."</td>");
                 print("\n<td align=\"center\" class=\"lista\">".makesize($torlist->uploaded)."</td>");
                 if ($torlist->downloaded>0)
                      $peerratio=number_format($torlist->uploaded/$torlist->downloaded,2);
                 else
                      $peerratio="&infin;";
                 print("\n<td align=\"center\" class=\"lista\">".unesc($peerratio)."</td>");
                 print("\n<td align=\"center\" class=\"".linkcolor($torlist->seeds)."\"><a href=\"peers.php?id=".$torlist->infohash."\">".$torlist->seeds."</td>");
                 print("\n<td align=\"center\" class=\"".linkcolor($torlist->leechers)."\"><a href=\"peers.php?id=".$torlist->infohash."\">".$torlist->leechers."</td>");
                 print("\n<td align=\"center\" class=\"lista\"><a href=\"torrent_history.php?id=".$torlist->infohash."\">".$torlist->finished."</td>\n</tr>");
         }
        }
          print("\n</table>");
   } else print("<tr>\n<td class=lista align=center colspan=9>No active torrents for this user</td>\n</tr>\n</table>");
block_end(); // end active torrents

// history - completed torrents by this user
block_begin("History (snatched torrents)");
?>
<TABLE width="100%" class="lista">
<!-- Column Headers  -->
<TR>
<TD align="center" class="header"><?php echo FILE; ?></TD>
<TD align="center" class="header"><?php echo SIZE; ?></TD>
<TD align="center" class="header"><?php echo PEER_CLIENT; ?></TD>
<TD align="center" class="header"><?php echo PEER_STATUS; ?></TD>
<TD align="center" class="header"><?php echo DOWNLOADED; ?></TD>
<TD align="center" class="header"><?php echo UPLOADED; ?></TD>
<TD align="center" class="header"><?php echo RATIO; ?></TD>
<TD align="center" class="header">S</TD>
<TD align="center" class="header">L</TD>
<TD align="center" class="header">C</TD>
</TR>
<?php
((mysqli_free_result($anq) || (is_object($anq) && (get_class($anq) == "mysqli_result"))) ? true : false);
$anq=run_query("SELECT history.uid FROM history INNER JOIN namemap ON history.infohash=namemap.info_hash WHERE history.uid=$id AND history.date IS NOT NULL ORDER BY date DESC");

if (mysqli_num_rows($anq)>0)
   {
    list($pagertop, $pagerbottom, $limit) = pager(($utorrents==0?15:$utorrents), mysqli_num_rows($anq), $_SERVER["PHP_SELF"]."?id=$id&",array("pagename" => "historypage"));
    $anq=run_query("SELECT namemap.filename, namemap.size, namemap.info_hash, history.active, history.agent, history.downloaded, history.uploaded, summary.seeds, summary.leechers, summary.finished
    FROM history INNER JOIN namemap ON history.infohash=namemap.info_hash INNER JOIN summary ON summary.info_hash=namemap.info_hash WHERE history.uid=$id AND history.date IS NOT NULL ORDER BY date DESC $limit");
    print("<div align=\"center\">$pagertop</div>");
    while ($torlist = mysqli_fetch_object($anq))
        {
                print("\n<tr>\n<td class=\"lista\"><a href=\"details.php?id=".$torlist->info_hash."\">".unesc($torlist->filename)."</td>");
                print("\n<td class=\"lista\" align=\"center\">".makesize($torlist->size)."</td>");
                print("\n<td class=\"lista\" align=\"center\">".htmlsafechars($torlist->agent)."</td>");
                print("\n<td align=\"center\" class=\"lista\">".($torlist->active=='yes'?ACTIVATED:'Stopped')."</td>");
                print("\n<td align=\"center\" class=\"lista\">".makesize($torlist->downloaded)."</td>");
                print("\n<td align=\"center\" class=\"lista\">".makesize($torlist->uploaded)."</td>");
                if ($torlist->downloaded>0)
                     $peerratio=number_format($torlist->uploaded/$torlist->downloaded,2);
                else
                     $peerratio="oo";
                print("\n<td align=\"center\" class=\"lista\">".unesc($peerratio)."</td>");
                print("\n<td align=\"center\" class=\"".linkcolor($torlist->seeds)."\"><a href=\"peers.php?id=".$torlist->info_hash."\">".$torlist->seeds."</td>");
                print("\n<td align=\"center\" class=\"".linkcolor($torlist->leechers)."\"><a href=\"peers.php?id=".$torlist->info_hash."\">".$torlist->leechers."</td>");
                print("\n<td align=\"center\" class=\"lista\"><a href=\"torrent_history.php?id=".$torlist->info_hash."\">".$torlist->finished."</td>\n</tr>");
        }
          print("\n</table>");
   } else print("<tr>\n<td class=\"lista\" align=\"center\" colspan=\"10\">No history for this user</td>\n</tr>\n</table>");
block_end(); // end history

print("<br /><br /><center><a href=\"javascript: history.go(-1);\">".BACK."</a></center><br />\n");
block_end();
stdfoot();

?>