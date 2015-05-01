<?php
/*
* BtiTracker v1.5.0 is a php tracker system for BitTorrent, easy to setup and configure.
* This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
* Updated and Maintained by Yupy.
* Copyright (C) 2004-2014 Btiteam.org
*/

function summaryAdd($column, $value, $abs = false)
{
	if (isset($GLOBALS["summaryupdate"][$column]))
	{
		if (!$abs) $GLOBALS["summaryupdate"][$column][0]+= $value;
		else show_error("Tracker bug calling summaryAdd");
	}
	else
	{
		$GLOBALS["summaryupdate"][$column][0] = $value;
		$GLOBALS["summaryupdate"][$column][1] = $abs;
	}
}

ignore_user_abort(1);

$GLOBALS["peer_id"] = '';
$summaryupdate = array();
$BASEPATH = dirname(__FILE__);

require($BASEPATH . '/include/defines.php');
require($BASEPATH . '/include/config.php');
require($BASEPATH . '/include/common.php');
require($BASEPATH . '/include/ann_vars.php');
require_once($BASEPATH . '/classes/class.Vars.php');

error_reporting(0);

/*
* Connect to Database.
*/
$db = new mysqli($dbhost, $dbuser, $dbpass, $database);
/*
* This is the "official" OO way to do it,
* BUT $connect_error was broken until PHP 5.2.9 and 5.3.0.
*/
if ($db->connect_error)
{
	die('Connect Error (' . $db->connect_errno . ') ' . $db->connect_error);
}

if (isset($_GET["pid"])) 
    $pid = $db->real_escape_string($_GET["pid"]);
else
    $pid = '';

if (strpos($pid, "?"))
{
	$tmp = substr($pid, strpos($pid, "?"));
	$pid = substr($pid, 0, strpos($pid, "?"));
	$tmpname = substr($tmp, 1, strpos($tmp, "=") - 1);
	$tmpvalue = substr($tmp, strpos($tmp, "=") + 1);
	$_GET[$tmpname] = $tmpvalue;
}

// Many thanks to KktoMx for figuring out this head-ache causer,
// and to bideomex for showing me how to do it PROPERLY... :)
if (get_magic_quotes_gpc())
{
	$info_hash = bin2hex(stripslashes($_GET["info_hash"]));
	$peer_id = bin2hex(stripslashes($_GET["peer_id"]));
}
else
{
	$info_hash = bin2hex($_GET["info_hash"]);
	$peer_id = bin2hex($_GET["peer_id"]);
}

if ($GZIP_ENABLED)
{
	if (stristr($_SERVER["HTTP_ACCEPT_ENCODING"], "gzip") && extension_loaded('zlib') && ini_get("zlib.output_compression") == 0)
	{
		if (ini_get('output_handler') != 'ob_gzhandler' && !$iscompact)
		{
			ob_start("ob_gzhandler");
		}
		else
		{
			ob_start();
		}
	}
	else
	{
		ob_start();
	}
}

header("Content-type: text/plain");
header("Pragma: no-cache");

// Error: no web browsers allowed
$agent = $db->real_escape_string($agent);

// Deny access made with a browser...
if (stripos($agent, 'Mozilla') !== false || stripos($agent, 'Opera') !== false || stripos($agent, 'Links') !== false || stripos($agent, 'Lynx') !== false || stripos($agent, 'Wget') !== false || strpos($peer_id, 'OP') === 0)
{
	header("HTTP/1.0 500 Bad Request");
	die("This a a bittorrent application and can't be loaded into a browser");
}

if (is_float($uploaded) || is_float($downloaded) || is_float($left))
{
    show_error("Stats Gone Crazy !");
}

if (!$sp_compact)
{
    show_error("Your client does not support the 'compact' tracker feature, please change/upgrade your torrent client to one that does.");
}

if ($uploaded < 0)
{
    show_error("Invalid upload (less than 0)");
}

if ($downloaded < 0)
{
    show_error("Invalid download (less than 0)");
}

if ($left < 0)
{
    show_error("Invalid left (less than 0)");
}

// check if al needed information is sent by the client
if (!isset($_GET["port"]) || !isset($_GET["downloaded"]) || !isset($_GET["uploaded"]) || !isset($_GET["left"]))
    show_error("Invalid information received from BitTorrent client");

$port = (int)$_GET["port"];
$ip = $db->real_escape_string(str_replace("::ffff:", "", vars::$realip));

// IP Banned ?? -> thank you to stunnabbz for post bug on sf.net
$nip = ip2long($ip);
$res = $db->query("SELECT * FROM bannedip WHERE '" . $nip . "' >= first AND '" . $nip . "' <= last") or error_log(__FILE__ . " - " . __LINE__);

if ($res->num_rows > 0)
{
	show_error("You are not authorized to use this tracker (" . $SITENAME . ") -- Your IP address (" . $ip . ") is BANNED.");
	die();
}
// end banned IP

