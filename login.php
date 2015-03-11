<?php
/*
* BtiTracker v1.5.0 is a php tracker system for BitTorrent, easy to setup and configure.
* This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
* Updated and Maintained by Yupy.
* Copyright (C) 2004-2014 Btiteam.org
*/
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'include'.DIRECTORY_SEPARATOR.'functions.php');

function login() {
	global $PRIVATE_TRACKER, $tpl, $STYLEPATH;

        if (!isset($user))
	     $user = '';

	$var_returno = urlencode('index.php');
	$tpl->assign('returno', $var_returno);

	$var_user = $user;
	$tpl->assign('user', $var_user);

        #If...
	$var_private_tracker = $PRIVATE_TRACKER;
	$tpl->assign('private_tracker', $var_private_tracker);
        #End If...

	$login = $tpl->draw($STYLEPATH . '/tpl/login', $return_string = true);
        echo $login;
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
