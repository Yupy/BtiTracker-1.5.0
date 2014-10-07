<?php

require_once("../include/config.php");

function step ($text = '', $stepname = '', $stepnumber = '') {
    print("<p><table class=lista cellpadding=0 cellspacing=0 width=90% align=center>\n"
        ."\t<tr>\n"
        ."\t\t<td class=block height=20px style='padding: 5px;'><center><b>" . $text . "</b><div align=right>STEP: " . $stepname . " (" . $stepnumber . "/5)</div>
</center></td>\n"
        ."\t</tr>\n"
        ."\t</table></p>\n"
        ."\t<table class=lista cellspacing=0 cellpadding=10 width=90% align=center>\n"
        ."\t<tr>\n"
        ."\t\t<td style='padding: 10px;' class='lista'><div align=justify>");

  }
function tr($x,$y,$noesc=0,$relation='') {
    if ($noesc)
        $a = $y;
    else {
        $a = htmlspecialchars($y);
        $a = str_replace("\n", "<br />\n", $a);
    }
    print("<tr".( $relation ? " relation = \"$relation\"" : "")."><td class=\"header\" valign=\"top\" align=\"right\">$x</td><td valign=\"top\" align=\"left\" class=\"lista\">$a</td></tr>\n");
}
function GetVar ($name) {
    if ( is_array($name) ) {
        foreach ($name as $var) GetVar ($var);
    } else {
        if ( !isset($_REQUEST[$name]) )
            return false;
        if ( get_magic_quotes_gpc() ) {
            $_REQUEST[$name] = ssr($_REQUEST[$name]);
        }
        $GLOBALS[$name] = $_REQUEST[$name];
        return $GLOBALS[$name];
    }
}
function ssr ($arg) {
    if (is_array($arg)) {
        foreach ($arg as $key=>$arg_bit) {
            $arg[$key] = ssr($arg_bit);
        }
    } else {
        $arg = stripslashes($arg);
    }
    return $arg;
}
function WriteConfig ($configname, $config) {
    $configname = basename($configname);
    $path = ROOT_PATH.'include/config.php'.$configname;
    if (!file_exists($path) || !is_writable ($path)) {
        die("<font color=red>Cannot read file [<b>".htmlspecialchars($configname)."</b>]!.</font><br><font color=blue>Before the setup starts, please ensure that you have properly configured file and directory access permissions. Please see below.</font><br><br>chmod 777 CONFIG (config directory).<br>chmod 777 CONFIG/main (the file which save the main settings).");
    }
    $data = @serialize($config);
    if (empty($data)) {
        die("<font color=red>Cannot serialize file [<b>".htmlspecialchars($configname)."</b>]</font><br><font color=blue>Before the setup starts, please ensure that you have properly configured file and directory access permissions. Please see below.</font><br><br>chmod 777 CONFIG (config directory).<br>chmod 777 CONFIG/main (the file which save the main settings).");
    }
    $fp = @fopen ($path, 'w');
    if (!$fp) {
        die("<font color=red>Cannot open file [<b>".htmlspecialchars($configname)."</b>] to save info!.</font><br><font color=blue>Before the setup starts, please ensure that you have properly configured file and directory access permissions. Please see below.</font><br><br>chmod 777 CONFIG (config directory).<br>chmod 777 CONFIG/main (the file which save the main settings).");
    }
    $Res = @fwrite($fp, $data);
    if (empty($Res)) {
        die("<font color=red>Cannot save info in file (error in serialisation) [<b>".htmlspecialchars($configname)."</b>] to save info!.</font><br><font color=blue>Before the setup starts, please ensure that you have properly configured file and directory access permissions. Please see below.</font><br><br>chmod 777 CONFIG (config directory).<br>chmod 777 CONFIG/main (the file which save the main settings).");
    }
    fclose($fp);
    return true;
}
function ReadConfig ($configname) {
    if (strstr($configname, ',')) {
        $configlist = explode(',', $configname);
        foreach ($configlist as $key=>$configname) {
            ReadConfig(trim($configname));
        }
    } else {
        $configname = basename($configname);
        $path = ROOT_PATH.'include/config.php'.$configname;
        if (!file_exists($path)) {
            die("<font color=red>File [<b>".htmlspecialchars($configname)."</b>] doesn't exist!.</font><br><font color=blue>Before the setup starts, please ensure that you have properly configured file and directory access permissions. Please see below.</font><br><br>chmod 777 CONFIG (config directory).<br>chmod 777 CONFIG/main (the file which save the main settings).");
        }
        $fp = fopen($path, 'r');
        $content = '';
        while (!feof($fp)) {
            $content .= fread($fp, 102400);
        }
        fclose($fp);
        if (empty($content)) {
            if ($configname == 'JBOY') {
                Header("Location: index.php");                  
                die; 
            }
            return array();
        }
        $tmp        = @unserialize($content);
        if (empty($tmp)) {
            if ($configname == 'JBOY') {
                Header("Location: index.php");                  
                die;                
            }
            die("<font color=red>Cannot read file [<b>".htmlspecialchars($configname)."</b>]!.</font><br><font color=blue>Before the setup starts, please ensure that you have properly configured file and directory access permissions. Please see below.</font><br><br>chmod 777 CONFIG (config directory).<br>chmod 777 CONFIG/main (the file which save the main settings).");
        }
        $GLOBALS[$configname] = $tmp;
        return true;
    }
}

