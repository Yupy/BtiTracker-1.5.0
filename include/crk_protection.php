<?php
/////////////////////////////////////////////////////////////////////////////////////
// xbtit - Bittorrent tracker/frontend
//
// Copyright (C) 2004 - 2007  Btiteam
//
//    This file is part of xbtit.
//
// Redistribution and use in source and binary forms, with or without modification,
// are permitted provided that the following conditions are met:
//
//   1. Redistributions of source code must retain the above copyright notice,
//      this list of conditions and the following disclaimer.
//   2. Redistributions in binary form must reproduce the above copyright notice,
//      this list of conditions and the following disclaimer in the documentation
//      and/or other materials provided with the distribution.
//   3. The name of the author may not be used to endorse or promote products
//      derived from this software without specific prior written permission.
//
// THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS OR IMPLIED
// WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
// MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
// IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
// SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED
// TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
// PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF
// LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
// NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE,
// EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
//
////////////////////////////////////////////////////////////////////////////////////

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
function crk($l) {

  global $CURUSER,$btit_settings;

  $xip=$_SERVER["REMOTE_ADDRESS"];
  if (function_exists("dbconn"))
     dbconn();
  if (function_exists("write_log"))
     write_log('Hacking Attempt! User: <a href="'.$btit_settings['url'].'/index.php?page=userdetails&amp;id='.$CURUSER['uid'].'">'.$CURUSER['username'].'</a> IP:'.$xip.' - Attempt: '.htmlspecialchars($l));

  header('Location: index.php');
  die();
}

//the bad words...
$ban['union']='select';
//$ban['update']='set';
$ban['set password for']='@';

$ban2=array('delete from','insert into','<script', '<object', '.write', '.location', '.cookie', '.open', 'vbscript:', '<iframe', '<layer', '<style', ':expression', '<base', 'id_level', 'users_level', 'xbt_', 'c99.txt', 'c99shell', 'r57.txt', 'r57shell.txt','/home/', '/var/', '/www/', '/etc/', '/bin', '/sbin/', '$_GET', '$_POST', '$_REQUEST', 'window.open', 'javascript:', 'xp_cmdshell',  '.htpasswd', '.htaccess', '<?php', '<?', '?>', '</script>');

//checking the bad words
$cepl=$_SERVER['QUERY_STRING'];
if (!empty($cepl)) {
  $cepl=preg_replace('/([\x00-\x08][\x0b-\x0c][\x0e-\x20])/', '', $cepl); 
  $cepl=urldecode($cepl);
  $cepl=strtolower($cepl);
}
foreach ($ban as $k => $l)
  if (str_replace($k, '',$cepl)!=$cepl&&str_replace($l, '',$cepl)!=$cepl)
      crk(($cepl));
if (str_replace($ban2,'',$cepl)!=$cepl)
  crk(($cepl));

$cepl=implode(' ', $_REQUEST);
if (!empty($cepl)) {
  $cepl=preg_replace('/([\x00-\x08][\x0b-\x0c][\x0e-\x20])/', '', $cepl);
  $cepl=urldecode($cepl);
  $cepl=strtolower($cepl);
}
foreach ($ban as $k => $l)
  if(str_replace($k, '',$cepl)!=$cepl&&str_replace($l, '',$cepl)!=$cepl)
    crk(($cepl));
if (str_replace($ban2,'',$cepl)!=$cepl)
  crk(($cepl));

$cepl=implode(' ', $_COOKIE);
if (!empty($cepl)) {
  $cepl=preg_replace('/([\x00-\x08][\x0b-\x0c][\x0e-\x20])/', '', $cepl); 
  $cepl=urldecode($cepl);
  $cepl=strtolower($cepl);
}
foreach ($ban as $k => $l)
  if(str_replace($k, '',$cepl)!=$cepl&&str_replace($l, '',$cepl)!=$cepl)
   crk(($cepl));
if (str_replace($ban2,'',$cepl)!=$cepl)
  crk(($cepl));
?>