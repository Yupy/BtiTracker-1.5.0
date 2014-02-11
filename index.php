<?php

if (file_exists("install.me"))
   {
   if (dirname($_SERVER["PHP_SELF"])=="/" || dirname($_SERVER["PHP_SELF"])=="\\")
      header("Location: http://".$_SERVER["HTTP_HOST"]."/install/");
   else
      header("Location: http://".$_SERVER["HTTP_HOST"].dirname($_SERVER["PHP_SELF"])."/install/");
   exit;
}

require_once ("include/functions.php");
require_once ("include/config.php");
require_once ("include/blocks.php");

dbconn(true);

standardheader('Index',true,0);

center_menu();

stdfoot();

?>