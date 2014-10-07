<?php
/* Tracker Configuration
 *
 *  This file provides configuration informatino for
 *  the tracker. The user-editable variables are at the top. It is
 *  recommended that you do not change the database settings
 *  unless you know what you are doing.
 */

//Maximum reannounce interval.
$GLOBALS['report_interval'] = 1800;
//Minimum reannounce interval. Optional.
$GLOBALS['min_interval'] = 300;
//Number of peers to send in one request.
$GLOBALS['maxpeers'] = 50;
//If set to true, then the tracker will accept any and all
//torrents given to it. Not recommended, but available if you need it.
$GLOBALS['dynamic_torrents'] = false;
// If set to true, NAT checking will be performed.
// This may cause trouble with some providers, so it's
// off by default.
$GLOBALS['NAT'] = false;
// Persistent connections: true or false.
// Check with your webmaster to see if you're allowed to use these.
// not recommended, only if you get very higher loads, but use at you own risk.
$GLOBALS['persist'] = false;
// Allow users to override ip= ?
// Enable this if you know people have a legit reason to use
// this function. Leave disabled otherwise.
$GLOBALS['ip_override'] = false;
// For heavily loaded trackers, set this to false. It will stop count the number
// of downloaded bytes and the speed of the torrent, but will significantly reduce
// the load.
$GLOBALS['countbytes'] = true;
// Table caches!
// Lowers the load on all systems, but takes up more disk space.
// You win some, you lose some. But since the load is the big problem,
// grab this.
//
// Warning! Enable this BEFORE making torrents, or else run makecache.php
// immediately, or else you'll be in deep trouble. The tables will lose
// sync and the database will be in a somewhat 'stale' state.
$GLOBALS['peercaching'] = true;
//Max num. of seeders with same PID.
$GLOBALS['maxseeds'] = 5;
//Max num. of leechers with same PID.
$GLOBALS['maxleech'] = 5;

/////////// End of User Configuration ///////////
$dbhost = 'localhost';
$dbuser = 'root';
$dbpass = '';
$database = 'BtiTracker';
//Tracker's name
$SITENAME='BtitTracker Test';
//Tracker's Base URL
$BASEURL='http://localhost';
// tracker's announce urls, can be more than one
$TRACKER_ANNOUNCEURLS=array();
$TRACKER_ANNOUNCEURLS[]='http://localhost/announce.php';
//Tracker's email (owner email)
$SITEEMAIL='your@email.com';
//Torrent's DIR
$TORRENTSDIR='torrents';
//validation type (must be none, user or admin
//none=validate immediatly, user=validate by email, admin=manually validate
$VALIDATION='none';
//Use or not the image code for new users' registration
$USE_IMAGECODE=true;
// interval for sanity check (good = 10 minutes)
$clean_interval='1800';
// interval for updating external torrents (depending of how many external torrents)
$update_interval='100';
// forum link or internal (empty = internal) or none
$FORUMLINK='';
// If you want to allow users to upload external torrents values true/false
$EXTERNAL_TORRENTS=false;
// Enable/disable GZIP compression, can save a lot of bandwidth
$GZIP_ENABLED=true;
// Show/Hide bottom page information on script's generation time and gzip
$PRINT_DEBUG=true;
// Enable/disable DHT network, add private flag to 'info' in torrent
$DHT_PRIVATE=true;
// Enable/disable Live Stats (up/down updated every announce) WARNING CAN DO HIGH SERVER LOAD!
$LIVESTATS=true;
// Enable/disable Site log
$LOG_ACTIVE=true;
//Enable Basic History (torrents/users)
$LOG_HISTORY=true;
// Default language (used for guest)
$DEFAULT_LANGUAGE=1;
// Default charset (used for guest)
$GLOBALS['charset']='UTF-8';
// Default style  (used for guest)
$DEFAULT_STYLE=1;
// Maximum number of users (0 = no limits)
$MAX_USERS=500;
//torrents per page
$ntorrents ='1';
//private announce (true/false), if set to true don't allow non register user to download
$PRIVATE_ANNOUNCE =true;
//private scrape (true/false), if set to true don't allow non register user to scrape (for stats)
$PRIVATE_SCRAPE =true;
//Show uploaders nick on torrent listing
$SHOW_UPLOADER = true;
$GLOBALS['block_newslimit'] = '2';
$GLOBALS['block_forumlimit'] = '3';
$GLOBALS['block_last10limit'] = '5';
$GLOBALS['block_mostpoplimit'] = '5';
$GLOBALS['clocktype'] = true;

?>
