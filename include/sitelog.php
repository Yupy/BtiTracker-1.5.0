<?php
if (!$CURUSER || $CURUSER["admin_access"]!="yes")
   {
       err_msg(ERROR,NOT_ADMIN_CP_ACCESS);
       stdfoot();
       exit;
}
else
{
    $delete_timeout=time() - (60*60*24*7); // delete log older then 7 days
    run_query("DELETE FROM logs where added<$delete_timeout");
    block_begin("Site Log");
    $logres=run_query("SELECT COUNT(*) FROM logs ORDER BY added DESC");
    $lognum=mysqli_fetch_row($logres);
    $num=$lognum[0];
    $perpage=(max(0,$CURUSER["postsperpage"])>0?$CURUSER["postsperpage"]:20);
    list($pagertop, $pagerbottom, $limit) = pager($perpage, $num, "admincp.php?user=".$CURUSER["uid"]."&code=".$CURUSER["random"]."&do=logview&");
    print $pagertop;
    print("\n<table class=\"lista\" width=\"100%\" align=\"center\"><tr>");
    print("\n<td class=\"header\">".DATE."</td>");
    print("\n<td class=\"header\">".USER_NAME."</td>");
    print("\n<td class=\"header\">".ACTION."</td>\n</tr>");
    $logres=run_query("SELECT * FROM logs ORDER BY added DESC $limit");
    if ($logres)
        {
        while ($logview=mysqli_fetch_array($logres))
            {
            if ($logview["type"]=="delete")
                $bgcolor="style=\"background-color:#FF95AC; color:#000000;\"";
            elseif ($logview["type"]=="add")
                $bgcolor="style=\"background-color:#C1FF83; color:#000000;\"";
            elseif ($logview["type"]=="modify")
                $bgcolor="style=\"background-color:#DEDEDE; color:#000000;\"";
            else
                $bgcolor="";
        include("offset.php");
        print("\n<tr><td class=\"lista\" $bgcolor>".date("d/m/Y H:i:s",$logview["added"]-$offset)."</td>
            <td class=\"lista\" $bgcolor>".$logview["user"]."</td>
            <td class=\"lista\" $bgcolor>".$logview["txt"]."</td></tr>");
         }

    }
    else
        print("<tr><td colspan=\"3\" align=\"center\">no log to view...</tr>");
    print("</table>");
    print $pagerbottom;
    block_end();
    print("<br />");
}
?>