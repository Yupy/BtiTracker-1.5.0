<?php
/*
* BtiTracker v1.5.0 is a php tracker system for BitTorrent, easy to setup and configure.
* This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
* Updated and Maintained by Yupy.
* Copyright (C) 2004-2014 Btiteam.org
*/
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'include'.DIRECTORY_SEPARATOR.'functions.php');

$scriptname = security::html_safe($_SERVER["PHP_SELF"]);
$addparam = '';

dbconn();

standardheader('Torrents');

if (!user::$current || user::$current["view_torrents"] != "yes")
{
    err_msg(ERROR.NOT_AUTHORIZED." ".MNU_TORRENT."!", SORRY."...");
    stdfoot();
    exit();
}

block_begin(MNU_TORRENT);

if (isset($_GET["search"]))
{
    $trova = security::html_safe(str_replace ("+", " ", $_GET["search"]));
} else {
    $trova = '';
}

?>
<p align='center'>
<form action='<?php $scriptname;?>' method='get'>
   <table border='0' class='lista' align='center'>
   <tr>
   <td class='block'><?php echo TORRENT_SEARCH;?></td>
   <td class='block'><?php echo CATEGORY_FULL;?></td>
   <td class='block'><?php echo TORRENT_STATUS;?></td>
   <td class='block'>&nbsp;</td>
   </tr>
   <tr>
   <td><input type='text' name='search' size='30' maxlength='50' value='<?php $trova;?>'></td>
   <td>
   <?php
    $category = (!isset($_GET["category"]) ? 0 : explode(";", (int)$_GET["category"]));

    if (is_array($category))
        $category = array_map("intval", $category);
    else
        $category = 0;

    categories( $category[0] );

    if (isset($_GET["active"]))
    {
        $active = intval($_GET["active"]);
    } else {
        $active=1;
    }
    // all
    if ($active == 0)
    {
        $where = " WHERE 1 = 1";
        $addparam .= "active=0";
    } // active only
    elseif ($active == 1) {
        $where = " WHERE leechers + seeds > 0";
        $addparam .= "active=1";
    } // dead only
    elseif ($active == 2) {
        $where = " WHERE leechers + seeds = 0";
        $addparam .= "active=2";
    }
    ?>
    </td>
    <td>
    <select name='active' size='1'>
    <option value='0'<?php if ($active == 0) echo " selected='selected' " ?>><?php echo ALL; ?></option>
    <option value='1'<?php if ($active == 1) echo " selected='selected' " ?>><?php echo ACTIVE_ONLY; ?></option>
    <option value='2'<?php if ($active == 2) echo " selected='selected' " ?>><?php echo DEAD_ONLY; ?></option>
    </select>
    </td>
    <td><input type='submit' value='<?php echo SEARCH; ?>'></td>
    </tr>
    </table>
</form>
</p>
<table width='100%'>
<tr>
<?php

if ($category[0] > 0) {
   $where .= " AND category IN (".implode(",", $category).")";
   $addparam .= "&amp;category=".implode(";", $category);
}

global $pagertop, $pagerbottom, $query_select;
// Search
if (isset($_GET["search"])) {
    $testocercato = trim($_GET["search"]);
    $testocercato = explode(" ", $testocercato);

    if ($_GET["search"] != '')
        $search = "search=" . implode("+",$testocercato);
    for ($k = 0; $k < count($testocercato); $k++) {
        $query_select .= " namemap.filename LIKE '%" . $db->real_escape_string($testocercato[$k]) . "%'";

        if ($k < count($testocercato) - 1)
            $query_select .= " AND ";
    }

    $where .= " AND " . $query_select;
}

$res = $db->query("SELECT COUNT(*) FROM summary LEFT JOIN namemap ON summary.info_hash = namemap.info_hash " . $where);

$row = $res->fetch_row();
$count = (int)$row[0];

if (!isset($search))
    $search = '';