// if private announce turned on and PID empty string or not send by client
if (($pid == "" || !$pid) && $PRIVATE_ANNOUNCE)
    show_error("Please redownload the torrent. PID system is active and pid was not found in the torrent");

// PID turned on
if ($PRIVATE_ANNOUNCE)
{
	$respid = $db->query("SELECT users.*, level, can_download, WT FROM users INNER JOIN users_level on users.id_level = users_level.id WHERE pid = '" . $pid . "' LIMIT 1");
	if (!$respid || $respid->num_rows != 1)
	    show_error("Invalid PID (private announce): " . $pid . ". Please redownload torrent from " . $BASEURL);
	else
	{
		$rowpid = $respid->fetch_array(MYSQLI_BOTH);
		if ($rowpid["can_download"] != "yes" && $PRIVATE_ANNOUNCE)
		    show_error("Sorry your level (" . htmlspecialchars($rowpid['level']) . ") is not allowed to download from " . $BASEURL);

		// waittime
		elseif ($rowpid["WT"] > 0)
		{
			$wait = 0;
			if (intval($rowpid['downloaded']) > 0)
			    $ratio = number_format((float)$rowpid['uploaded'] / (float)$rowpid['downloaded'], 2);
			else
			    $ratio = 0.0;

            $announce_wait_time = $BASEPATH . 'cache/announce_wait_time_' . $info_hash . '.txt';
            $announce_wait_time_expire = 5 * 60;

            if (file_exists($announce_wait_time) && is_array(unserialize(file_get_contents($announce_wait_time))) && (vars::$timestamp - filemtime($announce_wait_time)) < $announce_wait_time_expire) {
                $added = unserialize(@file_get_contents($announce_wait_time));
            } else {
			    $res2 = $db->query("SELECT UNIX_TIMESTAMP(data) AS data, uploader FROM namemap WHERE info_hash = '" . $info_hash . "'");
			    $added = $res2->fetch_array(MYSQLI_BOTH);

		        $handle = fopen($announce_wait_time, "w+");
                fwrite($handle, serialize($added));
                fclose($handle);
            }

			$vz = $added["data"];
			$timer = floor((vars::$timestamp - $vz) / 3600);
			if ($ratio < 1.0 && $rowpid['id'] != $added["uploader"])
			{
				$wait = (int)$rowpid["WT"];
			}

			$wait-= $timer;
			if ($wait <= 0)
			    $wait = 0;
			elseif ($wait != 0 && $left != 0)
			{
				show_error(htmlspecialchars($rowpid["username"]) . " your Waiting Time = " . $wait . " h");
			}
		}
	}
}
else
{
	// PID turned off
	$respid = $db->query("SELECT users.*, level, can_download, WT FROM users INNER JOIN users_level ON users.id_level = users_level.id WHERE users.cip = '" . $ip . "' LIMIT 1");

	if (!$respid || $respid->num_rows != 1) // maybe it's guest with new query I must found at least guest user
	    $respid = $db->query("SELECT users.*, level, can_download, WT FROM users INNER JOIN users_level ON users.id_level = users_level.id WHERE users.id_level = 1 LIMIT 1");

	if (!$respid || $respid->num_rows != 1)
	{
		// do nothing but tracker is misconfigured!!!
		// guest user not found...
	}
	else
	{
		$rowpid = $respid->fetch_array(MYSQLI_BOTH);
		if ($rowpid["can_download"] != "yes")
		    show_error("Sorry your level (" . htmlspecialchars($rowpid['level']) . ") is not allowed to download from " . $BASEURL);

		// waittime
		elseif ($rowpid["WT"] > 0)
		{
			$wait = 0;
			if (intval($rowpid['downloaded']) > 0)
			    $ratio = number_format((float)$rowpid['uploaded'] / (float)$rowpid['downloaded'], 2);
			else
			    $ratio = 0.0;

            $announce_wait_time = $BASEPATH . 'cache/announce_wait_time_' . $info_hash . '.txt';
            $announce_wait_time_expire = 5 * 60;

            if (file_exists($announce_wait_time) && is_array(unserialize(file_get_contents($announce_wait_time))) && (vars::$timestamp - filemtime($announce_wait_time)) < $announce_wait_time_expire) {
                $added = unserialize(@file_get_contents($announce_wait_time));
            } else {
			    $res2 = $db->query("SELECT UNIX_TIMESTAMP(data) AS data, uploader FROM namemap WHERE info_hash = '" . $info_hash . "'");
			    $added = $res2->fetch_array(MYSQLI_BOTH);

		        $handle = fopen($announce_wait_time, "w+");
                fwrite($handle, serialize($added));
                fclose($handle);
            }

			$vz = $added["data"];
			$timer = floor((vars::$timestamp - $vz) / 3600);
			if ($ratio < 1.0 && $rowpid['id'] != $added["uploader"])
			{
				$wait = (int)$rowpid["WT"];
			}

			$wait-= $timer;
			if ($wait <= 0) $wait = 0;
			elseif ($wait != 0 && $left != 0)
			{
				show_error(htmlspecialchars($rowpid["username"]) . " your Waiting Time = " . $wait . " h");
			}
		}
	}
}

