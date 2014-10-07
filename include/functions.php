<?php
/*
* BtiTracker v1.5.0 is a php tracker system for BitTorrent, easy to setup and configure.
* This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
* Updated and Maintained by Yupy.
* Copyright (C) 2004-2014 Btiteam.org
*/

// Emulate register_globals off
if (ini_get('register_globals')) {
    $superglobals = array(
        $_SERVER,
        $_ENV,
        $_FILES,
        $_COOKIE,
        $_POST,
        $_GET
    );
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

$tracker_version = "1.5.0";

// CHECK FOR INSTALLATION FOLDER WITHOUT INSTALL.ME
if (file_exists("install") && !file_exists("install.me")) {
    $err_msg_install = ("<div align='center' style='color:red; font-size:12pt; font-weight: bold;'>SECURITY WARNING: Delete install folder!</div>");
}

error_reporting(E_ALL ^ E_NOTICE);

require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'defines.php');
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'config.php');
require_once(INCL_PATH . 'common.php');
// protection against sql injection, xss attack
require_once(INCL_PATH . 'crk_protection.php');
// protection against sql injection, xss attack
require_once(INCL_PATH . 'theme_functions.php');
require_once(CLASS_PATH . 'class.Captcha.php');
require_once(CLASS_PATH . 'class.Cookies.php');
require_once(CLASS_PATH . 'class.Text.php');
require_once(CLASS_PATH . 'class.Template.php');
require_once(CLASS_PATH . 'class.Misc.php');
require_once(CLASS_PATH . 'class.Security.php');
require_once(CLASS_PATH . 'class.Vars.php');
require_once(CLASS_PATH . 'class.User.php');

raintpl::configure("base_url", null);
raintpl::configure("tpl_dir", "");
raintpl::configure("cache_dir", "cache/");

$tpl = new RainTPL;

// default for disabling DHT network
if (!isset($DHT_PRIVATE))
    $DHT_PRIVATE = true;
if (!isset($LIVESTATS))
    $LIVESTATS = false;
if (!isset($LOG_ACTIVE))
    $LOG_ACTIVE = false;
if (!isset($LOG_HISTORY))
    $LOG_HISTORY = false;
if (!isset($GZIP_ENABLED))
    $GZIP_ENABLED = false;
if (!isset($PRINT_DEBUG))
    $PRINT_DEBUG = true;
if (!isset($USE_IMAGECODE))
    $USE_IMAGECODE = true;
if (!isset($TRACKER_ANNOUNCEURLS)) {
    $TRACKER_ANNOUNCEURLS   = array();
    $TRACKER_ANNOUNCEURLS[] = $BASEURL . '/announce.php';
}

function get_microtime()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float) $usec + (float) $sec);
}

function print_version()
{
    global $time_start, $gzip, $PRINT_DEBUG, $tracker_version;
	
    $time_end = get_microtime();
    $max_mem = memory_get_peak_usage();
	
    print("<p align='center'>");
    if ($PRINT_DEBUG)
        print("[ Execution time: ".number_format(($time_end-$time_start),4)." sec. ] - [Memory usage: ".misc::makesize($max_mem)."] - [ GZIP: ".$gzip." ]<br />");
    print("BtiTracker (" . $tracker_version . ") by <a href='https://github.com/Yupy/BtiTracker-1.5.0' target='_blank'>Yupy<a/> & <a href='http://www.btiteam.org' target='_blank'>Btiteam</a></p>");
}

//Disallow special characters in username
function straipos($haystack, $array, $offset = 0)
{
    $occ = Array();
    for ($i = 0; $i < sizeof($array); $i++) {
        $pos = strpos($haystack, $array[$i], $offset);
        if (is_bool($pos))
            continue;
        $occ[$pos] = $i;
    }
    if (sizeof($occ) < 1)
        return false;
    ksort($occ);
    reset($occ);
    list($key, $value) = each($occ);
    return array(
        $key,
        $value
    );
}
//EOF

// Worker functions
if (function_exists("bcadd")) {
    function sqlAdd($left, $right)
    {
        return bcadd($left, $right, 0);
    }
    function sqlSubtract($left, $right)
    {
        return bcsub($left, $right, 0);
    }
    function sqlMultiply($left, $right)
    {
        return bcmul($left, $right, 0);
    }
    function sqlDivide($left, $right)
    {
        return bcdiv($left, $right, 0);
    }
} else {
    // Uses the mysql database connection to perform string math. :)
    // Used by byte counting functions
    // No error handling as we assume nothing can go wrong. :|
    function sqlAdd($left, $right)
    {
	    global $db;
		
        $query = 'SELECT ' . $left . ' + ' . $right;
        $results = $db->query($query) or showError(DATABASE_ERROR);
        return mysqli_result($results, 0, 0);
    }
    
    function sqlSubtract($left, $right)
    {
	    global $db;
		
        $query = 'SELECT ' . $left . ' - ' . $right;
        $results = $db->query($query) or showError(DATABASE_ERROR);
        return mysqli_result($results, 0, 0);
    }
    
    function sqlDivide($left, $right)
    {
	    global $db;
		
        $query = 'SELECT ' . $left . ' / ' . $right;
        $results = $db->query($query) or showError(DATABASE_ERROR);
        return mysqli_result($results, 0, 0);
    }
    
    function sqlMultiply($left, $right)
    {
	    global $db;
		
        $query = 'SELECT ' . $left . ' * ' . $right;
        $results = $db->query($query) or showError(DATABASE_ERROR);
        return mysqli_result($results, 0, 0);
    }
} // End of BC vs SQL

