<?php
/*
* BtiTracker v1.5.0 is a php tracker system for BitTorrent, easy to setup and configure.
* This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
* Updated and Maintained by Yupy.
* Copyright (C) 2004-2014 Btiteam.org
*/

global $db, $user;

if (!user::$current || user::$current["id"] == 1)
{
    #Do nothing
} else {
    block_begin(BLOCK_USER);
    // user information
    $style = style_list();
    $langue = language_list();
    print("\n<tr><td align='center' class='blocklist'>" . USER_NAME . ": " . unesc(user::$current["username"]) . "</td></tr>\n");
    print("<tr><td align='center' class='blocklist'>" . USER_LEVEL . ": " . security::html_safe(user::$current["level"]) . "</td></tr>\n");

    $resmail = $db->query("SELECT COUNT(*) FROM messages WHERE readed = 'no' AND receiver = " . user::$current['uid']);
    if ($resmail && $resmail->num_rows > 0)
    {
        $mail = $resmail->fetch_row();
        if ($mail[0]>0)
            print("<td class='blocklist' align='center'><a href='usercp.php?uid=" . user::$current["uid"] . "&do=pm&action=list'>" . MAILBOX . "</a> (<font color='#FF0000'><b>" . (int)$mail[0] . "</b></font>)</td>\n");
        else
            print("<td class='blocklist' align='center'><a href='usercp.php?uid=" . user::$current["uid"] . "&do=pm&action=list'>" . MAILBOX . "</a></td>\n");
    }
    else
        print("<tr><td align='center'>" . NO_MAIL . "</td></tr>");
        print("<tr><td align='center' class='blocklist'>");
        include(INCL_PATH . 'offset.php');
        print(USER_LASTACCESS . ":<br />" . date("d/m/Y H:i:s", user::$current["lastconnect"] - $offset));
        print("</td></tr>\n<tr><form name='jump'><td class='blocklist' align='center'>");
        print(USER_STYLE . ":<br />\n<select name='style' size='1' onChange='location=document.jump.style.options[document.jump.style.selectedIndex].value'>");

        foreach($style as $a)
        {
            print("<option ");
            if ($a["id"] == user::$current["style"])
                print("selected='selected'");
                print(" value='account_change.php?style=" . $a["id"] . "&returnto=" . urlencode($_SERVER['REQUEST_URI']) . "'>" . security::html_safe($a["style"]) . "</option>");
        }

        print("</select>");
        print("</td></tr>\n<tr><td class='blocklist' align='center'>");
        print(USER_LANGUE . ":<br />\n<select name='langue' size='1' onChange='location=document.jump.langue.options[document.jump.langue.selectedIndex].value'>");

		foreach($langue as $a)
        {
            print("<option ");
            if ($a["id"] == user::$current["language"])
                print("selected='selected'");
                print(" value='account_change.php?langue=" . $a["id"] . "&returnto=" . urlencode($_SERVER['REQUEST_URI']) . "'>" . security::html_safe($a["language"]) . "</option>");
        }

        print("</select>");
        print("</td>\n</form></tr>\n");
        print("\n<tr><td align='center' class='blocklist'><a href='usercp.php?uid=" . user::$current["uid"] . "'>" . USER_CP . "</a></td></tr>\n");

		if (user::$current["admin_access"] == "yes")
            print("\n<tr><td align='center' class='blocklist'><a href='admincp.php?user=" . user::$current["uid"] . "&code=" . user::$current["random"] . "'>" . MNU_ADMINCP . "</a></td></tr>\n");
	
	block_end();
}

?>