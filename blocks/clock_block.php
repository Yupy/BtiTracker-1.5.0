<?php
$clocktype = $GLOBALS["clocktype"];
require_once("addons/clock/clock.php");
block_begin("Clock",1,"center");
clock_display($clocktype);
block_end();
?>
