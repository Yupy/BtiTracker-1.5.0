<?php
/*
* BtiTracker v1.5.0 is a php tracker system for BitTorrent, easy to setup and configure.
* This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
* Updated and Maintained by Yupy.
* Copyright (C) 2004-2014 Btiteam.org
*/

global $db;

if (isset(user::$current) && user::$current && user::$current["uid"] > 1) {
?>
<table class='lista' cellpadding='2' cellspacing='0' width='100%'>
<tr>
<?php
    $style   = style_list();
    $langue  = language_list();
	
    $resuser = $db->query("SELECT * FROM users WHERE id = " . user::$current["uid"]);
    $rowuser = $resuser->fetch_array(MYSQLI_BOTH);
	
    print("<td class='lista' align='center'>" . USER_LEVEL . ": " . security::html_safe(user::$current["level"]) . "</td>\n");
    print("<td class='green' align='center'>&#8593&nbsp;" . misc::makesize((int)$rowuser['uploaded']));
    print("</td><td class='red' align='center'>&#8595&nbsp;" . misc::makesize((int)$rowuser['downloaded']));
    print("</td><td class='lista' align='center'>(SR " . ((int)$rowuser['downloaded'] > 0 ? number_format((int)$rowuser['uploaded'] / (int)$rowuser['downloaded'], 2) : "&infin;") . ")</td>\n");
    
	if (user::$current["admin_access"] == "yes")
        print("\n<td align='center' class='lista'><a href='admincp.php?user=" . user::$current["uid"] . "&code=" . user::$current["random"] . "'>" . MNU_ADMINCP . "</a></td>\n");
    
    print("<td class='lista' align='center'><a href='usercp.php?uid=" . user::$current["uid"] . "'>" . USER_CP . "</a></td>\n");
    
    $resmail = $db->query("SELECT COUNT(*) FROM messages WHERE readed = 'no' AND receiver = " . user::$current['uid']);
    if ($resmail && $resmail->num_rows > 0) {
        $mail = $resmail->fetch_row();
        if ($mail[0] > 0)
            print("<td class='lista' align='center'><a href='usercp.php?uid=" . user::$current["uid"] . "&do=pm&action=list'>" . MAILBOX . "</a> (<font color='#FF0000'><b>" . (int)$mail[0] . "</b></font>)</td>\n");
        else
            print("<td class='lista' align='center'><a href='usercp.php?uid=" . user::$current["uid"] . "&do=pm&action=list'>" . MAILBOX . "</a></td>\n");
    } else
        print("<td class='lista' align='center'><a href='usercp.php?uid=" . user::$current["uid"] . "&do=pm&action=list'>" . MAILBOX . "</a></td>\n");
    
    print("\n<form name='jump1'><td class='lista'><select name='style' size='1' onChange='location=document.jump1.style.options[document.jump1.style.selectedIndex].value' style='font-size:10px'>");
    foreach ($style as $a) {
        print("<option ");
        if ($a["id"] == user::$current["style"])
            print("selected='selected'");
        print(" value='account_change.php?style=" . (int)$a["id"] . "&returnto=" . urlencode($_SERVER['REQUEST_URI']) . "'>" . security::html_safe($a["style"]) . "</option>");
    }
    print("</select></td>");
    
    print("\n<td class='lista'><select name='langue' size='1' onChange='location=document.jump1.langue.options[document.jump1.langue.selectedIndex].value' style='font-size:10px'>");
    foreach ($langue as $a) {
        print("<option ");
        if ($a["id"] == user::$current["language"])
            print("selected='selected'");
        print(" value='account_change.php?langue=" . (int)$a["id"] . "&returnto=" . urlencode($_SERVER['REQUEST_URI']) . "'>" . security::html_safe($a["language"]) . "</option>");
    }
    print("</select></td></form>");

?>
</tr>
</table>
<?php
} else {
    if (!isset($user))
        $user = '';
?>
    <form action='login.php' name='login' method='post'>
    <table class='lista' border='0' width='100%' cellpadding='2' cellspacing='0'>
    <tr>
    <td class='lista' align='left'>
      <table border='0' cellpadding='2' cellspacing='0'>
      <tr>
      <td align='right' class='lista'><?php
    echo USER_NAME;
?>:</td>
      <td class='lista'><input type='text' size='15' name='uid' value='<?php
    $user;
?>' maxlength='40' style='font-size:10px' /></td>
      <td align='right' class='lista'><?php
    echo USER_PWD;
?>:</td>
      <td class='lista'><input type='password' size='15' name='pwd' maxlength='40' style='font-size:10px' /></td>
      <td class='lista' align='center'><input type='submit' value='<?php
    echo FRM_LOGIN;
?>' style='font-size:10px' /></td>
      </tr>
      </table>
    </td>
    <td class='lista' align='center'><a href='account.php'><?php
    echo ACCOUNT_CREATE;
?></a></td>
    <td class='lista' align='center'><a href='recover.php'><?php
    echo RECOVER_PWD;
?></a></td>
    </tr>
    </table>
    </form>
    <?php
}
?>