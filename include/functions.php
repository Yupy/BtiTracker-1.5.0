<?php

#
// Emulate register_globals off
#
if (ini_get('register_globals')) {
  $superglobals = array($_SERVER, $_ENV,$_FILES, $_COOKIE, $_POST, $_GET);
  if (isset($_SESSION)) {
      array_unshift($superglobals, $_SESSION);
  }
  foreach ($superglobals as $superglobal) {
      foreach ($superglobal as $global => $value) {
          unset($GLOBALS[$global]);
      }
  }
  @ini_set('register_globals', false);
}


$tracker_version="1.5.0";

// CHECK FOR INSTALLATION FOLDER WITHOUT INSTALL.ME
if (file_exists("install") && !file_exists("install.me"))
{
  $err_msg_install=("<div align=\"center\" style=\"color:red; font-size:12pt; font-weight: bold;\">SECURITY WARNING: Delete install folder!</div>");
}

error_reporting(E_ALL ^ E_NOTICE);

$CURRENTPATH = dirname(__FILE__);

require_once("$CURRENTPATH/config.php");
require_once("$CURRENTPATH/common.php");
// protection against sql injection, xss attack
require_once("$CURRENTPATH/crk_protection.php");
// protection against sql injection, xss attack
require_once("$CURRENTPATH/smilies.php");
require_once("$CURRENTPATH/defines.php");
//Fetch classes
require_once("classes/class.Captcha.php");
require_once("classes/class.Memcache.php");

$Memcached = new Memcached();

// default for disabling DHT network
if (!isset($DHT_PRIVATE))
    $DHT_PRIVATE=true;
if (!isset($LIVESTATS))
    $LIVESTATS=false;
if (!isset($LOG_ACTIVE))
    $LOG_ACTIVE=false;
if (!isset($LOG_HISTORY))
    $LOG_HISTORY=false;
if (!isset($GZIP_ENABLED))
    $GZIP_ENABLED=false;
if (!isset($PRINT_DEBUG))
    $PRINT_DEBUG=true;
if (!isset($USE_IMAGECODE))
    $USE_IMAGECODE=true;
if (!isset($TRACKER_ANNOUNCEURLS))
    {
    $TRACKER_ANNOUNCEURLS=array();
    $TRACKER_ANNOUNCEURLS[]="$BASEURL/announce.php";
    }

function get_microtime(){
    list($usec, $sec) = explode(" ",microtime());
    return ((float)$usec + (float)$sec);
    }


function print_version()
{

  GLOBAL $time_start, $gzip, $PRINT_DEBUG,$tracker_version;

  $time_end = get_microtime();
  $max_mem = memory_get_peak_usage();
  print("<p align='center'>");
  if ($PRINT_DEBUG)
        print("[ Execution time: ".number_format(($time_end-$time_start),4)." sec. ] - [Memory usage: ".makesize($max_mem)."] - [ GZIP: ".$gzip." ]<br />");
  print("BtiTracker ($tracker_version) by Yupy & <a href=\"http://www.btiteam.org\">Btiteam</a></p>");

}

//Disallow special characters in username

function straipos($haystack,$array,$offset=0)
{
   $occ = Array();
   for ($i = 0;$i<sizeof($array);$i++)
   {
       $pos = strpos($haystack,$array[$i],$offset);
       if (is_bool($pos)) continue;
       $occ[$pos] = $i;
   }
   if (sizeof($occ)<1) return false;
   ksort($occ);
   reset($occ);
   list($key,$value) = each($occ);
   return array($key,$value);
}
//EOF

//////////////////////////////////////////////////////////////////
// Worker functions

if (function_exists("bcadd"))
{
    function sqlAdd($left, $right)
    {
        return bcadd($left, $right,0);
    }
    function sqlSubtract($left, $right)
    {
        return bcsub($left, $right,0);
    }
    function sqlMultiply($left, $right)
    {
        return bcmul($left, $right,0);
    }
    function sqlDivide($left, $right)
    {
        return bcdiv($left, $right,0);
    }
}
else // BC vs SQL math
{

// Uses the mysql database connection to perform string math. :)
// Used by byte counting functions
// No error handling as we assume nothing can go wrong. :|
function sqlAdd($left, $right)
{
    $query = 'SELECT '.$left.'+'.$right;
    $results = run_query($query) or showError(DATABASE_ERROR);
    return mysqli_result($results,0,0);
}

// Ditto
function sqlSubtract($left, $right)
{
    $query = 'SELECT '.$left.'-'.$right;
    $results = run_query($query) or showError(DATABASE_ERROR);
    return mysqli_result($results,0,0);
}

function sqlDivide($left, $right)
{
    $query = 'SELECT '.$left.'/'.$right;
    $results = run_query($query) or showError(DATABASE_ERROR);
    return mysqli_result($results,0,0);
}

function sqlMultiply($left, $right)
{
    $query = 'SELECT '.$left.'*'.$right;
    $results = run_query($query) or showError(DATABASE_ERROR);
    return mysqli_result($results,0,0);
}

} // End of BC vs SQL



// Used by newtorrents.php and the dynamic_torrents setting
// Returns true/false, depending on if there were errors.
function makeTorrent($hash, $tolerate = false)
{

    if (strlen($hash) != 40)
        showError(MKTOR_INVALID_HASH);
    $result = true;
    // new with domain suffix and client

    if (!$result && !$tolerate)
        return false;
    if (isset($GLOBALS["peercaching"]) && $GLOBALS["peercaching"])
    {
    //
    }
    $query = "INSERT INTO summary set info_hash=\"".$hash."\", lastSpeedCycle=UNIX_TIMESTAMP()";
    if (!@run_query($query))
        $result = false;
    return $result;
}


// Slight redesign of loadPeers
function getRandomPeers($hash, $where="")
{
    // Don't want to send a bad "num peers" for new seeds

    $where="WHERE infohash=\"$hash\"";

    if ($GLOBALS["NAT"])
        $results = run_query("SELECT COUNT(*) FROM peers WHERE natuser = 'N' AND infohash=\"$hash\"");
    else
        $results = run_query("SELECT COUNT(*) FROM peers WHERE infohash=\"$hash\"");

    $peercount = mysqli_result($results, 0,0);

    // ORDER BY RAND() is expensive. Don't do it when the load gets too high
    if ($peercount < 500)
        $query = "SELECT ".((isset($_GET["no_peer_id"]) && $_GET["no_peer_id"] == 1) ? "" : "peer_id,")."ip, port, status FROM peers ".$where." ORDER BY RAND() LIMIT ${GLOBALS['maxpeers']}";
    else
        $query = "SELECT ".((isset($_GET["no_peer_id"]) && $_GET["no_peer_id"] == 1) ? "" : "peer_id,")."ip, port, status FROM peers ".$where." LIMIT ".@mt_rand(0, $peercount - $GLOBALS["maxpeers"]).", ${GLOBALS['maxpeers']}";

    $results = run_query($query);
    if (!$results)
        return false;

    $peerno = 0;
    while ($return[] = mysqli_fetch_assoc($results))
        $peerno++;

    array_pop ($return);
    ((mysqli_free_result($results) || (is_object($results) && (get_class($results) == "mysqli_result"))) ? true : false);
    $return['size'] = $peerno;

    return $return;
}


// Updates the peer user's info.
// Currently it does absolutely nothing. lastupdate is set in collectBytes
// as well.
function updatePeer($peerid, $hash)
{
}


