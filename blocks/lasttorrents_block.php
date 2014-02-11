<?php
global $CURUSER;
if (!$CURUSER || $CURUSER["view_torrents"]=="no")
   {
    // do nothing
   }
else
    {
  global $BASEURL, $STYLEPATH, $dblist;

  block_begin(LAST_TORRENTS);

  ?>
  <table cellpadding="4" cellspacing="1" width="100%">
  <?php

  $sql = "SELECT summary.info_hash as hash, summary.seeds, summary.leechers, summary.dlbytes AS dwned, format(summary.finished,0) as finished, namemap.filename, namemap.url, namemap.info, UNIX_TIMESTAMP(namemap.data) AS added, categories.image, categories.name AS cname, namemap.category AS catid, namemap.size, namemap.external, namemap.uploader FROM summary LEFT JOIN namemap ON summary.info_hash = namemap.info_hash LEFT JOIN categories ON categories.id = namemap.category WHERE summary.leechers + summary.seeds > 0 ORDER BY namemap.data DESC LIMIT " . $GLOBALS["block_last10limit"];
     $row = run_query($sql) or err_msg(ERROR,CANT_DO_QUERY.((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)));
  ?>
  <tr>
    <td colspan="2" align="center" class="header">&nbsp;<?php echo TORRENT_FILE; ?>&nbsp;</td>
    <td align="center" class="header">&nbsp;<?php echo CATEGORY; ?>&nbsp;</td>
<?php
if (max(0,$CURUSER["WT"])>0)
    print("<td align=\"center\" class=\"header\">&nbspWT&nbsp;</td>");
?>
    <td align="center" class="header">&nbsp;<?php echo ADDED; ?>&nbsp;</td>
    <td align="center" class="header">&nbsp;<?php echo SIZE; ?>&nbsp;</td>
    <td align="center" class="header">&nbsp;S&nbsp;</td>
    <td align="center" class="header">&nbsp;L&nbsp;</td>
    <td align="center" class="header">&nbsp;C&nbsp;</td>
  </tr>
  <?php

  if ($row)
  {
      while ($data=mysqli_fetch_array($row))
      {
      echo "<tr>\n";

          if ( strlen($data["hash"]) > 0 )
          {
             echo "\t<td NOWRAP align=\"center\" class=\"lista\">";

           // edit and delete picture/link
          if (( $CURUSER["uid"] == $data["uploader"] || $CURUSER["delete_torrents"] == "yes" ) &&  $CURUSER["uid"]>1)
           {
             print("<a href='delete.php?info_hash=" . $data["hash"] . "&amp;returnto=" . urlencode("index.php") . "'>".image_or_link("$STYLEPATH/delete.png","",DELETE)."</a>&nbsp;&nbsp;");
             }

             echo "<a href=download.php?id=".$data["hash"]."&f=" . rawurlencode($data["filename"]) . ".torrent><img src='images/torrent.gif' border='0' alt='".DOWNLOAD_TORRENT."' title='".DOWNLOAD_TORRENT."' /></a>";

          if ( $CURUSER["uid"] == $data["uploader"] || $CURUSER["edit_torrents"] == "yes" )
        {
          print("&nbsp;&nbsp;<a href='edit.php?info_hash=" . $data["hash"] . "&amp;returnto=" . urlencode("index.php") . "'>".image_or_link("$STYLEPATH/edit.png","",EDIT));
             }

       echo "</td>";
       if ($GLOBALS["usepopup"])
          echo "\t<td width=60% class=\"lista\"><a href=\"javascript:popdetails('details.php?id=" . $data['hash'] . "');\" title=\"" . VIEW_DETAILS . ": " . $data["filename"] . "\">" . $data["filename"] . "</a>".($data["external"]=="no"?"":" (<span style=\"color:red\">EXT</span>)")."</td>";
       else
          echo "\t<td width=60% class=\"lista\"><a href=\"details.php?id=" . $data['hash'] . "\" title=\"" . VIEW_DETAILS . ": " . $data["filename"] . "\">" . $data["filename"] . "</a>".($data["external"]=="no"?"":" (<span style=\"color:red\">EXT</span>)")."</td>";
       echo "\t<td align=\"center\" class=\"lista\"><a href=torrents.php?category=$data[catid]>" . image_or_link( ($data["image"] == "" ? "" : "images/categories/" . $data["image"]), "", $data["cname"]) . "</td>";

    //waitingtime
    // only if current user is limited by WT
    if (max(0,$CURUSER["WT"])>0)
        {
          $wait=0;
          $resuser=run_query("SELECT * FROM users WHERE id=".$CURUSER["uid"]);
          $rowuser=mysqli_fetch_array($resuser);
          if (max(0,$rowuser['downloaded'])>0) $ratio=number_format($rowuser['uploaded']/$rowuser['downloaded'],2);
          else $ratio=0.0;
          $res2 =run_query("SELECT * FROM namemap WHERE info_hash='".$data["hash"]."'");
          $added=mysqli_fetch_array($res2);
          $vz = sql_timestamp_to_unix_timestamp($added["data"]);
          $timer = floor((time() - $vz) / 3600);
          if($ratio<1.0 && $rowuser['id']!=$added["uploader"]){
              $wait=$CURUSER["WT"];
          }
          $wait -=$timer;
          if ($wait<=0)$wait=0;

          echo "\t<td align=\"center\" class=\"lista\">".$wait." h</td>";
        }
    //end waitingtime

             echo "\t<td nowrap=\"nowrap\" class=\"lista\" align='center'>" . get_elapsed_time($data["added"]) . " ago</td>";
             echo "\t<td nowrap=\"nowrap\" class=\"lista\" align='center'>" . makesize($data["size"]) . "</td>";

           if ( $data["external"] == "no" )
            {
              if ($GLOBALS["usepopup"])
                {
                echo "\t<td align=\"center\" class=\"".linkcolor($data["seeds"])."\"><a href=\"javascript:poppeer('peers.php?id=".$data["hash"]."');\" title=\"".PEERS_DETAILS."\">" . $data["seeds"] . "</a></td>\n";
                echo "\t<td align=\"center\" class=\"".linkcolor($data["leechers"])."\"><a href=\"javascript:poppeer('peers.php?id=".$data["hash"]."');\" title=\"".PEERS_DETAILS."\">" .$data["leechers"] . "</a></td>\n";
                if ($data["finished"]>0)
                   echo "\t<td align=\"center\" class=\"lista\"><a href=\"javascript:poppeer('torrent_history.php?id=".$data["hash"]."');\" title=\"History - ".$data["filename"]."\">" . $data["finished"] . "</a></td>";
                else
                    echo "\t<td align=\"center\" class=\"lista\">---</td>";

                }
              else
                {
                echo "\t<td align=\"center\" class=\"".linkcolor($data["seeds"])."\"><a href=\"peers.php?id=".$data["hash"]."\" title=\"".PEERS_DETAILS."\">" . $data["seeds"] . "</a></td>\n";
                echo "\t<td align=\"center\" class=\"".linkcolor($data["leechers"])."\"><a href=\"peers.php?id=".$data["hash"]."\" title=\"".PEERS_DETAILS."\">" .$data["leechers"] . "</a></td>\n";
                if ($data["finished"]>0)
                   echo "\t<td align=\"center\" class=\"lista\"><a href=\"torrent_history.php?id=".$data["hash"]."\" title=\"History - ".$data["filename"]."\">" . $data["finished"] . "</a></td>";
                else
                    echo "\t<td align=\"center\" class=\"lista\">---</td>";

                }
            }
           else
             {
               // linkcolor
               echo "\t<td align=\"center\" class=\"".linkcolor($data["seeds"])."\">" . $data["seeds"] . "</td>";
               echo "\t<td align=\"center\" class=\"".linkcolor($data["leechers"])."\">" .$data["leechers"] . "</td>";
               if ($data["finished"]>0)
                  echo "\t<td align=\"center\" class=\"lista\">" . $data["finished"] . "</td>";
               else
                   echo "\t<td align=\"center\" class=\"lista\">---</td>";

        }
           echo "</tr>\n";
           }
      }
  }
  else
  {
    echo "<tr><td class=\"lista\" colspan=9 align=center>" . NO_TORRENTS . "</td></tr>";
  }

  print("</table>");

  block_end();

} // end if user can view
?>