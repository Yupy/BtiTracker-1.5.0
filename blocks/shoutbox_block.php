<style>
div.chat
{
align: left;
overflow: auto;
width: 100%;
height: 132px;
padding: 0px;
}

</style>
<?php
require_once("include/functions.php");
require_once("include/smilies.php");
if (!isset($CURUSER)) global $CURUSER;
?>
<script language='javascript'>
function SmileIT(smile){
    document.forms['shout'].elements['mess'].value = document.forms['shout'].elements['mess'].value+" "+smile+" ";
    document.forms['shout'].elements['mess'].focus();
}

function Pophistory() {
         newWin=window.open('allshout.php','shouthistory','height=500,width=480,resizable=yes,scrollbars=yes');
         if (window.focus) {newWin.focus()}
}
function PopMoreSmiles(form,name) {
         link='moresmiles.php?form='+form+'&text='+name
         newWin=window.open(link,'moresmile','height=500,width=400,resizable=yes,scrollbars=yes');
         if (window.focus) {newWin.focus()}
}

</script>
<?php
function clean_shoutbox(){
  $f=@fopen("chat.php","w");
  if($f){
    fwrite($f, "<?php\n?>");
  }
  @fclose($f);
  redirect($_SERVER["PHP_SELF"]);
  exit;

}

function format_shout($text)
{
    global $smilies, $privatesmilies, $BASEURL;

    $s = $text;

    $s = strip_tags($s);

    $s = unesc($s);

    $f=@fopen("badwords.txt","r");
    if ($f && filesize ("badwords.txt")!=0)
       {
       $bw=fread($f,filesize("badwords.txt"));
       $badwords=explode("\n",$bw);
       for ($i=0;$i<count($badwords);++$i)
           $badwords[$i]=trim($badwords[$i]);
       $s = str_replace($badwords,"*censured*",$s);
       }
    @fclose($f);

    // [b]Bold[/b]
    $s = preg_replace("/\[b\]((\s|.)+?)\[\/b\]/", "<b>\\1</b>", $s);

    // [i]Italic[/i]
    $s = preg_replace("/\[i\]((\s|.)+?)\[\/i\]/", "<i>\\1</i>", $s);

    // [u]Underline[/u]
    $s = preg_replace("/\[u\]((\s|.)+?)\[\/u\]/", "<u>\\1</u>", $s);

    // [color=blue]Text[/color]
    $s = preg_replace(
        "/\[color=([a-zA-Z]+)\]((\s|.)+?)\[\/color\]/i",
        "<font color=\\1>\\2</font>", $s);

    // [color=#ffcc99]Text[/color]
    $s = preg_replace(
        "/\[color=(#[a-f0-9][a-f0-9][a-f0-9][a-f0-9][a-f0-9][a-f0-9])\]((\s|.)+?)\[\/color\]/i",
        "<font color=\\1>\\2</font>", $s);

    // [url=http://www.example.com]Text[/url]
    $s = preg_replace(
        "/\[url=((http|ftp|https|ftps|irc):\/\/[^<>\s]+?)\]((\s|.)+?)\[\/url\]/i",
        "<a href=\\1 target=_blank>\\3</a>", $s);

    // [url]http://www.example.com[/url]
    $s = preg_replace(
        "/\[url\]((http|ftp|https|ftps|irc):\/\/[^<>\s]+?)\[\/url\]/i",
        "<a href=\\1 target=_blank>\\1</a>", $s);

    // [size=4]Text[/size]
    $s = preg_replace(
        "/\[size=([1-7])\]((\s|.)+?)\[\/size\]/i",
        "<font size=\\1>\\2</font>", $s);

    // [font=Arial]Text[/font]
    $s = preg_replace(
        "/\[font=([a-zA-Z ,]+)\]((\s|.)+?)\[\/font\]/i",
        "<font face=\"\\1\">\\2</font>", $s);

    // Linebreaks
    $s = nl2br($s);

    // Maintain spacing
    $s = str_replace("  ", " &nbsp;", $s);

    reset($smilies);
    while (list($code, $url) = each($smilies))
        $s = str_replace($code, "<img border=0 src=$BASEURL/images/smilies/$url>", $s);

    reset($privatesmilies);
    while (list($code, $url) = each($privatesmilies))
        $s = str_replace($code, "<img border=0 src=$BASEURL/images/smilies/$url>", $s);

    return $s;
}

function smile() {
?>
<div align="center">
  <table cellpadding="1" cellspacing="1">
  <tr>
  <?php

  global $smilies, $count;
  reset($smilies);

  while ((list($code, $url) = each($smilies)) && $count<20)
        {
        print("\n<td><a href=\"javascript: SmileIT('".str_replace("'","\'",$code)."')\"><img border=0 src=images/smilies/".$url."></a></td>");
        $count++;
        }
  ?>
  </tr>
  </table>
</div>
<?php
}

