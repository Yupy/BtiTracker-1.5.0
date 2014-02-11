<?php

require_once("config.php");
require_once("functions.php");

dbconn();

global $CURUSER;

if (date('I',time())==1) {
$tz=(date('Z',time())-3600);
} else {
$tz=date('Z',time());
}
$offset=$tz-($CURUSER["time_offset"]*3600);

?>