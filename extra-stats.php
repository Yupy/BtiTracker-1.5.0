<?php
require_once ("include/functions.php");
require_once ("include/config.php");

dbconn();

function usertable($res, $frame_caption) {
 block_begin($frame_caption, 'center');
 begin_table();
 $num = 0;
 while ($a = mysqli_fetch_assoc($res)) {
   ++$num;

   if ($a["downloaded"]) {
     $ratio = $a["uploaded"] / $a["downloaded"];
     $ratio = number_format($ratio, 2);
   }
   else
     $ratio = INFINITE;
   if (!isset($menu)) $menu = "";
   if ($menu != "1") {
echo "<tr>"."<TABLE width=\"100%\" class=\"lista\">"
."<td class=\"header\" align=\"center\">".USER_LEVEL."</td>"
."<td class=\"header\" align=\"center\">".USER_NAME."</td>"
."<td class=\"header\" align=\"center\">".UPLOADED."</td>"
."<td class=\"header\" align=\"center\">".DOWNLOADED."</td>"
."<td class=\"header\" align=\"center\">".RATIO."</td>"
."</tr>";
     $menu = 1;
   }
   $topuser=($a["id"]>1?"<a href=\"userdetails.php?id=" . $a["id"] . "\"><b>" . $a["username"] ."</b></a>":"<b>" . $a["username"] ."</b>");
   print("<tr><td class=\"lista\" align=\"center\" width=\"20%\" > $num</td><td class=\"lista\" align=\"center\">$topuser</td><td class=\"lista\" align=\"center\" width=\"20%\">" . makesize($a["uploaded"]) .
         "</td><td class=\"lista\" align=\"center\" width=\"20%\">" . makesize($a["downloaded"]) .
         "</td><td class=\"lista\" align=\"center\" width=\"20%\">$ratio</td></tr>");
   }
end_table();

block_end();
}

function _torrenttable($res, $frame_caption,$speed="false") {

 block_begin($frame_caption, 'center');
 begin_table();
 $num = 0;
 while ($a = mysqli_fetch_assoc($res)) {
     ++$num;
     if ($a["leechers"]>0)
     {
       $r = $a["seeds"] / $a["leechers"];
       $ratio = number_format($r, 2);
     }
     else
       $ratio = INFINITE;
    if (!isset($menu)) $menu = "";
    if ($menu != "1") {
         echo "<tr>"."<TABLE width=\"100%\" class=\"lista\">"
         ."<td class=\"header\" align=\"center\">".USER_LEVEL."</td>"
         ."<td class=\"header\">".FILE."</td>";

          if ($speed!="true")
          {
            echo "<td class=\"header\" align=\"center\">".FINISHED."</td>"
                ."<td class=\"header\" align=\"center\">".SEEDERS."</td>"
                ."<td class=\"header\" align=\"center\">".LEECHERS."</td>"
                ."<td class=\"header\" align=\"center\">".PEERS."</td>"
                ."<td class=\"header\" align=\"center\">".RATIO."</td>";
            }
            else
            {
                echo "<td class=\"header\" align=\"right\">".SPEED."</td>";
            }
          echo "</tr>";
       $menu = 1;
       }

       print("<tr><td class=lista align=center>$num</td><td class=lista align=left>");
       print("<a href=\""."details.php?id=".$a['hash']."\">"."<b>");
       print($a["name"] . "</b></a></td>");
        if ($speed!="true")
          {
       print(" <td class=\"lista\" align=\"center\" width=\"10%\" > <a href=\"torrent_history.php?id=".$a["hash"]."\">" . number_format($a["finished"]) .
       "</a></td><td class=\"lista\" align=\"center\" width=\"10%\" > <a href=\"peers.php?id=".$a["hash"]."\">" . number_format($a["seeds"]) .
       "</a></td><td class=\"lista\" align=\"center\" width=\"10%\" > <a href=\"peers.php?id=".$a["hash"]."\">" . number_format($a["leechers"]) .
       "</a></td><td class=\"lista\" align=\"center\" width=\"10%\" > <a href=\"peers.php?id=".$a["hash"]."\">" . number_format($a["leechers"] + $a["seeds"]) .
       "</a></td><td class=\"lista\" align=\"center\" width=\"10%\" > $ratio</td>\n");
       }
       else
       {
       print(" <td class=\"lista\" align=\"center\">" . makesize($a["speed"])."/s"."\n");
       }
   }
   end_table();
   block_end();
}

standardheader('Tracker Statistics');

