<?php
/*
* BtiTracker v1.5.0 is a php tracker system for BitTorrent, easy to setup and configure.
* This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
* Updated and Maintained by Yupy.
* Copyright (C) 2004-2014 Btiteam.org
*/
require_once(CLASS_PATH . 'class.Template.php');

raintpl::configure("base_url", null);
raintpl::configure("tpl_dir", "");
raintpl::configure("cache_dir", "cache/" );

$tpl = new RainTPL;

dbconn();

function standardheader($title, $normalpage = true, $idlang = 0)
{
    global $SITENAME, $STYLEPATH, $USERLANG, $time_start, $gzip, $GZIP_ENABLED, $err_msg_install, $db;
    
    $time_start = get_microtime();
    
    // default settings for blocks/menu
    if (!isset($GLOBALS["charset"]))
        $GLOBALS["charset"] = "iso-8859-1";
    
    // controll if client can handle gzip
    if ($GZIP_ENABLED && user::$current['uid'] > 1) {
        if (stristr($_SERVER["HTTP_ACCEPT_ENCODING"], "gzip") && extension_loaded('zlib') && ini_get("zlib.output_compression") == 0) {
            if (ini_get('output_handler') != 'ob_gzhandler') {
                ob_start("ob_gzhandler");
                $gzip = 'enabled';
            } else {
                ob_start();
                $gzip = 'enabled';
            }
        } else {
            ob_start();
            $gzip = 'disabled';
        }
    } else
        $gzip = 'disabled';
    
    header("Content-Type: text/html; charset=" . $GLOBALS["charset"]);
    
    if ($title == "")
        $title = unesc($SITENAME);
    else
        $title = unesc($SITENAME) . " - " . security::html_safe($title);
    
   ?>
   <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
   <html><head>
    <title>
	<?php
    echo $title;
    ?>
	</title>
    <?php
    // get user's style
    $resheet = $db->query("SELECT * FROM style WHERE id = " . user::$current["style"]);
    if (!$resheet) {
        $STYLEPATH = "./style/base";
        $style     = "./style/base/torrent.css";
    } else {
        $resstyle  = $resheet->fetch_array(MYSQLI_BOTH);
        $STYLEPATH = $resstyle["style_url"];
        $style     = $resstyle["style_url"] . "/torrent.css";
    }
    print("<link rel='stylesheet' href='" . $style . "' type='text/css' />");
    ?>
    </head>
    <body>
    <?php
    
    // getting user language
    if ($idlang == 0)
        $reslang = $db->query("SELECT * FROM language WHERE id = " . user::$current["language"]);
    else
        $reslang = $db->query("SELECT * FROM language WHERE id=$idlang");
    
    if (!$reslang) {
        $USERLANG = "language/english.php";
    } else {
        $rlang    = $reslang->fetch_array(MYSQLI_BOTH);
        $USERLANG = "" . $rlang["language_url"];
    }
    
    clearstatcache();
    
    if (!file_exists($USERLANG)) {
        err_msg("Error!", "Missing Language!");
        print_version();
        print("</body>\n</html>\n");
        die;
    }
    
    require_once($USERLANG);
    
    if (!file_exists($style)) {
        err_msg("Error!", "Missing Style!");
        print_version();
        print("</body>\n</html>\n");
        die;
    }
    
    if ($normalpage)
        require_once($STYLEPATH . "/header.php");
    
    echo $err_msg_install;
}

function stdfoot($normalpage = true, $update = true)
{
    global $STYLEPATH;
	
    if ($normalpage)
        include($STYLEPATH . '/footer.php');
    
    print_version();
    print("</body>\n</html>\n");
    
    if ($update)
        register_shutdown_function("updatedata");
}

function linkcolor($num)
{
    if (!$num)
        return "red";
    if ($num == 1)
        return "yellow";
    
    return "green";
}

function image_or_link($image, $style = "", $link = "")
{
    if ($image == "")
        return $link;
    elseif (file_exists($image))
        return "<img src='" . $image . "' border='0' " . $style . " alt='" . $link . "' />";
    else
        return $link;
}

function err_msg($heading = "Error!", $string)
{
    global $tpl, $STYLEPATH;
    // just in case not found the language
    if (!defined("BACK"))
        define("BACK", "Back");
	
	$var_heading = $heading;
	$tpl->assign( "heading", $var_heading );
	
	$var_string = $string;
	$tpl->assign( "string", $var_string );
	
	$var_back = BACK;
	$tpl->assign( "back", $var_back );
	
	$err_msg = $tpl->draw( $STYLEPATH . '/tpl/err_msg', $return_string = true );
    echo $err_msg;
}

