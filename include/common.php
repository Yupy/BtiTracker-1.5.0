<?php

if (!function_exists("bcsub"))
    {
    function bcsub($first_num, $second_num)
    {
        $fn=max(0,$first_num);
        $sn=max(0,$second_num);
        return $fn-$sn;
    }

}

// Reports an error to the client in $message.
// Any other output will confuse the client, so please don't do that.
function show_error($message, $log=false)
{
  if ($log)
      error_log("BtiTracker: ERROR ($message)");

  echo "d14:failure reason".strlen($message).":$message"."e";
  exit(0);
}


function verifyHash($input)
{
    if (strlen($input) === 40 && preg_match('/^[0-9a-f]+$/', $input))
        return true;
    else
        return false;
}

/**** validip/getip courtesy of manolete <manolete@myway.com> ****/

// IP Validation
function validip($ip)
{
    return filter_var($ip, FILTER_VALIDATE_IP, array(
        'flags' => FILTER_FLAG_NO_PRIV_RANGE,
        FILTER_FLAG_NO_RES_RANGE
    )) ? true : false;
}

// Patched function to detect REAL IP address if it's valid
function getip() {
   if (isset($_SERVER["HTTP_CLIENT_IP"])) {
    if (validip($_SERVER["HTTP_CLIENT_IP"])) {
       return $_SERVER["HTTP_CLIENT_IP"];
     }
   }
   if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
     foreach (explode(",",$_SERVER["HTTP_X_FORWARDED_FOR"]) as $ip) {
       if (validip(trim($ip))) {
           return $ip;
       }
     }
   }
   if (validip(isset($_SERVER["HTTP_X_FORWARDED"])?$_SERVER["HTTP_X_FORWARDED"]:'127.0.0.1')) {
       return $_SERVER["HTTP_X_FORWARDED"];
   } elseif (validip(isset($_SERVER["HTTP_FORWARDED_FOR"])?$_SERVER["HTTP_FORWARDED_FOR"]:'127.0.0.1')) {
       return $_SERVER["HTTP_FORWARDED_FOR"];
   } elseif (validip(isset($_SERVER["HTTP_FORWARDED"])?$_SERVER["HTTP_FORWARDED"]:'127.0.0.1')) {
       return $_SERVER["HTTP_FORWARDED"];
   } elseif (validip(isset($_SERVER["HTTP_X_FORWARDED"])?$_SERVER["HTTP_X_FORWARDED"]:'127.0.0.1')) {
       return $_SERVER["HTTP_X_FORWARDED"];
   } else {
       return $_SERVER["REMOTE_ADDR"];
   }
}

if (!function_exists("hex2bin")) { 
function hex2bin ($input, $assume_safe=true)
{
    if ($assume_safe !== true && ! ((strlen($input) % 2) === 0 || preg_match ('/^[0-9a-f]+$/i', $input)))
        return "";
    return pack('H*', $input );
}
} 

// Runs a query with no regard for the result
function quickQuery($query)
{
    $results = @run_query($query);
    if (!is_bool($results))
        ((mysqli_free_result($results) || (is_object($results) && (get_class($results) == "mysqli_result"))) ? true : false);
    else
        return $results;
    return true;
}

function run_query($query)
{
    global $query_stat;
    $query_start_time = microtime(true); // Start time
    $result = mysqli_query($GLOBALS["___mysqli_ston"], $query);
    $query_end_time = microtime(true); // End time
    $query_stat[] = array(
        'seconds' => number_format($query_end_time - $query_start_time, 6) ,
        'query' => $query
    );
    return $result;
}

function mysqli_result($res, $row, $field = 0)
{ 
    $res->data_seek($row); 
    $datarow = $res->fetch_array(); 
    return $datarow[$field]; 
}

