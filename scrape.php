<?php

$BASEPATH=dirname(__FILE__);

require("$BASEPATH/include/config.php");
require("$BASEPATH/include/common.php");
# protection against sql injection, xss attack
require_once $BASEPATH.'/include/crk_protection.php';

// controll if client can handle gzip
if ($GZIP_ENABLED)
    {
     if (stristr($_SERVER["HTTP_ACCEPT_ENCODING"],"gzip") && extension_loaded('zlib') && ini_get("zlib.output_compression") == 0)
         {
         if (ini_get('output_handler')!='ob_gzhandler')
             {
             ob_start("ob_gzhandler");
             }
         else
             {
             ob_start();
             }
     }
     else
         {
         ob_start();
         }
}
// end gzip controll

error_reporting(0);

// connect to db
if ($GLOBALS["persist"])
    $conres=($GLOBALS["___mysqli_ston"] = mysqli_connect($dbhost,  $dbuser,  $dbpass)) or show_error("Tracker errore - mysql_connect: " . ((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)));
else
    $conres=($GLOBALS["___mysqli_ston"] = mysqli_connect($dbhost,  $dbuser,  $dbpass)) or show_error("Tracker errore - mysql_connect: " . ((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)));

    ((bool)mysqli_query($GLOBALS["___mysqli_ston"], "USE $database")) or show_error("Tracker errore - $database - ".((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)));

if (isset($_GET["pid"])) $pid = $_GET["pid"];
else $pid = "";

if (strpos($pid, "?"))
{
  $tmp = substr($pid , strpos($pid , "?"));
  $pid  = substr($pid , 0,strpos($pid , "?"));
  $tmpname = substr($tmp, 1, strpos($tmp, "=")-1);
  $tmpvalue = substr($tmp, strpos($tmp, "=")+1);
  $_GET[$tmpname] = $tmpvalue;
}

$usehash = false;

$pid = AddSlashes($pid);

// if private announce turned on and PID empty string or not send by client
if (($pid=="" || !$pid) && $PRIVATE_SCRAPE)
   show_error("Sorry. Private scrape is ON and PID system is required");


if (isset($_GET["info_hash"]))
{
  if ($pid!="")
     $qryStr=substr($_SERVER["QUERY_STRING"],strlen("?pid=$pid"));
  else
      $qryStr=$_SERVER["QUERY_STRING"];
  // support for multi-scrape
  // more info @ http://wiki.depthstrike.com/index.php/P2P:Programming:Trackers:PHP:Multiscrape
  foreach (explode("&", $qryStr) as $item)
   {
    if (substr($item, 0, 10) == "info_hash=")
      {
        $ihash=urldecode(substr($item,10));

        if (strlen($ihash) == 20)
            $ihash = bin2hex($ihash);
        else if (strlen($ihash) == 40)
            if (!verifyHash($ihash)) continue;
        else
            continue;

         $newmatches[]=$ihash;
      }
    }

    if (get_magic_quotes_gpc())
        $info_hash = stripslashes(join($newmatches,"','"));
    else
        $info_hash = join($newmatches,"','");

    $info_hash = strtolower("('$info_hash')");
    $usehash = true;
}

if ($usehash)
    $query = run_query("SELECT info_hash, filename FROM namemap WHERE external='no' AND info_hash IN $info_hash");
else
    $query = run_query("SELECT info_hash, filename FROM namemap WHERE external='no'");

$namemap = array();
while ($row = mysqli_fetch_row($query))
    $namemap[$row[0]] = $row[1];

if ($usehash)
    $query = run_query("SELECT summary.info_hash, summary.seeds, summary.leechers, summary.finished FROM summary LEFT JOIN namemap ON namemap.info_hash=summary.info_hash  WHERE namemap.external='no' AND summary.info_hash IN $info_hash") or show_error("Database error. Cannot complete request.");
else
    $query = run_query("SELECT summary.info_hash, summary.seeds, summary.leechers, summary.finished FROM summary LEFT JOIN namemap ON namemap.info_hash=summary.info_hash  WHERE namemap.external='no' ORDER BY summary.info_hash") or show_error("Database error. Cannot complete request.");


$result="d5:filesd";

while ($row = mysqli_fetch_row($query))
{
    $hash = hex2bin($row[0]);
    $result.="20:".$hash."d";
    $result.="8:completei".$row[1]."e";
    $result.="10:downloadedi".$row[3]."e";
    $result.="10:incompletei".$row[2]."e";
    if (isset($namemap[$row[0]]))
        $result.="4:name".strlen($namemap[$row[0]]).":".$namemap[$row[0]];
    $result.="e";
}

$result.="ee";

echo $result;

((is_null($___mysqli_res = mysqli_close($GLOBALS["___mysqli_ston"]))) ? false : $___mysqli_res);

?>