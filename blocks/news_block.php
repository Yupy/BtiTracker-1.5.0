<?php
/*
* BtiTracker v1.5.0 is a php tracker system for BitTorrent, easy to setup and configure.
* This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
* Updated and Maintained by Yupy.
* Copyright (C) 2004-2014 Btiteam.org
*/
require_once(INCL_PATH . 'functions.php');
require_once(INCL_PATH . 'blocks.php');

if (!user::$current || user::$current["view_news"] == "no")
{
    #Do Nothing...
} else {
    block_begin(LAST_NEWS);
    print_news($GLOBALS['block_newslimit']);
    block_end();
}

?>