// Transmits the actual data to the peer. No other output is permitted if
// this function is called, as that would break BEncoding.
// I don't use the bencode library, so watch out! If you add data,
// rules such as dictionary sorting are enforced by the remote side.
function sendPeerList($peers)
{
    echo "d";
    echo "8:intervali".$GLOBALS["report_interval"]."e";
    if (isset($GLOBALS["min_interval"]))
        echo "12:min intervali".$GLOBALS["min_interval"]."e";
    echo "5:peers";
    $size=$peers["size"];
    if (isset($_GET["compact"]) && $_GET["compact"] == '1')
    {
        $p = '';
        for ($i=0; $i < $size; $i++)
            $p .= str_pad(pack("Nn", ip2long($peers[$i]['ip']), $peers[$i]['port']), 6);
        echo strlen($p).':'.$p;
    }
    else // no_peer_id or no feature supported
    {
        echo 'l';
        for ($i=0; $i < $size; $i++)
        {
            echo "d2:ip".strlen($peers[$i]["ip"]).":".$peers[$i]["ip"];
            if (isset($peers[$i]["peer_id"]))
                echo "7:peer id20:".hex2bin($peers[$i]["peer_id"]);
            echo "4:port".$peers[$i]["port"]."ee";
        }
        echo "e";
    }
    if (isset($GLOBALS["trackerid"]))
    {
        // Now it gets annoying. trackerid is a string
        echo "10:tracker id".strlen($GLOBALS["trackerid"]).":".$GLOBALS["trackerid"];
    }
    echo "e";
}


// Returns a $peers array of all peers that have timed out (2* report interval seems fair
// for any reasonable report interval (900 or larger))
function loadLostPeers($hash, $timeout)
{
    $results = run_query("SELECT peer_id,bytes,ip,port,status,lastupdate,sequence from peers WHERE infohash=\"$hash\" AND lastupdate < (UNIX_TIMESTAMP() - 2 * $timeout)");
    $peerno = 0;
    if (!$results)
        return false;

    while ($return[] = mysqli_fetch_assoc($results))
        $peerno++;
    array_pop($return);
    $return["size"] = $peerno;
    ((mysqli_free_result($results) || (is_object($results) && (get_class($results) == "mysqli_result"))) ? true : false);
    return $return;
}

function trashCollector($hash, $timeout)
{
    if (isset($GLOBALS["trackerid"]))
        unset($GLOBALS["trackerid"]);

    if (!Lock($hash))
        return;

    $results = run_query("SELECT lastcycle FROM summary WHERE info_hash='$hash'");
    $lastcheck = (mysqli_fetch_row($results));

    // Check once every re-announce cycle
    if (($lastcheck[0] + $timeout) < time())
    {
        $peers = loadLostPeers($hash, $timeout);
        for ($i=0; $i < $peers["size"]; $i++)
            killPeer($peers[$i]["peer_id"], $hash, $peers[$i]["bytes"]);
        summaryAdd("lastcycle", "UNIX_TIMESTAMP()", true);
    }
    Unlock($hash);
}

// Attempts to aquire a lock by name.
// Returns true on success, false on failure
function Lock($hash, $time = 0)
{
    $results = run_query("SELECT GET_LOCK('$hash', $time)");
    $string = mysqli_fetch_row($results);
    if (strcmp($string[0], "1") == 0)
        return true;
    return false;

}

// Releases a lock. Ignores errors.
function Unlock($hash)
{
    quickQuery("SELECT RELEASE_LOCK('$hash')");
}

// Returns true if the lock is available
function isFreeLock($lock)
{
    if (Lock($lock, 0))
    {
        Unlock($lock);
        return true;
    }
    return false;
}


// It's cruel, but if people abuse my tracker, I just might do it.
// It pretends to accept the torrent, and reports that you are the
// only person connected.
function evilReject($ip, $peer_id, $port)
{

    // For those of you who are feeling evil, comment out this line.
    showError("Torrent is not authorized for use on this tracker.");

    $peers[0]["peer_id"] = $peer_id;
    $peers[0]["ip"] = $ip;
    $peers[0]["port"] = $port;
    $peers["size"] = 1;
    $GLOBALS["report_interval"] = 86400;
    $GLOBALS["min_interval"] = 86000;
    sendPeerList($peers);
    exit(0);
}


// Even if you're missing PHP 4.3.0, the MHASH extension might be of use.
// Someone was kind enought to email this code snippit in.
if (function_exists('mhash') && (!function_exists('sha1')) &&
defined('MHASH_SHA1'))
{
    function sha1($str)
    {
        return bin2hex(mhash(MHASH_SHA1,$str));
    }
}

// begin of function added from original

function unesc($x) {
    if (get_magic_quotes_gpc())
        return stripslashes($x);
    return $x;
}

function mksecret($len = 20) {
    $ret = "";
    for ($i = 0; $i < $len; $i++)
        $ret .= chr(mt_rand(0, 255));
    return $ret;
} 

function hashit($var,$addtext="")
{
   return md5("N8rbxEX7".$addtext.$var.$addtext."Ystkyi6xSRYOTKJU3AmJ1D2");
} 

function logincookie($id, $passhash, $expires = 0x7fffffff)
{
    setcookie("uid", $id, $expires, "/");
    setcookie("pass", $passhash, $expires, "/"); 
	setcookie( "hashx", hashit($id,$passhash), $expires, "/"); 
}

function logoutcookie() {
    setcookie("uid", "", 0x7fffffff, "/");
    setcookie("pass", "", 0x7fffffff, "/"); 
	setcookie("hashx", "", 0x7fffffff, "/"); 
}

function hash_pad($hash) {
    return str_pad($hash, 20);
} 


function userlogin() {
    global $CURUSER;
    unset($GLOBALS["CURUSER"]);


    $ip = getip(); //$_SERVER["REMOTE_ADDR"];
    $nip = ip2long($ip);
    $res = run_query("SELECT * FROM bannedip WHERE '$nip' >= first AND '$nip' <= last") or sqlerr(__FILE__, __LINE__);
    if (mysqli_num_rows($res) > 0)
    {
      header("HTTP/1.0 403 Forbidden");
      print("<html><body><h1>403 Forbidden</h1>Unauthorized IP address.</body></html>\n");
      die;
    }

    // guest
    if (empty($_COOKIE["uid"]) || empty($_COOKIE["pass"]))
       $id=1;

    if (!isset($_COOKIE["uid"])) $_COOKIE["uid"] = 1;
     $id = max(1 ,(int)$_COOKIE["uid"]); 
    // it's guest
    if (!$id)
       $id=1;

    $res = run_query("SELECT users.topicsperpage, users.postsperpage,users.torrentsperpage, users.flag, users.avatar, UNIX_TIMESTAMP(users.lastconnect) AS lastconnect, UNIX_TIMESTAMP(users.joined) AS joined, users.id as uid, users.username, users.password, users.random, users.email, users.language,users.style, users.time_offset, users_level.* FROM users INNER JOIN users_level ON users.id_level=users_level.id WHERE users.id = $id") or die(((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)));
    $row = mysqli_fetch_array($res);
    if (!$row)
       {
       $id=1;
       $res = run_query("SELECT users.topicsperpage, users.postsperpage,users.torrentsperpage, users.flag, users.avatar, UNIX_TIMESTAMP(users.lastconnect) AS lastconnect, UNIX_TIMESTAMP(users.joined) AS joined, users.id as uid, users.username, users.password, users.random, users.email, users.language,users.style, users.time_offset, users_level.* FROM users INNER JOIN users_level ON users.id_level=users_level.id WHERE users.id = 1");
       $row = mysqli_fetch_array($res);
       }
    if (!isset($_COOKIE["pass"])) $_COOKIE["pass"] = "";
    if (($_COOKIE["pass"] != md5($GLOBALS["salting"].$row["random"].$row["password"].$row["random"])) && $id != 1) 
       {
       $id=1;
       $res = run_query("SELECT users.topicsperpage, users.postsperpage,users.torrentsperpage, users.flag, users.avatar, UNIX_TIMESTAMP(users.lastconnect) AS lastconnect, UNIX_TIMESTAMP(users.joined) AS joined, users.id as uid, users.username, users.password, users.random, users.email, users.language,users.style, users.time_offset, users_level.* FROM users INNER JOIN users_level ON users.id_level=users_level.id WHERE users.id = 1");
       $row = mysqli_fetch_array($res);
       }

    if ($id>1)
       run_query("UPDATE users SET lastconnect=NOW(), lip=".$nip.", cip='".AddSlashes($ip)."' WHERE id = $id");
    else
        run_query("UPDATE users SET lastconnect=NOW(), lip=0, cip=NULL WHERE id = 1");
    $GLOBALS["CURUSER"] = $row;
    unset($row);

}

