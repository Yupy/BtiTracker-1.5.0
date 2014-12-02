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
	print("<link rel='stylesheet' href='style/base/ui.css' type='text/css' />");
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

$smilies = array(
	':angry:'			=> 'angry.gif',
	':D'				=> 'biggrin.gif',
	':|'				=> 'blank.gif',
	':blush:'			=> 'blush.gif',
	':cool:'			=> 'cool.gif',
	':(('			=> 'crying.gif',
	':<<:'			=> 'eyesright.gif',
	':frown:'			=> 'frown.gif',
	'<3'				=> 'heart.gif',
	':unsure:'			=> 'hmm.gif',
	':lol:'				=> 'laughing.gif',
	':ninja:'			=> 'ninja.gif',
	':no:'				=> 'no.gif',
	':nod:'				=> 'nod.gif',
	':ohno:'			=> 'ohnoes.gif',
	':omg:'				=> 'omg.gif',
	':O'				=> 'ohshit.gif',
	':paddle:'			=> 'paddle.gif',
	':('				=> 'sad.gif',
	':shifty:'			=> 'shifty.gif',
	':sick:'			=> 'sick.gif',
	':)'				=> 'smile.gif',
	':sorry:'			=> 'sorry.gif',
	':thanks:'			=> 'thanks.gif',
	':P'				=> 'tongue.gif',
	':wave:'			=> 'wave.gif',
	';)'				=> 'wink.gif',
	':creepy:'			=> 'creepy.gif',
	':worried:'			=> 'worried.gif',
	':wtf:'				=> 'wtf.gif',
	':wub:'				=> 'wub.gif',
);

function format_urls($s)
{
    return preg_replace("/(\A|[^=\]'\"a-zA-Z0-9])((http|ftp|https|ftps|irc):\/\/[^<>\s]+)/i","\\1<a target='_blank' href='redir.php?url=\\2'>\\2</a>", $s);
}

function format_quotes($s)
{
    $old_s = '';

    while ($old_s != $s)
    {
        $old_s = $s;

        //-- Find First Occurrence Of [/quote]
        $close = strpos($s, "[/quote]");

        if ($close === false)
        {
            return $s;
        }

        //-- Find Last [quote] Before First [/quote] --//
        //-- Note That There Is No Check For Correct Syntax --//
        $open = strripos(utf8::substr($s, 0, $close), "[quote");

        if ($open === false)
        {
            return $s;
        }

        $quote = utf8::substr($s, $open, $close - $open + 8);

        //-- [quote]Text[/quote] --//
        $quote = preg_replace("/\[quote\]\s*((\s|.)+?)\s*\[\/quote\]\s*/i", "<span class='sub'><strong>Quote:</strong></span><table class='main' border='1' cellspacing='0' cellpadding='10'><tr><td style='border: 1px black dotted'>\\1</td></tr></table><br />", $quote);

        //-- [quote=Author]Text[/quote] --//
        $quote = preg_replace("/\[quote=(.+?)\]\s*((\s|.)+?)\s*\[\/quote\]\s*/i", "<span class='sub'><strong>\\1 wrote:</strong></span><table class='main' border='1' cellspacing='0' cellpadding='10'><tr><td style='border: 1px black dotted'>\\2</td></tr></table><br />", $quote);

        $s = utf8::substr($s, 0, $open).$quote.utf8::substr($s, $close + 8);
    }
    return $s;
}


