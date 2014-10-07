<?php
/*
* BtiTracker v1.5.0 is a php tracker system for BitTorrent, easy to setup and configure.
* This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
* Updated and Maintained by Yupy.
* Copyright (C) 2004-2014 Btiteam.org
*/
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'include'.DIRECTORY_SEPARATOR.'functions.php');

dbconn();

standardheader('Peer Details');

$id = AddSlashes($_GET["id"]);

if (!isset($id) || !$id)
    die("Error ID");

$res = $db->query("SELECT * FROM namemap WHERE info_hash = '" . $id . "'");
if ($res) {
    $row = $res->fetch_array(MYSQLI_BOTH);
    if ($row) {
        $tsize = 0 + (int)$row['size'];
    }
} else
    die("Error ID");

$res = $db->query("SELECT * FROM peers LEFT JOIN countries ON peers.dns = countries.domain WHERE infohash = '" . $id . "' ORDER BY bytes ASC, status DESC");

block_begin(PEER_LIST);

$spacer = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";

print("<table width='100%' class='lista' border='0'>\n");
print("<tr><td align='center' class='header' colspan='2'>" . USER_NAME . "</td>");
print("<td align='center' class='header'>" . PEER_COUNTRY . "</td>");
print("<td align='center' class='header'>" . PEER_PORT . "</td>");
print("<td align='center' class='header'>" . PEER_PROGRESS . "</td>");
print("<td align='center' class='header'>" . PEER_STATUS . "</td>");
print("<td align='center' class='header'>" . PEER_CLIENT . "</td>\n");
print("<td align='center' class='header'>" . DOWNLOADED . "</td>\n");
print("<td align='center' class='header'>" . UPLOADED . "</td>\n");
print("<td align='center' class='header'>" . RATIO . "</td>\n");
print("<td align='center' class='header'>" . SEEN . "</td></tr>\n");

while ($row = $res->fetch_array(MYSQLI_BOTH)) {
    // for user name instead of peer
    if ($PRIVATE_ANNOUNCE)
        $resu = $db->query("SELECT users.username, users.id, countries.flagpic, countries.name FROM users LEFT JOIN countries ON countries.id = users.flag WHERE users.pid = '" . $db->real_escape_string($row["pid"]) . "'");
    else
        $resu = $db->query("SELECT users.username, users.id, countries.flagpic, countries.name FROM users LEFT JOIN countries ON countries.id = users.flag WHERE users.cip = '" . $db->real_escape_string($row["ip"]) . "'");
    if ($resu) {
        $rowuser = $resu->fetch_row();
        if ($rowuser && $rowuser[1] > 1) {
            print("<tr><td align='center' class='lista'>" . "<a href='userdetails.php?id=" . (int)$rowuser[1] . "'>" . security::html_safe(unesc($rowuser[0])) . "</a></td>" . "<td align='center' class='lista'><a href='usercp.php?do=pm&action=edit&uid=" . user::$current['uid'] . "&what=new&to=" . urlencode(unesc($rowuser[0])) . "'>" . image_or_link($STYLEPATH . "/pm.png", "", "PM") . "</a></td>");
        } else
            print("<tr><td align='left' class='lista' colspan='2'>" . GUEST . "</td>");
    }
    if ($row["flagpic"] != "" && $row["flagpic"] != "unknown.gif")
        print("<td align='center' class='lista'><img src='images/flag/" . $row["flagpic"] . "' alt='" . security::html_safe(unesc($row["name"])) . "' /></td>");
    elseif ($rowuser[2] != "" && !empty($rowuser[2]))
        print("<td align='center' class='lista'><img src='images/flag/" . $rowuser[2] . "' alt='" . security::html_safe(unesc($rowuser[3])) . "' /></td>");
    else
        print("<td align='center' class='lista'><img src='images/flag/unknown.gif' alt='" . UNKNOWN . "' /></td>");
    
    print("<td align='center' class='lista'>" . (int)$row["port"] . "</td>");
	if ($tsize != 0) {
    $stat = floor((($tsize - (int)$row['bytes']) / $tsize) * 100);
	} else {
	    $stat = floor((($tsize - (int)$row['bytes']) / 0) * 100);
	}
    $progress = "<table width='100' cellspacing='0' cellpadding='0'><tr><td class='progress' align='left'>";
    $progress .= "<img height='10' height='10' width='" . number_format($stat, 0) . "' src='" . $STYLEPATH . "/progress.jpg'></td></tr></table>";
    print("<td valign='top' align='center' class='lista'>" . $stat . "%<br />" . $progress . "</td>\n");
    print("<td align='center' class='lista'>" . $row["status"] . "</td>");
    print("<td align='center' class='lista'>" . security::html_safe(getagent(unesc($row["client"]), unesc($row["peer_id"]))) . "</td>");
    $dled = misc::makesize((int)$row["downloaded"]);
    $upld = misc::makesize((int)$row["uploaded"]);
    print("<td align='center' class='lista'>" . $dled . "</td>");
    print("<td align='center' class='lista'>" . $upld . "</td>");
    //Peer Ratio
    if (intval($row["downloaded"]) > 0) {
        $ratio = number_format((int)$row["uploaded"] / (int)$row["downloaded"], 2);
    } else {
        $ratio = "&infin;";
    }
    print("<td align='center' class='lista'>" . $ratio . "</td>");
    //End Peer Ratio
    print("<td align='center' class='lista'>" . get_elapsed_time($row["lastupdate"]) . " ago</td></tr>");
}

if ($res->num_rows == 0)
    print("<tr><td align='center' colspan='11' class='lista'>" . NO_PEERS . "</td></tr>");

print("</table>");

print("</div><br /><br /><center><a href='javascript: history.go(-1);'>" . BACK . "</a>");

block_end();
stdfoot();

?>