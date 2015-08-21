<?php
/*
* BtiTracker v1.5.0 is a php tracker system for BitTorrent, easy to setup and configure.
* This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
* Updated and Maintained by Yupy.
* Copyright (C) 2004-2014 Btiteam.org
*/
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'include'.DIRECTORY_SEPARATOR.'functions.php');

dbconn();

if (!isset($_POST["language"]))
    $_POST["language"] = 0;

$idlang = intval($_POST["language"]);

standardheader('Account Management', true, $idlang);

?>
<script language='javascript'>
<!--

function FormControl($nopwd)
{
// Controllo nome + pwd
    if (document.utente.user.value == "" )
      {
        alert(INSERT_USERNAME);
        return false;
      }

     if ($nopwd=="mod") {
        return true;
     }

    if ((document.utente.pwd.value == ""))
      {
      alert(INSERT_PASSWORD);
      return false;

      }

    if ((document.utente.pwd.value !=  document.utente.pwd1.value))
      {
      alert(DIF_PASSWORDS);
      return false;
      }
   return true;
  }
// -->
</SCRIPT>

<?php

if (isset($_GET["uid"]))
    $id = intval($_GET["uid"]);
else
    $id = "";
if (isset($_GET["returnto"]))
    $link = urldecode($_GET["returnto"]);
else
    $link = "";
if (isset($_GET["act"]))
    $act = security::html_safe($_GET["act"]);
else
    $act = "signup";
if (isset($_GET["language"]))
    $idlangue = intval($_GET["language"]);
else
    $idlangue = "";
if (isset($_GET["style"]))
    $idstyle = intval($_GET["style"]);
else
    $idstyle = "";
if (isset($_GET["flag"]))
    $idflag = intval($_GET["flag"]);
else
    $idflag = "";

if (isset($_POST["uid"]) && isset($_POST["act"])) {
    if (isset($_POST["uid"]))
        $id = intval($_POST["uid"]);
    else
        $id = "";
    if (isset($_POST["returnto"]))
        $link = urldecode($_POST["returnto"]);
    else
        $link = "";
    if (isset($_POST["act"]))
        $act = security::html_safe($_POST["act"]);
    else
        $act = "";
}

print("<center>");

if ($act == "mod") {
    if (user::$current["edit_users"] != "yes" || $id == 1)
        stderr(ERROR, ERR_NOT_AUTH);
    else
        block_begin(ACCOUNT_EDIT);
} elseif ($act == "signup" && isset(user::$current["uid"]) && user::$current["uid"] != 1) {
    $url = "index.php";
    redirect($url);
} elseif ($act == "signup")
    block_begin(ACCOUNT_CREATE);
elseif ($act == "del") {
    if (user::$current["delete_users"] != "yes" || $id == 1 || user::$current["uid"] == $id)
        stderr(ERROR, ERR_NOT_AUTH);
    else
        block_begin(ACCOUNT_DELETE);
}

print("</center>");

$res      = $db->query("SELECT COUNT(*) FROM users WHERE id > 1");
$nusers   = $res->fetch_row();
$numusers = (int)$nusers[0];

if ($act == "signup" && $MAX_USERS != 0 && $numusers >= $MAX_USERS) {
    err_msg(ERROR, REACHED_MAX_USERS);
    block_end();
    stdfoot();
    exit();
}

if ($act == "confirm") {
    $random = intval($_GET["confirm"]);
    $res    = $db->query("UPDATE users SET id_level = 3 WHERE id_level = 2 AND random = " . $random);
    if (!$res)
        die("ERROR: " . $db->error() . "\n");
    else {
        block_begin(ACCOUNT_CREATE);
        print("<tr><td align='center'>" . ACCOUNT_CONGRATULATIONS . "</td></tr>");
        block_end();
        stdfoot();
        exit;
    }
}

