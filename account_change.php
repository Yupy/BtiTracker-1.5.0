<?php
require_once("include/functions.php");
require_once("include/config.php");

if (isset($_GET["style"]))
    $style=intval($_GET["style"]);
else
    $style=1;
if (isset($_GET["returnto"]))
   $url=urldecode($_GET["returnto"]);
else
   $url="index.php";
if (isset($_GET["langue"]))
   $langue=intval($_GET["langue"]);
else
   $langue=1;

dbconn();

// guest don't need to change language!
if (!$CURUSER || $CURUSER["uid"]==1)
  {
  redirect($url);
  exit;
 }

if (isset($_GET["style"]))
   @run_query("UPDATE users SET style=$style WHERE id=".$CURUSER["uid"]);

if (isset($_GET["langue"]))
   @run_query("UPDATE users SET language=$langue WHERE id=".$CURUSER["uid"]);

redirect($url);
?>