if (isset($_GET["event"]))
    $event = $_GET["event"];
else
    $event = '';

if (!isset($GLOBALS["ip_override"]))
    $GLOBALS["ip_override"] = true;

if (isset($_GET["numwant"]))
    if ($_GET["numwant"] < $GLOBALS["maxpeers"] && $_GET["numwant"] >= 0)
	    $GLOBALS["maxpeers"] = (int)$_GET["numwant"];

if (isset($_GET["trackerid"]))
{
	if (is_numeric($_GET["trackerid"]))
	    $GLOBALS["trackerid"] = $db->real_escape_string($_GET["trackerid"]);
}

if (!is_numeric($port) || !is_numeric($downloaded) || !is_numeric($uploaded) || !is_numeric($left))
    show_error("Invalid numerical field(s) from client");

// Checks
// Upgrade holdover: check for unset directives
if (!isset($GLOBALS["countbytes"]))
    $GLOBALS["countbytes"] = true;

if (!isset($GLOBALS["peercaching"]))
    $GLOBALS["peercaching"] = false;

/* 
* Returns true if the user is firewalled, NAT'd, or whatever.
* The original tracker had its --nat_check parameter, so
* here is my version.
*
* This code has proven itself to be sufficiently correct,
* but will consume system resources when a lot of httpd processes
* are lingering around trying to connect to remote hosts.
* Consider disabling it under higher loads.
*/
function isFireWalled($hash, $peerid, $ip, $port)
{
	// NAT checking off?
	if (!$GLOBALS["NAT"]) return false;
	$protocol_name = 'BitTorrent protocol';
	$theError = '';

	// Hoping 10 seconds will be enough
	$fd = fsockopen($ip, $port, $errno, $theError, 10);

	if (!$fd)
	    return true;

	stream_set_timeout($fd, 5, 0);
	fwrite($fd, chr(strlen($protocol_name)) . $protocol_name . hex2bin("0000000000000000") . hex2bin($hash));
	$data = fread($fd, strlen($protocol_name) + 1 + 20 + 20 + 8);

	// ideally...
	fclose($fd);
	$offset = 0;

	// First byte: strlen($protocol_name), then the protocol string itself
	if (ord($data[$offset]) != strlen($protocol_name)) return true;
	$offset++;
	if (substr($data, $offset, strlen($protocol_name)) != $protocol_name) return true;
	$offset+= strlen($protocol_name);

	// 8 bytes reserved, ignore
	$offset+= 8;

	// Download ID (hash)
	if (substr($data, $offset, 20) != hex2bin($hash)) return true;
	$offset+= 20;

	// Peer ID
	if (substr($data, $offset, 20) != hex2bin($peerid)) return true;
	return false;
}

function getPeerInfo($user, $hash)
{
	global $db;

	// If "trackerid" is set, let's try that
	if (isset($GLOBALS["trackerid"]))
	{
		$query = "SELECT peer_id, bytes, ip, port, status, lastupdate, sequence FROM peers WHERE sequence = {$GLOBALS["trackerid"]} AND infohash = '" . $hash . "'";
		$results = $db->query($query) or show_error("Tracker error: invalid torrent");
		$data = $results->fetch_assoc();

		if (!$data || $data["peer_id"] != $user)
		{
			// Damn, but don't crash just yet.
			$query = "SELECT peer_id, bytes, ip, port, status, lastupdate, sequence FROM peers WHERE peer_id = '" . $user . "' AND infohash = '" . $hash . "'";
			$results = $db->query($query) or showError("Tracker error: invalid torrent");
			$data = $results->fetch_assoc();
			$GLOBALS["trackerid"] = $data["sequence"];
		}
	}
	else
	{
		$query = "SELECT peer_id, bytes, ip, port, status, lastupdate, sequence FROM peers WHERE peer_id = '" . $user . "' AND infohash = '" . $hash . "'";
		$results = $db->query($query) or showError("Tracker error: invalid torrent");
		$data = $results->fetch_assoc();
		$GLOBALS["trackerid"] = $data["sequence"];
	}

	if (!($data)) return false;
	return $data;
}

