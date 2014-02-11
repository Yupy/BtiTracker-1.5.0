<?php
require_once("include/functions.php");
require_once("include/config.php");
require_once ("include/class.BDecode.php");
require_once ("include/class.BEncode.php");

dbconn();

if (!$CURUSER || $CURUSER["can_download"]=="no")
   {
       standardheader('Download');
       err_msg(ERROR,NOT_AUTH_DOWNLOAD);
       die();
   }
if(ini_get('zlib.output_compression'))
  ini_set('zlib.output_compression','Off');

$infohash=$_GET["id"];
$filepath=$TORRENTSDIR."/".$infohash . ".btf";

if (!is_file($filepath) || !is_readable($filepath))
   {
       standardheader('Download');
       err_msg(ERROR,CANT_FIND_TORRENT);
       stdfoot();
       die();
    }

$f=urldecode($_GET["f"]);

// pid code begin
$result=run_query("SELECT pid FROM users WHERE id=".$CURUSER['uid']);
$row = mysqli_fetch_assoc($result);
$pid=$row["pid"];
if (!$pid)
   {
   $pid=md5($CURUSER['uid']+$CURUSER['username']+$CURUSER['password']+$CURUSER['lastconnect']);
   @run_query("UPDATE users SET pid='".$pid."' WHERE id='".$CURUSER['uid']."'");
}

$result=run_query("SELECT * FROM namemap WHERE info_hash='".$infohash."'");
$row = mysqli_fetch_assoc($result);

if ($row["external"]=="yes" || !$PRIVATE_ANNOUNCE)
   {
    $fd = fopen($filepath, "rb");
    $alltorrent = fread($fd, filesize($filepath));
    fclose($fd);
    header("Content-Type: application/x-bittorrent");
    header('Content-Disposition: attachment; filename="'.$f.'"');
    print($alltorrent);
   }
else
    {
    $fd = fopen($filepath, "rb");
    $alltorrent = fread($fd, filesize($filepath));
    $array = BDecode($alltorrent);
    fclose($fd);
    $array["announce"] = $BASEURL."/announce.php?pid=$pid";
    if (isset($array["announce-list"]) && is_array($array["announce-list"]))
      {
      for ($i=0;$i<count($array["announce-list"]);$i++)
          {
          if (in_array($array["announce-list"][$i][0],$TRACKER_ANNOUNCEURLS))
             {
             if (strpos($array["announce-list"][$i][0],"announce.php")===false)
                $array["announce-list"][$i][0] = trim(str_replace("/announce", "/$pid/announce", $array["announce-list"][$i][0]));
             else
                $array["announce-list"][$i][0] = trim(str_replace("/announce.php", "/announce.php?pid=$pid", $array["announce-list"][$i][0]));
             }
          }
    }
    $alltorrent=BEncode($array);

    header("Content-Type: application/x-bittorrent");
    header('Content-Disposition: attachment; filename="'.$f.'"');
    print($alltorrent);
}
?>