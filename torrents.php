<?php
require_once ("include/functions.php");
require_once ("include/config.php");

$scriptname = htmlsafechars($_SERVER["PHP_SELF"]);
$addparam = "";

dbconn();

standardheader('Torrents');

if(!$CURUSER || $CURUSER["view_torrents"]!="yes")
{
    err_msg(ERROR.NOT_AUTHORIZED." ".MNU_TORRENT."!",SORRY."...");
    stdfoot();
    exit();
}

block_begin(MNU_TORRENT);


if(isset($_GET["search"]))
{
    $trova = htmlsafechars(str_replace ("+"," ",$_GET["search"]));
} else {
    $trova = "";
}
?>

<p align="center">
<form action="<?php $scriptname;?>" method="get">
  <table border="0" class="lista" align="center">
  <tr>
  <td class="block"><?php echo TORRENT_SEARCH;?></td>
  <td class="block"><?php echo CATEGORY_FULL;?></td>
  <td class="block"><?php echo TORRENT_STATUS;?></td>
  <td class="block">&nbsp;</td>
  </tr>
  <tr>
  <td><input type="text" name="search" size="30" maxlength="50" value="<?php $trova;?>"></td>
  <td><?php
    $category = (!isset($_GET["category"])?0:explode(";",$_GET["category"]));
    // sanitize categories id
    if (is_array($category))
        $category = array_map("intval",$category);
    else
        $category = 0;

    categories( $category[0] );

    if(isset($_GET["active"]))
    {
        $active=intval($_GET["active"]);
    } else {
        $active=1;
    }
    // all
    if($active==0)
    {
        $where = " WHERE 1=1";
        $addparam.="active=0";
    } // active only
    elseif($active==1){
        $where = " WHERE leechers+seeds > 0";
        $addparam.="active=1";
    } // dead only
    elseif($active==2){
        $where = " WHERE leechers+seeds = 0";
        $addparam.="active=2";
    }
  ?>
  </td>
  <td>
  <select name="active" size="1">
  <option value="0"<?php if ($active==0) echo " selected=selected " ?>><?php echo ALL; ?></option>
  <option value="1"<?php if ($active==1) echo " selected=selected " ?>><?php echo ACTIVE_ONLY; ?></option>
  <option value="2"<?php if ($active==2) echo " selected=selected " ?>><?php echo DEAD_ONLY; ?></option>
  </select>
  </td>
  <td><input type="submit" value="<?php echo SEARCH; ?>"></td>
  </tr>
  </table>
</form>
</p>
<TABLE width="100%" >
<TR>
<?php

/* Rewrite, part 1: encode "WHERE" statement only. */

// echo "Totale torrents trovati: $count";
// selezione categoria
if ($category[0]>0) {
   $where .= " AND category IN (".implode(",",$category).")"; // . $_GET["category"];
   $addparam.="&amp;category=".implode(";",$category); // . $_GET["category"];
}
global $pagertop, $pagerbottom, $query_select;
// Search
if (isset($_GET["search"])) {
   $testocercato = trim($_GET["search"]);
   $testocercato = explode(" ",$testocercato);
   if ($_GET["search"]!="")
      $search = "search=" . implode("+",$testocercato);
    for ($k=0; $k < count($testocercato); $k++) {
        $query_select .= " namemap.filename LIKE '%" . ((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $testocercato[$k]) : ((trigger_error("Error...", E_USER_ERROR)) ? "" : "")) . "%'";
        if ($k<count($testocercato)-1)
           $query_select .= " AND ";
    }
    $where .= " AND " . $query_select;
}

// FINE RICERCA

// conteggio dei torrents...
$where_key = "torrent_count::";
 if (($count = $Memcached->get_value($where_key)) == false) {
$res = run_query("SELECT COUNT(*) FROM summary LEFT JOIN namemap ON summary.info_hash = namemap.info_hash $where")
        or die(((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)));

$row = mysqli_fetch_row($res);
$count = $row[0];
$Memcached->cache_value($where_key, $count, 3600); //Expire Time = 3600 secs
}

if (!isset($search)) $search = "";