// Any section of code might need to make a new peer, so this is a function here.
// I don't want to put it into funcsv2, even though it should, just for consistency's sake.
function start($info_hash, $ip, $port, $peer_id, $left, $downloaded = 0, $uploaded = 0, $upid = '')
{
	global $BASEURL, $db;

	if (isset($_GET["ip"]) && $GLOBALS["ip_override"])
	{
		// compact check: valid IP address:
		if ($_GET["ip"] != long2ip(ip2long($_GET["ip"]))) showError("Invalid IP address. Must be standard dotted decimal (hostnames not allowed)");
		$ip = $db->real_escape_string($_GET["ip"]);
	}
	else
	    $ip = vars::$ip;

	$ip = $db->real_escape_string($ip);
	$agent = $db->real_escape_string($agent);
	$remotedns = gethostbyaddr($ip);

	if (isset($_GET["ip"]))
	    $nuIP = $db->real_escape_string($_GET["ip"]);
	else
	    $nuIP = '';

	if ($remotedns == $nuIP)
	    $remotedns = "AA";
	else
	{
		$remotedns = strtoupper($remotedns);
		preg_match('/^(.+)\.([A-Z]{2,3})$/', $remotedns, $tldm);

		if (!empty($tldm[2]))
		    $remotedns = $db->real_escape_string($tldm[2]);
		else
		    $remotedns = "AA";
	}

	if ($left == 0)
	    $status = "seeder";
	else
	    $status = "leecher";

	if (@isFireWalled($info_hash, $peer_id, $ip, $port))
	    $nat = "Y";
	else
	    $nat = "N";

	$compact = $db->real_escape_string(pack('Nn', ip2long($ip) , $port));
	$peerid = $db->real_escape_string('2:ip' . strlen($ip) . ':' . $ip . '7:peer id20:' . hex2bin($peer_id) . "4:porti{$port}e");
	$no_peerid = $db->real_escape_string('2:ip' . strlen($ip) . ':' . $ip . "4:porti{$port}e");

	$results = @$db->query("INSERT INTO peers SET infohash = '" . $info_hash . "', peer_id = '" . $peer_id . "', port = '" . $port . "', ip = '" . $ip . "', lastupdate = UNIX_TIMESTAMP(), bytes = '" . $left . "', status = '" . $status . "', natuser = '" . $nat . "', client = '" . $agent . "', dns = '" . $remotedns . "', downloaded = " . $downloaded . ", uploaded = " . $uploaded . ", pid = '" . $upid . "'");

	// Special case: duplicated peer_id.
	if (!$results)
	{
		if ($db->connect_errno == 1062)
		{
			// Duplicate peer_id! Check IP address
			$peer = getPeerInfo($peer_id, $info_hash);
			if ($ip == $peer["ip"])
			{
				// Same IP address. Tolerate this error.
				return "WHERE natuser = 'N'";
			}

			// Different IP address. Assume they were disconnected, and alter the IP address.
			quickQuery("UPDATE peers SET ip = '" . $ip . "', compact = '" . $compact . "', with_peerid = '" . $peerid . "', without_peerid = '" . $no_peerid . "' WHERE peer_id = '" . $peer_id . "' AND infohash = '" . $info_hash . "'");
			return "WHERE natuser = 'N'";
		}

		error_log("BtiTracker: start: " . $db->error);
		show_error("Tracker/database error. The details are in the error log.");
	}

	$GLOBALS["trackerid"] = $db->insert_id;
	@$db->query("UPDATE peers SET sequence = '" . $GLOBALS['trackerid'] . "', compact = '" . $compact . "', with_peerid = '" . $peerid . "', without_peerid = '" . $no_peerid . "' WHERE peer_id = '" . $peer_id . "' AND infohash = '" . $info_hash . "'");

	if ($left == 0)
	{
		summaryAdd("seeds", 1);
		return "WHERE status = 'leecher' AND natuser = 'N'";
	}
	else
	{
		summaryAdd("leechers", 1);
		return "WHERE natuser = 'N'";
	}
}

// / End of function start
// default for max peers with same pid/ip
if (!isset($GLOBALS["maxseeds"]))
    $GLOBALS["maxseeds"] = 2;

if (!isset($GLOBALS["maxleech"]))
    $GLOBALS["maxleech"] = 2;

// Returns true if the torrent exists.
// Currently checks by locating the row in "summary"
// Always returns true if $dynamic_torrents=="1" unless an error occured
function verifyTorrent($hash)
{
	global $db;

	// only for internal tracked torrent!
	$query = "SELECT COUNT(*) FROM summary INNER JOIN namemap ON namemap.info_hash = summary.info_hash WHERE namemap.external = 'no' AND summary.info_hash = '" . $hash . "'";
	$results = $db->query($query);
	$res = mysqli_result($results, 0, 0);

	if ($res == 1)
	    return true;

	if ($GLOBALS["dynamic_torrents"])
	    return makeTorrent($hash);

	return false;
}

// Slight redesign of loadPeers
function getRandomPeers($hash, $where = '')
{
	global $db;

	// Don't want to send a bad "num peers" for new seeds
	$where = "WHERE infohash = '" . $hash . "'";

	if ($GLOBALS["NAT"])
	    $results = $db->query("SELECT COUNT(*) FROM peers WHERE natuser = 'N' AND infohash = '" . $hash . "'");
	else
	    $results = $db->query("SELECT COUNT(*) FROM peers WHERE infohash = '" . $hash . "'");

	$peercount = mysqli_result($results, 0, 0);

	if ($peercount < 500)
	    $query = "SELECT " . ((isset($_GET["no_peer_id"]) && $_GET["no_peer_id"] == 1) ? "" : "peer_id,") . "ip, port, status FROM peers " . $where . " ORDER BY RAND() LIMIT {$GLOBALS['maxpeers']}";
	else
	    $query = "SELECT " . ((isset($_GET["no_peer_id"]) && $_GET["no_peer_id"] == 1) ? "" : "peer_id,") . "ip, port, status FROM peers " . $where . " LIMIT " . @mt_rand(0, $peercount - $GLOBALS["maxpeers"]) . ", {$GLOBALS['maxpeers']}";

	$results = $db->query($query);

	if (!$results)
	    return false;

	$peerno = 0;
	while ($return[] = $results->fetch_assoc()) $peerno++;
	array_pop($return);
	$results->free();
	$return['size'] = $peerno;
	return $return;
}

