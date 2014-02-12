<?php
global $CURUSER, $Memcached;
if (!$CURUSER || $CURUSER["view_users"]=="no")
   {
    // do nothing
   }
else
    {

     block_begin("Online Users");
     $curtime=time();
     $curtime-=60*15;
     $print="";

     if (!isset($regusers)) $regusers = 0;
     if (!isset($gueststr)) $gueststr = '';

     $users="";
	  $onlineusers_key = "OnlineUsers::";
	if (($users = $Memcached->get_value($onlineusers_key)) === false) {
     $res=run_query("SELECT username, users.id, prefixcolor, suffixcolor FROM users INNER JOIN users_level ON users.id_level=users_level.id WHERE UNIX_TIMESTAMP(lastconnect)>=".$curtime." AND users.id>1") or die(((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)));

     if ($res)
        {
        while ($ruser=mysqli_fetch_row($res))
              {
              $users.=(($regusers>0?", ":"")."\n<a href=userdetails.php?id=$ruser[1]&returnto=".urlencode($_SERVER['REQUEST_URI']).">".StripSlashes($ruser[2].$ruser[0].$ruser[3])."</a>");
              $regusers++;
              }
     }
     $Memcached->cache_value($onlineusers_key, $users, 300);
     }
     // guest code
     $guest_ip  = explode('.', $_SERVER['REMOTE_ADDR']);
     $guest_ip  = pack("C*", $guest_ip [0], $guest_ip [1], $guest_ip [2], $guest_ip [3]);
     if (!file_exists("addons/guest.dat"))
        {
         $handle = fopen("addons/guest.dat", "w");
         fclose($handle);
     }
     $handle = fopen("addons/guest.dat", "rb+");
     flock($handle, LOCK_EX);
     $guest_num = intval(filesize("addons/guest.dat") / 8);
     if ($guest_num>0)
        $data = fread($handle, $guest_num * 8);
     else
         $data = fread($handle, 8);
     $guest=array();
     $updated = false;
     for($i=0;$i<$guest_num ;$i++)
         {
         if ($guest_ip == substr($data,  $i * 8 + 4, 4))
            {
                $updated = true;
                $guest[$i]=pack("L",time()).$guest_ip;
            }
         elseif (join("",unpack("L",substr($data,  $i * 8, 4)))<$curtime)
              $guest_num--;
         else
             $guest[$i] = substr($data, $i * 8, 8);

     }
     if($updated == false)
     {
         $guest[] = pack("L",time()).$guest_ip;
         $guest_num++;
     }

     rewind($handle);
     ftruncate($handle, 0);
     fwrite($handle, join('', $guest), $guest_num * 8);
     flock($handle, LOCK_UN);
     fclose($handle);
     $guest_num-=$regusers;
     if ($guest_num<0)
        $guest_num=0;
     if ($guest_num>0)
        $gueststr.=$guest_num+$regusers." visitor".($guest_num+$regusers>1?"s":"")." ($guest_num guest".($guest_num>1?"s":"")."\n";
     elseif ($guest_num+$regusers==0)
         $print.=NOBODY_ONLINE."\n";
     else
         $gueststr.=$guest_num+$regusers." visitor".($guest_num+$regusers>1?"s":"")." (";

     print($print."<tr><td class='lista' align='center'>" $gueststr . ($guest_num>0 && $regusers>0?" ".WORD_AND." ":"") . ($regusers>0?"$regusers ".MEMBER.($regusers>1?"s":"")."): ":")") . $users ."\n</td></tr>");
     block_end();

} // end if user can view
?>
