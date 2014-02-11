<?php
require_once("include/functions.php");
require_once("include/config.php");

dbconn();

standardheader('Password Recovery',true);

if ($_SERVER["REQUEST_METHOD"] == "POST")
{
  $email = trim(htmlsafechars(urldecode($_POST["email"])));
  if (!$email)
    stderr(ERROR,ERR_NO_EMAIL);
  $res = run_query("SELECT * FROM users WHERE email=" . sqlesc($email) . " LIMIT 1") or sqlerr();
  $arr = mysqli_fetch_assoc($res) or stderr(ERROR,ERR_EMAIL_NOT_FOUND_1." <b>$email</b> ".ERR_EMAIL_NOT_FOUND_2);
if ($USE_IMAGECODE)
{
  if (extension_loaded('gd'))
    {
     $arrgd = gd_info();
     if ($arrgd['FreeType Support']==1)
      {
        $public=$_POST['public_key'];
        $private=$_POST['private_key'];

          $p=new ocr_captcha();

          if ($p->check_captcha($public,$private) != true)
              {
              stderr(ERROR,ERR_IMAGE_CODE);
          }
       }
    }
}
  $floor = 100000;
  $ceiling = 999999;
  srand((double)microtime()*1000000);
  $random = mt_rand($floor, $ceiling);

  run_query("UPDATE users SET random=$random WHERE id=" . $arr["id"]) or sqlerr();
  if (!mysqli_affected_rows($GLOBALS["___mysqli_ston"]))
      stderr(ERROR,ERR_DB_ERR);

  $user_temp_id = $arr["id"];
  $user_temp_email = $email;

$body=<<<EOD
Someone, hopefully you, requested that the password for the account
associated with this email address ($email) be reset.

The request originated from {$_SERVER["REMOTE_ADDR"]}.

If you did not do this ignore this email. Please do not reply.


Should you wish to confirm this request, please follow this link:

$BASEURL/recover.php?id=$user_temp_id&random=$random


After you do this, your password will be reset and emailed back
to you.

--
$SITENAME
EOD;

  @mail( $arr["email"], "$SITENAME ".PASS_RESET_CONF, $body, "From: $SITENAME <$SITEEMAIL>")
    or stderr(ERROR,ERR_SEND_EMAIL);
  err_msg(SUCCESS,SUC_SEND_EMAIL." <b>$email</b>.\n".SUC_SEND_EMAIL_2);
}
elseif($_GET)
{
    $id = 0 + $_GET["id"];
    $random = intval($_GET["random"]);

if (!$id || !$random || empty($random) || $random==0)
    stderr(ERROR,ERR_UPDATE_USER);

$res = run_query("SELECT username, email, random FROM users WHERE id = $id");
$arr = mysqli_fetch_array($res) or httperr();

if ($random!=$arr["random"])
    stderr(ERROR,ERR_UPDATE_USER);

    $email = $arr["email"];

    // generate new password;
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";

    $newpassword = "";
    for ($i = 0; $i < 10; $i++)
      $newpassword .= $chars[mt_rand(0, strlen($chars) - 1)];

    run_query("UPDATE users SET password='".md5($newpassword)."' WHERE id=$id AND random=$random");

    if (!mysqli_affected_rows($GLOBALS["___mysqli_ston"]))
        stderr(ERROR,ERR_UPDATE_USER);

  $body = <<<EOD
As per your request we have generated a new password for your account.

Here is the information we now have on file for this account:

    User name: {$arr["username"]}
    Password:  $newpassword

You may login at $BASEURL/login.php

--
$SITENAME
EOD;

  @mail($email, "$SITENAME ".ACCOUNT_DETAILS, $body, "From: $SITENAME <$SITEEMAIL>")
    or stderr(ERROR,ERR_SEND_EMAIL);

  err_msg(SUCCESS,SUC_SEND_EMAIL." <b>$email</b>.\n".SUC_SEND_EMAIL_2);
}
else
{
    block_begin(RECOVER_TITLE);
    print("<p align=center>".RECOVER_DESC."</p>");
    ?>
    <div align="center">
      <form action="recover.php" name="recover" method="post">
        <table width="90%" class="lista" cellspacing="0" cellpadding="10">
        <tr><td class="header"><?php echo REGISTERED_EMAIL; ?></td>
        <td class="lista" align="left"><input type="text" size="40" name="email"></td></tr>
<?php
// -----------------------------
// Captcha hack
// -----------------------------
if ($USE_IMAGECODE)
  {
   if (extension_loaded('gd'))
     {
       $arr = gd_info();
       if ($arr['FreeType Support']==1)
        {
         $p=new ocr_captcha();

         print("<tr>\n\t<td align=left class=\"header\">".IMAGE_CODE.":</td>");
         print("\n\t<td align=left class=\"lista\"><input type='text' name='private_key' value='' maxlength='6' size='6'>\n");
         print($p->display_captcha(true));
         $private=$p->generate_private();
         print("</td>\n</tr>");
      }
     }
   }
?>
        </table>
        <table width="90%" class="lista" cellspacing="0" cellpadding="10">
        <tr><td colspan="2" align="center"><input type="submit" value="<?php echo FRM_CONFIRM;?>" class="btn"></td></tr>
        </table>
      </form>
    </div>
    <br />
    <?php
    block_end();
}

stdfoot();
?>