if ($count) {
   if ($addparam != "") {
      if ($search != "")
         $addparam .= "&amp;" . $search . "&amp;";
   }
   else {
      if ($search != "")
         $addparam .=  $search . "&amp;";
      else
          $addparam .= ""; //$scriptname . "?";
      }

    $torrentperpage=intval($CURUSER["torrentsperpage"]);
    if ($torrentperpage==0)
        $torrentperpage=($ntorrents==0?15:$ntorrents);

// Fixed possible SQL injection (thanks to jeremie78)
   $accepted_orders = array('speed', 'dwned', 'finished', 'leechers','seeds', 'size', 'data', 'filename', 'cname');
   $order = (isset($_GET['order']) && in_array($_GET['order'],$accepted_orders)) ? $_GET['order'] : 'data';
   $by = (isset($_GET["by"]) && $_GET["by"]=='ASC') ? 'ASC' : 'DESC';


    list($pagertop, $pagerbottom, $limit) = pager($torrentperpage, $count,  $scriptname."?" . $addparam.(strlen($addparam)>0?"&amp;":"")."order=$order&amp;by=$by&amp;");

// Do the query with the uploader nickname
if ($SHOW_UPLOADER)
    $query = "SELECT summary.info_hash as hash, summary.seeds, summary.leechers, summary.finished as finished,  summary.dlbytes as dwned , namemap.filename, namemap.url, namemap.info, namemap.anonymous, summary.speed, UNIX_TIMESTAMP( namemap.data ) as added, categories.image, categories.name as cname, namemap.category as catid, namemap.size, namemap.external, namemap.uploader as upname, users.username as uploader, prefixcolor, suffixcolor FROM summary LEFT JOIN namemap ON summary.info_hash = namemap.info_hash LEFT JOIN categories ON categories.id = namemap.category LEFT JOIN users ON users.id = namemap.uploader LEFT JOIN users_level ON users.id_level=users_level.id $where ORDER BY $order $by $limit";

// Do the query without the uploader nickname
else
    $query = "SELECT summary.info_hash as hash, summary.seeds, summary.leechers, summary.finished as finished,  summary.dlbytes as dwned , namemap.filename, namemap.url, namemap.info, summary.speed, UNIX_TIMESTAMP( namemap.data ) as added, categories.image, categories.name as cname, namemap.category as catid, namemap.size, namemap.external, namemap.uploader FROM summary LEFT JOIN namemap ON summary.info_hash = namemap.info_hash LEFT JOIN categories ON categories.id = namemap.category $where ORDER BY $order $by $limit";
// End the queries
   $results = run_query($query) or err_msg(ERROR,CANT_DO_QUERY.((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false))."<br>".$query);
}

$i = 0;

if ($by=="ASC")
    $mark="&nbsp;&#8593";
else
    $mark="&nbsp;&#8595";

?>
</TR>
<TR>
<TD colspan="2" align="center"> <?php echo $pagertop ?></td>
</tr>

<TR>
<TABLE width="100%" class="lista">
<!-- Column Headers  -->
<TR>
<?php
?>
<TD align="center" class="header"><?php echo "<a href=\"$scriptname?$addparam".(strlen($addparam)>0?"&amp;":"")."order=cname&amp;by=".($order=="cname" && $by=="ASC"?"DESC":"ASC")."\">".CATEGORY."</a>".($order=="cname"?$mark:""); ?></TD>
<TD align="center" class="header"><?php echo "<a href=\"$scriptname?$addparam".(strlen($addparam)>0?"&amp;":"")."order=filename&amp;by=".($order=="filename" && $by=="ASC"?"DESC":"ASC")."\">".FILE."</a>".($order=="filename"?$mark:""); ?></TD>
<TD align="center" class="header"><?php echo COMMENT; ?></TD>
<TD align="center" class="header"><?php echo RATING; ?></TD>
<?php
if (intval($CURUSER["WT"])>0)
    print("<TD align=\"center\" class=\"header\">".WT."</TD>");
