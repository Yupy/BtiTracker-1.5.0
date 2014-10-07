<?php
/*
 * BtiTracker v1.5.0 is a php tracker system for BitTorrent, easy to setup and configure.
 * This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
 * Updated and Maintained by Yupy.
 * Copyright (C) 2004-2014 Btiteam.org
 */

if (!defined("IN_ACP"))
    die("No direct access!");

$action = (isset($_GET["action"]) ? security::html_safe($_GET["action"]) : "");
$days   = (isset($_POST["days"]) ? max(0, (int)$_POST["days"]) : "");

if ($action == "prune") {
    if (!isset($_POST["id"]))
        redirect("admincp.php?user=" . user::$current["uid"] . "&code=" . user::$current["random"] . "&do=pruneu");
    $count = 0;
    foreach ($_POST["id"] as $id => $uid) {
        if ($uid == 1)
            continue;
        @$db->query("DELETE FROM users WHERE id = '" . $uid . "'");
        $count++;
    }
    block_begin("Pruned users");
    echo "<p align='center'>#" . $count . " users pruned!</p>";
    block_end();
    echo "<br />\n";
    exit;
} elseif ($action == "view") {
    // 30 DAYS
    if ($days == 0) {
        // days not set!!
        redirect("admincp.php?user=" . user::$current["uid"] . "&code=" . user::$current["random"] . "&do=pruneu");
        exit;
    }
    $timeout = (60 * 60 * 24) * $days;
    
    $res = $db->query("SELECT users.id, users.username, UNIX_TIMESTAMP(users.joined) AS joined, UNIX_TIMESTAMP(users.lastconnect) AS lastconnect, users_level.level FROM users INNER JOIN users_level ON users_level.id = users.id_level WHERE (users.id > 1 AND users_level.id_level < 3 AND UNIX_TIMESTAMP(joined) < (UNIX_TIMESTAMP() - " . $timeout . ")) OR (users.id > 1 AND users_level.id_level < 7 AND UNIX_TIMESTAMP(lastconnect) < (UNIX_TIMESTAMP() - " . $timeout . ")) ORDER BY users_level.id_level DESC, lastconnect");
    
    block_begin("Prune users");
    if (!$res) {
        print("<p align='center'>No users to prune...<p>");
    } elseif ($res->num_rows > 0) {
        print("<script type='text/javascript'>
       <!--
       function SetAllCheckBoxes(FormName, FieldName, CheckValue)
       {
         if(!document.forms[FormName])
           return;
         var objCheckBoxes = document.forms[FormName].elements[FieldName];
         if(!objCheckBoxes)
           return;
         var countCheckBoxes = objCheckBoxes.length;
         if(!countCheckBoxes)
           objCheckBoxes.checked = CheckValue;
         else
           // set the check value for all check boxes
           for(var i = 0; i < countCheckBoxes; i++)
             objCheckBoxes[i].checked = CheckValue;
       }
       // -->
       </script>
       ");
        print("\n<form action='admincp.php?user=" . user::$current["uid"] . "&code=" . user::$current["random"] . "&do=pruneu&action=prune' name='prune' method='post'>");
        print("\n<table class='lista' width='100%'>");
        print("\n<tr><td class='header' align='center'>" . NAME . "</td>");
        print("\n<td class='header' align='center'>" . USER_JOINED . "</td>");
        print("\n<td class='header' align='center'>" . USER_LASTACCESS . "</td>");
        print("\n<td class='header' align='center'>" . USER_LEVEL . "</td>");
        print("\n<td class='header' align='center'><input type='checkbox' name='all' onclick=\"SetAllCheckBoxes('prune','id[]',this.checked)\" /></td></tr>");
        $count = 0;
        while ($rusers = $res->fetch_array(MYSQLI_BOTH)) {
            include(INCL_PATH . 'offset.php');
            print("\n<tr>\n<td class='lista' align='left'>" . security::html_safe($rusers["username"]) . "</td>");
            print("\n<td class='lista' align='center'>" . date("d/m/Y H:i", $rusers["joined"] - $offset) . "</td>");
            print("\n<td class='lista' align='center'>" . date("d/m/Y H:i", $rusers["lastconnect"] - $offset) . "</td>");
            print("\n<td class='lista' align='center'>" . security::html_safe($rusers["level"]) . "</td>");
            print("\n<td class='lista' align='center'><input type='checkbox' name='id[]' value='" . (int)$rusers["id"] . "' /></td></tr>");
            $count++;
        }
        print("\n<tr>\n<td class='lista' align='right' colspan='5'><input type='submit' name='action' value='GO' /></td></tr>");
        print("\n</table>\n</form>");
    } else {
        print("<p align='center'>No users to prune...<p>");
    }
	
    block_end();
    print("<br />\n");
} else {
    block_begin("Prune users");
    print("\n<form action='admincp.php?user=" . user::$current["uid"] . "&code=" . user::$current["random"] . "&do=pruneu&action=view' name='prune' method='post'>");
    print("<p align='center'>Imput the number of days which the users are to be considered as 'dead' (not connected from x days OR has signed from x days and still validating)&nbsp;<input type='text' name='days' value='" . $days . "' size='10' maxlength='3' />");
    print("\n<input type='submit' name='action' value='View' /></td></tr>");
    print("\n</p></form>");
    block_end();
    print("<br />\n");
}

?>