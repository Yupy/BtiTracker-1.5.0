<?php
require_once ("include/functions.php");
require_once ("include/config.php");

dbconn();

standardheader('Manage News');

if ($CURUSER["edit_news"]!="yes")
   {
   err_msg(ERROR,ERR_NOT_AUTH);
   stdfoot();
   exit();
   }

if (isset($_GET["act"])) $action=$_GET["act"];
else $action ="";

if ($action=="del")
   {
       if ($CURUSER["delete_news"]=="yes")
          {
              run_query("DELETE FROM news WHERE id=".$_GET["id"]);
              redirect("index.php");
              exit();
          }
          else
              {
              err_msg(ERROR,CANT_DELETE_NEWS);
              stdfoot();
              exit();
              }

   }
elseif ($action=="edit")
       {
       if ($CURUSER["edit_news"]=="yes")
          {
              $rnews=run_query("SELECT * FROM news WHERE id=".intval($_GET["id"]));
              if (!$rnews)
                 {
                 err_msg(ERROR,ERR_BAD_NEWS_ID);
                 stdfoot();
                 exit();
                 }
              $row=mysqli_fetch_array($rnews);
              if ($row)
                 {
                   $news=unesc($row["news"]);
                   $title=unesc($row["title"]);
                 }
              else
                  {
                   err_msg(ERROR,ERR_NO_NEWS_ID);
                   stdfoot();
                   exit();
                  }
          }
          else
              {
              err_msg(ERROR,CANT_DELETE_NEWS);
              stdfoot();
              exit();
              }
       }
else
    {
if (!isset($_POST["conferma"])) ;
      elseif ($_POST["conferma"]==FRM_CONFIRM)
         {
         if (isset($_POST["news"]) && isset($_POST["title"]))
            {
              $news=$_POST["news"];
              $uid=$CURUSER["uid"];
              $title=$_POST["title"];
              if ($news=="" || $title=="")
              {
                  err_msg(ERROR,ERR_INS_TITLE_NEWS);
              }
              else
              {
                $news=sqlesc($news);
                $title=sqlesc($title);
                $nid=intval($_POST["id"]);
                $action=$_POST['action'];
                if ($action=="edit")
                   run_query("UPDATE news SET news=$news,title=$title WHERE id=$nid") or die(((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)));
                else
                    run_query("INSERT INTO news (news,title,user_id,date) VALUES ($news,$title,$uid,NOW())") or die(((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)));
                redirect("index.php");
                exit();
              }
            }
         }
         elseif ($_POST["conferma"]==FRM_CANCEL) {
                redirect("index.php");
                exit();
                }
         else {
              $title="";
              $news="";
         }
}

block_begin(NEWS_PANEL);
global $news, $title;
?>
<div align="center">
  <form action="news.php" name="news" method="post">
  <table border="0" class="lista">
  <tr><td><input type="hidden" name="action" value="<?php echo $action ?>"/></td></tr>
  <tr><td><input type="hidden" name="id" value="<?php echo $_GET["id"] ?>"/></td></tr>
  <tr>
       <td align="center" colspan=2 class="header" >
           <?php echo NEWS_INSERT; ?>:<br />
       </td>
  </tr>
  <tr>
     <td align="left" class="lista" style="font-size:10pt">
         <?php echo NEWS_TITLE; ?>
     </td>
     <td align="left" class="lista">
         <input type="text" name="title" size="40" maxlength="40" value="<?php echo $title; ?>"/>
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