// Transmits the actual data to the peer. No other output is permitted if
// this function is called, as that would break BEncoding.
// I don't use the bencode library, so watch out! If you add data,
// rules such as dictionary sorting are enforced by the remote side.
function sendPeerList($peers)
{
	echo "d";
	echo "8:intervali" . $GLOBALS["report_interval"] . "e";
	if (isset($GLOBALS["min_interval"])) echo "12:min intervali" . $GLOBALS["min_interval"] . "e";
	echo "5:peers";
	$size = $peers["size"];
	if (isset($_GET["compact"]) && $_GET["compact"] == '1')
	{
		$p = '';
		for ($i = 0; $i < $size; $i++) $p.= str_pad(pack("Nn", ip2long($peers[$i]['ip']) , $peers[$i]['port']) , 6);
		echo strlen($p) . ':' . $p;
	}
	else // no_peer_id or no feature supported
	{
		echo 'l';
		for ($i = 0; $i < $size; $i++)
		{
			echo "d2:ip" . strlen($peers[$i]["ip"]) . ":" . $peers[$i]["ip"];
			if (isset($peers[$i]["peer_id"])) echo "7:peer id20:" . hex2bin($peers[$i]["peer_id"]);
			echo "4:porti" . $peers[$i]["port"] . "ee";
		}

		echo "e";
	}

	if (isset($GLOBALS["trackerid"]))
	{
		// Now it gets annoying. trackerid is a string
		echo "10:tracker id" . strlen($GLOBALS["trackerid"]) . ":" . $GLOBALS["trackerid"];
	}

	echo "e";
}

// Faster pass-through version of getRandompeers => sendPeerList
// It's the only way to use cache tables. In fact, it only uses it.
function sendRandomPeers($info_hash)
{
	global $db;

	if (isset($_GET["compact"]) && $_GET["compact"] == '1')
	    $column = "compact";
	else if (isset($_GET["no_peer_id"]) && $_GET["no_peer_id"] == '1')
	    $column = "without_peerid";
	else
	    $column = "with_peerid";

	$query = "SELECT " . $column . " FROM peers WHERE infohash = '" . $info_hash . "' ORDER BY RAND() LIMIT " . (int)$GLOBALS["maxpeers"];

	echo "d";
	echo "8:intervali" . $GLOBALS["report_interval"] . "e";

	if (isset($GLOBALS["min_interval"]))
	    echo "12:min intervali" . $GLOBALS["min_interval"] . "e";

	echo "5:peers";

	$result = $db->query($query);

	if ($column == "compact")
	{
		echo ($result->num_rows * 6) . ":";

		while ($row = $result->fetch_row())
		    echo str_pad($row[0], 6);
	}
	else
	{
		echo "l";
		while ($row = $result->fetch_row)
		    echo "d" . $row[0] . "e";

		echo "e";
	}

	if (isset($GLOBALS["trackerid"]))
	    echo "10:tracker id" . strlen($GLOBALS["trackerid"]) . ":" . $GLOBALS["trackerid"];

	echo "e";
	$result->free();
}

// Deletes a peer from the system and performs all cleaning up
//  $assumepeer contains the result of getPeerInfo, or false
//  if we should grab it ourselves.
function killPeer($userid, $hash, $left, $assumepeer = false)
{
	global $db;

	if (!$assumepeer)
	{
		$peer = getPeerInfo($userid, $hash);

		if (!$peer)
		    return;

		if ($left != $peer["bytes"])
		    $bytes = bcsub($peer["bytes"], $left);
		else
		    $bytes = 0;
	}
	else
	{
		$bytes = 0;
		$peer = $assumepeer;
	}

	quickQuery("DELETE FROM peers WHERE peer_id = '" . $userid . "' AND infohash = '" . $hash . "'");
	if ($db->affected_rows == 1)
	{
		if ($peer["status"] == "leecher")
		    summaryAdd("leechers", -1);
		else
		    summaryAdd("seeds", -1);

		if ($GLOBALS["countbytes"] && ((float)$bytes) > 0)
		    summaryAdd("dlbytes", $bytes);

		if ($peer["bytes"] != 0 && $left == 0)
		    summaryAdd("finished", 1);

		summaryAdd("lastcycle", "UNIX_TIMESTAMP()", true);
	}
}

