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
    $maintrackertoolbar_torrents = CACHE_PATH . 'maintrackertoolbar_total_torrents.txt';
    $maintrackertoolbar_torrents_expire = 5 * 60;

    if (file_exists($maintrackertoolbar_torrents) && is_array(unserialize(file_get_contents($maintrackertoolbar_torrents))) && (vars::$timestamp - filemtime($maintrackertoolbar_torrents)) < $maintrackertoolbar_torrents_expire) {
        $torrents = unserialize(@file_get_contents($maintrackertoolbar_torrents));
    } else {
        $res = $db->query("SELECT COUNT(*) AS tot FROM namemap");
        if ($res) {
            $row      = $res->fetch_array(MYSQLI_BOTH);
            $torrents = (int)$row["tot"];
        } else
            $torrents = 0;

        $handle = fopen($maintrackertoolbar_torrents, "w+");
        fwrite($handle, serialize($torrents));
        fclose($handle);
    }

    $maintrackertoolbar_users = CACHE_PATH . 'maintrackertoolbar_total_users.txt';
    $maintrackertoolbar_users_expire = 5 * 60;

    if (file_exists($maintrackertoolbar_users) && is_array(unserialize(file_get_contents($maintrackertoolbar_users))) && (vars::$timestamp - filemtime($maintrackertoolbar_users)) < $maintrackertoolbar_users_expire) {
        $users = unserialize(@file_get_contents($maintrackertoolbar_userss));
    } else {
        $res = $db->query("SELECT COUNT(*) AS tot FROM users WHERE id > 1");
        if ($res) {
            $row   = $res->fetch_array(MYSQLI_BOTH);
            $users = (int)$row["tot"];
        } else
            $users = 0;

        $handle = fopen($maintrackertoolbar_users, "w+");
        fwrite($handle, serialize($users));
        fclose($handle);
    }
    
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

    $maintrackertoolbar_traffic = CACHE_PATH . 'maintrackertoolbar_total_traffic.txt';
    $maintrackertoolbar_traffic_expire = 5 * 60;
    
    if (file_exists($maintrackertoolbar_traffic) && is_array(unserialize(file_get_contents($maintrackertoolbar_traffic))) && (vars::$timestamp - filemtime($maintrackertoolbar_traffic)) < $maintrackertoolbar_traffic_expire) {
        $row = unserialize(@file_get_contents($maintrackertoolbar_traffic));
    } else {
        $res = $db->query("SELECT SUM(downloaded) AS dled, SUM(uploaded) AS upld FROM users");
        $row = $res->fetch_array(MYSQLI_BOTH);

        $handle = fopen($maintrackertoolbar_traffic, "w+");
        fwrite($handle, serialize($row));
        fclose($handle);
    }
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
