<?php
require_once ("include/functions.php");
require_once ("include/config.php");

dbconn();

if (!isset($_POST["language"])) $_POST["language"] = 0;
$idlang=intval($_POST["language"]);

standardheader('Account Management',true,$idlang);

?>

<SCRIPT Language="Javascript">
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

if (isset($_GET["uid"])) $id=intval($_GET["uid"]);
 else $id="";
if (isset($_GET["returnto"])) $link=urldecode($_GET["returnto"]);
 else $link="";
if (isset($_GET["act"])) $act=$_GET["act"];
 else $act="signup";
if (isset($_GET["language"])) $idlangue=intval($_GET["language"]);
 else $idlangue="";
if (isset($_GET["style"])) $idstyle=intval($_GET["style"]);
 else $idstyle="";
if (isset($_GET["flag"])) $idflag=intval($_GET["flag"]);
 else $idflag="";

if (isset($_POST["uid"]) && isset($_POST["act"]))
  {
if (isset($_POST["uid"])) $id=intval($_POST["uid"]);
 else $id="";
if (isset($_POST["returnto"])) $link=urldecode($_POST["returnto"]);
 else $link="";
if (isset($_POST["act"])) $act=$_POST["act"];
 else $act="";
  }

print("<center>");
if ($act=="mod")
  {
   if ($CURUSER["edit_users"]!="yes" || $id==1)
      stderr(ERROR,ERR_NOT_AUTH);
   else
      block_begin(ACCOUNT_EDIT);
  }
elseif ($act=="signup" && isset($CURUSER["uid"]) && $CURUSER["uid"]!=1) {
        $url="index.php";
        redirect($url);
}
elseif ($act=="signup")
   block_begin(ACCOUNT_CREATE);
elseif ($act=="del")
  {
   if ($CURUSER["delete_users"]!="yes" || $id==1 || $CURUSER["uid"]==$id)
      stderr(ERROR,ERR_NOT_AUTH);
   else
      block_begin(ACCOUNT_DELETE);
  }
print("</center>");

$res=run_query("SELECT count(*) FROM users WHERE id>1");
$nusers=mysqli_fetch_row($res);
$numusers=$nusers[0];

if ($act=="signup" && $MAX_USERS!=0 && $numusers>=$MAX_USERS)
   {
   err_msg(ERROR,REACHED_MAX_USERS);
   block_end();
   stdfoot();
   exit();
}

if ($act=="confirm") {
      $random=intval($_GET["confirm"]);
      $res=run_query("UPDATE users SET id_level=3 WHERE id_level=2 AND random=$random");
      if (!$res)
         die("ERROR: " . ((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)) . "\n");
      else {
          block_begin(ACCOUNT_CREATE);
          print("<tr><td align=\"center\">".ACCOUNT_CONGRATULATIONS."</td></tr>");
          block_end();
          stdfoot();
          exit;
          }
}