if ($count) {
    if ($addparam != '') {
        if ($search != '')
            $addparam .= "&amp;" . $search . "&amp;";
   } else {
        if ($search != '')
            $addparam .=  $search . "&amp;";
        else
            $addparam .= '';
    }

    $torrentperpage = user::$current["torrentsperpage"];

    if ($torrentperpage == 0)
        $torrentperpage = ($ntorrents == 0 ? 15 : $ntorrents);

    // Fixed possible SQL injection (thanks to jeremie78)
    $accepted_orders = array('speed', 'dwned', 'finished', 'leechers','seeds', 'size', 'data', 'filename', 'cname');
    $order = (isset($_GET['order']) && in_array($_GET['order'], $accepted_orders)) ? $db->real_escape_string($_GET['order']) : 'data';
    $by = (isset($_GET["by"]) && $db->real_escape_string($_GET["by"]) == 'ASC') ? 'ASC' : 'DESC';

    list($pagertop, $limit) = misc::pager($torrentperpage, $count,  $scriptname."?" . $addparam.(utf8::strlen($addparam) > 0 ? "&amp;" : "")."order=" . $order . "&amp;by=" . $by . "&amp;");

    if ($SHOW_UPLOADER)
        $query = "SELECT summary.info_hash AS hash, summary.seeds, summary.leechers, summary.finished AS finished, summary.dlbytes AS dwned, namemap.filename, namemap.url, namemap.info, namemap.anonymous, summary.speed, UNIX_TIMESTAMP( namemap.data ) AS added, categories.image, categories.name AS cname, namemap.category AS catid, namemap.size, namemap.external, namemap.uploader AS upname, users.username AS uploader, prefixcolor, suffixcolor FROM summary LEFT JOIN namemap ON summary.info_hash = namemap.info_hash LEFT JOIN categories ON categories.id = namemap.category LEFT JOIN users ON users.id = namemap.uploader LEFT JOIN users_level ON users.id_level=users_level.id " . $where . " ORDER BY " . $order . " " . $by . " " . $limit;
    else
        $query = "SELECT summary.info_hash AS hash, summary.seeds, summary.leechers, summary.finished AS finished, summary.dlbytes AS dwned, namemap.filename, namemap.url, namemap.info, summary.speed, UNIX_TIMESTAMP( namemap.data ) AS added, categories.image, categories.name AS cname, namemap.category AS catid, namemap.size, namemap.external, namemap.uploader FROM summary LEFT JOIN namemap ON summary.info_hash = namemap.info_hash LEFT JOIN categories ON categories.id = namemap.category " . $where . " ORDER BY " . $order . " " . $by . " " . $limit;

   $results = $db->query($query) or err_msg(ERROR, CANT_DO_QUERY . "<br />" . $query);
}

$i = 0;

if ($by == "ASC")
    $mark = "&nbsp;&#8593";
else
    $mark = "&nbsp;&#8595";

?>
</tr>
<tr>
<td colspan='2' align='center'><?php echo $pagertop ?></td>
</tr>

<tr>
<table width='100%' class='lista'>
<!-- Column Headers  -->
<tr>
<td align='center' class='header'><?php echo "<a href='" . $scriptname . "?" . $addparam . "" . (utf8::strlen($addparam) > 0 ? "&amp;" : "") . "order=cname&amp;by=" . ($order == "cname" && $by == "ASC" ? "DESC" : "ASC") . "'>" . CATEGORY . "</a>" . ($order == "cname" ? $mark : ""); ?></td>
<td align='center' class='header'><?php echo "<a href='" . $scriptname . "?" . $addparam . "" . (utf8::strlen($addparam) > 0 ? "&amp;" : "") . "order=filename&amp;by=" . ($order == "filename" && $by == "ASC" ? "DESC" : "ASC") . "'>" . FILE . "</a>" . ($order == "filename" ? $mark : ""); ?></td>
<td align='center' class='header'><?php echo COMMENT; ?></td>
<td align='center' class='header'><?php echo RATING; ?></td>
<?php
if (user::$current["WT"] > 0)
    print("<td align='center' class='header'>" . WT . "</td>");
?>
<td align='center' class='header'><?php echo DOWN; ?></td>
<td align='center' class='header'><?php echo "<a href='" . $scriptname . "?" . $addparam . "" . (utf8::strlen($addparam) > 0 ? "&amp;" : "") . "order=data&amp;by=" . ($order == "data" && $by == "ASC" ? "DESC" : "ASC") . "'>" . ADDED . "</a>" . ($order == "data" ? $mark : ""); ?></td>
<td align='center' class='header'><?php echo "<a href='" . $scriptname . "?" . $addparam . "" . (utf8::strlen($addparam) > 0 ? "&amp;" : "") . "order=size&amp;by=" . ($order == "size" && $by == "DESC" ? "ASC" : "DESC") . "'>" . SIZE . "</a>" . ($order == "size" ? $mark : ""); ?></td>
<?php
if ($SHOW_UPLOADER)
    print("<td align='center' class='header'>" . UPLOADER . "</td>");
