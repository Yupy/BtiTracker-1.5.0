<?php
// - baterist BTIT v1.2 SearchDiff Hack v0.3

global $CURUSER;
$gonderen= $CURUSER['uid'];

function report($id, $name, $down, $up, $rank, $first, $last) {

global $CURUSER;

IF ($down > 0 ) $ratio = substr($up / $down,0,5); else $ratio = "&infin;";

if ($down > $up) $diff="<b><font color=red>&#8595&nbsp;".makesize($down-$up)."</font></b>";
elseif ($up > $down) $diff="<b><font color=blue>&#8593&nbsp;".makesize($up-$down)."</font></b>";
else $diff="<b><font color=Cyan>0</font></b>";

print("\n<tr>\n<td class=lista align=center><b><font color=blue>".$id."</font></b></td>");
print("\n<td class=lista align=center><b><font color=lavender><a href=userdetails.php?id=".$id.">".$name."</a></font></b></td>");
print("\n<td class=lista align=center><b><font color=Red> &#8595&nbsp;".makesize($down)."</b></font></td>");
print("\n<td class=lista align=center><b><font color=green> &#8593&nbsp;".makesize($up)."</b></font></td>");
print("\n<td class=lista align=center><b>".$ratio."</b></td>");
print("\n<td class=lista align=center><b>".$rank."</b></td>");
print("\n<td class=lista align=center><b>".$diff."</b></td>");
print("\n<td class=lista align=center><b>".date("d/m/Y H:i:s",$first)."</b></td>");
print("\n<td class=lista align=center><b>".date("d/m/Y H:i:s",$last)."</b></td>");
print("\n<td class=lista align=center><b><a href=account.php?act=mod&uid=".$id."&returnto=admincp.php?user=".$CURUSER["uid"]."&code=".$CURUSER["random"]."&do=searchdiff><img src=./style/base/edit.png border=0 alt='Edit'/></b></td>");
print("\n<td class=lista align=center><b><a href=account.php?act=del&uid=".$id."&returnto=admincp.php?user=".$CURUSER["uid"]."&code=".$CURUSER["random"]."&do=searchdiff><img src=./style/base/delete.png border=0 alt='Delete'/></b></td>");
print("\n<td class=lista align=center><input type=\"checkbox\" name=\"uyedegis[]\" value=\"".$id."\" /></td>\n</tr>");
}


$type=(isset($_POST["type"])?$_POST["type"]:"GB");
$diff=(isset($_POST["diff"])?$_POST["diff"]:50);
$readyto=(isset($_POST["readyto"])?$_POST["readyto"]:"sa");
$kullan=(isset($_POST["kullan"])?$_POST["kullan"]:0);
$kullan1=(isset($_POST["kullan1"])?$_POST["kullan1"]:0);
$changeug=(isset($_POST["changeug"])?$_POST["changeug"]:"sa");
$mesajat=(isset($_POST["mesajat"])?$_POST["mesajat"]:"sa");
$grupdegis=(isset($_POST["grupdegis"])?$_POST["grupdegis"]:"sa");
$mesajmetni=(isset($_POST["mesajmetni"])?$_POST["mesajmetni"]:"sa");
$baslik=(isset($_POST["baslik"])?$_POST["baslik"]:"sa");

$count=0;
block_begin("Search Diff");
?>
<center>
<TABLE class="lista">
<TR>
<TD align="center" class="header"><?php echo "Search Difference"; ?></TD></TR>
<TR><TD align="center" class="blocklist">
<form method=post action="admincp.php?user=<?php echo $CURUSER["uid"]; ?>&code=<?php echo $CURUSER["random"]; ?>&do=searchdiff">
&nbsp;&nbsp;<input type=text name="diff" value='<?php echo $diff;?>' size=13 maxlength=16>

<?php

$s=array('KB' => '1024', 'MB' => '1048576', 'GB' => '1073741824', 'TB' => '1099411627776' );
$opt=array("KB","MB","GB","TB");
print("&nbsp;&nbsp;<select name=type>");
for ($id=0; $id<count($opt); $id++) {
$option="<option ";
if ($opt[$id]==$type) $option.="selected=selected ";
$option.="value=".$opt[$id].">".$opt[$id]."</option>";
print($option);
}

?>
</select>
</TD></TR>

<tr>
<td align="center" class="blocklist">
User Group :

         <?php
   //<!Dropdown added by miskotes>
         print("<select name=\"kullan\">");
         print("<option value=0".($kullan==0 ? " selected=selected " : "").">".ALL."</option>");
         $res=run_query("SELECT id,level FROM users_level WHERE id_level>1 ORDER BY id_level");
         while($row=mysqli_fetch_array($res))
         {
             $select="<option value='".$row["id"]."'";
             if ($kullan==$row["id"])
                $select.="selected=\"selected\"";
             $select.=">".$row["level"]."</option>\n";
             print $select;
         }
         print("</select>");
   // <!End dropdown>
         ?>

