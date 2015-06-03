<?php
/*
* BtiTracker v1.5.0 is a php tracker system for BitTorrent, easy to setup and configure.
* This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
* Updated and Maintained by Yupy.
* Copyright (C) 2004-2014 Btiteam.org
*/
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'include'.DIRECTORY_SEPARATOR.'functions.php');

dbconn();

standardheader('Manage News');

if (user::$current["edit_news"] != "yes") {
    err_msg(ERROR, ERR_NOT_AUTH);
    stdfoot();
    exit();
}

if (isset($_GET["act"]))
    $action = security::html_safe($_GET["act"]);
else
    $action = '';

if ($action == "del") {
    if (user::$current["delete_news"] == "yes") {
        $db->query("DELETE FROM news WHERE id = " . (int)$_GET["id"]);
        redirect("index.php");
        exit();
    } else {
        err_msg(ERROR, CANT_DELETE_NEWS);
        stdfoot();
        exit();
    }
    
} elseif ($action == "edit") {
    if (user::$current["edit_news"] == "yes") {
        $rnews = $db->query("SELECT * FROM news WHERE id = " . intval($_GET["id"]));
        if (!$rnews) {
            err_msg(ERROR, ERR_BAD_NEWS_ID);
            stdfoot();
            exit();
        }
        $row = $rnews->fetch_array(MYSQLI_BOTH);
        if ($row) {
            $news = security::html_safe(unesc($row["news"]));
            $title = security::html_safe(unesc($row["title"]));
        } else {
            err_msg(ERROR, ERR_NO_NEWS_ID);
            stdfoot();
            exit();
        }
    } else {
        err_msg(ERROR, CANT_DELETE_NEWS);
        stdfoot();
        exit();
    }
} else {
    if (!isset($_POST["conferma"]));
    elseif ($_POST["conferma"] == FRM_CONFIRM) {
        if (isset($_POST["news"]) && isset($_POST["title"])) {
            $news = $_POST["news"];
            $uid = user::$current["uid"];
            $title = $_POST["title"];
            if ($news == "" || $title == "") {
                err_msg(ERROR, ERR_INS_TITLE_NEWS);
            } else {
                $news = sqlesc($news);
                $title = sqlesc($title);
                $nid  = intval($_POST["id"]);
                $action = security::html_safe($_POST['action']);
                if ($action == "edit")
                    $db->query("UPDATE news SET news = " . $news . ", title = " . $title . " WHERE id = " . $nid);
                else
                    $db->query("INSERT INTO news (news, title, user_id, date) VALUES (" . $news . ", " . $title . ", " . $uid . ", NOW())");
                redirect("index.php");
                exit();
            }
        }
    } elseif ($_POST["conferma"] == FRM_CANCEL) {
        redirect("index.php");
        exit();
    } else {
        $title = '';
        $news  = '';
    }
}

block_begin(NEWS_PANEL);

global $news, $title;
?>
<div align='center'>
  <form action='news.php' name='news' method='post'>
  <table border='0' class='lista'>
  <tr><td><input type='hidden' name='action' value='<?php echo $action ?>'/></td></tr>
  <tr><td><input type='hidden' name='id' value='<?php echo (int)$_GET['id'] ?>'/></td></tr>
  <tr>
       <td align='center' colspan='2' class='header' >
           <?php echo NEWS_INSERT; ?>:<br />
       </td>
  </tr>
  <tr>
     <td align='left' class='lista' style='font-size:10pt'>
         <?php echo NEWS_TITLE; ?>
     </td>
     <td align='left' class='lista'>
         <input type='text' name='title' size='40' maxlength='40' value='<?php echo $title; ?>'/>
     </td>
  </tr>
  <tr>
     <td align='left' class='lista' valign='top' style='font-size:10pt'>
         <?php echo NEWS_DESCRIPTION; ?>
     </td>
      <td align='left' class='lista'>
  <?php echo textbbcode('news', 'news', security::html_safe($news)); ?>
      </td>
  </tr>
  <tr>
  </tr>
  <tr>
     <td align='left' class='header'>
         <input type='submit' name='conferma' value='<?php echo FRM_CONFIRM ?>' />
     </td>
     <td align='left' class='header'>
         <input type='submit' name='conferma' value='<?php echo FRM_CANCEL ?>' />
     </td>
  </tr>
  </table>
  </form>
</div>

<?php

block_end();
stdfoot();

?>