?>
<td align='center' class='header'><?php echo "<a href='" . $scriptname . "?" . $addparam . "" . (utf8::strlen($addparam) > 0 ? "&amp;" : "") . "order=seeds&amp;by=" . ($order == "seeds" && $by == "DESC" ? "ASC" : "DESC")."'>" . SHORT_S . "</a>" . ($order == "seeds" ? $mark : ""); ?></td>
<td align='center' class='header'><?php echo "<a href='" . $scriptname . "?" . $addparam . "" . (utf8::strlen($addparam) > 0 ? "&amp;" : "") . "order=leechers&amp;by=" . ($order == "leechers" && $by == "DESC" ? "ASC" : "DESC")."'>" . SHORT_L . "</a>" . ($order == "leechers" ? $mark : ""); ?></td>
<td align='center' class='header'><?php echo "<a href='" . $scriptname . "?" . $addparam . "" . (utf8::strlen($addparam) > 0 ? "&amp;" : "") . "order=finished&amp;by=" . ($order == "finished" && $by == "ASC" ? "DESC" : "ASC")."'>" . SHORT_C . "</a>" . ($order == "finished" ? $mark : ""); ?></td>
<td align='center' class='header'><?php echo "<a href='" . $scriptname . "?" . $addparam . "" . (utf8::strlen($addparam) > 0 ? "&amp;" : "") . "order=dwned&amp;by=" . ($order == "dwned" && $by == "ASC" ? "DESC" : "ASC")."'>" . DOWNLOADED . "</a>" . ($order == "dwned" ? $mark : ""); ?></td>
<td align='center' class='header'><?php echo "<a href='" . $scriptname . "?" . $addparam . "" . (utf8::strlen($addparam) > 0 ? "&amp;" : "") . "order=speed&amp;by=" . ($order == "speed" && $by == "ASC" ? "DESC" : "ASC")."'>" . SPEED . "</a>" . ($order == "speed" ? $mark : ""); ?></td>
<td align='center' class='header'><?php echo AVERAGE; ?></td>
</tr>
<tr>

<?php
if ($SHOW_UPLOADER && user::$current["WT"] > 0)
    echo "<td colspan='15' class='lista'></td>";
elseif ($SHOW_UPLOADER || user::$current["WT"] > 0)
    echo "<td colspan='14' class='lista'></td>";
else
    echo "<td colspan='13\' class='lista'></td>";
?>
</tr>
<?php

