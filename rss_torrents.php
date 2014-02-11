<?php
require_once("include/functions.php");
require_once("include/config.php");

dbconn(true);

if ($CURUSER["view_torrents"]!="yes")
   {
   header(ERR_500);
   die;
}

header("Content-type: text/xml; charset=".$GLOBALS["charset"]);

print("<?php xml version=\"1.0\" encoding=\"".$GLOBALS["charset"]."\"?>");
?>

<rss version="2.0">
<channel>
<title><?php print $SITENAME;?></title>
<description>rss feed script designed and coded by beeman (modified by Lupin and VisiGod)</description>
<link><?php print $BASEURL;?></link>
<lastBuildDate><?php print date("D, d M Y H:i:s O");?></lastBuildDate>
<copyright><?php print "(c) ". date("Y",time())." " .$SITENAME;?></copyright>

<?php

  $getItems = "SELECT namemap.info_hash as id, namemap.comment as description, namemap.filename, summary.seeds AS seeders, summary.leechers, UNIX_TIMESTAMP( namemap.data ) as added, categories.name as cname, namemap.size FROM summary LEFT JOIN namemap ON summary.info_hash = namemap.info_hash LEFT JOIN categories ON categories.id = namemap.category ORDER BY data DESC LIMIT 20";
  $doGet=run_query($getItems) or die(((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)));;

  while($item=mysqli_fetch_array($doGet))
   {
    $id=$item['id'];
    $filename=safehtml($item['filename']);
    $added=strip_tags(date("D, d M Y H:i:s O",$item['added']));
    $cat=strip_tags($item['cname']);
    $seeders=strip_tags($item['seeders']);
    $leechers=strip_tags($item['leechers']);
    $desc=format_comment($item['description']);
    $f=rawurlencode($item['filename']);
    // output to browser

?>

  <item>
  <title><![CDATA[<?php print htmlsafechars("[$cat] $filename [".SEEDERS." ($seeders)/".LEECHERS." ($leechers)]");?>]]></title>
  <description><![CDATA[<?php print $desc; ?>]]></description>
  <link><?php print "$BASEURL/details.php?id=$id";?></link>
  <guid><?php print "$BASEURL/details.php?id=$id";?></guid>
  <enclosure url="<?php print("$BASEURL/download.php?id=$id&amp;f=$f.torrent");?>" length="<?php print $item["size"] ?>" type="application/x-bittorrent" />
  <pubDate><?php print $added;?></pubDate>
  </item>

<?php
}

?>
</channel>
</rss>