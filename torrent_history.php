<?php
require_once ("include/functions.php");
require_once ("include/config.php");

dbconn();

standardheader('History Details');

$id = AddSlashes($_GET["id"]);
if (!isset($id) || !$id)
    die("Error ID");

// control if torrent exist in our db
$res = run_query("SELECT * FROM namemap WHERE info_hash='$id'") or die(((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)));
if ($res) {
   $row=mysqli_fetch_array($res);
   if ($row) {
      $tsize=0+$row["size"];
      }
}
else
    die("Error ID");

// select lastest 30 records for infohash
$res = run_query("SELECT history.*,username, countries.name AS country, countries.flagpic, level, prefixcolor,suffixcolor FROM history INNER JOIN users ON history.uid=users.id INNER JOIN countries ON users.flag=countries.id INNER JOIN users_level ON users.id_level=users_level.id WHERE history.infohash='$id' AND history.date IS NOT NULL ORDER BY date DESC LIMIT 0,30") or die(((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)));

block_begin("Torrent History (last 30 snatchers)");

$spacer = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";

print("<table class=\"lista\" border=\"0\" width=\"100%\">\n");
print("<tr><td align=\"center\" class=\"header\" colspan=\"2\">".USER_NAME."</td>");
print("<td align=\"center\" class=\"header\">".PEER_COUNTRY."</td>");
print("<td align=\"center\" class=\"header\">Active</td>");
print("<td align=\"center\" class=\"header\">".PEER_CLIENT."</td>\n");
print("<td align=\"center\" class=\"header\">".DOWNLOADED."</td>\n");
print("<td align=\"center\" class=\"header\">".UPLOADED."</td>\n");
print("<td align=\"center\" class=\"header\">".RATIO."</td>\n");
print("<td align=\"center\" class=\"header\">".FINISHED."</td></tr>\n");

while ($row = mysqli_fetch_array($res))
{
    print("<tr><td align=\"center\" class=\"lista\">".
       "<a href=\"userdetails.php?id=".$row["uid"]."\">".unesc($row["username"])."</a></td>".
       "<td align=\"center\" class=\"lista\"><a href=\"usercp.php?do=pm&action=edit&uid=$CURUSER[uid]&what=new&to=".urlencode(unesc($row["username"]))."\">".image_or_link("$STYLEPATH/pm.png","","PM")."</a></td>");
  if ($row["flagpic"]!="")
    print("<td align=\"center\" class=\"lista\"><img src=images/flag/".$row["flagpic"]." alt=".$row["country"]." /></td>");
  else
    print("<td align=\"center\" class=\"lista\"><img src=images/flag/unknown.gif alt=".UNKNOWN." /></td>");
  print("<td align=\"center\" class=\"lista\">".$row["active"]."</td>");
  print("<td align=\"center\" class=\"lista\">".htmlsafechars($row["agent"])."</td>");
  $dled=makesize($row["downloaded"]);
  $upld=makesize($row["uploaded"]);
  print("<td align=\"center\" class=\"lista\">".$dled."</td>");
  print("<td align=\"center\" class=\"lista\">".$upld."</td>");
//Peer Ratio
  if (intval($row["downloaded"])>0) {
     $ratio=number_format($row["uploaded"]/$row["downloaded"],2);}
  else {$ratio="oo";}
  print("<td align=\"center\" class=\"lista\">".$ratio."</td>");
//End Peer Ratio

  print("<td align=\"center\" class=\"lista\">".get_elapsed_time($row["date"])." ago</td></tr>");

}

if (mysqli_num_rows($res)==0)
  print("<tr><td align=\"center\" colspan=\"9\" class=\"lista\">No history to display</td></tr>");

print("</table>");

print("</div><br /><br /><center><a href=\"javascript: history.go(-1);\">".BACK."</a>");

block_end();

stdfoot();
?>