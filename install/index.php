<?php
ob_start();
# IMPORTANT: Do not edit below unless you know what you are doing!
define ( 'IN_INSTALL', true );
define ( 'THIS_ROOT_PATH', './' );
define ( 'ROOT_PATH', '../' );
define ( 'INSTALL_VERSION', 'v.1.0 by JBoy (Based on Xam)' );
define ( 'TRACKER_VERSION', 'BtiTracker (1.5.0)' );
define ( 'TIMENOW', time());
define ('VERSION','0.24b');
define ('DATA_CHUNK_LENGTH',16384);  // How many chars are read per time
define ('MAX_QUERY_LINES',300);      // How many lines may be considered to be one query (except text lines)
error_reporting  (E_ERROR | E_WARNING | E_PARSE);
@set_magic_quotes_runtime(0);
ignore_user_abort(1);
@set_time_limit(0);
@ini_set('auto_detect_line_endings', true);
require_once( THIS_ROOT_PATH.'functions.php' );
require_once( ROOT_PATH. 'include/config.php');
$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : 'step0');
$allowed_actions = array('step0','step1','step2','step3','step4','step5','save_db','save_admin');
if (!in_array($action, $allowed_actions))
    $action = 'step0';
?>
<html>
<head>
<title><?php echo TRACKER_VERSION;?> INSTALLATION <?php echo INSTALL_VERSION;?></title>
<link rel="stylesheet" href="<?php echo ROOT_PATH;?>/style/base/torrent.css" type="text/css">
</head>
<body>
<?php
// CHECK IF IT MAY CONTINUE
if ( file_exists( THIS_ROOT_PATH.'install.lock') )
{
    step("Installation ERROR!","ERROR!","*");
    die("<center>For security reasons, this installer is locked!<br>Please (via FTP) remove the 'install.lock' file before continue.</center>");
}

