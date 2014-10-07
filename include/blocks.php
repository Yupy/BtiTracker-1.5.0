<?php
/*
 * BtiTracker v1.5.0 is a php tracker system for BitTorrent, easy to setup and configure.
 * This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
 * Updated and Maintained by Yupy.
 * Copyright (C) 2004-2014 Btiteam.org
 */

function main_menu()
{
    global $db;

    $res    = $db->query("SELECT * FROM blocks WHERE position = 't' AND status = 1 ORDER BY sortid");
    $i      = 0;
    $blocks = array();
    while ($result = $res->fetch_array(MYSQLI_BOTH)) {
        if ($result["status"]) {
            $block        = $result["content"];
            $blocks[$i++] = $block;
            
        }
    }
    foreach ($blocks as $entry) {
        if ($entry != "forum")
            include(BLOCKS_PATH . "" . $entry . "_block.php");
        elseif ($entry == "forum" && ($GLOBALS["FORUMLINK"] == "" || $GLOBALS["FORUMLINK"] == "internal"))
            include(BLOCKS_PATH . "" . $entry . "_block.php");
    }
}

function center_menu()
{
    global $db;
	
    $res    = $db->query("SELECT * FROM blocks WHERE position = 'c' AND status = 1  ORDER BY sortid");
    $i      = 0;
    $blocks = array();
    while ($result = $res->fetch_array(MYSQLI_BOTH)) {
        if ($result["status"]) {
            $block        = $result["content"];
            $blocks[$i++] = $block;
            
        }
    }
    foreach ($blocks as $entry) {
        if ($entry != "forum")
            include(BLOCKS_PATH . "" . $entry . "_block.php");
        elseif ($entry == "forum" && ($GLOBALS["FORUMLINK"] == "" || $GLOBALS["FORUMLINK"] == "internal"))
            include(BLOCKS_PATH . "" . $entry . "_block.php");
    }
}


function side_menu()
{
    global $db;

    $res    = $db->query("SELECT * FROM blocks WHERE position = 'l' AND status = 1  ORDER BY sortid");
    $i      = 0;
    $blocks = array();
    while ($result = $res->fetch_array(MYSQLI_BOTH)) {
        if ($result["status"]) {
            $block        = $result["content"];
            $blocks[$i++] = $block;
            
        }
    }
    if (count($blocks) > 0) {
        // make new columns only if at least 1 block
        ?>
        <td width='200' valign='top'>
        <?php
        foreach ($blocks as $entry) {
            if ($entry != "forum")
                include(BLOCKS_PATH . "" . $entry . "_block.php");
            elseif ($entry == "forum" && ($GLOBALS["FORUMLINK"] == "" || $GLOBALS["FORUMLINK"] == "internal"))
                include(BLOCKS_PATH . "" . $entry . "_block.php");
        }
        
        ?>
        </td>
        <?php
    }
}

function right_menu()
{
    global $db;

    $res    = $db->query("SELECT * FROM blocks WHERE position = 'r' AND status = 1  ORDER BY sortid");
    $i      = 0;
    $blocks = array();
    while ($result = $res->fetch_array(MYSQLI_BOTH)) {
        if ($result["status"]) {
            $block        = $result["content"];
            $blocks[$i++] = $block;
            
        }
    }
    if (count($blocks) > 0) {
        ?>
        <td width='200' valign='top'>
        <?php
        // make new columns only if at least 1 block
        foreach ($blocks as $entry) {
            if ($entry != "forum")
                include(BLOCKS_PATH . "" . $entry . "_block.php");
            elseif ($entry == "forum" && ($GLOBALS["FORUMLINK"] == "" || $GLOBALS["FORUMLINK"] == "internal"))
                include(BLOCKS_PATH . "" . $entry . "_block.php");
        }
        ?>
        </td>
        <?php
    }
}

?>