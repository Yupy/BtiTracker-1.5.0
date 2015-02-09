<?php
/*
* BtiTracker v1.5.0 is a php tracker system for BitTorrent, easy to setup and configure.
* This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
* Updated and Maintained by Yupy.
* Copyright (C) 2004-2014 Btiteam.org
*/
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'include'.DIRECTORY_SEPARATOR.'functions.php');

dbconn();

standardheader('User Details');

block_begin(USER_DETAILS);

$id = intval(0 + $_GET["id"]);

if (!isset($_GET["returnto"]))
    $_GET["returnto"] = '';

$link = rawurlencode($_GET["returnto"]);

if (user::$current["view_users"] != "yes")
{
    err_msg(ERROR,NOT_AUTHORIZED." ".MEMBERS);
    block_end();
    stdfoot();
    die();
}

if ($id > 1)
{
    $res = $db->query("SELECT users.avatar, users.email, users.cip, users.username, users.downloaded, users.uploaded, UNIX_TIMESTAMP(users.joined) AS joined, UNIX_TIMESTAMP(users.lastconnect) AS lastconnect, users_level.level, users.flag, countries.name, countries.flagpic, users.pid, users.time_offset FROM users INNER JOIN users_level ON users_level.id = users.id_level LEFT JOIN countries ON users.flag = countries.id WHERE users.id = " . $id);
    $num = $res->num_rows;

    if ($num == 0)
    {
        err_msg(ERROR,BAD_ID);
        block_end();
        stdfoot();
        die();
    } else {
        $row = $res->fetch_array(MYSQLI_BOTH);
    }
} else {
    err_msg(ERROR,BAD_ID);
    block_end();
    stdfoot();
    die();
}

$utorrents = user::$current["torrentsperpage"];

print("<table class='lista' width='100%'>\n");
print("<tr>\n<td class='header'>" . USER_NAME . "</td>\n<td class='lista'>" . security::html_safe(unesc($row["username"])) . "&nbsp;&nbsp;&nbsp;");

if (user::$current["uid"] > 1 && $id != user::$current["uid"])
    print("<a href='usercp.php?do=pm&amp;action=edit&amp;uid=" . user::$current["uid"] . "&amp;what=new&amp;to=" . urlencode(unesc($row["username"])) . "'>" . image_or_link($STYLEPATH . "/pm.png","","PM") . "</a>\n");

if (user::$current["edit_users"] == "yes" && $id != user::$current["uid"])
    print("\n&nbsp;&nbsp;&nbsp<a href='account.php?act=mod&amp;uid=" . $id . "&amp;returnto=userdetails.php?id=" . $id . "'>".image_or_link($STYLEPATH."/edit.png","",EDIT)."</a>");

if (user::$current["delete_users"] == "yes" && $id != user::$current["uid"])
  print("\n&nbsp;&nbsp;&nbsp<a onclick='return confirm('".AddSlashes(DELETE_CONFIRM)."')' href='account.php?act=del&uid=".$id."&returnto=users.php'>".image_or_link($STYLEPATH."/delete.png","",DELETE)."</a>");

print("</td>");

if ($row["avatar"] && $row["avatar"] != '')
   print("<td class='lista' align='center' valign='middle' rowspan='4'><img border='0' width='138' src='".security::html_safe($row["avatar"])."' /></td>");

print("</tr>");

if (user::$current["edit_users"] == "yes" || user::$current["admin_access"] == "yes")
{
    print("<tr>\n<td class='header'>".EMAIL."</td>\n<td class='lista'><a href='mailto:".unesc($row["email"])."'>".unesc($row["email"])."</a></td></tr>\n");
    print("<tr>\n<td class='header'>".LAST_IP."</td>\n<td class='lista'>".($row["cip"])."</td></tr>\n");
    print("<tr>\n<td class='header'>".USER_LEVEL."</td>\n<td class='lista'>".security::html_safe($row['level'])."</td></tr>\n");

    $colspan = " colspan='2'";
} else {
  print("<tr>\n<td class='header'>".USER_LEVEL."</td>\n<td class='lista'>".security::html_safe($row['level'])."</td></tr>\n");
 
  $colspan = '';
}

print("<tr>\n<td class='header'>".USER_JOINED."</td>\n<td class='lista'".$colspan.">".($row["joined"] == 0 ? "N/A" : get_date_time($row["joined"]))."</td></tr>\n");
print("<tr>\n<td class='header'>".USER_LASTACCESS."</td>\n<td class='lista'".$colspan.">".($row["lastconnect"] == 0 ? "N/A" : get_date_time($row["lastconnect"]))."</td></tr>\n");
print("<tr>\n<td class='header'>".PEER_COUNTRY."</td>\n<td class='lista' colspan='2'>".($row["flag"] == 0 ? "" : unesc($row['name']))."&nbsp;&nbsp;<img src='images/flag/".(!$row["flagpic"] || $row["flagpic"] == "" ? "unknown.gif" : $row["flagpic"])."' alt='".($row["flag"] == 0 ? "Unknown" : unesc($row['name']))."' /></td></tr>\n");

