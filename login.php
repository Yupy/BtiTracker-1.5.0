<?php
/*
* BtiTracker v1.5.0 is a php tracker system for BitTorrent, easy to setup and configure.
* This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
* Updated and Maintained by Yupy.
* Copyright (C) 2004-2014 Btiteam.org
*/
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'include'.DIRECTORY_SEPARATOR.'functions.php');

function login()
{
    if (!isset($user))
	    $user = '';
		
	?>
   <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
   <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
    <head>
	<title><?php echo security::html_safe($SITENAME); ?></title>
	<meta http-equiv="X-UA-Compatible" content="chrome=1; IE=edge" />
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link rel="shortcut icon" href="favicon.ico" />
	<link href="style/home.css" rel="stylesheet" type="text/css" />
    </head>
    <body>
    <!--Page by What.CD-->
    <div id="head">
    </div>
    <table class="layout" id="maincontent" style="border-top: solid 1px #3399FF; border-bottom: solid 1px #3399FF;">
	<tr>
		<td align="center" valign="middle">
			<div id="logo">
				<ul>
					<li><a href="index.php">Home</a></li>
					<li><a href="account.php">Signup</a></li>
					<li><a href="recover.php">Recover</a></li>
				</ul>
			</div>
	<form method="post" action="login.php?returnto=<?php echo urlencode('index.php'); ?>">
	<table class="layout">
		<tr>
			<td>Username&nbsp;</td>
			<td colspan="2">
				<input type="text" name="uid" id="uid" value="<?php $user ?>" required="required" size="40" maxlength="40" pattern="[A-Za-z0-9_?]{1,20}" autofocus="autofocus" placeholder="Username" />
			</td>
		</tr>
		<tr>
			<td>Password&nbsp;</td>
			<td colspan="2">
				<input type="password" name="pwd" id="pwd" required="required" size="40" maxlength="100" pattern=".{6,100}" placeholder="Password" />
			</td>
		</tr>
		<tr>
			<td></td>
			<td>
				<input type="checkbox" id="keeplogged" name="keeplogged" value="1" />
				<label for="keeplogged">Remember me</label>
			</td>
			<td><input type="submit" name="login" value="Log in" class="submit" /></td>
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

dbconn();

if (!user::$current || user::$current["uid"] == 1) {
    if (isset($_POST["uid"]) && $_POST["uid"])
        $user = security::html_safe($_POST["uid"]);
    else
	$user = '';

    if (isset($_POST["pwd"]) && $_POST["pwd"])
        $pwd = $_POST["pwd"];
    else
	$pwd='';

    if (isset($_POST["uid"]) && isset($_POST["pwd"]))
    {
        $res = $db->query("SELECT * FROM users WHERE username = '" . AddSlashes($user) . "'");
        $row = $res->fetch_array(MYSQLI_BOTH);

        if (!$row)
        {
            standardheader("Login");
            print("<br /><br /><div align='center'><font size='2' color='#FF0000'>" . ERR_USERNAME_INCORRECT . "</font></div>");
            login();
        }
        elseif (md5($row["random"].$row["password"].$row["random"]) != md5($row["random"].md5($pwd).$row["random"]))
        {
            standardheader("Login");
            print("<br /><br /><div align='center'><font size='2' color='#FF0000'>" . ERR_PASSWORD_INCORRECT . "</font></div>");
            login();
        } else {
            $db->query("UPDATE users SET loginhash = '" . md5(ip::get_ip().$row['password']) . "' WHERE id = " . (int)$row['id']);
            $salted = md5($GLOBALS["salting"].$row["random"].$row["password"].$row["random"]);
            logincookie((int)$row["id"], $salted);

            if (isset($_GET["returnto"]))
                $url = security::html_safe(urldecode($_GET["returnto"]));
            else
                $url = "index.php";

            redirect($url);
        }
    } else {
        standardheader("Login");
        login();
        exit;
    }
} else {
    if (isset($_GET["returnto"]))
        $url = security::html_safe(urldecode($_GET["returnto"]));
    else
        $url = "index.php";

    redirect($url);
}

?>
