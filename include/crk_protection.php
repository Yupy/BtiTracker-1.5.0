<?php
/*
 * BtiTracker v1.5.0 is a php tracker system for BitTorrent, easy to setup and configure.
 * This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
 * Updated and Maintained by Yupy.
 * Copyright (C) 2004-2014 Btiteam.org
 */

/*
########################################################
#   CRK-Protection v2.0                                #
#   Anti-Hacking Module by CobraCRK                    #
#   This is made by CobraCRK - cobracrk[at]yahoo.com   #
#   This shall not used without my approval!           #
#   You may not share this!!!                          #
#   DO NOT REMOVE THIS COPYRIGHT!                      #
########################################################
#         This version was made for BtitTracker        #
########################################################
*/
require_once('classes/class.Vars.php');
require_once('classes/class.User.php');

function crk($l)
{
    global $BASEURL;
    
    $xip = vars::$realip;
    
    if (function_exists("dbconn"))
        dbconn();
    
    if (function_exists("write_log"))
        write_log('Hacking Attempt! User: <a href="' . $BASEURL . '/userdetails.php?id=' . user::$current['uid'] . '">' . user::$current['username'] . '</a> IP:' . $xip . ' - Attempt: ' . htmlspecialchars($l) . ', INFO');
    
    header('Location: index.php');
    die();
}

//the bad words...
$ban['union']            = 'select';
//$ban['update']='set';
$ban['set password for'] = '@';

$ban2 = array(
    'delete from',
    'insert into',
    '<script',
    '<object',
    '.write',
    '.location',
    '.cookie',
    '.open',
    'vbscript:',
    '<iframe',
    '<layer',
    '<style',
    ':expression',
    '<base',
    'id_level',
    'users_level',
    'xbt_',
    'c99.txt',
    'c99shell',
    'r57.txt',
    'r57shell.txt',
    '/home/',
    '/var/',
    '/www/',
    '/etc/',
    '/bin',
    '/sbin/',
    '$_GET',
    '$_POST',
    '$_REQUEST',
    'window.open',
    'javascript:',
    'xp_cmdshell',
    '.htpasswd',
    '.htaccess',
    '<?php',
    '<?',
    '?>',
    '</script>'
);

//checking the bad words
$cepl = $_SERVER['QUERY_STRING'];
if (!empty($cepl)) {
    $cepl = preg_replace('/([\x00-\x08][\x0b-\x0c][\x0e-\x20])/', '', $cepl);
    $cepl = urldecode($cepl);
    $cepl = strtolower($cepl);
}
foreach ($ban as $k => $l)
    if (str_replace($k, '', $cepl) != $cepl && str_replace($l, '', $cepl) != $cepl)
        crk(($cepl));
if (str_replace($ban2, '', $cepl) != $cepl)
    crk(($cepl));

$cepl = implode(' ', $_REQUEST);
if (!empty($cepl)) {
    $cepl = preg_replace('/([\x00-\x08][\x0b-\x0c][\x0e-\x20])/', '', $cepl);
    $cepl = urldecode($cepl);
    $cepl = strtolower($cepl);
}
foreach ($ban as $k => $l)
    if (str_replace($k, '', $cepl) != $cepl && str_replace($l, '', $cepl) != $cepl)
        crk(($cepl));
if (str_replace($ban2, '', $cepl) != $cepl)
    crk(($cepl));

$cepl = implode(' ', $_COOKIE);
if (!empty($cepl)) {
    $cepl = preg_replace('/([\x00-\x08][\x0b-\x0c][\x0e-\x20])/', '', $cepl);
    $cepl = urldecode($cepl);
    $cepl = strtolower($cepl);
}
foreach ($ban as $k => $l)
    if (str_replace($k, '', $cepl) != $cepl && str_replace($l, '', $cepl) != $cepl)
        crk(($cepl));
if (str_replace($ban2, '', $cepl) != $cepl)
    crk(($cepl));

?>