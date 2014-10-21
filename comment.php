<?php
/*
* BtiTracker v1.5.0 is a php tracker system for BitTorrent, easy to setup and configure.
* This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
* Updated and Maintained by Yupy.
* Copyright (C) 2004-2014 Btiteam.org
*/
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'include'.DIRECTORY_SEPARATOR.'functions.php');

dbconn();
standardheader('Comments');

if (!user::$current || user::$current["uid"] == 1)
{
    err_msg(ERROR, ONLY_REG_COMMENT);
    stdfoot();
    exit();
}

$comment = $db->real_escape_string($_POST["comment"]);

$id = $db->real_escape_string($_GET["id"]);

if (isset($_GET["cid"]))
    $cid = intval($_GET["cid"]);
else
    $cid = 0;

function comment_form()
{
    global $comment, $id, $cid;

    block_begin(NEW_COMMENT);
    
    $comment = str_replace('\r\n', "\n", $comment);
    
    ?>
    <center>
    <form enctype='multipart/form-data' name='comment' method='post'>
    <input type='hidden' name='info_hash' value='<?php echo $id; ?>' />
    <table class='lista' border='0' cellpadding='10'>
    <tr>
    <tr><td align='left' class='header'><?php echo USER_NAME;?>:</td><td class='lista' align='left'><input name='user' type='text'  value='<?php echo security::html_safe($_GET["usern"]) ?>' size='20' maxlength='100' disabled; readonly></td></tr>
    <tr><td align='left' class='header'><?php echo COMMENT_1;?>:</td><td class='lista' align='left'><?php textbbcode("comment","comment", security::html_safe(unesc($comment))); ?></td></tr>
    <tr><td class='header' colspan='2' align='center'><input type='submit' name='confirm' value='<?php echo FRM_CONFIRM;?>' />&nbsp;&nbsp;&nbsp;<input type='submit' name='confirm' value='<?php echo FRM_PREVIEW;?>' /></td></tr>
    </table>
    </form>
    </center>
    
    <?php
    block_end();
}

if (isset($_GET["action"]))
{
    if (user::$current["admin_access"] == "yes" && $_GET["action"] == "delete")
    {
        @$db->query("DELETE FROM comments WHERE id = " . $cid);
        redirect("details.php?id=" . $id . "#comments");
        exit;
    }
}

if (isset($_POST["info_hash"]))
{
    if ($_POST["confirm"]==FRM_CONFIRM) {
        $comment = $db->real_escape_string(addslashes($_POST["comment"]));
        $user = AddSlashes(user::$current["username"]);

        if ($user == '')
		    $user = "Anonymous";

        @$db->query("INSERT INTO comments (added, text, ori_text, user, info_hash) VALUES (NOW(), '" . $comment . "', '" . $comment . "', '" . $user . "', '" . $db->real_escape_string(StripSlashes($_POST["info_hash"])) . "')");
        redirect("details.php?id=" . StripSlashes($_POST["info_hash"]) . "#comments");
    }
	
    # Comment preview by miskotes
    #############################
    if ($_POST["confirm"] == FRM_PREVIEW) {
        block_begin(COMMENT_PREVIEW);
        
	$comment = str_replace('\r\n', "\n", $comment);
	
        print("<table width='100%' align='center' class='lista'><tr><td class='lista' align='center'>" . text::full_format($comment) . "</td></tr>\n");
        print("</table>");
		
        block_end();
        comment_form();
        stdfoot();
    #####################
    # Comment preview end
    }
    else
        redirect("details.php?id=" . StripSlashes($_POST["info_hash"])."#comments");
} else {
    comment_form();
   stdfoot();

}

?>