function makeTorrent($hash, $tolerate = false)
{
    global $db;
	
    if (strlen($hash) != 40)
        showError(MKTOR_INVALID_HASH);
    $result = true;
    
    if (!$result && !$tolerate)
        return false;
	
    if (isset($GLOBALS["peercaching"]) && $GLOBALS["peercaching"]) {
	#Do Nothing...
    }
    $query = "INSERT INTO summary SET info_hash = '" . $hash . "', lastSpeedCycle = UNIX_TIMESTAMP()";
    if (!@$db->query($query))
        $result = false;
	
    return $result;
}

// Slight redesign of loadPeers
function getRandomPeers($hash, $where = "")
{
   global $db;
    
    $where = "WHERE infohash = '" . $hash . "'";
    
    if ($GLOBALS["NAT"])
        $results = $db->query("SELECT COUNT(*) FROM peers WHERE natuser = 'N' AND infohash = '" . $hash . "'");
    else
        $results = $db->query("SELECT COUNT(*) FROM peers WHERE infohash = '" . $hash . "'");
    
    $peercount = mysqli_result($results, 0, 0);
    
    if ($peercount < 500)
        $query = "SELECT " . ((isset($_GET["no_peer_id"]) && $_GET["no_peer_id"] == 1) ? "" : "peer_id,") . "ip, port, status FROM peers " . $where . " ORDER BY RAND() LIMIT {$GLOBALS['maxpeers']}";
    else
        $query = "SELECT " . ((isset($_GET["no_peer_id"]) && $_GET["no_peer_id"] == 1) ? "" : "peer_id,") . "ip, port, status FROM peers " . $where . " LIMIT " . @mt_rand(0, $peercount - (int)$GLOBALS["maxpeers"]) . ", {$GLOBALS['maxpeers']}";
    
    $results = $db->query($query);
    if (!$results)
        return false;
    
    $peerno = 0;
    while ($return[] = $results->fetch_assoc())
        $peerno++;
    
    array_pop($return);
    $results->free();
    $return['size'] = $peerno;
    
    return $return;
}

// Updates the peer user's info.
// Currently it does absolutely nothing. lastupdate is set in collectBytes
// as well.
function updatePeer($peerid, $hash)
{
    #Do Nothing...
}

// Transmits the actual data to the peer. No other output is permitted if
// this function is called, as that would break BEncoding.
// I don't use the bencode library, so watch out! If you add data,
// rules such as dictionary sorting are enforced by the remote side.
function sendPeerList($peers)
{
    echo "d";
    echo "8:intervali" . $GLOBALS["report_interval"] . "e";
    if (isset($GLOBALS["min_interval"]))
        echo "12:min intervali" . $GLOBALS["min_interval"] . "e";
    echo "5:peers";
    $size = (int)$peers["size"];
    if (isset($_GET["compact"]) && $_GET["compact"] == '1') {
        $p = '';
        for ($i = 0; $i < $size; $i++)
            $p .= str_pad(pack("Nn", ip2long($peers[$i]['ip']), $peers[$i]['port']), 6);
        echo strlen($p) . ':' . $p;
    } else // no_peer_id or no feature supported
        {
        echo 'l';
        for ($i = 0; $i < $size; $i++) {
            echo "d2:ip" . strlen($peers[$i]["ip"]) . ":" . $peers[$i]["ip"];
            if (isset($peers[$i]["peer_id"]))
                echo "7:peer id20:" . hex2bin($peers[$i]["peer_id"]);
            echo "4:port" . $peers[$i]["port"] . "ee";
        }
        echo "e";
    }
    if (isset($GLOBALS["trackerid"])) {
        // Now it gets annoying. trackerid is a string
        echo "10:tracker id" . strlen($GLOBALS["trackerid"]) . ":" . $GLOBALS["trackerid"];
    }
    echo "e";
}

// Returns a $peers array of all peers that have timed out (2* report interval seems fair
// for any reasonable report interval (900 or larger))
function loadLostPeers($hash, $timeout)
{
    global $db;
	
    $results = $db->query("SELECT peer_id, bytes, ip, port, status, lastupdate, sequence FROM peers WHERE infohash = '" . $hash . "' AND lastupdate < (UNIX_TIMESTAMP() - 2 * " . $timeout . ")");

    $peerno  = 0;
    if (!$results)
        return false;
    
    while ($return[] = $results->fetch_assoc())
        $peerno++;
    array_pop($return);
    $return["size"] = $peerno;
    $results->free();
    return $return;
}

