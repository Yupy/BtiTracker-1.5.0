<?php
/*
* BtiTracker v1.5.0 is a php tracker system for BitTorrent, easy to setup and configure.
* This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
* Updated and Maintained by Yupy.
* Copyright (C) 2004-2014 Btiteam.org
*/
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'include'.DIRECTORY_SEPARATOR.'functions.php');

dbconn();

standardheader('Torrent Details');

if (!user::$current || user::$current["view_torrents"] != "yes") {
    err_msg(ERROR . NOT_AUTHORIZED . " " . MNU_TORRENT . "!", SORRY . "...");
    stdfoot();
    exit();
}

block_begin(TORRENT_DETAIL);

$id = AddSlashes((isset($_GET["id"]) ? $_GET["id"] : false));

if (!isset($id) || !$id)
    die(ERROR_ID . ": " . $id);

if (isset($_GET["act"])) {
    print("<center>" . TORRENT_UPDATE . "</center>");
    require_once(INCL_PATH . 'getscrape.php');
    scrape(urldecode($_GET["surl"]), $id);
    redirect("details.php?id=" . $id);
    exit();
}

if (isset($_GET["vote"]) && $_GET["vote"] == VOTE) {
    if (isset($_GET["rating"]) && $_GET["rating"] == 0) {
        err_msg(ERROR, ERR_NO_VOTE);
        block_end();
        stdfoot();
        exit();
    } else {
        @$db->query("INSERT INTO ratings SET infohash = '" . $id . "', userid = " . user::$current['uid'] . ", rating = " . intval($_GET["rating"]) . ", added = '" . vars::$timestamp . "'");
        redirect("details.php?id=" . $id);
    }
    exit();
}

$res = $db->query("SELECT namemap.info_hash, namemap.filename, namemap.url, UNIX_TIMESTAMP(namemap.data) AS data, namemap.size, namemap.comment, namemap.uploader, categories.name AS cat_name, summary.seeds, summary.leechers, summary.finished, summary.speed, namemap.external, namemap.announce_url, UNIX_TIMESTAMP(namemap.lastupdate) AS lastupdate, namemap.anonymous, users.username FROM namemap LEFT JOIN categories ON categories.id = namemap.category LEFT JOIN summary ON summary.info_hash = namemap.info_hash LEFT JOIN users ON users.id = namemap.uploader WHERE namemap.info_hash = '" . $id . "'");
$row = $res->fetch_array(MYSQLI_BOTH);

if (!$row)
    die("Bad ID!");

$spacer = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";

print("<div align='center'><table class='lista' border='0' cellspacing='5' cellpadding='5'>\n");
print("<tr><td align='right' class='header'> " . FILE_NAME);

if (user::$current["uid"] > 1 && (user::$current["uid"] == $row["uploader"] || user::$current["edit_torrents"] == "yes" || user::$current["delete_torrents"] == "yes"))
    print("<br />&nbsp;&nbsp;");

// edit and delete picture/link
if (user::$current["uid"] > 1 && (user::$current["uid"] == $row["uploader"] || user::$current["edit_torrents"] == "yes")) {
    print("<a href='edit.php?info_hash=" . $row["info_hash"] . "&amp;returnto=" . urlencode("torrents.php") . "'>" . image_or_link($STYLEPATH . "/edit.gif", "", EDIT) . "</a>&nbsp;&nbsp;");
}

if (user::$current["uid"] > 1 && (user::$current["uid"] == $row["uploader"] || user::$current["delete_torrents"] == "yes")) {
    print("<a href='delete.php?info_hash=" . $row["info_hash"] . "&amp;returnto=" . urlencode("torrents.php") . "'>" . image_or_link($STYLEPATH . "/delete.gif", "", DELETE) . "</a>");
}

print("</td><td class='lista' align='center'>" . security::html_safe($row["filename"]) . "</td></tr>\n");
print("<tr><td align='right' class='header'> " . TORRENT . ":</td><td class='lista' align='center'><a href='download.php?id=" . $row["info_hash"] . "&f=" . rawurlencode($row["filename"]) . ".torrent'>" . security::html_safe($row["filename"]) . "</a></td></tr>\n");
print("<tr><td align='right' class='header'> " . INFO_HASH . ":</td><td class='lista' align='center'>" . security::html_safe($row["info_hash"]) . "</td></tr>\n");