if (user::$current["edit_users"] == "yes") {
    if (!isset($_POST["elimina"]))
        $_POST["elimina"] = "";

    if ($_POST["elimina"] == FRM_DELETE) {
        if (user::$current["delete_users"] != "yes") {
            print(CANT_DELETE_USER);
            print("<a href='" . $link . "'>" . BACK . "</a>");
            block_end();
            stdfoot();
            exit();
        }

        $ret = $db->query("SELECT users_level.id_level FROM users_level INNER JOIN users ON users.id_level = users_level.id WHERE username = '" . $db->real_escape_string($_POST["user"]) . "'");
        $row = @$ret->fetch_array();
        if ($row && $row["id_level"] > user::$current["id_level"]) {
            // impossible to delete higher levels
            print(ERR_NOT_AUTH);
            print("<a href='" . $link . "'>" . BACK . "</a>");
            block_end();
            stdfoot();
            exit();
        }

        @$db->query("DELETE FROM users WHERE username = '" . $db->real_escape_string($_POST["user"]) . "'");
        write_log("Deleted user " . $db->real_escape_string($_POST["user"]), "delete");
        print("<script language='javascript'>window.location.href='" . $link . "'</script>");
        block_end();
        stdfoot();
        exit();
    } elseif ($_POST["elimina"] == FRM_CANCEL)
        print("<script language='javascript'>window.location.href='" . $link . "'</script>");
    
    if (!isset($_POST["conferma"]))
        $_POST["conferma"] = "";

    if ($_POST["conferma"]) {
        if ($act == "signup") {
            $ret = aggiungiutente();
            if ($ret == 0) {
                if ($VALIDATION == "user") {
                    print("<div align='center'><br /><table border='0' width='500' cellspacing='0' cellpadding='0'><tr>\n");
                    print("<td bgcolor='#FFFFFF' align='center' style='border-style: dotted; border-width: 1px' bordercolor='#CC0000'>\n");
                    print("<br /><font color='#FF0000'><b>" . ACCOUNT_CREATED . "</b><br /><br />" . EMAIL_SENT . "</font><br /><br /></td>\n");
                    print("</tr></table></div><br />\n");
                    block_end();
                    stdfoot();
                    exit();
                } else if ($VALIDATION == "none") {
                    print("<div align='center'><br /><table border='0' width='500' cellspacing='0' cellpadding='0'><tr>\n");
                    print("<td bgcolor='#FFFFFF' align='center' style='border-style: dotted; border-width: 1px' bordercolor='#CC0000'>\n");
                    print("<br /><font color='#FF0000'><b>" . ACCOUNT_CREATED . "</b><br /><br />" . ACCOUNT_CONGRATULATIONS . "</font><br /><br /></td>\n");
                    print("</tr></table></div><br />\n");
                    block_end();
                    stdfoot();
                    exit();
                } else {
                    print("<div align='center'><br /><table border='0' width='500' cellspacing='0' cellpadding='0'><tr>\n");
                    print("<td bgcolor='#FFFFFF' align='center' style='border-style: dotted; border-width: 1px' bordercolor='#CC0000'>\n");
                    print("<br /><font color='#FF0000'><b>" . ACCOUNT_CREATED . "</b><br /><br />" . WAIT_ADMIN_VALID . "</font><br /><br /></td>\n");
                    print("</tr></table></div><br />\n");
                    block_end();
                    stdfoot();
                    exit();
                }
            } elseif ($ret == -1)
                err_msg(ERROR, ERR_MISSING_DATA);
            elseif ($ret == -2)
                err_msg(ERROR, ERR_EMAIL_ALREADY_EXISTS);
            elseif ($ret == -3)
                err_msg(ERROR, "Invalid Email!");
            elseif ($ret == -7)
                err_msg(ERROR, "<font color='black'>" . ERR_NO_SPACE . "<strong><font color='red'>" . preg_replace('/\ /', '_', $db->real_escape_string($_POST["user"])) . "</strong></font></font><br />");
            elseif ($ret == -8)
                err_msg(ERROR, ERR_SPECIAL_CHAR);
            elseif ($ret == -9)
                err_msg(ERROR, ERR_PASS_LENGTH);
            else
                err_msg(ERROR, ERR_USER_ALREADY_EXISTS);
            
            block_end();
            stdfoot();
            exit();
        } elseif ($act == "mod" && user::$current['edit_users'] == "yes" && user::$current["uid"] > 1) {
            $ret = $db->query("SELECT users.*, users_level.id_level AS idlevel FROM users INNER JOIN users_level ON users.id_level = users_level.id WHERE username = '" . $db->real_escape_string($_POST["user"]) . "'");
            $row = @$ret->fetch_array(MYSQLI_BOTH);

            if ($row && $row["idlevel"] > user::$current["id_level"] && user::$current["uid"] != $row["id"]) {
                // impossible to edit higher levels
                print(ERR_NOT_AUTH);
                print("<br />\n<a href='" . $link . "'>" . BACK . "</a>");
                block_end();
                stdfoot();
                exit();
            }

            modificautente();
            print("<script language='javascript'>window.location.href='" . $link . "'</script>");
            block_end();
            stdfoot();
            exit();
        }
    }
    
    if ($id != 0) {
        $res = $db->query("SELECT users.*, users_level.id_level AS idlevel, users_level.level FROM users INNER JOIN users_level ON users.id_level = users_level.id WHERE users.id = " . $id);
        $num = $res->num_rows;

        if ($num = 0)
            print("<p><center>" . ERROR . " " . USER_NOT_FOUND . "</center></p>");
        else {
            $row = $res->fetch_array(MYSQLI_BOTH);
            // prevent editing users account if current user's level < edited account
            if ($row && $row["idlevel"] > user::$current["id_level"] && user::$current["uid"] != $row["id"]) {
                // impossible to edit higher levels
                print(ERR_NOT_AUTH);
                print("<br /><a href='" . $link . "'>\n" . BACK . "</a>");
                block_end();
                stdfoot();
                exit();
            } elseif ($row && $row["id"] == user::$current["uid"]) {
                // try to edit own account???
                print("Use your own panel to change your account details!");
                print("<br /><a href='" . $link . "'>\n" . BACK . "</a>");
                block_end();
                stdfoot();
                exit();
            } else
                tabella($act, $row);
        }
    } else {
        tabella($act);
    }
    
    print("<center><a href='javascript: history.go(-1);'>" . BACK . "</a></center>");
} else {
    if ($_POST["conferma"]) {
        if ($act == "signup") {
            $ret = aggiungiutente();
            if ($ret == 0) {
                if ($VALIDATION == "user") {
                    print("<div align='center'><br /><table border='0' width='500' cellspacing='0' cellpadding='0'><tr>\n");
                    print("<td bgcolor='#FFFFFF' align='center' style='border-style: dotted; border-width: 1px' bordercolor='#CC0000'>\n");
                    print("<br /><font color='#FF0000'><b>" . ACCOUNT_CREATED . "</b><br /><br />" . EMAIL_SENT . "</font><br /><br /></td>\n");
                    print("</tr></table></div><br />\n");
                    block_end();
                    stdfoot();
                    exit();
                } else if ($VALIDATION == "none") {
                    print("<div align='center'><br /><table border='0' width='500' cellspacing='0' cellpadding='0'><tr>\n");
                    print("<td bgcolor='#FFFFFF' align='center' style='border-style: dotted; border-width: 1px' bordercolor='#CC0000'>\n");
                    print("<br /><font color='#FF0000'><b>" . ACCOUNT_CREATED . "</b><br /><br />" . ACCOUNT_CONGRATULATIONS . "</font><br /><br /></td>\n");
                    print("</tr></table></div><br />\n");
                    block_end();
                    stdfoot();
                    exit();
                } else {
                    print("<div align='center'><br /><table border='0' width='500' cellspacing='0' cellpadding='0'><tr>\n");
                    print("<td bgcolor='#FFFFFF' align='center' style='border-style: dotted; border-width: 1px' bordercolor='#CC0000'>\n");
                    print("<br /><font color='#FF0000'><b>" . ACCOUNT_CREATED . "</b><br /><br />" . WAIT_ADMIN_VALID . "</font><br /><br /></td>\n");
                    print("</tr></table></div><br />\n");
                    block_end();
                    stdfoot();
                    exit();
                }
            } elseif ($ret == -1)
                err_msg(ERROR, ERR_MISSING_DATA);
            elseif ($ret == -2)
                err_msg(ERROR, ERR_EMAIL_ALREADY_EXISTS);
            elseif ($ret == -3)
                err_msg(ERROR, ERR_NO_EMAIL);
            elseif ($ret == -7)
                err_msg(ERROR, "<font color='black'>" . ERR_NO_SPACE . "<strong><font color='red'>" . preg_replace('/\ /', '_', $db->real_escape_string($_POST["user"])) . "</strong></font></font><br />");
            elseif ($ret == -8)
                err_msg(ERROR, ERR_SPECIAL_CHAR);
            elseif ($ret == -9)
                err_msg(ERROR, ERR_PASS_LENGTH);
            else
                err_msg(ERROR, ERR_USER_ALREADY_EXISTS);
        }
    } elseif ($act == "mod" && user::$current["uid"] != $id)
        err_msg(ERROR, NOT_AUTH);
    else
        tabella($act);
    
}

