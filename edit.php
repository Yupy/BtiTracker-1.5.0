<?php
/*
* BtiTracker v1.5.0 is a php tracker system for BitTorrent, easy to setup and configure.
* This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
* Updated and Maintained by Yupy.
* Copyright (C) 2004-2014 Btiteam.org
*/
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'include'.DIRECTORY_SEPARATOR.'functions.php');

dbconn();

standardheader('Edit Torrents');

$scriptname = $_SERVER["PHP_SELF"];
$link = $_GET["returnto"];

if ($link == "")
   $link = "torrents.php";

if ((isset($_POST["comment"])) && (isset($_POST["name"])))
{
    if ($_POST["action"] == FRM_CONFIRM)
	{
        if ($_POST["name"] == '')
        {
            err_msg("Error!", "You must specify torrent name.");
            stdfoot();
            exit;
        }

        if ($_POST["comment"] == '')
        {
            err_msg("Error!","You must specify description.");
            stdfoot();
            exit;
        }

        $fname = sqlesc(security::html_safe($_POST["name"]));
        $torhash = AddSlashes($_POST["info_hash"]);
        write_log("Modified torrent " . $fname . " (" . $torhash . ")", "modify");
        echo "<center>".PLEASE_WAIT."</center>";

        $db->query("UPDATE namemap SET filename = " . $fname . ", comment = '" . $db->real_escape_string(AddSlashes($_POST["comment"])) . "', category = " . intval($_POST["category"]) . " WHERE info_hash = '" . $torhash . "'");

		print("<script language='javascript'>window.location.href='" . $link . "'</script>");
        exit();
    } else {
        print("<script language='javascript'>window.location.href='" . $link . "'</script>");
        exit();
    }
}

// view torrent's details
if (isset($_GET["info_hash"]))
{
    $query = "SELECT namemap.info_hash, namemap.filename, namemap.url, UNIX_TIMESTAMP(namemap.data) AS data, namemap.size, namemap.comment, namemap.category AS cat_name, summary.seeds, summary.leechers, summary.finished, summary.speed, namemap.uploader FROM namemap LEFT JOIN categories ON categories.id = namemap.category LEFT JOIN summary ON summary.info_hash = namemap.info_hash WHERE namemap.info_hash = '" . AddSlashes($_GET["info_hash"]) . "'";
    $res = $db->query($query) or die(CANT_DO_QUERY);
    $results = $res->fetch_array(MYSQLI_BOTH);

    if (!$results)
        err_msg(ERROR, TORRENT_EDIT_ERROR);
    else {
        block_begin(EDIT_TORRENT);

        if (!user::$current || (user::$current["edit_torrents"] == "no" && user::$current["uid"] != $results["uploader"]))
        {
            err_msg(ERROR, CANT_EDIT_TORR);
            block_end();
            stdfoot();
            exit();
        }
        ?>
        
        <div align='center'>
        <form action='<?php echo $scriptname . "?returnto=" . $link; ?>' method='post' name='edit'>
        <table class='lista'>
        <tr>
        <td align='right' class='header'><?php echo FILE_NAME; ?>:</td><td class='lista'><input type='text' name='name' value='<?php echo security::html_safe($results["filename"]); ?>' size='60' /></td>
        </tr>
		<tr>
        <td align='right' class='header'><?php echo INFO_HASH;?>:</td><td class='lista'><?php echo security::html_safe($results["info_hash"]);  ?></td>
        </tr><tr>
        <td align='right' class='header'><?php echo DESCRIPTION; ?>:</td><td class='lista'><?php textbbcode("edit", "comment", security::html_safe(unesc($results["comment"]))) ?></td>
        </tr>
		<tr>
         
        <?php
        echo "<td align='right' class='header'>".CATEGORY_FULL.":</td><td class='lista' align='left'>";
        
        categories($results["cat_name"]);
        
        echo "</td>";
		
        include(INCL_PATH . 'offset.php');
        
        ?>
        </tr>
		<tr>
        <td align='right' class='header'><?php echo SIZE; ?>:</td><td class='lista'><?php echo misc::makesize((int)$results["size"]); ?></td>
        </tr>
		<tr>
        <td align='right' class='header'><?php echo ADDED; ?>:</td><td class='lista'><?php echo date("d/m/Y H:m:s", $results["data"] - $offset); ?></td>
        </tr>
		<tr>
        <td align='right' class='header'><?php echo DOWNLOADED; ?>:</td><td class='lista'><?php echo (int)$results["finished"] . " " . X_TIMES; ?></td>
        </tr>
		<tr>
        <td align='right' class='header'><?php echo PEERS; ?>:</td><td class='lista'><?php echo SEEDERS .": " . (int)$results["seeds"] . ", " . LEECHERS .": " . (int)$results["leechers"] . " = " . ((int)$results["leechers"] + (int)$results["seeds"]) . " " . PEERS; ?></td>
        </tr>
        <tr>
		<td><input type='hidden' name='info_hash' size='40' value='<?php echo security::html_safe($results["info_hash"]);  ?>'></td><td></td>
		</tr>
        <tr>
		<td align='right'></td>
        </table>
        <table>
		<td align='right'>
        <input type='submit' value='<?php echo FRM_CONFIRM; ?>' name='action' />
        </td>
        <td>
        <input type='submit' value='<?php echo FRM_CANCEL; ?>' name='action' /></td>
        </form>
        </table>
        </tr>
        </div>
        
        <?php
    }

block_end();
}

stdfoot();

?>