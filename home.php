<?php
/*
* BtiTracker v1.5.0 is a php tracker system for BitTorrent, easy to setup and configure.
* This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
* Updated and Maintained by Yupy.
* Copyright (C) 2004-2014 Btiteam.org
*/
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'include'.DIRECTORY_SEPARATOR.'functions.php');

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
					<li><a href="home.php">Home</a></li>
					<li><a href="login.php">Log in</a></li>
					<li><a href="account.php">Signup</a></li>
				</ul>
			</div>
<div class="poetry">You've stumbled upon a door where your mind is the key. There are none who will lend you guidance; these trials are yours to conquer alone. Entering here will take more than mere logic and strategy, but the criteria are just as hidden as what they reveal. Find yourself, and you will find the very thing hidden behind this page. Beyond here is something like a utopia&#8202;&mdash;&#8202;beyond here is <?php echo security::html_safe($SITENAME); ?>.</div>
<span class="center">This is a mirage.</span>
		</td>
	</tr>
</table>
<div id="foot">
	<span><a href="http://www.btiteam.org" target="_blank">BtiTeam.org</a> | <a href="https://github.com/Yupy/BtiTracker-1.5.0" target="_blank">GitHub.com</a> | <a href="#">BtiTracker v1.5.0 by Yupy &amp; Btiteam</a></span>
</div>
</body>
</html>