function dbconn($do_clean=false) {

    global $dbhost, $dbuser, $dbpass, $database, $HTTP_SERVER_VARS;

    if ($GLOBALS["persist"])
        $conres=($GLOBALS["___mysqli_ston"] = mysqli_connect($dbhost,  $dbuser,  $dbpass));
    else
        $conres=($GLOBALS["___mysqli_ston"] = mysqli_connect($dbhost,  $dbuser,  $dbpass));

    if (!$conres)
    {
      switch (((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_errno($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_errno()) ? $___mysqli_res : false)))
      {
        case 1040:
        case 2002:
            if ($HTTP_SERVER_VARS[REQUEST_METHOD] == "GET")
                die("<html><head><meta http-equiv=refresh content=\"20 $HTTP_SERVER_VARS[REQUEST_URI]\"></head><body><table border=0 width=100% height=100%><tr><td><h3 align=center>".ERR_SERVER_LOAD."</h3></td></tr></table></body></html>");
            else
                die(ERR_CANT_CONNECT);
        default:
            die("[" . ((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_errno($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_errno()) ? $___mysqli_res : false)) . "] dbconn: mysql_connect: " . ((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)));
      }
    }
    ((bool)mysqli_query($GLOBALS["___mysqli_ston"], "USE $database"))
        or die(ERR_CANT_OPEN_DB." $database - ".((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)));

    userlogin();

    if ($do_clean)
       register_shutdown_function("cleandata");
}

function cleandata() {

    global $CURRENTPATH;

    require_once("$CURRENTPATH/sanity.php");

    global $clean_interval;

    if ((0+$clean_interval)==0)
       return;

    $now = time();

    $res = run_query("SELECT last_time FROM tasks WHERE task='sanity'");
    $row = mysqli_fetch_array($res);
    if (!$row) {
        run_query("INSERT INTO tasks (task, last_time) VALUES ('sanity',$now)");
        return;
    }
    $ts = $row[0];
    if ($ts + $clean_interval > $now)
        return;
    run_query("UPDATE tasks SET last_time=$now WHERE task='sanity' AND last_time = $ts");
    if (!mysqli_affected_rows($GLOBALS["___mysqli_ston"]))
        return;

    do_sanity();

}

function updatedata() {

    global $CURRENTPATH;

    require_once("$CURRENTPATH/getscrape.php");

    global $update_interval;

    if ((0+$update_interval)==0)
       return;

    $now = time();

    $res = @run_query("SELECT last_time FROM tasks WHERE task='update'");
    $row = @mysqli_fetch_array($res);
    if (!$row) {
        run_query("INSERT INTO tasks (task, last_time) VALUES ('update',$now)");
        return;
    }
    $ts = $row[0];
    if ($ts + $update_interval > $now)
        return;

    run_query("UPDATE tasks SET last_time=$now WHERE task='update' AND last_time = $ts");
    if (!mysqli_affected_rows($GLOBALS["___mysqli_ston"]))
        return;

    // new control time is lastupdate (before the current one) - update interval
    $ts=$ts-$update_interval;

    $res = @run_query("SELECT announce_url FROM namemap WHERE external='yes' AND UNIX_TIMESTAMP(lastupdate)<$ts ORDER BY lastupdate ASC LIMIT 1");
    if (!$res || mysqli_num_rows($res)==0)
       return;

    // get the url to scrape, take 5 torrent at a time (try to getting multiscrape)
    $row = mysqli_fetch_row($res);

    $resurl=@run_query("SELECT info_hash FROM namemap WHERE external='yes' AND UNIX_TIMESTAMP(lastupdate)<$ts AND announce_url='".$row[0]."' ORDER BY lastupdate DESC LIMIT 5");
    if (!$resurl || mysqli_num_rows($resurl)==0)
        return

    $combinedinfohash=array();
    while ($rhash=mysqli_fetch_row($resurl))
        $combinedinfohash[]=$rhash[0];

    scrape($row[0],implode("','",$combinedinfohash));

}

function pager($rpp, $count, $href, $opts = array()) {

    if($rpp!=0) $pages = ceil($count / $rpp);
    else $pages=0;

    if (!isset($opts["lastpagedefault"]))
        $pagedefault = 0;
    else {
        $pagedefault = floor(($count - 1) / $rpp);
        if ($pagedefault < 0)
            $pagedefault = 0;
    }

    $pagename="page";

    if (isset($opts["pagename"]))
      {
       $pagename=$opts["pagename"];
       if (isset($_GET[$opts["pagename"]]))
          $page = max(0 ,(int)$_GET[$opts["pagename"]]);
       else
          $page = $pagedefault;
      }
    elseif (isset($_GET["page"])) {
        $page = 0 + $_GET["page"];
        if ($page < 0)
            $page = $pagedefault;
    }
    else
        $page = $pagedefault;

    $pager = "";

    $mp = $pages - 1;
    $as = "<b>&lt;&lt;&nbsp;".PREVIOUS."</b>";
    if ($page >= 1) {
        $pager .= "<a href=\"{$href}$pagename=" . ($page - 1) . "\">";
        $pager .= $as;
        $pager .= "</a>";
    }
    else
        $pager .= $as;

    $pager .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";

    $as = "<b>".NEXT."&nbsp;&gt;&gt;</b>";

    if ($page < $mp && $mp >= 0) {
        $pager .= "<a href=\"{$href}$pagename=" . ($page + 1) . "\">";
        $pager .= $as;
        $pager .= "</a>";
    }
    else
        $pager .= $as;

    if ($count) {
        $pagerarr = array();
        $dotted = 0;
        $dotspace = 3;
        $dotend = $pages - $dotspace;
        $curdotend = $page - $dotspace;
        $curdotstart = $page + $dotspace;
        for ($i = 0; $i < $pages; $i++) {
            if (($i >= $dotspace && $i <= $curdotend) || ($i >= $curdotstart && $i < $dotend)) {
                if (!$dotted)
                    $pagerarr[] = "...";
                $dotted = 1;
                continue;
            }
            $dotted = 0;
            $start = $i * $rpp + 1;
            $end = $start + $rpp - 1;
            if ($end > $count)
                $end = $count;

            $text = "$start&nbsp;-&nbsp;$end";
            if ($i != $page)
                $pagerarr[] = "<a href=\"{$href}$pagename=$i\">$text</a>";
            else
                $pagerarr[] = "<b>$text</b>";
        }

        $pagerstr = join(" | ", $pagerarr);
        $pagertop = "<p align=\"center\">$pager<br />$pagerstr</p>\n";
        $pagerbottom = "<p align=\"center\">$pagerstr<br />$pager</p>\n";
    }
    else {
        $pagertop = "<p align=\"center\">$pager</p>\n";
        $pagerbottom = $pagertop;
    }

    $start = $page * $rpp;
    return array($pagertop, $pagerbottom, "LIMIT $start,$rpp");

}