function tabella($action, $dati = array())
{
    global $idflag, $link, $idlangue, $idstyle, $db, $USE_IMAGECODE;
    
    ?>
    <center>
    <p>
	
    <form name="utente" method="post" OnSubmit="return FormControl('<?php echo $action; ?>')" action="<?php echo htmlentities(urldecode($_SERVER['PHP_SELF'])) . "?act=" . $action . "&returnto=" . urlencode($link); ?>">
    <input type="hidden" name="act" value="<?php echo $action; ?>" />
    <input type="hidden" name="uid" value="<?php echo (int)$dati["id"]; ?>" />
    <input type="hidden" name="returnto" value="<?php echo urlencode($link); ?> "/>
    <input type="hidden" name="language" value="<?php echo $idlangue; ?> "/>
    <input type="hidden" name="style" value="<?php echo $idstyle; ?> "/>
    <input type="hidden" name="flag" value="<?php echo $idflag; ?> "/>
    <input type="hidden" name="username" value="<?php echo security::html_safe($dati["username"]); ?>"/>
    <table width="60%" border="0" class="lista">
    <tr>
    <td align="left" class="header"><?php echo USER_NAME; ?>: </td>
    <td align="left" class="lista">
    <?php
    if ($action == "mod" || $action == "del")
        print("\n<input type='text' size='40' name='user' value='" . security::html_safe(unesc($dati['username'])) . "' " . ($action == "mod" ? "" : "readonly") . " />");
    else
        print("\n<input type='text' size='40' name='user' />");
    ?>
    </td>
    </tr>
    <?php
    if ((user::$current["uid"] == $dati["id"] && $action == "mod") || $action == "signup" || (user::$current["edit_users"] == "yes" && $action == "mod")) {
    ?>
    <tr>
    <td align="left" class="header"><?php echo USER_PWD; ?>:</td>
    <td align="left" class="lista"><input type="password" size="40" name="pwd" /></td>
    </tr>
    <tr>
    <td align="left" class="header"><?php echo USER_PWD_AGAIN; ?>:</td>
    <td align="left" class="lista"><input type="password" size="40" name="pwd1" /></td>
    </tr>
    <tr>
    <td align="left" class="header"><?php echo USER_EMAIL; ?>:</td>
    <td align="left" class="lista"><input type="text" size="30" name="email" value="<?php if ($action == "mod") echo security::html_safe($dati['email']); ?>"/></td>
    </tr>
    <?php

        $lres = language_list();
        print("<tr>\n\t<td align='left' class='header'>" . USER_LANGUE . ":</td>");
        print("\n\t<td align='left' class='lista'><select name='language'>");
        foreach ($lres as $langue) {
            $option = "\n<option ";
            if ($langue["id"] == $dati["language"])
                $option .= "selected='selected' ";
            $option .= "value='" . (int)$langue["id"] . "'>" . security::html_safe($langue["language"]) . "</option>";
            print($option);
        }
        print("</select></td>\n</tr>");
        
        $sres = style_list();
        print("<tr>\n\t<td align='left' class='header'>" . USER_STYLE . ":</td>");
        print("\n\t<td align='left' class='lista'><select name='style'>");
        foreach ($sres as $style) {
            $option = "\n<option ";
            if ($style["id"] == $dati["style"])
                $option .= "selected='selected' ";
            $option .= "value='" . (int)$style["id"] . "'>" . security::html_safe($style["style"]) . "</option>";
            print($option);
        }
        print("</select></td>\n</tr>");
        $fres = flag_list();
        
        print("<tr>\n\t<td align='left' class='header'>" . PEER_COUNTRY . ":</td>");
        print("\n\t<td align='left' class='lista'><select name='flag'>\n<option value='0'>---</option>");
        
        $thisip    = vars::$realip;
        $remotedns = gethostbyaddr($thisip);
        
        if ($remotedns != $thisip) {
            $remotedns = utf8::strtoupper($remotedns);
            preg_match('/^(.+)\.([A-Z]{2,3})$/', $remotedns, $tldm);

            if (isset($tldm[2]))
                $remotedns = $db->real_escape_string($tldm[2]);
        }
        
        foreach ($fres as $flag) {
            $option = "\n<option ";
            if ($flag["id"] == $dati["flag"] || ($flag["domain"] == $remotedns && $action == "signup"))
                $option .= "selected='selected' ";
            $option .= "value='" . (int)$flag["id"] . "'>" . security::html_safe($flag["name"]) . "</option>";
            print($option);
        }
        print("</select></td>\n</tr>");
        
        $zone     = date('Z', vars::$timestamp);
        $daylight = date('I', vars::$timestamp) * 3600;
        $os       = $zone - $daylight;

        if ($os != 0) {
            $timeoff = $os / 3600;
        } else {
            $timeoff = 0;
        }
        
        if (!user::$current || user::$current["uid"] == 1)
            $dati["time_offset"] = $timeoff;
        
        $tres = timezone_list();
        print("<tr>\n\t<td align='left' class='header'>" . TIMEZONE . ":</td>");
        print("\n\t<td align='left' class='lista' colspan='2'>\n<select name='timezone'>");
        foreach ($tres as $timezone) {
            $option = "\n<option ";
            if ($timezone["difference"] == $dati["time_offset"])
                $option .= "selected='selected' ";
            $option .= "value='" . $timezone["difference"] . "'>" . security::html_safe(unesc($timezone["timezone"])) . "</option>";
            print($option);
        }
        print("</select></td>\n</tr>");
        
        // -----------------------------
        // Captcha hack
        // -----------------------------
        // if set to use secure code: try to display imagecode
        if (user::$current['edit_users'] == 'yes' && $action == "mod" && user::$current["uid"] != $dati["id"]) {
            print("<tr>\n\t<td align='left' class='header'>" . USER_LEVEL . ":</td><td align='left' class='lista'>");
            print("<select name='level'>");

            $res = $db->query("SELECT level FROM users_level WHERE id_level <= " . user::$current["id_level"] . " ORDER BY id_level");
            while ($row = $res->fetch_array(MYSQLI_BOTH)) {
                $select = "<option value='" . unesc($row["level"]) . "'";
                if (unesc($dati["level"]) == unesc($row["level"]))
                    $select .= "selected='selected'";
                $select .= ">" . security::html_safe(unesc($row["level"])) . "</option>\n";
                print $select;
            }
            print("</select></td></tr>");
        } elseif ($USE_IMAGECODE && $action != "mod") {
            if (extension_loaded('gd')) {
                $arr = gd_info();
                if ($arr['FreeType Support'] == 1) {
                    $p = new ocr_captcha();
                    
                    print("<tr>\n\t<td align='left' class='header'>" . IMAGE_CODE . ":</td>");
                    print("\n\t<td align='left' class='lista'><input type='text' name='private_key' value='' maxlength='6' size='6'>\n");
                    print($p->display_captcha(true));
                    $private = $p->generate_private();
                    print("</td>\n</tr>");
                }
            }
        }
        // -----------------------------
        // Captcha hack
        // -----------------------------
    }
    
    ?>
    <tr>
    <td align="center" class="header"></td>
    <?php

    if ($action == "del")
        print("\n<td align='left' class='lista'><input type='submit' name='elimina' value='" . FRM_DELETE . "' />&nbsp;&nbsp;&nbsp;&nbsp;<input type='submit' name='elimina' value='" . FRM_CANCEL . "' /></td>");
    else
        print("\n<td align='left' class='lista'><input type='submit' name='conferma' value='" . FRM_CONFIRM . "' />&nbsp;&nbsp;&nbsp;&nbsp;<input type='reset' name='annulla' value='" . FRM_CANCEL . "' /></td>");
    
	?>
    </tr>
    </table>
    </form>
    </center>
    </p>
    <?php
}