function format_comment($text, $strip_html = true)
{
    global $smilies;

    $s = $text;

    unset($text);

    $s = str_replace(";)", ":wink:", $s);

    if ($strip_html)
    {
        $s = htmlentities($s, ENT_QUOTES, 'UTF-8');
    }
	
    $f = @fopen("badwords.txt", "r");
    if ($f && filesize ("badwords.txt") != 0)
    {
        $bw = fread($f, filesize("badwords.txt"));
        $badwords = explode("\n", $bw);
        for ($i = 0; $i < count($badwords); ++$i)
           $badwords[$i] = trim($badwords[$i]);
        $s = str_replace($badwords, "*Censored*", $s);
    }
    @fclose($f);

    if (preg_match("#function\s*\((.*?)\|\|#is", $s))
    {
        $s = str_replace(":", "&#58;", $s);
        $s = str_replace("[", "&#91;", $s);
        $s = str_replace("]", "&#93;", $s);
        $s = str_replace(")", "&#41;", $s);
        $s = str_replace("(", "&#40;", $s);
        $s = str_replace("{", "&#123;", $s);
        $s = str_replace("}", "&#125;", $s);
        $s = str_replace("$", "&#36;", $s);
    }

    //-- [*] --//
    if (utf8::stripos($s, '[*]') !== false)
    {
        $s = preg_replace("/\[\*\]/", "<img src=\"images/list.gif\" alt=\"List\" title=\"List\" class=\"listitem\" />", $s);
    }

    //-- [b]Bold[/b] --//
    if (utf8::stripos($s, '[b]') !== false)
    {
        $s = preg_replace('/\[b\](.+?)\[\/b\]/is', "<span style='font-weight:bold;'>\\1</span>", $s);
    }

    //-- [i]Italic[/i] --//
    if (utf8::stripos($s, '[i]') !== false)
    {
        $s = preg_replace('/\[i\](.+?)\[\/i\]/is', "<span style='font-style: italic;'>\\1</span>", $s);
    }

    //-- [u]Underline[/u] --//
    if (utf8::stripos($s, '[u]') !== false)
    {
        $s = preg_replace('/\[u\](.+?)\[\/u\]/is', "<span style='text-decoration:underline;'>\\1</span>", $s);
    }

    //-- [color=blue]Text[/color] --//
    if (utf8::stripos($s, '[color=') !== false)
    {
        $s = preg_replace('/\[color=([a-zA-Z]+)\](.+?)\[\/color\]/is', '<span style="color: \\1">\\2</span>', $s);

        //-- [color=#ffcc99]Text[/color] --//
        $s = preg_replace('/\[color=(#[a-f0-9]{6})\](.+?)\[\/color\]/is', '<span style="color: \\1">\\2</span>', $s);
    }

    //-- Media Tag --//
    if (utf8::stripos($s, '[media=') !== false)
    {
        $s = preg_replace("#\[media=(youtube|liveleak|GameTrailers|imdb)\](.+?)\[/media\]#ies", "_MediaTag('\\2','\\1')", $s);
        $s = preg_replace("#\[media=(youtube|liveleak|GameTrailers|vimeo)\](.+?)\[/media\]#ies", "_MediaTag('\\2','\\1')", $s);
    }

    //-- Img Using Lightbox --//
    //-- [img=http://www/image.gif] --//
    if (utf8::stripos($s, '[img') !== false)
    {
        $s = preg_replace("/\[img\]((http|https):\/\/[^\s'\"<>]+(\.(jpg|gif|png|bmp|jpeg)))\[\/img\]/i", "<img src=\"\\1\" alt=\"\" />", $s);
        $s = preg_replace("/\[img=((http|https):\/\/[^\s'\"<>]+(\.(gif|jpg|png|bmp|jpeg)))\]/i", "<img src=\"\\1\" alt=\"\" />", $s);
    }

    //-- [size=4]Text[/size] --//
    if (utf8::stripos($s, '[size=') !== false)
    {
        $s = preg_replace("/\[size=([1-7])\]((\s|.)+?)\[\/size\]/i", "<font size=\\1>\\2</font>", $s);
    }

    //-- [font=Arial]Text[/font] --//
    if (utf8::stripos($s, '[face=') !== false)
    {
        $s = preg_replace('/\[face=([a-zA-Z ,]+)\](.+?)\[\/face\]/is', '<span style="font-family: \\1">\\2</span>', $s);
    }

    //-- [s]Stroke[/s] --//
    if (utf8::stripos($s, '[s]') !== false)
    {
        $s = preg_replace("/\[s\](.+?)\[\/s\]/is", "<s>\\1</s>", $s);
    }

     //-- Dynamic Vars --//

    //-- [Spoiler]TEXT[/Spoiler] --//
    if (utf8::stripos($s, '[spoiler]') !== false)
    {
        $s = preg_replace("/\[spoiler\](.+?)\[\/spoiler\]/is", "<div class=\"smallfont\" align=\"left\"><input type=\"button\" value=\"Show\" style=\"width:75px;font-size:10px;margin:0px;padding:0px;\" onclick=\"if (this.parentNode.parentNode.getElementsByTagName('div')[1].getElementsByTagName('div')[0].style.display != '') {this.parentNode.parentNode.getElementsByTagName('div')[1].getElementsByTagName('div')[0].style.display = '';this.innerText = ''; this.value = 'Hide'; } else { this.parentNode.parentNode.getElementsByTagName('div')[1].getElementsByTagName('div')[0].style.display = 'none'; this.innerText = ''; this.value = 'Show'; }\" /><div style=\"margin: 10px; padding: 10px; border: 1px inset;\" align=\"left\"><div style=\"display: none;\">\\1</div></div></div>", $s);
    }

    //-- [mcom]Text[/mcom] --//
    if (utf8::stripos($s, '[mcom]') !== false)
    {
        $s = preg_replace("/\[mcom\](.+?)\[\/mcom\]/is", "<div style=\"font-size: 18pt; line-height: 50%;\"><div style=\"border-color: red; background-color: red; color: white; text-align: center; font-weight: bold; font-size: large;\"><strong>\\1</strong></div></div>", $s);
    }

    //-- The [you] Tag --//
    if (utf8::stripos($s, '[you]') !== false)
    {
        $s = preg_replace("/\[you\]/i", user::$current['username'], $s);
    }

    //-- [mail]Mail[/mail] --//
    if (stripos($s, '[mail]') !== false)
    {
        $s = preg_replace("/\[mail\](.+?)\[\/mail\]/is", "<a href=\"mailto:\\1\" target=\"_blank\">\\1</a>", $s);
    }

    //--[Align=(center|left|right|justify)]Text[/align] --//
    if (utf8::stripos($s, '[align=') !== false)
    {
        $s = preg_replace("/\[align=([a-zA-Z]+)\](.+?)\[\/align\]/is", "<div style=\"text-align:\\1\">\\2</div>", $s);
    }

    //-- Quotes --//
    $s = format_quotes($s);

    //-- URLs --//
    $s = format_urls($s);

    if (utf8::stripos($s, '[url') !== false)
    {
        //-- [url=http://www.example.com]Text[/url] --//
        $s = preg_replace("/\[url=([^()<>\s]+?)\]((\s|.)+?)\[\/url\]/i","<a target=_blank href=redir.php?url=\\1>\\2</a>", $s);
        //-- [url]http://www.example.com[/url] --//
        $s = preg_replace("/\[url\]([^()<>\s]+?)\[\/url\]/i","<a target=_blank href=redir.php?url=\\1>\\1</a>", $s);
    }

    //-- Linebreaks --//
    $s = nl2br($s);

    //-- [pre]Preformatted[/pre] --//
    if (utf8::stripos($s, '[pre]') !== false)
    {
        $s = preg_replace("/\[pre\](.+?)\[\/pre\]/is", "<tt><span style=\"white-space: nowrap;\">\\1</span></tt>", $s);
    }

    //-- [nfo]NFO-preformatted[/nfo] --//
    if (utf8::stripos($s, '[nfo]') !== false)
    {
        $s = preg_replace("/\[nfo\](.+?)\[\/nfo\]/i", "<tt><span style=\"white-space: nowrap;\"><font face='MS Linedraw' size='2' style='font-size: 10pt; line-height: "."10pt'>\\1</font></span></tt>", $s);
    }

    //-- Maintain Spacing --//
    $s = str_replace("  ", " &nbsp;", $s);

    reset($smilies);
    while (list($code, $url) = each($smilies))
    {
        $s = str_replace($code, "<img src='images/smilies/{$url}' border='0' alt='".security::html_safe($code)."' title='".security::html_safe($code)."' />", $s);
    }

    return $s;
}