// Transfers bytes from "left" to "dlbytes" when a peer reports in.
function collectBytes($peer, $hash, $left, $downloaded = 0, $uploaded = 0, $pid = '')
{
	$peerid = (int)$peer["peer_id"];

	if (!$GLOBALS["countbytes"])
	{
		quickQuery("UPDATE peers SET lastupdate = UNIX_TIMESTAMP(), downloaded = " . $downloaded . ", uploaded = " . $uploaded . ", pid = '" . $pid . "' WHERE infohash = '" . $hash . "' AND " . (isset($GLOBALS["trackerid"]) ? "sequence = '" . $GLOBALS['trackerid'] . "'" : "peer_id = '" . $peerid . "'"));
		return;
	}

	$diff = bcsub($peer["bytes"], $left);
	quickQuery("UPDATE peers SET " . (($diff != 0) ? "bytes = '" . $left . "'," : "") . " lastupdate = UNIX_TIMESTAMP(), downloaded = " . $downloaded . ", uploaded = " . $uploaded . ", pid = '" . $pid . "' WHERE infohash = '" . $hash . "' AND " . (isset($GLOBALS["trackerid"]) ? "sequence = '" . $GLOBALS['trackerid'] . "'" : "peer_id = '" . $peerid . "'"));

	// Anti-negative clause
	if (((float)$diff) > 0)
	    summaryAdd("dlbytes", $diff);
}

function runSpeed($info_hash, $delta)
{
	global $db;

	// stick in our latest data before we calc it out
	quickQuery("INSERT IGNORE INTO timestamps (info_hash, bytes, delta, sequence) SELECT '" . $info_hash . "' AS info_hash, dlbytes, UNIX_TIMESTAMP() - lastSpeedCycle, NULL FROM summary WHERE info_hash = '" . $info_hash . "'");

    $announce_bytes_ts = $BASEPATH . 'cache/announce_bytes_timestamps_' . $info_hash . '.txt';
    $announce_bytes_ts_expire = 5 * 60;
    
    if (file_exists($announce_bytes_ts) && is_array(unserialize(file_get_contents($announce_bytes_ts))) && (vars::$timestamp - filemtime($announce_bytes_ts)) < $announce_bytes_ts_expire) {
        $data = unserialize(@file_get_contents($announce_bytes_ts));
    } else {
	    $results = $db->query('SELECT (MAX(bytes) - MIN(bytes)) / SUM(delta), COUNT(*), MIN(sequence) FROM timestamps WHERE info_hash = "' . $info_hash . '"');
	    $data = $results->fetch_row();

		$handle = fopen($announce_bytes_ts, "w+");
        fwrite($handle, serialize($data));
        fclose($handle);
    }

	summaryAdd("speed", $data[0], true);
	summaryAdd("lastSpeedCycle", "UNIX_TIMESTAMP()", true);
	@unlink($BASEPATH . 'cache/announce_count_bytes_' . $info_hash . '.txt');

	// if we have more than 20 drop the rest
	if ($data[1] == 21) {
	    quickQuery("DELETE FROM timestamps WHERE info_hash=\"$info_hash\" AND sequence = " . $data['2']);
	    @unlink($BASEPATH . 'cache/announce_bytes_timestamps_' . $info_hash . '.txt');
	} else if ($data[1] > 21) {
	    quickQuery('DELETE FROM timestamps WHERE info_hash = "' . $info_hash . '" ORDER BY sequence LIMIT ' . ($data['1'] - 20));
	    @unlink($BASEPATH . 'cache/announce_bytes_timestamps_' . $info_hash . '.txt');
	}
}

$results = $db->query("SELECT status, count(status) FROM peers WHERE " . ($PRIVATE_ANNOUNCE ? "pid = '" . $pid . "'" : "ip = '" . $ip . "'") . " AND infohash = '" . $info_hash . "' AND peer_id <> '" . $peer_id . "' GROUP BY status") or show_error("Tracker error: invalid torrent");
$status = array();

while ($resstat = $results->fetch_row())
    $status[$resstat[0]] = $resstat[1];

if (!isset($status["leecher"]))
    $status["leecher"] = 0;

if (!isset($status["seeder"]))
    $status["seeder"] = 0;

if ($status["seeder"] >= $GLOBALS["maxseeds"] || $status["leecher"] >= $GLOBALS["maxleech"])
    show_error("Sorry max peers reached! Redownload torrent from " . $BASEURL);

