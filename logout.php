<?php

require_once("include/functions.php");
require_once("include/config.php"); 

$check_hash = (isset($_GET['check_hash']) && htmlsafechars($_GET['check_hash']));
$salty = md5("SomeRandomTextYouWant".$CURUSER['username']."");
if (empty($check_hash)) 
die("No Hash, your up to no good...");
if ($check_hash != $salty) 
die("Unsecure Logout, hash mismatch please contact the Staff!");   

logoutcookie();

header("Location: ./");

?>