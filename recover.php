<?php
/*
* BtiTracker v1.5.0 is a php tracker system for BitTorrent, easy to setup and configure.
* This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
* Updated and Maintained by Yupy.
* Copyright (C) 2004-2014 Btiteam.org
*/
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'include'.DIRECTORY_SEPARATOR.'functions.php');

dbconn();

standardheader('Password Recovery', true);

if ($_SERVER["REQUEST_METHOD"] == "POST")
{
    $email = trim(htmlentities(urldecode($_POST["email"])));

    if (!$email)
        stderr(ERROR, ERR_NO_EMAIL);

    $res = $db->query("SELECT * FROM users WHERE email = " . sqlesc($email) . " LIMIT 1");
    $arr = $res->fetch_assoc() or stderr(ERROR, ERR_EMAIL_NOT_FOUND_1 . " <b>" . $email . "</b> " . ERR_EMAIL_NOT_FOUND_2);

    if ($USE_IMAGECODE)
    {
        if (extension_loaded('gd'))
        {
            $arrgd = gd_info();

            if ($arrgd['FreeType Support'] == 1)
            {
                $public = $_POST['public_key'];
                $private = $_POST['private_key'];

                $p = new ocr_captcha();

                if ($p->check_captcha($public, $private) != true)
                {
                    stderr(ERROR, ERR_IMAGE_CODE);
                }
            }
        }
    }

    $floor = 100000;
    $ceiling = 999999;
    srand((double)microtime() * 1000000);
    $random = mt_rand($floor, $ceiling);

    $db->query("UPDATE users SET random = " . $random . " WHERE id = " . (int)$arr["id"]);

    if (!$db->affected_rows)
        stderr(ERROR, ERR_DB_ERR);

    $user_temp_id = (int)$arr["id"];
    $user_temp_email = $email;

$body = <<<EOD
Someone, hopefully you, requested that the password for the account
associated with this email address ({$email}) be reset.

The request originated from {$_SERVER['REMOTE_ADDR']}.

If you did not do this ignore this email. Please do not reply.


Should you wish to confirm this request, please follow this link:

{$BASEURL}/recover.php?id={$user_temp_id}&random={$random}


After you do this, your password will be reset and emailed back
to you.

--
{$SITENAME} Crew
EOD;


    @mail($arr["email"], $SITENAME . " " . PASS_RESET_CONF, $body, "From: " . $SITENAME . " <" . $SITEEMAIL . ">")
    or stderr(ERROR, ERR_SEND_EMAIL);

?>
<meta http-equiv="X-UA-Compatible" content="chrome=1; IE=edge" />
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="shortcut icon" href="favicon.ico" />
<link href="style/home.css" rel="stylesheet" type="text/css" />
<div id="head">
</div>
<table class="layout" id="maincontent" style="border-top: solid 1px #3399FF; border-bottom: solid 1px #3399FF;">
	<tr>
		<td align="center" valign="middle">
			<div id="logo">
				<ul>
					<li><a href="login.php">Log in</a></li>
					<li><a href="account.php">Signup</a></li>
				</ul>
			</div>
<span class="center">
<?php
    err_msg(SUCCESS, SUC_SEND_EMAIL . " <b>" . $email . "</b>.\n" . SUC_SEND_EMAIL_2);
?>
</span>
</td>
	</tr>
    </table>
    <div id="foot">
	<span><a href="http://www.btiteam.org" target="_blank">BtiTeam.org</a> | <a href="https://github.com/Yupy/BtiTracker-1.5.0" target="_blank">GitHub.com</a> | <a href="#">BtiTracker v1.5.0 by Yupy &amp; Btiteam</a></span>
</div>
<?php
}
elseif ($_GET)
{
    $id = 0 + (int)$_GET["id"];
    $random = intval($_GET["random"]);

    if (!$id || !$random || empty($random) || $random == 0)
        stderr(ERROR, ERR_UPDATE_USER);

    $res = $db->query("SELECT username, email, random FROM users WHERE id = " . $id);
    $arr = $res->fetch_array(MYSQLI_BOTH) or httperr();

    if ($random != $arr["random"])
        stderr(ERROR,ERR_UPDATE_USER);

    $email = $arr["email"];

    // generate new password;
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";

    $newpassword = '';
    for ($i = 0; $i < 10; $i++)
        $newpassword .= $chars[mt_rand(0, utf8::strlen($chars) - 1)];

    $db->query("UPDATE users SET password = '" . md5($newpassword) . "' WHERE id = " . $id . " AND random = " . $random);

    if (!$db->affected_rows)
        stderr(ERROR, ERR_UPDATE_USER);

    $body = <<<EOD
    As per your request we have generated a new password for your account.

    Here is the information we now have on file for this account:

    User name: {$arr["username"]}
    Password:  {$newpassword}

    You may login at {$BASEURL}/login.php

    --
    {$SITENAME} Crew
EOD;

    @mail($email, $SITENAME . " " . ACCOUNT_DETAILS, $body, "From: " . $SITENAME . " <" . $SITEEMAIL . ">") or stderr(ERROR, ERR_SEND_EMAIL);

?>
<meta http-equiv="X-UA-Compatible" content="chrome=1; IE=edge" />
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="shortcut icon" href="favicon.ico" />
<link href="style/home.css" rel="stylesheet" type="text/css" />
<div id="head">
</div>
<table class="layout" id="maincontent" style="border-top: solid 1px #3399FF; border-bottom: solid 1px #3399FF;">
	<tr>
		<td align="center" valign="middle">
			<div id="logo">
				<ul>
					<li><a href="login.php">Log in</a></li>
					<li><a href="account.php">Signup</a></li>
				</ul>
			</div>
<span class="center">
<?php
    err_msg(SUCCESS,SUC_SEND_EMAIL . " <b>" . $email . "</b>.\n" . SUC_SEND_EMAIL_2);
?>
</span>
</td>
	</tr>
    </table>
    <div id="foot">
	<span><a href="http://www.btiteam.org" target="_blank">BtiTeam.org</a> | <a href="https://github.com/Yupy/BtiTracker-1.5.0" target="_blank">GitHub.com</a> | <a href="#">BtiTracker v1.5.0 by Yupy &amp; Btiteam</a></span>
</div>
<?php
} else {

?>
<meta http-equiv="X-UA-Compatible" content="chrome=1; IE=edge" />
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="shortcut icon" href="favicon.ico" />
<link href="style/home.css" rel="stylesheet" type="text/css" />
<!--Page Design by What.CD-->
<div id="head">
</div>
<table class="layout" id="maincontent" style="border-top: solid 1px #3399FF; border-bottom: solid 1px #3399FF;">
	<tr>
		<td align="center" valign="middle">
			<div id="logo">
				<ul>
					<li><a href="login.php">Log in</a></li>
					<li><a href="account.php">Signup</a></li>
				</ul>
			</div>
        <div class="poetry"><?php echo RECOVER_DESC; ?></div>
		<form action="recover.php" name="recover" method="post">
	    <table class="layout">
		<br />
		<tr>
			<td>Email&nbsp;</td>
			<td colspan="2">
				<input type="text" size="40" name="email">
			</td>
		</tr>
	<?php
    // -----------------------------
    // Captcha hack
    // -----------------------------
    if ($USE_IMAGECODE)
    {
        if (extension_loaded('gd'))
        {
            $arr = gd_info();

            if ($arr['FreeType Support'] == 1)
            {
                $p = new ocr_captcha();

                print("<tr>\n\t<td>Image Code&nbsp;</td>");
                print("\n\t<td colspan='2'><input type='text' name='private_key' value='' maxlength='6' size='6'>\n");
                print($p->display_captcha(true));

                $private = $p->generate_private();

                print("</td>\n</tr>");
            }
        }
    }
	?>
		<tr>
			<td></td>
			<td><input type="submit" name="recover" value="Confirm" class="submit" /></td>
		</tr>
	</table>
	</form>
	</td>
	</tr>
    </table>
    <div id="foot">
	<span><a href="http://www.btiteam.org" target="_blank">BtiTeam.org</a> | <a href="https://github.com/Yupy/BtiTracker-1.5.0" target="_blank">GitHub.com</a> | <a href="#">BtiTracker v1.5.0 by Yupy &amp; Btiteam</a></span>
</div>
</body>
</html>
	
<?php

}

stdfoot();

?>
