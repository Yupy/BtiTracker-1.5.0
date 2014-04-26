<?php

require_once ("include/functions.php");
require_once ("include/config.php");

dbconn();

standardheader('Manage News');

if ($CURUSER["edit_news"] != "yes")
{
    err_msg(ERROR, ERR_NOT_AUTH);
    stdfoot();
    exit();
}

if (isset($_GET["act"]))
    $action = $db->escape_string($_GET["act"]);
else
    $action = '';

if ($action == "del")
{
    if ($CURUSER["delete_news"] == "yes")
    {
        $db->execute("
		            DELETE FROM 
					    news 
					WHERE id = ".intval($_GET["id"])) or $db->display_errors();

        redirect("index.php");
        exit();
    } else {
        err_msg(ERROR, CANT_DELETE_NEWS);
        stdfoot();
        exit();
    }
}
elseif ($action == "edit")
{
    if ($CURUSER["edit_news"] == "yes")
    {
        $rnews = $db->execute("
		                    SELECT 
									 * 
								  FROM 
									 news 
								  WHERE id = ".intval($_GET["id"])) or $db->display_errors();

        if (!$rnews)
       {
            err_msg(ERROR, ERR_BAD_NEWS_ID);
            stdfoot();
            exit();
        }

        $row = $db->fetch_array($rnews);

        if ($row)
        {
            $news = htmlsafechars(unesc($row["news"]));
            $title = htmlsafechars(unesc($row["title"]));
        } else {
            err_msg(ERROR, ERR_NO_NEWS_ID);
            stdfoot();
            exit();
        }
    } else {
        err_msg(ERROR,CANT_DELETE_NEWS);
        stdfoot();
        exit();
    }
} else {
if (!isset($_POST["conferma"]));
    elseif ($_POST["conferma"] == FRM_CONFIRM)
    {
        if (isset($_POST["news"]) && isset($_POST["title"]))
        {
            $news = $db->escape_string($_POST["news"]);
            $uid = (int)$CURUSER["uid"];
            $title = $db->escape_string($_POST["title"]);

            if ($news == '' || $title == '')
            {
                err_msg(ERROR, ERR_INS_TITLE_NEWS);
            } else {
                $news = sqlesc($news);
                $title = sqlesc($title);
                $nid = intval($_POST["id"]);
                $action = $db->escape_string($_POST['action']);

                if ($action == "edit")
                    $db->execute("
					             UPDATE 
								      news 
								    SET 
								      news = ".$news.", 
									   title = ".$title." 
								    WHERE id = ".$nid) or $db->display_errors();
                else
                    $db->execute("
					            INSERT INTO 
								     news (news, title, user_id, date) 
							      VALUES 
								     (".$news.", ".$title.", ".$uid.", NOW())") or $db->display_errors();

				redirect("index.php");
                exit();
            }
        }
    }
    elseif ($_POST["conferma"] == FRM_CANCEL) {
        redirect("index.php");
        exit();
    } else {
        $title = '';
        $news = '';
    }
}

block_begin(NEWS_PANEL);

global $news, $title;

?>
<div align="center">
  <form action="news.php" name="news" method="post">
  <table border="0" class="lista">
  <tr><td><input type="hidden" name="action" value="<?php echo htmlsafechars($action) ?>"/></td></tr>
  <tr><td><input type="hidden" name="id" value="<?php echo (int)$_GET["id"] ?>"/></td></tr>
  <tr>
       <td align="center" colspan="2" class="header" >
           <?php echo NEWS_INSERT; ?>:<br />
       </td>
  </tr>
  <tr>
     <td align="left" class="lista" style="font-size:10pt">
         <?php echo NEWS_TITLE; ?>
     </td>
     <td align="left" class="lista">
         <input type="text" name="title" size="40" maxlength="40" value="<?php echo htmlsafechars($title); ?>"/>
     </td>
  </tr>
  <tr>
     <td align="left" class="lista" valign="top" style="font-size:10pt">
         <?php echo NEWS_DESCRIPTION; ?>
     </td>
      <td align="left" class="lista">
  <?php echo textbbcode("news","news",$news); ?>
      </td>
  </tr>
  <tr>
  </tr>
  <tr>
     <td align="left" class="header" >
         <input type="submit" name="conferma" value="<?php echo FRM_CONFIRM ?>" />
     </td>
     <td align="left"class="header" >
         <input type="submit" name="conferma" value="<?php echo FRM_CANCEL ?>" />
     </td>
  </tr>
  </table>
  </form>
</div>

<?php

block_end();
stdfoot();

?>