// SAVE THE DATABASE
if ($action == 'save_db'){
    step("Tracker Configuration (DONE!)","Tracker Setup","2");

            @chmod("../include/config.php",0777);
                 // if I get an error chmod, I'll try to put change into the file
                 $fd = fopen("../include/config.php", "w") or die("Can't save configurations");
                 $foutput ="<?php\n/* Tracker Configuration\n *\n *  This file provides configuration informatino for\n *  the tracker. The user-editable variables are at the top. It is\n *  recommended that you do not change the database settings\n *  unless you know what you are doing.\n */\n\n";
                 $foutput.= "//Maximum reannounce interval.\n";
                 $foutput.= "\$GLOBALS[\"report_interval\"] = " . $_POST["rinterval"] . ";\n";
                 $foutput.= "//Minimum reannounce interval. Optional.\n";
                 $foutput.= "\$GLOBALS[\"min_interval\"] = " . $_POST["mininterval"] . ";\n";
                 $foutput.= "//Number of peers to send in one request.\n";
                 $foutput.= "\$GLOBALS[\"maxpeers\"] = " . $_POST["maxpeers"] . ";\n";
                 $foutput.= "//If set to true, then the tracker will accept any and all\n";
                 $foutput.= "//torrents given to it. Not recommended, but available if you need it.\n";
                 $foutput.= "\$GLOBALS[\"dynamic_torrents\"] = " . $_POST["dynamic"] . ";\n";
                 $foutput.= "// If set to true, NAT checking will be performed.\n";
                 $foutput.= "// This may cause trouble with some providers, so it's\n";
                 $foutput.= "// off by default.\n";
                 $foutput.= "\$GLOBALS[\"NAT\"] = " . $_POST["nat"] . ";\n";
                 $foutput.= "// Persistent connections: true or false.\n";
                 $foutput.= "// Check with your webmaster to see if you're allowed to use these.\n";
                 $foutput.= "// not recommended, only if you get very higher loads, but use at you own risk.\n";
                 $foutput.= "\$GLOBALS[\"persist\"] = " . $_POST["persist"] . ";\n";
                 $foutput.= "// Allow users to override ip= ?\n";
                 $foutput.= "// Enable this if you know people have a legit reason to use\n";
                 $foutput.= "// this function. Leave disabled otherwise.\n";
                 $foutput.= "\$GLOBALS[\"ip_override\"] = " . $_POST["override"] . ";\n";
                 $foutput.= "// For heavily loaded trackers, set this to false. It will stop count the number\n";
                 $foutput.= "// of downloaded bytes and the speed of the torrent, but will significantly reduce\n";
                 $foutput.= "// the load.\n";
                 $foutput.= "\$GLOBALS[\"countbytes\"] = " . $_POST["countbyte"] . ";\n";
                 $foutput.= "// Table caches!\n";
                 $foutput.= "// Lowers the load on all systems, but takes up more disk space.\n";
                 $foutput.= "// You win some, you lose some. But since the load is the big problem,\n";
                 $foutput.= "// grab this.\n";
                 $foutput.= "//\n";
                 $foutput.= "// Warning! Enable this BEFORE making torrents, or else run makecache.php\n";
                 $foutput.= "// immediately, or else you'll be in deep trouble. The tables will lose\n";
                 $foutput.= "// sync and the database will be in a somewhat \"stale\" state.\n";
                 $foutput.= "\$GLOBALS[\"peercaching\"] = " . $_POST["caching"] . ";\n";
                 $foutput.= "//Max num. of seeders with same PID.\n";
                 $foutput.= "\$GLOBALS[\"maxseeds\"] = " . $_POST["maxseeds"] . ";\n";
                 $foutput.= "//Max num. of leechers with same PID.\n";
                 $foutput.= "\$GLOBALS[\"maxleech\"] = " . $_POST["maxleech"] . ";\n";
                 $foutput.= "\n/////////// End of User Configuration ///////////\n";
                 $foutput.= "\$dbhost = \"". $_POST["dbhost"] ."\";\n";
                 $foutput.= "\$dbuser = \"". $_POST["dbuser"] ."\";\n";
                 $foutput.= "\$dbpass = \"". $_POST["dbpwd"] ."\";\n";
                 $foutput.= "\$database = \"" .$_POST["dbname"] . "\";\n";
                 $foutput.= "//Tracker's name\n";
                 $foutput.= "\$SITENAME=\"".$_POST["trackername"]."\";\n";
                 $foutput.= "//Tracker's Base URL\n";
                 $foutput.= "\$BASEURL=\"".$_POST["trackerurl"]."\";\n";
                 $foutput.= "// tracker's announce urls, can be more than one\n";
                 $foutput.= "\$TRACKER_ANNOUNCEURLS=array();\n";
                 $tannounceurls=array();
                 $tannounceurls=explode("\n",$_POST["tracker_announceurl"]);
                 foreach($tannounceurls as $taurl)
                      {
                      $taurl=str_replace(array("\n","\r\n","\r"),"",$taurl);
                      if ($taurl!="")
                        $foutput.= "\$TRACKER_ANNOUNCEURLS[]=\"".trim($taurl)."\";\n";
                      }
                 $foutput.= "//Tracker's email (owner email)\n";
                 $foutput.= "\$SITEEMAIL=\"".$_POST["trackeremail"]."\";\n";
                 $foutput.= "//Torrent's DIR\n";
                 $foutput.= "\$TORRENTSDIR=\"".$_POST["torrentdir"]."\";\n";
                 $foutput.= "//validation type (must be none, user or admin\n";
                 $foutput.= "//none=validate immediatly, user=validate by email, admin=manually validate\n";
                 $foutput.= "\$VALIDATION=\"".$_POST["validation"]."\";\n";
                 $foutput.= "//Use or not the image code for new users' registration\n";
                 $foutput.= "\$USE_IMAGECODE=".$_POST["imagecode"].";\n";
                 $foutput.= "// interval for sanity check (good = 10 minutes)\n";
                 $foutput.= "\$clean_interval=\"".$_POST["sinterval"]."\";\n";
                 $foutput.= "// interval for updating external torrents (depending of how many external torrents)\n";
                 $foutput.= "\$update_interval=\"".$_POST["uinterval"]."\";\n";
                 $foutput.= "// forum link or internal (empty = internal) or none\n";
                 $foutput.= "\$FORUMLINK=\"".$_POST["f_link"]."\";\n";
                 $foutput.= "// If you want to allow users to upload external torrents values true/false\n";
                 $foutput.= "\$EXTERNAL_TORRENTS=".$_POST["exttorrents"].";\n";
                 $foutput.= "// Enable/disable GZIP compression, can save a lot of bandwidth\n";
                 $foutput.= "\$GZIP_ENABLED=".$_POST["gzip_enabled"].";\n";
                 $foutput.= "// Show/Hide bottom page information on script's generation time and gzip\n";
                 $foutput.= "\$PRINT_DEBUG=".$_POST["show_debug"].";\n";
                 $foutput.= "// Enable/disable DHT network, add private flag to \"info\" in torrent\n";
                 $foutput.= "\$DHT_PRIVATE=".$_POST["dht"].";\n";
                 $foutput.= "// Enable/disable Live Stats (up/down updated every announce) WARNING CAN DO HIGH SERVER LOAD!\n";
                 $foutput.= "\$LIVESTATS=".$_POST["livestat"].";\n";
                 $foutput.= "// Enable/disable Site log\n";
                 $foutput.= "\$LOG_ACTIVE=".$_POST["logactive"].";\n";
                 $foutput.= "//Enable Basic History (torrents/users)\n";
                 $foutput.= "\$LOG_HISTORY=".$_POST["loghistory"].";\n";
                 $foutput.= "// Default language (used for guest)\n";
                 $foutput.= "\$DEFAULT_LANGUAGE=".$_POST["default_langue"].";\n";
                 $foutput.= "// Default charset (used for guest)\n";
                 $foutput.= "\$GLOBALS[\"charset\"]=\"".$_POST["charset"]."\";\n";
                 $foutput.= "// Default style  (used for guest)\n";
                 $foutput.= "\$DEFAULT_STYLE=".$_POST["default_style"].";\n";
                 $foutput.= "// Maximum number of users (0 = no limits)\n";
                 $foutput.= "\$MAX_USERS=".$_POST["maxusers"].";\n";
                 $foutput.= "//torrents per page\n";
                 $foutput.= "\$ntorrents =\"".$_POST["ntorrents"]."\";\n";
                 $foutput.= "//private announce (true/false), if set to true don't allow non register user to download\n";
                 $foutput.= "\$PRIVATE_ANNOUNCE =".$_POST["p_announce"].";\n";
                 $foutput.= "//private scrape (true/false), if set to true don't allow non register user to scrape (for stats)\n";
                 $foutput.= "\$PRIVATE_SCRAPE =".$_POST["p_scrape"].";\n";
                 $foutput.= "//Show uploaders nick on torrent listing\n";
                 $foutput.= "\$SHOW_UPLOADER = ".$_POST["show_uploader"].";\n";
                 $foutput.= "\$GLOBALS[\"block_newslimit\"] = \"". $_POST["newslimit"]."\";\n";
                 $foutput.= "\$GLOBALS[\"block_forumlimit\"] = \"". $_POST["forumlimit"]."\";\n";
                 $foutput.= "\$GLOBALS[\"block_last10limit\"] = \"". $_POST["last10limit"]."\";\n";
                 $foutput.= "\$GLOBALS[\"block_mostpoplimit\"] = \"". $_POST["mostpoplimit"]."\";\n";
                 $foutput.= "\$GLOBALS[\"clocktype\"] = " . $_POST["clocktype"] . ";\n";
                 $foutput.= "\$GLOBALS[\"salting\"] = \"" . $_POST["salting"] . "\";\n";
                 $foutput.= "\n?>";
                 fwrite($fd,$foutput) or die(CANT_SAVE_CONFIG);
                 fclose($fd);
                 @chmod("../include/config.php",0744);
                 Print("DATABASE Settings has been saved, please click next!");
                 print("<div align=\"center\"><input type=\"button\" class=\"button\" name=\"continue\" value=\"Next >>\" onclick=\"javascript:document.location.href='index.php?action=step3'\" /></div>");

// CREATE ADMIN ACCOUNT
}elseif ($action == 'save_admin') {
    dbconn ();
    step("Administrator Setup (DONE!)","Admin Setup","4");
    $pwd=((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $_POST["wantpassword"]) : ((trigger_error("Error...", E_USER_ERROR)) ? "" : ""));
    $pwd1=((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $_POST["passwagain"]) : ((trigger_error("Error...", E_USER_ERROR)) ? "" : ""));
    if (!mkglobal("wantusername:wantpassword:passagain:email"))
        die('Error, Please try again!');
    $email = htmlspecialchars(trim($email));
    $email = safe_email($email);
    if (!check_email($email))
        bark("Invalid email address!");
if (empty($wantusername) || empty($wantpassword) || empty($email))
    bark("Don't leave any fields blank.");

if (strlen($wantusername) > 12)
    bark("Sorry, username is too long (max is 12 chars)");

if ($wantpassword != $passagain)
    bark("The passwords didn't match! Must've typoed. Try again.");

if (strlen($wantpassword) < 6)
    bark("Sorry, password is too short (min is 6 chars)");

if (strlen($wantpassword) > 40)
    bark("Sorry, password is too long (max is 40 chars)");

if ($wantpassword == $wantusername)
    bark("Sorry, password cannot be same as user name.");

if (!validemail($email))
    bark("That doesn't look like a valid email address.");

if (!validusername($wantusername))
    bark("Invalid username.");
    $a = (@mysqli_fetch_row(@mysqli_query($GLOBALS["___mysqli_ston"], "select count(*) from users where email='$email'"))) or sqlerr(__FILE__, __LINE__);
    if ($a[0] != 0)
    bark("The e-mail address ".htmlspecialchars($email)." is already in use.");
    $res = mysqli_query($GLOBALS["___mysqli_ston"], "SELECT COUNT(*) FROM users") or sqlerr(__FILE__, __LINE__);
    $arr = mysqli_fetch_row($res);
    $ret = mysqli_query($GLOBALS["___mysqli_ston"], "INSERT INTO users (username, password, random, id_level, email, joined, lastconnect, lip, pid, time_offset) VALUES ('$wantusername', '" . md5($pwd) . "', '437747', '8', '$email', NOW(), NOW(), '1409937172','db5830162cd732c59efba163abc76507', '0')");
    if (!$ret) {
    if (((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_errno($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_errno()) ? $___mysqli_res : false)) == 1062)
        bark("Username already exists!");
    bark("borked");
    }else {
        Print("" . $wantusername . " has been added, please click next!");
        print("<div align=\"center\"><input type=\"button\" class=\"button\" name=\"continue\" value=\"Next >>\" onclick=\"javascript:document.location.href='index.php?action=step5'\" /></div>");
    }

}elseif ($action == 'step5') {
        step("Finish the Installation","Finish","5");

    if ($FH = @fopen( THIS_ROOT_PATH.'install.lock', 'w' ) )
    {
        @fwrite( $FH, 'bleh', 4 );
        @fclose($FH);

        @chmod( THIS_ROOT_PATH.'install.lock', 0666 );
        $msg="<center>Although the installer is now locked (to re-install, remove the file 'install.lock'), for added security, please remove the install folder before continuing.
             <br><br>
             <b><a href='../login.php'>CLICK HERE TO LOGIN!</a></center>";
    }
    else
    {
        $msg = "<center>PLEASE REMOVE THE INSTALLER (install folder & install.me file) BEFORE CONTINUING!<br>Failure to do so will enable ANYONE to delete/change your tracker at any time!
                <br><br>
                <b><a href='../login.php'>CLICK HERE TO LOGIN!</a></center>";
    }
    print ("$msg");
    @chmod("../install.me",0777);
    @unlink("../install.me");

}elseif ($action == 'step0') {
    step("Welcome to the installation wizard for ".TRACKER_VERSION.".","Welcome Screen","0");
?>

            <p>Welcome to the installation wizard for <?php echo TRACKER_VERSION;?> installation <?php echo INSTALL_VERSION;?>. This wizard will install and configure a copy of <?php echo TRACKER_VERSION;?> on your server.</p>
            <p>Now that you've uploaded <?php echo TRACKER_VERSION;?> files the database and settings need to be created and imported. Below is an outline of what is going to be completed during installation.</p>
            <ul>
                <li>Requirements checked,</li>
                <li>Configuration of database engine and tracker settings,</li>
                <li>SQL Import,</li>
            </ul>
            Before we go any further, please ensure that all the files have been uploaded, and that the following files have suitable permissions to allow this script to write to it ( 0777 should be sufficient ).

            <ul>
                <li>/include/config.php,</li>
                <li>/addons/guest.dat,</li>
                <li>/torrents/,</li>
                <li>/badwords.txt,</li>
                <li>/chat.php</li>
            </ul>

<?php echo TRACKER_VERSION;?>  requires PHP 5.3 or better and an MYSQL database.<br><br>

<b>You will also need the following information that your webhost can provide:</b><br>
MYSQL 5.x.x or greater.<br>
PHP version 5.3 or greater.<br>
The Apache webserver version 3 or similar webservers<br>
The ability to change directory permissions to 777 or to change ownership of directories to be owned by the webserver process.<br><br>
<br />
<br />
You can view full changelog <a href="../changelog.txt" target="_blank">here</a>
<br><br>
After each step has successfully been completed, click Next or Continue button to move on to the next step.<br>
Click "<b>Next</b>" to start.
      <br><span class="darkred"><br><div align="center"><input type="button" class="button" name="continue" value="Next >>" onclick="javascript:document.location.href='index.php?action=step1'" /></div></tr></td></div></table>
<?php
}