if ($count) {
    if (!isset($values[$i % 2]))
	    $writeout = '';
    else
        $writeout = $values[$i % 2];

    while ($data = $results->fetch_array(MYSQLI_BOTH))
    {
        $commentres = $db->query("SELECT COUNT(*) AS comments FROM comments WHERE info_hash = '" . $db->real_escape_string($data["hash"]) . "'");
        $commentdata = $commentres->fetch_assoc();

        echo "<tr>\n";
        echo "\t<td align='center' class='lista'><a href='torrents.php?category=" . (int)$data['catid'] . "'>" . image_or_link(($data["image"] == "" ? "" : "images/categories/" . $data["image"]), "", security::html_safe($data["cname"])) . "</td>";
        echo "\t<td align='left' class='lista'><a href='details.php?id=" . $data["hash"] . "' title='" . VIEW_DETAILS . ": " . security::html_safe($data["filename"]) . "'>" . security::html_safe($data["filename"]) . "</a>" . ($data["external"] == "no" ? "" : " (<span style='color:red'>EXT</span>)") . "</td>";

    if ($commentdata) {
        if ($commentdata["comments"] > 0)
        {
            echo "\t<td align='center' class='lista'><a href='details.php?id=" . $data["hash"] . "#comments' title='Comments for: " . security::html_safe($data["filename"]) . "'>" . (int)$commentdata["comments"] . "</a></td>";
        }
		else
            echo "\t<td align='center' class='lista'>---</td>";
    }
    else
	    echo "\t<td align='center' class='lista'>---</td>";

    // Rating
    $vres = $db->query("SELECT SUM(rating) AS totrate, COUNT(*) AS votes FROM ratings WHERE infohash = '" . $db->real_escape_string($data["hash"]) . "'");
    $vrow = @$vres->fetch_array(MYSQLI_BOTH);

    if ($vrow && $vrow["votes"] >= 1)
    {
        $totrate = round($vrow["totrate"] / (int)$vrow["votes"], 1);

        if ($totrate == 5)
            $totrate = "<img src='" . $STYLEPATH . "/5.gif' title='" . (int)$vrow['votes'] . " " . VOTES_RATING . ": " . $totrate . " / 5.0)' />";
        elseif ($totrate > 4.4 && $totrate < 5)
            $totrate = "<img src='" . $STYLEPATH . "/4.5.gif' title='" . (int)$vrow['votes'] . " " . VOTES_RATING . ": " . $totrate . " / 5.0)' />";
        elseif ($totrate > 3.9 && $totrate < 4.5)
            $totrate = "<img src='" . $STYLEPATH . "/4.gif' title='" . (int)$vrow['votes'] . " " . VOTES_RATING . ": " . $totrate . " / 5.0)' />";
        elseif ($totrate > 3.4 && $totrate < 4)
            $totrate = "<img src='" . $STYLEPATH . "/3.5.gif' title='" . (int)$vrow['votes'] . " " . VOTES_RATING . ": " . $totrate . " / 5.0)' />";
        elseif ($totrate > 2.9 && $totrate < 3.5)
            $totrate = "<img src='" . $STYLEPATH . "/3.gif' title='" . (int)$vrow['votes'] . " " . VOTES_RATING . ": " . $totrate . " / 5.0)' />";
        elseif ($totrate > 2.4 && $totrate < 3)
            $totrate = "<img src='" . $STYLEPATH . "/2.5.gif' title='" . (int)$vrow['votes'] . " " . VOTES_RATING . ": " . $totrate . " / 5.0)' />";
        elseif ($totrate > 1.9 && $totrate < 2.5)
            $totrate = "<img src='" . $STYLEPATH . "/2.gif' title='" . (int)$vrow['votes'] . " " . VOTES_RATING . ": " . $totrate . " / 5.0)' />";
        elseif ($totrate > 1.4 && $totrate < 2)
            $totrate = "<img src='" . $STYLEPATH . "/1.5.gif' title='" . (int)$vrow['votes'] . " " . VOTES_RATING . ": " . $totrate . " / 5.0)' />";
        else
            $totrate = "<img src='" . $STYLEPATH . "/1.gif' title='" . (int)$vrow['votes'] . " " . VOTES_RATING . ": " . $totrate . " / 5.0)' />";
    }
    else
        $totrate = NA;

    echo "\t<td align='center' class='lista'>" . $totrate . "</td>\n";
    // end rating

    //waitingtime
    if (user::$current["WT"] > 0)
    {
        $wait = 0;
        $resuser = $db->query("SELECT * FROM users WHERE id = " . user::$current["uid"]);
        $rowuser = $resuse->fetch_array(MYSQLI_BOTH);

        $wait = 0;

        if (intval($rowuser['downloaded']) > 0)
		    $ratio = number_format((int)$rowuser['uploaded'] / (int)$rowuser['downloaded'], 2);
        else
		    $ratio = 0.0;

        $vz = $data["added"];
        $timer = floor((vars::$timestamp - $vz) / 3600);

        if ($ratio < 1.0 && $rowuser['id'] != $data["uploader"]) {
            $wait = user::$current["WT"];
        }

        $wait-= $timer;

        if ($wait <= 0)
		    $wait = 0;

        if (utf8::strlen($data["hash"]) > 0)
            echo "\t<td align='center' class='lista'>" . ($wait > 0 ? $wait." h" : "---") . "</td>\n";
        //end waitingtime
    }

    echo "\t<td align='center' class='lista'><a href='download.php?id=" . $data["hash"] . "&amp;f=" . urlencode($data["filename"]) . ".torrent'>".image_or_link("images/download.gif","","torrent")."</a></td>\n";

   include(INCL_PATH . "offset.php");
   echo "\t<td align='center' class='lista'>" . date("d/m/Y H:m:s", $data["added"] - $offset) . "</td>\n";
   echo "\t<td align='center' class='lista'>" . misc::makesize((int)$data["size"]) . "</td>\n";

    //Uploaders nick details
    if ($SHOW_UPLOADER && $data["anonymous"] == "true")
        echo "\t<td align='center' class='lista'>" . ANONYMOUS . "</td>\n";
    elseif ($SHOW_UPLOADER && $data["anonymous"] == "false")
        echo "\t<td align='center' class='lista'><a href='userdetails.php?id=" . (int)$data["upname"] . "'>" . StripSlashes($data['prefixcolor'].security::html_safe($data["uploader"]).$data['suffixcolor']) . "</a></td>\n";
    //Uploaders nick details

    if ($data["external"] == "no")
    {
        echo "\t<td align='center' class='" . linkcolor($data["seeds"]) . "'><a href='peers.php?id=" . $data["hash"] . "' title='" . PEERS_DETAILS . "'>" . (int)$data["seeds"] . "</a></td>\n";
        echo "\t<td align='center' class='" . linkcolor($data["leechers"]) . "'><a href='peers.php?id=" . $data["hash"] . "' title='" . PEERS_DETAILS . "'>" . (int)$data["leechers"] . "</a></td>\n";

		if ($data["finished"] > 0)
            echo "\t<td align='center' class='lista'><a href='torrent_history.php?id=" . $data["hash"] . "' title='History - " . security::html_safe($data["filename"]) . "'>" . number_format((int)$data["finished"], 0) . "</a></td>";
        else
            echo "\t<td align='center' class='lista'>---</td>";
    } else {
        // linkcolor
        echo "\t<td align='center' class='" . linkcolor($data["seeds"]) . "'>" . (int)$data["seeds"] . "</td>";
        echo "\t<td align='center' class='" . linkcolor($data["leechers"]) . "'>" . (int)$data["leechers"] . "</td>";

        if ($data["finished"] > 0)
            echo "\t<td align='center' class='lista'>" . number_format((int)$data["finished"], 0) . "</td>";
        else
            echo "\t<td align='center' class='lista'>---</td>";
    }

    if ($data["dwned"] > 0)
        echo "\t<td align='center' class='lista'>" . misc::makesize((int)$data["dwned"]) . "</td>";
    else
        echo "\t<td align='center' class='lista'>" . NA . "</td>";

    if ($data["speed"] < 0 || $data["external"] == "yes") {
        $speed = NA;

        echo "\t<td align='center' class='lista'>" . $speed . "</td>\n";
    }
    else if ($data["speed"] > 2097152) {
        $speed = round((int)$data["speed"] / 1048576, 2) . " MiB per sec";

        echo "\t<td align='center' class='lista'>" . $speed . "</td>\n";
    } else {
        $speed = round((int)$data["speed"] / 1024, 2) . " KiB per sec";

        echo "\t<td align='center' class='lista'>" . $speed . "</TD>\n";
    }

    // progress
    if ($data["external"] == "yes")
        $prgsf = floor((((int)$data["seeds"]) / ((int)$data["leechers"] > 0 ? (int)$data["leechers"] : 1)) * 100) . " %";
    else {
        $id = $db->real_escape_string($data['hash']);
        $subres = $db->query("SELECT SUM(bytes) AS to_go, COUNT(*) AS numpeers FROM peers WHERE infohash = '" . $id . "'");
        $subres2 = $db->query("SELECT size FROM namemap WHERE info_hash = '" . $id . "'");
        $torrent = $subres2->fetch_array(MYSQLI_BOTH);
        $subrow = $subres->fetch_array(MYSQLI_BOTH);

        $tmp = 0 + (int)$subrow["numpeers"];

        if ($tmp > 0) {
            $tsize = (0 + (int)$torrent["size"]) * $tmp;
            $tbyte = 0 + (int)$subrow["to_go"];
            $prgs = (($tsize - $tbyte) / $tsize) * 100;
            $prgsf = floor($prgs);
        }
        else
            $prgsf = 0;
        $prgsf .= "%";
    }
    print("<td align='center' class='lista'>" . $prgsf . "</td>");

    echo "</tr>\n";
    $i++;
    }
} // if count

if ($i == 0 && $SHOW_UPLOADER)
    echo "<tr><td class='lista' colspan='17' align='center'>" . NO_TORRENTS . "</td></tr>";
elseif ($i == 0 && !$SHOW_UPLOADER)
    echo "<td><td class='lista' colspan='16' align='center'>" . NO_TORRENTS . "</td></tr>";

?>
</tr>
</table>
<tr><td colspan='2' align='center'><?php echo $pagertop ?></td></tr>

<?php

block_end();
stdfoot();

?>