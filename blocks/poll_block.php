<?php
/*
* BtiTracker v1.5.0 is a php tracker system for BitTorrent, easy to setup and configure.
* This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
* Updated and Maintained by Yupy.
* Copyright (C) 2004-2014 Btiteam.org
*/
require_once(INCL_PATH . 'functions.php');

dbconn();

$res = $db->query("SELECT * FROM polls WHERE status = 'true'");
$result = $res->fetch_array(MYSQLI_BOTH);
$pid    = $db->real_escape_string($result["pid"]);

if ($result) {
    $res2 = $db->query("SELECT * FROM poll_voters WHERE pid = '" . $pid . "'");
    $question = security::html_safe($result["poll_question"]);

    block_begin("Poll: " . $question);

    print("<tr><td class='blocklist' align='center'>\n");
    print("<table cellspacing='2' cellpading='2'>\n");

    $total_votes = 0;
    $check       = 0;

    if (user::$current["id_level"] < 3 || (isset($_POST['showres']) && $_POST['showres'] == 'Show Results'))
        $check = 1;
    else
        $check = 0;

    while ($voters = $res2->fetch_array(MYSQLI_BOTH)) {
        if (user::$current["uid"] == $voters["memberid"])
            $check = 1;
    }
    
    if ($check == 1) {
        $poll_answers = unserialize(stripslashes($result["choices"]));
        
        reset($poll_answers);
        foreach ($poll_answers as $entry) {
            $id     = $entry[0];
            $choice = $entry[1];
            $votes  = $entry[2];
            
            $total_votes += $votes;
            
            if (strlen($choice) < 1) {
                continue;
            }
            
            $percent = $votes == 0 ? 0 : $votes / (int)$result["votes"] * 100;
            $percent = sprintf('%.2f', $percent);
            $width   = $percent > 0 ? floor(round($percent) * 0.7) : 0;
            $percent = floor($percent);
            
            print("<tr><td width='50%' class='lista'>" . $choice . "</td><td class='lista'> (<b>" . $votes . "</b>) </td><td class='lista'><img src='images/bar.gif' width='" . $width . "' height='11' align='left' /></td><td align='left' class='lista'>&nbsp;(" . $percent . "%)</td></tr>");
        }
    }
    elseif ($check == 0) {
        // Show poll form
        $poll_answers = unserialize(stripslashes($result["choices"]));
        reset($poll_answers);
        
    ?>     
    <form action='<?php echo $_SERVER['REQUEST_URI']; ?>' method='post'>
    <?php
        foreach ($poll_answers as $entry) {
            $id     = $entry[0];
            $choice = $entry[1];
            $votes  = $entry[2];
            
            $total_votes += $votes;
            
            if (strlen($choice) < 1) {
                continue;
            }
            
            ?>
            <tr><td colspan='3' align='left'><input type='radio' name='poll_vote' value='<?php echo $id; ?>' /><b>&nbsp;<?php echo $choice; ?></b></td></tr>
            <?php
        }
        
        print("\n<td align='left' class='lista'><input type='submit' name='submit' value='Submit' />&nbsp;&nbsp;&nbsp;&nbsp;<input type='submit' name='showres' value='Show Results' /></td>");
    ?>
    </form>
    <?php
    }

    if (isset($_POST['submit']) && $_POST['submit'] == 'Submit' && isset($_POST['poll_vote']) && $check != 1) {
        $voteid         = $db->real_escape_string($_POST['poll_vote']);
        $memberid       = user::$current["uid"];
        $ip             = vars::$realip;
        $new_poll_array = array();

        $db->query("INSERT INTO poll_voters SET ip = '$ip', votedate = '" . vars::$timestamp . "', pid = '" . $pid . "', memberid = '" . $memberid . "'");
        $poll_answers = unserialize(stripslashes($result["choices"]));
        reset($poll_answers);
        
        foreach ($poll_answers as $var) {
            $id     = $var[0];
            $choice = $var[1];
            $votes  = $var[2];
            if ($id == $voteid)
                $votes++;
            $new_poll_array[] = array(
                $id,
                $choice,
                $votes
            );
        }

        $votings = addslashes(serialize($new_poll_array));
        $uvotes  = (int)$result["votes"] + 1;

        $db->query("UPDATE polls SET choices = '" . $votings . "' WHERE pid = '" . $pid . "'");
        $db->query("UPDATE polls SET votes = '" . $uvotes . "' WHERE pid = '" . $pid . "'");

        redirect($_SERVER['REQUEST_URI']);
    }

    print("</table>\n</td></tr>");
    block_end();

}

?>