if (!empty($row["comment"]))
    print("<tr><td align='right' class='header'> " . DESCRIPTION . ":</td><td align='center' class='lista'>" . format_comment(unesc($row["comment"])) . "</td></tr>\n");

if (isset($row["cat_name"]))
    print("<tr><td align='right' class='header'> " . CATEGORY_FULL . ":</td><td class='lista' align='center'>" . security::html_safe(unesc($row["cat_name"])) . "</td></tr>\n");
else
    print("<tr><td align='right' class='header'> " . CATEGORY_FULL . ":</td><td class='lista' align='center'>(None)</td></tr>\n");

// rating
print("<tr><td align='right' class='header'> " . RATING . ":</td><td class='lista' align='center'>\n");

$vres = $db->query("SELECT SUM(rating) AS totrate, COUNT(*) AS votes FROM ratings WHERE infohash = '" . $id . "'");
$vrow = @$vres->fetch_array(MYSQLI_BOTH);
if ($vrow && $vrow["votes"] >= 1)
{
    $totrate = round($vrow["totrate"] / (int)$vrow["votes"], 1);

    if ($totrate == 5)
        $totrate = "<img src='" . $STYLEPATH . "/5.gif' title='" . (int)$vrow['votes'] . " " . VOTES_RATING . ": " . $totrate . " / 5.0)' />";
    elseif ($totrate > 4.4 && $totrate < 5)
        $totrate = "<img src='" . $STYLEPATH . "/4.5.gif' title='" . (int)$vrow['votes'] . " " . VOTES_RATING . ": " . $totrate . " / 5.0)' />";
    elseif ($totrate > 3.9 && $totrate < 4.5)
        $totrate = "<img src='" . $STYLEPATH . "/4.gif' title='" . (int)$vrow['votes'] . " " . VOTES_RATING . ": " . $totrate . " / 5.0)' />";
    elseif ($totrate > 3.4 && $totrate < 4)
        $totrate = "<img src='" . $STYLEPATH . "/3.5.gif' title='" . (int)$vrow['votes'] . " " . VOTES_RATING . ": " . $totrate . " / 5.0)' />";
    elseif ($totrate > 2.9 && $totrate < 3.5)
        $totrate = "<img src='" . $STYLEPATH . "/3.gif' title='" . (int)$vrow['votes'] . " " . VOTES_RATING . ": " . $totrate . " / 5.0)' />";
    elseif ($totrate > 2.4 && $totrate < 3)
        $totrate = "<img src='" . $STYLEPATH . "/2.5.gif' title='" . (int)$vrow['votes'] . " " . VOTES_RATING . ": " . $totrate . " / 5.0)' />";
    elseif ($totrate > 1.9 && $totrate < 2.5)
        $totrate = "<img src='" . $STYLEPATH . "/2.gif' title='" . (int)$vrow['votes'] . " " . VOTES_RATING . ": " . $totrate . " / 5.0)' />";
    elseif ($totrate > 1.4 && $totrate < 2)
        $totrate = "<img src='" . $STYLEPATH . "/1.5.gif' title='" . (int)$vrow['votes'] . " " . VOTES_RATING . ": " . $totrate . " / 5.0)' />";
    else
        $totrate = "<img src='" . $STYLEPATH . "/1.gif' title='" . (int)$vrow['votes'] . " " . VOTES_RATING . ": " . $totrate . " / 5.0)' />";
}
else
    $totrate = NA;

if ($row["username"] != user::$current["username"] && user::$current["uid"] > 1) {
    $ratings = array(
        5 => FIVE_STAR,
        4 => FOUR_STAR,
        3 => THREE_STAR,
        2 => TWO_STAR,
        1 => ONE_STAR
    );

    $xres = $db->query("SELECT rating, added FROM ratings WHERE infohash = '" . $id . "' AND userid = " . user::$current["uid"]);
    $xrow = @$xres->fetch_array(MYSQLI_BOTH);
    if ($xrow)
        $s = $totrate . " (" . YOU_RATE . " '" . $ratings[$xrow["rating"]] . "')";
    else {
        $s = "<form method='get' action='details.php' name='vote'>\n";
        $s .= "<input type='hidden' name='id' value='" . $id . "' />\n";
        $s .= "<select name='rating'>\n";
        $s .= "<option value='0'>(" . ADD_RATING . ")</option>\n";
        foreach ($ratings as $k => $v) {
            $s .= "<option value='" . $k . "'>" . $v . "</option>\n";
        }
        $s .= "</select>\n";
        $s .= "<input type='submit' name='vote' value='" . VOTE . "' />";
        $s .= "</form>\n";
    }
} else {
    $s = $totrate;
}
print $s;
print("</td></tr>\n");
print("<tr><td align=right class='header'> " . SIZE . ":</td><td class='lista' align='center'>" . misc::makesize((int)$row["size"]) . "</td></tr>\n");

