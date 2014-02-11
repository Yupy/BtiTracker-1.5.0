<?php
require_once("include/functions.php");
require_once("include/config.php");

dbconn(true);

if ($CURUSER["view_torrents"]!="yes" && $CURUSER["view_forum"]!="yes")
   {
   header(ERR_500);
   die;
}

header('Content-type: text/xml');

function safehtml($string)
{
$validcharset=array(
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

   if (in_array($GLOBALS["charset"],$validcharset))
      return htmlsafechars($string,ENT_COMPAT,$GLOBALS["charset"]);
   else
       return htmlsafechars($string);
}

?>

<rss version="2.0">
<channel>
<title><?php print $SITENAME;?></title>
<description>rss feed script designed and coded by beeman (modified by Lupin and VisiGod)</description>
<link><?php print $BASEURL;?></link>
<lastBuildDate><?php print date("D, d M Y H:i:s T");?></lastBuildDate>
<copyright><?php print "(c) ". date("Y",time())." " .$SITENAME;?></copyright>

<?php

if ($CURUSER["view_torrents"]=="yes")
{
  $getItems = "SELECT namemap.info_hash as id, namemap.comment as description, namemap.filename, summary.seeds AS seeders, summary.leechers, UNIX_TIMESTAMP( namemap.data ) as added, categories.name as cname, namemap.size FROM summary LEFT JOIN namemap ON summary.info_hash = namemap.info_hash LEFT JOIN categories ON categories.id = namemap.category $where ORDER BY data DESC LIMIT 20";
  $doGet=run_query($getItems) or die(((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)));;

  while($item=mysqli_fetch_array($doGet))
   {
    $id=$item['id'];
    $filename=strip_tags($item['filename']);
    $added=strip_tags(date("d/m/Y H:i:s",$item['added']));
    $descr=format_comment($item['description']."\n");
    $seeders=strip_tags($item['seeders']);
    $leechers=strip_tags($item['leechers']);
    // output to browser

?>

  <item>
  <title><?php print safehtml("[".TORRENT."] ".$filename);?></title>
  <description><?php print safehtml($descr)." (".SEEDERS." ".safehtml($seeders)." -- ".LEECHERS." ".safehtml($leechers);?>)</description>
  <link><?php print $BASEURL;?>/details.php?id=<?php print $id;?></link>
  <pubDate><?php print $added;?></pubDate>
  </item>

<?php
  }
}
// forums
if ($CURUSER["view_forum"]=="yes")
{
  $getItems = "select topics.id as topicid, posts.id as postid, forums.name, users.username,topics.subject,posts.added, posts.body from topics inner join posts on posts.topicid=topics.id inner join forums on topics.forumid=forums.id inner join users on users.id=posts.userid ORDER BY added DESC LIMIT 100";
  $doGet=run_query($getItems) or die(((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)));

  while($item=mysqli_fetch_array($doGet))
   {
    $topicid=$item['topicid'];
    $postid=$item['postid'];
    $forum=strip_tags($item['name']);
    $subject=strip_tags($item['subject']);
    $added=strip_tags(date("d/m/Y H:i:s",$item['added']));
    $body=format_comment("[b]Author: ".$item['username']."[/b]\n\n".$item['body']."\n");
    // output to browser
    $link=htmlsafechars($BASEURL."/forum.php?action=viewtopic&topicid=$topicid&page=p$postid#$postid");
?>

  <item>
  <title><?php print safehtml("[".FORUM."] ".$forum." - ".$subject);?></title>
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