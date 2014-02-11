<?php

block_begin("Trackerload");

if (!function_exists("getmicrotime"))
{
   function getmicrotime(){
       list($usec, $sec) = explode(" ",microtime());
       return ((float)$usec + (float)$sec);
       }
}

$percent = min(100, round(@exec('ps ax | grep -c apache') / 256 * 10 ),4);

// try other method
if ($percent == 0)
    {
    $time_start = getmicrotime();
    $time = round(getmicrotime() - $time_start,4);
    $percent = $time * 60;
    }


echo "<div align=\"center\">".TRACKER_LOAD.": ($percent %)</div><table class=blocklist align=center border=0 width=400><tr><td style='padding: 0px; background-image: url(addons/serverload/loadbarbg.gif); background-repeat: repeat-x'>";

//TRACKER LOAD
if ($percent <= 70) $pic = "addons/serverload/loadbargreen.gif";
elseif ($percent <= 90) $pic = "addons/serverload/loadbaryellow.gif";
else $pic = "addons/serverload/loadbarred.gif";
$width = $percent*4;
echo "<img height=15 width=$width src=\"$pic\" alt='$percent%'></td></tr></table>";
echo "<center>" . trim(@exec('uptime')) . "</center><br>";

if (isset($load))
print("<tr><td class=blocklist>10min load average (%)</td><td align=right>$load</td></tr>\n");
print("<br>");
$percent = min(100, round(@exec('ps ax | grep -c apache') / 256 * 50),4);
// try other method
if ($percent == 0)
    {
    $time = round(getmicrotime() - $time_start,4);
    $percent = $time * 60;
    }

echo "<div align=\"center\">".GLOBAL_SERVER_LOAD.": ($percent %)</div><table class=main align=center border=0 width=400><tr><td style='padding: 0px; background-image: url(addons/serverload/loadbarbg.gif); background-repeat: repeat-x'>";

 if ($percent <= 70) $pic = "addons/serverload/loadbargreen.gif";
  elseif ($percent <= 90) $pic = "addons/serverload/loadbaryellow.gif";
   else $pic = "addons/serverload/loadbarred.gif";
        $width = $percent * 4;
echo "<img height=15 width=$width src=\"$pic\" alt='$percent%'></td></tr></table><br /><br />";
block_end();
print("<br />");
?>