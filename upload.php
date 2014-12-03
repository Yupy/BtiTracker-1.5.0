<?php
/*
* BtiTracker v1.5.0 is a php tracker system for BitTorrent, easy to setup and configure.
* This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
* Updated and Maintained by Yupy.
* Copyright (C) 2004-2014 Btiteam.org
*/
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'include'.DIRECTORY_SEPARATOR.'functions.php');
require_once(CLASS_PATH . 'class.Bencode.php');

function_exists("sha1") or die('<font color="red">' . NOT_SHA . '</font></body></html>');

dbconn();

standardheader('Uploads');

if (!user::$current || user::$current["can_upload"] == "no") {
    stderr(ERROR . NOT_AUTHORIZED_UPLOAD, SORRY . "...");
}

block_begin(MNU_UPLOAD);

print("<table class='lista' border='0' width='100%'>\n");
print("<tr><td align='center'>");

if (isset($_FILES["torrent"])) {
    if ($_FILES["torrent"]["error"] != 4) {
        $fd = fopen($_FILES["torrent"]["tmp_name"], "rb") or die(FILE_UPLOAD_ERROR_1);
        is_uploaded_file($_FILES["torrent"]["tmp_name"]) or die(FILE_UPLOAD_ERROR_2);
        $length = filesize($_FILES["torrent"]["tmp_name"]);
        if ($length)
            $alltorrent = fread($fd, $length);
        else {
            err_msg(ERROR, FILE_UPLOAD_ERROR_3);
            print("</td></tr></table>");
            block_end();
            stdfoot();
            exit();
        }
        
        //uTorrent v3.x.x fix
        $alltorrent = preg_replace("/file-mediali(.*?)ee(.*?):/i", "file-mediali0ee$2:", $alltorrent);
        $alltorrent = preg_replace("/file-durationli(.*?)ee(.*?):/i", "file-durationli0ee$2:", $alltorrent);
        
        $array = Bencode::decode($alltorrent);
        if (!isset($array)) {
            echo "<font color='red'>" . ERR_PARSER . "</FONT>";
            endOutput();
            exit;
        }
        if (!$array) {
            echo "<font color='red'>" . ERR_PARSER . "</FONT>";
            endOutput();
            exit;
        }

        if (in_array($array["announce"], $TRACKER_ANNOUNCEURLS) && $DHT_PRIVATE) {
            $array["info"]["private"] = 1;
            $hash  = sha1(Bencode::encode($array["info"]));
        } else {
            $hash = sha1(Bencode::encode($array["info"]));
        }
        fclose($fd);
    }
    
    if (isset($_POST["filename"]))
        $filename = $db->real_escape_string(htmlspecialchars($_POST["filename"]));
    else
        $filename = $db->real_escape_string(htmlspecialchars($_FILES["torrent"]["name"]));
    
    if (isset($hash) && $hash)
        $url = $TORRENTSDIR . "/" . $hash . ".btf";
    else
        $url = 0;
    
    if (isset($_POST["info"]) && $_POST["info"] != "")
        $comment = $db->real_escape_string($_POST["info"]);
    else {
        err_msg(ERROR, "You must enter a description!");
        print("</td></tr></table>");
        block_end();
        stdfoot();
        exit();
    }
    
    if (strlen($filename) == 0 && isset($array["info"]["name"]))
        $filename = $db->real_escape_string(htmlspecialchars($array["info"]["name"]));
    
    if (isset($array["comment"]))
        $info = $db->real_escape_string(utf8::is_utf8($array["comment"]));
    else
        $info = "";
    
    if (isset($array["info"]) && $array["info"])
        $upfile = $array["info"];
    else
        $upfile = 0;
    
    if (isset($upfile["length"])) {
        $size = floatval($upfile["length"]);
    } else if (isset($upfile["files"])) {
        // multifiles torrent
        $size = 0;
        foreach ($upfile["files"] as $file) {
            $size += floatval($file["length"]);
        }
    } else
        $size = "0";
    
    if (!isset($array["announce"])) {
        err_msg(ERROR, "Announce is empty");
        print("</td></tr></table>");
        block_end();
        stdfoot();
        exit();
    }
    
    $categoria = intval(0 + $_POST["category"]);
    $announce  = $array["announce"];
    $anonyme   = sqlesc($_POST["anonymous"]);
    $curuid    = user::$current["uid"];
    
    if ($categoria == 0) {
        err_msg(ERROR, WRITE_CATEGORY);
        print("</td></tr></table>");
        block_end();
        stdfoot();
        exit();
    }
    
    if ((strlen($hash) != 40) || !verifyHash($hash)) {
        echo ("<center><font color='red'>" . ERR_HASH . "</font></center>");
        endOutput();
    }

    if (!in_array($announce, $TRACKER_ANNOUNCEURLS) && $EXTERNAL_TORRENTS == false) {
        err_msg(ERROR, ERR_EXTERNAL_NOT_ALLOWED);
        unlink($_FILES["torrent"]["tmp_name"]);
        print("</td></tr></table>");
        block_end();
        stdfoot();
        exit();
    }

    if (in_array($announce, $TRACKER_ANNOUNCEURLS))
        $query = "INSERT INTO namemap (info_hash, filename, url, info, category, data, size, comment, uploader, anonymous) VALUES (\"$hash\", \"$filename\", \"$url\", \"$info\", 0 + " . $categoria . ",NOW(), \"$size\", \"$comment\", " . $curuid . ", " . $anonyme . ")";
    else
        $query = "INSERT INTO namemap (info_hash, filename, url, info, category, data, size, comment, external, announce_url, uploader, anonymous) VALUES (\"$hash\", \"$filename\", \"$url\", \"$info\", 0 + " . $categoria . ", NOW(), \"$size\", \"$comment\", \"yes\", \"$announce\", " . $curuid . ", " . $anonyme . ")";
	
    $status = makeTorrent($hash, true);
    quickQuery($query);
	
    if ($status) {
        move_uploaded_file($_FILES["torrent"]["tmp_name"], $TORRENTSDIR . "/" . $hash . ".btf") or die(ERR_MOVING_TORR);

        if (!in_array($announce, $TRACKER_ANNOUNCEURLS)) {
            require_once(INCL_PATH . 'getscrape.php');
            scrape($announce, $hash);
            print("<center>" . MSG_UP_SUCCESS . "<br /><br />\n");
            write_log("Uploaded new torrent $filename - EXT ($hash)", "add");
        } else {
            if ($DHT_PRIVATE) {
                $alltorrent = Bencode::encode($array);
                $fd         = fopen($TORRENTSDIR . "/" . $hash . ".btf", "rb+");
                fwrite($fd, $alltorrent);
                fclose($fd);
            }
            // with pid system active or private flag (dht disabled), tell the user to download the new torrent
            write_log("Uploaded new torrent " . $filename . " (" . $hash . ")", "add");
            print("<center>" . MSG_UP_SUCCESS . "<br /><br />\n");
            if ($PRIVATE_ANNOUNCE || $DHT_PRIVATE)
                print(MSG_DOWNLOAD_PID . "<br /><a href='download.php?id=$hash&f=" . urlencode($filename) . ".torrent'>" . DOWNLOAD . "</a><br /><br />");
        }
        print("<a href='torrents.php'>" . RETURN_TORRENTS . "</a></center>");
        print("</td></tr></table>");
        block_end();
    } else {
        err_msg(ERROR, ERR_ALREADY_EXIST);
        unlink($_FILES["torrent"]["tmp_name"]);
        print("</td></tr></table>");
        block_end();
        stdfoot();
    }
} else
    endOutput();