// the display the box only if number of rows is > 0
if ($CURUSER["view_users"]=="yes")
{
  $r = run_query("SELECT * FROM users WHERE uploaded>0 ORDER BY uploaded DESC LIMIT 10") or die;
  if (mysqli_num_rows($r)>0) { usertable($r, TOP_10_UPLOAD); echo "<br /><br />"; }
  $r = run_query("SELECT * FROM users WHERE uploaded>0 AND downloaded>0 ORDER BY downloaded DESC LIMIT 10") or die;
  if (mysqli_num_rows($r)>0) { usertable($r, TOP_10_DOWNLOAD); echo "<br /><br />";}
  $r = run_query("SELECT * FROM users WHERE downloaded > 104857600 ORDER BY uploaded - downloaded DESC LIMIT 10") or die;
  if (mysqli_num_rows($r)>0) { usertable($r, TOP_10_SHARE." <font class=lista>".MINIMUM_100_DOWN."</font>"); echo "<br /><br />";}
  $r = run_query("SELECT * FROM users WHERE downloaded > 104857600 ORDER BY downloaded - uploaded DESC, downloaded DESC LIMIT 10") or die;
  if (mysqli_num_rows($r)>0) { usertable($r, TOP_10_WORST." <font class=lista>".MINIMUM_100_DOWN."</font>"); echo "<br /><br />"; }
 }
if ($CURUSER["view_torrents"]=="yes")
{
 $r = run_query("SELECT summary.info_hash as hash, summary.seeds as seeds, summary.leechers as leechers, summary.finished,  summary.dlbytes as dwned , namemap.filename as name, namemap.url as url, namemap.info, summary.speed as speed, namemap.uploader FROM summary LEFT  JOIN namemap ON summary.info_hash = namemap.info_hash ORDER BY seeds + leechers DESC LIMIT 10") or sqlerr();
  if (mysqli_num_rows($r)>0) { _torrenttable($r, TOP_10_ACTIVE." </font>"); echo "<br /><br />";}
 $r = run_query("SELECT summary.info_hash as hash, summary.seeds as seeds, summary.leechers as leechers, summary.finished,  summary.dlbytes as dwned , namemap.filename as name, namemap.url as url, namemap.info, summary.speed as speed, namemap.uploader FROM summary LEFT  JOIN namemap ON summary.info_hash = namemap.info_hash WHERE seeds >= 5 ORDER BY seeds / leechers DESC, seeds DESC LIMIT 10") or sqlerr();
  if (mysqli_num_rows($r)>0) { _torrenttable($r, TOP_10_BEST_SEED."<font class=small>(".MINIMUM_5_SEED.")</font>"); echo "<br /><br />";}
 $r = run_query("SELECT summary.info_hash as hash, summary.seeds as seeds, summary.leechers as leechers, summary.finished,  summary.dlbytes as dwned , namemap.filename as name, namemap.url as url, namemap.info, summary.speed as speed, namemap.uploader FROM summary LEFT  JOIN namemap ON summary.info_hash = namemap.info_hash WHERE leechers >= 5 AND finished > 0 ORDER BY seeds / leechers ASC, leechers DESC LIMIT 10") or sqlerr();
  if (mysqli_num_rows($r)>0) { _torrenttable($r, TOP_10_WORST_SEED." <font class=small>(".MINIMUM_5_LEECH.")</font>"); echo "<br /><br />";}
 $r = run_query("SELECT summary.info_hash as hash, summary.seeds as seeds, summary.leechers as leechers, summary.finished,  summary.dlbytes as dwned , namemap.filename as name, namemap.url as url, namemap.info, summary.speed as speed, namemap.uploader FROM summary LEFT  JOIN namemap ON summary.info_hash = namemap.info_hash WHERE external='no' ORDER BY speed DESC, seeds DESC LIMIT 10") or sqlerr();
  if (mysqli_num_rows($r)>0) { _torrenttable($r, TOP_10_BSPEED); echo "<br /><br />";}
 $r = run_query("SELECT summary.info_hash as hash, summary.seeds as seeds, summary.leechers as leechers, summary.finished,  summary.dlbytes as dwned , namemap.filename as name, namemap.url as url, namemap.info, summary.speed as speed, namemap.uploader FROM summary LEFT  JOIN namemap ON summary.info_hash = namemap.info_hash WHERE external='no' ORDER BY speed ASC, seeds DESC LIMIT 10") or sqlerr();
  if (mysqli_num_rows($r)>0) { _torrenttable($r, TOP_10_WSPEED); echo "<br /><br />";}
}
 stdfoot();
?>