function trashCollector($hash, $timeout)
{
    global $db;
	
    if (isset($GLOBALS["trackerid"]))
        unset($GLOBALS["trackerid"]);
    
    if (!Lock($hash))
        return;
    
    $results   = $db->query("SELECT lastcycle FROM summary WHERE info_hash = '" . $hash . "'");
    $lastcheck = ($results->fetch_row());
    
    // Check once every re-announce cycle
    if (($lastcheck[0] + $timeout) < vars::$timestamp) {
        $peers = loadLostPeers($hash, $timeout);
        for ($i = 0; $i < $peers["size"]; $i++)
            killPeer($peers[$i]["peer_id"], $hash, $peers[$i]["bytes"]);
        summaryAdd("lastcycle", "UNIX_TIMESTAMP()", true);
    }
    Unlock($hash);
}

// Attempts to aquire a lock by name.
// Returns true on success, false on failure
function Lock($hash, $time = 0)
{
    global $db;
	
    $results = $db->query("SELECT GET_LOCK('" . $hash . "', " . $time . ")");
    $string  = $results->fetch_row();
    if (strcmp($string[0], "1") == 0)
        return true;
    return false;
}

// Releases a lock. Ignores errors.
function Unlock($hash)
{
    quickQuery("SELECT RELEASE_LOCK('" . $hash . "')");
}

