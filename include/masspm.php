<?php
global $CURUSER;
// only for security, maybe someone try to access directly to the file...
if (!defined("IN_ACP"))
    die("No direct access!");

/**
Mass PM by vibes, Fuctions include ability to PM all users
or PM by userlevel or PM by Ratio, Ratio and userlevel work together so
you can PM say members with a ratio of 0.5 and below
**/
// Language defines, you can eva cut these and paste to your langfiles or just leave here and edit before uploading depends if you use more then one language in your admincp really ;) note: defines added to langfile will overwrite the same defines in this file!!
define("USERS_FOUND" , "users found");
define("USERS_PMED" , "users PMed");
define("WHO_PM" , "Who will the pm be sent to?");
define("MASS_SENT" , "Mass PM sent!!!");
define("MASS_PM" , "Mass PM");
define("MASS_PM_ERROR" , "It maybe a good idea to actually write something before submitting it!!!!");
define("RATIO_ONLY" , "this ratio only");
define("RATIO_GREAT" , "greater then this ratio");
define("RATIO_LOW" , "lower then this ratio");

//MASSPM SETTINGS

//This is for the drop down ratio box Where do you want the ratio range to start from?
$value=0.0;
//This is for the ending ratio range where do you want it to end?
$cutoff=10.0;
//Should we PM the sender a copy of the PM? usage: true or False
$pm_sender= true;
//Should we list the users PMed in the PM sent Box? usage: True or False
$list_users= true;
//what should the default subject be if none set?
$default_subject= "Global Notice";
//Who will the PM be sent from, you can register an acounnt here called system then change to $sender=100; where 100 is the systems UID number
$sender= $CURUSER['uid'];
//This will be added to the end of each message to deactivate set value to false EG $footer = false; by adding a \r in the footer before message will insert a new line
$footer= "\r\r this is an automated system please do not reply!!!";

//!!!!!*****DEBUG MODE*****!!!!! set to false for testing, DO NOT alter if you do not know how to read the code below this setting (comments added to make reading the code easy)!!! PMs wont send if set to false, recommented modes for testing are $list_users= true; and $pm_sender= true; to check PM is sent ;)
$pm = true;

//END OF SETTINGS, DONOT EDIT BELOW USNLESS YOU KNOW WHAT YOU ARE DOING!!!!

// initialize some variable...
$ratio=0;
$pick=0;
$msg="";
$ratio_details="";
$l_users="";
$level=0;
$level1=0;

// end

if(isset($_GET["error"]))
    $error=$_GET["error"];
else
    $error="";

