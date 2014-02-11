#-----------------------------#
# Welcome to the BtitTracker. #
#-----------------------------#


Just a few words about the installation, the settings and some credits ;)


1. INSTALLATION
2. SETTINGS
3. CREDITS


#-----------------#
# 1. INSTALLATION #
#-----------------#

Unpack the archive (if you're reading this document, you've probably already done it) :)

USING INSTALLER:
----------------
Upload all the files (except config.php if upgrading) into your ftp account, 

change the properties/CHMOD 777 
- "torrents" folder, 
- chat.php, 
- addons/guest.dat, 
- include/config.php,
- badwords.txt 
so it has full read/write capabilities.

Open your browser and point to your site address, you'll find the installation wizard.


MANUAL INSTALLATION OR UPGRADE:
-------------------------------

Locate the "include" folder and edit config.php to set the basic
information for your mysql account. The others can be edited
with the Admin Panel.

The dbhost is the address for accessing your mysql server (90% of the time it works if
left as localhost).

$dbhost = "localhost";
dbuser is the username you use to access your mysql server.
$dbuser = "yourdbusername";
dbpass is your password you use to access your mysql server.
$dbpass = "yourdbpassword";
database is the database name.
$database = "databasename";

Other settings can be changed in admincp and are explained below.

Now, open your mysql manager (something like phpmyadmin), select your database, 
select the "SQL" tab and:
- if you're doing a fresh installation then process sql/database.sql
- if you're doing an upgrade from alpha 1 then process upgrade/alpha1_to_2.sql and upgrade/alpha2_to3.sql
- if you're doing an upgrade from alpha 2 then process upgrade/alpha2_to_3.sql
- if you're doing an upgrade from alpha 3 then process upgrade/alpha3_to_beta1.sql
- if you're doing an upgrade from beta1b then process upgrade/beta1b_to_beta1c.sql
- if you're doing an upgrade from beta1c then process upgrade/beta1c_to_v1.sql
- if you're doing an upgrade from 1.0 or 1.1 or 1.2 then process upgrade/v12_to_v13.sql
- if you're doing an upgrade from 1.3x then process upgrade/v13x_to_v14.sql
- if you're doing an upgrade from 1.4 then process upgrade/v14_to_v141.sql



N.B. If you're upgrading from previous versions: go to admin panel, and set new options.
같같같같같같같같같같같같같같같같같같같같같같같�


Upload all the files (except config.php if upgrading) into your ftp account, 
change the properties/CHMOD 777 
- "torrents" folder, 
- chat.php, 
- addons/guest.dat, 
- include/config.php,
- badwords.txt 

so it has full read/write capabilities.


And enjoy!





#-----------------------------------#
# 2. SETTINGS (with default values) #
#-----------------------------------#

The $GLOBALS[*] settings are from the original tracker by DeHackEd.

$GLOBALS["report_interval"] = 1800;
this is the maximum interval (in seconds) the tracker can send to each client 
before he needs (the client) to announce himself.

$GLOBALS["min_interval"] = 300;
same as above, but it is the minimum interval before a client can resend announcement.

$GLOBALS["maxpeers"] = 50;
the maximum number of peers the tracker can send in one time to a client.

$GLOBALS["dynamic_torrents"] = false;
If set to true, then the tracker will accept any and all
torrents sent to it. This is not recommended, but its there if you need.
NOTE: if set to "true" there is no need to upload the torrents! 
YOU HAVE BEEN WARNED ABOUT THIS SETTING

$GLOBALS["NAT"] = false;
If set to true, NAT checking will be performed.
This may cause trouble with some providers, so it's
off by default.

$GLOBALS["persist"] = false;
Persistent connections: true or false.
Check with your webmaster to see if you're allowed to use these.
Highly recommended, especially for higher loads.

$GLOBALS["ip_override"] = false;
Allow users to override ip= ?
Enable this if you know people have a legit reason to use
this function. Leave disabled otherwise.

$GLOBALS["countbytes"] = true;
For heavily loaded trackers, set this to false. It will stop the tracker counting
the number of downloaded bytes and the speed of the torrents, but will significantly reduce
the load.

