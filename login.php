<?php

require_once ("include/functions.php");
require_once ("include/config.php");

function login()
{
block_begin(LOGIN);
if(!isset ($user))$user="";
?>
<form method="post" action="login.php?returnto=<?php echo urlencode("index.php"); ?>">
<table align="center" class="lista" border="0" cellpadding="10">
<tr><td align="right" class="header"><?php echo USER_NAME;?>:</td><td class="lista"><input type="text" size="40" name="uid" value="<?php $user ?>" maxlength="40" /></td></tr>
<tr><td align="right" class="header"><?php echo USER_PWD;?>:</td><td class="lista"><input type="password" size="40" name="pwd" maxlength="40" /></td></tr>
<tr><td colspan="2"  class="header" align="center"><input type="submit" value="<?php echo FRM_CONFIRM;?>" /></td></tr>
<tr><td colspan="2"  class="header" align="center"><?php echo NEED_COOKIES;?></td></tr>
</table>
</form>
<p align="center">
<a href="account.php"><?php echo ACCOUNT_CREATE ?></a>&nbsp;&nbsp;&nbsp;<a href="recover.php"><?php echo RECOVER_PWD ?></a>
</p>
<?php
block_end();
stdfoot();

}

dbconn();

if (!$CURUSER || $CURUSER["uid"]==1) {
if (isset($_POST["uid"]) && $_POST["uid"])
  $user=$_POST["uid"];
else $user='';
if (isset($_POST["pwd"]) && $_POST["pwd"])
  $pwd=$_POST["pwd"];
else $pwd='';

  if (isset($_POST["uid"]) && isset($_POST["pwd"]))
  {
    $res = run_query("SELECT * FROM users WHERE username ='".AddSlashes($user)."'")
        or die(((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)));
    $row = mysqli_fetch_array($res);

    if (!$row)
        {
        standardheader("Login");
        print("<br /><br /><div align=\"center\"><font size=\"2\" color=\"#FF0000\">".ERR_USERNAME_INCORRECT."</font></div>");
        login();
        }
    elseif (md5($row["random"].$row["password"].$row["random"]) != md5($row["random"].md5($pwd).$row["random"]))
        {
                standardheader("Login");
                print("<br /><br /><div align=\"center\"><font size=\"2\" color=\"#FF0000\">".ERR_PASSWORD_INCORRECT."</font></div>");
                login();
                }
    else
    {
    run_query("UPDATE users SET loginhash='".md5(getip().$row['password'])."' WHERE id=$row[id]");
    $salted = md5($GLOBALS["salting"].$row["random"].$row["password"].$row["random"]);
    logincookie($row["id"], $salted);
    $Memcached->delete_value("OnlineUsers::");

    if (isset($_GET["returnto"]))
       $url=htmlsafechars(urldecode($_GET["returnto"]));
    else
        $url="index.php";

    redirect($url);
    }
  }
  else
  {
   standardheader("Login");
   login();
   exit;
  }
}
else {

  if (isset($_GET["returnto"]))
     $url=htmlsafechars(urldecode($_GET["returnto"]));
  else
      $url="index.php";

  redirect($url);
}
?>