// UPDATE users ratio down/up for every event on every announce
// only with the difference between stored down/up and sended by client
if ($LIVESTATS)
{
	$resstat = $db->query("SELECT uploaded, downloaded FROM peers WHERE peer_id = '" . $peer_id . "' AND infohash = '" . $info_hash . "'");

	if ($resstat && $resstat->num_rows > 0)
	{
		$livestat = $resstat->fetch_array(MYSQLI_BOTH);

		// only if uploaded/downloaded are >= stored data in peer list
		if ($uploaded >= $livestat["uploaded"])
		    $newup = ($uploaded - (int)$livestat["uploaded"]);
		else
		    $newup = $uploaded;

		if ($downloaded >= $livestat["downloaded"])
		    $newdown = ($downloaded - (int)$livestat["downloaded"]);
		else
		    $newdown = $downloaded;

		quickquery("UPDATE users SET downloaded = IFNULL(downloaded, 0) + " . $newdown . ", uploaded = IFNULL(uploaded, 0) + " . $newup . " WHERE " . ($PRIVATE_ANNOUNCE ? "pid = '" . $pid . "'" : "cip = '" . $ip . "'") . "");
	}

	$resstat->free();

	// begin history - also this is registred live or not
	if ($LOG_HISTORY)
	{
		$resu = $db->query("SELECT id FROM users WHERE " . ($PRIVATE_ANNOUNCE ? "pid = '" . $pid . "'" : "cip = '" . $ip . "'") . " ORDER BY lastconnect DESC LIMIT 1");

		// if found at least one user should be 1
		if ($resu && $resu->num_rows == 1)
		{
			$curuid = $resu->fetch_array(MYSQLI_BOTH);
			quickQuery("UPDATE history SET uploaded = IFNULL(uploaded, 0) + " . $newup . ", downloaded = IFNULL(downloaded, 0) + " . $newdown . " WHERE uid = " . (int)$curuid["id"] . " AND infohash = '" . $info_hash . "'");
		}

		$resu->free();
	}
}

switch ($event)
{
	// client sent start
case "started":
	verifyTorrent($info_hash) or show_error("Torrent is not authorized for use on this tracker.");
	$start = start($info_hash, $ip, $port, $peer_id, $left, $downloaded, $uploaded, $pid);
	if ($GLOBALS["peercaching"])
	    sendRandomPeers($info_hash);
	else
	{
		$peers = getRandomPeers($info_hash, "");
		sendPeerList($peers);
	}

	// begin history
	if ($LOG_HISTORY)
	{
		$resu = $db->query("SELECT id FROM users WHERE " . ($PRIVATE_ANNOUNCE ? "pid = '" . $pid . "'" : "cip = '" . $ip . "'") . " ORDER BY lastconnect DESC LIMIT 1");

		// if found at least one user should be 1
		if ($resu && $resu->num_rows == 1)
		{
			$curuid = $resu->fetch_array(MYSQLI_BOTH);
			quickQuery("UPDATE history SET active = 'yes', agent = '" . getagent($agent, $peer_id) . "' WHERE uid = " . (int)$curuid["id"] . " AND infohash = '" . $info_hash . "'");

			// record is not present, create it (only if not seeder: original seeder don't exist in history table, other already exists)
			if ($db->affected_rows == 0 && $left > 0)
			    quickQuery("INSERT INTO history (uid, infohash, active, agent) VALUES (" . (int)$curuid["id"] . ", '" . $info_hash . "', 'yes', '" . getagent($agent, $peer_id) . "')");
		}

		$resu->free();
	}
	break;

// client sent stop
case "stopped":
	verifyTorrent($info_hash) or show_error("Torrent is not authorized for use on this tracker.");
	killPeer($peer_id, $info_hash, $left);

	// I don't know why, but the real tracker returns peers on event=stopped
	// but I'll just send an empty list. On the other hand,
	// TheSHADOW asked for this.
	if (isset($_GET["tracker"]))
	    $peers = getRandomPeers($info_hash);
	else
	    $peers = array("size" => 0);

	sendPeerList($peers);

	// update user uploaded/downloaded
	if (!$LIVESTATS)
	    @$db->query("UPDATE users SET uploaded = IFNULL(uploaded, 0) + " . $uploaded . ", downloaded = IFNULL(downloaded, 0) + " . $downloaded . " WHERE " . ($PRIVATE_ANNOUNCE ? "pid = '" . $pid . "'" : "cip = '" . $ip . "'") . " AND id > 1 LIMIT 1");

	// begin history - if LIVESTAT, only the active/agent part
	if ($LOG_HISTORY)
	{
		$resu = $db->query("SELECT id FROM users WHERE " . ($PRIVATE_ANNOUNCE ? "pid = '" . $pid . "'" : "cip = '" . $ip . "'") . " ORDER BY lastconnect DESC LIMIT 1");

		// if found at least one user should be 1
		if ($resu && $resu->num_rows == 1)
		{
			$curuid = $resu->fetch_array(MYSQLI_BOTH);
			quickQuery("UPDATE history SET active = 'no', " . ($LIVESTATS ? "" : " uploaded = IFNULL(uploaded, 0) + " . $uploaded . ", downloaded = IFNULL(downloaded, 0) + " . $downloaded . ",") . " agent = '" . getagent($agent, $peer_id) . "' WHERE uid = " . (int)$curuid["id"] . " AND infohash = '" . $info_hash . "'");
		}

		$resu->free();
	}
	break;

// client sent complete
case "completed":
	verifyTorrent($info_hash) or show_error("Torrent is not authorized for use on this tracker.");
	$peer_exists = getPeerInfo($peer_id, $info_hash);
	if (!is_array($peer_exists))
	    start($info_hash, $ip, $port, $peer_id, $left, $downloaded, $uploaded, $pid);
	else
	{
		quickQuery("UPDATE peers SET bytes = 0, status = 'seeder' WHERE sequence = '" . $GLOBALS['trackerid'] . "' AND infohash = '" . $info_hash . "'");

		// Race check
		if ($db->affected_rows == 1)
		{
			summaryAdd("leechers", -1);
			summaryAdd("seeds", 1);
			summaryAdd("finished", 1);
			summaryAdd("lastcycle", "UNIX_TIMESTAMP()", true);
		}
	}

	collectBytes($peer_exists, $info_hash, $left, $downloaded, $uploaded, $pid);
	$peers = getRandomPeers($info_hash);
	sendPeerList($peers);

	// begin history
	if ($LOG_HISTORY)
	{
		$resu = $db->query("SELECT id FROM users WHERE " . ($PRIVATE_ANNOUNCE ? "pid = '" . $pid . "'" : "cip = '" . $ip . "'") . " ORDER BY lastconnect DESC LIMIT 1");

		// if found at least one user should be 1
		if ($resu && $resu->num_rows == 1)
		{
			$curuid = $resu->fetch_array(MYSQLI_BOTH);

			// if user has already completed this torrent, mysql will give error because of unique index (uid+infohash)
			// upload/download will be updated on stop event...
			// record should already exist (created on stated event)
			quickQuery("UPDATE history SET date = UNIX_TIMESTAMP(), active = 'yes', agent = '" . getagent($agent, $peer_id) . "' WHERE uid = " . (int)$curuid["id"] . " AND infohash = '" . $info_hash . "'");

			// record is not present, create it
			if ($db->affected_rows == 0)
			    quickQuery("INSERT INTO history (uid, infohash, date, active, agent) VALUES (" . (int)$curuid["id"] . ", '" . $info_hash . "', UNIX_TIMESTAMP(), 'yes', '" . getagent($agent, $peer_id) . "')");
		}

		$resu->free();
	}
	break;

// client sent no event
case '':
	verifyTorrent($info_hash) or show_error("Torrent is not authorized for use on this tracker.");
	$peer_exists = getPeerInfo($peer_id, $info_hash);
	$where = "WHERE natuser = 'N'";

	if (!is_array($peer_exists))
	    $where = start($info_hash, $ip, $port, $peer_id, $left, $downloaded, $uploaded, $pid);

	if ($peer_exists["bytes"] != 0 && $left == 0)
	{
		quickQuery("UPDATE peers SET bytes = 0, status = 'seeder' WHERE sequence = '" . $GLOBALS['trackerid'] . "' AND infohash = '" . $info_hash . "'");

		if ($db->affected_rows == 1)
		{
			summaryAdd("leechers", -1);
			summaryAdd("seeds", 1);
			summaryAdd("finished", 1);
			summaryAdd("lastcycle", "UNIX_TIMESTAMP()", true);
		}
	}

	collectBytes($peer_exists, $info_hash, $left, $downloaded, $uploaded, $pid);

	if ($GLOBALS["peercaching"])
	    sendRandomPeers($info_hash);
	else
	{
		$peers = getRandomPeers($info_hash, '');
		sendPeerList($peers);
	}
	break;

// not valid event
default:
	show_error("Invalid event = from client.");
}

