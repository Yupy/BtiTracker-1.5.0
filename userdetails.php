<?php
require_once ("include/functions.php");
require_once ("include/config.php");

dbconn();

standardheader('User Details');

block_begin(USER_DETAILS);

$id = intval(0+$_GET["id"]);

if (!isset($_GET["returnto"]))
    $_GET["returnto"] = "";

$link = rawurlencode($_GET["returnto"]);

if ($CURUSER["view_users"]!="yes")
{
    err_msg(ERROR,NOT_AUTHORIZED." ".MEMBERS);
    block_end();
    stdfoot();
    die();
}

if ($id>1)
{
   $res = $db->execute("
                            SELECT 
							    users.avatar, 
								users.email, 
								users.cip, 
								users.username, 
								UNIX_TIMESTAMP(users.joined) 
							AS 
							    joined, 
								UNIX_TIMESTAMP(users.lastconnect) 
							AS 
							    lastconnect, 
								users_level.level, 
								users.flag, 
								countries.name, 
								countries.flagpic, 
								users.pid, 
								users.time_offset 
							FROM 
							    users 
							INNER JOIN 
							    users_level 
							ON 
							    users_level.id = users.id_level 
							LEFT JOIN 
							    countries 
							ON 
							    users.flag = countries.id 
							WHERE users.id = ".$db->escape_string($id)) or $db->display_errors();
   $num = $db->count_select($res);

    if ($num == 0)
    {
        err_msg(ERROR,BAD_ID);
        block_end();
        stdfoot();
        die();
    }
    else
    {
        $row = $db->fetch_array($res);
    }
} else {
    err_msg(ERROR,BAD_ID);
    block_end();
    stdfoot();
    die();
}

$utorrents = intval($CURUSER["torrentsperpage"]);

if (($user_stats = $Memcached->get_value('user::stats::'.$id)) === false) {
    $stats_sql = $db->execute('SELECT uploaded, downloaded FROM users WHERE id = '.$db->escape_string($id)) or $db->display_errors();
    $user_stats = $db->fetch_assoc($stats_sql);

    $user_stats['uploaded'] = (float)$user_stats['uploaded'];
    $user_stats['downloaded'] = (float)$user_stats['downloaded'];
    $Memcached->cache_value('user::stats::'.$id, $user_stats, 3600);
}

print("<table class='lista' width='100%'>\n");
print("<tr>\n<td class='header'>".USER_NAME."</td>\n<td class='lista'>".unesc($row["username"])."&nbsp;&nbsp;&nbsp;");
if ($CURUSER["uid"]>1 && $id!=$CURUSER["uid"])
   print("<a href='usercp.php?do=pm&action=edit&uid=".$CURUSER["uid"]."&what=new&to=".urlencode(unesc($row["username"]))."'>".image_or_link("".$STYLEPATH."/pm.png","","PM")."</a>\n");
if ($CURUSER["edit_users"]=="yes" && $id!=$CURUSER["uid"])
  print("\n&nbsp;&nbsp;&nbsp<a href='account.php?act=mod&uid=$id&returnto=userdetails.php?id=".$id."'>".image_or_link("".$STYLEPATH."/edit.png","",EDIT)."</a>");
if ($CURUSER["delete_users"]=="yes" && $id!=$CURUSER["uid"])
  print("\n&nbsp;&nbsp;&nbsp<a onclick=\"return confirm('".AddSlashes(DELETE_CONFIRM)."')\" href='account.php?act=del&uid=".$id."&returnto=users.php'>".image_or_link("".$STYLEPATH."/delete.png","",DELETE)."</a>");
print("</td>");
if ($row["avatar"] && $row["avatar"]!="")
   print("<td class='lista' align='center' valign='middle' rowspan='4'><img border='0' width='120' height='170' src='".htmlsafechars($row["avatar"])."' /></td>");
print("</tr>");
if ($CURUSER["edit_users"]=="yes" || $CURUSER["admin_access"]=="yes")
{
  print("<tr>\n<td class='header'>".EMAIL."</td>\n<td class='lista'><a href='mailto:".$row["email"]."'>".$row["email"]."</a></td></tr>\n");
  print("<tr>\n<td class='header'>".LAST_IP."</td>\n<td class='lista'>".($row["cip"])."</td></tr>\n");
  print("<tr>\n<td class='header'>".USER_LEVEL."</td>\n<td class='lista'>".$row['level']."</td></tr>\n");
  $colspan = "colspan='2'";
}
else
{
  print("<tr>\n<td class='header'>".USER_LEVEL."</td>\n<td class='lista'>".$row['level']."</td></tr>\n");
  $colspan="";
}
print("<tr>\n<td class='header'>".USER_JOINED."</td>\n<td class='lista' ".$colspan.">".($row["joined"]==0 ? "N/A" : get_date_time($row["joined"]))."</td></tr>\n");
print("<tr>\n<td class='header'>".USER_LASTACCESS."</td>\n<td class='lista' ".$colspan.">".($row["lastconnect"]==0 ? "N/A" : get_date_time($row["lastconnect"]))."</td></tr>\n");
// flag hack
print("<tr>\n<td class='header'>".PEER_COUNTRY."</td>\n<td class='lista' colspan='2'>".($row["flag"]==0 ? "":unesc($row['name']))."&nbsp;&nbsp;<img src='images/flag/".(!$row["flagpic"] || $row["flagpic"]==""?"unknown.gif":$row["flagpic"])."' alt='".($row["flag"]==0 ? "Unknown":unesc($row['name']))."' /></td></tr>\n");

// user's local time
if ($db->get_date('I', $db->get_time()) == 1)
{
    $tzu = ($db->get_date('Z', $db->get_time()) - 3600);
} else {
    $tzu = $db->get_date('Z', $db->get_time());
}

$offsetu = $tzu-($row["time_offset"]*3600);
print("<tr>\n<td class='header'>".USER_LOCAL_TIME."</td>\n<td class='lista' colspan='2'>".$db->get_date("d/m/Y H:i:s", $db->get_time()-$offsetu)."&nbsp;(GMT".($row["time_offset"]>0?" +".$row["time_offset"]:($row["time_offset"]==0?"":" ".$row["time_offset"])).")</td></tr>\n");
// end user's local time
print("<tr>\n<td class='header'>".DOWNLOADED."</td>\n<td class='lista' colspan='2'>".makesize($user_stats["downloaded"])."</td></tr>\n");
print("<tr>\n<td class='header'>".UPLOADED."</td>\n<td class='lista' colspan='2'>".makesize($user_stats["uploaded"])."</td></tr>\n");
if (intval($user_stats["downloaded"])>0)
 {
   $sr = $user_stats["uploaded"] / $user_stats["downloaded"];
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
  $ratio = number_format($sr,2)."&nbsp;&nbsp;<img src='".$s."'>";
 }
else
   $ratio = "&infin;";

print("<tr>\n<td class=\"header\">".RATIO."</td>\n<td class=\"lista\" colspan=\"2\">".$ratio."</td></tr>\n");
// Only show if forum is internal
if ( $GLOBALS["FORUMLINK"] == '' || $GLOBALS["FORUMLINK"] == 'internal' )
{
   $sql = $db->execute("
                            SELECT 
							    * 
							FROM 
							    posts 
							INNER JOIN 
						        users 
							ON 
							    posts.userid = users.id 
							WHERE users.id = " . $id) or $db->display_errors();
   $posts = $db->count_select($sql);

   $memberdays = max(1, round( ( $db->get_time() - $row['joined'] ) / 86400 ));
   $posts_per_day = number_format(round($posts / $memberdays,2),2);
   print("<tr>\n<td class=\"header\"><b>".FORUM." ".POSTS.":</b></td>\n<td class=\"lista\" colspan=\"2\">" . $posts . " &nbsp; [" . sprintf(POSTS_PER_DAY, $posts_per_day) . "]</td></tr>\n");
}
print("</table>");

block_begin(UPLOADED." ".MNU_TORRENT);
$resuploaded = $db->execute("
                                    SELECT 
									    namemap.info_hash 
									FROM 
									    namemap 
									INNER JOIN 
									    summary 
									ON 
									    namemap.info_hash = summary.info_hash 
									WHERE 
									    uploader = ".$id." 
									AND 
									    namemap.anonymous = \"false\" 
									ORDER BY data DESC") or $db->display_errors();
$numtorrent = $db->count_select($resuploaded);

if ($numtorrent > 0)
{
   list($pagertop, $pagerbottom, $limit) = pager(($utorrents==0?15:$utorrents), $numtorrent, $_SERVER["PHP_SELF"]."?id=".$id."&");
   
   print("".$pagertop."");
   
   $resuploaded = $db->execute("
                                        SELECT 
										    namemap.info_hash, 
											namemap.filename, 
											UNIX_TIMESTAMP(namemap.data) 
										AS 
										    added, 
											namemap.size, 
											summary.seeds, 
											summary.leechers, 
											summary.finished 
										FROM 
										    namemap 
										INNER JOIN 
										    summary 
										ON 
										    namemap.info_hash = summary.info_hash 
										WHERE 
										    uploader = ".$id." 
										AND 
										    namemap.anonymous = \"false\" 
										ORDER BY data DESC ".$limit) or $db->display_errors();
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
if ($resuploaded && $db->count_select($resuploaded)>0)
   {
   while ($rest = $db->fetch_array($resuploaded))
         {
            print("\n<tr>\n<td class=\"lista\"><a href=details.php?id=".$rest{"info_hash"}.">".unesc($rest["filename"])."</td>");
            include("include/offset.php");
            print("\n<td class=\"lista\" align=\"center\">".$db->get_date("d/m/Y", $rest["added"]-$offset)."</td>");
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
block_begin("Active Torrents");
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
    $anq = $db->execute("
	                            SELECT 
								    peers.ip 
								FROM 
								    peers 
								INNER JOIN 
								    namemap 
								ON 
								    namemap.info_hash = peers.infohash 
								INNER JOIN 
								    summary 
								ON 
								    summary.info_hash = peers.infohash
                                WHERE peers.pid = '".$row["pid"]."'") or $db->display_errors();
else
    $anq = $db->execute("
	                            SELECT 
								    peers.ip 
								FROM 
								    peers 
								INNER JOIN 
								    namemap 
								ON 
								    namemap.info_hash = peers.infohash 
								INNER JOIN 
								    summary 
								ON 
								    summary.info_hash = peers.infohash
                                WHERE peers.ip = '".($row["cip"])."'") or $db->display_errors();

if ($db->count_select($anq)>0)
   {
    list($pagertop, $pagerbottom, $limit) = pager(($utorrents==0?15:$utorrents), $db->count_select($anq), $_SERVER["PHP_SELF"]."?id=".$id."&",array("pagename" => "activepage"));
    
	if ($PRIVATE_ANNOUNCE)
        $anq = $db->execute("
		                            SELECT 
									    peers.ip, 
										peers.infohash, 
										namemap.filename, 
										namemap.size, 
										peers.status, 
										peers.downloaded, 
										peers.uploaded, 
										summary.seeds, 
										summary.leechers, 
										summary.finished
                                    FROM 
									    peers 
									INNER JOIN 
									    namemap 
									ON 
									    namemap.info_hash = peers.infohash 
									INNER JOIN 
									    summary 
									ON 
									    summary.info_hash = peers.infohash
                                    WHERE 
									    peers.pid = '".$row["pid"]."' 
									ORDER BY peers.status DESC ".$limit) or $db->display_errors();
    else
        $anq = $db->execute("
		                            SELECT 
									    peers.ip, 
										peers.infohash, 
										namemap.filename, 
										namemap.size, 
										peers.status, 
										peers.downloaded, 
										peers.uploaded, 
										summary.seeds, 
										summary.leechers, 
										summary.finished
                                    FROM 
									    peers 
									INNER JOIN 
									    namemap 
									ON 
									    namemap.info_hash = peers.infohash 
									INNER JOIN 
									   summary 
								    ON 
									    summary.info_hash = peers.infohash
                                    WHERE 
									    peers.ip = '".($row["cip"])."' 
									ORDER BY peers.status DESC ".$limit) or $db->display_errors();
    print("<div align=\"center\">$pagertop</div>");
    while ($torlist = $db->fetch_object($anq))
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
   } else print("<tr>\n<td class='lista' align='center' colspan='9'>No active torrents for this user</td>\n</tr>\n</table>");
block_end(); // end active torrents

// history - completed torrents by this user
block_begin("History (Snatched Torrents)");
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

$anq->free();
$anq = $db->execute("
                            SELECT 
							    history.uid 
							FROM 
							    history 
							INNER JOIN 
							    namemap 
							ON 
							    history.infohash = namemap.info_hash 
							WHERE 
							    history.uid = ".$id." 
							AND 
							    history.date IS NOT NULL 
							ORDER BY date DESC") or $db->display_errors();

if ($db->count_select($anq)>0)
   {
    list($pagertop, $pagerbottom, $limit) = pager(($utorrents==0?15:$utorrents), $db->count_select($anq), $_SERVER["PHP_SELF"]."?id=".$id."&",array("pagename" => "historypage"));
    
	$anq = $db->execute("
	                            SELECT 
								    namemap.filename, 
									namemap.size, 
									namemap.info_hash, 
									history.active, 
									history.agent, 
									history.downloaded, 
									history.uploaded, 
									summary.seeds, 
									summary.leechers, 
									summary.finished
                                FROM 
								    history 
								INNER JOIN 
								    namemap 
								ON 
								    history.infohash = namemap.info_hash 
								INNER JOIN 
								    summary 
								ON 
								    summary.info_hash = namemap.info_hash 
								WHERE 
								    history.uid = ".$id." 
								AND 
								    history.date IS NOT NULL 
								ORDER BY date DESC ".$limit) or $db->display_errors();

    print("<div align=\"center\">".$pagertop."</div>");

    while ($torlist = $db->fetch_object($anq))
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
                     $peerratio="&infin;";
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