function safehtml($string)
{
$validcharset=array(
"ISO-8859-1",
"ISO-8859-15",
"UTF-8",
"cp-866",
"cp-1251",
"cp-1252",
"KOI8-R",
"BIG5",
"GB2312",
"BIG5-HKSCS",
"Shift_JIS",
"EUC-JP");

   if (in_array($GLOBALS["charset"],$validcharset))
      return htmlentities($string,ENT_COMPAT,$GLOBALS["charset"]);
   else
       return htmlentities($string);
}

block_begin(SHOUTBOX);
echo "";
$msg = array();
function file_save($filename, $content, $flags = 0)
{if (!($file = fopen($filename, 'w')))
     return FALSE;
$n = fwrite($file, $content);
fclose($file);
return $n ? $n : FALSE;
}

if (!file_exists("chat.php")) file_save("chat.php","<?php\n\$msg = ".var_export($msg,TRUE)."\n?>");

include "chat.php";
/*
$canpost = empty($_POST['submit']) ? 'Refresh' : $_POST['submit'];
$canpost = ($canpost == 'Refresh') ? 0 : 1;
*/
if (!empty($_POST['mess']) && !empty($_POST['pseudo']) && $CURUSER["uid"]>1)
{
  $i = count($msg);
  if ($i == 0) $oldi = 0;
  else $oldi = $i - 1;

  if (!isset($msg[$oldi]['texte']) || $msg[$oldi]['texte'] != htmlsafechars($_POST['mess']))
  {
  $msg[$i]['pseudo'] = htmlsafechars($CURUSER["username"]);
  $msg[$i]['texte'] = htmlsafechars($_POST['mess']);
  $msg[$i]['date'] = time();
  unset ($_POST['pseudo']);
  unset ($_POST['mess']);
  }
}

$msg2 = array_reverse($msg);
echo '<div align="left" class="chat"><table width="95%"  align="center"> <tr><td>';
include("include/offset.php");
for ($i=0;$i<10 && $i<count($msg2);++$i)
{
  $sql="SELECT users.id as uid,prefixcolor,suffixcolor FROM users INNER JOIN users_level ON users_level.id=users.id_level WHERE users.username='".$msg2[$i]['pseudo']."'";
  $res = run_query($sql);
  $result=mysqli_fetch_assoc($res);
  // user or level don't exit in db
  if (!$result)
    echo '<b>'.'</b>&nbsp;&nbsp;&nbsp;['.date("d/m/y H:i",$msg2[$i]['date']-$offset).']'.'&nbsp;&nbsp;<b>'.$msg2[$i]['pseudo'].'</b>:&nbsp;&nbsp;&nbsp;'.format_shout($msg2[$i]['texte']).'<hr>';
  else
  {
    echo '<b>'.'</b>&nbsp;&nbsp;&nbsp;['.date("d/m/y H:i",$msg2[$i]['date']-$offset).']'."&nbsp;&nbsp;<a STYLE='text-decoration:none' href='userdetails.php?id=".$result["uid"]."'>".unesc($result['prefixcolor']).$msg2[$i]['pseudo'].unesc($result['suffixcolor']).'</a>:&nbsp;&nbsp;&nbsp;'.format_shout($msg2[$i]['texte']).'<hr>';
    unset($result);
  }
  ((mysqli_free_result($res) || (is_object($res) && (get_class($res) == "mysqli_result"))) ? true : false);
}
echo "</td></tr></table></div>";

file_save("chat.php", "<?php\n\$msg = ".var_export($msg,TRUE)."\n?>");

unset ($_POST['pseudo']);
unset ($_POST['mess']);

if ($CURUSER["uid"]>1)
{
?>
<div class="miniform" align="center">
<form method="post" name="shout">
<input type="hidden" name="pseudo" value="<?php echo $CURUSER["username"]?>" /><br />
<input name="mess" size="70" maxlength="100" />
<br />
<a href="javascript: PopMoreSmiles('shout','mess')">Emoticons</a> &nbsp; &nbsp; &nbsp;<input name="submit" type="submit" value="<?php echo FRM_CONFIRM; ?>">&nbsp;&nbsp;
<input name="submit" type="submit" value="Refresh">&nbsp;&nbsp;
<?php
$messages = count($msg);
if ($messages > 0){
if ($CURUSER["edit_torrents"]=="yes"){
?>
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
   <input type="submit" name="action" value="Clean" /> &nbsp; &nbsp; &nbsp;<a href="javascript: Pophistory()"><?php echo HISTORY; ?></a>
</form>
<?php
if (isset($_POST['action']) && $_POST['action'] == 'Clean') clean_shoutbox();
}
else {
?>
<a href="javascript: Pophistory()"><?php echo HISTORY; ?></a>
<?php
  }
}
?>
</form>
</div>
<?php
}
else
    print("<div align=\"center\"><a href=\"javascript: Pophistory()\">".HISTORY."</a>\n<br />".ERR_MUST_BE_LOGGED_SHOUT."</div>");
block_end();
?>