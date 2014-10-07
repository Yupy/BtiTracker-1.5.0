<?php
/*
* BtiTracker v1.5.0 is a php tracker system for BitTorrent, easy to setup and configure.
* This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
* Updated and Maintained by Yupy.
* Copyright (C) 2004-2014 Btiteam.org
*/

global $db;

if (!user::$current || user::$current["view_torrents"] == "no") {
    // do nothing
} else {
    global $BASEURL, $STYLEPATH, $dblist;
    
    block_begin(LAST_TORRENTS);
    
   ?>
  <table cellpadding='4' cellspacing='1' width='100%'>
  <?php
    
    $sql = "SELECT summary.info_hash AS hash, summary.seeds, summary.leechers, summary.dlbytes AS dwned, format(summary.finished, 0) AS finished, namemap.filename, namemap.url, namemap.info, UNIX_TIMESTAMP(namemap.data) AS added, categories.image, categories.name AS cname, namemap.category AS catid, namemap.size, namemap.external, namemap.uploader FROM summary LEFT JOIN namemap ON summary.info_hash = namemap.info_hash LEFT JOIN categories ON categories.id = namemap.category WHERE summary.leechers + summary.seeds > 0 ORDER BY namemap.data DESC LIMIT " . (int)$GLOBALS["block_last10limit"];
    $row = $db->query($sql) or err_msg(ERROR, CANT_DO_QUERY);
    
	?>
    <tr>
    <td colspan='2' align='center' class='header'>&nbsp;<?php echo TORRENT_FILE; ?>&nbsp;</td>
    <td align='center' class='header'>&nbsp;<?php echo CATEGORY; ?>&nbsp;</td>
    <?php
    if (max(0, user::$current["WT"]) > 0)
        print("<td align='center' class='header'>&nbspWT&nbsp;</td>");
    ?>
    <td align='center' class='header'>&nbsp;<?php echo ADDED; ?>&nbsp;</td>
    <td align='center' class='header'>&nbsp;<?php echo SIZE; ?>&nbsp;</td>
    <td align='center' class='header'>&nbsp;S&nbsp;</td>
    <td align='center' class='header'>&nbsp;L&nbsp;</td>
    <td align='center' class='header'>&nbsp;C&nbsp;</td>
    </tr>
    <?php
    
    if ($row) {
        while ($data = $row->fetch_array(MYSQLI_BOTH)) {
            echo "<tr>\n";
            
            if (utf8::strlen($data["hash"]) > 0) {
                echo "\t<td nowrap align='center' class='lista'>";
                
                // edit and delete picture/link
                if ((user::$current["uid"] == $data["uploader"] || user::$current["delete_torrents"] == "yes") && user::$current["uid"] > 1) {
                    print("<a href='delete.php?info_hash=" . $data["hash"] . "&amp;returnto=" . urlencode("index.php") . "'>" . image_or_link($STYLEPATH . "/delete.png", "", DELETE) . "</a>&nbsp;&nbsp;");
                }
                
                echo "<a href='download.php?id=" . $data["hash"] . "&f=" . rawurlencode($data["filename"]) . ".torrent'><img src='images/torrent.gif' border='0' alt='" . DOWNLOAD_TORRENT . "' title='" . DOWNLOAD_TORRENT . "' /></a>";
                
                if (user::$current["uid"] == $data["uploader"] || user::$current["edit_torrents"] == "yes") {
                    print("&nbsp;&nbsp;<a href='edit.php?info_hash=" . $data["hash"] . "&amp;returnto=" . urlencode("index.php") . "'>" . image_or_link($STYLEPATH . "/edit.png", "", EDIT));
                }
                
                echo "</td>";
                echo "\t<td width=60% class='lista'><a href='details.php?id=" . $data['hash'] . "' title='" . VIEW_DETAILS . ": " . security::html_safe($data["filename"]) . "'>" . security::html_safe($data["filename"]) . "</a>" . ($data["external"] == "no" ? "" : " (<span style='color:red'>EXT</span>)") . "</td>";
                echo "\t<td align='center' class='lista'><a href='torrents.php?category=" . (int)$data['catid'] . "'>" . image_or_link(($data["image"] == "" ? "" : "images/categories/" . $data["image"]), "", security::html_safe($data["cname"])) . "</td>";
                
                //waitingtime
                // only if current user is limited by WT
                if (max(0, user::$current["WT"]) > 0) {
                    $wait    = 0;
                    $resuser = $db->query("SELECT * FROM users WHERE id = " . user::$current["uid"]);
                    $rowuser = $resuser->fetch_array(MYSQLI_BOTH);

                    if (max(0, (int)$rowuser['downloaded']) > 0)
                        $ratio = number_format((int)$rowuser['uploaded'] / (int)$rowuser['downloaded'], 2);
                    else
                        $ratio = 0.0;

                    $res2  = $db->query("SELECT * FROM namemap WHERE info_hash = '" . $db->real_escape_string($data["hash"]) . "'");
                    $added = $res2->fetch_array(MYSQLI_BOTH);

                    $vz    = sql_timestamp_to_unix_timestamp($added["data"]);
                    $timer = floor((vars::$timestamp - $vz) / 3600);
                    if ($ratio < 1.0 && $rowuser['id'] != $added["uploader"]) {
                        $wait = user::$current["WT"];
                    }
                    $wait -= $timer;
                    if ($wait <= 0)
                        $wait = 0;
                    
                    echo "\t<td align='center' class='lista'>" . $wait . " h</td>";
                }
                //end waitingtime
                
                echo "\t<td nowrap='nowrap' class='lista' align='center'>" . get_elapsed_time($data["added"]) . " ago</td>";
                echo "\t<td nowrap='nowrap' class='lista' align='center'>" . misc::makesize((int)$data["size"]) . "</td>";
                
                if ($data["external"] == "no") {
                    echo "\t<td align='center' class='" . linkcolor($data["seeds"]) . "'><a href='peers.php?id=" . $data["hash"] . "' title='" . PEERS_DETAILS . "'>" . (int)$data["seeds"] . "</a></td>\n";
                    echo "\t<td align='center' class='" . linkcolor($data["leechers"]) . "'><a href='peers.php?id=" . $data["hash"] . "' title='" . PEERS_DETAILS . "'>" . (int)$data["leechers"] . "</a></td>\n";
                    
					if ($data["finished"] > 0)
                        echo "\t<td align='center' class='lista'><a href='torrent_history.php?id=" . $data["hash"] . "' title='History - " . security::html_safe($data["filename"]) . "'>" . (int)$data["finished"] . "</a></td>";
                    else
                        echo "\t<td align='center' class='lista'>---</td>";
                } else {
                    // linkcolor
                    echo "\t<td align='center' class='" . linkcolor($data["seeds"]) . "'>" . (int)$data["seeds"] . "</td>";
                    echo "\t<td align='center' class='" . linkcolor($data["leechers"]) . "'>" . (int)$data["leechers"] . "</td>";
					
                    if ($data["finished"] > 0)
                        echo "\t<td align='center' class='lista'>" . (int)$data["finished"] . "</td>";
                    else
                        echo "\t<td align='center' class='lista'>---</td>";
                    
                }
                echo "</tr>\n";
            }
        }
    } else {
        echo "<tr><td class='lista' colspan='9' align='center'>" . NO_TORRENTS . "</td></tr>";
    }
    print("</table>");
    
    block_end();
    
} // end if user can view

?>