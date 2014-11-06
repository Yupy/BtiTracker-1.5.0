<?php
/*
* BtiTracker v1.5.0 is a php tracker system for BitTorrent, easy to setup and configure.
* This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
* Updated and Maintained by Yupy.
* Copyright (C) 2004-2014 Btiteam.org
*/
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'include'.DIRECTORY_SEPARATOR.'functions.php');

dbconn(true);

standardheader('User Control Panel');

$uid = (isset($_GET["uid"]) ? intval($_GET["uid"]) : 1);

?>
<script type="text/javascript">
<!--
var newwindow;
function popusers(url)
{
newwindow=window.open(url,'popusers','height=100,width=450');
if (window.focus) {newwindow.focus()}
}
// -->
</script>
<?php

if (user::$current["uid"] != $uid || user::$current["uid"] == 1) {
    err_msg(ERROR, ERR_USER_NOT_USER);
    stdfoot();
    exit;
} else {
    $utorrents = user::$current["torrentsperpage"];
    if (isset($_GET["do"]))
        $do = security::html_safe($_GET["do"]);
    else
        $do = '';
    if (isset($_GET["action"]))
        $action = security::html_safe($_GET["action"]);
    
    // begin the real admin page
    block_begin(user::$current["username"] . "'s Control Panel");
    print("\n<table class='lista' width='100%' align='center'><tr>");
    print("\n<td class='header' align='center'><a href='usercp.php?uid=".$uid."'>" . MNU_UCP_HOME . "</a></td>");
    print("\n<td class='header' align='center'><a href='usercp.php?uid=".$uid."&do=pm&action=list&what=inbox'>" . MNU_UCP_PM . "</a></td>");
    print("\n<td class='header' align='center'><a href='usercp.php?uid=".$uid."&do=pm&action=list&what=outbox'>" . MNU_UCP_OUT . "</a></td>");
    print("\n<td class='header' align='center'><a href='usercp.php?do=pm&action=edit&uid=".$uid."&what=new'>" . MNU_UCP_NEWPM . "</a></td>");
    print("\n<td class='header' align='center'><a href='usercp.php?do=user&action=change&uid=".$uid."'>" . MNU_UCP_INFO . "</a></td>");
    print("\n<td class='header' align='center'><a href='usercp.php?do=pwd&action=change&uid=".$uid."'>" . MNU_UCP_CHANGEPWD . "</a></td>");
    print("\n<td class='header' align='center'><a href='usercp.php?do=pid_c&action=change&uid=".$uid."'>" . CHANGE_PID . "</a></td>");
    print("\n</tr></table>\n");
    
    if ($do == "pm" && $action == "list") {
        // MODIFIED select for deletion by gAnDo
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
            -->
            </script>
            ");
        
        if (isset($_GET["what"]) && $_GET["what"])
            $what = security::html_safe($_GET["what"]);
        else
            $what = '';

        if ($what == "outbox") {
            block_begin(MNU_UCP_OUT);
            print("\n<form action='usercp.php?do=pm&action=deleteall&uid=".$uid."&type=out' name='deleteall' method='post'>");
            print("\n<table class='lista' width='100%' align='center'>");
            print("\n<tr><td class='header' align='center'>" . READED . "</td><td class='header' align='center'>" . RECEIVER . "</td><td class='header' align='center'>Date</td><td class='header' align='center'>" . SUBJECT . "</td>");

            $res = $db->query("SELECT messages.*, users.username AS receivername FROM messages LEFT JOIN users ON users.id = messages.receiver WHERE sender = " . $uid . " ORDER BY added DESC");

			if (!$res || $res->num_rows == 0) {
                print("\n</tr><tr><td class='lista' colspan='5' align='center'>" . NO_MESSAGES . "</td></tr>");
            } else {
                print("\n<td class=\"header\" align=\"center\"><input type=\"checkbox\" name=\"all\" onclick=\"SetAllCheckBoxes('deleteall','msg[]',this.checked)\" /></td></tr>");

				while ($result = $res->fetch_array(MYSQLI_BOTH))
                    print("\n<tr><td class='lista' align='center'>" . unesc($result["readed"]) . "</td>
                              <td class='lista' align='center'><a href='userdetails.php?id=" . (int)$result["receiver"] . "'>" . security::html_safe(unesc($result["receivername"])) . "</a></td>
                              <td class='lista' align='center'>" . get_date_time($result["added"]) . "</td>
                              <td class='lista' align='center'><a href='usercp.php?do=pm&action=read&uid=" . $uid . "&id=" . (int)$result['id'] . "&what=outbox'>" . format_comment(unesc($result["subject"])) . "</a></td>
                              <td class='lista' align='center'><input type='checkbox' name='msg[]' value='" . (int)$result["id"] . "' /></td>
                              </tr>");
                print("\n<tr>\n<td class='lista' align='right' colspan='5'><input type='submit' name='action' value='Delete' /></td></tr>");
            }
            print("\n</table></form>");
            block_end();
            print("<br />");
        } else {
            block_begin(MNU_UCP_IN);
            print("\n<form action='usercp.php?do=pm&action=deleteall&uid=$uid&type=in' name='deleteall' method='post'>");
            print("\n<table class='lista' width='100%' align='center'>");
            print("\n<tr><td class='header' align='center'>" . READED . "</td><td class='header' align='center'>" . SENDER . "</td><td class='header' align='center'>Date</td><td class='header' align='center'>" . SUBJECT . "</td>");

            $res = $db->query("SELECT messages.*, users.username AS sendername FROM messages LEFT JOIN users ON users.id = messages.sender WHERE receiver = " . $uid . " ORDER BY added DESC");

			if (!$res || $res->num_rows == 0)
                print("\n</tr><tr><td class='lista' colspan='5' align='center'>" . NO_MESSAGES . "</td></tr>");
            else {
                print("\n<td class=\"header\" align=\"center\"><input type=\"checkbox\" name=\"all\" onclick=\"SetAllCheckBoxes('deleteall','msg[]',this.checked)\" /></td></tr>");

				while ($result = $res->fetch_array(MYSQLI_BOTH))
                    print("\n<tr>
                              <td class='lista' align='center'>" . unesc($result["readed"]) . "</td>
                              <td class='lista' align='center'><a href='userdetails.php?id=" . (int)$result["sender"] . "'>" . security::html_safe(unesc($result["sendername"])) . "</a></td>
                              <td class='lista' align='center'>" . get_date_time($result["added"]) . "</a></td>
                              <td class='lista' align='center'><a href='usercp.php?do=pm&action=read&uid=" . $uid . "&id=" . (int)$result['id'] . "&what=inbox'>" . format_comment(unesc($result["subject"])) . "</a></td>
                              <td class='lista' align='center'><input type='checkbox' name='msg[]' value='" . (int)$result["id"] . "' /></td>
                              </tr>");
                print("\n<tr>\n<td class='lista' align='right' colspan='5'><input type='submit' name='action' value='Delete' /></td></tr>");
            }

            print("\n</table></form>");
            block_end();
            print("<br />");
        }
    }
	elseif ($do == "pm" && $action == "read") {
        $id = intval($_GET["id"]);
        $what = security::html_safe($_GET["what"]);

        if ($what == "inbox")
            $res = $db->query("SELECT messages.*, messages.sender AS userid, users.username AS sendername FROM messages INNER JOIN users ON users.id = messages.sender WHERE receiver = " . $uid . " AND messages.id = " . $id);
        elseif ($what == "outbox")
            $res = $db->query("SELECT messages.*, messages.receiver AS userid, users.username AS sendername FROM messages INNER JOIN users ON users.id = messages.receiver WHERE sender = " . $uid . " AND messages.id = " . $id);

		block_begin(PRIVATE_MSG);
        if (!$res)
            err_msg(ERROR, BAD_ID);
        else {
            print("\n<table class='lista' width='100%' align='center' cellpadding='2'>");

            $result = $res->fetch_array(MYSQLI_BOTH);

            print("\n<tr><td width='30%' rowspan='2' class='lista'><a href='userdetails.php?id=" . (int)$result["userid"] . "'>" . security::html_safe(unesc($result["sendername"])) . "</a><br />" . get_date_time($result["added"]) . "<br />(" . get_elapsed_time($result["added"]) . " ago)</td>");
            print("\n<td class='header'>" . SUBJECT . ": " . format_comment(unesc($result["subject"])) . "</td></tr>");
            print("\n<tr><td>" . format_comment(unesc($result["msg"])) . "</td></tr>");
            print("\n</table>");
            print("<br />");

            if ($what == "inbox") {
                print("\n<table class='lista' width='100%' align='center'>");
                print("\n<tr><td class='lista' align='center'><input onclick=\"location.href='usercp.php?do=pm&action=edit&what=quote&uid=" . $uid . "&id=" . $id . "'\" type='button' value='" . QUOTE . "' /></td><td class='lista' align='center'><input onclick=\"location.href='usercp.php?do=pm&action=edit&uid=" . $uid . "&id=" . $id . "'\" type='button' value='" . ANSWER . "' /></td><td class='lista' align='center'><input type='button' onclick=\"location.href='usercp.php?do=pm&action=delete&uid=" . $uid . "&id=" . $id . "'\" value='".DELETE."' /></td></tr>");
                print("\n</table>");

                $db->query("UPDATE messages SET readed = 'yes' WHERE id = ". $id);
            }
        }

        print("<br />");
        block_end();
        print("<br />");
    }
	elseif ($do == "pm" && $action == "edit") {
        // if new pm will give id=0 and empty array
        if (isset($_GET['id']) && $_GET['id'])
            $id = intval(0 + $_GET['id']);
        else
            $id = 0;

        if (!isset($_GET['what']))
            $_GET['what'] = '';
        if (!isset($_GET['to']))
            $_GET['to'] = '';
        
        $res = $db->query("SELECT messages.*, users.username AS sendername FROM messages INNER JOIN users ON users.id = messages.sender WHERE receiver = " . $uid . " AND messages.id = " . $id);

		block_begin(PRIVATE_MSG);

        if (!$res)
            err_msg(ERROR, BAD_ID);
        else {
            print("\n<form method='post' name='edit' action='usercp.php?do=" . $do . "&action=post&uid=" . $uid . "&what=" . security::html_safe($_GET["what"]) . "'><table class='lista' align='center' cellpadding='2'>");

			$result = $res->fetch_array(MYSQLI_BOTH);

			print("\n<tr><td class='header'>" . RECEIVER . ":</td><td class='header'><input type='text' name='receiver' value='" . ($_GET["what"] != "new" ? unesc($result["sendername"]) : security::html_safe(urldecode($_GET["to"]))) . "' size='40' maxlength='40' " . ($_GET["what"] != "new" ? " readonly" : "") . " />&nbsp;&nbsp;" . ($_GET["what"] == "new" ? "<a href=\"javascript:popusers('searchusers.php');\">" . FIND_USER . "</a>" : "") . "</td></tr>");
            print("\n<tr><td class='header'>" . SUBJECT . ":</td><td class='header'><input type='text' name='subject' value='" . ($_GET["what"] != "new" ? (strpos(unesc($result["subject"]), "Re:") === false ? "Re:" : "") . unesc($result["subject"]) : "") . "' size='40' maxlength='40' /></td></tr>");
            print("\n<tr><td colspan='2'>");
            print(textbbcode("edit", "msg", ($_GET["what"] == "quote" ? "[quote=" . security::html_safe($result["sendername"]) . "]" . security::html_safe(unesc($result["msg"])) . "[/quote]" : "")));
            print("\n</td></tr>");
            print("\n</table>");
            print("<br />");
            print("\n<table class='lista' width='100%' align='center'>");
            print("\n<tr><td class='lista' align='center'><input type='submit' name='confirm' value='" . FRM_CONFIRM . "' /></td>");
            print("<td class='lista' align='center'><input type='submit' name='confirm' value='" . FRM_PREVIEW . "' /></td>");
            print("<td class='lista' align='center'><input type='submit' name='confirm' value='" . FRM_CANCEL . "' /></td></tr>");
            print("\n</table></form>");
        }

        print("<br />");
        block_end();
        print("<br />");
    }
	elseif ($do == "pm" && $action == "delete") {
        $id = intval($_GET["id"]);
        $db->query("DELETE FROM messages WHERE receiver = " . $uid . " AND id = " . $id);

        redirect("usercp.php?uid=" . $uid . "&do=pm&action=list&what=inbox");
    }
	elseif ($do == "pm" && $action == "deleteall") {
        // MODIFIED DELETE ALL VERSION BY gAnDo
        if (isset($_GET["type"]))
            $what = security::html_safe($_GET["type"]);
        else {
            redirect("usercp.php?uid=" . $uid . "&do=pm&action=list&what=" . ($what == "in" ? "inbox" : "outbox"));
            exit;
        }

        foreach ($_POST["msg"] as $selected => $msg)
            @$db->query("DELETE FROM messages WHERE id = '" . $msg . "'");

        redirect("usercp.php?uid=" . $uid . "&do=pm&action=list&what=" . ($what == "in" ? "inbox" : "outbox"));
    }
	elseif ($do == "pm" && $action == "post") {
        if ($_POST["confirm"] == FRM_CONFIRM) {
            $res = $db->query("SELECT id FROM users WHERE username = " . sqlesc($_POST["receiver"]));

            if (!$res || $res->num_rows == 0)
                err_msg(ERROR, ERR_USER_NOT_FOUND);
            else {
                $result = $res->fetch_array(MYSQLI_BOTH);
                $subject = sqlesc($_POST["subject"]);
                $msg = sqlesc($_POST["msg"]);
                $rec = (int)$result["id"];
                $send = user::$current["uid"];
                
                if ($rec == 1 || $rec == $send)
                    err_msg(ERROR, ERR_PM_GUEST);
                else {
                    if ($subject == "''")
                        $subject = "'No Subject'";

                    $db->query("INSERT INTO messages (sender, receiver, added, subject, msg) VALUES (" . $send . ", " . $rec . ", UNIX_TIMESTAMP(), " . $subject . ", " . $msg . ")");

					redirect("usercp.php?uid=" . $uid . "&do=pm&action=list");
                }
            }
        }
		elseif ($_POST["confirm"] == FRM_PREVIEW) {
            block_begin(PRIVATE_MSG);

            block_begin(FRM_PREVIEW);

            print("<table width='100%' align='center' class='lista'><tr><td class='lista' align='center'>" . format_comment(unesc($_POST["msg"])) . "</td></tr>\n");
            print("</table>");
            block_end();
            print("<br />");

            print("\n<form method='post' name='edit' action='usercp.php?do=" . $do . "&action=post&uid=" . $uid . "&what=" . security::html_safe($_GET["what"]) . "'><table class='lista' align='center' cellpadding='2'>");
            print("\n<tr><td class='header'>" . RECEIVER . ":</td><td class='header'><input type='text' name='receiver' value='" . security::html_safe(unesc($_POST["receiver"])) . "' size='40' maxlength='40' />&nbsp;&nbsp;" . ($_GET["what"] == "new" ? "<a href='javascript:popusers('searchusers.php');'>" . FIND_USER . "</a>" : "") . "</td></tr>");
            print("\n<tr><td class='header'>" . SUBJECT . ":</td><td class='header'><input type='text' name='subject' value='" . security::html_safe(unesc($_POST["subject"])) . "' size='40' maxlength='40' /></td></tr>");
            print("\n<tr><td colspan='2'>");
            print(textbbcode("edit", "msg", security::html_safe(unesc($_POST["msg"]))));
            print("\n</td></tr>");
            print("\n</table>");
            print("<br />");
            print("\n<table class='lista' width='100%' align='center'>");
            print("\n<tr><td class='lista' align='center'><input type='submit' name='confirm' value='" . FRM_CONFIRM . "' /></td>");
            print("<td class='lista' align='center'><input type='submit' name='confirm' value='" . FRM_PREVIEW . "' /></td>");
            print("<td class='lista' align='center'><input type='submit' name='confirm' value='" . FRM_CANCEL . "' /></td></tr>");
            print("\n</table></form>");
            block_end();
        }
		else
            redirect("usercp.php?uid=" . $uid . "&do=pm&action=list");
    }
	elseif ($do == "pwd" && $action == "change") {
        block_begin(MNU_UCP_CHANGEPWD);

        print("\n<form method='post' name='password' action='usercp.php?do=pwd&action=post&uid=" . $uid . "'><table class='lista' width='100%' align='center'>");
        print("\n<tr><td class='header'>" . OLD_PWD . "</td><td class='lista'><input type='password' name='old_pwd' size='40' maxlength='40' /></td></tr>");
        print("\n<tr><td class='header'>" . USER_PWD . "</td><td class='lista'><input type='password' name='new_pwd' size='40' maxlength='40' /></td></tr>");
        print("\n<tr><td class='header'>" . USER_PWD_AGAIN . "</td><td class='lista'><input type='password' name='new_pwd1' size='40' maxlength='40' /></td></tr>");
        print("\n</table>");
        print("<br />");
        print("\n<table class='lista' width='100%' align='center'>");
        print("\n<tr><td class='lista' align='center'><input type='submit' name='confirm' value='" . FRM_CONFIRM . "'/></td><td class='lista' align='center'><input type='submit' name='confirm' value='" . FRM_CANCEL . "'/></td></tr>");
        print("\n</table></form>");
        print("<br />");
        block_end();
        print("<br />");
    }
	elseif ($do == "pwd" && $action == "post") {
        if ($_POST["confirm"] == FRM_CONFIRM) {
            if ($_POST["old_pwd"] == "")
                err_msg(ERROR, INS_OLD_PWD);
            elseif ($_POST["new_pwd"] == "")
                err_msg(ERROR, INS_NEW_PWD);
            elseif ($_POST["new_pwd"] != $_POST["new_pwd1"])
                err_msg(ERROR, DIF_PASSWORDS);
            else {
                $respwd = $db->query("SELECT * FROM users WHERE id = " . $uid . " AND password = '" . md5($_POST["old_pwd"]) . "' AND username = " . sqlesc(user::$current["username"]));

				if (!$respwd || $respwd->num_rows == 0)
                    err_msg(ERROR, ERR_RETR_DATA);
                else {
                    $db->query("UPDATE users SET password = '" . md5($_POST["new_pwd"]) . "' WHERE id = " . $uid . " AND password = '" . md5($_POST["old_pwd"]) . "' AND username = " . sqlesc(user::$current["username"]));

					print("<p align='center'><b>" . PWD_CHANGED . "</b><br /><br />");
                    print(NOW_LOGIN . "<br /><br />");
                    print("<a href='login.php'>Go</a><br /></p>");
                }
            }
        } else
            redirect("usercp.php?uid=" . $uid);
    }
	elseif ($do == "user" && $action == "change") {
        block_begin(ACCOUNT_EDIT);
?>
        <center>
        <p>
        <form name='utente' method='post' action='usercp.php?do=user&action=post&uid=<?php
        echo $uid;
?>'>
        <table width='60%' border='0' class='lista'>
        <tr>
           <td align='left' class='header'><?php
        echo USER_NAME;
?>: </td>
           <td align='left' class='lista'><?php
        echo user::$current["username"];
?></td>
           <!--avatar-->
           <?php
        if (user::$current["avatar"] && user::$current["avatar"] != "")
            print("<td class='lista' align='center' valign='top' rowspan='3'><img border='0' src='" . unesc(user::$current["avatar"]) . "' /></td>");
?>
        </tr>
        <tr>
           <td align='left' class='header'><?php
        echo AVATAR_URL;
?> </td>
           <td align='left' class='lista'><input type='text' size='40' name='avatar' maxlength='100' value='<?php
        echo unesc(user::$current["avatar"]);
?>' /></td>
        </tr>
        <tr>
           <td align='left' class='header'><?php
        echo USER_EMAIL;
?>:</td>
           <td align='left' class='lista'><input type='text' size='30' name='email' maxlength='50' value='<?php
        echo unesc(user::$current["email"]);
?>' /></td>
        </tr>
    <?php
        // Reverify Mail Hack by Petr1fied - Start --->
        if ($VALIDATION == "user") {
            // Display a message informing users that they will have
            // to verify their e-mail address if they attempt to change it 
?>
        <tr>
           <td align='left' class='header'></td>
           <td align='left' class='lista' colspan='2'><?php
            echo REVERIFY_MSG;
?></td>
        </tr>
    <?php
        } // <--- Reverify Mail Hack by Petr1fied - End 
?>
    <?php
        $lres = language_list();
        print("<tr>\n\t<td align='left' class='header'>" . USER_LANGUE . ":</td>");
        print("\n\t<td align='left' class='lista' colspan='2'><select name='language'>");

        foreach ($lres as $langue) {
            $option = "\n<option ";
            if ($langue["id"] == user::$current["language"])
                $option .= "selected='selected' ";

            $option .= "value='" . $langue["id"] . "'>" . security::html_safe(unesc($langue["language"])) . "</option>";
            print($option);
        }

        print("</select></td>\n</tr>");
        
        $sres = style_list();
        print("<tr>\n\t<td align='left' class='header'>" . USER_STYLE . ":</td>");
        print("\n\t<td align='left' class='lista' colspan='2'><select name='style'>");

        foreach ($sres as $style) {
            $option = "\n<option ";
            if ($style["id"] == user::$current["style"])
                $option .= "selected='selected' ";

            $option .= "value='" . $style["id"] . "'>" . security::html_safe(unesc($style["style"])) . "</option>";
            print($option);
        }

        print("</select></td>\n</tr>");

        $fres = flag_list();
        print("<tr>\n\t<td align='left' class='header'>" . PEER_COUNTRY . ":</td>");
        print("\n\t<td align='left' class='lista' colspan='2'><select name='flag'>\n<option value='0'>---</option>");

        foreach ($fres as $flag) {
            $option = "\n<option ";
            if ($flag["id"] == user::$current["flag"])
                $option .= "selected='selected' ";

            $option .= "value='" . $flag["id"] . "'>" . security::html_safe(unesc($flag["name"])) . "</option>";
            print($option);
        }

        print("</select></td>\n</tr>");
        
        $tres = timezone_list();
        print("<tr>\n\t<td align='left' class='header'>" . TIMEZONE . ":</td>");
        print("\n\t<td align='left' class='lista' colspan='2'><select name='timezone'>");

        foreach ($tres as $timezone) {
            $option = "\n<option ";
            if ($timezone["difference"] == user::$current["time_offset"])
                $option .= "selected=selected ";
            $option .= "value=" . $timezone["difference"] . ">" . security::html_safe(unesc($timezone["timezone"])) . "</option>";
            print($option);
        }

        print("</select></td>\n</tr>");
        if ($FORUMLINK == "" || $FORUMLINK == "internal") {
            // topics per page
?>
    <tr>
        <td align='left' class='header'><?php
            echo TOPICS_PER_PAGE;
?>: </td>
        <td align='left' class='lista' colspan='2'><input type='text' size='3' name='topicsperpage' maxlength='3' value='<?php
            echo user::$current["topicsperpage"];
?>' /></td>
    </tr>
        <!-- posts per page -->
    <tr>
        <td align='left' class='header'><?php
            echo POSTS_PER_PAGE;
?>: </td>
        <td align='left' class='lista' colspan='2'><input type='text' size='3' name='postsperpage' maxlength='3' value='<?php
            echo user::$current["postsperpage"];
?>' /></td>
    </tr>
    <?php
        }
        // torrents per page
?>
    <tr>
        <td align='left' class='header'><?php
        echo TORRENTS_PER_PAGE;
?>: </td>
        <td align='left' class='lista' colspan='2'><input type='text' size='3' name='torrentsperpage' maxlength='3' value='<?php
        echo user::$current["torrentsperpage"];
?>' /></td>
    </tr>
    <!-- Password confirmation required to update user record -->
    <tr>
        <td align='left' class='header'><?php
        echo USER_PWD;
?>: </td>
        <td align='left' class='lista' colspan='2'><input type='password' size='40' name='passconf' value='' /><?php
        echo MUST_ENTER_PASSWORD;
?></td>
    </tr>
    <!-- Password confirmation required to update user record -->
        <tr>
           <td align='center' class='header' colspan='3'>
        <?php
        print("<input type='submit' name='confirm' value='" . FRM_CONFIRM . "' />&nbsp;&nbsp;&nbsp;&nbsp;<input type='submit' name='confirm' value='" . FRM_CANCEL . "' /></td>");
?>
        </tr>
        </table>
        </form>
        </center>
        </p>

        <?php

        print("<br />");
        block_end();
        print("<br />");
    }
	elseif ($do == "user" && $action == "post") {
        if ($_POST["confirm"] == FRM_CONFIRM) {
            $idlangue = intval(0 + $_POST["language"]);
            $idstyle = intval(0 + $_POST["style"]);
            $email = AddSlashes($_POST["email"]);
            $avatar = security::html_safe(AddSlashes($_POST["avatar"]));
            $idflag = intval(0 + $_POST["flag"]);
            $timezone = intval($_POST["timezone"]);
            
            // Password confirmation required to update user record
            (isset($_POST["passconf"])) ? $password = md5($_POST["passconf"]) : $password = "";
            
            $res = $db->query("SELECT password FROM users WHERE id = " . user::$current["uid"]);

            if ($res->num_rows > 0)
                $user = $res->fetch_assoc();
            
            if (!isset($user) || $password == "" || $user["password"] != $password) {
                err_msg(ERROR, ERR_PASS_WRONG);
                block_end();
                stdfoot();
                exit();
            }
            // Password confirmation required to update user record
            
            // check avatar image extension if someone have better idea ;)
            if ($avatar && $avatar != "" && !in_array(substr($avatar, strlen($avatar) - 4), array(
                ".gif",
                ".jpg",
                ".bmp",
                ".png"
            ))) {
                stderr(ERROR, ERR_AVATAR_EXT);
            }
            
            
            if ($email == "")
                err_msg(ERROR, ERR_NO_EMAIL);
            else {
                // Reverify Mail Hack by Petr1fied - Start --->
                if ($VALIDATION == "user") {
                    // Send a verification e-mail to the e-mail address they want to change it to
                    if (($email != "") && ($email != user::$current["email"])) {
                        $id = user::$current["uid"];
                        // Generate a random number between 10000 and 99999
                        $floor = 100000;
                        $ceiling = 999999;
                        srand((double) microtime() * 1000000);
                        $random = mt_rand($floor, $ceiling);
                        
                        // Update the members record with the random number and store the email they want to change to
                        @$db->query("UPDATE users SET random = '" . $random . "', temp_email = '" . $email . "' WHERE id = '" . $id . "'");
                        
                        // Send the verification email
                        @ini_set("sendmail_from", "");
                        if ($db->errno == 0)
                            mail($email, EMAIL_VERIFY, EMAIL_VERIFY_MSG . "\n\n" . $BASEURL . "/usercp.php?do=verify&action=changemail&newmail=" . $email . "&uid=" . $id . "&random=" . $random . "", "From: " . $SITENAME . " <" . $SITEEMAIL . ">") or stderr(ERROR, "Sending email has failed!");
                    }
                }
                $set = array();
                
                if ($VALIDATION != "user") {
                    if ($email != "")
                        $set[] = "email = '" . $email . "'";
                }
                // <--- Reverify Mail Hack by Petr1fied - End
				if ($idlangue > 0)
                    $set[] = "language = " . $idlangue;

                if ($idstyle > 0)
                    $set[] = "style = " . $idstyle;

                if ($idflag > 0)
                    $set[] = "flag = " . $idflag;
                
                $set[] = "time_offset = '" . $timezone. "'";
                $set[] = "avatar = '" . $avatar . "'";
                $set[] = "topicsperpage = " . intval(0 + $_POST["topicsperpage"]);
                $set[] = "postsperpage = " . intval(0 + $_POST["postsperpage"]);
                $set[] = "torrentsperpage = " . intval(0 + $_POST["torrentsperpage"]);
                
                $updateset = implode(",", $set);
                
                // Reverify Mail Hack by Petr1fied - Start --->
                // If they've tried to change their e-mail, give them a message telling them as much
                if (($email != "") && ($VALIDATION == "user") && ($email != user::$current["email"])) {
                    block_begin(EMAIL_VERIFY_BLOCK);
                    print(EMAIL_VERIFY_SENT1 . " " . $email . " " . EMAIL_VERIFY_SENT2 . "<a href='" . $BASEURL . "'>" . MNU_INDEX . "</a><br /><br /></center>");
                    block_end();
                    print("<br /><br />");
                }
				elseif ($updateset != "")
                {
                    $db->query("UPDATE users SET " . $updateset . " WHERE id = " . $uid);

                    print("<p align='center'><b>" . INF_CHANGED . "</b><br /><br />");
                    print("<a href='usercp.php?uid=" . $uid . "'>" . BCK_USERCP . "</a><br /></p>");
                }
            }
        } else
            redirect("usercp.php?uid=" . $uid);
    }
    // Reverify Mail Hack by Petr1fied - Start --->
    // Update the members e-mail account if the validation link checks out
    // If both "do=verify" and "action=changemail" are in the url
    elseif ($do == "verify" && $action == "changemail") {
        // Get the other values we need from the url
        $newmail = security::html_safe($_GET["newmail"]);
        $id = intval($_GET["uid"]);
        $random = intval($_GET["random"]);
        $idlevel = user::$current["id_level"];
        
        // Get the members random number, current email and temp email from their record
        $getacc = $db->fetch_assoc($db->query("SELECT random, email, temp_email FROM users WHERE id = " . $id));
        $oldmail = security::html_safe($getacc["email"]);
        $dbrandom = (int)$getacc["random"];
        $mailcheck = security::html_safe($getacc["temp_email"]);
        
        // Start a block to output the data to
        block_begin("Update email address");
        
        // If the random number in the url matches that in the member record
        if ($random == $dbrandom) {
            // Verify the email address in the url is the address we sent the mail to
            if ($newmail != $mailcheck) {
                err_msg(ERROR, NOT_MAIL_IN_URL);
                block_end();
                exit();
            }
            
            // Update their tracker member record with the now verified email address
            @$db->query("UPDATE users SET email = '" . $db->real_escape_string($newmail) . "' WHERE id = '" . $id . "'");
            // Print a message stating that their email has been successfully changed
            print(REVERIFY_CONGRATS1 . " " . $oldmail . " " . REVERIFY_CONGRATS2 . " " . $newmail . " " . REVERIFY_CONGRATS3 . "<a href='" . $BASEURL . "'>" . MNU_INDEX . "</a><br /><br /></center>");
            // If the member clicking the link is validating...
            if ($idlevel == 2)
            // ...we may as well upgrade their rank to member whilst we're at it.
                @$db->query("UPDATE users SET id_level = 3 WHERE id = '" . $id . "'");
        }
        // If the random number in the url is incorrect print an error message
        else
            print(REVERIFY_FAILURE . "<a href='" . $BASEURL . "'>" . MNU_INDEX . "</a><br /><br /></center>");
        // End the block and add a couple of linespaces afterwards.
        block_end();
        print("<br /><br />");
    }
    // <--- Reverify Mail Hack by Petr1fied - End
    elseif ($do == "pid_c" && $action == "change") {
        block_begin(CHANGE_PID);

        $result = $db->query("SELECT pid FROM users WHERE id = " . user::$current['uid']);
        $row = $result->fetch_assoc();

        $pid = $db->real_escape_string($row["pid"]);

        if (!$pid) {
            $pid = md5(user::$current['uid'] + user::$current['username'] + user::$current['password'] + user::$current['lastconnect']);
            $res = $db->query("UPDATE users SET pid = '" . $pid . "' WHERE id = '" . user::$current['uid'] . "'");
        }

        print("\n<form method='post' name='pid' action='usercp.php?do=pid_c&action=post&uid=" . $uid . "'><table class='lista' width='100%' align='center'>");
        print("\n<tr><td class='header'>" . PID . ":</td><td class='lista'>" . $pid . "</td></tr>");
        print("\n<tr><td class='header' align='center' colspan='2'><input type='submit' name='confirm' value='Reset PID'/>&nbsp;&nbsp;&nbsp;<input type='submit' name='confirm' value='" . FRM_CANCEL . "'/></td></tr>");
        print("\n</table></form>");
        print("<br />");
        block_end();
        print("<br />");
    }
	elseif ($do == "pid_c" && $action == "post") {
        if ($_POST["confirm"] == "Reset PID") {
            $pid = md5(user::$current['uid'] + user::$current['username'] + user::$current['password'] + user::$current['lastconnect']);
            $res = $db->query("UPDATE users SET pid = '" . $pid . "' WHERE id = '" . user::$current['uid'] . "'");

            if ($res)
                redirect("usercp.php?uid=" . $uid);
            else
                err_msg(ERROR, NOT_POSS_RESET_PID . "<br /><a href='usercp.php?uid=" . $uid . "'>" . HOME . "</a><br />");
        } else {
            redirect("usercp.php?uid=" . $uid);
        }
    } else {
        block_begin(WELCOME_UCP);
        print("<center><br />" . UCP_NOTE_1 . "<br />" . UCP_NOTE_2 . "<br /><br />\n");
        print("</center>");
        block_end();

        block_begin(CURRENT_DETAILS);

        $id = user::$current["uid"];
        $res = $db->query("SELECT users.lip, users.username, users.downloaded, users.uploaded, UNIX_TIMESTAMP(users.joined) AS joined, users.flag, countries.name, countries.flagpic FROM users LEFT JOIN countries ON users.flag = countries.id WHERE users.id = " . $id);
        $row = $res->fetch_array(MYSQLI_BOTH);

        print("<table class='lista' width='100%'>\n");
        print("<tr>\n<td class='header'>" . USER_NAME . "</td>\n<td class='lista'>" . unesc(user::$current["username"]) . "</td>\n");

        if (user::$current["avatar"] && user::$current["avatar"] != "")
            print("<td class='lista' align='center' valign='middle' rowspan='4'><img border='0' width='138' src='" . security::html_safe(user::$current["avatar"]) . "' /></td>");

        print("</tr>");

        if (user::$current["edit_users"] == "yes" || user::$current["admin_access"] == "yes") {
            print("<tr>\n<td class='header'>" . EMAIL . "</td>\n<td class='lista'>" . unesc(user::$current["email"]) . "</td></tr>\n");
            print("<tr>\n<td class='header'>" . LAST_IP . "</td>\n<td class='lista'>" . long2ip($row["lip"]) . "</td></tr>\n");
            print("<tr>\n<td class='header'>" . USER_LEVEL . "</td>\n<td class='lista'>" . unesc(user::$current["level"]) . "</td></tr>\n");

            $colspan = " colspan='2'";
        } else {
            print("<tr>\n<td class='header'>" . USER_LEVEL . "</td>\n<td class='lista'>" . unesc(user::$current["level"]) . "</td></tr>\n");

            $colspan = '';
        }

        print("<tr>\n<td class='header'>" . USER_JOINED . "</td>\n<td class='lista'" . $colspan . ">" . (user::$current["joined"] == 0 ? "N/A" : get_date_time(user::$current["joined"])) . "</td></tr>\n");
        print("<tr>\n<td class='header'>" . USER_LASTACCESS . "</td>\n<td class='lista'" . $colspan . ">" . (user::$current["lastconnect"] == 0 ? "N/A" : get_date_time(user::$current["lastconnect"])) . "</td></tr>\n");
        print("<tr>\n<td class='header'>" . PEER_COUNTRY . "</td>\n<td class='lista' colspan='2'>" . ($row["flag"] == 0 ? "" : unesc($row['name'])) . "&nbsp;&nbsp;<img src='images/flag/" . (!$row["flagpic"] || $row["flagpic"] == "" ? "unknown.gif" : $row["flagpic"]) . "' alt='" . ($row["flag"] == 0 ? "Unknown" : unesc($row['name'])) . "' /></td></tr>\n");
        print("<tr>\n<td class='header'>" . DOWNLOADED . "</td>\n<td class='lista' colspan='2'>" . misc::makesize((int)$row["downloaded"]) . "</td></tr>\n");
        print("<tr>\n<td class='header'>" . UPLOADED . "</td>\n<td class='lista' colspan='2'>" . misc::makesize((int)$row["uploaded"]) . "</td></tr>\n");

        if (intval($row["downloaded"]) > 0) {
            $sr = (int)$row["uploaded"] / (int)$row["downloaded"];

            if ($sr >= 4)
                $s = "images/smilies/thumbsup.gif";
            else if ($sr >= 2)
                $s = "images/smilies/grin.gif";
            else if ($sr >= 1)
                $s = "images/smilies/smile1.gif";
            else if ($sr >= 0.5)
                $s = "images/smilies/noexpression.gif";
            else if ($sr >= 0.25)
                $s = "images/smilies/sad.gif";
            else
                $s = "images/smilies/thumbsdown.gif";
            $ratio = number_format($sr, 2) . "&nbsp;&nbsp;<img src='" . $s . "'>";
        }
		else
            $ratio = "&infin;";
        
        print("<tr>\n<td class='header'>" . RATIO . "</td>\n<td class='lista' colspan='2'>" . $ratio . "</td></tr>\n");

        // Only show if forum is internal
        if ($GLOBALS["FORUMLINK"] == '' || $GLOBALS["FORUMLINK"] == 'internal') {
            $sql  = $db->query("SELECT * FROM posts INNER JOIN users ON posts.userid = users.id WHERE users.id = " . user::$current["uid"]);
            $posts = $sql->num_rows;

            $memberdays = max(1, round((vars::$timestamp - $row['joined']) / 86400));
            $posts_per_day = number_format(round($posts / $memberdays, 2), 2);
            print("<tr>\n<td class='header'>" . FORUM . " " . POSTS . ":</td>\n<td class='lista' colspan='2'>" . $posts . " &nbsp; [" . sprintf(POSTS_PER_DAY, $posts_per_day) . "]</td></tr>\n");
        }
        print("</table>");
        block_end();
        // ------------------------
        block_begin(UPLOADED . " " . MNU_TORRENT);

        $resuploaded = $db->query("SELECT namemap.filename, UNIX_TIMESTAMP(namemap.data) AS added, namemap.size, summary.seeds, summary.leechers, summary.finished FROM namemap INNER JOIN summary ON namemap.info_hash = summary.info_hash WHERE uploader = " . $uid . " ORDER BY data DESC");
        $numtorrent = $resuploaded->num_rows;

        if ($numtorrent > 0) {
            list($pagertop, $limit) = misc::pager(($utorrents == 0 ? 15 : $utorrents), $numtorrent, $_SERVER["PHP_SELF"] . "?uid=" . $uid . "&");

            print($pagertop);

            $resuploaded = $db->query("SELECT namemap.filename, UNIX_TIMESTAMP(namemap.data) AS added, namemap.size, summary.seeds, summary.leechers, summary.finished, summary.info_hash AS hash FROM namemap INNER JOIN summary ON namemap.info_hash = summary.info_hash WHERE uploader = " . $uid . " ORDER BY data DESC " . $limit);
        }
?>
<table width='100%' class='lista'>
<!-- Column Headers  -->
<tr>
<td align='center' class='header'><?php
        echo FILE;
?></td>
<td align='center' class='header'><?php
        echo ADDED;
?></td>
<td align='center' class='header'><?php
        echo SIZE;
?></td>
<td align='center' class='header'><?php
        echo SHORT_S;
?></td>
<td align='center' class='header'><?php
        echo SHORT_L;
?></td>
<td align='center' class='header'><?php
        echo SHORT_C;
?></td>
<td align='center' class='header'><?php
        echo EDIT;
?></td>
<td align='center' class='header'><?php
        echo DELETE;
?></td>
</tr>

<?php

        if ($resuploaded && $resuploaded->num_rows > 0) {
            while ($rest = $resuploaded->fetch_array(MYSQLI_BOTH)) {
                print("\n<tr>\n<td class='lista'>" . security::html_safe(unesc($rest["filename"])) . "</td>");

                include(INCL_PATH . 'offset.php');

                print("\n<td class='lista' align='center'>" . date("d/m/Y H:m:s", $rest["added"] - $offset) . "</td>");
                print("\n<td class='lista' align='right'>" . misc::makesize((int)$rest["size"]) . "</td>");
                print("\n<td align='right' class='" . linkcolor($rest["seeds"]) . "'>" . (int)$rest['seeds'] . "</td>");
                print("\n<td align='right' class='" . linkcolor($rest["leechers"]) . "'>" . (int)$rest['leechers'] . "</td>");
                print("\n<td class='lista' align='right'>" . ($rest["finished"] > 0 ? (int)$rest["finished"] : "---") . "</td>");
                print("<td class='lista' align='center'><a href='edit.php?info_hash=" . $rest["hash"] . "&returnto=" . urlencode("torrents.php") . "'>" . image_or_link($STYLEPATH . "/edit.png", "", EDIT) . "</a></td>");
                print("<td class='lista' align='center'><a href='delete.php?info_hash=" . $rest["hash"] . "&returnto=" . urlencode("torrents.php") . "'>" . image_or_link($STYLEPATH . "/delete.png", "", DELETE) . "</a></td>\n</tr>");
            }
            print("\n</table>");
        } else {
            print("<tr>\n<td class='lista' align='center' colspan='8'>" . NO_TORR_UP_USER . "</td>\n</tr>\n</table>");
        }

        block_end();
        // ------------------------
        print("<br />");
    }
    block_end();
}

stdfoot();
exit();

?>