function aggiungiutente()
{
    global $SITENAME, $SITEEMAIL, $db, $BASEURL, $VALIDATION, $USERLANG, $USE_IMAGECODE;
    
    $utente   = $db->real_escape_string($_POST["user"]);
    $pwd      = $db->real_escape_string($_POST["pwd"]);
    $pwd1     = $db->real_escape_string($_POST["pwd1"]);
    $email    = $db->real_escape_string($_POST["email"]);
    $idlangue = intval($_POST["language"]);
    $idstyle  = intval($_POST["style"]);
    $idflag   = intval($_POST["flag"]);
    $timezone = intval($_POST["timezone"]);
    
    if (utf8::strtoupper($utente) == utf8::strtoupper("Guest")) {
        print(ERROR . " " . ERR_GUEST_EXISTS . "<br />\n");
        print("<a href='account.php'>" . BACK . "</a>");
        block_end();
        stdfoot();
        exit;
    }
    
    if ($pwd != $pwd1) {
        print(ERROR . " " . DIF_PASSWORDS . "<br />\n");
        print("<a href='account.php'>" . BACK . "</a>");
        block_end();
        stdfoot();
        exit;
    }
    
    if ($VALIDATION == "none")
        $idlevel = 3;
    else
        $idlevel = 2;
    # Create Random number
    $floor   = 100000;
    $ceiling = 999999;
    srand((double) microtime() * 1000000);
    $random = mt_rand($floor, $ceiling);
    
    if ($utente == "" || $pwd == "" || $email == "") {
        return -1;
        exit;
    }
    
    $res = $db->query("SELECT email FROM users WHERE email = '" . $email . "'");
    if ($res->num_rows > 0) {
        return -2;
        exit;
    }

    if (!security::valid_email($email)) {
        return -3;
        exit;
    }
    
    // duplicate username
    $res = $db->query("SELECT username FROM users WHERE username = '" . $utente . "'");
    if ($res->num_rows > 0) {
        return -4;
        exit;
    }
    // duplicate username
    
    if (strpos($db->real_escape_string($utente), " ") == true) {
        return -7;
        exit;
    }
    if ($USE_IMAGECODE) {
        if (extension_loaded('gd')) {
            $arr = gd_info();
            if ($arr['FreeType Support'] == 1) {
                $public  = $_POST['public_key'];
                $private = $_POST['private_key'];
                
                $p = new ocr_captcha();
                
                if ($p->check_captcha($public, $private) != true) {
                    err_msg(ERROR, ERR_IMAGE_CODE);
                    block_end();
                    stdfoot();
                    exit;
                }
            }
        }
    }

    $bannedchar = array("\\", "/", ":", "*", "?", "\"", "@", "$", "'", "`", ",", ";", ".", "<", ">", "!", "£", "%", "^", "&", "(", ")", "+", "=", "#", "~");

    if (straipos($db->real_escape_string($utente), $bannedchar) == true) {
        return -8;
        exit;
    }
    
    if (utf8::strlen($db->real_escape_string($pwd)) < 4) {
        return -9;
        exit;
    }
    
    @$db->query("INSERT INTO users (username, password, random, id_level, email, style, language, flag, joined, lastconnect, pid, time_offset) VALUES ('" . $utente . "', '" . md5($pwd) . "', " . $random . ", " . $idlevel . ", '" . $email . "', " . $idstyle . ", " . $idlangue . ", " . $idflag . ", NOW(), NOW(), '" . md5(uniqid(mt_rand(), true)) . "', '" . $timezone . "')");
    
	if ($VALIDATION == "user") {
        ini_set("sendmail_from", "");
        if ($db->errno == 0) {
            mail($email, ACCOUNT_CONFIRM, ACCOUNT_MSG . "\n\n" . $BASEURL . "/account.php?act=confirm&confirm=" . $random . "&language=" . $idlangue . "", "From: " . $SITENAME . " <" . $SITEEMAIL . ">");
            write_log("Signup new User " . $utente . " (" . $email . ")", "add");
        } else
            die($db->error);
    }
    
    return $db->errno;
}

