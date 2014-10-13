<?php
/*
* BtiTracker v1.5.0 is a php tracker system for BitTorrent, easy to setup and configure.
* This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
* Updated and Maintained by Yupy.
* Copyright (C) 2004-2014 Btiteam.org
*/

if (!user::$current || user::$current["admin_access"] != "yes") {
    err_msg(ERROR, NOT_ADMIN_CP_ACCESS);
    stdfoot();
    exit;
} else {
    $delete_timeout = vars::$timestamp - (60 * 60 * 24 * 7); // delete log older then 7 days
    $db->query("DELETE FROM logs WHERE added < " . $delete_timeout);
	
    block_begin("Site Log");
	
    $logres  = $db->query("SELECT COUNT(*) FROM logs ORDER BY added DESC");
    $lognum  = $logres->fetch_row();
    $num     = (int)$lognum[0];
    $perpage = (max(0, user::$current["postsperpage"]) > 0 ? user::$current["postsperpage"] : 20);
    
	list($pagertop, $limit) = misc::pager($perpage, $num, "admincp.php?user=" . user::$current["uid"] . "&code=" . user::$current["random"] . "&do=logview&");
    print $pagertop;
    print("\n<table class='lista' width='100%' align='center'><tr>");
    print("\n<td class='header'>" . DATE . "</td>");
    print("\n<td class='header'>" . USER_NAME . "</td>");
    print("\n<td class='header'>" . ACTION . "</td>\n</tr>");
	
    $logres = $db->query("SELECT * FROM logs ORDER BY added DESC " . $limit);
    if ($logres) {
        while ($logview = $logres->fetch_array(MYSQLI_BOTH)) {
            if ($logview["type"] == "delete")
                $bgcolor = "style='background-color:#FF95AC; color:#000000;'";
            elseif ($logview["type"] == "add")
                $bgcolor = "style='background-color:#C1FF83; color:#000000;'";
            elseif ($logview["type"] == "modify")
                $bgcolor = "style='background-color:#DEDEDE; color:#000000;'";
            else
                $bgcolor = "";
			
            include(INCL_PATH . 'offset.php');
            print("\n<tr><td class='lista' " . $bgcolor . ">" . date("d/m/Y H:i:s", $logview["added"] - $offset) . "</td>
            <td class='lista' " . $bgcolor . ">" . security::html_safe($logview["user"]) . "</td>
            <td class='lista' " . $bgcolor . ">" . unesc($logview["txt"]) . "</td></tr>");
        }
    } else
        print("<tr><td colspan='3' align='center'>No log to view...</tr>");
	
    print("</table>");
    print $pagertop;
    block_end();
    print("<br />");

}

?>