if ($GLOBALS["countbytes"])
{
	// Once every minute or so, we run the speed update checker.
    $announce_count_bytes = $BASEPATH . 'cache/announce_count_bytes_' . $info_hash . '.txt';
    $announce_count_bytes_expire = 5 * 60;
    
    if (file_exists($announce_count_bytes) && is_array(unserialize(file_get_contents($announce_count_bytes))) && (vars::$timestamp - filemtime($announce_count_bytes)) < $announce_count_bytes_expire) {
        $results = unserialize(@file_get_contents($announce_count_bytes));
    } else {
	    $query = @$db->query("SELECT UNIX_TIMESTAMP() - lastSpeedCycle FROM summary WHERE info_hash = '" . $info_hash . "'");
	    $results = $query->fetch_row();

		$handle = fopen($announce_count_bytes, "w+");
        fwrite($handle, serialize($results));
        fclose($handle);
    }

	if ($results[0] >= 60)
	    @runSpeed($info_hash, $results[0]);
}

// Finally, it's time to do stuff to the summary table.
if (!empty($summaryupdate))
{
	$stuff = '';
	foreach($summaryupdate as $column => $value)
	{
		$stuff.= ', ' . $column . ($value[1] ? "=" : "=" . $column . "+") . $value[0];
	}

	$db->query("UPDATE summary SET " . substr($stuff, 1) . " WHERE info_hash = '" . $info_hash . "'");
}

// generaly not needed, but
// just in case server don't close connection
$db->close();

?>
