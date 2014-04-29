<?php
require_once ("include/functions.php");
require_once ("include/config.php");

dbconn(true);

standardheader('User Control Panel');

$uid=(isset($_GET["uid"])?intval($_GET["uid"]):1);

if ($CURUSER["uid"]!=$uid || $CURUSER["uid"]==1)
   {
       err_msg(ERROR,ERR_USER_NOT_USER);
       stdfoot();
       exit;
}
else
    {
    $utorrents=intval($CURUSER["torrentsperpage"]);
    if (isset($_GET["do"])) $do=$_GET["do"];
      else $do = "";
    if (isset($_GET["action"]))
       $action=$_GET["action"];

    // begin the real admin page
     block_begin($CURUSER["username"]."'s Control Panel");
     print("\n<table class=\"lista\" width=\"100%\" align=\"center\"><tr>");
     print("\n<td class=\"header\" align=\"center\"><a href=\"usercp.php?uid=$uid\">".MNU_UCP_HOME."</a></td>");
     print("\n<td class=\"header\" align=\"center\"><a href=\"usercp.php?uid=$uid&do=pm&action=list&what=inbox\">".MNU_UCP_PM."</a></td>");
     print("\n<td class=\"header\" align=\"center\"><a href=\"usercp.php?uid=$uid&do=pm&action=list&what=outbox\">".MNU_UCP_OUT."</a></td>");
     print("\n<td class=\"header\" align=\"center\"><a href=\"usercp.php?do=pm&action=edit&uid=$uid&what=new\">".MNU_UCP_NEWPM."</a></td>");
     print("\n<td class=\"header\" align=\"center\"><a href=\"usercp.php?do=user&action=change&uid=$uid\">".MNU_UCP_INFO."</a></td>");
     print("\n<td class=\"header\" align=\"center\"><a href=\"usercp.php?do=pwd&action=change&uid=$uid\">".MNU_UCP_CHANGEPWD."</a></td>");
     print("\n<td class=\"header\" align=\"center\"><a href=\"usercp.php?do=pid_c&action=change&uid=$uid\">".CHANGE_PID."</a></td>");
     print("\n</tr></table>\n");

     if ($do=="pm" && $action=="list")
        {
        // MODIFIED select for deletion by gAnDo
            print("<script type=\"text/javascript\">
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
                 $what=$_GET["what"];
           else $what = "";
                   if ($what=="outbox")
                  {
                   block_begin(MNU_UCP_OUT);
                   print("\n<form action=\"usercp.php?do=pm&action=deleteall&uid=$uid&type=out\" name=\"deleteall\" method=\"post\">");
                   print("\n<table class=lista width=100% align=center>");
                   print("\n<tr><td class=header align=center>".READED."</td><td class=header align=center>".RECEIVER."</td><td class=header align=center>".DATE."</td><td class=header align=center>".SUBJECT."</td>");

                   $res=run_query("select messages.*, users.username as receivername FROM messages LEFT JOIN users on users.id=messages.receiver WHERE sender=$uid ORDER BY added DESC");
                   if (!$res || mysqli_num_rows($res)==0)
                     {
                      print("\n</tr><tr><td class=lista colspan=5 align=center>".NO_MESSAGES."</td></tr>");
                     }
                   else {
                       print("\n<td class=\"header\" align=\"center\"><input type=\"checkbox\" name=\"all\" onclick=\"SetAllCheckBoxes('deleteall','msg[]',this.checked)\" /></td></tr>");
                        while ($result=mysqli_fetch_array($res))
                              print("\n<tr><td class=lista align=center>".unesc($result["readed"])."</td>
                              <td class=lista align=center><a href=userdetails.php?id=".$result["receiver"].">".unesc($result["receivername"])."</a></td>
                              <td class=lista align=center>".get_date_time($result["added"])."</td>
                              <td class=lista align=center><a href=usercp.php?do=pm&action=read&uid=$uid&id=$result[id]&what=outbox>".format_comment(unesc($result["subject"]))."</a></td>
                              <td class=\"lista\" align=\"center\"><input type=\"checkbox\" name=\"msg[]\" value=\"".$result["id"]."\" /></td>
                              </tr>");
                        print("\n<tr>\n<td class=\"lista\" align=\"right\" colspan=\"5\"><input type=\"submit\" name=\"action\" value=\"Delete\" /></td></tr>");
                   }
                   print("\n</table></form>");
                   block_end();
                   print("<br />");
                   }
                   else
                  {
                   block_begin(MNU_UCP_IN);
                   print("\n<form action=\"usercp.php?do=pm&action=deleteall&uid=$uid&type=in\" name=\"deleteall\" method=\"post\">");
                   print("\n<table class=lista width=100% align=center>");
                   print("\n<tr><td class=header align=center>".READED."</td><td class=header align=center>".SENDER."</td><td class=header align=center>".DATE."</td><td class=header align=center>".SUBJECT."</td>");

                   $res=run_query("select messages.*, users.username as sendername FROM messages LEFT JOIN users on users.id=messages.sender WHERE receiver=$uid ORDER BY added DESC");
                   if (!$res || mysqli_num_rows($res)==0)
                      print("\n</tr><tr><td class=lista colspan=5 align=center>".NO_MESSAGES."</td></tr>");
                   else {
                        print("\n<td class=\"header\" align=\"center\"><input type=\"checkbox\" name=\"all\" onclick=\"SetAllCheckBoxes('deleteall','msg[]',this.checked)\" /></td></tr>");
                        while ($result=mysqli_fetch_array($res))
                              print("\n<tr>
                              <td class=lista align=center>".unesc($result["readed"])."</td>
                              <td class=lista align=center><a href=userdetails.php?id=".$result["sender"].">".unesc($result["sendername"])."</a></td>
                              <td class=lista align=center>".get_date_time($result["added"])."</a></td>
                              <td class=lista align=center><a href=usercp.php?do=pm&action=read&uid=$uid&id=$result[id]&what=inbox>".format_comment(unesc($result["subject"]))."</a></td>
                              <td class=\"lista\" align=\"center\"><input type=\"checkbox\" name=\"msg[]\" value=\"".$result["id"]."\" /></td>
                              </tr>");
                        print("\n<tr>\n<td class=\"lista\" align=\"right\" colspan=\"5\"><input type=\"submit\" name=\"action\" value=\"Delete\" /></td></tr>");

                   }
                   print("\n</table></form>");
                   block_end();
                   print("<br />");
                   }
        }
     elseif ($do=="pm" && $action=="read")
        {
            $id=intval($_GET["id"]);
            $what=$_GET["what"];
            if ($what=="inbox")
               $res=run_query("select messages.*, messages.sender as userid, users.username as sendername FROM messages INNER JOIN users on users.id=messages.sender WHERE receiver=$uid AND messages.id=$id");
            elseif ($what=="outbox")
                $res=run_query("select messages.*, messages.receiver as userid, users.username as sendername FROM messages INNER JOIN users on users.id=messages.receiver WHERE sender=$uid AND messages.id=$id");
            block_begin(PRIVATE_MSG);
            if (!$res)
               err_msg(ERROR,BAD_ID);
            else
                {
                print("\n<table class=\"lista\" width=\"100%\" align=\"center\" cellpadding=\"2\">");
                $result=mysqli_fetch_array($res);
                print("\n<tr><td width=30% rowspan=2 class=lista><a href=userdetails.php?id=".$result["userid"].">".unesc($result["sendername"])."</a><br />".get_date_time($result["added"])."<br />(".get_elapsed_time($result["added"])." ago)</td>");
                print("\n<td class=header>".SUBJECT.": ".format_comment(unesc($result["subject"]))."</td></tr>");
                print("\n<tr><td>".format_comment(unesc($result["msg"]))."</td></tr>");
                print("\n</table>");
                print("<br />");
                if ($what=="inbox")
                   {
                   print("\n<table class=lista width=100% align=center>");
                   print("\n<tr><td class=lista align=center><input onclick=\"location.href='usercp.php?do=pm&action=edit&what=quote&uid=$uid&id=$id'\" type=\"button\" value=\"".QUOTE."\"/></td><td class=lista align=center><input onclick=\"location.href='usercp.php?do=pm&action=edit&uid=$uid&id=$id'\" type=\"button\" value=\"".ANSWER."\"/></td><td class=lista align=center><input type=\"button\" onclick=\"location.href='usercp.php?do=pm&action=delete&uid=$uid&id=$id'\" value=\"".DELETE."\"/></td></tr>");
                   print("\n</table>");
                   run_query("UPDATE messages SET readed='yes' WHERE id=$id");
                }
            }
            print("<br />");
            block_end();
            print("<br />");

        }
     elseif ($do=="pm" && $action=="edit")
        {
            // if new pm will give id=0 and empty array
            if (isset($_GET['id']) && $_GET['id'])
                        $id=intval(0+$_GET['id']);
            else $id=0;
            if (!isset($_GET['what'])) $_GET['what'] = '';
            if (!isset($_GET['to'])) $_GET['to'] = '';

            $res=run_query("select messages.*, users.username as sendername FROM messages INNER JOIN users on users.id=messages.sender WHERE receiver=$uid AND messages.id=$id");
            block_begin(PRIVATE_MSG);
            if (!$res)
               err_msg(ERROR,BAD_ID);
            else
                {
                print("\n<form method=post name=edit action=usercp.php?do=$do&action=post&uid=$uid&what=".htmlsafechars($_GET["what"])."><table class=\"lista\" align=\"center\" cellpadding=\"2\">");
                $result=mysqli_fetch_array($res);
                print("\n<tr><td class=header>".RECEIVER.":</td><td class=header><input type=\"text\" name=\"receiver\" value=\"".($_GET["what"]!="new" ? unesc($result["sendername"]):htmlsafechars(urldecode($_GET["to"])))."\" size=\"40\" maxlength=\"40\" ".($_GET["what"]!="new" ? " readonly" : "")." />&nbsp;&nbsp;".($_GET["what"]=="new" ? "<a href=\"javascript:popusers('searchusers.php');\">".FIND_USER."</a>" : "")."</td></tr>");
                print("\n<tr><td class=header>".SUBJECT.":</td><td class=header><input type=\"text\" name=\"subject\" value=\"".($_GET["what"]!="new" ? (strpos(unesc($result["subject"]), "Re:")===false?"Re:":"").unesc($result["subject"]):"")."\" size=\"40\" maxlength=\"40\" /></td></tr>");
                print("\n<tr><td colspan=2>");
                print(textbbcode("edit","msg",($_GET["what"]=="quote" ? "[quote=".htmlsafechars($result["sendername"])."]".unesc($result["msg"])."[/quote]" : "")));
                print("\n</td></tr>");
                print("\n</table>");
                print("<br />");
                print("\n<table class=lista width=100% align=center>");
                print("\n<tr><td class=lista align=center><input type=\"submit\" name=\"confirm\" value=\"".FRM_CONFIRM."\" /></td>");
                print("<td class=lista align=center><input type=\"submit\" name=\"confirm\" value=\"".FRM_PREVIEW."\" /></td>");
                print("<td class=lista align=center><input type=\"submit\" name=\"confirm\" value=\"".FRM_CANCEL."\" /></td></tr>");
                print("\n</table></form>");
            }
            print("<br />");
            block_end();
            print("<br />");

        }
     elseif ($do=="pm" && $action=="delete")
        {
            $id=intval($_GET["id"]);
            run_query("DELETE FROM messages WHERE receiver=$uid AND id=$id") or die(((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)));
            redirect("usercp.php?uid=$uid&do=pm&action=list&what=inbox");
        }
     elseif ($do=="pm" && $action=="deleteall")
        {
        // MODIFIED DELETE ALL VERSION BY gAnDo
            if (isset($_GET["type"]))
                $what=$_GET["type"];
            else
                {
                redirect("usercp.php?uid=$uid&do=pm&action=list&what=".($what=="in"?"inbox":"outbox"));
                exit;
                }
           foreach($_POST["msg"] as $selected=>$msg)
                  @run_query("DELETE FROM messages WHERE id=\"$msg\"");
           redirect("usercp.php?uid=$uid&do=pm&action=list&what=".($what=="in"?"inbox":"outbox"));
        }
     elseif ($do=="pm" && $action=="post")
        {
            if ($_POST["confirm"]==FRM_CONFIRM)
               {
               $res=run_query("SELECT id FROM users WHERE username=".sqlesc($_POST["receiver"]));
               if (!$res || mysqli_num_rows($res)==0)
                  err_msg(ERROR,ERR_USER_NOT_FOUND);
               else
                   {
                   $result=mysqli_fetch_array($res);
                   $subject=sqlesc($_POST["subject"]);
                   $msg=sqlesc($_POST["msg"]);
                   $rec=$result["id"];
                   $send=$CURUSER["uid"];

                   if ($rec==1 || $rec==$send)
                      err_msg(ERROR,ERR_PM_GUEST);
                   else {
                        if ($subject=="''")
                           $subject="'no subject'";
                        run_query("INSERT INTO messages (sender, receiver, added, subject, msg) VALUES ($send,$rec,UNIX_TIMESTAMP(),$subject,$msg)") or die(((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)));
                        redirect("usercp.php?uid=$uid&do=pm&action=list");
                        }
                   }
               }
            elseif ($_POST["confirm"]==FRM_PREVIEW)
                {
                block_begin(PRIVATE_MSG);
                block_begin(FRM_PREVIEW);
                print("<table width=100% align=center class=lista><tr><td class=lista align=center>" . format_comment(unesc($_POST["msg"])) . "</td></tr>\n");
                print("</table>");
                block_end();
                print("<br />");
                print("\n<form method=post name=edit action=usercp.php?do=$do&action=post&uid=$uid&what=".htmlsafechars($_GET["what"])."><table class=\"lista\" align=\"center\" cellpadding=\"2\">");
                print("\n<tr><td class=header>".RECEIVER.":</td><td class=header><input type=\"text\" name=\"receiver\" value=\"".htmlsafechars(unesc($_POST["receiver"]))."\" size=\"40\" maxlength=\"40\" />&nbsp;&nbsp;".($_GET["what"]=="new" ? "<a href=\"javascript:popusers('searchusers.php');\">".FIND_USER."</a>" : "")."</td></tr>");
                print("\n<tr><td class=header>".SUBJECT.":</td><td class=header><input type=\"text\" name=\"subject\" value=\"".htmlsafechars(unesc($_POST["subject"]))."\" size=\"40\" maxlength=\"40\" /></td></tr>");
                print("\n<tr><td colspan=2>");
                print(textbbcode("edit","msg",htmlsafechars(unesc($_POST["msg"]))));
                print("\n</td></tr>");
                print("\n</table>");
                print("<br />");
                print("\n<table class=lista width=100% align=center>");
                print("\n<tr><td class=lista align=center><input type=\"submit\" name=\"confirm\" value=\"".FRM_CONFIRM."\" /></td>");
                print("<td class=lista align=center><input type=\"submit\" name=\"confirm\" value=\"".FRM_PREVIEW."\" /></td>");
                print("<td class=lista align=center><input type=\"submit\" name=\"confirm\" value=\"".FRM_CANCEL."\" /></td></tr>");
                print("\n</table></form>");
                block_end();
                }
               else
                   redirect("usercp.php?uid=$uid&do=pm&action=list");
        }
     elseif ($do=="pwd" && $action=="change")
        {
            block_begin(MNU_UCP_CHANGEPWD);
            print("\n<form method=\"post\" name=\"password\" action=\"usercp.php?do=pwd&action=post&uid=$uid\"><table class=\"lista\" width=\"100%\" align=\"center\">");
            print("\n<tr><td class=header>".OLD_PWD."</td><td class=lista><input type=\"password\" name=\"old_pwd\" size=\"40\" maxlength=\"40\" /></td></tr>");
            print("\n<tr><td class=header>".USER_PWD."</td><td class=lista><input type=\"password\" name=\"new_pwd\" size=\"40\" maxlength=\"40\" /></td></tr>");
            print("\n<tr><td class=header>".USER_PWD_AGAIN."</td><td class=lista><input type=\"password\" name=\"new_pwd1\" size=\"40\" maxlength=\"40\" /></td></tr>");
            print("\n</table>");
            print("<br />");
            print("\n<table class=lista width=100% align=center>");
            print("\n<tr><td class=lista align=center><input type=\"submit\" name=\"confirm\" value=\"".FRM_CONFIRM."\"/></td><td class=lista align=center><input type=\"submit\" name=\"confirm\" value=\"".FRM_CANCEL."\"/></td></tr>");
            print("\n</table></form>");
            print("<br />");
            block_end();
            print("<br />");
        }
     elseif ($do=="pwd" && $action=="post")
        {
        if ($_POST["confirm"]==FRM_CONFIRM)
           {
            if ($_POST["old_pwd"]=="")
               err_msg(ERROR,INS_OLD_PWD);
            elseif ($_POST["new_pwd"]=="")
               err_msg(ERROR,INS_NEW_PWD);
            elseif ($_POST["new_pwd"]!=$_POST["new_pwd1"])
               err_msg(ERROR,DIF_PASSWORDS);
            else
                {
                $respwd = run_query("SELECT * FROM users WHERE id=$uid AND password='".md5($_POST["old_pwd"])."' AND username=".sqlesc($CURUSER["username"])."");
                if (!$respwd || mysqli_num_rows($respwd)==0)
                   err_msg(ERROR,ERR_RETR_DATA);
                else {
                    run_query("UPDATE users SET password='".md5($_POST["new_pwd"])."' WHERE id=$uid AND password='".md5($_POST["old_pwd"])."' AND username=".sqlesc($CURUSER["username"])."") or die(((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)));
                    print("<p align=center><b>".PWD_CHANGED."</b><br /><br />");
                    print(NOW_LOGIN."<br /><br />");
                    print("<a href=\"login.php\">Go</a><br /></p>");
                    }
                }
            }
            else
                redirect("usercp.php?uid=$uid");
        }
     elseif ($do=="user" && $action=="change")
        {
        block_begin(ACCOUNT_EDIT);
?>
        <center>
        <p>
        <form name="utente" method="post" action="usercp.php?do=user&action=post&uid=<?php echo $uid; ?>">
        <table width="60%" border="0" class="lista">
        <tr>
           <td align="left" class="header"><?php echo USER_NAME ?>: </td>
           <td align="left" class="lista"><?php echo $CURUSER["username"]; ?></td>
           <!--avatar-->
           <?php
           if ($CURUSER["avatar"] && $CURUSER["avatar"]!="")
               print("<td class=lista align=center valign=top rowspan=3><img border=0 src=".unesc($CURUSER["avatar"])." /></td>");
           ?>
        </tr>
        <tr>
           <td align="left" class="header"><?php echo AVATAR_URL;?>: </td>
           <td align="left" class="lista"><input type="text" size="40" name="avatar" maxlength="100" value="<?php echo unesc($CURUSER["avatar"]); ?>"/></td>
        </tr>
        <tr>
           <td align="left" class="header"><?php echo USER_EMAIL?>:</td>
           <td align="left" class="lista"><input type="text" size="30" name="email" maxlength="30" value="<?php echo unesc($CURUSER["email"]);?>"/></td>
        </tr>
        <?php
        // Reverify Mail Hack by Petr1fied - Start --->
        if ($VALIDATION=="user") {
        // Display a message informing users that they will have
        // to verify their e-mail address if they attempt to change it ?>
        <tr>
           <td align="left" class="header"></td>
           <td align="left" class="lista" colspan=2><?php echo REVERIFY_MSG ?></td>
        </tr>
        <?php
            } // <--- Reverify Mail Hack by Petr1fied - End ?>
           <?php
           $lres=language_list();
           print("<tr>\n\t<td align=left class=\"header\">".USER_LANGUE.":</td>");
           print("\n\t<td align=\"left\" class=\"lista\" colspan=2><select name=language>");
           foreach($lres as $langue)
             {
               $option="\n<option ";
               if ($langue["id"]==$CURUSER["language"])
                  $option.="selected=selected ";
               $option.="value=".$langue["id"].">".unesc($langue["language"])."</option>";
               print($option);
             }
           print("</select></td>\n</tr>");

           $sres=style_list();
           print("<tr>\n\t<td align=left class=\"header\">".USER_STYLE.":</td>");
           print("\n\t<td align=\"left\" class=\"lista\" colspan=2><select name=style>");
           foreach($sres as $style)
             {
               $option="\n<option ";
               if ($style["id"]==$CURUSER["style"])
                  $option.="selected=selected ";
               $option.="value=".$style["id"].">".unesc($style["style"])."</option>";
               print($option);
             }
           print("</select></td>\n</tr>");
        // flag hack
        $fres=flag_list();
        print("<tr>\n\t<td align=left class=\"header\">".PEER_COUNTRY.":</td>");
        print("\n\t<td align=\"left\" class=\"lista\" colspan=2><select name=flag>\n<option value='0'>--</option>");
        foreach($fres as $flag)
          {
          $option="\n<option ";
              if ($flag["id"]==$CURUSER["flag"])
                $option.="selected=selected ";
              $option.="value=".$flag["id"].">".unesc($flag["name"])."</option>";
              print($option);
          }
        print("</select></td>\n</tr>");

           $tres=timezone_list();
           print("<tr>\n\t<td align=left class=\"header\">".TIMEZONE.":</td>");
           print("\n\t<td align=\"left\" class=\"lista\" colspan=\"2\"><select name=\"timezone\">");
           foreach($tres as $timezone)
             {
               $option="\n<option ";
               if ($timezone["difference"]==$CURUSER["time_offset"])
                  $option.="selected=selected ";
               $option.="value=".$timezone["difference"].">".unesc($timezone["timezone"])."</option>";
               print($option);
             }
           print("</select></td>\n</tr>");
           if ($FORUMLINK=="" || $FORUMLINK=="internal")
        {
        // topics per page
        ?>
    <tr>
        <td align="left" class="header"><?php echo TOPICS_PER_PAGE;?>: </td>
        <td align="left" class="lista" colspan="2"><input type="text" size="3" name="topicsperpage" maxlength="3" value="<?php echo $CURUSER["topicsperpage"]; ?>"/></td>
    </tr>
        <!-- posts per page -->
    <tr>
        <td align="left" class="header"><?php echo POSTS_PER_PAGE;?>: </td>
        <td align="left" class="lista" colspan="2"><input type="text" size="3" name="postsperpage" maxlength="3" value="<?php echo $CURUSER["postsperpage"]; ?>"/></td>
    </tr>
    <?php
        }
        // torrents per page
        ?>
    <tr>
        <td align="left" class="header"><?php echo TORRENTS_PER_PAGE;?>: </td>
        <td align="left" class="lista" colspan="2"><input type="text" size="3" name="torrentsperpage" maxlength="3" value="<?php echo $CURUSER["torrentsperpage"]; ?>"/></td>
    </tr>
    <!-- Password confirmation required to update user record -->
    <tr>
        <td align="left" class="header"><?php echo USER_PWD; ?>: </td>
        <td align="left" class="lista" colspan="2"><input type="password" size="40" name="passconf" value=""/><?php echo MUST_ENTER_PASSWORD; ?></td>
    </tr>
    <!-- Password confirmation required to update user record -->
        <tr>
           <td align="center" class="header" colspan="3">
        <?php
        print("<input type=\"submit\" name=\"confirm\" value=\"".FRM_CONFIRM."\" />&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"submit\" name=\"confirm\" value=\"".FRM_CANCEL."\" /></td>");
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
     elseif ($do=="user" && $action=="post")
        {
        if ($_POST["confirm"]==FRM_CONFIRM)
           {
           $idlangue=intval(0+$_POST["language"]);
           $idstyle=intval(0+$_POST["style"]);
           $email=AddSlashes($_POST["email"]);
           $avatar=htmlsafechars(AddSlashes($_POST["avatar"]));
           $idflag=intval(0+$_POST["flag"]);
           $timezone=intval($_POST["timezone"]);
           
           // Password confirmation required to update user record
           (isset($_POST["passconf"])) ? $password=md5($_POST["passconf"]) : $password="";
                      
           $res=run_query("SELECT password FROM users WHERE id=".$CURUSER["uid"]);
           if(mysqli_num_rows($res)>0)
               $user=mysqli_fetch_assoc($res);           

           if(!isset($user) || $password=="" || $user["password"]!=$password)
           {
               err_msg(ERROR,ERR_PASS_WRONG);
               block_end();
               stdfoot();
               exit();
           }
           // Password confirmation required to update user record
           
           // check avatar image extension if someone have better idea ;)
           if ($avatar && $avatar!="" && !in_array(substr($avatar,strlen($avatar)-4),array(".gif",".jpg",".bmp",".png")))
              {
              stderr(ERROR, ERR_AVATAR_EXT);
           }


           if ($email=="")
              err_msg(ERROR,ERR_NO_EMAIL);
           else
               {
               // Reverify Mail Hack by Petr1fied - Start --->
               if ($VALIDATION=="user") {
                   // Send a verification e-mail to the e-mail address they want to change it to
                   if (($email!="")&&($email!=$CURUSER["email"])) {
                       $id=$CURUSER["uid"];
                       // Generate a random number between 10000 and 99999
                       $floor = 100000;
                       $ceiling = 999999;
                       srand((double)microtime()*1000000);
                       $random = mt_rand($floor, $ceiling);

                       // Update the members record with the random number and store the email they want to change to
                       @run_query("UPDATE users SET random='".$random."', temp_email='".$email."' WHERE id='".$id."'");

                       // Send the verification email
                       @ini_set("sendmail_from","");
                       if (((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_errno($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_errno()) ? $___mysqli_res : false))==0)
                          mail($email,EMAIL_VERIFY,EMAIL_VERIFY_MSG."\n\n".$BASEURL."/usercp.php?do=verify&action=changemail&newmail=$email&uid=$id&random=$random","From: $SITENAME <$SITEEMAIL>") OR stderr(ERROR,"Sending email has failed!");
                       }
               }
               $set=array();

               if ($VALIDATION!="user") {
                   if ($email!="")
                   $set[]="email='$email'";
                }
                // <--- Reverify Mail Hack by Petr1fied - End
                  $set[]="language=$idlangue";
               if ($idstyle>0)
                  $set[]="style=$idstyle";
               if ($idflag>0)
                  $set[]="flag=$idflag";

               $set[]="time_offset='$timezone'";
               $set[]="avatar='$avatar'";
               $set[]="topicsperpage=".intval(0+$_POST["topicsperpage"]);
               $set[]="postsperpage=".intval(0+$_POST["postsperpage"]);
               $set[]="torrentsperpage=".intval(0+$_POST["torrentsperpage"]);

               $updateset=implode(",",$set);

               // Reverify Mail Hack by Petr1fied - Start --->
               // If they've tried to change their e-mail, give them a message telling them as much
               if (($email!="")&&($VALIDATION=="user")&&($email!=$CURUSER["email"]))
                  {
                  block_begin(EMAIL_VERIFY_BLOCK);
                  print(EMAIL_VERIFY_SENT1." $email ".EMAIL_VERIFY_SENT2."<a href=$BASEURL>".MNU_INDEX."</a><br /><br /></center>");
                  block_end();
                  print("<br /><br />");
                  }
               elseif ($updateset!="")
               // <--- Reverify Mail Hack by Petr1fied - End
                  {
                  run_query("UPDATE users SET $updateset WHERE id=$uid") or die(((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)));
                  print("<p align=center><b>".INF_CHANGED."</b><br /><br />");
                  print("<a href=\"usercp.php?uid=$uid\">".BCK_USERCP."</a><br /></p>");
                  }
              }
           }
           else
               redirect("usercp.php?uid=$uid");
        }

// Reverify Mail Hack by Petr1fied - Start --->
// Update the members e-mail account if the validation link checks out
// ==========================================================================================
    // If both "do=verify" and "action=changemail" are in the url
    elseif ($do=="verify" && $action=="changemail")
       {
       // Get the other values we need from the url
       $newmail=$_GET["newmail"];
       $id=intval($_GET["uid"]);
       $random=intval($_GET["random"]);
       $idlevel=$CURUSER["id_level"];

       // Get the members random number, current email and temp email from their record
       $getacc=mysqli_fetch_assoc(run_query("SELECT random, email, temp_email from users WHERE id=".$id));
       $oldmail=$getacc["email"];
       $dbrandom=$getacc["random"];
       $mailcheck=$getacc["temp_email"];

       // Start a block to output the data to
       block_begin("Update email address");

       // If the random number in the url matches that in the member record
       if ($random==$dbrandom) {

           // Verify the email address in the url is the address we sent the mail to
           if ($newmail!=$mailcheck) {
             err_msg(ERROR,NOT_MAIL_IN_URL); block_end(); exit();
           }

            // Update their tracker member record with the now verified email address
            @run_query("UPDATE users SET email='".((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $newmail) : ((trigger_error("Error...", E_USER_ERROR)) ? "" : ""))."' WHERE id='".$id."'");
            // Print a message stating that their email has been successfully changed
            print(REVERIFY_CONGRATS1."$oldmail".REVERIFY_CONGRATS2."$newmail".REVERIFY_CONGRATS3."<a href=$BASEURL>".MNU_INDEX."</a><br /><br /></center>");
            // If the member clicking the link is validating...
            if ($idlevel==2)
                // ...we may as well upgrade their rank to member whilst we're at it.
                @run_query("UPDATE users SET id_level=3 WHERE id='".$id."'");
                }
           // If the random number in the url is incorrect print an error message
           else print(REVERIFY_FAILURE."<a href=$BASEURL>".MNU_INDEX."</a><br /><br /></center>");
           // End the block and add a couple of linespaces afterwards.
           block_end();
           print("<br /><br />");
       }
// <--- Reverify Mail Hack by Petr1fied - End

    elseif ($do=="pid_c" && $action=="change")
       {

           block_begin(CHANGE_PID);
           $result=run_query("SELECT pid FROM users WHERE id=".$CURUSER['uid']);
           $row = mysqli_fetch_assoc($result);
           $pid=$row["pid"];
           if (!$pid)
           {$pid=md5($CURUSER['uid']+$CURUSER['username']+$CURUSER['password']+$CURUSER['lastconnect']);
           $res=run_query("UPDATE users SET pid='".$pid."' WHERE id='".$CURUSER['uid']."'");
           }
           print("\n<form method=\"post\" name=\"pid\" action=\"usercp.php?do=pid_c&action=post&uid=$uid\"><table class=\"lista\" width=\"100%\" align=\"center\">");
           print("\n<tr><td class=header>".PID.":</td><td class=lista>".$pid."</td></tr>");
           print("\n<tr><td class=header align=center colspan=2><input type=\"submit\" name=\"confirm\" value=\"Reset PID\"/>&nbsp;&nbsp;&nbsp;<input type=\"submit\" name=\"confirm\" value=\"".FRM_CANCEL."\"/></td></tr>");
           print("\n</table></form>");
           print("<br />");
           block_end();
           print("<br />");
       }
    elseif ($do=="pid_c" && $action=="post")
       {
       if ($_POST["confirm"]=="Reset PID"){
          $pid=md5($CURUSER['uid']+$CURUSER['username']+$CURUSER['password']+$CURUSER['lastconnect']);
          $res=run_query("UPDATE users SET pid='".$pid."' WHERE id='".$CURUSER['uid']."'");
          if ($res)
             redirect("usercp.php?uid=$uid");
          else
              err_msg(ERROR,NOT_POSS_RESET_PID."<br /><a href=\"usercp.php?uid=$uid\">".HOME."</a><br />");
          }
          else {
               redirect("usercp.php?uid=$uid");
               }
        }
     else {
          block_begin(WELCOME_UCP);
          print("<center><br />".UCP_NOTE_1."<br />".UCP_NOTE_2."<br /><br />\n");
          print("</center>");
          block_end();
          block_begin(CURRENT_DETAILS);
// ------------------------
          $id = intval($CURUSER["uid"]);
          $res=run_query("SELECT users.lip,users.username, UNIX_TIMESTAMP(users.joined) as joined, users.flag, countries.name, countries.flagpic FROM users LEFT JOIN countries ON users.flag=countries.id WHERE users.id=$id") or die(((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)));
          $row = mysqli_fetch_array($res);
		  
        if (($user_stats = $Memcached->get_value('usercp::stats::'.$CURUSER['uid'])) === false) {
            $stats_sql = $db->execute('SELECT uploaded, downloaded FROM users WHERE id = '.$db->escape_string($CURUSER['uid'])) or $db->display_errors();
            $user_stats = $db->fetch_assoc($stats_sql);

            $user_stats['uploaded'] = (float)$user_stats['uploaded'];
            $user_stats['downloaded'] = (float)$user_stats['downloaded'];
            $Memcached->cache_value('usercp::stats::'.$CURUSER['uid'], $user_stats, 3600);
        }
		  
          print("<table class=lista width=100%>\n");
          print("<tr>\n<td class=header>".USER_NAME."</td>\n<td class=lista>".unesc($CURUSER["username"])."</td>\n");
          if ($CURUSER["avatar"] && $CURUSER["avatar"]!="")
             print("<td class=lista align=center valign=middle rowspan=4><img border=0 src=".htmlsafechars($CURUSER["avatar"])." /></td>");
          print("</tr>");
          if ($CURUSER["edit_users"]=="yes" || $CURUSER["admin_access"]=="yes")
          {
            print("<tr>\n<td class=header>".EMAIL."</td>\n<td class=lista>".unesc($CURUSER["email"])."</td></tr>\n");
            print("<tr>\n<td class=header>".LAST_IP."</td>\n<td class=lista>".long2ip($row["lip"])."</td></tr>\n");
            print("<tr>\n<td class=header>".USER_LEVEL."</td>\n<td class=lista>".unesc($CURUSER["level"])."</td></tr>\n");
            $colspan=" colspan=2";
          }
          else
          {
            print("<tr>\n<td class=header>".USER_LEVEL."</td>\n<td class=lista>".unesc($CURUSER["level"])."</td></tr>\n");
            $colspan="";
          }
          print("<tr>\n<td class=header>".USER_JOINED."</td>\n<td class=lista$colspan>".($CURUSER["joined"]==0 ? "N/A" : get_date_time($CURUSER["joined"]))."</td></tr>\n");
          print("<tr>\n<td class=header>".USER_LASTACCESS."</td>\n<td class=lista$colspan>".($CURUSER["lastconnect"]==0 ? "N/A" : get_date_time($CURUSER["lastconnect"]))."</td></tr>\n");
          print("<tr>\n<td class=header>".PEER_COUNTRY."</td>\n<td class=lista colspan=2>".($row["flag"]==0 ? "":unesc($row['name']))."&nbsp;&nbsp;<img src=images/flag/".(!$row["flagpic"] || $row["flagpic"]==""?"unknown.gif":$row["flagpic"])." alt=".($row["flag"]==0 ? "unknow":unesc($row['name']))." /></td></tr>\n");
          print("<tr>\n<td class=header>".DOWNLOADED."</td>\n<td class=lista colspan=2>".makesize($user_stats["downloaded"])."</td></tr>\n");
          print("<tr>\n<td class=header>".UPLOADED."</td>\n<td class=lista colspan=2>".makesize($user_stats["uploaded"])."</td></tr>\n");
          if (intval($user_stats["downloaded"])>0)
           {
             $sr = $user_stats["uploaded"] / $user_stats["downloaded"];
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
            $ratio=number_format($sr,2)."&nbsp;&nbsp;<img src=$s>";
           }
          else
             $ratio="oo";

          print("<tr>\n<td class=header>".RATIO."</td>\n<td class=lista colspan=2>$ratio</td></tr>\n");
          // Only show if forum is internal
          if ( $GLOBALS["FORUMLINK"] == '' || $GLOBALS["FORUMLINK"] == 'internal' )
             {
             $sql = run_query("SELECT * FROM posts INNER JOIN users ON posts.userid = users.id WHERE users.id = " . $CURUSER["uid"]);
             $posts = mysqli_num_rows($sql);
             $memberdays = max(1, round( ( time() - $row['joined'] ) / 86400 ));
             $posts_per_day = number_format(round($posts / $memberdays,2),2);
             print("<tr>\n<td class=header><b>".FORUM." ".POSTS.":</b></td>\n<td class=lista colspan=2>" . $posts . " &nbsp; [" . sprintf(POSTS_PER_DAY, $posts_per_day) . "]</td></tr>\n");
          }
          print("</table>");
          block_end();
          // ------------------------
          block_begin(UPLOADED." ".MNU_TORRENT);
          $resuploaded = run_query("SELECT namemap.filename, UNIX_TIMESTAMP(namemap.data) as added, namemap.size, summary.seeds, summary.leechers, summary.finished FROM namemap INNER JOIN summary ON namemap.info_hash=summary.info_hash WHERE uploader=$uid ORDER BY data DESC");
          $numtorrent=mysqli_num_rows($resuploaded);
          if ($numtorrent>0)
             {
             list($pagertop, $pagerbottom, $limit) = pager(($utorrents==0?15:$utorrents), $numtorrent, $_SERVER["PHP_SELF"]."?uid=$uid&");
             print("$pagertop");
             $resuploaded = run_query("SELECT namemap.filename, UNIX_TIMESTAMP(namemap.data) as added, namemap.size, summary.seeds, summary.leechers, summary.finished, summary.info_hash as hash FROM namemap INNER JOIN summary ON namemap.info_hash=summary.info_hash WHERE uploader=$uid ORDER BY data DESC $limit");
          }
?>
<TABLE width="100%" class="lista">
<!-- Column Headers  -->
<TR>
<TD align="center" class="header"><?php echo FILE; ?></TD>
<TD align="center" class="header"><?php echo ADDED; ?></TD>
<TD align="center" class="header"><?php echo SIZE; ?></TD>
<TD align="center" class="header"><?php echo SHORT_S; ?></TD>
<TD align="center" class="header"><?php echo SHORT_L; ?></TD>
<TD align="center" class="header"><?php echo SHORT_C; ?></TD>
<TD align="center" class="header"><?php echo EDIT; ?></TD>
<TD align="center" class="header"><?php echo DELETE; ?></TD>
</TR>
<?php
          if ($resuploaded && mysqli_num_rows($resuploaded)>0)
             {
             while ($rest=mysqli_fetch_array($resuploaded))
                   {
                     print("\n<tr>\n<td class=\"lista\">".unesc($rest["filename"])."</td>");
                     include("include/offset.php");
                     print("\n<td class=\"lista\" align=\"center\">".date("d/m/Y",$rest["added"]-$offset)."</td>");
                     print("\n<td class=\"lista\" align=\"right\">".makesize($rest["size"])."</td>");
                     print("\n<td align=\"right\" class=\"".linkcolor($rest["seeds"])."\">$rest[seeds]</td>");
                     print("\n<td align=\"right\" class=\"".linkcolor($rest["leechers"])."\">$rest[leechers]</td>");
                     print("\n<td class=lista align=right>".($rest["finished"]>0?$rest["finished"]:"---")."</td>");
                     print("<td class=\"lista\" align=\"center\"><a href=\"edit.php?info_hash=".$rest["hash"]."&returnto=".urlencode("torrents.php")."\">".image_or_link("$STYLEPATH/edit.png","",EDIT)."</a></td>");
                     print("<td class=\"lista\" align=\"center\"><a href=\"delete.php?info_hash=".$rest["hash"]."&returnto=".urlencode("torrents.php")."\">".image_or_link("$STYLEPATH/delete.png","",DELETE)."</a></td>\n</tr>");
                   }
                   print("\n</table>");
             }
          else
              {
              print("<tr>\n<td class=lista align=center colspan=8>".NO_TORR_UP_USER."</td>\n</tr>\n</table>");
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
