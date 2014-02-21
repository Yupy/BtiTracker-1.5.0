<?php
global $CURUSER, $user;

         block_begin("".BLOCK_USER."");

         if (!$CURUSER || $CURUSER["id"]==1)
            {
            // guest-anonymous, login require
            ?>
            <form action="login.php" name="login" method="post">
            <table class="lista" border="0" align="center">
            <tr><td align="right" class="header"><?php echo USER_NAME?>:</td><td class="lista"><input type="text" size="10" name="uid" value="<?php $user ?>" maxlength="40" /></td></tr>
            <tr><td align="right" class="header"><?php echo USER_PWD?>:</td><td class="lista"><input type="password" size="10" name="pwd" maxlength="40" /></td></tr>
            <tr><td colspan="2"  class="header" align="center"><input type="submit" value="<?php echo FRM_LOGIN?>" /></td></tr>
            <tr><td class="header" align="center"><a href="account.php"><?php echo ACCOUNT_CREATE?></a></td><td class="header" align="center"><a href="recover.php"><?php echo RECOVER_PWD?></a></td></tr>
            </table>
            </form>
            <?php
            }
         else
             {
             // user information
             $style=style_list();
             $langue=language_list();
             print("\n<tr><td align=center class=blocklist>".USER_NAME.":  " .unesc($CURUSER["username"])."</td></tr>\n");
             print("<tr><td align=center class=blocklist>".USER_LEVEL.": ".$CURUSER["level"]."</td></tr>\n");
             $resmail=run_query("SELECT COUNT(*) FROM messages WHERE readed='no' AND receiver=$CURUSER[uid]");
             if ($resmail && mysqli_num_rows($resmail)>0)
                {
                 $mail=mysqli_fetch_row($resmail);
                 if ($mail[0]>0)
                    print("<td class=blocklist align=center><a href=usercp.php?uid=".$CURUSER["uid"]."&do=pm&action=list>".MAILBOX."</a> (<font color=\"#FF0000\"><b>$mail[0]</b></font>)</td>\n");
                 else
                     print("<td class=blocklist align=center><a href=usercp.php?uid=".$CURUSER["uid"]."&do=pm&action=list>".MAILBOX."</a></td>\n");
                }
             else
                 print("<tr><td align=center>".NO_MAIL."</td></tr>");
             print("<tr><td align=center class=blocklist>");
             include("include/offset.php");
             print(USER_LASTACCESS.":<br />".date("d/m/Y H:i:s",$CURUSER["lastconnect"]-$offset));
             print("</td></tr>\n<tr><form name=jump><td class=blocklist align=center>");
             print(USER_STYLE.":<br>\n<select name=\"style\" size=\"1\" onChange=\"location=document.jump.style.options[document.jump.style.selectedIndex].value\">");
             foreach($style as $a)
                            {
                            print("<option ");
                            if ($a["id"]==$CURUSER["style"])
                               print("selected=selected");
                            print(" value=account_change.php?style=".$a["id"]."&returnto=".urlencode($_SERVER['REQUEST_URI']).">".$a["style"]."</option>");
                            }
             print("</select>");
             print("</td></tr>\n<tr><td class=blocklist align=center>");
             print(USER_LANGUE.":<br>\n<select name=\"langue\" size=\"1\" onChange=\"location=document.jump.langue.options[document.jump.langue.selectedIndex].value\">");
             foreach($langue as $a)
                            {
                            print("<option ");
                            if ($a["id"]==$CURUSER["language"])
                               print("selected=selected");
                            print(" value=account_change.php?langue=".$a["id"]."&returnto=".urlencode($_SERVER['REQUEST_URI']).">".$a["language"]."</option>");
                            }
             print("</select>");
             print("</td>\n</tr>\n");
             print("\n<tr><td align=\"center\" class=\"blocklist\"><a href=\"usercp.php?uid=".$CURUSER["uid"]."\">".USER_CP."</a></td></tr>\n");
						
             if ($CURUSER["admin_access"]=="yes")
 print("\n<tr><td align=\"center\" class=\"blocklist\"><a href=\"admincp.php?user=".$CURUSER["uid"]."&code=".$CURUSER["random"]."\">".MNU_ADMINCP."</a></td></tr>\n");
              print("</form>\n</table>");
             }

 block_end();

?>