if (date('I', vars::$timestamp) == 1) {
    $tzu = (date('Z', vars::$timestamp) - 3600);
} else {
    $tzu = date('Z', vars::$timestamp);
}

$offsetu = $tzu - ($row["time_offset"] * 3600);

print("<tr>\n<td class='header'>".USER_LOCAL_TIME."</td>\n<td class='lista' colspan='2'>".date("d/m/Y H:i:s", vars::$timestamp - $offsetu)."&nbsp;(GMT".($row["time_offset"] > 0 ? " + ".$row["time_offset"] : ($row["time_offset"] == 0 ? "" : " ".$row["time_offset"])).")</td></tr>\n");
print("<tr>\n<td class='header'>".DOWNLOADED."</td>\n<td class='lista' colspan='2'>".misc::makesize((float)$row["downloaded"])."</td></tr>\n");
print("<tr>\n<td class='header'>".UPLOADED."</td>\n<td class='lista' colspan='2'>".misc::makesize((float)$row["uploaded"])."</td></tr>\n");

if (intval($row["downloaded"]) > 0)
{
    $sr = (float)$row["uploaded"] / (float)$row["downloaded"];

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

    $ratio = number_format($sr, 2)."&nbsp;&nbsp;<img src='".$s."'>";
}
else
    $ratio = "&infin;";

print("<tr>\n<td class='header'>".RATIO."</td>\n<td class='lista' colspan='2'>".$ratio."</td></tr>\n");

// Only show if forum is internal
if ($GLOBALS["FORUMLINK"] == '' || $GLOBALS["FORUMLINK"] == 'internal')
{
    $sql = $db->query("SELECT * FROM posts INNER JOIN users ON posts.userid = users.id WHERE users.id = " . $id);
    $posts = $sql->num_rows;
    $memberdays = max(1, round((vars::$timestamp - $row['joined']) / 86400 ));
    $posts_per_day = number_format(round($posts / $memberdays, 2), 2);

    print("<tr>\n<td class='header'>".FORUM." ".POSTS."</td>\n<td class='lista' colspan='2'>" . $posts . " &nbsp; [" . sprintf(POSTS_PER_DAY, $posts_per_day) . "]</td></tr>\n");
}

print("</table>");

#Uploaded Torrents
block_begin(UPLOADED." ".MNU_TORRENT);

$resuploaded = $db->query("SELECT namemap.info_hash FROM namemap INNER JOIN summary ON namemap.info_hash = summary.info_hash WHERE uploader = ".$id." AND namemap.anonymous = 'false' ORDER BY data DESC");
$numtorrent = $resuploaded->num_rows;

if ($numtorrent > 0)
{
    list($pagertop, $limit) = misc::pager(($utorrents == 0 ? 15 : $utorrents), $numtorrent, $_SERVER["PHP_SELF"]."?id=".$id."&");
    print($pagertop);

    $resuploaded = $db->query("SELECT namemap.info_hash, namemap.filename, UNIX_TIMESTAMP(namemap.data) AS added, namemap.size, summary.seeds, summary.leechers, summary.finished FROM namemap INNER JOIN summary ON namemap.info_hash = summary.info_hash WHERE uploader = ".$id." AND namemap.anonymous = 'false' ORDER BY data DESC ".$limit);
}

?>
<table width='100%' class='lista'>
<!-- Column Headers  -->
<tr>
    <td align='center' class='header'><?php echo FILE; ?></td>
    <td align='center' class='header'><?php echo ADDED; ?></td>
    <td align='center' class='header'><?php echo SIZE; ?></td>
    <td align='center' class='header'><?php echo SHORT_S; ?></td>
    <td align='center' class='header'><?php echo SHORT_L; ?></td>
    <td align='center' class='header'><?php echo SHORT_C; ?></td>
</tr>

<?php