function _MediaTag ($content, $type)
{
    if ($content == '' or $type == '')
    {
        return;
    }

    $return = '';

    switch ($type)
    {
        case 'youtube':
            $return = preg_replace("#^http://(?:|www\.)youtube\.com/watch\?v=([\-_a-zA-Z0-9]+)+?$#i", "<object type='application/x-shockwave-flash' height='355' width='425' data='http://www.youtube.com/v/\\1'><param name='movie' value='http://www.youtube.com/v/\\1' /><param name='allowScriptAccess' value='sameDomain' /><param name='quality' value='best' /><param name='bgcolor' value='#FFFFFF' /><param name='scale' value='noScale' /><param name='salign' value='TL' /><param name='FlashVars' value='playerMode=embedded' /><param name='wmode' value='transparent' /></object>", $content);
            break;

        case 'liveleak':
            $return = preg_replace("#^http://(?:|www\.)liveleak\.com/view\?i=([_a-zA-Z0-9]+)+?$#i", "<object type='application/x-shockwave-flash' height='355' width='425' data='http://www.liveleak.com/e/\\1'><param name='movie' value='http://www.liveleak.com/e/\\1' /><param name='allowScriptAccess' value='sameDomain' /><param name='quality' value='best' /><param name='bgcolor' value='#FFFFFF' /><param name='scale' value='noScale' /><param name='salign' value='TL' /><param name='FlashVars' value='playerMode=embedded' /><param name='wmode' value='transparent' /></object>", $content);
            break;

        case 'GameTrailers':
            $return = preg_replace("#^http://(?:|www\.)gametrailers\.com/video/([\-_a-zA-Z0-9]+)+?/([0-9]+)+?$#i", "<object type='application/x-shockwave-flash' height='355' width='425' data='http://www.gametrailers.com/remote_wrap.php?mid=\\2'><param name='movie' value='http://www.gametrailers.com/remote_wrap.php?mid=\\2' /><param name='allowScriptAccess' value='sameDomain' /> <param name='allowFullScreen' value='true' /><param name='quality' value='high' /></object>", $content);
            break;

        case 'imdb':
            $return = preg_replace("#^http://(?:|www\.)imdb\.com/video/screenplay/([_a-zA-Z0-9]+)+?$#i", "<div class='\\1'><div style=\"padding: 3px; background-color: transparent; border: none; width:690px;\"><div style=\"text-transform: uppercase; border-bottom: 1px solid #CCCCCC; margin-bottom: 3px; font-size: 0.8em; font-weight: bold; display: block;\"><span onclick=\"if (this.parentNode.parentNode.getElementsByTagName('div')[1].getElementsByTagName('div')[0].style.display != '') { this.parentNode.parentNode.getElementsByTagName('div')[1].getElementsByTagName('div')[0].style.display = ''; this.innerHTML = '<strong>Imdb Trailer: </strong><a href=\'#\' onclick=\'return false;\'>hide</a>'; } else { this.parentNode.parentNode.getElementsByTagName('div')[1].getElementsByTagName('div')[0].style.display = 'none'; this.innerHTML = '<b>Imdb Trailer: </b><a href=\'#\' onclick=\'return false;\'>show</a>'; }\" ><b>Imdb Trailer: </b><a href=\"#\" onclick=\"return false;\">show</a></span></div><div class=\"quotecontent\"><div style=\"display: none;\"><iframe style='vertical-align: middle;' src='http://www.imdb.com/video/screenplay/\\1/player' scrolling='no' width='660' height='490' frameborder='0'></iframe></div></div></div></div>", $content);
            break;

        case 'vimeo':
            $return = preg_replace("#^http://(?:|www\.)vimeo\.com/([0-9]+)+?$#i", "<object type='application/x-shockwave-flash' width='425' height='355' data='http://vimeo.com/moogaloop.swf?clip_id=\\1&amp;server=vimeo.com&amp;show_title=1&amp;show_byline=1&amp;show_portrait=0&amp;color=&amp;fullscreen=1'>
            <param name='allowFullScreen' value='true' />
            <param name='allowScriptAccess' value='sameDomain' />
            <param name='movie' value='http://vimeo.com/moogaloop.swf?clip_id=\\1&amp;server=vimeo.com&amp;show_title=1&amp;show_byline=1&amp;show_portrait=0&amp;color=&amp;fullscreen=1' />
            <param name='quality' value='high' />
            </object>", $content);
            break;

        default:

            $return = 'Not Found !';
    }

    return $return;
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