?>
<TD align="center" class="header"><?php echo DOWN; ?></TD>
<TD align="center" class="header"><?php echo "<a href=\"$scriptname?$addparam".(strlen($addparam)>0?"&amp;":"")."order=data&amp;by=".($order=="data" && $by=="ASC"?"DESC":"ASC")."\">".ADDED."</a>".($order=="data"?$mark:""); ?></TD>
<TD align="center" class="header"><?php echo "<a href=\"$scriptname?$addparam".(strlen($addparam)>0?"&amp;":"")."order=size&amp;by=".($order=="size" && $by=="DESC"?"ASC":"DESC")."\">".SIZE."</a>".($order=="size"?$mark:""); ?></TD>
<?php
if ($SHOW_UPLOADER)
    print ("<TD align=\"center\" class=\"header\">".UPLOADER."</TD>");
?>
<TD align="center" class="header"><?php echo "<a href=\"$scriptname?$addparam".(strlen($addparam)>0?"&amp;":"")."order=seeds&amp;by=".($order=="seeds" && $by=="DESC"?"ASC":"DESC")."\">".SHORT_S."</a>".($order=="seeds"?$mark:""); ?></TD>
<TD align="center" class="header"><?php echo "<a href=\"$scriptname?$addparam".(strlen($addparam)>0?"&amp;":"")."order=leechers&amp;by=".($order=="leechers" && $by=="DESC"?"ASC":"DESC")."\">".SHORT_L."</a>".($order=="leechers"?$mark:""); ?></TD>
<TD align="center" class="header"><?php echo "<a href=\"$scriptname?$addparam".(strlen($addparam)>0?"&amp;":"")."order=finished&amp;by=".($order=="finished" && $by=="ASC"?"DESC":"ASC")."\">".SHORT_C."</a>".($order=="finished"?$mark:""); ?></TD>
<TD align="center" class="header"><?php echo "<a href=\"$scriptname?$addparam".(strlen($addparam)>0?"&amp;":"")."order=dwned&amp;by=".($order=="dwned" && $by=="ASC"?"DESC":"ASC")."\">".DOWNLOADED."</a>".($order=="dwned"?$mark:""); ?></TD>
<TD align="center" class="header"><?php echo "<a href=\"$scriptname?$addparam".(strlen($addparam)>0?"&amp;":"")."order=speed&amp;by=".($order=="speed" && $by=="ASC"?"DESC":"ASC")."\">".SPEED."</a>".($order=="speed"?$mark:"");; ?></TD>
<TD align="center" class="header"><?php echo AVERAGE; ?></TD>
</TR>
<TR>

<?php
if ($SHOW_UPLOADER && intval($CURUSER["WT"])>0)
    echo "<TD colspan=\"15\" class=\"lista\"></TD>";
elseif ($SHOW_UPLOADER || intval($CURUSER["WT"])>0)
    echo "<TD colspan=\"14\" class=\"lista\"></TD>";
else
    echo "<TD colspan=\"13\" class=\"lista\"></TD>";