// Returns true if the lock is available
function isFreeLock($lock)
{
    if (Lock($lock, 0)) {
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
    
    $peers[0]["peer_id"]        = $peer_id;
    $peers[0]["ip"]             = $ip;
    $peers[0]["port"]           = $port;
    $peers["size"]              = 1;
    $GLOBALS["report_interval"] = 86400;
    $GLOBALS["min_interval"]    = 86000;
    sendPeerList($peers);
    exit(0);
}

if (function_exists('mhash') && (!function_exists('sha1')) && defined('MHASH_SHA1')) {
    function sha1($str)
    {
        return bin2hex(mhash(MHASH_SHA1, $str));
    }
}

function unesc($x)
{
    if (get_magic_quotes_gpc())
        return stripslashes($x);
    return $x;
}

function mksecret($len = 20)
{
    $ret = "";
    for ($i = 0; $i < $len; $i++)
        $ret .= chr(mt_rand(0, 255));
    return $ret;
}

function hashit($var, $addtext = '')
{
    return md5("R8rYxEX7" . $addtext . $var . $addtext . "Ystkyi6xSRYOTKJU3AmJ1D2");
}

function logincookie($id, $passhash, $expires = 0x7fffffff)
{
    Cookie::set("uid", $id, $expires, "/");
    Cookie::set("pass", $passhash, $expires, "/");
    Cookie::set("hashx", hashit($id, $passhash), $expires, "/");
}

function logoutcookie()
{
    Cookie::set("uid", "", 0x7fffffff, "/");
    Cookie::set("pass", "", 0x7fffffff, "/");
    Cookie::set("hashx", "", 0x7fffffff, "/");
}

function hash_pad($hash)
{
    return str_pad($hash, 20);
}

function format_urls($s)
{
    return preg_replace('/(\A|\s)((?:http|ftp|https|ftps|irc):\/\/[^()<>\s]+)/i', '$1<a href="/out.php?url=$2"' . $link . '>$2</a>', $s);
}

function userlogin()
{
    global $db;
    unset($GLOBALS["CURUSER"]);
    
    $ip  = ip::get_ip();
    $nip = ip2long($ip);
    $res = $db->query("SELECT * FROM bannedip WHERE '" . $nip . "' >= first AND '" . $nip . "' <= last") or sqlerr(__FILE__, __LINE__);
    if ($res->num_rows > 0) {
        header("HTTP/1.0 403 Forbidden");
        print("<html><body><h1>403 Forbidden</h1>Unauthorized IP address.</body></html>\n");
        die;
    }
    
    // guest
    if (empty($_COOKIE["uid"]) || empty($_COOKIE["pass"]))
        $id = 1;
    
    if (!isset($_COOKIE["uid"]))
        $_COOKIE["uid"] = 1;

    $id = max(1, (int) $_COOKIE["uid"]);

    // it's guest
    if (!$id)
        $id = 1;
    
    $res = $db->query("SELECT users.topicsperpage, users.postsperpage, users.torrentsperpage, users.flag, users.avatar, UNIX_TIMESTAMP(users.lastconnect) AS lastconnect, UNIX_TIMESTAMP(users.joined) AS joined, users.id AS uid, users.username, users.password, users.loginhash, users.random, users.email, users.language, users.style, users.time_offset, users_level.* 
	    FROM users INNER JOIN users_level ON users.id_level = users_level.id 
		WHERE users.id = " . $id);
    $row = $res->fetch_array(MYSQLI_BOTH);
    
    user::prepare_user($row);
    
    if (!$row) {
        $id  = 1;
        $res = $db->query("SELECT users.topicsperpage, users.postsperpage, users.torrentsperpage, users.flag, users.avatar, UNIX_TIMESTAMP(users.lastconnect) AS lastconnect, UNIX_TIMESTAMP(users.joined) AS joined, users.id AS uid, users.username, users.password, users.loginhash, users.random, users.email, users.language, users.style, users.time_offset, users_level.* 
		    FROM users INNER JOIN users_level ON users.id_level = users_level.id WHERE users.id = 1");
        $row = $res->fetch_array(MYSQLI_BOTH);
    }
	
    if (!isset($_COOKIE["pass"]))
        $_COOKIE["pass"] = "";
	
    if (($_COOKIE["pass"] != md5($GLOBALS["salting"] . $row["random"] . $row["password"] . $row["random"])) && $id != 1) {
        $id  = 1;
        $res = $db->query("SELECT users.topicsperpage, users.postsperpage, users.torrentsperpage, users.flag, users.avatar, UNIX_TIMESTAMP(users.lastconnect) AS lastconnect, UNIX_TIMESTAMP(users.joined) AS joined, users.id AS uid, users.username, users.password, users.loginhash, users.random, users.email, users.language, users.style, users.time_offset, users_level.* 
		    FROM users INNER JOIN users_level ON users.id_level = users_level.id 
			WHERE users.id = 1");
        $row = $res->fetch_array(MYSQLI_BOTH);
    }
    
    #Hide Staff IP's by Yupy... Because we <3 our Staff...
    $hide_ips = array(
        "Moderator" => 6,
        "Administrator" => 7,
        "Owner" => 8
    ); // Staff ID level's 
    
    $ip = ($row["id_level"] <> $hide_ips["Moderator"]) ? $ip : "127.0.0.1";
    $ip = ($row["id_level"] <> $hide_ips["Administrator"]) ? $ip : "127.0.0.1";
    $ip = ($row["id_level"] <> $hide_ips["Owner"]) ? $ip : "127.0.0.1";
    
    if ($id > 1)
        $db->query("UPDATE users SET lastconnect = NOW(), lip = " . $nip . ", cip = '" . AddSlashes($ip) . "' WHERE id = " . $id);
    else
        $db->query("UPDATE users SET lastconnect = NOW(), lip = 0, cip = NULL WHERE id = 1");
    
    user::$current = $row;
    $GLOBALS['CURUSER'] =& user::$current;
    unset($row);
}

function dbconn($do_clean = false)
{
    global $dbhost, $dbuser, $dbpass, $database, $HTTP_SERVER_VARS, $db;
    
    /*
     * Connect to Database.
     */
	if ($GLOBALS["persist"])
        $db = new mysqli($dbhost, $dbuser, $dbpass, $database);
	else
	    $db = new mysqli($dbhost, $dbuser, $dbpass, $database);
    /*
     * This is the "official" OO way to do it,
     * BUT $connect_error was broken until PHP 5.2.9 and 5.3.0.
     */
    if ($db->connect_error) {
        die('Connect Error (' . $db->connect_errno . ') ' . $db->connect_error);
    }
	
    $db->query("SET NAMES UTF8");
    $db->query("SET collation_connection = 'utf8_general_ci'");
    
    userlogin();
    
    if ($do_clean)
        register_shutdown_function("cleandata");
}

function cleandata()
{
    global $db;
    
    require_once(INCL_PATH . 'sanity.php');
    
    global $clean_interval;
    
    if ((0 + $clean_interval) == 0)
        return;
    
    $now = vars::$timestamp;
    
    $res = $db->query("SELECT last_time FROM tasks WHERE task = 'sanity'");
    $row = $res->fetch_array(MYSQLI_BOTH);
	
    if (!$row) {
        $db->query("INSERT INTO tasks (task, last_time) VALUES ('sanity', " . $now . ")");
        return;
    }
	
    $ts = $row[0];
    if ($ts + $clean_interval > $now)
        return;
	
    $db->query("UPDATE tasks SET last_time = " . $now . " WHERE task = 'sanity' AND last_time = " . $ts);
	
    if (!$db->affected_rows)
        return;
    
    do_sanity();
}

function updatedata()
{
    global $db;
    
    require_once(INCL_PATH . 'getscrape.php');
    
    global $update_interval;
    
    if ((0 + $update_interval) == 0)
        return;
    
    $now = vars::$timestamp;
    
    $res = @$db->query("SELECT last_time FROM tasks WHERE task='update'");
    $row = @$res->fetch_array(MYSQLI_BOTH);
    if (!$row) {
        $db->query("INSERT INTO tasks (task, last_time) VALUES ('update', " . $now . ")");
        return;
    }

    $ts = $row[0];
    if ($ts + $update_interval > $now)
        return;
    
    $db->query("UPDATE tasks SET last_time = " . $now . " WHERE task = 'update' AND last_time = " . $ts);
    if (!$db->affected_rows)
        return;
    
    // new control time is lastupdate (before the current one) - update interval
    $ts = $ts - $update_interval;
    
    $res = @$db->query("SELECT announce_url FROM namemap WHERE external = 'yes' AND UNIX_TIMESTAMP(lastupdate) < " . $ts . " ORDER BY lastupdate ASC LIMIT 1");
    if (!$res || $res->num_rows == 0)
        return;
    
    // get the url to scrape, take 5 torrent at a time (try to getting multiscrape)
    $row = $res->fetch_row();
    
    $resurl = @$db->query("SELECT info_hash FROM namemap WHERE external = 'yes' AND UNIX_TIMESTAMP(lastupdate) < " . $ts . " AND announce_url = '" . $row[0] . "' ORDER BY lastupdate DESC LIMIT 5");
    if (!$resurl || $resurl->num_rows == 0)
        return $combinedinfohash = array();
	
    while ($rhash = $resurl->fetch_row())
        $combinedinfohash[] = $rhash[0];
    
    scrape($row[0], implode("','", $combinedinfohash)); 
}

// give back categories recorset
function genrelist()
{
    global $db;
	
    $ret = array();
    $res = $db->query("SELECT * FROM categories ORDER BY sort_index, id");
    
    while ($row = $res->fetch_array(MYSQLI_BOTH))
        $ret[] = $row;
    
    return $ret;
}

// this returns all the categories
function categories($val = '')
{
    global $db;
	
    echo "<select name='category'><option value='0'>----</option>";
    $c_q = @$db->query("SELECT * FROM categories WHERE sub = '0' ORDER BY id ASC");
    while ($c = $c_q->fetch_array(MYSQLI_BOTH)) {
        $cid  = (int)$c["id"];
        $name = security::html_safe(unesc($c["name"]));
		
        // lets see if it has sub-categories.
        $s_q  = $db->query("SELECT * FROM categories WHERE sub = '" . $cid . "'");
        $s_t  = $s_q->num_rows;
		
        if ($s_t == 0) {
            $checked = "";
            if ($cid == $val) {
                $checked = "selected";
            }
            echo "<option " . $checked . " value='" . $cid . "'>" . $name . "</option>";
        } else {
            echo "<optgroup label='" . $name . "'>";
            while ($s = $s_q->fetch_array(MYSQLI_BOTH)) {
                $sub     = (int)$s["id"];
                $name    = security::html_safe($s["name"]);
                $checked = "";
                if ($sub == $val) {
                    $checked = "selected";
                }
                echo "<option " . $checked . " value='" . $sub . "'>" . $name . "</option>";
            }
            echo "</optgroup>";
        }
    }
    echo "</select>";
}

// this returns all the subcategories
function sub_categories($val = '')
{
    global $db;
	
    echo "<select name='sub_category'><option value='0'>---</option>";
    $c_q = @$db->query("SELECT * FROM categories WHERE sub = '0' ORDER BY id ASC");
    while ($c = $c_q->fetch_array(MYSQLI_BOTH)) {
        $cid      = (int)$c["id"];
        $name     = security::html_safe(unesc($c["name"]));
        $selected = ($cid == $val) ? "selected" : "";
        echo "<option " . $selected . " value='" . $cid . "'>" . $name . "</option>";
    }
    echo "</select>";
}

// this returns the category of a sub-category
function sub_cat($sub)
{
    global $db;
	
    $c_q  = @$db->query("SELECT name FROM categories WHERE id = '" . $sub . "'");
	$c_q = @$c_q->fetch_array(MYSQLI_BOTH);
    $name = security::html_safe(unesc($c_q["name"]));
    return $name;
}

function style_list()
{
    global $db;
	
    $ret = array();
    $res = $db->query("SELECT * FROM style ORDER BY id");
    
    while ($row = $res->fetch_array(MYSQLI_BOTH))
        $ret[] = $row;
    
    return $ret;
}

function language_list()
{
    global $db;
	
    $ret = array();
    $res = $db->query("SELECT * FROM language ORDER BY language");
    
    while ($row = $res->fetch_array(MYSQLI_BOTH))
        $ret[] = $row;
    
    return $ret;
}

function flag_list($with_unknown = false)
{
    global $db;
	
    $ret = array();
    $res = $db->query("SELECT * FROM countries " . (!$with_unknown ? "WHERE id <> 100" : "") . " ORDER BY name");
    
    while ($row = $res->fetch_array(MYSQLI_BOTH))
        $ret[] = $row;
    
    return $ret;
}

function timezone_list()
{
    global $db;
	
    $ret = array();
    $res = $db->query("SELECT * FROM timezone");
    
    while ($row = $res->fetch_array(MYSQLI_BOTH))
        $ret[] = $row;
    
    return $ret;
}

function format_quote($s)
{
    while ($old_s != $s) {
        $old_s = $s;
        
        //find first occurrence of [/quote]
        $close = utf8::stripos($s, '[/quote]');
        if ($close === false)
            return $s;
        
        // find last [quote] before first [/quote]
        // note that there is no check for correct syntax
        $open = utf8::strripos(utf8::substr($s, 0, $close), '[quote');
        if ($open === false)
            return $s;
        
        $quote = utf8::substr($s, $open, $close - $open + 8);
        
        //[quote]Text[/quote]
        $quote = preg_replace('/\[quote\]\s*(.+?)\s*\[\/quote\]\s*/is', $bbcode['quote'][0] . 'Quote:' . $bbcode['quote'][1] . $bbcode['quote'][2] . '$1' . $bbcode['quote'][3], $quote);
        
        //[quote=Author]Text[/quote]
        $quote = preg_replace('/\[quote=([ \S]+?)\]\s*(.+?)\s*\[\/quote\]\s*/is', $bbcode['quote'][0] . '$1 wrote:' . $bbcode['quote'][1] . $bbcode['quote'][2] . '$2' . $bbcode['quote'][3], $quote);
        
        $s = utf8::substr($s, 0, $open) . $quote . utf8::substr($s, $close + 8);
    }
    
    return $s;
}

function sqlesc($x)
{
    global $db;
	
    return "'" . $db->real_escape_string(unesc($x)) . "'";
}

function print_news($limit = 0)
{
    global $db, $limitqry, $adm_menu, $CURRENTPATH;
	
    $output = '';
    
    $model = "<table cellpadding='4' cellspacing='1' border='0' width='100%' bgcolor='#000000' style='font-family:Verdana;font-size:11px'>" . "\n{admin_menu}" . "\n<tr><td class='header' align='center'>" . POSTED_BY . ": {user_name}" . "\n<br>" . POSTED_DATE . ": {news_date}" . "\n</td></tr><tr><td class='lista' align='center'>" . "\n<b>" . TITLE . ": {news_title}</b><br><br>" . "\n<table style='border-top:1px' solid gray;width:100%;font-family:Verdana;font-size:10px'>" . "\n<tr><td>{news}</td></tr>" . "\n</table></td></tr></table><br>";
    
	if ($limit > 0)
        $limitqry = "LIMIT " . $limit;
	
    $res = $db->query("SELECT news.id, news.title, news.news,UNIX_TIMESTAMP(news.date) AS news_date, users.username FROM news INNER JOIN users ON users.id = news.user_id ORDER BY date DESC " . $limitqry);
    while ($rows = $res->fetch_array(MYSQLI_BOTH)) {
        if (user::$current["edit_news"] == "yes" || user::$current["delete_news"] == "yes")
            $adm_menu = "<tr><td class='header' align='center'>";
        if (user::$current["edit_news"] == "yes")
            $adm_menu .= "<a href='news.php'>" . ADD . "</a>&nbsp;&nbsp;&nbsp;<a href='news.php?act=edit&id=" . (int)$rows["id"] . "'>" . EDIT . "</a>";
        if (user::$current["delete_news"] == "yes")
            $adm_menu .= "&nbsp;&nbsp;&nbsp;<a onclick='return confirm('" . str_replace("'", "\'", DELETE_CONFIRM) . "')' href='news.php?act=del&id=" . (int)$rows["id"] . "'>" . DELETE . "</a></td></tr>";
        else
            $adm_menu .= "";
        
        include(INCL_PATH . 'offset.php');
        $news   = text::full_format($rows["news"]);
        $output = preg_replace("/{user_name}/", security::html_safe(unesc($rows["username"])), $model);
        $output = preg_replace("/{admin_menu}/", $adm_menu, $output);
        $output = preg_replace("/{news_date}/", date("d/m/Y H:i", $rows["news_date"] - $offset), $output);
        $output = preg_replace("/{news_title}/", security::html_safe(unesc($rows["title"])), $output);
        $output = preg_replace("/{news}/", $news, $output);
        print $output;
    }
	
    if ($output == "") {
        print("<center>" . NO_NEWS . "...<br />");
        if (user::$current["edit_news"] == "yes")
            print("<br /><a href='news.php'><img border='0' alt='" . ADD . "' src='images/new.gif'></a><br /></center>");
    }
}

function print_users()
{
    global $db, $STYLEPATH, $CURRENTPATH;
    
    if (!isset($_GET["searchtext"]))
        $_GET["searchtext"] = "";
    if (!isset($_GET["level"]))
        $_GET["level"] = "";
    
    $search    = security::html_safe($_GET["searchtext"]);
    $addparams = "";
	
    if ($search != "") {
        $where     = " AND users.username LIKE '%" . security::html_safe($db->real_escape_string($_GET["searchtext"])) . "%'";
        $addparams = "searchtext=" . $search;
    } else
        $where = "";
    
    $level = intval(0 + $_GET["level"]);
    if ($level > 0) {
        $where .= " AND users.id_level = " . $level;
        if ($addparams != "")
            $addparams .= "&level=" . $level;
        else
            $addparams = "level=" . $level;
    }
    
    $order_param = 3;
    // getting order
    if (isset($_GET["order"])) {
        $order_param = (int)$_GET["order"];
        switch ($order_param) {
            case 1:
                $order = "username";
                break;
            
            case 2:
                $order = "level";
                break;
            
            case 3:
                $order = "joined";
                break;
            
            case 4:
                $order = "lastconnect";
                break;
            
            case 5:
                $order = "flag";
                break;
            
            case 6:
                $order = "ratio";
                break;
            
            default:
                $order = "joined";
        }
    } else
        $order = "joined";
    
    if (isset($_GET["by"])) {
        $by_param = (int)$_GET["by"];
        $by       = ($by_param == 1 ? "ASC" : "DESC");
    } else
        $by = "ASC";
    
    if ($addparams != "")
        $addparams .= "&";
    
    $scriptname = security::html_safe($_SERVER["PHP_SELF"]);
    
    $res = $db->query("SELECT COUNT(*) FROM users INNER JOIN users_level ON users.id_level = users_level.id WHERE users.id > 1 " . $where);
    $row   = $res->fetch_row();
    $count = (int)$row[0];
	
    list($pagertop, $pagerbottom, $limit) = misc::pager(20, $count, "users.php?" . $addparams . "order=" . $order_param . "&by=" . $by_param . "&");
    
    if ($by == "ASC")
        $mark = "&nbsp;&#8593";
    else
        $mark = "&nbsp;&#8595";
    
    ?>
        <div align='center'>
        <form action='users.php' name='ricerca' method='get'>
           <table border='0' class='lista'>
           <tr>
           <td class='block'><?php echo FIND_USER; ?></td>
           <td class='block'><?php echo USER_LEVEL; ?></td>
           <td class='block'>&nbsp;</td>
           </tr>
           <tr>
           <td><input type='text' name='searchtext' size='30' maxlength='50' value='<?php echo $search; ?>' /></td>
    <?php
    print("<td><select name='level'>");
    print("<option value='0'" . ($level == 0 ? " selected='selected' " : "") . ">" . ALL . "</option>");
    $res = $db->query("SELECT id, level FROM users_level WHERE id_level > 1 ORDER BY id_level");
    while ($row = $res->fetch_array(MYSQLI_BOTH)) {
        $select = "<option value='" . (int)$row["id"] . "'";
        if ($level == $row["id"])
            $select .= "selected='selected'";
        $select .= ">" . security::html_safe($row["level"]) . "</option>\n";
        print $select;
    }
    print("</select></td>");
    ?>
        </td>
        <td><input type='submit' value='<?php echo SEARCH; ?>' /></td>
        </tr>
    </table>
    </form>
    <?php
    print $pagertop;
    ?>
    <table class='lista' width='95%'>
        <tr>
        <td class='header' align='center'>
	<?php
    echo "<a href='" . $scriptname . "?" . $addparams . "" . "order=1&by=" . ($order == "username" && $by == "ASC" ? "2" : "1") . "'>" . USER_NAME . "</a>" . ($order == "username" ? $mark : "");
    ?>
	    </td>
        <td class='header' align='center'>
	<?php
    echo "<a href='" . $scriptname . "?" . $addparams . "" . "order=2&by=" . ($order == "level" && $by == "ASC" ? "2" : "1") . "'>" . USER_LEVEL . "</a>" . ($order == "level" ? $mark : "");
    ?>
	    </td>
        <td class='header' align='center'>
	<?php
    echo "<a href='" . $scriptname . "?" . $addparams . "" . "order=3&by=" . ($order == "joined" && $by == "ASC" ? "2" : "1") . "'>" . USER_JOINED . "</a>" . ($order == "joined" ? $mark : "");
    ?>
	    </td>
        <td class='header' align='center'>
	<?php
    echo "<a href='" . $scriptname . "?" . $addparams . "" . "order=4&by=" . ($order == "lastconnect" && $by == "ASC" ? "2" : "1") . "'>" . USER_LASTACCESS . "</a>" . ($order == "lastconnect" ? $mark : "");
    ?>
	    </td>
        <td class='header' align='center'>
	<?php
    echo "<a href='" . $scriptname . "?" . $addparams . "" . "order=5&by=" . ($order == "flag" && $by == "ASC" ? "2" : "1") . "'>" . PEER_COUNTRY . "</a>" . ($order == "flag" ? $mark : "");
    ?>
	    </td>
        <td class='header' align='center'>
	<?php
    echo "<a href='" . $scriptname . "?" . $addparams . "" . "order=6&by=" . ($order == "ratio" && $by == "ASC" ? "2" : "1") . "'>" . RATIO . "</a>" . ($order == "ratio" ? $mark : "");
    ?>
	    </td>
    <?php
    if (user::$current["uid"] > 1) {
    ?>
	    <td class='header' align='center'><?php echo PM; ?></td>
	<?php
    }

    if (user::$current["edit_users"] == "yes")
        print("<td class='header' align='center'>" . EDIT . "</td>");
    if (user::$current["delete_users"] == "yes")
        print("<td class='header' align='center'>" . DELETE . "</td>");
    else
        print("</tr>");
	
    $query  = "SELECT prefixcolor, suffixcolor, users.id, downloaded, uploaded, IF(downloaded > 0, uploaded / downloaded, 0) AS ratio, username, level, UNIX_TIMESTAMP(joined) AS joined, UNIX_TIMESTAMP(lastconnect) AS lastconnect, flag, flagpic, name 
	    FROM users INNER JOIN users_level ON users.id_level = users_level.id LEFT JOIN countries ON users.flag = countries.id 
		WHERE users.id > 1 " . $where . " ORDER BY " . $order . " " . $by . " " . $limit;
    $rusers = $db->query($query);
	
    if ($rusers->num_rows == 0)
        print("<tr><td class='lista' colspan='9'>" . NO_USERS_FOUND . "</td></tr>");
    else {
        include(INCL_PATH . 'offset.php');
        while ($row_user = $rusers->fetch_array(MYSQLI_BOTH)) {
            print("<tr>\n");
            print("<td class='lista'><a href='userdetails.php?id=" . (int)$row_user["id"] . "'>" . unesc($row_user["prefixcolor"]) . security::html_safe(unesc($row_user["username"])) . unesc($row_user["suffixcolor"]) . "</a></td>");
            print("<td class='lista' align='center'>" . security::html_safe($row_user["level"]) . "</td>");
            print("<td class='lista' align='center'>" . ($row_user["joined"] == 0 ? NOT_AVAILABLE : date("d/m/Y H:i:s", $row_user["joined"] - $offset)) . "</td>");
            print("<td class='lista' align='center'>" . ($row_user["lastconnect"] == 0 ? NOT_AVAILABLE : date("d/m/Y H:i:s", $row_user["lastconnect"] - $offset)) . "</td>");
            print("<td class='lista' align='center'>" . ($row_user["flag"] == 0 ? "<img src='images/flag/unknown.gif' alt='" . UNKNOWN . "' title='" . UNKNOWN . "' />" : "<img src='images/flag/" . $row_user['flagpic'] . "' alt='" . security::html_safe($row_user['name']) . "' title='" . security::html_safe($row_user['name']) . "' />") . "</td>");
            //user ratio
            if (max(0, (int)$row_user["downloaded"]) > 0)
                $ratio = number_format((int)$row_user["uploaded"] / (int)$row_user["downloaded"], 2);
            else
                $ratio = "&infin;";
            print("<td class='lista' align='center'>" . $ratio . "</td>");
            if (user::$current["uid"] > 1)
                print("<td class='lista' align='center'><a href='usercp.php?do=pm&action=edit&uid=" . user::$current['uid'] ."&what=new&to=" . urlencode(security::html_safe(unesc($row_user["username"]))) . "'>" . image_or_link($STYLEPATH . "/pm.png", "", "PM") . "</a></td>");
            if (user::$current["edit_users"] == "yes")
                print("<td class='lista' align='center'><a href='account.php?act=mod&uid=" . (int)$row_user["id"] . "&returnto=" . urlencode("users.php") . "'>" . image_or_link($STYLEPATH . "/edit.png", "", EDIT) . "</a></td>");
            if (user::$current["delete_users"] == "yes")
                print("<td class='lista' align='center'><a onclick='return confirm('" . AddSlashes(DELETE_CONFIRM) . "')' href='account.php?act=del&uid=" . (int)$row_user["id"] . "&returnto=" . urlencode("users.php") . "'>" . image_or_link($STYLEPATH . "/delete.png", "", DELETE) . "</a></td>");
            print("</tr>\n");
        }
    }
    print("</table>\n</div>\n<br />");
}

function is_valid_id($id)
{
    return is_numeric($id) && ($id > 0) && (floor($id) == $id);
}

function get_date_time($timestamp = 0)
{
    include(INCL_PATH . 'offset.php');
    if ($timestamp)
        return date("d/m/Y H:i:s", $timestamp - $offset);
    else
        return gmdate("d/m/Y H:i:s");
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
    $mins  = floor((vars::$timestamp - $ts) / 60);
    $hours = floor($mins / 60);
    $mins -= $hours * 60;
    $days = floor($hours / 24);
    $hours -= $days * 24;
    $weeks = floor($days / 7);
    $days -= $weeks * 7;
    $t = "";
    if ($weeks > 0)
        return $weeks ." week" . ($weeks > 1 ? "s" : "");
    if ($days > 0)
        return $days . " day" . ($days > 1 ? "s" : "");
    if ($hours > 0)
        return $hours . " hour" . ($hours > 1 ? "s" : "");
    if ($mins > 0)
        return $mins . " min" . ($mins > 1 ? "s" : "");
    return "< 1 min";
}

function sql_timestamp_to_unix_timestamp($s)
{
    return mktime(utf8::substr($s, 11, 2), utf8::substr($s, 14, 2), utf8::substr($s, 17, 2), utf8::substr($s, 5, 2), utf8::substr($s, 8, 2), utf8::substr($s, 0, 4));
}

function gmtime()
{
    return strtotime(get_date_time());
}

function sqlerr($file = '', $line = '')
{
    global $db;
	
    print("<table border='0' bgcolor='blue' align='left' cellspacing='0' cellpadding='10' style='background: blue'>" . "<tr><td class='embedded'><font color='white'><h1>" . ERR_SQL_ERR . "</h1>\n" . "<b>" . $db->error . ($file != '' && $line != '' ? "<p>in " . $file . ", line " . $line . "</p>" : "") . "</b></font></td></tr></table>");
    die;
}

// v.1.3
function write_log($text, $reason = "add")
{
    GLOBAL $db, $LOG_ACTIVE;
    
    if ($LOG_ACTIVE) {
        $text   = sqlesc($text);
        $reason = sqlesc($reason);
        $db->query("INSERT INTO logs (added, txt, type, user) VALUES(UNIX_TIMESTAMP(), " . $text . ", " . $reason . ", '" . user::$current["username"] . "')") or sqlerr(__FILE__, __LINE__);
    }
}

// EOF

?>
