<?php
global $CURUSER;
if (!$CURUSER || $CURUSER["view_torrents"]=="no")
   {
    // do nothing
   }
else
    {
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
?>
<table class="lista" cellpadding="2" cellspacing="0" width="100%">
<tr>
<td class=lista align=center><?php echo BLOCK_INFO; ?>:</td>
<td class=lista align=center><?php echo MEMBERS; ?>:</td><td align=right><?php echo $users; ?></td>
<td class=lista align=center><?php echo TORRENTS; ?>:</td><td align=right><?php echo $torrents; ?></td>
<td class=lista align=center><?php echo SEEDERS; ?>:</td><td align=right><?php echo $seeds; ?></td>
<td class=lista align=center><?php echo LEECHERS; ?>:</td><td align=right><?php echo $leechers; ?></td>
<td class=lista align=center><?php echo PEERS; ?>:</td><td align=right><?php echo $peers; ?></td>
<td class=lista align=center><?php echo SEEDERS."/".LEECHERS; ?>:</td><td align=right><?php echo $percent."%"; ?></td>
<td class=lista align=center><?php echo TRAFFIC; ?>:</td><td align=right><?php echo $traffic; ?></td>
</tr></table>
<?php
} // end if user can view
?> 