elseif ($action == 'step1') {
        step("Requirements Check","Req.Check","1");
    include_once ('reqcheck.php');
}

elseif ($action == 'step2') {
$purl=parse_url($_SERVER["PHP_SELF"]);
$BASEURL="http://".$_SERVER["SERVER_NAME"].substr($purl['path'],0,strpos($purl["path"],"/install"));
    step("Tracker Configuration","Tracker setup","2");
print ("<form method='post' action='".$_SERVER["SCRIPT_NAME"]."?action=step3' name='config'><input type='hidden' name='action' value='save_db'>");
?>

<table class="lista" width="100%" align="center">
            <tr><td class="header" align="center" colspan="2">Database Settings</td></tr>
            <tr><td class="header">Host address (usually localhost):</td><td class="lista"><input type="text" name="dbhost" value="<?php echo $dbhost;?>" size="40" /></td></tr>
            <tr><td class="header">Database Name :</td><td class="lista"><input type="text" name="dbname" value="<?php echo $database;?>" size="40" /></td></tr>
            <tr><td class="header">Database User :</td><td class="lista"><input type="text" name="dbuser" value="<?php echo $dbuser;?>" size="40" /></td></tr>
            <tr><td class="header">Database Password :</td><td class="lista"><input type="text" name="dbpwd" value="<?php echo $dbpass;?>" size="40" /></td></tr>
			<tr><td class="header">User Salt :</td><td class="lista"><input type="text" name="salting" value="<?php echo $salting;?>" size="40" /><br /><small><font color="red">IMPORTANT!</font> Add a random text of 32 characters</small></td></tr>
            <tr><td class="header" align="center" colspan="2">Tracker's general settings</td></tr>
            <tr><td class="header">Tracker's Name:</td><td class="lista"><input type="text" name="trackername" value="<?php echo $SITENAME;?>" size="40" /></td></tr>
            <tr><td class="header">Base Tracker's URL (without last /):</td><td class="lista"><input type="text" name="trackerurl" value="<?php echo $BASEURL; ?>" size="40" /></td></tr>
            <tr><td class="header">Tracker's Announce URLS (one url per row):</td><td class="lista"><textarea name="tracker_announceurl" rows="5" cols="40"><?php echo $BASEURL."/announce.php";?></textarea></td></tr>
            <tr><td class="header">Tracker's email:</td><td class="lista"><input type="text" name="trackeremail" value="<?php echo $SITEEMAIL;?>" size="40" /></td></tr>
            <tr><td class="header">Torrent's DIR:</td><td class="lista"><input type="text" name="torrentdir" value="<?php echo $TORRENTSDIR;?>" size="40" /></td></tr>
            <tr><td class="header">Allow External:</td><td class="lista"> true <input type="radio" name="exttorrents" value="true" <?php if ($EXTERNAL_TORRENTS==true) echo "checked" ?> />&nbsp;&nbsp; false <input type="radio" name="exttorrents" value="false" <?php if ($EXTERNAL_TORRENTS==false) echo "checked" ?> /></td></tr>
            <tr><td class="header">Enabled GZIP:</td><td class="lista"> true <input type="radio" name="gzip_enabled" value="true" <?php if ($GZIP_ENABLED==true) echo "checked" ?> />&nbsp;&nbsp; false <input type="radio" name="gzip_enabled" value="false" <?php if ($GZIP_ENABLED==false) echo "checked" ?> /></td></tr>
            <tr><td class="header">Show Debug infos on page's bottom:</td><td class="lista"> true <input type="radio" name="show_debug" value="true" <?php if ($PRINT_DEBUG==true) echo "checked" ?> />&nbsp;&nbsp; false <input type="radio" name="show_debug" value="false" <?php if ($PRINT_DEBUG==false) echo "checked" ?> /></td></tr>
            <tr><td class="header">Disable DHT (private flag in torrent)<br />will be set only on  new uploaded torrents:</td><td class="lista"> true <input type="radio" name="dht" value="true" <?php if ($DHT_PRIVATE==true) echo "checked" ?> />&nbsp;&nbsp; false <input type="radio" name="dht" value="false" <?php if ($DHT_PRIVATE==false) echo "checked" ?> /></td></tr>
            <tr><td class="header">Enable Live Stats (warning to high server load!):</td><td class="lista"> true <input type="radio" name="livestat" value="true" <?php if ($LIVESTATS==true) echo "checked" ?> />&nbsp;&nbsp; false <input type="radio" name="livestat" value="false" <?php if ($LIVESTATS==false) echo "checked" ?> /></td></tr>
            <tr><td class="header">Enable Site Log (log change on torrents/users):</td><td class="lista"> true <input type="radio" name="logactive" value="true" <?php if ($LOG_ACTIVE==true) echo "checked" ?> />&nbsp;&nbsp; false <input type="radio" name="logactive" value="false" <?php if ($LOG_ACTIVE==false) echo "checked" ?> /></td></tr>
            <tr><td class="header">Enable Basic History (torrents/users):</td><td class="lista"> true <input type="radio" name="loghistory" value="true" <?php if ($LOG_HISTORY==true) echo "checked" ?> />&nbsp;&nbsp; false <input type="radio" name="loghistory" value="false" <?php if ($LOG_HISTORY==false) echo "checked" ?> /></td></tr>
            <tr><td class="header">Private Announce:</td><td class="lista"> true <input type="radio" name="p_announce" value="true" <?php if ($PRIVATE_ANNOUNCE==true) echo "checked" ?> />&nbsp;&nbsp; false <input type="radio" name="p_announce" value="false" <?php if ($PRIVATE_ANNOUNCE==false) echo "checked" ?> /></td></tr>
            <tr><td class="header">Private Scrape:</td><td class="lista"> true <input type="radio" name="p_scrape" value="true" <?php if ($PRIVATE_SCRAPE==true) echo "checked" ?> />&nbsp;&nbsp; false <input type="radio" name="p_scrape" value="false" <?php if ($PRIVATE_SCRAPE==false) echo "checked" ?> /></td></tr>
            <tr><td class="header">Show Uploaders nick:</td><td class="lista"> true <input type="radio" name="show_uploader" value="true" <?php if ($SHOW_UPLOADER==true) echo "checked" ?> />&nbsp;&nbsp; false <input type="radio" name="show_uploader" value="false" <?php if ($SHOW_UPLOADER==false) echo "checked" ?> /></td></tr>
            <tr><td class="header">Default Language:</td><td class="lista"><input type="text" name="default_langue" value="1" size="2" readonly />&nbsp;(1 = English)</td></tr>
			<tr><td class="header">Character Encoding:</td><td class="lista">
            <select name="charset">
            <option <?php print($GLOBALS["charset"]=="ISO-8859-1"?"selected":""); ?>>ISO-8859-1
            <option <?php print($GLOBALS["charset"]=="ISO-8859-2"?"selected":""); ?>>ISO-8859-2
            <option <?php print($GLOBALS["charset"]=="ISO-8859-3"?"selected":""); ?>>ISO-8859-3
            <option <?php print($GLOBALS["charset"]=="ISO-8859-4"?"selected":""); ?>>ISO-8859-4
            <option <?php print($GLOBALS["charset"]=="ISO-8859-5"?"selected":""); ?>>ISO-8859-5
            <option <?php print($GLOBALS["charset"]=="ISO-8859-6"?"selected":""); ?>>ISO-8859-6
            <option <?php print($GLOBALS["charset"]=="ISO-8859-6-e"?"selected":""); ?>>ISO-8859-6-e
            <option <?php print($GLOBALS["charset"]=="ISO-8859-6-i"?"selected":""); ?>>ISO-8859-6-i
            <option <?php print($GLOBALS["charset"]=="ISO-8859-7"?"selected":""); ?>>ISO-8859-7
            <option <?php print($GLOBALS["charset"]=="ISO-8859-8"?"selected":""); ?>>ISO-8859-8
            <option <?php print($GLOBALS["charset"]=="ISO-8859-8-e"?"selected":""); ?>>ISO-8859-8-e
            <option <?php print($GLOBALS["charset"]=="ISO-8859-8-i"?"selected":""); ?>>ISO-8859-8-i
            <option <?php print($GLOBALS["charset"]=="ISO-8859-9"?"selected":""); ?>>ISO-8859-9
            <option <?php print($GLOBALS["charset"]=="ISO-8859-10"?"selected":""); ?>>ISO-8859-10
            <option <?php print($GLOBALS["charset"]=="ISO-8859-13"?"selected":""); ?>>ISO-8859-13
            <option <?php print($GLOBALS["charset"]=="ISO-8859-14"?"selected":""); ?>>ISO-8859-14
            <option <?php print($GLOBALS["charset"]=="ISO-8859-15"?"selected":""); ?>>ISO-8859-15
            <option <?php print($GLOBALS["charset"]=="UTF-8"?"selected":""); ?>>UTF-8
            <option <?php print($GLOBALS["charset"]=="ISO-2022-JP"?"selected":""); ?>>ISO-2022-JP
            <option <?php print($GLOBALS["charset"]=="EUC-JP"?"selected":""); ?>>EUC-JP
            <option <?php print($GLOBALS["charset"]=="Shift_JIS"?"selected":""); ?>>Shift_JIS
            <option <?php print($GLOBALS["charset"]=="GB2312"?"selected":""); ?>>GB2312
            <option <?php print($GLOBALS["charset"]=="Big5"?"selected":""); ?>>Big5
            <option <?php print($GLOBALS["charset"]=="EUC-KR"?"selected":""); ?>>EUC-KR
            <option <?php print($GLOBALS["charset"]=="windows-1250"?"selected":""); ?>>windows-1250
            <option <?php print($GLOBALS["charset"]=="windows-1251"?"selected":""); ?>>windows-1251
            <option <?php print($GLOBALS["charset"]=="windows-1252"?"selected":""); ?>>windows-1252
            <option <?php print($GLOBALS["charset"]=="windows-1253"?"selected":""); ?>>windows-1253
            <option <?php print($GLOBALS["charset"]=="windows-1254"?"selected":""); ?>>windows-1254
            <option <?php print($GLOBALS["charset"]=="windows-1255"?"selected":""); ?>>windows-1255
            <option <?php print($GLOBALS["charset"]=="windows-1256"?"selected":""); ?>>windows-1256
            <option <?php print($GLOBALS["charset"]=="windows-1257"?"selected":""); ?>>windows-1257
            <option <?php print($GLOBALS["charset"]=="windows-1258"?"selected":""); ?>>windows-1258
            <option <?php print($GLOBALS["charset"]=="KOI8-R"?"selected":""); ?>>KOI8-R
            <option <?php print($GLOBALS["charset"]=="KOI8-U"?"selected":""); ?>>KOI8-U
            <option <?php print($GLOBALS["charset"]=="cp866"?"selected":""); ?>>cp866
            <option <?php print($GLOBALS["charset"]=="cp874"?"selected":""); ?>>cp874
            <option <?php print($GLOBALS["charset"]=="TIS-620"?"selected":""); ?>>TIS-620
            <option <?php print($GLOBALS["charset"]=="VISCII"?"selected":""); ?>>VISCII
            <option <?php print($GLOBALS["charset"]=="VPS"?"selected":""); ?>>VPS
            <option <?php print($GLOBALS["charset"]=="TCVN-5712"?"selected":""); ?>>TCVN-5712
            </select>
            <tr><td class="header">Default Style:</td><td class="lista"><input type="text" name="default_style" value="1" size="2" />&nbsp;(1=base, 2=green, 3=dark, 4=killbill)</td></tr>
            <tr><td class="header">Max Users (numeric, 0 = no limits):</td><td class="lista"><input type="text" name="maxusers" value="<?php echo 0+$MAX_USERS;?>" size="40" /></td></tr>
            <tr><td class="header">Torrents per page:</td><td class="lista"><input type="text" name="ntorrents" value="<?php echo (0+$ntorrents==0?"15":$ntorrents);?>" size="40" /></td></tr>
            <tr><td class="header" align="center" colspan="2">Tracker's specific settings</td></tr>
            <tr><td class="header">Sanity interval (numeric seconds, 0 = disabled)<br />Good value, if enabled, is 1800 (30 minutes):</td><td class="lista"><input type="text" name="sinterval" value="<?php echo 0+$clean_interval;?>" size="40" /></td></tr>
            <tr><td class="header">Update External interval (numeric seconds, 0 = disabled)<br />Depending of how many external torrents:</td><td class="lista"><input type="text" name="uinterval" value="<?php echo 0+$update_interval;?>" size="40" /></td></tr>
            <tr><td class="header">Maximum reannounce interval (numeric seconds):</td><td class="lista"><input type="text" name="rinterval" value="<?php echo $GLOBALS["report_interval"];?>" size="40" /></td></tr>
            <tr><td class="header">Minimum reannounce interval (numeric seconds):</td><td class="lista"><input type="text" name="mininterval" value="<?php echo $GLOBALS["min_interval"];?>" size="40" /></td></tr>
            <tr><td class="header">Max N. of peers for request (numeric):</td><td class="lista"><input type="text" name="maxpeers" value="<?php echo $GLOBALS["maxpeers"];?>" size="40" /></td></tr>
            <tr><td class="header">Dynamic Torrents (not recommended):</td><td class="lista"> true <input type="radio" name="dynamic" value="true" <?php if ($GLOBALS["dynamic_torrents"]==true) echo "checked" ?> />&nbsp;&nbsp; false <input type="radio" name="dynamic" value="false" <?php if ($GLOBALS["dynamic_torrents"]==false) echo "checked" ?> /></td></tr>
            <tr><td class="header">NAT checking :</td><td class="lista"> true <input type="radio" name="nat" value="true" <?php if ($GLOBALS["NAT"]==true) echo "checked" ?> />&nbsp;&nbsp; false <input type="radio" name="nat" value="false" <?php if ($GLOBALS["NAT"]==false) echo "checked" ?> /></td></tr>
            <tr><td class="header">Persistent connections (Database, not recommended):</td><td class="lista"> true <input type="radio" name="persist" value="true" <?php if ($GLOBALS["persist"]==true) echo "checked" ?> />&nbsp;&nbsp; false <input type="radio" name="persist" value="false" <?php if ($GLOBALS["persist"]==false) echo "checked" ?> /></td></tr>
            <tr><td class="header">Allow users to override ip :</td><td class="lista"> true <input type="radio" name="override" value="true" <?php if ($GLOBALS["ip_override"]==true) echo "checked" ?> />&nbsp;&nbsp; false <input type="radio" name="override" value="false" <?php if ($GLOBALS["ip_override"]==false) echo "checked" ?> /></td></tr>
            <tr><td class="header">Calculate Speed and Dow.ded bytes :</td><td class="lista"> true <input type="radio" name="countbyte" value="true" <?php if ($GLOBALS["countbytes"]==true) echo "checked" ?> />&nbsp;&nbsp; false <input type="radio" name="countbyte" value="false" <?php if ($GLOBALS["countbytes"]==false) echo "checked" ?> /></td></tr>
            <tr><td class="header">Table caches :</td><td class="lista"> true <input type="radio" name="caching" value="true" <?php if ($GLOBALS["peercaching"]==true) echo "checked" ?> />&nbsp;&nbsp; false <input type="radio" name="caching" value="false" <?php if ($GLOBALS["peercaching"]==false) echo "checked" ?> /></td></tr>
            <tr><td class="header">Max num. of seeds with same PID :</td><td class="lista"><input type="text" name="maxseeds" value="<?php echo $GLOBALS["maxseeds"];?>" size="40" /></td></tr>
            <tr><td class="header">Max num. of leechers with same PID :</td><td class="lista"><input type="text" name="maxleech" value="<?php echo $GLOBALS["maxleech"];?>" size="40" /></td></tr>
            <tr><td class="header">Validation Mode:</td><td class="lista">
            <select name="validation" size="1">
            <option value="none"<?php if($VALIDATION=="none") echo " selected"?>>none</option>
            <option value="user"<?php if($VALIDATION=="user") echo " selected"?>>user</option>
            <option value="admin"<?php if($VALIDATION=="admin") echo " selected"?>>admin</option>
            </select></td></tr>
            <tr><td class="header">Secure Registration (use ImageCode, GD+Freetype libraries needed):</td><td class="lista"> true <input type="radio" name="imagecode" value="true" <?php if ($USE_IMAGECODE==true) echo "checked" ?> />&nbsp;&nbsp; false <input type="radio" name="imagecode" value="false" <?php if ($USE_IMAGECODE==false) echo "checked" ?> /></td></tr>
            <tr><td class="header">Forum link (can be: forum link or internal/empty or none):</td><td class="lista"><input type="text" name="f_link" value="<?php echo $GLOBALS["FORUMLINK"];?>" size="40" /></td></tr>
            <tr><td class="header" align="center" colspan="2">Index/Blocks page settings</td></tr>
            <tr><td class="header">Clock type:</td><td class="lista">&nbsp;&nbsp;Analog&nbsp;<input type="radio" name="clocktype" value="true" <?php if ($GLOBALS["clocktype"]==true) echo "checked" ?> />&nbsp;&nbsp;Digital&nbsp;<input type="radio" name="clocktype" value="false" <?php if ($GLOBALS["clocktype"]==false) echo "checked" ?> /></td></tr>
            <tr><td class="header">Limit for Latest News block:</td><td class="lista"><input type="text" name="newslimit" value="<?php echo $GLOBALS["block_newslimit"];?>" size="3" maxlength="3" /></td></tr>
            <tr><td class="header">Limit for Forum block:</td><td class="lista"><input type="text" name="forumlimit" value="<?php echo $GLOBALS["block_forumlimit"];?>" size="3" maxlength="3" /></td></tr>
            <tr><td class="header">Limit for Latest Torrents block:</td><td class="lista"><input type="text" name="last10limit" value="<?php echo $GLOBALS["block_last10limit"];?>" size="3" maxlength="3" /></td></tr>
            <tr><td class="header">Limit for Most Popular Torrents block:</td><td class="lista"><input type="text" name="mostpoplimit" value="<?php echo $GLOBALS["block_mostpoplimit"];?>" size="3" maxlength="3" /></td></tr>

<?php

tr("Save configuration (Press next when ready)","<input type='submit' name='save' value='Next >>'>\n", 1);
print ("</form>");

}elseif ($action == 'step3') {
    step("SQL Dump. Powered by BigDump","Sql","3");
    include_once ('bigdump.php');

}elseif ($action == 'step4') {
    step("Administrator Setup","Admin Setup","4");
    dbconn(true);
    print ("<form method='post' action='".$_SERVER["SCRIPT_NAME"]."'><input type='hidden' name='action' value='save_admin'>");
    ?>
    <tr><td class="header">Desired username:</td><td align="left" class="lista"><input type="text" size="40" name="wantusername" /><br><font class="small">Allowed Characters: (a-z), (A-Z), (0-9)</font></td></tr>
    <tr><td class="header">Pick a password:</td><td align="left" class="lista"><input type="password" size="40" name="wantpassword" /></td></tr>
    <tr><td class="header">Enter password again:</td><td align="left" class="lista"><input type="password" size="40" name="passagain" /></td></tr>
    <tr><td class="header">Email address:</td><td align="left" class="lista"><input type="text" size="40" name="email" />
    <tr><td colspan="2" align="center" class="lista"><font color="red"><b>All Fields are required!</b><p></font><input type="submit" value="Sign up! (PRESS ONLY ONCE)" style='height: 25px'></td></tr></form>
<?php
}

print("</table></body></html>");
ob_end_flush();
?>