</td>
</tr>
<TR>
<TR><TD align="center" class="lista">
<input type="submit" name="readyto" value="Go"></td></tr></form></input></table><br>
</center>
<?php
if ($changeug=="Work"){
if ($grupdegis=="evet"){
 print("<center>");
 foreach($_POST["uyedegis"] as $uyedegis=>$degeri)
 {
  @run_query("UPDATE users SET id_level='".$kullan1."' WHERE id='".$degeri."'");
  print("User <b>".$degeri."</b> ID LEVEL has changed to <b>".$kullan1."</b><br>");
 }
 print("</center>");
 }

  if ($mesajat=="evet"){
    print("<center>");
  foreach($_POST["uyedegis"] as $uyedegis=>$degeri)
   {
   @run_query("INSERT INTO messages (sender, receiver, added, subject, msg) VALUES ('".$gonderen."','".$degeri."',UNIX_TIMESTAMP(),'".$baslik."','".$mesajmetni."')");
   print("PM send to User <b>".$degeri."</b><br>");
   }
     print("</center>");
  }

}
if ($readyto=="Go") {

$mdiff=$_POST["diff"] * $s[$_POST["type"]];
?>
<TABLE width=100% class=lista cellpadding=0 cellspacing=0>
<TR><TD align="center" height="20px" class="block"><b>Search for difference > <?php echo makesize($mdiff); ?> and User Group=<?php echo ($kullan==0?"ALL":$kullan) ?></b></TD></TR></table>
<center>
<TABLE width=80% class="lista">
<tr>
<td colspan="12">
<form method=post action="admincp.php?user=<?php echo $CURUSER["uid"]; ?>&code=<?php $CURUSER["random"]; ?>&do=searchdiff">

<center>
<b><input name="mesajat" type="checkbox" value="evet" />
MESSAGE</b> <br /><input name="baslik" type="text" id="baslik" value="Write subject here" size="40" maxlength="40" />
<br />
<textarea name="mesajmetni" cols="32" rows="5" id="mesajmetni">Write Your PM Here!</textarea>
<tr>
<td colspan="12">
<input name="grupdegis" type="checkbox" value="evet" /> <b>Change User Group : </b>
         <?php
   // <!Dropdown added by miskotes>
         print("<select name=\"kullan1\">");
         $res=run_query("SELECT id,level FROM users_level WHERE id_level>1 ORDER BY id_level");
         while($row=mysqli_fetch_array($res))
         {
             $select="<option value='".$row["id"]."'";
             if ($kullan1==$row["id"])
                $select.="selected=\"selected\"";
             $select.=">".$row["level"]."</option>\n";
             print $select;
         }
         print("</select>");
 //<!End dropdown>
         ?>
   </td></tr></center>

<tr>
<td colspan="12">
<input type="submit" name="changeug" value="Work">
</td>
</tr>
<TD align="center" class="header">ID</TD>
<TD align="center" class="header">User</TD>
<TD align="center" class="header">Downloaded</TD>
<TD align="center" class="header">Uploaded</TD>
<TD align="center" class="header">Ratio</TD>
<TD align="center" class="header">Rank</TD>
<TD align="center" class="header">Difference</TD>
<TD align="center" class="header">Register Date</TD>
<TD align="center" class="header">Last Connect</TD>
<TD align="center" class="header">Edit</TD>
<TD align="center" class="header">Delete</TD>
<TD align="center" class="header">C</TD>
</TR>
<?php
if ($kullan==0) {
$q=run_query("SELECT users.id as fid, username, downloaded, uploaded, level, UNIX_TIMESTAMP(joined) as joined, UNIX_TIMESTAMP(lastconnect) as lastconnect FROM users LEFT JOIN users_level ON users.id_level=users_level.id where ((downloaded - uploaded) > '".$mdiff."') ORDER BY (uploaded / downloaded) ASC");
}
else
{
$q=run_query("SELECT users.id as fid, username, downloaded, uploaded, level, UNIX_TIMESTAMP(joined) as joined, UNIX_TIMESTAMP(lastconnect) as lastconnect FROM users LEFT JOIN users_level ON users.id_level=users_level.id where (users.id_level='".$kullan."' and (downloaded - uploaded) > '".$mdiff."') ORDER BY (uploaded / downloaded) ASC");
}

while ($user=mysqli_fetch_object($q)) {
if ($user) {
report($user->fid, $user->username, $user->downloaded, $user->uploaded, $user->level, $user->joined, $user->lastconnect);
   $count++;
}
}

print("</form></table>");

echo "<br><br> Found <b>".$count."</b> users whose difference is higher than <b>".makesize($mdiff)."</b>";


}
   block_end();

?>