// give back categories recorset
function genrelist()
{
    global $Memcached;
	
    $Key = 'Genrelist::';
 if (($ret = $Memcached->get_value($Key)) == false) {
    $ret = array();
    $res = run_query("SELECT * FROM categories ORDER BY sort_index, id");

    while ($row = mysqli_fetch_array($res))
        $ret[] = $row;
    $Memcached->cache_value($Key, $ret, 30 * 86400);
    }

    return $ret;
}
// this returns all the categories
function categories($val="")
{
    echo "<select name='category'><option value='0'>----</option>";
    $c_q = @run_query("SELECT * FROM categories WHERE sub='0' ORDER BY id ASC");
    while($c = mysqli_fetch_array($c_q))
    {
        $cid = $c["id"];
        $name = unesc($c["name"]);
        // lets see if it has sub-categories.
        $s_q = run_query("SELECT * FROM categories WHERE sub='$cid'");
        $s_t = mysqli_num_rows($s_q);
        if($s_t == 0)
        {
            $checked = "";
            if($cid == $val){ $checked = "selected"; }
            echo "<option $checked value='$cid'>$name</option>";
        } else {
            echo "<optgroup label='$name'>";
            while($s = mysqli_fetch_array($s_q))
            {
                $sub = $s["id"];
                $name  = $s["name"];
                $checked = "";
                if($sub == $val){ $checked = "selected"; }
                echo "<option $checked value='$sub'>$name</option>";
            }
            echo "</optgroup>";
        }
    }
    echo "</select>";
}
// this returns all the subcategories
function sub_categories($val="")
{
    echo "<select name='sub_category'><option value='0'>---</option>";
    $c_q = @run_query("SELECT * FROM categories WHERE sub='0' ORDER BY id ASC");
    while($c = mysqli_fetch_array($c_q))
    {
        $cid = $c["id"];
        $name = unesc($c["name"]);
        $selected = ($cid == $val)?"selected":"";
        echo "<option $selected value='$cid'>$name</option>";
    }
    echo "</select>";
}
// this returns the category of a sub-category
function sub_cat($sub)
{
    global $Memcached;

    $Key = 'Subcat::';
 if (($name = $Memcached->get_value($Key)) == false) {
    $c_q = @mysqli_fetch_array( @run_query("SELECT name FROM categories WHERE id = '".$sub."'") );
    $name = unesc($c_q["name"]);
    $Memcached->cache_value($Key, $name, 30 * 86400);
    }
    return $name;
}

function style_list()
{
    global $Memcached;

    $Key = 'Stylelist::';
 if (($ret = $Memcached->get_value($Key)) == false) {
    $ret = array();
    $res = run_query("SELECT * FROM style ORDER BY id");

    while ($row = mysqli_fetch_array($res))
        $ret[] = $row;
    $Memcached->cache_value($Key, $ret, 30 * 86400);
    }
    return $ret;
}

function language_list()
{
    global $Memcached;

    $Key = 'Languagelist::';
 if (($ret = $Memcached->get_value($Key)) == false) {
    $ret = array();
    $res = run_query("SELECT * FROM language ORDER BY language");

     while ($row = mysqli_fetch_array($res))
        $ret[] = $row;
    $Memcached->cache_value($Key, $ret, 30 * 86400);
    }
    return $ret;
}

function flag_list($with_unknown=false)
{
  $ret = array();
    $res = run_query("SELECT * FROM countries ".(!$with_unknown?"WHERE id<>100":"")." ORDER BY name");

    while ($row = mysqli_fetch_array($res))
      $ret[] = $row;

    return $ret;
}

function timezone_list()
{
  $ret = array();
    $res = run_query("SELECT * FROM timezone");

    while ($row = mysqli_fetch_array($res))
      $ret[] = $row;

    return $ret;
}

function stdfoot($normalpage=true, $update=true) {

    global $STYLEPATH; 
	
    if ($normalpage) include($STYLEPATH."/footer.php");

    print_version();
    print("</body>\n</html>\n");

    if ($update)
        register_shutdown_function("updatedata");

}

function linkcolor($num) {

    if (!$num)
        return "red";
    if ($num == 1)
        return "yellow";

    return "green";
}

function format_quote($text)
{
  $string=$text;
  $prev_string = "";
  while ($prev_string != $string)
        {
    $prev_string = $string;
    $string = preg_replace("/\[quote\]\s*((\s|.)+?)\s*\[\/quote\]\s*/i", "<br /><b>".QUOTE.":</b><br /><table width=\"100%\" border=\"1\" cellspacing=\"0\" cellpadding=\"10\" class=\"lista\"><tr><td >\\1</td></tr></table><br />", $string);
    $string = preg_replace("/\[quote=(.+?)\]\s*((\s|.)+?)\s*\[\/quote\]\s*/i", "<br /><b>\${1} ".WROTE.":</b><br /><table width=\"100%\" border=\"1\" cellspacing=\"0\" cellpadding=\"10\" class=\"lista\"><tr><td>\\2</td></tr></table><br />", $string);
    // code
    $string = preg_replace("/\[code\]\s*((\s|.)+?)\s*\[\/code\]\s*/i", "<br /><b>Code</b><br /><table width=\"100%\" border=\"1\" cellspacing=\"0\" cellpadding=\"10\" class=\"lista\"><tr><td>\\1</td></tr></table><br />", $string);

  }

return $string;
}

