<?php
/*
* BtiTracker v1.5.0 is a php tracker system for BitTorrent, easy to setup and configure.
* This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
* Updated and Maintained by Yupy.
* Copyright (C) 2004-2014 Btiteam.org
*/
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'include'.DIRECTORY_SEPARATOR.'functions.php');

dbconn();

standardheader('Users Search', false);

if (isset($_GET['action']) && $_GET['action'])
    $action = security::html_safe($_GET['action']);
else
    $action = '';

if ($action! = "find")
{
    ?>
    <form action='searchusers.php?action=find' name='users' method='post'>
    <div align='center'>
    <table class='lista'>
    <tr>
        <td><?php echo USER_NAME;?>:</td>
        <td class='lista'><input type='text' name='user' size='40' maxlength='40' /></td>
        <td class='lista'><input type='submit' name='confirm' value='Search' /></td>
    </tr>
    </table>
    </div>
    </form>
    <?php
} else {
    $res = $db->query("SELECT username FROM users WHERE id > 1 AND username LIKE '%" . $db->real_escape_string($_POST["user"]) . "%' ORDER BY username");

	if (!$res or $res->num_rows == 0)
    {
        print("<center>" . NO_USERS_FOUND . "!<br />");
        print("<a href='searchusers.php'>" . RETRY . "</a></center>");
    } else {
        ?>
        <script language='javascript'>
            function SendIT(){
                window.opener.document.forms['edit'].elements['receiver'].value = document.forms['result'].elements['name'].options[document.forms['result'].elements['name'].options.selectedIndex].value;
                window.close();
            }
        </script>

        <div align='center'>
        <form name='result'><table class='lista'>
        <tr>
        <td class='lista'><?php print(USER_NAME);?>:</td>
        <?php
        print("\n<td class='lista'><select name='name' size='1'>");

        while($result = $res->fetch_array(MYSQLI_BOTH))
            print("\n<option name='uname' value='" . security::html_safe($result["username"]) . "'>" . security::html_safe($result["username"]) . "</option>");

        print("\n</select></td>");
        print("\n<td class='lista'><input type='button' name='confirm' onclick='javascript:SendIT();' value='" . FRM_CONFIRM . "' /></td>");
        ?>
        </tr>
        </table></form>
        </div>
        <?php
    }
}

print("\n<br />\n<div align='center'><a href='javascript: window.close()'>" . CLOSE . "</a></div>");
print("</body>\n</html>\n");

?>