if ($CURUSER["edit_users"]=="yes") {

if (!isset($_POST["elimina"])) $_POST["elimina"] = "";
if ($_POST["elimina"]==FRM_DELETE) {
   if ($CURUSER["delete_users"]!="yes") {
      print(CANT_DELETE_USER);
      print("<a href=$link>".BACK."</a>");
      block_end();
      stdfoot();
      exit();
      }
   $ret=run_query("SELECT users_level.id_level FROM users_level INNER JOIN users ON users.id_level=users_level.id WHERE username='".((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $_POST["user"]) : ((trigger_error("Error...", E_USER_ERROR)) ? "" : ""))."'");
   $row=@mysqli_fetch_array($ret);
   if ($row && $row["id_level"]>$CURUSER["id_level"]) {
    // impossible to delete higher levels
      print(ERR_NOT_AUTH);
      print(" <a href=$link>".BACK."</a>");
      block_end();
      stdfoot();
      exit();
   }
   @run_query("DELETE FROM users WHERE username='".((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $_POST["user"]) : ((trigger_error("Error...", E_USER_ERROR)) ? "" : ""))."'");
   write_log("Deleted user ".((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $_POST["user"]) : ((trigger_error("Error...", E_USER_ERROR)) ? "" : "")),"delete");
   print("<script LANGUAGE=\"javascript\">window.location.href=\"$link\"</script>");
   block_end();
   stdfoot();
   exit();
}
elseif ($_POST["elimina"]==FRM_CANCEL)
       print("<script LANGUAGE=\"javascript\">window.location.href=\"$link\"</script>");

if (!isset($_POST["conferma"])) $_POST["conferma"] = "";
if ($_POST["conferma"]) {
   if ($act=="signup") {
      $ret=aggiungiutente();
      if ($ret==0)
         {
             if ($VALIDATION=="user")
                {
                  print("<div align=\"center\"><br /><table border=\"0\" width=\"500\" cellspacing=\"0\" cellpadding=\"0\"><tr>\n");
                  print("<td bgcolor=\"#FFFFFF\" align=\"center\" style=\"border-style: dotted; border-width: 1px\" bordercolor=\"#CC0000\">\n");
                  print("<br /><font color=\"#FF0000\"><b>".ACCOUNT_CREATED."</b><br /><br />".EMAIL_SENT."</font><br /><br /></td>\n");
                  print("</tr></table></div><br />\n");
                  block_end();
                  stdfoot();
                  exit();
                }
             else if ($VALIDATION=="none")
                  {
                  print("<div align=\"center\"><br /><table border=\"0\" width=\"500\" cellspacing=\"0\" cellpadding=\"0\"><tr>\n");
                  print("<td bgcolor=\"#FFFFFF\" align=\"center\" style=\"border-style: dotted; border-width: 1px\" bordercolor=\"#CC0000\">\n");
                  print("<br /><font color=\"#FF0000\"><b>".ACCOUNT_CREATED."</b><br /><br />".ACCOUNT_CONGRATULATIONS."</font><br /><br /></td>\n");
                  print("</tr></table></div><br />\n");
                  block_end();
                  stdfoot();
                  exit();
                  }
             else
                 {
                  print("<div align=\"center\"><br /><table border=\"0\" width=\"500\" cellspacing=\"0\" cellpadding=\"0\"><tr>\n");
                  print("<td bgcolor=\"#FFFFFF\" align=\"center\" style=\"border-style: dotted; border-width: 1px\" bordercolor=\"#CC0000\">\n");
                  print("<br /><font color=\"#FF0000\"><b>".ACCOUNT_CREATED."</b><br /><br />".WAIT_ADMIN_VALID."</font><br /><br /></td>\n");
                  print("</tr></table></div><br />\n");
                  block_end();
                  stdfoot();
                  exit();
                 }
         }
      elseif ($ret==-1)
        err_msg(ERROR,ERR_MISSING_DATA);
      elseif ($ret==-2)
        err_msg(ERROR,ERR_EMAIL_ALREADY_EXISTS);
      elseif ($ret==-3)
        err_msg(ERROR,"Invalid Email!"); // valid email check - by vibes
      elseif ($ret==-7)
        err_msg(ERROR,"<font color=\"black\">".ERR_NO_SPACE."<strong><font color=\"red\">".preg_replace('/\ /', '_', ((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $_POST["user"]) : ((trigger_error("Error...", E_USER_ERROR)) ? "" : "")))."</strong></font></font><br />");
      elseif ($ret==-8)
        err_msg(ERROR,ERR_SPECIAL_CHAR);
      elseif ($ret==-9)
        err_msg(ERROR,ERR_PASS_LENGTH);
      else
        err_msg(ERROR,ERR_USER_ALREADY_EXISTS);

       block_end();
       stdfoot();
       exit();
      }
elseif ($act=="mod" && $CURUSER['edit_users'] == "yes" && $CURUSER["uid"] > 1) {
  $ret=run_query("SELECT users.*, users_level.id_level as idlevel FROM users INNER JOIN users_level ON users.id_level=users_level.id WHERE username='".((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $_POST["user"]) : ((trigger_error("Error...", E_USER_ERROR)) ? "" : ""))."'");
  $row=@mysqli_fetch_array($ret);
  if ($row && $row["idlevel"] > $CURUSER["id_level"] && $CURUSER["uid"]!=$row["id"]){
   // impossible to edit higher levels
      print(ERR_NOT_AUTH);
      print("<br />\n <a href=$link>".BACK."</a>");
      block_end();
      stdfoot();
      exit();
  }
       modificautente();
       print("<script LANGUAGE=\"javascript\">window.location.href=\"$link\"</script>");
       block_end();
       stdfoot();
       exit();
       }
}

if ($id!=0) {
   $res=run_query("SELECT users.*, users_level.id_level as idlevel, users_level.level FROM users INNER JOIN users_level ON users.id_level=users_level.id WHERE users.id=$id");
   $num=mysqli_num_rows($res);
   if ($num=0)
      print("<p><center>".ERROR." ".USER_NOT_FOUND."</center></p>");
   else {
        $row=mysqli_fetch_array($res);
        // prevent editing users account if current user's level < edited account
        if ($row && $row["idlevel"] > $CURUSER["id_level"] && $CURUSER["uid"]!=$row["id"]){
         // impossible to edit higher levels
            print(ERR_NOT_AUTH);
            print("<br /> <a href=$link>\n".BACK."</a>");
            block_end();
            stdfoot();
            exit();
        }
        elseif ($row && $row["id"]==$CURUSER["uid"])
            {
            // try to edit own account???
            print("Use your panel to change your account details!");
            print("<br /> <a href=$link>\n".BACK."</a>");
            block_end();
            stdfoot();
            exit();
        }
        else
            tabella($act,$row);
      }
}
else {
 tabella($act);
 }

  print("<center><a href=\"javascript: history.go(-1);\">".BACK."</a></center>");
  }