if ($resuploaded && $resuploaded->num_rows > 0)
{
    while ($rest = $resuploaded->fetch_array(MYSQLI_BOTH))
    {
        print("\n<tr>\n<td class='lista'><a href='details.php?id=".$rest{"info_hash"}."'>".security::html_safe(unesc($rest["filename"]))."</td>");
	
        include(INCL_PATH . 'offset.php');
        print("\n<td class='lista' align='center'>".date("d/m/Y H:m:s", $rest["added"] - $offset)."</td>");
        print("\n<td class='lista' align='center'>".misc::makesize((int)$rest["size"])."</td>");
        print("\n<td align='center' class='".linkcolor($rest["seeds"])."'><a href='peers.php?id=".$rest{"info_hash"}."'>".(int)$rest['seeds']."</td>");
        print("\n<td align='center' class='".linkcolor($rest["leechers"])."'><a href='peers.php?id=".$rest{"info_hash"}."'>".(int)$rest['leechers']."</td>");

        if ($rest["finished"] > 0)
            print("\n<td align='center' class='lista'><a href='torrent_history.php?id=".$rest["info_hash"]."'>".(int)$rest["finished"]."</a></td>");
        else
            print("\n<td align='center' class='lista'>---</td>");
    }
    print("\n</table>");
} else {
    print("<tr>\n<td class='lista' align='center' colspan='6'>".NO_TORR_UP_USER."</td>\n</tr>\n</table>");
}

block_end();
#End Uploaded Torrents

#Active Torrents - hack by petr1fied - modified by Lupin 20/10/05
block_begin("Active torrents");

?>
<table width='100%' class='lista'>
<!-- Column Headers  -->
<tr>
    <td align='center' class='header'><?php echo FILE; ?></td>
    <td align='center' class='header'><?php echo SIZE; ?></td>
    <td align='center' class='header'><?php echo PEER_STATUS; ?></td>
    <td align='center' class='header'><?php echo DOWNLOADED; ?></td>
    <td align='center' class='header'><?php echo UPLOADED; ?></td>
    <td align='center' class='header'><?php echo RATIO; ?></td>
    <td align='center' class='header'>S</td>
    <td align='center' class='header'>L</td>
    <td align='center' class='header'>C</td>
</tr>

<?php

if ($PRIVATE_ANNOUNCE)
    $anq = $db->query("SELECT peers.ip FROM peers INNER JOIN namemap ON namemap.info_hash = peers.infohash INNER JOIN summary ON summary.info_hash = peers.infohash WHERE peers.pid = '".$db->real_escape_string($row["pid"])."'");
else
    $anq = $db->query("SELECT peers.ip FROM peers INNER JOIN namemap ON namemap.info_hash = peers.infohash INNER JOIN summary ON summary.info_hash = peers.infohash WHERE peers.ip = '".$db->real_escape_string($row["cip"])."'");