function format_comment($text, $strip_html = true)
{
    global $smilies, $privatesmilies, $BASEURL;

    $s = $text;

    if ($strip_html)
        $s = htmlsafechars($s);

    $s = unesc($s);

    $f=@fopen("badwords.txt","r");
    if ($f && filesize ("badwords.txt")!=0)
       {
       $bw=fread($f,filesize("badwords.txt"));
       $badwords=explode("\n",$bw);
       for ($i=0;$i<count($badwords);++$i)
           $badwords[$i]=trim($badwords[$i]);
       $s = str_replace($badwords,"*censored*",$s);
       }
    @fclose($f);

    // [*]
    $s = preg_replace("/\[\*\]/", "<li>", $s);

    // [b]Bold[/b]
    $s = preg_replace("#\[b\](.*?)\[/b\]#si", "<b>\\1</b>", $s);
    $s = preg_replace("#\[B\](.*?)\[/B\]#si", "<b>\\1</b>", $s);

    // [i]Italic[/i]
    $s = preg_replace("#\[i\](.*?)\[/i\]#si", "<i>\\1</i>", $s);
    $s = preg_replace("#\[I\](.*?)\[/I\]#si", "<i>\\1</i>", $s);

    // [u]Underline[/u]
    $s = preg_replace("#\[u\](.*?)\[/u\]#si", "<u>\\1</u>", $s);
    $s = preg_replace("#\[U\](.*?)\[/U\]#si", "<u>\\1</u>", $s);

    // [img]http://www/image.gif[/img]
    $s = preg_replace("/\[img\](http:\/\/[^\s'\"<>]+(\.gif|\.jpg|\.png))\[\/img\]/i", "<img border=0 src=\"\\1\">", $s);

    // [img=http://www/image.gif]
    $s = preg_replace("/\[img=(http:\/\/[^\s'\"<>]+(\.gif|\.jpg|\.png))\]/i", "<img border=0 src=\"\\1\">", $s);

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

    $s=format_quote($s);

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

function image_or_link($image,$style="",$link="")
{
  if ($image=="")
      return $link;
  elseif (file_exists($image))
     return "<img src=$image border=0 $style alt=\"$link\"/>";
  else
      return $link;
}

function standardheader($title,$normalpage=true,$idlang=0) {

    global $CURUSER, $SITENAME, $STYLEPATH, $USERLANG, $time_start, $gzip, $GZIP_ENABLED, $err_msg_install;

    $time_start = get_microtime();

    // default settings for blocks/menu
    if (!isset($GLOBALS["charset"]))
       $GLOBALS["charset"] = "iso-8859-1";

    // controll if client can handle gzip
    if ($GZIP_ENABLED)
        {
         if (stristr($_SERVER["HTTP_ACCEPT_ENCODING"],"gzip") && extension_loaded('zlib') && ini_get("zlib.output_compression") == 0)
             {
             if (ini_get('output_handler')!='ob_gzhandler')
                 {
                 ob_start("ob_gzhandler");
                 $gzip='enabled';
                 }
             else
                 {
                 ob_start();
                 $gzip='enabled';
                 }
         }
         else
             {
             ob_start();
             $gzip='disabled';
             }
    }
    else
        $gzip='disabled';

    header("Content-Type: text/html; charset=".$GLOBALS["charset"]);

    if ($title == "")
        $title = unesc($SITENAME);
    else
        $title = unesc($SITENAME) . " - " . htmlsafechars($title);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html><head>
<title><?php echo $title; ?></title>
<?php
// get user's style
$resheet=run_query("SELECT * FROM style where id=".$CURUSER["style"]."");
if (!$resheet)
   {

   $STYLEPATH="./style/base";
   $style="./style/base/torrent.css";
   }
else
    {
        $resstyle=mysqli_fetch_array($resheet);
        $STYLEPATH=$resstyle["style_url"];
        $style=$resstyle["style_url"]."/torrent.css";
    }
print("<link rel=\"stylesheet\" href=$style type=\"text/css\" />");
?>
</head>
<body>
<?php

// getting user language
if ($idlang==0)
   $reslang=run_query("SELECT * FROM language WHERE id=".$CURUSER["language"]);
else
   $reslang=run_query("SELECT * FROM language WHERE id=$idlang");

if (!$reslang)
   {
   $USERLANG="language/english.php";
   }
else
    {
        $rlang=mysqli_fetch_array($reslang);
        $USERLANG="".$rlang["language_url"];
    }

clearstatcache();

if (!file_exists($USERLANG))
    {
    err_msg("Error!","Missing Language!");
    print_version();
    print("</body>\n</html>\n");
    die;
}

require_once($USERLANG);

if (!file_exists($style))
    {
    err_msg("Error!","Missing Style!");
    print_version();
    print("</body>\n</html>\n");
    die;
}


if ($normalpage)
   require_once($STYLEPATH."/header.php");

echo $err_msg_install;

}

function err_msg($heading="Error!",$string)
{
 // just in case not found the language
 if (!defined("BACK"))
      define("BACK","Back");

 print("<div align=\"center\"><br /><table border=\"0\" width=\"500\" cellspacing=\"0\" cellpadding=\"0\"><tr>\n");
 print("<td bgcolor=\"#FFFFFF\" align=\"center\" style=\"border-style: dotted; border-width: 1px\" bordercolor=\"#CC0000\">\n");
 print("<font color=\"#CC0000\"><b>$heading</b><br />$string<br /></font></td>\n");
 print("</tr></table></div><br />\n");
 print("<center><a href=javascript:history.go(-1)>".BACK."</a></center>");
}

function htmlsafechars($data, $onlyspecialchars = false, $strip_invalid = true)
{
    if (!is_scalar($data))
        return '';

    $invalid_chars = array("\x00","\x01","\x02","\x03","\x04","\x05","\x06","\x07","\x08","\x0b","\x0c",
        "\x0e","\x0f","\x10","\x11","\x12","\x13","\x14","\x15","\x16","\x17","\x18","\x19","\x1a","\x1b",
        "\x1c","\x1d","\x1e","\x1f");

    if ($onlyspecialchars)
        $data = htmlspecialchars($data, ENT_QUOTES, "UTF-8");
    else
        $data = htmlentities($data, ENT_QUOTES, "UTF-8");
    if ($strip_invalid)
        $data = str_replace($invalid_chars, '', $data);

    return $data;
} 

function sqlesc($x) {
    return "'".((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], unesc($x)) : ((trigger_error("Error...", E_USER_ERROR)) ? "" : ""))."'";
}

function print_news($limit=0)
         {

         global $CURUSER, $limitqry, $adm_menu, $CURRENTPATH;
     $output="";

         $model="<table cellpadding=\"4\" cellspacing=\"1\" border=\"0\" width=\"100%\" bgcolor=\"#000000\" style=\"font-family:Verdana;font-size:11px\">"
             ."\n{admin_menu}"
             ."\n<tr><td class=\"header\" align=\"center\">".POSTED_BY.": {user_name}"
             ."\n<br>".POSTED_DATE.": {news_date}"
             ."\n</td></tr><tr><td class=\"lista\" align=\"center\">"
             ."\n<b>".TITLE.": {news_title}</b><br><br>"
             ."\n<table style=\"border-top:1px\" solid gray;width:100%;font-family:Verdana;font-size:10px'>"
             ."\n<tr><td>{news}</td></tr>"
             ."\n</table></td></tr></table><br>";
         if ($limit>0)
            $limitqry="LIMIT $limit";
         $res=run_query("select news.id, news.title,news.news,UNIX_TIMESTAMP(news.date) as news_date,users.username FROM news INNER JOIN users on users.id=news.user_id ORDER BY date DESC $limitqry ");
         while ($rows=mysqli_fetch_array($res))
               {
               if ($CURUSER["edit_news"]=="yes" || $CURUSER["delete_news"]=="yes")
                  $adm_menu="<tr><td class=header align=center>";
               if ($CURUSER["edit_news"]=="yes")
                  $adm_menu.="<a href=news.php>".ADD."</a>&nbsp;&nbsp;&nbsp;<a href=news.php?act=edit&id=".$rows["id"].">".EDIT."</a>";
               if ($CURUSER["delete_news"]=="yes")
                  $adm_menu.="&nbsp;&nbsp;&nbsp;<a onclick=\"return confirm('". str_replace("'","\'",DELETE_CONFIRM)."')\" href=news.php?act=del&id=".$rows["id"].">".DELETE."</a></td></tr>";
           else $adm_menu.="";

               include("$CURRENTPATH/offset.php");
               $news=format_comment($rows["news"]);
               $output = preg_replace("/{user_name}/", unesc($rows["username"]), $model);
               $output = preg_replace("/{admin_menu}/", $adm_menu, $output);
               $output = preg_replace("/{news_date}/", date("d/m/Y H:i",$rows["news_date"]-$offset), $output);
               $output = preg_replace("/{news_title}/", unesc($rows["title"]), $output);
               $output = preg_replace("/{news}/", $news, $output);
               print $output;
               }
               if ($output=="")
                  {
                  print("<center>".NO_NEWS."...<br />");
                  if ($CURUSER["edit_news"]=="yes")
                     print("<br /><a href=\"news.php\"><img border=0 alt=\"".ADD."\" src=\"images/new.gif\"></a><br /></center>");
                  }
}

