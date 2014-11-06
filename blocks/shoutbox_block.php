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
/*
* BtiTracker v1.5.0 is a php tracker system for BitTorrent, easy to setup and configure.
* This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
* Updated and Maintained by Yupy.
* Copyright (C) 2004-2014 Btiteam.org
*/

require_once(INCL_PATH . 'functions.php');

global $db;

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

function clean_shoutbox()
{
    $f = @fopen("chat.php","w");
    if ($f) {
        fwrite($f, "<?php\n?>");
    }
    @fclose($f);
    redirect($_SERVER["PHP_SELF"]);
    exit;
}

function smile()
{
    ?>
    <div align='center'>
    <table cellpadding='1' cellspacing='1'>
    <tr>
    <?php
    
    global $smilies, $count;
    reset($smilies);
    
    while ((list($code, $url) = each($smilies)) && $count < 20) {
        print("\n<td><a href=\"javascript: SmileIT('" . str_replace("'", "\'", $code) . "')\"><img border='0' src='images/smilies/" . $url . "'></a></td>");
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
    $validcharset = array(
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
        "EUC-JP"
    );
    
    if (in_array($GLOBALS["charset"], $validcharset))
        return htmlentities($string, ENT_COMPAT, $GLOBALS["charset"]);
    else
        return htmlentities($string);
}

block_begin(SHOUTBOX);

echo '';

$msg = array();

function file_save($filename, $content, $flags = 0)
{
    if (!($file = fopen($filename, 'w')))
        return false;
    $n = fwrite($file, $content);
    fclose($file);
    return $n ? $n : false;
}

if (!file_exists("chat.php"))
    file_save("chat.php", "<?php\n\$msg = " . var_export($msg, true) . "\n?>");

include "chat.php";

if (!empty($_POST['mess']) && !empty($_POST['pseudo']) && user::$current["uid"] > 1) {
    $i = count($msg);

    if ($i == 0)
        $oldi = 0;
    else
        $oldi = $i - 1;
    
    if (!isset($msg[$oldi]['texte']) || $msg[$oldi]['texte'] != security::html_safe($_POST['mess'])) {
        $msg[$i]['pseudo'] = security::html_safe(user::$current["username"]);
        $msg[$i]['texte']  = security::html_safe($_POST['mess']);
        $msg[$i]['date']   = vars::$timestamp;
        unset($_POST['pseudo']);
        unset($_POST['mess']);
    }
}

$msg2 = array_reverse($msg);

echo "<div align='left' class='chat'><table width='95%' align='center'><tr><td>";

include(INCL_PATH . 'offset.php');
for ($i = 0; $i < 10 && $i < count($msg2); ++$i) {
    $sql    = "SELECT users.id AS uid, prefixcolor, suffixcolor FROM users INNER JOIN users_level ON users_level.id = users.id_level WHERE users.username = '" . $db->real_escape_string($msg2[$i]['pseudo']) . "'";
    $res    = $db->query($sql);
    $result = $res->fetch_assoc();
    // user or level don't exit in db
    if (!$result)
        echo '<b>' . '</b>&nbsp;&nbsp;&nbsp;[' . date("d/m/y H:i", $msg2[$i]['date'] - $offset) . ']' . '&nbsp;&nbsp;<b>' . security::html_safe($msg2[$i]['pseudo']) . '</b>:&nbsp;&nbsp;&nbsp;' . format_comment(security::html_safe($msg2[$i]['texte'])) . '<hr>';
    else {
        echo '<b>' . '</b>&nbsp;&nbsp;&nbsp;[' . date("d/m/y H:i", $msg2[$i]['date'] - $offset) . ']' . "&nbsp;&nbsp;<a style='text-decoration:none' href='userdetails.php?id=" . (int)$result["uid"] . "'>" . unesc($result['prefixcolor']) . security::html_safe($msg2[$i]['pseudo']) . unesc($result['suffixcolor']) . '</a>:&nbsp;&nbsp;&nbsp;' . format_comment(security::html_safe($msg2[$i]['texte'])) . '<hr>';
        unset($result);
    }
    $res->free();
}
echo "</td></tr></table></div>";

file_save("chat.php", "<?php\n\$msg = " . var_export($msg, true) . "\n?>");

unset($_POST['pseudo']);
unset($_POST['mess']);

if (user::$current["uid"] > 1) {
?>
<div class="miniform" align="center">
<form method="post" name="shout">
<input type="hidden" name="pseudo" value="<?php
    echo user::$current["username"];
?>" /><br />
<input name="mess" size="70" maxlength="100" />
<br />
<a href="javascript: PopMoreSmiles('shout','mess')">Emoticons</a> &nbsp; &nbsp; &nbsp;<input name="submit" type="submit" value="<?php
    echo FRM_CONFIRM;
?>">&nbsp;&nbsp;
<input name="submit" type="submit" value="Refresh">&nbsp;&nbsp;
<?php
    $messages = count($msg);
    if ($messages > 0) {
        if (user::$current["edit_torrents"] == "yes") {
?>
<form action="<?php
            echo $_SERVER['PHP_SELF'];
?>" method="post">
   <input type="submit" name="action" value="Clean" /> &nbsp; &nbsp; &nbsp;<a href="javascript: Pophistory()"><?php
            echo HISTORY;
?></a>
</form>
<?php
            if (isset($_POST['action']) && $_POST['action'] == 'Clean')
                clean_shoutbox();
        } else {
?>
<a href="javascript: Pophistory()"><?php
            echo HISTORY;
?></a>
<?php
        }
    }
?>
</form>
</div>
<?php
} else
    print("<div align='center'><a href='javascript: Pophistory()'>" . HISTORY . "</a>\n<br />" . ERR_MUST_BE_LOGGED_SHOUT . "</div>");

block_end();

?>