if ($anq->num_rows > 0)
{
    list($pagertop, $limit) = misc::pager(($utorrents == 0 ? 15 : $utorrents), $anq->num_rows, $_SERVER["PHP_SELF"]."?id=".$id."&", array("pagename" => "activepage"));

	if ($PRIVATE_ANNOUNCE)
        $anq = $db->query("SELECT peers.ip, peers.infohash, namemap.filename, namemap.size, peers.status, peers.downloaded, peers.uploaded, summary.seeds, summary.leechers, summary.finished
                    FROM peers INNER JOIN namemap ON namemap.info_hash = peers.infohash INNER JOIN summary ON summary.info_hash = peers.infohash
                    WHERE peers.pid = '".$db->real_escape_string($row["pid"])."' ORDER BY peers.status DESC ".$limit);
    else
        $anq = $db->query("SELECT peers.ip, peers.infohash, namemap.filename, namemap.size, peers.status, peers.downloaded, peers.uploaded, summary.seeds, summary.leechers, summary.finished
                    FROM peers INNER JOIN namemap ON namemap.info_hash = peers.infohash INNER JOIN summary ON summary.info_hash = peers.infohash
                    WHERE peers.ip = '".$db->real_escape_string($row["cip"])."' ORDER BY peers.status DESC ".$limit);

    print("<div align='center'>".$pagertop."</div>");

    while ($torlist = $anq->fetch_object())
    {
        if ($torlist->ip != '')
        {
            print("\n<tr>\n<td class='lista'><a href='details.php?id=".$torlist->infohash."'>".security::html_safe(unesc($torlist->filename))."</td>");
            print("\n<td class='lista' align='center'>".misc::makesize((int)$torlist->size)."</td>");
            print("\n<td align='center' class='lista'>".unesc($torlist->status ? 'Seeder' : 'Leecher')."</td>");
            print("\n<td align='center' class='lista'>".misc::makesize((int)$torlist->downloaded)."</td>");
            print("\n<td align='center' class='lista'>".misc::makesize((int)$torlist->uploaded)."</td>");
	
            if ($torlist->downloaded > 0)
                $peerratio = number_format((int)$torlist->uploaded / (int)$torlist->downloaded, 2);
            else
                $peerratio = "&infin;";

            print("\n<td align='center' class='lista'>".unesc($peerratio)."</td>");
            print("\n<td align='center' class='".linkcolor($torlist->seeds)."'><a href='peers.php?id=".$torlist->infohash."'>".(int)$torlist->seeds."</td>");
            print("\n<td align='center' class='".linkcolor($torlist->leechers)."'><a href='peers.php?id=".$torlist->infohash."'>".(int)$torlist->leechers."</td>");
            print("\n<td align='center' class='lista'><a href='torrent_history.php?id=".$torlist->infohash."'>".(int)$torlist->finished."</td>\n</tr>");
        }
    }
    print("\n</table>");
}
else
    print("<tr>\n<td class='lista' align='center' colspan='9'>This user has no Active Torrents</td>\n</tr>\n</table>");

block_end();
#End Active Torrents

# History - completed torrents by this user
block_begin("History (Snatched Torrents)");

?>
<table width='100%' class='lista'>
<!-- Column Headers  -->
<tr>
    <td align='center' class='header'><?php echo FILE; ?></td>
    <td align='center' class='header'><?php echo SIZE; ?></td>
    <td align='center' class='header'><?php echo PEER_CLIENT; ?></td>
    <td align='center' class='header'><?php echo PEER_STATUS; ?></td>
    <td align='center' class='header'><?php echo DOWNLOADED; ?></td>
    <td align='center' class='header'><?php echo UPLOADED; ?></td>
    <td align='center' class='header'><?php echo RATIO; ?></td>
    <td align='center' class='header'>S</td>
    <td align='center' class='header'>L</td>
    <td align='center' class='header'>C</TD>
</tr>

<?php

$anq->free();
$anq = $db->query("SELECT history.uid FROM history INNER JOIN namemap ON history.infohash = namemap.info_hash WHERE history.uid = ".$id." AND history.date IS NOT NULL ORDER BY date DESC");

if ($anq->num_rows > 0)
{
    list($pagertop, $limit) = misc::pager(($utorrents == 0 ? 15 : $utorrents), $anq->num_rows, $_SERVER["PHP_SELF"]."?id=".$id."&", array("pagename" => "historypage"));
 
	$anq = $db->query("SELECT namemap.filename, namemap.size, namemap.info_hash, history.active, history.agent, history.downloaded, history.uploaded, summary.seeds, summary.leechers, summary.finished
    FROM history INNER JOIN namemap ON history.infohash = namemap.info_hash INNER JOIN summary ON summary.info_hash = namemap.info_hash WHERE history.uid = ".$id." AND history.date IS NOT NULL ORDER BY date DESC ".$limit);

	print("<div align='center'>".$pagertop."</div>");

	while ($torlist = $anq->fetch_object())
    {
        print("\n<tr>\n<td class='lista'><a href='details.php?id=".$torlist->info_hash."'>".security::html_safe(unesc($torlist->filename))."</td>");
        print("\n<td class='lista' align='center'>".misc::makesize((int)$torlist->size)."</td>");
        print("\n<td class='lista' align='center'>".security::html_safe($torlist->agent)."</td>");
        print("\n<td align='center' class='lista'>".($torlist->active == 'yes' ? ACTIVATED : 'Stopped')."</td>");
        print("\n<td align='center' class='lista'>".misc::makesize((int)$torlist->downloaded)."</td>");
        print("\n<td align='center' class='lista'>".misc::makesize((int)$torlist->uploaded)."</td>");

        if ($torlist->downloaded > 0)
            $peerratio = number_format((int)$torlist->uploaded / (int)$torlist->downloaded, 2);
        else
            $peerratio = "&infin;";

        print("\n<td align='center' class='lista'>".unesc($peerratio)."</td>");
        print("\n<td align='center' class='".linkcolor($torlist->seeds)."'><a href='peers.php?id=".$torlist->info_hash."'>".(int)$torlist->seeds."</td>");
        print("\n<td align='center' class='".linkcolor($torlist->leechers)."'><a href='peers.php?id=".$torlist->info_hash."'>".(int)$torlist->leechers."</td>");
        print("\n<td align='center' class='lista'><a href='torrent_history.php?id=".$torlist->info_hash."'>".(int)$torlist->finished."</td>\n</tr>");
    }
    print("\n</table>");
}
else
    print("<tr>\n<td class='lista' align='center' colspan='10'>No history for this user</td>\n</tr>\n</table>");

block_end();
#End Torrents History

print("<br /><br /><center><a href='javascript: history.go(-1);'>".BACK."</a></center><br />\n");

block_end();
stdfoot();

?>
