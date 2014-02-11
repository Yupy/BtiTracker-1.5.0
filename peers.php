<?php
require_once ("include/functions.php");
require_once ("include/config.php");

dbconn();

standardheader('Peer Details');

$id = AddSlashes($_GET["id"]);
if (!isset($id) || !$id)
    die("Error ID");


$res = run_query("SELECT * FROM namemap WHERE info_hash='$id'") or die(((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)));
if ($res) {
   $row=mysqli_fetch_array($res);
   if ($row) {
      $tsize=0+$row["size"];
      }
}
else
    die("Error ID");
$res = run_query("SELECT * FROM peers LEFT JOIN countries ON peers.dns=countries.domain WHERE infohash='$id' ORDER BY bytes ASC, status DESC") or die(((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)));

block_begin(PEER_LIST);

$spacer = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";

print("<table width=100% class=\"lista\" border=\"0\">\n");
print("<tr><td align=center class=\"header\" colspan=2>".USER_NAME."</td>");
print("<td align=center class=\"header\">".PEER_COUNTRY."</td>");
print("<td align=center class=\"header\">".PEER_PORT."</td>");
print("<td align=center class=\"header\">".PEER_PROGRESS."</td>");
print("<td align=center class=\"header\">".PEER_STATUS."</td>");
print("<td align=center class=\"header\">".PEER_CLIENT."</td>\n");
print("<td align=center class=\"header\">".DOWNLOADED."</td>\n");
print("<td align=center class=\"header\">".UPLOADED."</td>\n");
print("<td align=center class=\"header\">".RATIO."</td>\n");
print("<td align=center class=\"header\">".SEEN."</td></tr>\n");

while ($row = mysqli_fetch_array($res))
{
  // for user name instead of peer
 if ($PRIVATE_ANNOUNCE)
    $resu=run_query("SELECT users.username,users.id,countries.flagpic,countries.name FROM users LEFT JOIN countries ON countries.id=users.flag WHERE users.pid='".$row["pid"]."'");
 else
    $resu=run_query("SELECT users.username,users.id,countries.flagpic,countries.name FROM users LEFT JOIN countries ON countries.id=users.flag WHERE users.cip='".$row["ip"]."'");
 if ($resu)
    {
    $rowuser=mysqli_fetch_row($resu);
    if ($rowuser && $rowuser[1]>1)
      {
        print("<tr><td align=center class=\"lista\">".
           "<a href=\"userdetails.php?id=$rowuser[1]\">".unesc($rowuser[0])."</a></td>".
           "<td align=center class=\"lista\"><a href=\"usercp.php?do=pm&action=edit&uid=$CURUSER[uid]&what=new&to=".urlencode(unesc($rowuser[0]))."\">".image_or_link("$STYLEPATH/pm.png","","PM")."</a></td>");
      }
    else
        print("<tr><td align=left class=\"lista\" colspan=2>".GUEST."</td>");
    }
  if ($row["flagpic"]!="" && $row["flagpic"]!="unknown.gif")
    print("<td align=center class=\"lista\"><img src=\"images/flag/".$row["flagpic"]."\" alt=\"".unesc($row["name"])."\" /></td>");
  elseif ($rowuser[2]!="" && !empty($rowuser[2]))
    print("<td align=center class=\"lista\"><img src=\"images/flag/".$rowuser[2]."\" alt=\"".unesc($rowuser[3])."\" /></td>");
  else
    print("<td align=center class=\"lista\"><img src=\"images/flag/unknown.gif\" alt=\"".UNKNOWN."\" /></td>");

  print("<td align=center class=\"lista\">".$row["port"]."</td>");
  $stat=floor((($tsize - $row["bytes"]) / $tsize) *100);
  $progress="<table width=100 cellspacing=0 cellpadding=0><tr><td class=\"progress\" align=left>";
  $progress.="<img height=10 height=10 width=".number_format($stat,0)." src=\"$STYLEPATH/progress.jpg\"></td></tr></table>";
  print("<td valign=top align=center class=\"lista\">".$stat."%<br />" . $progress . "</td>\n");
  print("<td align=center class=\"lista\">".$row["status"]."</td>");
  print("<td align=center class=\"lista\">".htmlsafechars(getagent(unesc($row["client"]),unesc($row["peer_id"])))."</td>");
  $dled=makesize($row["downloaded"]);
  $upld=makesize($row["uploaded"]);
  print("<td align=center class=\"lista\">".$dled."</td>");
  print("<td align=center class=\"lista\">".$upld."</td>");
//Peer Ratio
  if (intval($row["downloaded"])>0) {
     $ratio=number_format($row["uploaded"]/$row["downloaded"],2);}
  else {$ratio="oo";}
  print("<td align=center class=\"lista\">".$ratio."</td>");
//End Peer Ratio

  print("<td align=center class=\"lista\">".get_elapsed_time($row["lastupdate"])." ago</td></tr>");

}

if (mysqli_num_rows($res)==0)
  print("<tr><td align=center colspan=11 class=\"lista\">".NO_PEERS."</td></tr>");

print("</table>");

print("</div><br /><br /><center><a href=\"javascript: history.go(-1);\">".BACK."</a>");

block_end();

stdfoot();
?>