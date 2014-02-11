<?php
global $CURUSER;
if (!$CURUSER || $CURUSER["view_torrents"]=="no")
   {
    // do nothing
   }
else
    {
   global $SITENAME;

   block_begin(BLOCK_INFO);

   $res=run_query("select count(*) as tot FROM namemap");
   if ($res)
      {
      $row=mysqli_fetch_array($res);
      $torrents=$row["tot"];
      }
   else
       $torrents=0;

   $res=run_query("select count(*) as tot FROM users where id>1");
   if ($res)
      {
      $row=mysqli_fetch_array($res);
      $users=$row["tot"];
      }
   else
       $users=0;

   $res=run_query("select sum(seeds) as seeds, sum(leechers) as leechs FROM summary");
   if ($res)
      {
      $row=mysqli_fetch_array($res);
      $seeds=0+$row["seeds"];
      $leechers=0+$row["leechs"];
      }
   else {
      $seeds=0;
      $leechers=0;
      }

      if ($leechers>0)
         $percent=number_format(($seeds/$leechers)*100,0);
      else
          $percent=number_format($seeds*100,0);

   $peers=$seeds+$leechers;

   $res=run_query("select sum(downloaded) as dled, sum(uploaded) as upld FROM users");
   $row=mysqli_fetch_array($res);
   $dled=0+$row["dled"];
   $upld=0+$row["upld"];
   $traffic=makesize($dled+$upld);

   print("<tr><td class=blocklist align=center>\n");
   print("<table width=100% cellspacing=2 cellpading=2>\n");
   print("<tr>\n<td colspan=2 align=center><u>".unesc($SITENAME)."</u></td></tr>\n");
   print("<tr><td align=left>".MEMBERS.":</td><td align=right>$users</td></tr>\n");
   print("<tr><td align=left>".TORRENTS.":</td><td align=right>$torrents</td></tr>\n");
   print("<tr><td align=left>".SEEDERS.":</td><td align=right>$seeds</td></tr>\n");
   print("<tr><td align=left>".LEECHERS.":</td><td align=right>$leechers</td></tr>\n");
   print("<tr><td align=left>".PEERS.":</td><td align=right>$peers</td></tr>\n");
   print("<tr><td align=left>".SEEDERS."/".LEECHERS.":</td><td align=right>$percent%</td></tr>\n");
   print("<tr><td align=left>".TRAFFIC.":</td><td align=right>$traffic</td></tr>\n");
   print("</table>\n</td></tr>");
   block_end();

} // end if user can view
?>