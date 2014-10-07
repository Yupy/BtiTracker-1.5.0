<?php
/*
* BtiTracker v1.5.0 is a php tracker system for BitTorrent, easy to setup and configure.
* This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
* Updated and Maintained by Yupy.
* Copyright (C) 2004-2014 Btiteam.org
*/
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'include'.DIRECTORY_SEPARATOR.'functions.php');

dbconn();

standardheader('Shoutbox', false);

?>
<style>
div.chat
{
align: center;
overflow: auto;
width: 95%;
height: 500px;
padding: 3px;
}
</style>
<?php

	$Smileys = array(
		':angry:'			=> 'angry.gif',
		':-D'				=> 'biggrin.gif',
		':D'				=> 'biggrin.gif',
		':|'				=> 'blank.gif',
		':-|'				=> 'blank.gif',
		':blush:'			=> 'blush.gif',
		':cool:'			=> 'cool.gif',
		':(('			=> 'crying.gif',
		':crying:'			=> 'crying.gif',
		':<<:'			=> 'eyesright.gif',
		':frown:'			=> 'frown.gif',
		'<3'				=> 'heart.gif',
		':unsure:'			=> 'hmm.gif',
		':lol:'				=> 'laughing.gif',
		':ninja:'			=> 'ninja.gif',
		':no:'				=> 'no.gif',
		':nod:'				=> 'nod.gif',
		':ohno:'			=> 'ohnoes.gif',
		':ohnoes:'			=> 'ohnoes.gif',
		':omg:'				=> 'omg.gif',
		':o'				=> 'ohshit.gif',
		':O'				=> 'ohshit.gif',
		':paddle:'			=> 'paddle.gif',
		':('				=> 'sad.gif',
		':-('				=> 'sad.gif',
		':shifty:'			=> 'shifty.gif',
		':sick:'			=> 'sick.gif',
		':)'				=> 'smile.gif',
		':-)'				=> 'smile.gif',
		':sorry:'			=> 'sorry.gif',
		':thanks:'			=> 'thanks.gif',
		':P'				=> 'tongue.gif',
		':p'				=> 'tongue.gif',
		':-P'				=> 'tongue.gif',
		':-p'				=> 'tongue.gif',
		':wave:'			=> 'wave.gif',
		';)'				=> 'wink.gif',
		':wink:'			=> 'wink.gif',
		':creepy:'			=> 'creepy.gif',
		':worried:'			=> 'worried.gif',
		':wtf:'				=> 'wtf.gif',
		':wub:'				=> 'wub.gif',
	);

function format_shout($text)
{
    global $Smileys, $BASEURL;
    
    $s = $text;
    $s = strip_tags($s);
    $s = security::html_safe(unesc($s));
    
    $f = @fopen("badwords.txt", "r");
    if ($f && filesize("badwords.txt") != 0) {
        $bw = fread($f, filesize("badwords.txt"));
        $badwords = explode("\n", $bw);
        for ($i = 0; $i < count($badwords); ++$i)
            $badwords[$i] = trim($badwords[$i]);
        $s = str_replace($badwords, "*censored*", $s);
    }
    @fclose($f);
    
    // [b]Bold[/b]
    $s = preg_replace("/\[b\]((\s|.)+?)\[\/b\]/", "<b>\\1</b>", $s);
    
    // [i]Italic[/i]
    $s = preg_replace("/\[i\]((\s|.)+?)\[\/i\]/", "<i>\\1</i>", $s);
    
    // [u]Underline[/u]
    $s = preg_replace("/\[u\]((\s|.)+?)\[\/u\]/", "<u>\\1</u>", $s);
    
    // [u]Underline[/u]
    $s = preg_replace("/\[u\]((\s|.)+?)\[\/u\]/i", "<u>\\1</u>", $s);
    
    // [color=blue]Text[/color]
    $s = preg_replace("/\[color=([a-zA-Z]+)\]((\s|.)+?)\[\/color\]/i", "<font color=\\1>\\2</font>", $s);
    
    // [color=#ffcc99]Text[/color]
    $s = preg_replace("/\[color=(#[a-f0-9][a-f0-9][a-f0-9][a-f0-9][a-f0-9][a-f0-9])\]((\s|.)+?)\[\/color\]/i", "<font color=\\1>\\2</font>", $s);
    
    // [url=http://www.example.com]Text[/url]
    $s = preg_replace("/\[url=((http|ftp|https|ftps|irc):\/\/[^<>\s]+?)\]((\s|.)+?)\[\/url\]/i", "<a href=\\1 target=_blank>\\3</a>", $s);
    
    // [url]http://www.example.com[/url]
    $s = preg_replace("/\[url\]((http|ftp|https|ftps|irc):\/\/[^<>\s]+?)\[\/url\]/i", "<a href=\\1 target=_blank>\\1</a>", $s);
    
    // [size=4]Text[/size]
    $s = preg_replace("/\[size=([1-7])\]((\s|.)+?)\[\/size\]/i", "<font size=\\1>\\2</font>", $s);
    
    // [font=Arial]Text[/font]
    $s = preg_replace("/\[font=([a-zA-Z ,]+)\]((\s|.)+?)\[\/font\]/i", "<font face=\"\\1\">\\2</font>", $s);
    
    // Linebreaks
    $s = nl2br($s);
    
    // Maintain spacing
    $s = str_replace("  ", " &nbsp;", $s);
    
    reset($Smileys);
    while (list($code, $url) = each($Smileys))
        $s = str_replace($code, "<img border='0' src='" . $BASEURL . "/images/smilies/" . $url . "'>", $s);
    
    return $s;
}

block_begin("Shout history");

echo "<br />";

$msg = array();

function file_save($filename, $content, $flags = 0)
{
    if (!($file = fopen($filename, 'w')))
        return FALSE;
    $n = fwrite($file, $content);
    fclose($file);
    return $n ? $n : FALSE;
}

if (!file_exists("chat.php"))
    file_save("chat.php", "<?php\n\$msg = " . var_export($msg, TRUE) . "\n?>");

include "chat.php";

while (count($msg) >= 100)
    array_shift($msg);

$msg2 = array_reverse($msg);
echo "<div align='center' class='chat'><table width='92%'>";
include(INCL_PATH . 'offset.php');
for ($i = 0; $i < count($msg2); ++$i) {
    echo "<tr><td class='header' align='left'>" . security::html_safe($msg2[$i]['pseudo']) . "&nbsp;&nbsp;&nbsp;[" . date("d/m/y H:i:s", $msg2[$i]['date'] - $offset) . "]</td></tr><tr><td class='lista' align='left'>" . format_shout($msg2[$i]['texte']) . "</td></tr>";
}
echo "</table></div>";

file_save("chat.php", "<?php\n\$msg = " . var_export($msg, TRUE) . "\n?>");
print("<br />");

block_end();

print("<br />");
print("<div align='center'><a href='javascript: window.close()'>" . CLOSE . "</a></div>");

stdfoot(false);

?>