function block_begin($title="-",$colspan=1,$calign="justify") {
    print("<br /><table class=lista cellpadding=0 cellspacing=0 width=95% align=center>\n"
        ."\t<tr>\n"
        ."\t\t<TD class=block align=center height=20px colspan=$colspan><b>$title</b></TD>\n"
        ."\t</tr>\n"
        ."\t<tr>\n"
        ."\t\t<TD width=100% align=$calign valign=top>");
}

function block_end($colspan=1) {
    print("\t\t</td>\n"
        ."\t</tr>\n"
        ."\t<tr>\n"
        ."\t\t<TD class=block colspan=$colspan align=center height=20px></TD>\n"
        ."\t</tr>\n"
        ."</table>");
}

function print_users()
         {
         global $CURUSER, $STYLEPATH, $CURRENTPATH;

     if (!isset($_GET["searchtext"])) $_GET["searchtext"] = "";
     if (!isset($_GET["level"])) $_GET["level"] = "";

         $search=htmlsafechars($_GET["searchtext"]);
         $addparams="";
         if ($search!="")
            {
            $where=" AND users.username LIKE '%".htmlsafechars(((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $_GET["searchtext"]) : ((trigger_error("Error...", E_USER_ERROR)) ? "" : "")))."%'";
            $addparams="searchtext=$search";
            }
         else
             $where="";

         $level=intval(0+$_GET["level"]);
         if ($level>0)
            {
            $where.=" AND users.id_level=$level";
            if ($addparams!="")
               $addparams.="&level=$level";
            else
                $addparams="level=$level";
            }

 $order_param=3;
          // getting order
          if (isset($_GET["order"]))
             {
             $order_param=(int)$_GET["order"];
             switch ($order_param)
               {
               case 1:
                    $order="username";
                    break;

               case 2:
                    $order="level";
                    break;

               case 3:
                    $order="joined";
                    break;

               case 4:
                    $order="lastconnect";
                    break;

               case 5:
                    $order="flag";
                    break;
                         
               case 6:
                    $order="ratio";
                    break;

               default:
                   $order="joined";

             }
          }
          else
              $order="joined";


          if (isset($_GET["by"]))
           {
              $by_param=(int)$_GET["by"];
              $by=($by_param==1?"ASC":"DESC");
          }
          else
              $by="ASC"; 

         if ($addparams!="")
            $addparams.="&";

         $scriptname=htmlsafechars($_SERVER["PHP_SELF"]);

         $res=run_query("select COUNT(*) FROM users INNER JOIN users_level ON users.id_level=users_level.id WHERE users.id>1 $where") or die(((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)));
         $row = mysqli_fetch_row($res);
         $count = $row[0];
         list($pagertop, $pagerbottom, $limit) = pager(20, $count,  "users.php?".$addparams."order=$order_param&by=$by_param&");

        if ($by=="ASC")
            $mark="&nbsp;&#8593";
        else
            $mark="&nbsp;&#8595";

         ?>
         <div align="center">
         <form action="users.php" name="ricerca" method="get">
           <table border="0" class="lista">
           <tr>
           <td class="block"><?php echo FIND_USER; ?> </td>
           <td class="block"><?php echo USER_LEVEL; ?> </td>
           <td class="block">&nbsp;</td>
           </tr>
           <tr>
           <td><input type="text" name="searchtext" size="30" maxlength="50" value="<?php echo $search ?>" /></td>
           <?php
           print("<td><select name=\"level\">");
           print("<option value=0".($level==0 ? " selected=selected " : "").">".ALL."</option>");
           $res=run_query("SELECT id,level FROM users_level WHERE id_level>1 ORDER BY id_level");
           while($row=mysqli_fetch_array($res))
           {
               $select="<option value='".$row["id"]."'";
               if ($level==$row["id"])
                  $select.="selected=\"selected\"";
               $select.=">".$row["level"]."</option>\n";
               print $select;
           }
           print("</select></td>");
           ?>
           </td>
           <td><input type="submit" value="<?php echo SEARCH; ?>" /></td>
           </tr>
           </table>
         </form>
         <?php print $pagertop; ?>
         <table class="lista" width="95%" >
         <tr>
         <td class="header" align="center"><?php echo "<a href=\"$scriptname?$addparams"."order=1&by=".($order=="username" && $by=="ASC"?"2":"1")."\">".USER_NAME."</a>".($order=="username"?$mark:"");?></td>
         <td class="header" align="center"><?php echo "<a href=\"$scriptname?$addparams"."order=2&by=".($order=="level" && $by=="ASC"?"2":"1")."\">".USER_LEVEL."</a>".($order=="level"?$mark:"")?></td>
         <td class="header" align="center"><?php echo "<a href=\"$scriptname?$addparams"."order=3&by=".($order=="joined" && $by=="ASC"?"2":"1")."\">".USER_JOINED."</a>".($order=="joined"?$mark:"");?></td>
         <td class="header" align="center"><?php echo "<a href=\"$scriptname?$addparams"."order=4&by=".($order=="lastconnect" && $by=="ASC"?"2":"1")."\">".USER_LASTACCESS."</a>".($order=="lastconnect"?$mark:"");?></td>
         <td class="header" align="center"><?php echo "<a href=\"$scriptname?$addparams"."order=5&by=".($order=="flag" && $by=="ASC"?"2":"1")."\">".PEER_COUNTRY."</a>".($order=="flag"?$mark:"");?></td>
         <td class="header" align="center"><?php echo "<a href=\"$scriptname?$addparams"."order=6&by=".($order=="ratio" && $by=="ASC"?"2":"1")."\">".RATIO."</a>".($order=="ratio"?$mark:"");?></td>
         <?php if ($CURUSER["uid"]>1) { ?> <td class="header" align="center"><?php echo PM;?></td> <?php } ?>
         <?php
             if ($CURUSER["edit_users"]=="yes")
                print("<td class=\"header\" align=\"center\">".EDIT."</td>");
             if ($CURUSER["delete_users"]=="yes")
                print("<td class=\"header\" align=\"center\">".DELETE."</td>");
         else print ("</tr>");

         $query="select prefixcolor, suffixcolor, users.id, downloaded,uploaded, IF(downloaded>0,uploaded/downloaded,0) as ratio, username,level,UNIX_TIMESTAMP(joined) AS joined,UNIX_TIMESTAMP(lastconnect) AS lastconnect, flag, flagpic, name FROM users INNER JOIN users_level ON users.id_level=users_level.id LEFT JOIN countries ON users.flag=countries.id WHERE users.id>1 $where ORDER BY $order $by $limit";
         $rusers=run_query($query);
         
         if (mysqli_num_rows($rusers)==0)
            // flag hack
            print("<tr><td class=lista colspan=9>".NO_USERS_FOUND."</td></tr>");
         else
             {
                 include("$CURRENTPATH/offset.php");
                 while ($row_user=mysqli_fetch_array($rusers))
                       {
                       print("<tr>\n");
                       print("<td class=\"lista\"><a href=userdetails.php?id=".$row_user["id"].">".unesc($row_user["prefixcolor"]).unesc($row_user["username"]).unesc($row_user["suffixcolor"])."</a></td>");
                       print("<td class=\"lista\" align=\"center\">".$row_user["level"]."</td>");
                       print("<td class=\"lista\" align=\"center\">".($row_user["joined"]==0 ? NOT_AVAILABLE : date("d/m/Y H:i:s",$row_user["joined"]-$offset))."</td>");
                       print("<td class=\"lista\" align=\"center\">".($row_user["lastconnect"]==0 ? NOT_AVAILABLE : date("d/m/Y H:i:s",$row_user["lastconnect"]-$offset))."</td>");
                       print("<td class=\"lista\" align=\"center\">". ( $row_user["flag"] == 0 ? "<img src='images/flag/unknown.gif' alt='".UNKNOWN."' title='".UNKNOWN."' />" : "<img src='images/flag/" . $row_user['flagpic'] . "' alt='" . $row_user['name'] . "' title='" . $row_user['name'] . "' />")."</td>");
                       //user ratio
                       if (max(0,$row_user["downloaded"])>0)
                          $ratio=number_format($row_user["uploaded"]/$row_user["downloaded"],2);
                       else
                           $ratio="oo";
                       print("<td class=\"lista\" align=\"center\">$ratio</td>");
                       if ($CURUSER["uid"]>1)
                          print("<td class=\"lista\" align=\"center\"><a href=usercp.php?do=pm&action=edit&uid=$CURUSER[uid]&what=new&to=".urlencode(unesc($row_user["username"])).">".image_or_link("$STYLEPATH/pm.png","","PM")."</a></td>");
                       if ($CURUSER["edit_users"]=="yes")
                          print("<td class=\"lista\" align=\"center\"><a href=account.php?act=mod&uid=".$row_user["id"]."&returnto=".urlencode("users.php").">".image_or_link("$STYLEPATH/edit.png","",EDIT)."</a></td>");
                       if ($CURUSER["delete_users"]=="yes")
                          print("<td class=\"lista\" align=\"center\"><a  onclick=\"return confirm('".AddSlashes(DELETE_CONFIRM)."')\" href=account.php?act=del&uid=".$row_user["id"]."&returnto=".urlencode("users.php").">".image_or_link("$STYLEPATH/delete.png","",DELETE)."</a></td>");
                       print("</tr>\n");
                       }
             }
         print("</table>\n</div>\n<br />");
} 