//check if Mass PM was posted
if ($action=="post")
{
if(isset($_POST['masspm']))
{
//collect info from form
  $ratio = (isset($_POST["ratio"])?$_POST["ratio"]:0);
  $pick = (isset($_POST["pick"])?$_POST["pick"]:0);
  $level=intval(0+$_POST["level"]);
  $level1=intval(0+$_POST["level1"]);
  $subject = sqlesc($_POST["subject"]);
  $msg = (isset($_POST["msg"])?$_POST["msg"]:"");
//check if a subject was set, if not asign one
if ($subject=="''")
$subject="'$default_subject'";
//check if a message was set, if not redirect back to form with error
if($msg == "")
{
redirect("admincp.php?user=".$CURUSER["uid"]."&code=".$CURUSER["random"]."&do=masspm&action=write&error=return");
exit();
}
//check if we want to PM selected userlevels
  if ($level>0)
  {

$user_id_query = run_query("SELECT level FROM users_level WHERE id_level=$level")or sqlerr(__FILE__, __LINE__);
$user_rank=mysqli_fetch_array($user_id_query);
$user_level=$user_rank['level'];

$user_id_query1 = run_query("SELECT level FROM users_level WHERE id_level=$level1")or sqlerr(__FILE__, __LINE__);
$user_rank1=mysqli_fetch_array($user_id_query1);
$user_level1=$user_rank1['level'];
    if($level1>0 && $level < $level1)
    {
      $where = " AND id_level>=$level AND id_level<=$level1";
      $usr_lev = "in ".USER_LEVEL."s <b>($user_level - $user_level1)</b>";
    }

    elseif($level1>0 && $level > $level1)
    {
      $where = " AND id_level<=$level AND id_level>=$level1";
      $usr_lev = "in ".USER_LEVEL."s <b>($user_level1 - $user_level)</b>";
    }

    elseif($level>0 && $level1==0 || $level1>0 && $level1 == $level)
    {
      $where = " AND id_level=$level";
      $usr_lev = "in ".USER_LEVEL." <b>($user_level)</b>";
    }

  }
// this just incase first box is set to all and second is set to a level, setup to PM the one level selected :)
  elseif($level==0 && $level1>0)
  {
$user_id_query1 = run_query("SELECT level FROM users_level WHERE id_level=$level1")or sqlerr(__FILE__, __LINE__);
$user_rank1=mysqli_fetch_array($user_id_query1);
$user_level1=$user_rank1['level'];
    $where = " AND id_level=$level1";
    $usr_lev = "in ".USER_LEVEL." <b>($user_level1)</b>";
  }
//no userlevels selected to PM so PM everyone
  else
  {
    $where = "";
    $usr_lev = "in all ".USER_LEVEL."s";
  }

// do we want to PM users based on ratio?
$check_ratio=false;
if($ratio>0)
{
$check_ratio=true;
}

//add a footer to the message
if($footer)
$msg = "$msg $footer";
$msg = sqlesc($msg);
$i = 0;
//do database call
    $result_id = run_query("SELECT * FROM users where id > 1$where") or sqlerr(__FILE__, __LINE__);
   while ($id_collect = mysqli_fetch_array($result_id))
   {
if(!$list_users)
$l_users ="not listing users as its deactivated";
$user_id = $id_collect['id'];
// stop PM to sender added function below to PM sender ;)
if($user_id == $CURUSER['uid']) continue;
//did we want to PM based on ratio?
if($check_ratio)
{
$downloaded = $id_collect["downloaded"];
$uploaded = $id_collect["uploaded"];
//added in to stop divisons by zero
  if($downloaded == 0)
    $downloaded = "0.2";
  if($uploaded == 0)
    $uploaded = "0.1";
  $ratio1=number_format($uploaded/$downloaded,2);
// if matching ratio
  if($pick == 0)
  {
    $ratio_details = "with a ".RATIO." of <b>($ratio)</b>";
    if($ratio == $ratio1)
      {
      if($list_users)
      $l_users .="<a href=userdetails.php?id=$user_id>".$id_collect['username']."</a>  -  ";
      if($pm)
      run_query("INSERT INTO messages (sender, receiver, added, subject, msg) VALUES ($sender,$user_id,UNIX_TIMESTAMP(),$subject,$msg)");
      }
  else continue;
  }
//if ratio X + greater
  if($pick == 1)
  {
    $ratio_details = "with a ".RATIO." of <b>($ratio)</b> and above";
    if($ratio < $ratio1)
      {
      if($list_users)
      $l_users .="<a href=userdetails.php?id=$user_id>".$id_collect['username']."</a>  -  ";
      if($pm)
      run_query("INSERT INTO messages (sender, receiver, added, subject, msg) VALUES ($sender,$user_id,UNIX_TIMESTAMP(),$subject,$msg)");
      }
  else continue;
  }
//if ratio X + lower
  if($pick == 2)
  {
    $ratio_details = "with a ".RATIO." of <b>($ratio)</b> and below";
    if($ratio > $ratio1)
      {
      if($list_users)
      $l_users .="<a href=userdetails.php?id=$user_id>".$id_collect['username']."</a>  -  ";
      if($pm)
      run_query("INSERT INTO messages (sender, receiver, added, subject, msg) VALUES ($sender,$user_id,UNIX_TIMESTAMP(),$subject,$msg)");
      }
  else continue;
  }

}
//otherwise we did not want to pm users based on ratio
else
{
if($list_users)
$l_users .="<a href=userdetails.php?id=$user_id>".$id_collect['username']."</a>  -  ";
if($pm)
run_query("INSERT INTO messages (sender, receiver, added, subject, msg) VALUES ($sender,$user_id,UNIX_TIMESTAMP(),$subject,$msg)");
}
$i = $i+ 1;
}
}
// PM sender if true
if($pm_sender)
run_query("INSERT INTO messages (sender, receiver, added, subject, msg) VALUES ($sender,".$CURUSER['uid'].",UNIX_TIMESTAMP(),$subject,$msg)");
//pm sent block
block_begin(MASS_SENT);
print("\n<table class=\"lista\" width=\"100%\" align=\"center\" cellpadding=\"2\">");
print("\n<tr><td>".MASS_SENT."</td></tr>");
print("\n<tr><td class=\"header\">".SUBJECT.":</td><td class=\"lista\">".unesc($subject)."</td></tr>");
print("\n<tr><td class=\"header\">".BODY.":</td><td class=\"lista\">".format_comment(unesc($msg))."</td></tr><tr><td class=\"header\">info</td><td><b>$i</b> ".USERS_FOUND." $usr_lev $ratio_details !!<br><br>".USERS_PMED."<br>$l_users<br><br>Mass PM by vibes</td></tr>");
print("\n</table>");
print("<br />");
block_end();
            print("<br />");
}
// no pm set so display the form
elseif($action=="write")
{
block_begin(MASS_PM);

//error?
if($error=="return")
    echo "".MASS_PM_ERROR."";


print("\n<form method=\"post\" name=\"masspm\" action=\"admincp.php?user=".$CURUSER["uid"]."&code=".$CURUSER["random"]."&do=masspm&action=post\">
    <table class=\"lista\" align=\"center\" cellpadding=\"2\"><tr><td colspan=\"2\" class=\"header\">".WHO_PM."</td></tr>");
           print("<tr><td class=\"header\">From ".USER_LEVEL.":</td><td><select name=\"level\">");
           print("<option value=0".($level==0 ? " selected=selected " : "").">".ALL."</option>");
           $res=run_query("SELECT id,level FROM users_level WHERE id_level>1 ORDER BY id_level");
           while($row=mysqli_fetch_array($res))
           {
               $select="<option value='".$row["id"]."'";
               if ($level==$row["id"])
                  $select.="selected=\"selected\"";
               $select.=">".$row["level"]."</option>\n";
               print $select;
           }
           print("</select></td></tr>");
 print("<tr><td class=header>To ".USER_LEVEL.":</td><td><select name=\"level1\">");
           print("<option value=0".($level1==0 ? " selected=selected " : "").">".ALL."</option>");
           $res1=run_query("SELECT id,level FROM users_level WHERE id_level>1 ORDER BY id_level");
           while($row1=mysqli_fetch_array($res1))
           {
               $select="<option value='".$row1["id"]."'";
               if ($level1==$row1["id"])
                  $select.="selected=\"selected\"";
               $select.=">".$row1["level"]."</option>\n";
               print $select;
           }
           print("</select></td></tr>");
print("<tr><td class=header>".RATIO.":</td><td><select name=\"ratio\"><option value=0".($ratio==0 ? " selected=selected " : "").">any</option>");

while($value < $cutoff+0.1)
{
    print("<option value=$value".($ratio=="$value" ? " selected=selected " : "").">$value</option>");
    $value=$value + .1;
}
print("</select></td></tr>");
print("<tr><td class=\"header\">".RATIO.":</td><td><select name=\"pick\"><option value=0".($pick==0 ? " selected=selected " : "").">".RATIO_ONLY."</option>");
print("<option value=1".($pick==1 ? " selected=selected " : "").">".RATIO_GREAT."</option><option value=2".($pick==2 ? " selected=selected " : "").">".RATIO_LOW."</option>");
print("\n</select></td></tr><tr><td class=\"header\">".SUBJECT.":</td>");
    print("<td class=\"lista\"><input type=\"text\" name=\"subject\" size=\"40\" maxlength=\"40\" /></td></tr>");
                print("\n<tr><td colspan=\"2\">");
                print(textbbcode("masspm","msg","$msg"));
                print("\n</td></tr>");
                print("\n</table>");
                print("<br />");
                print("\n<table class=\"lista\" width=\"100%\" align=\"center\">");
                print("\n<tr><td class=\"lista\" align=\"center\" colspan=\"2\"><input type=\"submit\" name=\"masspm\" value=\"".FRM_CONFIRM."\" /></td></tr>");
                print("\n</table></form>");
            print("<br />");
            block_end();
            print("<br />");
}
else
redirect("admincp.php?user=".$CURUSER["uid"]."&code=".$CURUSER["random"]."");
?>