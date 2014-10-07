<?php
/*
* BtiTracker v1.5.0 is a php tracker system for BitTorrent, easy to setup and configure.
* This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
* Updated and Maintained by Yupy.
* Copyright (C) 2004-2014 Btiteam.org
*/
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'include'.DIRECTORY_SEPARATOR.'functions.php');

dbconn(true);

if (user::$current["view_torrents"] != "yes")
{
    header(ERR_500);
    die;
}

header("Content-type: text/xml; charset=" . $GLOBALS["charset"]);

print("<?php xml version='1.0' encoding='" . $GLOBALS["charset"]."'?>");

?>
<rss version='2.0'>
<channel>
<title><?php print $SITENAME;?></title>
<description>rss feed script designed and coded by beeman (modified by Lupin and VisiGod)</description>
<link><?php print $BASEURL;?></link>
<lastBuildDate><?php print date("D, d M Y H:i:s O");?></lastBuildDate>
<copyright><?php print "(c) " . date("Y", vars::$timestamp) . " " . $SITENAME;?></copyright>

<?php

$getItems = "SELECT namemap.info_hash AS id, namemap.comment AS description, namemap.filename, summary.seeds AS seeders, summary.leechers, UNIX_TIMESTAMP( namemap.data ) AS added, categories.name AS cname, namemap.size FROM summary LEFT JOIN namemap ON summary.info_hash = namemap.info_hash LEFT JOIN categories ON categories.id = namemap.category ORDER BY data DESC LIMIT 20";
$doGet = $db->query($getItems);

while($item = $doGet->fetch_array(MYSQLI_BOTH))
{
    $id = $db->real_escape_string($item['id']);
    $filename = security::html_safe($item['filename']);
    $added = strip_tags(date("D, d M Y H:i:s O", $item['added']));
    $cat = strip_tags($item['cname']);
    $seeders = strip_tags($item['seeders']);
    $leechers = strip_tags($item['leechers']);
    $desc = format_comment($item['description']);
    $f = rawurlencode($item['filename']);
    // output to browser

?>
  <item>
  <title><![CDATA[<?php print security::html_safe("[" . $cat . "] " . $filename . " [" . SEEDERS . " (" . $seeders . ") / " . LEECHERS . " (" . $leechers . ")]");?>]]></title>
  <description><![CDATA[<?php print $desc; ?>]]></description>
  <link><?php print $BASEURL . "/details.php?id=" . $id;?></link>
  <guid><?php print $BASEURL . "/details.php?id=" . $id;?></guid>
  <enclosure url="<?php print($BASEURL . "/download.php?id=" . $id . "&amp;f=" . $f . ".torrent");?>" length="<?php print $item["size"] ?>" type="application/x-bittorrent" />
  <pubDate><?php print $added;?></pubDate>
  </item>

<?php
}

?>
</channel>
</rss>