function makesize($Size, $Levels = 2) {
	$Units = array(' BiT',' KiB',' MiB',' GiB',' TiB',' PiB',' EiB',' ZiB',' YiB');
	$Size = (double) $Size;
	for($Steps = 0; abs($Size) >= 1024; $Size /= 1024, $Steps++) {
	}
  if (func_num_args() == 1 && $Steps >= 4) {
		$Levels++;
	}
	return number_format($Size,$Levels).$Units[$Steps];
} 

function redirect($redirecturl) {
// using javascript for redirecting
// some hosting has warning enabled and this is causing
// problem withs header() redirecting...

        print("If your browser doesn't have javascript enabled, click <a href=\"$redirecturl\"> here </a>");
        print("<script LANGUAGE=\"javascript\">window.location.href=\"$redirecturl\"</script>");

}

function textbbcode($form,$name,$content="") {
?>

<script language="javascript"  type="text/javascript">

// Remember the current position.
function storeCaret(text)
{
    // Only bother if it will be useful.
    if (typeof(text.createTextRange) != "undefined")
        text.caretPos = document.selection.createRange().duplicate();
}

function SmileIT(smile,textarea){
    // Attempt to create a text range (IE).
    if (typeof(textarea.caretPos) != "undefined" && textarea.createTextRange)
    {
        var caretPos = textarea.caretPos;

        caretPos.text = caretPos.text.charAt(caretPos.text.length - 1) == ' ' ? smile + ' ' : smile
        caretPos.select();
    }
    // Mozilla text range replace.
    else if (typeof(textarea.selectionStart) != "undefined")
    {
        var begin = textarea.value.substr(0, textarea.selectionStart);
        var end = textarea.value.substr(textarea.selectionEnd);
        var scrollPos = textarea.scrollTop;

        textarea.value = begin + smile + end;

        if (textarea.setSelectionRange)
        {
            textarea.focus();
            textarea.setSelectionRange(begin.length + smile.length, begin.length + smile.length);
        }
        textarea.scrollTop = scrollPos;
    }
    // Just put it on the end.
    else
    {
        textarea.value += smile;
        textarea.focus(textarea.value.length - 1);
    }
}

function PopMoreSmiles(form,name) {
         link='moresmiles.php?form='+form+'&text='+name
         newWin=window.open(link,'moresmile','height=500,width=300,resizable=yes,scrollbars=yes');
         if (window.focus) {newWin.focus()}
}

function BBTag(opentag, closetag, textarea)
{
    // Can a text range be created?
    if (typeof(textarea.caretPos) != "undefined" && textarea.createTextRange)
    {
        var caretPos = textarea.caretPos, temp_length = caretPos.text.length;

        caretPos.text = caretPos.text.charAt(caretPos.text.length - 1) == ' ' ? opentag + caretPos.text + closetag + ' ' : opentag + caretPos.text + closetag;

        if (temp_length == 0)
        {
            caretPos.moveStart("character", -closetag.length);
            caretPos.moveEnd("character", -closetag.length);
            caretPos.select();
        }
        else
            textarea.focus(caretPos);
    }
    // Mozilla text range wrap.
    else if (typeof(textarea.selectionStart) != "undefined")
    {
        var begin = textarea.value.substr(0, textarea.selectionStart);
        var selection = textarea.value.substr(textarea.selectionStart, textarea.selectionEnd - textarea.selectionStart);
        var end = textarea.value.substr(textarea.selectionEnd);
        var newCursorPos = textarea.selectionStart;
        var scrollPos = textarea.scrollTop;

        textarea.value = begin + opentag + selection + closetag + end;

        if (textarea.setSelectionRange)
        {
            if (selection.length == 0)
                textarea.setSelectionRange(newCursorPos + opentag.length, newCursorPos + opentag.length);
            else
                textarea.setSelectionRange(newCursorPos, newCursorPos + opentag.length + selection.length + closetag.length);
            textarea.focus();
        }
        textarea.scrollTop = scrollPos;
    }
    // Just put them on the end, then.
    else
    {
        textarea.value += opentag + closetag;
        textarea.focus(textarea.value.length - 1);
    }
}
</script>

  <table width="100%" cellpadding="0" cellspacing="0">
    <tr>
      <td colspan=2>
      <table cellpadding="0" cellspacing="1">
      <tr>
      <td><input style="font-weight: bold;" type="button" name="bold" value="B " onclick="javascript: BBTag('[b]','[/b]',<?php echo "document.forms.".$form.".".$name; ?>)" /></td>
      <td><input style="font-style: italic;" type="button" name="italic" value="i " onclick="javascript: BBTag('[i]','[/i]',<?php echo "document.forms.".$form.".".$name; ?>)" /></td>
      <td><input style="text-decoration: underline;" type="button" name="underline" value="U " onclick="javascript: BBTag('[u]','[/u]',<?php echo "document.forms.".$form.".".$name; ?>)" /></td>
      <td><input type="button" name="li" value="List " onclick="javascript: BBTag('[*]','',<?php echo "document.forms.".$form.".".$name; ?>)" /></td>
      <td><input type="button" name="code" value="Code" onclick="javascript: BBTag('[code]','[/code]',<?php echo "document.forms.".$form.".".$name; ?>)" /></td>
      <td><input type="button" name="quote" value="Quote" onclick="javascript: BBTag('[quote]','[/quote]',<?php echo "document.forms.".$form.".".$name; ?>)" /></td>
      <td><input type="button" name="url" value="Url" onclick="javascript: BBTag('[url]','[/url]',<?php echo "document.forms.".$form.".".$name; ?>)" /></td>
      <td><input type="button" name="img" value="Img" onclick="javascript: BBTag('[img]','[/img]',<?php echo "document.forms.".$form.".".$name; ?>)" /></td>
      <td>
            <select onchange="BBTag('[color=' + this.options[this.selectedIndex].value.toLowerCase() + ']','[/color]', <?php echo "document.forms.".$form.".".$name; ?>); this.selectedIndex = 0;" size="1" style="background-color:#DEDEDE;" name="fontchange">
            <option value="" selected="selected">Change Color</option>
            <option value="Black" style="color:black">Black</option>
            <option value="Red" style="color:red">Red</option>
            <option value="Yellow" style="color:Yellow">Yellow</option>
            <option value="Pink" style="color:Pink">Pink</option>
            <option value="Green" style="color:Green">Green</option>
            <option value="Orange" style="color:Orange">Orange</option>
            <option value="Purple" style="color:Purple">Purple</option>
            <option value="Blue" style="color:Blue">Blue</option>
            <option value="Beige" style="color:Beige">Beige</option>
            <option value="Brown" style="color:Brown">Brown</option>
            <option value="Teal" style="color:Teal">Teal</option>
            <option value="Navy" style="color:Navy">Navy</option>
            <option value="Maroon" style="color:Maroon">Maroon</option>
            <option value="LimeGreen" style="color:LimeGreen">Lime Green</option>
            </select>
      </td>
      <td>
            <select onchange="BBTag('[size=' + this.options[this.selectedIndex].value.toLowerCase() + ']','[/size]', <?php echo "document.forms.".$form.".".$name; ?>); this.selectedIndex = 0;" size="1" style="background-color:#DEDEDE;" name="fontchange">
            <option value="" selected="selected">Font Size</option>
            <option value="1">xx-small</option>
            <option value="2">x-small</option>
            <option value="3">small</option>
            <option value="4">medium</option>
            <option value="5">large</option>
            <option value="6">x-large</option>
            <option value="7">xx-large</option>
            </select>
      </td>
      </tr>
      </table>
      </td>
    </tr>
    <tr>
      <td>
      <textarea name="<?php echo $name; ?>" rows="10" cols="40" onselect="storeCaret(this);" onclick="storeCaret(this);" onkeyup="storeCaret(this);" onchange="storeCaret(this);"><?php echo $content; ?></textarea>
      </td>
      <td>
      <table width="100%" cellpadding="1" cellspacing="1">
      <?php

      global $smilies, $count;
      reset($smilies);
      while ((list($code, $url) = each($smilies)) && $count<20) {
         if ($count % 4==0)
            print("<tr>");

            print("\n<td><a href=\"javascript: SmileIT('".str_replace("'","\'",$code)."',document.forms.".$form.".".$name.");\"><img border=0 src=images/smilies/".$url."></a></td>");
            $count++;

         if ($count % 4==0)
            print("</tr>");
      }
      ?>
      </table>
      <center><a href="javascript: PopMoreSmiles('<?php echo $form; ?>','<?php echo $name; ?>')"><?php echo MORE_SMILES;?></a></center>
      </td>
    </tr>
  </table>
<?php
}

