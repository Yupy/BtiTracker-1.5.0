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
    $res = $db->query("SELECT COUNT(*) AS tot FROM namemap");
    if ($res) {
        $row      = $res->fetch_array(MYSQLI_BOTH);
        $torrents = (int)$row["tot"];
    } else
        $torrents = 0;
    
    $res = $db->query("SELECT COUNT(*) AS tot FROM users WHERE id > 1");
    if ($res) {
        $row   = $res->fetch_array(MYSQLI_BOTH);
        $users = (int)$row["tot"];
    } else
        $users = 0;
    
    $res = $db->query("SELECT SUM(seeds) AS seeds, SUM(leechers) AS leechs FROM summary");
    if ($res) {
        $row      = $res->fetch_array(MYSQLI_BOTH);
        $seeds    = 0 + (int)$row["seeds"];
        $leechers = 0 + (int)$row["leechs"];
    } else {
        $seeds    = 0;
        $leechers = 0;
    }
    
    if ($leechers > 0)
        $percent = number_format(($seeds / $leechers) * 100, 0);
    else
        $percent = number_format($seeds * 100, 0);
    
    $peers = $seeds + $leechers;
    
    $res     = $db->query("SELECT SUM(downloaded) AS dled, SUM(uploaded) AS upld FROM users");
    $row     = $res->fetch_array(MYSQLI_BOTH);
    $dled    = 0 + (float)$row["dled"];
    $upld    = 0 + (float)$row["upld"];
    $traffic = misc::makesize($dled + $upld);

    ?>
    <table class='lista' cellpadding='2' cellspacing='0' width='100%'>
    <tr>
    <td class='lista' align='center'><?php echo BLOCK_INFO; ?>:</td>
    <td class='lista' align='center'><?php echo MEMBERS; ?>:</td><td align='right'><?php echo $users; ?></td>
    <td class='lista' align='center'><?php echo TORRENTS; ?>:</td><td align='right'><?php echo $torrents; ?></td>
    <td class='lista' align='center'><?php echo SEEDERS; ?>:</td><td align='right'><?php echo $seeds; ?></td>
    <td class='lista' align='center'><?php echo LEECHERS; ?>:</td><td align='right'><?php echo $leechers; ?></td>
    <td class='lista' align='center'><?php echo PEERS; ?>:</td><td align='right'><?php echo $peers; ?></td>
    <td class='lista' align='center'><?php echo SEEDERS." / ".LEECHERS; ?>:</td><td align='right'><?php echo $percent."%"; ?></td>
    <td class='lista' align='center'><?php echo TRAFFIC; ?>:</td><td align='right'><?php echo $traffic; ?></td>
    </tr>
	</table>
    <?php
}

?>
