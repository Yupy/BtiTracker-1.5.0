<?php
/*
* BtiTracker v1.5.0 is a php tracker system for BitTorrent, easy to setup and configure.
* This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
* Updated and Maintained by Yupy.
* Copyright (C) 2004-2014 Btiteam.org
*/
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'include'.DIRECTORY_SEPARATOR.'functions.php');

dbconn();

standardheader('Delete Torrents');

$id = $db->real_escape_string($_GET["info_hash"]);

if (!isset($id) || !$id)
    die("Error ID");

$res = $db->query("SELECT namemap.info_hash, namemap.uploader, namemap.filename, namemap.url, UNIX_TIMESTAMP(namemap.data) AS data, namemap.size, namemap.comment, categories.name AS cat_name, summary.seeds, summary.leechers, summary.finished, summary.speed FROM namemap LEFT JOIN categories ON categories.id = namemap.category LEFT JOIN summary ON summary.info_hash = namemap.info_hash WHERE namemap.info_hash = '" . $id . "'");
$row = $res->fetch_array(MYSQLI_BOTH);

if (user::$current["delete_torrents"] != "yes" && user::$current["uid"] != $row["uploader"]) {
    err_msg(SORRY, CANT_DELETE_TORRENT);
    stdfoot();
    exit();
}

$scriptname = security::html_safe($_SERVER["PHP_SELF"]);

$link = urlencode($_GET["returnto"]);
$hash = AddSlashes($_GET["info_hash"]);

if ($link == "")
    $link = "torrents.php";

if (isset($_POST["action"])) {
    if ($_POST["action"] == DELETE) {
        $ris = $db->query("SELECT info_hash, filename, url FROM namemap WHERE info_hash = '" . $hash . "'");
        if ($ris->num_rows == 0) {
            err_msg("Sorry!", "Torrent " . $hash . " not found.");
            exit();
        } else {
            list($torhash, $torname, $torurl) = $ris->fetch_array(MYSQLI_BOTH);
        }

        write_log("Deleted torrent " . $torname . " (" . $torhash . ")", "delete");
        
        @$db->query("DELETE FROM summary WHERE info_hash = '" . $hash . "'");
        @$db->query("DELETE FROM namemap WHERE info_hash = '" . $hash . "'");
        @$db->query("DELETE FROM timestamps WHERE info_hash = '" . $hash . "'");
        @$db->query("DELETE FROM comments WHERE info_hash = '" . $hash . "'");
        @$db->query("DELETE FROM ratings WHERE infohash = '" . $hash . "'");
        @$db->query("DELETE FROM peers WHERE infohash = '" . $hash . "'");
        @$db->query("DELETE FROM history WHERE infohash = '" . $hash . "'");
        
        @unlink(CACHE_PATH . 'torrent_details_' . $hash . '.txt');
        
        unlink($TORRENTSDIR . "/" . $hash . ".btf");
        
        print("<script language='javascript'>window.location.href='" . $link . "'</script>");
        exit();
    } else {
        print("<script language='javascript'>window.location.href='" . $link . "'</script>");
        exit();
    }
}

block_begin(DELETE_TORRENT);

print("<table width='100%' class='lista' border='0' cellspacing='5' cellpadding='5'>\n");
print("<tr><td align='right' class='header'>" . FILE_NAME . ":</td><td class='lista'>" . security::html_safe($row["filename"]) . "</td></tr>");
print("<tr><td align='right' class='header'>" . INFO_HASH . ":</td><td class='lista'>" . security::html_safe($row["info_hash"]) . "</td></tr>");

if (!empty($row["comment"]))
    print("<tr><td align='right' class='header'>" . DESCRIPTION . ":</td><td align='left' class='lista'>" . format_comment(unesc($row["comment"])) . "</td></tr>");

if (isset($row["cat_name"]))
    print("<tr><td align='right' class='header'>" . CATEGORY_FULL . ":</td><td class='lista'>" . security::html_safe($row["cat_name"]) . "</td></tr>");
else
    print("<tr><td align='right' class='header'>" . CATEGORY_FULL . ":</td><td class='lista'>(None)</td></tr>");

print("<tr><td align='right' class='header'>" . SIZE . ":</td><td class='lista'>" . misc::makesize((int)$row["size"]) . "</td></tr>");
print("<tr><td align='right' class='header'>" . ADDED . ":</td><td class='lista'>" . date("d/m/Y H:m:s", $row["data"]) . "</td></tr>");

if ($row["speed"] < 0) {
    $speed = "N/A";
} else if ($row["speed"] > 2097152) {
    $speed = round((int)$row["speed"] / 1048576, 2) . " MiB per sec";
} else {
    $speed = round((int)$row["speed"] / 1024, 2) . " KiB per sec";
}
print("<tr><td align='right' class='header'>" . SPEED . ":</td><td class='lista'>" . $speed . "</td></tr>");
print("<tr><td align='right' class='header'>" . DOWNLOADED . ":</td><td class='lista'>" . (int)$row["finished"] . "</td></tr>");
print("<tr><td align='right' class='header'>" . PEERS . ":</td><td class='lista'>" . SEEDERS . ": " . (int)$row["seeds"] . ", " . LEECHERS . ": " . (int)$row["leechers"] . " = " . ((int)$row["leechers"] + (int)$row["seeds"]) . " " . PEERS . "</td></tr>");
print("</table>\n");
print("<form action='" . $scriptname . "?info_hash=" . $id . "&returnto=" . $link . "' name='delete' method='post'>");
print("<center><input type='submit' name='action' value='" . DELETE . "' />");
print("&nbsp;&nbsp;<input type='submit' name='action' value='" . FRM_CANCEL . "' /></center>");
print("</form>");

block_end();
stdfoot();

?>