$GLOBALS["peercaching"] = false;
Table caches!
Lowers the load on all systems, but takes up more disk space.
You win some, you lose some. But since the load is the big problem,
grab this.

Warning! Enable this BEFORE making torrents, or else run makecache.php
immediately, or else you'll be in deep trouble. The tables will lose
sync and the database will be in a somewhat "stale" state.

$dbhost = "localhost";
dbuser is the username you use to access your mysql server.
$dbuser = "yourdbusername";
dbpass is your password you use to access your mysql server.
$dbpass = "yourdbpassword";
database is the database name.
$database = "databasename";

$SITENAME="Btit Test";
Tracker's name, this will appear in the title bar.

$BASEURL="http://itbt.altervista.org";
Tracker's Base URL, this is the main url FOR THE TRACKER, if you host 
a site and the tracker isn't in main directory, put the tracker's dir.
WITHOUT THE FINAL BACKSLASH!

$SITEEMAIL="localhost@localhost";
Tracker's email (owner email), this email will be used as sender email
for validating.

$TORRENTSDIR="torrents";
Torrent's DIR, where the *.torrent will be stored.
WITHOUT FINAL BACKSLASH!
Must be chmod to 777

$VALIDATION="user";
validation type (must be none, user or admin)
none=validate immediatly, 
user=validate by email, 
admin=manually validate

$clean_interval="1800";
interval for sanity check (good = 30 minutes)

$update_interval="1800";
interval for updating external torrents (depending of how many external torrents)

$FORUMLINK="";
forum link or internal (empty = internal) or none
Note that internal forum is very basic, but if you don't
have special needs, it's good. If you choose forum link (url)
it will be opened in new windows.

Some words about the forum settings: forums_views, forums_edit, forums_delete 
(in users_level table) are globals settings, edit and delete are for moderations
purpose only, each forum have it's own level view/post/reply settings.

$EXTERNAL_TORRENTS=true;
If you want to allow users to upload external torrents values true/false

$DEFAULT_LANGUAGE=1;
Default language (used for guest and set to user that use canceled language)

$DEFAULT_STYLE=1;
Default style  (used for guest and set to user that use canceled language)

$MAX_USERS=500;
Maximum number of users (0 = no limits)

$ntorrents ="15";
torrents per page, default = 15

$PRIVATE_ANNOUNCE =true;
private announce (true/false), if set to true don't allow non register user to download

$PRIVATE_SCRAPE =false;
private scrape (true/false), if set to true don't allow non register user to scrape (for stats)
at the moment not used 

$GLOBALS["clocktype"] = true;
// If true, the clock type will be Analog
// If false, the clock type will be Digital
$GLOBALS["onlineblock"] = true;
various aspect block configuration (default is all true, view all)


#------------#
# 3. CREDITS #
#------------#

This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
We aim to make a nice user interface and a good admin tool at the same time.
Some code and some ideas came from other trackers:
- torrentbits (http://www.torrentbits.org - dead)
- torrenttrader (http://www.torrentrader.org)
- bytemoonsoon (deadlink)
- DHKold for original ShoutBox code.
- Tbdev: CoLdFuSiOn (http://www.tbdev.net)

the rest has been coded, designed and thought up from scratch.

Also some help from Static_Rage writing the english translation for the
tracker and this readme file. (www.voidnightmare.com)

Thanks to coder addons/hacks (many are included in this version): 
Ripper, cobracrk, JBoy, Liroy, Petr1fied, miskotes, gAndo, Fireworx, Freelancer, Sktoch, Nimrod, etc... 
(sorry if someone is missed :))

Thanks to style maker: 
bmfan, pipphot78 (alias ch3), Skotch, Fireworx, etc... (sorry again if someone is missed :))

Many thanks to all guys how partecipate for the testing and for addons/styles etc.

This code is completly free of charge, as the future hack, as help, 
as all you need for put and run this tracker (no supporter club or 
other work around for paying for free scripts).
You can change it, but please give credit to us.

If you have questions, doubt or other, visit our support forum:
http://www.btiteam.org

Btiteam.