function modificautente()
{
    global $db;

    $utente  = security::html_safe($db->real_escape_string($_POST["user"]));
    $oldname = security::html_safe($db->real_escape_string($_POST["username"]));

    if (trim($utente) == "") {
        err_msg(ERROR, INSERT_USERNAME);
        block_end();
        stdfoot();
        exit;
    } elseif (utf8::strtoupper($utente) == utf8::strtoupper("Guest")) {
        err_msg(ERROR, ERR_GUEST_EXISTS . "<br />\n");
        block_end();
        stdfoot();
        exit;
    }
    
    // duplicate username
    $res = $db->query("SELECT username FROM users WHERE username = '" . $utente . "' AND id <> " . intval($_POST["uid"]));
    if ($res->num_rows > 0) {
        err_msg(ERROR, ERR_USER_ALREADY_EXISTS . "<br />\n");
        block_end();
        stdfoot();
        exit;
    }

    if (isset($_POST["pwd"]))
        $pwd = $db->real_escape_string($_POST["pwd"]);
    else
        $pwd = "";

    // now in $_POST["level"] there is the level name, we need to select the id_level to know if current user
    // is allowed to modify the requested user
    $rlev   = $db->query("SELECT id, id_level FROM users_level WHERE level = '" . $db->real_escape_string(unesc($_POST["level"])) . "'");
    $reslev = $rlev->fetch_assoc();

    if (user::$current["id_level"] >= $reslev["id_level"])
        $level = intval($reslev["id"]);
    else
        $level = 0;
    
    $idlangue = intval($_POST["language"]);
    $idstyle  = intval($_POST["style"]);
    $idflag   = intval($_POST["flag"]);
    $timezone = intval($_POST["timezone"]);
    
    if (isset($_POST["email"]))
        $email = $db->real_escape_string($_POST["email"]);
    else
        $email = "";

    $set = array();
    
    if ($email != "")
        $set[] = "email='$email'";
    if ($level > 0)
        $set[] = "id_level='$level'";
    if ($idlangue > 0)
        $set[] = "language=$idlangue";
    if ($idstyle > 0)
        $set[] = "style=$idstyle";
    if ($pwd != "")
        $set[] = "password='" . md5($pwd) . "'";
    if ($idflag > 0)
        $set[] = "flag=$idflag";
    if ($timezone >= -12)
        $set[] = "time_offset=$timezone";
    // username
    $set[] = "username='$utente'";
    
    $updateset = implode(",", $set);
    
    if ($updateset != "")
        @$db->query("UPDATE users SET " . $updateset . " WHERE username = '" . $oldname . "'");
    
    write_log("Modified User " . $utente . "", "modify");
}

block_end();
stdfoot();

?>