?>
</TR>
<?php
if ($count) {
  if (!isset($values[$i % 2])) $writeout = "";
  else $writeout = $values[$i % 2];
  while ($data=mysqli_fetch_array($results))
  {
   // search for comments
   $commentres = run_query("SELECT COUNT(*) as comments FROM comments WHERE info_hash='" . $data["hash"] . "'");
   $commentdata = mysqli_fetch_assoc($commentres);
   echo "<TR>\n";
   echo "\t<td align=\"center\" class=\"lista\"><a href=torrents.php?category=$data[catid]>".image_or_link(($data["image"]==""?"":"images/categories/" . $data["image"]),"",$data["cname"])."</td>";
   echo "\t<TD align=\"left\" class=\"lista\"><A HREF=\"details.php?id=".$data["hash"]."\" title=\"".VIEW_DETAILS.": ".$data["filename"]."\">".$data["filename"]."</A>".($data["external"]=="no"?"":" (<span style=\"color:red\">EXT</span>)")."</td>";
   if ($commentdata) {
      if ($commentdata["comments"]>0)
        {
            echo "\t<TD align=\"center\" class=\"lista\"><A HREF=\"details.php?id=".$data["hash"]."#comments\" title=\"".VIEW_DETAILS.": ".$data["filename"]."\">".$commentdata["comments"]."</A></td>";
        }
     else
         echo "\t<TD align=\"center\" class=\"lista\">--</td>";
   }
   else echo "\t<TD align=\"center\" class=\"lista\">--</td>";

   // Rating
   $vres = run_query("SELECT sum(rating) as totrate, count(*) as votes FROM ratings WHERE infohash = '" . $data["hash"] . "'");
   $vrow = @mysqli_fetch_array($vres);
   if ($vrow && $vrow["votes"]>=1)
      {
      $totrate=round($vrow["totrate"]/$vrow["votes"],1);
      if ($totrate==5)
         $totrate="<img src=$STYLEPATH/5.gif title=\"$vrow[votes] ".VOTES_RATING.": $totrate/5.0)\" />";
      elseif ($totrate>4.4 && $totrate<5)
         $totrate="<img src=$STYLEPATH/4.5.gif title=\"$vrow[votes] ".VOTES_RATING.": $totrate/5.0)\" />";
      elseif ($totrate>3.9 && $totrate<4.5)
         $totrate="<img src=$STYLEPATH/4.gif title=\"$vrow[votes] ".VOTES_RATING.": $totrate/5.0)\" />";
      elseif ($totrate>3.4 && $totrate<4)
         $totrate="<img src=$STYLEPATH/3.5.gif title=\"$vrow[votes] ".VOTES_RATING.": $totrate/5.0)\" />";
      elseif ($totrate>2.9 && $totrate<3.5)
         $totrate="<img src=$STYLEPATH/3.gif title=\"$vrow[votes] ".VOTES_RATING.": $totrate/5.0)\"  />";
      elseif ($totrate>2.4 && $totrate<3)
         $totrate="<img src=$STYLEPATH/2.5.gif title=\"$vrow[votes] ".VOTES_RATING.": $totrate/5.0)\"  />";
      elseif ($totrate>1.9 && $totrate<2.5)
         $totrate="<img src=$STYLEPATH/2.gif title=\"$vrow[votes] ".VOTES_RATING.": $totrate/5.0)\"  />";
      elseif ($totrate>1.4 && $totrate<2)
         $totrate="<img src=$STYLEPATH/1.5.gif title=\"$vrow[votes] ".VOTES_RATING.": $totrate/5.0)\"  />";
      else
         $totrate="<img src=$STYLEPATH/1.gif title=\"$vrow[votes] ".VOTES_RATING.": $totrate/5.0)\"  />";
      }
   else
       $totrate=NA;

   echo "\t<TD align=\"center\" class=\"lista\">$totrate</td>\n";
    // end rating

    //waitingtime
    // display only if the curuser have some WT restriction
    if (intval($CURUSER["WT"])>0)
        {
        $wait=0;
        $resuser=run_query("SELECT * FROM users WHERE id=".$CURUSER["uid"]);
        $rowuser=mysqli_fetch_array($resuser);
        $wait=0;
        if (intval($rowuser['downloaded'])>0) $ratio=number_format($rowuser['uploaded']/$rowuser['downloaded'],2);
        else $ratio=0.0;

        $vz = $data["added"];
        $timer = floor((time() - $vz) / 3600);
        if($ratio<1.0 && $rowuser['id']!=$data["uploader"]){
            $wait=$CURUSER["WT"];
        }
        $wait -=$timer;

        if ($wait<=0)$wait=0;
       if (strlen($data["hash"]) > 0)
            echo "\t<td align=\"center\" class=\"lista\">".($wait>0?$wait." h":"---")."</td>\n"; // WT
    //end waitingtime
    }
       echo "\t<TD align=\"center\" class=\"lista\"><A HREF=download.php?id=".$data["hash"]."&amp;f=" . urlencode($data["filename"]) . ".torrent>".image_or_link("images/download.gif","","torrent")."</A></TD>\n";

   include("include/offset.php");
   echo "\t<td align=\"center\" class=\"lista\">" . date("d/m/Y",$data["added"]-$offset) . "</td>\n"; // data
   echo "\t<td align=\"center\" class=\"lista\">" . makesize($data["size"]) . "</td>\n";
//Uploaders nick details
if ($SHOW_UPLOADER && $data["anonymous"] == "true")
echo "\t<td align=\"center\" class=\"lista\">" . ANONYMOUS . "</td>\n";
elseif ($SHOW_UPLOADER && $data["anonymous"] == "false")
echo "\t<td align=\"center\" class=\"lista\"><a href=userdetails.php?id=" . $data["upname"] . ">".StripSlashes($data['prefixcolor'].$data["uploader"].$data['suffixcolor'])."</a></td>\n";
//Uploaders nick details
   if ($data["external"]=="no")
      {
        echo "\t<td align=\"center\" class=\"".linkcolor($data["seeds"])."\"><a href=\"peers.php?id=".$data["hash"]."\" title=\"".PEERS_DETAILS."\">" . $data["seeds"] . "</a></td>\n";
        echo "\t<td align=\"center\" class=\"".linkcolor($data["leechers"])."\"><a href=\"peers.php?id=".$data["hash"]."\" title=\"".PEERS_DETAILS."\">" .$data["leechers"] . "</a></td>\n";
        if ($data["finished"]>0)
           echo "\t<td align=\"center\" class=\"lista\"><a href=\"torrent_history.php?id=".$data["hash"]."\" title=\"History - ".$data["filename"]."\">" . number_format($data["finished"],0) . "</a></td>";
        else
            echo "\t<td align=\"center\" class=\"lista\">---</td>";
      }
   else
       {
       // linkcolor
       echo "\t<td align=\"center\" class=\"".linkcolor($data["seeds"])."\">" . $data["seeds"] . "</td>";
       echo "\t<td align=\"center\" class=\"".linkcolor($data["leechers"])."\">" .$data["leechers"] . "</td>";
       if ($data["finished"]>0)
          echo "\t<td align=\"center\" class=\"lista\">" . number_format($data["finished"],0) . "</td>";
       else
           echo "\t<td align=\"center\" class=\"lista\">---</td>";
   }
   if ($data["dwned"]>0)
      echo "\t<td align=\"center\" class=\"lista\">" . makesize($data["dwned"]) . "</td>";
   else
       echo "\t<td align=\"center\" class=\"lista\">".NA."</td>";

   if ($data["speed"] < 0 || $data["external"]=="yes") {
      $speed = NA;
      echo "\t<TD align=\"center\" class=\"lista\">$speed</TD>\n";
   }
       else if ($data["speed"] > 2097152) {
            $speed = round($data["speed"]/1048576,2) . " MB/sec";
            echo "\t<TD align=\"center\" class=\"lista\">$speed</TD>\n";
   }
       else {
               $speed = round($data["speed"] / 1024, 2) . " KB/sec";
               echo "\t<TD align=\"center\" class=\"lista\">$speed</TD>\n";
   }
  // progress
  if ($data["external"]=="yes")
     $prgsf=floor((($data["seeds"])/($data["leechers"]>0?$data["leechers"]:1))*100)."%";  //NA;
  else {
       $id = $data['hash'];
       $subres = run_query("SELECT sum(bytes) as to_go, count(*) as numpeers FROM peers where infohash='$id'" ) or ((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false));
       $subres2 = run_query("SELECT size FROM namemap WHERE info_hash ='$id'") or ((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false));
       $torrent = mysqli_fetch_array($subres2);
       $subrow = mysqli_fetch_array($subres);
       $tmp=0+$subrow["numpeers"];
       if ($tmp>0) {
          $tsize=(0+$torrent["size"])*$tmp;
          $tbyte=0+$subrow["to_go"];
          $prgs=(($tsize-$tbyte)/$tsize) * 100; //100 * (1-($tbyte/$tsize));
          $prgsf=floor($prgs);
          }
       else
           $prgsf=0;
       $prgsf.="%";
  }
  print("<td align=\"center\" class=\"lista\">".$prgsf ."</td>");

   echo "</TR>\n";
   $i++;
  }
} // if count

if ($i == 0 && $SHOW_UPLOADER)
         echo "<TR><TD class=\"lista\" colspan=\"17\" align=\"center\">".NO_TORRENTS."</TD></TR>";
elseif ($i == 0 && !$SHOW_UPLOADER) echo "<TR><TD class=\"lista\" colspan=\"16\" align=\"center\">".NO_TORRENTS."</TD></TR>";

?>
</TR>
</TABLE>
<TR><TD colspan="2" align="center"> <?php echo $pagerbottom ?></TD></TR>

<?php

block_end();
stdfoot();
?>
