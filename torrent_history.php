<?php
/*
* BtiTracker v1.5.0 is a php tracker system for BitTorrent, easy to setup and configure.
* This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
* Updated and Maintained by Yupy.
* Copyright (C) 2004-2014 Btiteam.org
*/
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'include'.DIRECTORY_SEPARATOR.'functions.php');

dbconn();

standardheader('History Details');

$id = AddSlashes($_GET["id"]);

if (!isset($id) || !$id)
    die("Error ID");

// control if torrent exist in our db
$res = $db->query("SELECT * FROM namemap WHERE info_hash = '" . $id . "'");

if ($res) {
    $row = $res->fetch_array(MYSQLI_BOTH);
  
    if ($row) {
        $tsize = 0 + (int)$row["size"];
    }
}
else
    die("Error ID");

// select lastest 30 records for infohash
$res = $db->query("SELECT history.*, username, countries.name AS country, countries.flagpic, level, prefixcolor, suffixcolor FROM history INNER JOIN users ON history.uid = users.id INNER JOIN countries ON users.flag = countries.id INNER JOIN users_level ON users.id_level = users_level.id WHERE history.infohash = '" . $id . "' AND history.date IS NOT NULL ORDER BY date DESC LIMIT 0, 30");

block_begin("Torrent History (Last 30 Snatchers)");

$spacer = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";

print("<table class='lista' border='0' width='100%'>\n");
print("<tr><td align='center' class='header' colspan='2'>" . USER_NAME . "</td>");
print("<td align='center' class='header'>" . PEER_COUNTRY . "</td>");
print("<td align='center' class='header'>Active</td>");
print("<td align='center' class='header'>" . PEER_CLIENT . "</td>\n");
print("<td align='center' class='header'>" . DOWNLOADED . "</td>\n");
print("<td align='center' class='header'>" . UPLOADED . "</td>\n");
print("<td align='center' class='header'>" . RATIO . "</td>\n");
print("<td align='center' class='header'>" . FINISHED . "</td></tr>\n");

while ($row = $res->fetch_array(MYSQLI_BOTH))
{
    print("<tr><td align='center' class='lista'>".
       "<a href='userdetails.php?id=" . (int)$row["uid"] . "'>" . security::html_safe(unesc($row["username"])) . "</a></td>".
       "<td align='center' class='lista'><a href='usercp.php?do=pm&action=edit&uid=" . user::$current['uid'] . "&what=new&to=" . urlencode(unesc($row["username"])) . "'>" . image_or_link($STYLEPATH . "/pm.png", "", "PM") . "</a></td>");

    if ($row["flagpic"] != "")
        print("<td align='center' class='lista'><img src='images/flag/" . $row["flagpic"] . "' alt='" . security::html_safe($row["country"]) . "' /></td>");
    else
        print("<td align='center' class='lista'><img src='images/flag/unknown.gif' alt='" . UNKNOWN . "' /></td>");

    print("<td align='center' class='lista'>" . $row["active"] . "</td>");
    print("<td align='center' class='lista'>" . security::html_safe($row["agent"]) . "</td>");

    $dled = misc::makesize((int)$row["downloaded"]);
    $upld = misc::makesize((int)$row["uploaded"]);
    print("<td align='center' class='lista'>" . $dled . "</td>");
    print("<td align='center' class='lista'>" . $upld . "</td>");

    if (intval($row["downloaded"]) > 0) {
        $ratio = number_format((int)$row["uploaded"] / (int)$row["downloaded"], 2);
    } else {
	    $ratio = "&infin;";
    }
    print("<td align='center' class='lista'>" . $ratio . "</td>");
    print("<td align='center' class='lista'>" . get_elapsed_time($row["date"]) . " ago</td></tr>");
}

if ($res->num_rows == 0)
    print("<tr><td align='center' colspan='9' class='lista'>No history to display</td></tr>");

print("</table>");

print("</div><br /><br /><center><a href='javascript: history.go(-1);'>".BACK."</a>");

block_end();
stdfoot();

?>