// files in torrent - by Lupin 20/10/05
?>
<script type='text/javascript' language='JavaScript'>
function ShowHide(id,id1) {
    obj = document.getElementsByTagName("div");
    if (obj[id].style.display == 'block'){
     obj[id].style.display = 'none';
     obj[id1].style.display = 'block';
    }
    else {
     obj[id].style.display = 'block';
     obj[id1].style.display = 'none';
    }
}
</script>
<?php

require_once(CLASS_PATH . 'class.Bencode.php');
if (file_exists($row["url"])) {
    print("
    <tr>
    <td align='right' class='header' valign='top'>
    <a name='#expand' href='#expand' onclick=\"javascript:ShowHide('files', 'msgfile');\">Show/Hide Files: </td>
    <td align='left' class='lista'>
    <div name='files' style='display:none' id='files'>
        <table class='lista'>
        <tr>
        <td align='center' class='header'>" . FILE_NAME . "</td>
        <td align='center' class='header'>" . SIZE . "</td>
        </tr>");
    $ffile = fopen($row["url"], "rb");
    $content = fread($ffile, filesize($row["url"]));
    fclose($ffile);
    $content  = Bencode::decode($content);
    $numfiles = 0;
    if (isset($content["info"]) && $content["info"]) {
        $thefile = $content["info"];
        if (isset($thefile["length"])) {
            $numfiles++;
            print("\n<tr>\n<td align='left' class='lista'>" . security::html_safe($thefile["name"]) . "</td>\n<td align='right' class='lista'>" . misc::makesize((int)$thefile["length"]) . "</td></tr>\n");
        } elseif (isset($thefile["files"])) {
            foreach ($thefile["files"] as $singlefile) {
                print("\n<tr>\n<td align='left' class='lista'>" . security::html_safe(implode("/", $singlefile["path"])) . "</td>\n<td align='right' class='lista'>" . misc::makesize((int)$singlefile["length"]) . "</td></tr>\n");
                $numfiles++;
            }
        } else {
            print("\n<tr>\n<td colspan='2'>No Data...</td></tr>\n"); // can't be but...
        }
    }
    print("</table></div>
    <div name='msgfile' style='display:block' id='msgfile' align='center'>" . $numfiles . "" . ($numfiles == 1 ? " file" : " files") . "</div>
    </td></tr>\n");
}
// end files in torrents

include(INCL_PATH . 'offset.php');
print("<tr><td align='right' class='header'> " . ADDED . ":</td><td class='lista' align='center'>" . date("d/m/Y H:m:s", $row["data"] - $offset) . "</td></tr>\n");

if ($row["anonymous"] == "true") {
    if (user::$current["edit_torrents"] == "yes")
        $uploader = "<a href=userdetails.php?id=" . (int)$row['uploader'] . ">" . TORRENT_ANONYMOUS . "</a>";
    else
        $uploader = TORRENT_ANONYMOUS;
} else
    $uploader = "<a href=userdetails.php?id=" . (int)$row['uploader'] . ">" . security::html_safe($row["username"]) . "</a>";

print("<tr><td align='right' class='header'>" . UPLOADER . ":</td><td class='lista' align='center'>" . $uploader . "</td></tr>\n");

if ($row["speed"] < 0) {
    $speed = "N/A";
} else if ($row["speed"] > 2097152) {
    $speed = round((int)$row["speed"] / 1048576, 2) . " MiB per sec";
} else {
    $speed = round((int)$row["speed"] / 1024, 2) . " KiB per sec";
}

print("<tr><td align='right' class='header'> " . SPEED . ":</td><td class='lista' align='center'>" . $speed . "</td></tr>\n");

if ($row["external"] == "no") {
    print("<tr><td align='right' class='header'> " . DOWNLOADED . ":</td><td class='lista' align='center'><a href='torrent_history.php?id=" . $row["info_hash"] . "'>" . (int)$row["finished"] . "</a> " . X_TIMES . "</td></tr>\n");
    print("<tr><td align='right' class='header'> " . PEERS . ":</td><td class='lista' align='center'>" . SEEDERS . ": <a href='peers.php?id=" . $row["info_hash"] . "'>" . (int)$row["seeds"] . "</a>, " . LEECHERS . ": <a href='peers.php?id=" . $row["info_hash"] . "'>" . (int)$row["leechers"] . "</a> = <a href='peers.php?id=" . $row["info_hash"] . "'>" . ((int)$row["leechers"] + (int)$row["seeds"]) . "</a> " . PEERS . "</td></tr>\n");
} else {
    print("<tr><td align='right' class='header'> " . DOWNLOADED . ":</td><td class='lista' align='center'>" . (int)$row["finished"] . " " . X_TIMES . "</td></tr>\n");
    print("<tr><td align='right' class='header'> " . PEERS . ":</td><td class='lista' align='center'>" . SEEDERS . ": " . (int)$row["seeds"] . ", " . LEECHERS . ": " . (int)$row["leechers"] . " = " . ((int)$row["leechers"] + (int)$row["seeds"]) . " " . PEERS . "</td></tr>\n");
}

if ($row["external"] == "yes") {
    print("<tr><td valign='middle' align='right' class='header'><a href='details.php?act=update&id=" . $row["info_hash"] . "&surl=" . urlencode($row["announce_url"]) . "'>" . UPDATE . "</a></td><td class='lista' align='center'><b>EXTERNAL</b><br />" . security::html_safe($row["announce_url"]) . "</td></tr>\n");
    print("<tr><td valign='middle' align='right' class='header'>" . LAST_UPDATE . "</td><td class='lista' align='center'>" . get_date_time($row["lastupdate"]) . "</td></tr>\n");
}
print("</table>\n");
print("<a name='comments' /></a>");
// comments...
$subres = $db->query("SELECT comments.id, text, UNIX_TIMESTAMP(added) AS data, user, users.id AS uid FROM comments LEFT JOIN users ON comments.user = users.username WHERE info_hash = '" . $id . "' ORDER BY added ASC");
if (!$subres || $subres->num_rows == 0) {
    if (user::$current["uid"] > 1)
        $s = "<br /><br />\n<table width='95%' class='lista'>\n<tr>\n<td align='center'>\n<a href='comment.php?id=" . $id . "&usern=" . urlencode(user::$current["username"]) . "'>" . NEW_COMMENT . "</a>\n</td>\n</tr>\n";
    else
        $s = "<br /><br />\n<table width='95%' class='lista'>\n";

    $s .= "<tr>\n<td class='lista' align='center'>" . NO_COMMENTS . "</td>\n</tr>\n";
    $s .= "</table>\n";
} else {
    print("<br /><br />");
    if (user::$current["uid"] > 1)
        $s = "<br /><br />\n<table width='95%' class='lista'><tr><td colspan='3' align='center'><a href='comment.php?id=" . $id . "&usern=" . urlencode(user::$current["username"]) . "'>" . NEW_COMMENT . "</a></td></tr>\n";
    else
        $s = "<br /><br />\n<table width='95%' class='lista'>\n";

    while ($subrow = $subres->fetch_array(MYSQLI_BOTH)) {
        $s .= "<tr><td class='header'><a href='userdetails.php?id=" . (int)$subrow["uid"] . "'>" . security::html_safe($subrow["user"]) . "</a></td><td class='header'>" . date("d/m/Y H.i.s", $subrow["data"] - $offset) . "</td>\n";
        // only users able to delete torrents can delete comments...
        if (user::$current["delete_torrents"] == "yes")
            $s .= "<td class='header' align='right'><a onclick='return confirm('" . str_replace("'", "\'", DELETE_CONFIRM) . "')' href='comment.php?id=$id&cid=" . $subrow["id"] . "&action=delete'>" . image_or_link($STYLEPATH . "/delete.png", "", DELETE) . "</a></td>\n";
        $s .= "</tr>\n";
        $s .= "<tr><td colspan='3' class='lista' align='center'>" . format_comment(unesc($subrow["text"])) . "</td></tr>\n";
    }
    $s .= "</table>\n";
}
print($s);

print("</div><br /><br /><center><a href='javascript: history.go(-1);'>" . BACK . "</a>");
print("</center>\n");

block_end();
stdfoot();

?>