else {
     if ($_POST["conferma"]) {
        if ($act=="signup") {
           $ret=aggiungiutente();
           if ($ret==0)
              {
              if ($VALIDATION=="user")
                 {
                   print("<div align=\"center\"><br /><table border=\"0\" width=\"500\" cellspacing=\"0\" cellpadding=\"0\"><tr>\n");
                   print("<td bgcolor=\"#FFFFFF\" align=\"center\" style=\"border-style: dotted; border-width: 1px\" bordercolor=\"#CC0000\">\n");
                   print("<br /><font color=\"#FF0000\"><b>".ACCOUNT_CREATED."</b><br /><br />".EMAIL_SENT."</font><br /><br /></td>\n");
                   print("</tr></table></div><br />\n");
                   block_end();
                   stdfoot();
                   exit();
                 }
              else if ($VALIDATION=="none")
                   {
                   print("<div align=\"center\"><br /><table border=\"0\" width=\"500\" cellspacing=\"0\" cellpadding=\"0\"><tr>\n");
                   print("<td bgcolor=\"#FFFFFF\" align=\"center\" style=\"border-style: dotted; border-width: 1px\" bordercolor=\"#CC0000\">\n");
                   print("<br /><font color=\"#FF0000\"><b>".ACCOUNT_CREATED."</b><br /><br />".ACCOUNT_CONGRATULATIONS."</font><br /><br /></td>\n");
                   print("</tr></table></div><br />\n");
                   block_end();
                   stdfoot();
                   exit();
                   }
              else
                  {
                   print("<div align=\"center\"><br /><table border=\"0\" width=\"500\" cellspacing=\"0\" cellpadding=\"0\"><tr>\n");
                   print("<td bgcolor=\"#FFFFFF\" align=\"center\" style=\"border-style: dotted; border-width: 1px\" bordercolor=\"#CC0000\">\n");
                   print("<br /><font color=\"#FF0000\"><b>".ACCOUNT_CREATED."</b><br /><br />".WAIT_ADMIN_VALID."</font><br /><br /></td>\n");
                   print("</tr></table></div><br />\n");
                   block_end();
                   stdfoot();
                   exit();
                  }
              }
           elseif ($ret==-1)
             err_msg(ERROR,ERR_MISSING_DATA);
           elseif ($ret==-2)
             err_msg(ERROR,ERR_EMAIL_ALREADY_EXISTS);
           elseif ($ret==-3)
             err_msg(ERROR,ERR_NO_EMAIL);
           elseif ($ret==-7)
             err_msg(ERROR,"<font color=\"black\">".ERR_NO_SPACE."<strong><font color=\"red\">".preg_replace('/\ /', '_', ((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $_POST["user"]) : ((trigger_error("Error...", E_USER_ERROR)) ? "" : "")))."</strong></font></font><br />");
           elseif ($ret==-8)
             err_msg(ERROR,ERR_SPECIAL_CHAR);
           elseif ($ret==-9)
             err_msg(ERROR,ERR_PASS_LENGTH);
           else
            err_msg(ERROR,ERR_USER_ALREADY_EXISTS);
           }
        }
      elseif ($act=="mod" && $CURUSER["uid"]!=$id)
             err_msg(ERROR,NOT_AUTH);
      else
          tabella($act);

}

