<?php
/*
* BtiTracker v1.5.0 is a php tracker system for BitTorrent, easy to setup and configure.
* This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
* Updated and Maintained by Yupy.
* Copyright (C) 2004-2014 Btiteam.org
*/
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'include'.DIRECTORY_SEPARATOR.'functions.php');

dbconn(true);

if (user::$current["view_torrents"] != "yes" && user::$current["view_forum"] != "yes")
{
    header(ERR_500);
    die;
}

header('Content-type: text/xml');

function safehtml($string)
{
    $validcharset = array(
    "ISO-8859-1",
    "ISO-8859-15",
    "UTF-8",
    "cp866",
    "cp1251",
    "cp1252",
    "KOI8-R",
    "BIG5",
    "GB2312",
    "BIG5-HKSCS",
    "Shift_JIS",
    "EUC-JP");

    if (in_array($GLOBALS["charset"], $validcharset))
        return htmlentities($string, ENT_COMPAT, $GLOBALS["charset"]);
    else
        return htmlentities($string);
}

?>

<rss version="2.0">
<channel>
<title><?php print $SITENAME;?></title>
<description>rss feed script designed and coded by beeman (modified by Lupin and VisiGod)</description>
<link><?php print $BASEURL;?></link>
<lastBuildDate><?php print date("D, d M Y H:i:s T");?></lastBuildDate>
<copyright><?php print "(c) " . date("Y", vars::$timestamp) . " " . $SITENAME;?></copyright>

<?php

if (user::$current["view_torrents"] == "yes")
{
    $getItems = "SELECT namemap.info_hash AS id, namemap.comment AS description, namemap.filename, summary.seeds AS seeders, summary.leechers, UNIX_TIMESTAMP( namemap.data ) AS added, categories.name AS cname, namemap.size FROM summary LEFT JOIN namemap ON summary.info_hash = namemap.info_hash LEFT JOIN categories ON categories.id = namemap.category " . $where . " ORDER BY data DESC LIMIT 20";
    $doGet = $db->query($getItems);

    while($item = $doGet->fetch_array(MYSQLI_BOTH))
    {
        $id = $db->real_escape_string($item['id']);
        $filename = strip_tags($item['filename']);
        $added = strip_tags(date("d/m/Y H:i:s", $item['added']));
        $descr = format_comment($item['description'] . "\n");
        $seeders = strip_tags($item['seeders']);
        $leechers = strip_tags($item['leechers']);
        // output to browser

        ?>
        <item>
        <title><?php print safehtml("[" . TORRENT . "] " . $filename);?></title>
        <description><?php print safehtml($descr) . " (" . SEEDERS . " " . safehtml($seeders) . " -- " . LEECHERS . " " . safehtml($leechers);?>)</description>
        <link><?php print $BASEURL;?>/details.php?id=<?php print $id;?></link>
        <pubDate><?php print $added;?></pubDate>
        </item>

       <?php
    }
}

// forums
if (user::$current["view_forum"] == "yes")
{
    $getItems = "SELECT topics.id AS topicid, posts.id AS postid, forums.name, users.username, topics.subject, posts.added, posts.body FROM topics INNER JOIN posts ON posts.topicid = topics.id INNER JOIN forums ON topics.forumid = forums.id INNER JOIN users ON users.id = posts.userid ORDER BY added DESC LIMIT 100";
    $doGet = $db->query($getItems);

    while($item = $doGet->fetch_array(MYSQLI_BOTH))
    {
        $topicid = (int)$item['topicid'];
        $postid = (int)$item['postid'];
        $forum = strip_tags($item['name']);
        $subject = strip_tags($item['subject']);
        $added = strip_tags(date("d/m/Y H:i:s", $item['added']));
        $body = format_comment("[b]Author: " . security::html_safe($item['username']) . "[/b]\n\n" . security::html_safe($item['body']) . "\n");
        // output to browser
        $link = security::html_safe($BASEURL."/forum.php?action=viewtopic&topicid=" . $topicid . "&page=p" . $postid . "#" . $postid);
        ?>

        <item>
        <title><?php print safehtml("[" . FORUM . "] " . $forum . " - " . $subject);?></title>
        <description><?php print safehtml($body); ?></description>
        <link><?php print $link;?></link>
        <pubDate><?php print $added;?></pubDate>
        </item>

       <?php
    }
}

?>

</channel>
</rss>