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
  </CENTER>
  <?php
    echo "<center>" . INSERT_DATA . "<BR><BR>";
    echo " " . ANNOUNCE_URL . "<br /><b>";
    foreach ($TRACKER_ANNOUNCEURLS as $taurl)
        echo "$taurl<br />";
    echo "</b><BR></center>";
?>
  <FORM name="upload" method="post" ENCTYPE="multipart/form-data">
  <TABLE class="lista" align="center">
  <TR><TD class="header"><?php
    echo TORRENT_FILE;
?>:</TD><TD class="lista" align="left">
  <?php
    if (function_exists("sha1"))
        echo '<INPUT TYPE="file" NAME="torrent">';
    else
        echo "<I>" . NO_SHA_NO_UP . "</I>";
?>
  </TD>
  </TR>
  <?php
    echo "<TR><TD class=\"header\" >" . CATEGORY_FULL . " : </TD><TD class=\"lista\" align=\"left\">";
    
    categories($category[0]);
    
    echo "</TD></TR>";
?>
  <TR>
  <TD class="header"><?php
    echo FILE_NAME;
?>(<?php
    echo FACOLTATIVE;
?>): </TD>
  <TD class="lista"  align="left"><INPUT TYPE="text" name="filename" size="50" maxlength="200" /></TD>
  </TR>
  <TR>
  <TD class="header" valign="top"><?php
    echo DESCRIPTION;
?>: </TD>
  <TD class="lista"  align="left"><?php
    textbbcode("upload", "info");
?></TD>
  </TR>
  <?php
    print("<TR><TD colspan=\"2\"><INPUT TYPE=\"hidden\" name=\"user_id\" size=\"50\" value=\"$user_id\" /> </TD /></TR>");
    print('<TR><td class="header">' . TORRENT_ANONYMOUS . '</td><TD class="lista">&nbsp;&nbsp;' . NO . '<INPUT TYPE="radio" name="anonymous" value="false" checked />&nbsp;&nbsp;' . YES . '<INPUT TYPE="radio" name="anonymous" value="true" /></TD></TR>');
    if (function_exists("sha1"))
        echo '<TR><TD class="lista" align="center" colspan="2"><INPUT type="checkbox" name="autoset" value="enabled" disabled checked />' . TORRENT_CHECK . '</TD></TR>';
?>
  <TR>
  <TD align="right"><INPUT type="submit" value="<?php
    echo FRM_SEND;
?>" /></TD>
  <TD align="left"><INPUT type="reset" value="<?php
    echo FRM_RESET;
?>" /></TD>
  </TR>
  </TABLE>
  </FORM>
  <?php
    print("</td></tr></table>");
    block_end();
}

stdfoot();

?>
