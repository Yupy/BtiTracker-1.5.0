<?php

function main_menu()
{
$res =run_query("SELECT * FROM blocks WHERE position='t' AND status=1 ORDER BY sortid");
$i=0;
$blocks=array();
while($result=mysqli_fetch_array($res)){
    if($result["status"]) {
        $block=$result["content"];
        $blocks[$i++]=$block;

    }
}
foreach ($blocks as $entry){
    if($entry!="forum")
    include("blocks/".$entry."_block.php");
    elseif($entry=="forum" && ($GLOBALS["FORUMLINK"]=="" || $GLOBALS["FORUMLINK"]=="internal"))
    include("blocks/".$entry."_block.php");
}

}

function center_menu()
{


$res =run_query("SELECT * FROM blocks WHERE position='c' AND status=1  ORDER BY sortid");
$i=0;
$blocks=array();
while($result=mysqli_fetch_array($res)){
    if($result["status"]) {
        $block=$result["content"];
        $blocks[$i++]=$block;

    }
}
foreach ($blocks as $entry){
    if($entry!="forum")
    include("blocks/".$entry."_block.php");
    elseif($entry=="forum" && ($GLOBALS["FORUMLINK"]=="" || $GLOBALS["FORUMLINK"]=="internal"))
    include("blocks/".$entry."_block.php");
}

}


function side_menu()
{


$res =run_query("SELECT * FROM blocks WHERE position='l' AND status=1  ORDER BY sortid");
$i=0;
$blocks=array();
while($result=mysqli_fetch_array($res)){
    if($result["status"]) {
        $block=$result["content"];
        $blocks[$i++]=$block;

    }
}
if (count($blocks)>0)
 {
 // make new columns only if at least 1 block
?>
<td width="200" valign=top>
<?php
  foreach ($blocks as $entry){
     if($entry!="forum")
     include("blocks/".$entry."_block.php");
     elseif($entry=="forum" && ($GLOBALS["FORUMLINK"]=="" || $GLOBALS["FORUMLINK"]=="internal"))
     include("blocks/".$entry."_block.php");
 }

 ?>
</td>
 <?php
 }
}

function right_menu()
{

$res =run_query("SELECT * FROM blocks WHERE position='r' AND status=1  ORDER BY sortid");
$i=0;
$blocks=array();
while($result=mysqli_fetch_array($res)){
    if($result["status"]) {
        $block=$result["content"];
        $blocks[$i++]=$block;

    }
}
if (count($blocks)>0)
 {
?>
<td width="200" valign="top">
<?php
 // make new columns only if at least 1 block
 foreach ($blocks as $entry){
    if($entry!="forum")
    include("blocks/".$entry."_block.php");
    elseif($entry=="forum" && ($GLOBALS["FORUMLINK"]=="" || $GLOBALS["FORUMLINK"]=="internal"))
    include("blocks/".$entry."_block.php");
    }
?>
</td>
<?php
 }
}

?>