function block_begin($title = "-", $colspan = 1, $calign = "justify")
{
    global $tpl, $STYLEPATH;
	
	$var_colspan = $colspan;
	$tpl->assign( "colspan", $var_colspan );
	
	$var_title = $title;
	$tpl->assign( "title", $var_title );
	
	$var_calign = $calign;
	$tpl->assign( "calign", $var_calign );
	
	$block_begin = $tpl->draw( $STYLEPATH . '/tpl/block_begin', $return_string = true );
    echo $block_begin;
}

function block_end($colspan = 1)
{
    global $tpl, $STYLEPATH;
	
	$var_colspan = $colspan;
	$tpl->assign( "colspan", $var_colspan );
	
	$block_end = $tpl->draw( $STYLEPATH . '/tpl/block_end', $return_string = true );
    echo $block_end;
}

function redirect($redirecturl)
{
    global $tpl, $STYLEPATH;
	
	$var_redirecturl = $redirecturl;
	$tpl->assign( "redirecturl", $var_redirecturl );
	
	$redirect_url = $tpl->draw( 'style/base/tpl/redirect', $return_string = true );
    echo $redirect_url;
}

function textbbcode($form, $text, $content = "")
{
    global $tpl, $STYLEPATH;
	
	$var_text = $text;
	$tpl->assign( "text", $var_text );
	
	$var_content = security::html_safe($content);
	$tpl->assign( "content", $var_content );
	
	$text_bbcode = $tpl->draw( $STYLEPATH . '/tpl/text_bbcode', $return_string = true );
    echo $text_bbcode;
}

function begin_table($fullwidth = false, $padding = 5)
{
    global $tpl, $STYLEPATH;
	
    if ($fullwidth)
        $width = " width='100%'";
    else
        $width = "";
		
	$var_width = $width;
	$tpl->assign( "width", $var_width );
	
	$var_padding = $padding;
	$tpl->assign( "padding", $var_padding );
	
	$begin_table = $tpl->draw( $STYLEPATH . '/tpl/begin_table', $return_string = true );
    echo $begin_table;
}

function end_table()
{
    global $tpl, $STYLEPATH;
	
	$end_table = $tpl->draw( $STYLEPATH . '/tpl/end_table', $return_string = true );
    echo $end_table;
}

function begin_frame($caption = "", $center = false, $padding = 10)
{
    global $tpl, $STYLEPATH;
	
    if ($caption)
        $cption = "<center><h3>" . $caption . "</h3></center>\n";
    
    if ($center)
        $tdextra = " align='center'";
    else
        $tdextra = "";
	
	$var_cption = $cption;
	$tpl->assign( "caption", $var_cption );
	
	$var_padding = $padding;
	$tpl->assign( "padding", $var_padding );
	
	$var_tdextra = $tdextra;
	$tpl->assign( "tdextra", $var_tdextra );
    
	$begin_frame = $tpl->draw( $STYLEPATH . '/tpl/begin_frame', $return_string = true );
    echo $begin_frame;
}

function end_frame()
{
    global $tpl, $STYLEPATH;
	
	$end_frame = $tpl->draw( $STYLEPATH . '/tpl/end_frame', $return_string = true );
    echo $end_frame;
}

function stderr($heading, $text)
{
    err_msg($heading, $text);
    stdfoot();
    die;
}

function attach_frame($padding = 10)
{
    global $tpl, $STYLEPATH;
	
	$attach_frame = $tpl->draw( $STYLEPATH . '/tpl/attach_frame', $return_string = true );
    echo $attach_frame;
}

function httperr($code = 404)
{
    global $tpl, $STYLEPATH;
	
    header("HTTP/1.0 404 Not found");

	$var_not_found = ERR_NOT_FOUD;
	$tpl->assign( "error_not_found", $var_not_found );
	
	$httperr = $tpl->draw( $STYLEPATH . '/tpl/httperr', $return_string = true );
    echo $httperr;
	
    exit();
}

function peercolor($num)
{
    if (!$num) {
        return "#FF0000";
    } elseif ($num == 1) {
        return "#BEC635";
    } else {
        return "green";
    }
}

?>