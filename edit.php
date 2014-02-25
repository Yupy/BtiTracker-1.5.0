<?php
require_once("include/functions.php");
require_once("include/config.php");

dbconn();

standardheader('Edit Torrents');

$scriptname = $_SERVER["PHP_SELF"];
$link = $_GET["returnto"];

if ($link=="")
   $link="torrents.php";

// save editing and got back from where i come

if ((isset($_POST["comment"])) && (isset($_POST["name"]))){

   if ($_POST["action"]==FRM_CONFIRM) {

   if ($_POST["name"]=='')
        {
        err_msg("Error!","You must specify torrent name.");
        stdfoot();
        exit;
   }

   if ($_POST["comment"]=='')
        {
        err_msg("Error!","You must specify description.");
        stdfoot();
        exit;
   }

   $fname=sqlesc(htmlsafechars($_POST["name"]));
   $torhash=AddSlashes($_POST["info_hash"]);
   write_log("Modified torrent $fname ($torhash)","modify");
   echo "<center>".PLEASE_WAIT."</center>";
   run_query("UPDATE namemap SET filename=$fname, comment='" . AddSlashes($_POST["comment"]) . "', category=" . intval($_POST["category"]) . " WHERE info_hash='" . $torhash . "'");
   $Memcached->delete_value("Description::".$torhash);
   print("<script LANGUAGE=\"javascript\">window.location.href=\"$link\"</script>");
   exit();
   }

   else {
        print("<script LANGUAGE=\"javascript\">window.location.href=\"$link\"</script>");
        exit();
   }
}

// view torrent's details
if (isset($_GET["info_hash"])) {

  $query ="SELECT namemap.info_hash, namemap.filename, namemap.url, UNIX_TIMESTAMP(namemap.data) as data, namemap.size, namemap.comment, namemap.category as cat_name, summary.seeds, summary.leechers, summary.finished, summary.speed, namemap.uploader FROM namemap LEFT JOIN categories ON categories.id=namemap.category LEFT JOIN summary ON summary.info_hash=namemap.info_hash WHERE namemap.info_hash ='" . AddSlashes($_GET["info_hash"]) . "'";
  $res = run_query($query) or die(CANT_DO_QUERY.((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)));
  $results = mysqli_fetch_array($res);

  if (!$results)
     err_msg(ERROR,TORRENT_EDIT_ERROR);

  else {

  block_begin(EDIT_TORRENT);

  if (!$CURUSER || ($CURUSER["edit_torrents"]=="no" && $CURUSER["uid"]!=$results["uploader"]))
     {
         err_msg(ERROR,CANT_EDIT_TORR);
         block_end();
         stdfoot();
         exit();
     }
?>

<div align="center">
<form action="<?php echo $scriptname."?returnto=$link"; ?>" method="post" name="edit">
<table class=lista>
<tr>
<td align=right class=header><?php echo FILE_NAME; ?>: </td><td class=lista><input type="text" name="name" value="<?php echo $results["filename"]; ?>" size="60" /></td>
</tr><tr>
<td align=right class=header><?php echo INFO_HASH;?>:</td><td class=lista ><?php echo $results["info_hash"];  ?></td>
</tr><tr>
<td align=right class="header"><?php echo DESCRIPTION; ?>:</td><td class=lista><?php textbbcode("edit","comment",unesc($results["comment"])) ?></td>
</tr><tr>

<?php
       echo "<td align=right class=\"header\" >".CATEGORY_FULL." : </td><td class=\"lista\" align=\"left\">";

    categories($results["cat_name"]);

      echo "</td>";
include("include/offset.php");

?>

</tr><tr>
<td align=right class="header"><?php echo SIZE; ?>:</td><td class="lista" ><?php echo makesize($results["size"]); ?></td>
</tr><tr>
<td align=right class="header"><?php echo ADDED; ?>:</td><td class="lista" ><?php echo date("d/m/Y",$results["data"]-$offset); ?></td>
</tr><tr>
<td align=right class="header"><?php echo DOWNLOADED; ?>:</td><td class="lista" ><?php echo $results["finished"]." ".X_TIMES; ?></td>
</tr><tr>
<td align=right class="header"><?php echo PEERS; ?>:</td><td class="lista" ><?php echo SEEDERS .": " .$results["seeds"].",".LEECHERS .": ". $results["leechers"]."=". ($results["leechers"]+$results["seeds"]). " ". PEERS; ?></td>
</tr>
<tr><td><INPUT TYPE=hidden NAME="info_hash" SIZE=40 VALUE=<?php echo $results["info_hash"];  ?>></TD><td></td></tr>
<tr><td ALIGN=RIGHT></td>
</table>
<table><td ALIGN=right>
<INPUT type="submit" value="<?php echo FRM_CONFIRM; ?>" name="action" />
</TD>
<td>
<INPUT type="submit" value="<?php echo FRM_CANCEL;?>" name="action" /></td>
</form>
</table>
</tr>
</div>

<?php
  }  // results

  block_end();

} // info_hash

stdfoot();

?>