function dbconn($do_clean=false) {

    global $dbhost, $dbuser, $dbpass, $database, $HTTP_SERVER_VARS;

    if ($GLOBALS["persist"])
        $conres=mysql_pconnect($dbhost, $dbuser, $dbpass);
    else
        $conres=mysql_connect($dbhost, $dbuser, $dbpass);

    if (!$conres)
    {
      switch (mysql_errno())
      {
        case 1040:
        case 2002:
            if ($HTTP_SERVER_VARS[REQUEST_METHOD] == "GET")
                die("<html><head><meta http-equiv=refresh content=\"20 $HTTP_SERVER_VARS[REQUEST_URI]\"></head><body><table border=0 width=100% height=100%><tr><td><h3 align=center>".ERR_SERVER_LOAD."</h3></td></tr></table></body></html>");
            else
                die(ERR_CANT_CONNECT);
        default:
            die("[" . mysql_errno() . "] dbconn: mysql_connect: " . mysql_error());
      }
    }
    mysql_select_db($database)
        or die(ERR_CANT_OPEN_DB." $database - ".mysql_error());
}
function flag_list($with_unknown=false)
{
  $ret = array();
    $res = mysql_query("SELECT * FROM countries ".(!$with_unknown?"WHERE id<>100":"")." ORDER BY name");

    while ($row = mysql_fetch_array($res))
      $ret[] = $row;

    return $ret;
}
function timezone_list()
{
  $ret = array();
    $res = mysql_query("SELECT * FROM timezone");

    while ($row = mysql_fetch_array($res))
      $ret[] = $row;

    return $ret;
}

function mkglobal($vars) {
    if (!is_array($vars))
        $vars = explode(":", $vars);
    foreach ($vars as $v) {
        if (isset($_GET[$v]))
            $GLOBALS[$v] = unesc($_GET[$v]);
        elseif (isset($_POST[$v]))
            $GLOBALS[$v] = unesc($_POST[$v]);
        else
            return 0;
    }
    return 1;
}
function unesc($x) {
    if (get_magic_quotes_gpc())
        return stripslashes($x);
    return $x;
}
function safe_email($email) {   
    $email = str_replace("<","",$email); 
    $email = str_replace(">","",$email); 
    $email = str_replace("\'","",$email); 
    $email = str_replace('\"',"",$email); 
    $email = str_replace("\\\\","",$email); 
    return $email; 
}
function check_email ($email) {
    # Check EMail Function v.02 by xam!
    if(ereg("^([A-Za-z0-9]+_+)|([A-Za-z0-9]+\-+)|([A-Za-z0-9]+\.+)|([A-Za-z0-9]+\++))*[A-Za-z0-9]+@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([a-zA-Z0-9\-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$", $email)) 
        return true;
    else
        return false;
}
function bark($msg) {
    stdmsg("Signup Failed! (See Below)", $msg,false);
    exit;
}
function stdmsg($heading, $text, $htmlstrip = TRUE)
{
    if ($htmlstrip) {
        $heading = htmlspecialchars(trim($heading));
        $text = htmlspecialchars(trim($text));
    }
    print("<table class=main width=737 border=0 cellpadding=0 cellspacing=0><tr><td class=embedded>\n");
        if ($heading)
            print("<h2>$heading</h2>\n");
    print("<table width=100% border=1 cellspacing=0 cellpadding=10><tr><td class=text>\n");
    print($text . "</td></tr></table></td></tr></table>\n");
}
function validusername($username)
{
    if ($username == "")
      return false;

    // The following characters are allowed in user names
    $allowedchars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";

    for ($i = 0; $i < strlen($username); ++$i)
      if (strpos($allowedchars, $username[$i]) === false)
        return false;

    return true;
}
function validemail($email) {
    return preg_match('/^[\w.-]+@([\w.-]+\.)+[a-z]{2,6}$/is', $email);
}
function mksecret($len = 20) {
    $ret = "";
    for ($i = 0; $i < $len; $i++)
        $ret .= chr(mt_rand(0, 255));
    return $ret;
}

?>