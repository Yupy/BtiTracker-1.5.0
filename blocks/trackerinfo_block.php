<?php
global $CURUSER;

if (!$CURUSER || $CURUSER["view_torrents"] == "no")
{
    // do nothing
} else {
    global $SITENAME, $Memcached, $db;

   block_begin(BLOCK_INFO);

       $torrentstats_key = "Torrent::Stats::";
    if (($torrents = $Memcached->get_value($torrentstats_key)) == false)
	{
        $res = $db->execute("SELECT COUNT(*) AS tot FROM namemap") or $db->display_errors();
        if ($res)
        {
            $row = $db->fetch_array($res);
            $torrents = $row["tot"];
        }
        else
            $torrents = 0;

        $Memcached->cache_value($torrentstats_key, $torrents, 3200);
    }

        $userscount_key = "Users::Count::";
    if (($users = $Memcached->get_value($userscount_key)) == false)
	{
        $res = $db->execute("SELECT COUNT(*) AS tot FROM users WHERE id > 1") or $db->display_errors();

        if ($res)
        {
            $row = $db->fetch_array($res);
            $users = $row["tot"];
        }
        else
            $users = 0;

        $Memcached->cache_value($userscount_key, $users, 180);
    }

        $res = $db->execute("SELECT SUM(seeds) AS seeds, SUM(leechers) AS leechs FROM summary") or $db->display_errors();

        if ($res)
        {
            $row = $db->fetch_array($res);
            $seeds = 0 + $row["seeds"];
            $leechers = 0 + $row["leechs"];
        } else {
            $seeds = 0;
            $leechers = 0;
        }

        if ($leechers > 0)
            $percent = number_format(($seeds / $leechers) * 100, 0);
        else
            $percent = number_format($seeds * 100, 0);

        $peers = $seeds + $leechers;

        $totaltraffic_key = "Total::Traffic::";
    if (($traffic = $Memcached->get_value($totaltraffic_key)) == false)
	{
        $res = $db->execute("SELECT SUM(downloaded) AS dled, SUM(uploaded) AS upld FROM users") or $db->display_errors();

        $row = $db->fetch_array($res);
        $dled = 0 + $row["dled"];
        $upld = 0 + $row["upld"];
        $traffic = makesize($dled + $upld);
        $Memcached->cache_value($totaltraffic_key, $traffic, 180);
    }

   print("<tr><td class='blocklist' align='center'>\n");
   print("<table width='100%' cellspacing='2' cellpading='2'>\n");
   print("<tr>\n<td colspan='2' align='center'><u>".unesc($SITENAME)."</u></td></tr>\n");
   print("<tr><td align='left'>".MEMBERS.":</td><td align='right'>".$users."</td></tr>\n");
   print("<tr><td align='left'>".TORRENTS.":</td><td align='right'>".$torrents."</td></tr>\n");
   print("<tr><td align='left'>".SEEDERS.":</td><td align='right'>".$seeds."</td></tr>\n");
   print("<tr><td align='left'>".LEECHERS.":</td><td align='right'>".$leechers."</td></tr>\n");
   print("<tr><td align='left'>".PEERS.":</td><td align='right'>".$peers."</td></tr>\n");
   print("<tr><td align='left'>".SEEDERS."/".LEECHERS.":</td><td align='right'>".$percent."%</td></tr>\n");
   print("<tr><td align='left'>".TRAFFIC.":</td><td align='right'>".$traffic."</td></tr>\n");
   print("</table>\n</td></tr>");
   block_end();

} // end if user can view

?>