function tabella($action,$dati=array()) {

global $idflag,$link, $idlangue, $idstyle, $CURUSER,$USE_IMAGECODE;


?>
<center>
<p>

<form name="utente" method="post" OnSubmit="return FormControl('<?php echo $action; ?>')" action="<?php echo htmlentities(urldecode($_SERVER['PHP_SELF'])) ."?act=$action&returnto=".urlencode($link); ?>">
<input type="hidden" name="act" value="<?php echo $action ?>" />
<input type="hidden" name="uid" value="<?php echo $dati["id"] ?>" />
<input type="hidden" name="returnto" value="<?php echo urlencode($link) ?> "/>
<input type="hidden" name="language" value="<?php echo $idlangue ?> "/>
<input type="hidden" name="style" value="<?php echo $idstyle ?> "/>
<input type="hidden" name="flag" value="<?php echo $idflag ?> "/>
<input type="hidden" name="username" value="<?php echo $dati["username"] ?>"/>
<table width="60%" border="0" class="lista">
<tr>
   <td align=left class="header"><?php echo USER_NAME ?>: </td>
   <td align="left" class="lista">
   <?php
   if ($action=="mod" || $action=="del")
      print("\n<input type=\"text\" size=\"40\" name=\"user\" value=\"".unesc($dati['username'])."\" ".($action=="mod"?"":"readonly")." />");
   else
       print("\n<input type=\"text\" size=\"40\" name=\"user\" />");
   ?>
   </td>
</tr>
<?php
if (($CURUSER["uid"]==$dati["id"] && $action=="mod") || $action=="signup" || ($CURUSER["edit_users"]=="yes" && $action=="mod"))
   {
   ?>
<tr>
   <td align=left class="header"><?php echo USER_PWD?>:</td>
   <td align="left" class="lista"><input type="password" size="40" name="pwd" /></td>
</tr>
<tr>
   <td align=left class="header"><?php echo USER_PWD_AGAIN?>:</td>
   <td align="left" class="lista"><input type="password" size="40" name="pwd1" /></td>
</tr>
<tr>
   <td align=left class="header"><?php echo USER_EMAIL?>:</td>
   <td align="left" class="lista"><input type="text" size="30" name="email" value="<?php if ($action=="mod") echo $dati['email']; ?>"/></td>
</tr>
   <?php
   $lres=language_list();
   print("<tr>\n\t<td align=left class=\"header\">".USER_LANGUE.":</td>");
   print("\n\t<td align=\"left\" class=\"lista\"><select name=language>");
   foreach($lres as $langue)
     {
       $option="\n<option ";
       if ($langue["id"]==$dati["language"])
          $option.="selected=selected ";
       $option.="value=".$langue["id"].">".$langue["language"]."</option>";
       print($option);
     }
   print("</select></td>\n</tr>");

   $sres=style_list();
   print("<tr>\n\t<td align=left class=\"header\">".USER_STYLE.":</td>");
   print("\n\t<td align=\"left\" class=\"lista\"><select name=style>");
   foreach($sres as $style)
     {
       $option="\n<option ";
       if ($style["id"]==$dati["style"])
          $option.="selected=selected ";
       $option.="value=".$style["id"].">".$style["style"]."</option>";
       print($option);
     }
   print("</select></td>\n</tr>");
   $fres=flag_list();

   print("<tr>\n\t<td align=left class=\"header\">".PEER_COUNTRY.":</td>");
   print("\n\t<td align=left class=\"lista\"><select name=flag>\n<option value='0'>---</option>");

   $thisip = $_SERVER["REMOTE_ADDR"];
   $remotedns = gethostbyaddr($thisip);

   if ($remotedns != $thisip)
       {
       $remotedns = strtoupper($remotedns);
       preg_match('/^(.+)\.([A-Z]{2,3})$/', $remotedns, $tldm);
       if (isset($tldm[2]))
              $remotedns = ((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $tldm[2]) : ((trigger_error("Error...", E_USER_ERROR)) ? "" : ""));
     }

   foreach($fres as $flag)
    {
        $option="\n<option ";
            if ($flag["id"]==$dati["flag"] || ($flag["domain"]==$remotedns && $action=="signup"))
              $option.="selected=selected ";
            $option.="value='".$flag["id"]."'>".$flag["name"]."</option>";
         print($option);
    }
   print("</select></td>\n</tr>");

           $zone=date('Z',time());
           $daylight=date('I',time())*3600;
           $os=$zone-$daylight;
           if($os!=0){ $timeoff=$os/3600; } else { $timeoff=0; }

           if(!$CURUSER || $CURUSER["uid"]==1)
              $dati["time_offset"]=$timeoff;

           $tres=timezone_list();
           print("<tr>\n\t<td align=left class=\"header\">".TIMEZONE.":</td>");
           print("\n\t<td align=\"left\" class=\"lista\" colspan=\"2\">\n<select name=\"timezone\">");
           foreach($tres as $timezone)
             {
               $option="\n<option ";
               if ($timezone["difference"]==$dati["time_offset"])
                  $option.="selected=selected ";
               $option.="value=".$timezone["difference"].">".unesc($timezone["timezone"])."</option>";
               print($option);
             }
           print("</select></td>\n</tr>");

// -----------------------------
// Captcha hack
// -----------------------------
// if set to use secure code: try to display imagecode

if ($CURUSER['edit_users']=='yes' && $action=="mod" && $CURUSER["uid"]!=$dati["id"]) {
   print("<tr>\n\t<td align=left class=\"header\">". USER_LEVEL .":</td><td align=\"left\" class=\"lista\">");
   print("<select name=\"level\">");
   $res=run_query("SELECT level FROM users_level WHERE id_level<=".$CURUSER["id_level"]." ORDER BY id_level");
   while($row=mysqli_fetch_array($res))
   {
       $select="<option value='".unesc($row["level"])."'";
       if (unesc($dati["level"])==unesc($row["level"]))
          $select.="selected=\"selected\"";
       $select.=">".unesc($row["level"])."</option>\n";
       print $select;
   }
   print("</select></td></tr>");
}
elseif ($USE_IMAGECODE && $action!="mod")
  {
   if (extension_loaded('gd'))
     {
       $arr = gd_info();
       if ($arr['FreeType Support']==1)
        {
         $p=new ocr_captcha();

         print("<tr>\n\t<td align=left class=\"header\">".IMAGE_CODE.":</td>");
         print("\n\t<td align=left class=\"lista\"><input type=text name=private_key value='' maxlength=6 size=6>\n");
         print($p->display_captcha(true));
         $private=$p->generate_private();
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
   <td align=center class="header"></td>
<?php
if ($action=="del")
   print("\n<td align=left class=lista><input type=\"submit\" name=\"elimina\" value=\"".FRM_DELETE."\" />&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"submit\" name=\"elimina\" value=\"".FRM_CANCEL."\" /></td>");
else
   print("\n<td align=left class=lista><input type=\"submit\" name=\"conferma\" value=\"".FRM_CONFIRM."\" />&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"reset\" name=\"annulla\" value=\"".FRM_CANCEL."\" /></td>");
?>
</tr>
</table>
</form>
</center>
</p>
<?php
}

function aggiungiutente() {

global $SITENAME,$SITEEMAIL,$BASEURL,$VALIDATION,$USERLANG,$USE_IMAGECODE;

$utente=((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $_POST["user"]) : ((trigger_error("Error...", E_USER_ERROR)) ? "" : ""));
$pwd=((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $_POST["pwd"]) : ((trigger_error("Error...", E_USER_ERROR)) ? "" : ""));
$pwd1=((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $_POST["pwd1"]) : ((trigger_error("Error...", E_USER_ERROR)) ? "" : ""));
$email=((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $_POST["email"]) : ((trigger_error("Error...", E_USER_ERROR)) ? "" : ""));
$idlangue=intval($_POST["language"]);
$idstyle=intval($_POST["style"]);
$idflag=intval($_POST["flag"]);
$timezone=intval($_POST["timezone"]);

if (strtoupper($utente) == strtoupper("Guest")) {
        print(ERROR." ".ERR_GUEST_EXISTS."<br />\n");
        print("<a href=account.php>".BACK."</a>");
        block_end();
        stdfoot();
        exit;
        }

if ($pwd != $pwd1) {
    print(ERROR." ".DIF_PASSWORDS."<br />\n");
    print("<a href=account.php>".BACK."</a>");
    block_end();
    stdfoot();
    exit;
    }

if ($VALIDATION=="none")
   $idlevel=3;
else
   $idlevel=2;
# Create Random number
$floor = 100000;
$ceiling = 999999;
srand((double)microtime()*1000000);
$random = mt_rand($floor, $ceiling);

if ($utente=="" || $pwd=="" || $email=="") {
   return -1;
   exit;
}

$res=run_query("SELECT email FROM users WHERE email='$email'");
if (mysqli_num_rows($res)>0)
   {
   return -2;
   exit;
}
// valid email check - by vibes
$regex = "/^[_+a-z0-9-]+(\.[_+a-z0-9-]+)*"
                ."@[a-z0-9-]+(\.[a-z0-9-]{1,})*"
                ."\.([a-z]{2,}){1}$/";
if(!preg_match($regex,$email))
   {
   return -3;
   exit;
}
// valid email check end

// duplicate username
$res=run_query("SELECT username FROM users WHERE username='$utente'");
if (mysqli_num_rows($res)>0)
   {
   return -4;
   exit;
}
// duplicate username

if (strpos(((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $utente) : ((trigger_error("Error...", E_USER_ERROR)) ? "" : "")), " ")==true)
   {
   return -7;
   exit;
}
if ($USE_IMAGECODE)
{
  if (extension_loaded('gd'))
    {
     $arr = gd_info();
     if ($arr['FreeType Support']==1)
      {
        $public=$_POST['public_key'];
        $private=$_POST['private_key'];

          $p=new ocr_captcha();

          if ($p->check_captcha($public,$private) != true)
              {
              err_msg(ERROR,ERR_IMAGE_CODE);
              block_end();
              stdfoot();
              exit;
          }
       }
    }
}
$bannedchar=array("\\", "/", ":", "*", "?", "\"", "@", "$", "'", "`", ",", ";", ".", "<", ">", "!", "£", "%", "^", "&", "(", ")", "+", "=", "#", "~");
if (straipos(((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $utente) : ((trigger_error("Error...", E_USER_ERROR)) ? "" : "")), $bannedchar)==true)
   {
   return -8;
   exit;
}

if(strlen(((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $pwd) : ((trigger_error("Error...", E_USER_ERROR)) ? "" : "")))<4)
   {
   return -9;
   exit;
}

@run_query("INSERT INTO users (username, password, random, id_level, email, style, language, flag, joined, lastconnect, pid, time_offset) VALUES ('$utente', '" . md5($pwd) . "', $random, $idlevel, '$email', $idstyle, $idlangue, $idflag, NOW(), NOW(),'".md5(uniqid(mt_rand(),true))."', '".$timezone."')");
if ($VALIDATION=="user")
   {
   ini_set("sendmail_from","");
   if (((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_errno($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_errno()) ? $___mysqli_res : false))==0)
     {
      mail($email,ACCOUNT_CONFIRM,ACCOUNT_MSG."\n\n".$BASEURL."/account.php?act=confirm&confirm=$random&language=$idlangue","From: $SITENAME <$SITEEMAIL>");
      write_log("Signup new user $utente ($email)","add");
      }
   else
       DIE(((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)));
   }

return ((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_errno($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_errno()) ? $___mysqli_res : false));
}

function modificautente() {

$utente=htmlsafechars(((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $_POST["user"]) : ((trigger_error("Error...", E_USER_ERROR)) ? "" : "")));
$oldname=htmlsafechars(((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $_POST["username"]) : ((trigger_error("Error...", E_USER_ERROR)) ? "" : "")));
if (trim($utente)=="")
  {
    err_msg(ERROR,INSERT_USERNAME);
    block_end();
    stdfoot();
    exit;
}
elseif (strtoupper($utente) == strtoupper("Guest")) {
        err_msg(ERROR,ERR_GUEST_EXISTS."<br />\n");
        block_end();
        stdfoot();
        exit;
        }

// duplicate username
$res=run_query("SELECT username FROM users WHERE username='$utente' AND id<>".intval($_POST["uid"]));
if (mysqli_num_rows($res)>0)
   {
        err_msg(ERROR,ERR_USER_ALREADY_EXISTS."<br />\n");
        block_end();
        stdfoot();
        exit;
}
if (isset ($_POST["pwd"])) $pwd=((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $_POST["pwd"]) : ((trigger_error("Error...", E_USER_ERROR)) ? "" : ""));
else $pwd="";
global $CURUSER;
// now in $_POST["level"] there is the level name, we need to select the id_level to know if current user
// is allowed to modify the requested user
$rlev=run_query("SELECT id,id_level FROM users_level WHERE level='".((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], unesc($_POST["level"])) : ((trigger_error("Error...", E_USER_ERROR)) ? "" : ""))."'");
$reslev=mysqli_fetch_assoc($rlev);
if ($CURUSER["id_level"] >= $reslev["id_level"])
    $level=intval($reslev["id"]);
else
    $level=0;

$idlangue=intval($_POST["language"]);
$idstyle=intval($_POST["style"]);
$idflag=intval($_POST["flag"]);
$timezone=intval($_POST["timezone"]);

if (isset ($_POST["email"])) $email=((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $_POST["email"]) : ((trigger_error("Error...", E_USER_ERROR)) ? "" : ""));
else $email="";
$set=array();

if ($email!="")
   $set[]="email='$email'";
if ($level>0)
   $set[]="id_level='$level'";
if ($idlangue>0)
   $set[]="language=$idlangue";
if ($idstyle>0)
   $set[]="style=$idstyle";
if ($pwd!="")
   $set[]="password='".md5($pwd)."'";
if ($idflag>0)
   $set[]="flag=$idflag";
if ($timezone>=-12)
   $set[]="time_offset=$timezone";
// username
$set[]="username='$utente'";

$updateset=implode(",",$set);

if ($updateset!="")
   @run_query("UPDATE users SET $updateset WHERE username='$oldname'");

   write_log("Modified user $utente","modify");
}

block_end();
stdfoot();
?>