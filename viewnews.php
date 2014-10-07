<?php
/*
* BtiTracker v1.5.0 is a php tracker system for BitTorrent, easy to setup and configure.
* This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
* Updated and Maintained by Yupy.
* Copyright (C) 2004-2014 Btiteam.org
*/
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'include'.DIRECTORY_SEPARATOR.'functions.php');

dbconn();

standardheader('News');

if (user::$current["view_news"] == "no")
{
    err_msg(ERROR, NOT_AUTH_VIEW_NEWS);
    stdfoot();
    exit;
} else {
    block_begin("News");
    print_news();
    block_end();
}

stdfoot();

?>