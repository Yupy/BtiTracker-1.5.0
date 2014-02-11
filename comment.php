<?php
require_once("include/functions.php");
require_once("include/config.php");

dbconn();
standardheader('Comments');

if (!$CURUSER || $CURUSER["uid"]==1)
   {
   err_msg(ERROR,ONLY_REG_COMMENT);
   stdfoot();
   exit();
}

$comment = ($_POST["comment"]);

$id = $_GET["id"];
if (isset($_GET["cid"]))
    $cid = intval($_GET["cid"]);
else
    $cid=0;

function comment_form()
{

    global $comment, $id, $cid;

     block_begin("".NEW_COMMENT."");

?>
  <center>
  <FORM ENCTYPE="multipart/form-data" name="comment" METHOD="POST">
  <input type="hidden" name="info_hash" value="<?php echo $id; ?>" />
  <table class="lista" border="0" cellpadding="10">
  <tr>
  <tr><td align="left" class="header"><?php echo USER_NAME;?></td><td class="lista" align="left" ><INPUT name="user" TYPE="TEXT"  value="<?php echo $_GET["usern"] ?>" size="20" maxlength="100" disabled; readonly></td></tr>
  <tr><td align="left" class="header"><?php echo COMMENT_1;?>:</td><td class="lista" align="left"><?php textbbcode("comment","comment", htmlsafechars(unesc($comment))); ?></td></tr>
  <tr><td class="header" colspan="2" align="center"><input type="submit" name="confirm" value="<?php echo FRM_CONFIRM;?>" />&nbsp;&nbsp;&nbsp;<input type="submit" name="confirm" value="<?php echo FRM_PREVIEW;?>" /></td></tr>
  </table>
  </form>
  </center>

<?php
   block_end();
}

if (isset($_GET["action"]))
 {
  if ($CURUSER["admin_access"]=="yes" && $_GET["action"]=="delete")
    {
     @run_query("DELETE FROM comments WHERE id=$cid");
     redirect("details.php?id=$id#comments");
     exit;
    }
 }

if (isset($_POST["info_hash"]))
   {
   if ($_POST["confirm"]==FRM_CONFIRM) {
   $comment = addslashes($_POST["comment"]);
      $user=AddSlashes($CURUSER["username"]);
      if ($user=="") $user="Anonymous";
  @run_query("INSERT INTO comments (added,text,ori_text,user,info_hash) VALUES (NOW(),\"$comment\",\"$comment\",\"$user\",\"" . ((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], StripSlashes($_POST["info_hash"])) : ((trigger_error("Error...", E_USER_ERROR)) ? "" : "")) . "\")");
  redirect("details.php?id=" . StripSlashes($_POST["info_hash"])."#comments");
  }
# Comment preview by miskotes
#############################

if ($_POST["confirm"]==FRM_PREVIEW) {
block_begin("".COMMENT_PREVIEW."");
print("<table width=100% align=center class=lista><tr><td class=lista align=center>" . format_comment($comment) . "</td></tr>\n");
print("</table>");
block_end();
comment_form();
stdfoot();

#####################
# Comment preview end
}
  else
    redirect("details.php?id=" . StripSlashes($_POST["info_hash"])."#comments");
}

else {
 comment_form();
   stdfoot();
}
?>