// begin functions for the forum

function is_valid_id($id)
{
  return is_numeric($id) && ($id > 0) && (floor($id) == $id);
}

function begin_table($fullwidth = false, $padding = 5)
{
   if ($fullwidth) $width = " width=100%";
     else $width = "";
  print("<table class=lista$width border=1 cellspacing=0 cellpadding=$padding>\n");
}

function end_table()
{
  print("</td></tr></table>\n");
}

function begin_frame($caption = "", $center = false, $padding = 10)
{
  if ($caption)
    print("<center><h3>$caption</h3></center>\n");

  if ($center)
    $tdextra = " align=center";
  else $tdextra ="";

  print("<table width=100% cellspacing=0 cellpadding=$padding><tr><td$tdextra>\n");
}

function end_frame()
{
  print("</td></tr></table>\n");
}

function get_date_time($timestamp = 0)
{

  global $CURRENTPATH;

  include("$CURRENTPATH/offset.php");
  if ($timestamp)
    return date("d/m/Y H:i:s", $timestamp-$offset);
  else
    return gmdate("d/m/Y H:i:s");
}

function stderr($heading, $text)
{
  err_msg($heading,$text);
  stdfoot();
  die;
}
function encodehtml($s, $linebreaks = true)
{
  $s = str_replace("<", "&lt;", str_replace("&", "&amp;", $s));
  if ($linebreaks)
    $s = nl2br($s);
  return $s;
}

function get_elapsed_time($ts)
{
  $mins = floor((time() - $ts) / 60);
  $hours = floor($mins / 60);
  $mins -= $hours * 60;
  $days = floor($hours / 24);
  $hours -= $days * 24;
  $weeks = floor($days / 7);
  $days -= $weeks * 7;
  $t = "";
  if ($weeks > 0)
    return "$weeks week" . ($weeks > 1 ? "s" : "");
  if ($days > 0)
    return "$days day" . ($days > 1 ? "s" : "");
  if ($hours > 0)
    return "$hours hour" . ($hours > 1 ? "s" : "");
  if ($mins > 0)
    return "$mins min" . ($mins > 1 ? "s" : "");
  return "< 1 min";
}

function sql_timestamp_to_unix_timestamp($s)
{
  return mktime(substr($s, 11, 2), substr($s, 14, 2), substr($s, 17, 2), substr($s, 5, 2), substr($s, 8, 2), substr($s, 0, 4));
}

function gmtime()
{
    return strtotime(get_date_time());
}

function sqlerr($file = '', $line = '')
{
  print("<table border=0 bgcolor=blue align=left cellspacing=0 cellpadding=10 style='background: blue'>" .
    "<tr><td class=embedded><font color=white><h1>".ERR_SQL_ERR."</h1>\n" .
  "<b>" . ((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)) . ($file != '' && $line != '' ? "<p>in $file, line $line</p>" : "") . "</b></font></td></tr></table>");
  die;
}

function attach_frame($padding = 10)
{
  print("</td></tr><tr><td style='border-top: 0px'>\n");
}

function httperr($code = 404) {
    header("HTTP/1.0 404 Not found");
    print("<h1>".ERR_NOT_FOUND."</h1>\n");
    exit();
}

function peercolor($num)
{
    if(!$num){
        return "#FF0000";
    } elseif($num == 1){
        return "#BEC635";
    } else {
        return "green";
    }
}
 
// v.1.3
function write_log($text,$reason="add")
{
  GLOBAL $CURUSER, $LOG_ACTIVE;

  if ($LOG_ACTIVE)
    {
     $text = sqlesc($text);
     $reason=sqlesc($reason);
     run_query("INSERT INTO logs (added, txt,type,user) VALUES(UNIX_TIMESTAMP(), $text, $reason,'".$CURUSER["username"]."')") or sqlerr(__FILE__, __LINE__);
  }
}

// EOF
?>