function endOutput()
{
    global $BASEURL, $user_id, $TRACKER_ANNOUNCEURLS;
    ?>
    </center>
    <?php
      echo "<center>" . INSERT_DATA . "<br /><br />";
      echo " " . ANNOUNCE_URL . "<br /><b>";
      foreach ($TRACKER_ANNOUNCEURLS as $taurl)
          echo $taurl . '<br />';
      echo "</b><br /></center>";
    ?>
    <form name='upload' method='post' enctype='multipart/form-data'>
    <table class='lista' align='center'>
    <tr>
       <td class='header'><?php echo TORRENT_FILE; ?></td>
       <td class='lista' align='left'>
    <?php
    if (function_exists("sha1"))
       echo "<input type='file' name='torrent'>";
    else
       echo "<i>" . NO_SHA_NO_UP . "</i>";
    ?>
       </td>
    </tr>
    <?php
       echo "<tr><td class='header'>" . CATEGORY_FULL . "</td><td class='lista' align='left'>";
       categories($category[0]);
       echo "</td></tr>";
    ?>
    <tr>
       <td class='header'><?php echo FILE_NAME; ?></td>
       <td class='lista' align='left'><input type='text' name='filename' size='50' maxlength='200' /></td>
    </tr>
    <tr>
       <td class='header' valign='top'><?php echo DESCRIPTION; ?></td>
       <td class='lista' align='left'><?php textbbcode("upload", "info"); ?></td>
    </tr>
    <?php
    print("<tr>
       <td colspan='2'><input type='hidden' name='user_id' size='50' value='" . $user_id . "' /></td>
    </tr>");
    print("<tr>
       <td class='header'>" . TORRENT_ANONYMOUS . "</td>
       <td class='lista'>&nbsp;&nbsp;" . NO . "<input type='radio' name='anonymous' value='false' checked />&nbsp;&nbsp;" . YES . "<input type='radio' name='anonymous' value='true' /></td>
    </tr>");
    if (function_exists("sha1"))
        echo "<tr>
          <td class='lista' align='center' colspan='2'><input type='checkbox' name='autoset' value='enabled' disabled checked />" . TORRENT_CHECK . "</td>
        </tr>";
    ?>
    <tr>
       <td align='right'><input type='submit' value='<?php echo FRM_SEND; ?>' /></td>
       <td align='left'><input type='reset' value='<?php echo FRM_RESET; ?>' /></td>
    </tr>
    </table>
    </form>
    <?php
    print("</td></tr></table>");
    block_end();
}

stdfoot();

?>