#========================================
#getAgent function by deliopoulos
#========================================
function StdDecodePeerId($id_data, $id_name){
  $version_str = "";
  for ($i=0; $i<=strlen($id_data); $i++){
    $c = $id_data[$i];
    if ($id_name=="BitTornado" || $id_name=="ABC") {
      if ($c!='-' && ctype_digit($c)) $version_str .= "$c.";
      elseif ($c!='-' && ctype_alpha($c)) $version_str .= (ord($c)-55).".";
      else break;
    }
    elseif($id_name=="BitComet"||$id_name=="BitBuddy"||$id_name=="Lphant"||$id_name=="BitPump"||$id_name=="BitTorrent Plus! v2") {
      if ($c != '-' && ctype_alnum($c)){
        $version_str .= "$c";
        if($i==0) $version_str = intval($version_str) .".";
      }
      else{
        $version_str .= ".";
        break;
      }
    }
    else {
      if ($c != '-' && ctype_alnum($c)) $version_str .= "$c.";
      else break;
    }
  }
  $version_str = substr($version_str,0,strlen($version_str)-1);
  return "$id_name $version_str";
}
function MainlineDecodePeerId($id_data, $id_name){
  $version_str = "";
  for ($i=0; $i<=strlen($id_data); $i++){
    $c = $id_data[$i];
    if ($c != '-' && ctype_alnum($c)) $version_str .= "$c.";
  }
  $version_str = substr($version_str,0,strlen($version_str)-1);
    return "$id_name $version_str";
}
function DecodeVersionString ($ver_data, $id_name){
    $version_str = "";
    $version_str .= intval(ord($ver_data[0]) + 0).".";
    $version_str .= intval(ord($ver_data[1])/10 + 0);
    $version_str .= intval(ord($ver_data[1])%10 + 0);
    return "$id_name $version_str";
}
function getagent($httpagent, $peer_id="") {
  if($peer_id!="") $peer_id=hex2bin($peer_id);
  if(substr($peer_id,0,3)=='-AX') return StdDecodePeerId(substr($peer_id,4,4),"BitPump"); # AnalogX BitPump
  if(substr($peer_id,0,3)=='-BB') return StdDecodePeerId(substr($peer_id,3,5),"BitBuddy"); # BitBuddy
  if(substr($peer_id,0,3)=='-BC') return StdDecodePeerId(substr($peer_id,4,4),"BitComet"); # BitComet
  if(substr($peer_id,0,3)=='-BS') return StdDecodePeerId(substr($peer_id,3,7),"BTSlave"); # BTSlave
  if(substr($peer_id,0,3)=='-BX') return StdDecodePeerId(substr($peer_id,3,7),"BittorrentX"); # BittorrentX
  if(substr($peer_id,0,3)=='-CT') return "Ctorrent $peer_id[3].$peer_id[4].$peer_id[6]"; # CTorrent
  if(substr($peer_id,0,3)=='-KT') return StdDecodePeerId(substr($peer_id,3,7),"KTorrent"); # KTorrent
  if(substr($peer_id,0,3)=='-LT') return StdDecodePeerId(substr($peer_id,3,7),"libtorrent"); # libtorrent
  if(substr($peer_id,0,3)=='-LP') return StdDecodePeerId(substr($peer_id,4,4),"Lphant"); # Lphant
  if(substr($peer_id,0,3)=='-MP') return StdDecodePeerId(substr($peer_id,3,7),"MooPolice"); # MooPolice
  if(substr($peer_id,0,3)=='-MT') return StdDecodePeerId(substr($peer_id,3,7),"Moonlight"); # MoonlightTorrent
  if(substr($peer_id,0,3)=='-PO') return StdDecodePeerId(substr($peer_id,3,7),"PO Client"); #unidentified clients with versions
  if(substr($peer_id,0,3)=='-QT') return StdDecodePeerId(substr($peer_id,3,7),"Qt 4 Torrent"); # Qt 4 Torrent
  if(substr($peer_id,0,3)=='-RT') return StdDecodePeerId(substr($peer_id,3,7),"Retriever"); # Retriever
  if(substr($peer_id,0,3)=='-S2') return StdDecodePeerId(substr($peer_id,3,7),"S2 Client"); #unidentified clients with versions
  if(substr($peer_id,0,3)=='-SB') return StdDecodePeerId(substr($peer_id,3,7),"Swiftbit"); # Swiftbit
  if(substr($peer_id,0,3)=='-SN') return StdDecodePeerId(substr($peer_id,3,7),"ShareNet"); # ShareNet
  if(substr($peer_id,0,3)=='-SS') return StdDecodePeerId(substr($peer_id,3,7),"SwarmScope"); # SwarmScope
  if(substr($peer_id,0,3)=='-SZ') return StdDecodePeerId(substr($peer_id,3,7),"Shareaza"); # Shareaza
  if(preg_match("/^RAZA ([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+)/", $httpagent, $matches)) return "Shareaza $matches[1]";
  if(substr($peer_id,0,3)=='-TN') return StdDecodePeerId(substr($peer_id,3,7),"Torrent.NET"); # Torrent.NET
  if(substr($peer_id,0,3)=='-TR') return StdDecodePeerId(substr($peer_id,3,7),"Transmission"); # Transmission
  if(substr($peer_id,0,3)=='-TS') return StdDecodePeerId(substr($peer_id,3,7),"TorrentStorm"); # Torrentstorm
  if(substr($peer_id,0,3)=='-UR') return StdDecodePeerId(substr($peer_id,3,7),"UR Client"); # unidentified clients with versions
  if(substr($peer_id,0,3)=='-UT') return StdDecodePeerId(substr($peer_id,3,7),"uTorrent"); # uTorrent
  if(substr($peer_id,0,3)=='-XT') return StdDecodePeerId(substr($peer_id,3,7),"XanTorrent"); # XanTorrent
  if(substr($peer_id,0,3)=='-ZT') return StdDecodePeerId(substr($peer_id,3,7),"ZipTorrent"); # ZipTorrent
  if(substr($peer_id,0,3)=='-bk') return StdDecodePeerId(substr($peer_id,3,7),"BitKitten"); # BitKitten
  if(substr($peer_id,0,3)=='-lt') return StdDecodePeerId(substr($peer_id,3,7),"libTorrent"); # libTorrent
  if(substr($peer_id,0,3)=='-pX') return StdDecodePeerId(substr($peer_id,3,7),"pHoeniX"); # pHoeniX
  if(substr($peer_id,0,2)=='BG') return StdDecodePeerId(substr($peer_id,2,4),"BTGetit"); # BTGetit
  if(substr($peer_id,2,2)=='BM') return DecodeVersionString(substr($peer_id,0,2),"BitMagnet"); # BitMagnet
  if(substr($peer_id,0,2)=='OP') return StdDecodePeerId(substr($peer_id,2,4),"Opera"); # Opera
  if(substr($peer_id,0,4)=='270-') return "GreedBT 2.7.0"; # GreedBT
  if(substr($peer_id,0,4)=='271-') return "GreedBT 2.7.1"; # GreedBT 2.7.1
  if(substr($peer_id,0,4)=='346-') return "TorrentTopia"; # TorrentTopia
  if(substr($peer_id,0,3)=='-AR') return "Arctic Torrent"; # Arctic (no way to know the version)
  if(substr($peer_id,0,3)=='-G3') return "G3 Torrent"; # G3 Torrent
  if(substr($peer_id,0,6)=='BTDWV-') return "Deadman Walking"; # Deadman Walking
  if(substr($peer_id,5,7)=='Azureus') return "Azureus 2.0.3.2"; # Azureus 2.0.3.2
  if(substr($peer_id,0,8)=='PRC.P---') return "BitTorrent Plus! II"; # BitTorrent Plus! II
  if(substr($peer_id,0,8)=='P87.P---') return "BitTorrent Plus!"; # BitTorrent Plus!
  if(substr($peer_id,0,4)=='Plus') return StdDecodePeerId(substr($peer_id,4,5),"BitTorrent Plus! v2"); # BitTorrent Plus! v2 (not 100% sure on this one)
  if(substr($peer_id,0,8)=='S587Plus') return "BitTorrent Plus!"; # BitTorrent Plus!
  if(substr($peer_id,0,7)=='martini') return "Martini Man"; # Martini Man
  if(substr($peer_id,4,6)=='btfans') return "SimpleBT"; # SimpleBT
  if(substr($peer_id,3,9)=='SimpleBT?') return "SimpleBT"; # SimpleBT
  if(ereg("MFC_Tear_Sample", $httpagent)) return "SimpleBT";
  if(substr($peer_id,0,5)=='btuga') return "BTugaXP"; # BTugaXP
  if(substr($peer_id,0,5)=='BTuga') return "BTuga"; # BTugaXP
  if(substr($peer_id,0,5)=='oernu') return "BTugaXP"; # BTugaXP
  if(substr($peer_id,0,10)=='DansClient') return "XanTorrent"; # XanTorrent
  if(substr($peer_id,0,16)=='Deadman Walking-') return "Deadman"; # Deadman client
  if(substr($peer_id,0,8)=='XTORR302') return "TorrenTres 0.0.2"; # TorrenTres
  if(substr($peer_id,0,7)=='turbobt') return "TurboBT ".(substr($peer_id,7,5)); # TurboBT
  if(substr($peer_id,0,7)=='a00---0') return "Swarmy"; # Swarmy
  if(substr($peer_id,0,7)=='a02---0') return "Swarmy"; # Swarmy
  if(substr($peer_id,0,7)=='T00---0') return "Teeweety"; # Teeweety
  if(substr($peer_id,0,7)=='rubytor') return "Ruby Torrent v".ord($peer_id[7]); # Ruby Torrent
  if(substr($peer_id,0,5)=='Mbrst') return MainlineDecodePeerId(substr($peer_id,5,5),"burst!"); # burst!
  if(substr($peer_id,0,4)=='btpd') return "BT Protocol Daemon ".(substr($peer_id,5,3)); # BT Protocol Daemon
  if(substr($peer_id,0,8)=='XBT022--') return "BitTorrent Lite"; # BitTorrent Lite based on XBT code
  if(substr($peer_id,0,3)=='XBT') return StdDecodePeerId(substr($peer_id,3,3), "XBT"); # XBT Client
  if(substr($peer_id,0,4)=='-BOW') return StdDecodePeerId(substr($peer_id,4,5),"Bits on Wheels"); # Bits on Wheels
  if(substr($peer_id,1,2)=='ML') return MainlineDecodePeerId(substr($peer_id,3,5),"MLDonkey"); # MLDonkey
  if($peer_id[0]=='A') return StdDecodePeerId(substr($peer_id,1,9),"ABC"); # ABC
  if($peer_id[0]=='R') return StdDecodePeerId(substr($peer_id,1,5),"Tribler"); # Tribler
  if($peer_id[0]=='M'){
    if(preg_match("/^Python/", $httpagent, $matches)) return "Spoofing BT Client"; # Spoofing BT Client
    return MainlineDecodePeerId(substr($peer_id,1,7),"Mainline"); # Mainline BitTorrent with version
  }
  if($peer_id[0]=='O') return StdDecodePeerId(substr($peer_id,1,9),"Osprey Permaseed"); # Osprey Permaseed
  if($peer_id[0]=='S'){
    if(preg_match("/^BitTorrent\/3.4.2/", $httpagent, $matches)) return "Spoofing BT Client"; # Spoofing BT Client
    return StdDecodePeerId(substr($peer_id,1,9),"Shad0w"); # Shadow's client
  }
  if($peer_id[0]=='T'){
    if(preg_match("/^Python/", $httpagent, $matches)) return "Spoofing BT Client"; # Spoofing BT Client
    return StdDecodePeerId(substr($peer_id,1,9),"BitTornado"); # BitTornado
  }
  if($peer_id[0]=='U') return StdDecodePeerId(substr($peer_id,1,9),"UPnP"); # UPnP NAT Bit Torrent
  # Azureus / Localhost
  if(substr($peer_id,0,3)=='-AZ') {
    if(preg_match("/^Localhost ([0-9]+\.[0-9]+\.[0-9]+)/", $httpagent, $matches)) return "Localhost $matches[1]";
    if(preg_match("/^BitTorrent\/3.4.2/", $httpagent, $matches)) return "Spoofing BT Client"; # Spoofing BT Client
    if(preg_match("/^Python/", $httpagent, $matches)) return "Spoofing BT Client"; # Spoofing BT Client
    return StdDecodePeerId(substr($peer_id,3,7),"Azureus");
  }
  if(ereg("Azureus", $peer_id)) return "Azureus 2.0.3.2";
  # BitComet/BitLord/BitVampire/Modded FUTB BitComet
  if(substr($peer_id,0,4)=='exbc' || substr($peer_id,1,3)=='UTB'){
    if(substr($peer_id,0,4)=='FUTB') return DecodeVersionString(substr($peer_id,4,2),"BitComet Mod1");
    elseif(substr($peer_id,0,4)=='xUTB') return DecodeVersionString(substr($peer_id,4,2),"BitComet Mod2");
    elseif(substr($peer_id,6,4)=='LORD') return DecodeVersionString(substr($peer_id,4,2),"BitLord");
    elseif(substr($peer_id,6,3)=='---' && DecodeVersionString(substr($peer_id,4,2),"BitComet")=='BitComet 0.54') return "BitVampire";
    else return DecodeVersionString(substr($peer_id,4,2),"BitComet");
  }
  # Rufus
  if(substr($peer_id,2,2)=='RS'){
    for ($i=0; $i<=strlen(substr($peer_id,4,9)); $i++){
      $c = $peer_id[$i+4];
      if (ctype_alnum($c) || $c == chr(0)) $rufus_chk = true;
      else break;
    }
    if ($rufus_chk) return DecodeVersionString(substr($peer_id,0,2),"Rufus"); # Rufus
  }
  # BitSpirit
  if(substr($peer_id,14,6)=='HTTPBT' || substr($peer_id,16,4)=='UDP0') {
    if(substr($peer_id,2,2)=='BS') {
      if($peer_id[1]==chr(0)) return "BitSpirit v1";
      if($peer_id[1]== chr(2)) return "BitSpirit v2";
    }
        return "BitSpirit";
  }
  #BitSpirit
  if(substr($peer_id,2,2)=='BS') {
    if($peer_id[1]==chr(0)) return "BitSpirit v1";
    if($peer_id[1]==chr(2)) return "BitSpirit v2";
    return "BitSpirit";
  }
  # eXeem beta
  if(substr($peer_id,0,3)=='-eX') {
    $version_str = "";
    $version_str .= intval($peer_id[3],16).".";
    $version_str .= intval($peer_id[4],16);
    return "eXeem $version_str";
  }
  if(substr($peer_id,0,2)=='eX') return "eXeem"; # eXeem beta .21
  if(substr($peer_id,0,12)==(chr(0)*12) && $peer_id[12]==chr(97) && $peer_id[13]==chr(97)) return "Experimental 3.2.1b2"; # Experimental 3.2.1b2
  if(substr($peer_id,0,12)==(chr(0)*12) && $peer_id[12]==chr(0) && $peer_id[13]==chr(0)) return "Experimental 3.1"; # Experimental 3.1
  //if(substr($peer_id,0,12)==(chr(0)*12)) return "Mainline (obsolete)"; # Mainline BitTorrent (obsolete)
  //return "$httpagent [$peer_id]";
  return "Unknown client";
}
#========================================
#getAgent function by deliopoulos
#========================================

?>
