<?php

require_once("include/functions.php");
require_once("include/config.php");

$check_hash = (isset($_GET['check_hash']) && htmlspecialchars($_GET['check_hash']));
$salty = md5("SomeRandomTextYouWant".user::$current['username']."");

//if (empty($check_hash)) 
 //   die("No Hash, your up to no good...");

//if ($check_hash != $salty) 
  //  die("Unsecure Logout, Hash mismatch please contact the Staff !");

logoutcookie();

header("Location: " . vars::$base_url . "/");

?>