<?php
require_once("include/functions.php");
require_once("include/config.php");
require_once("include/blocks.php");

?>
<table width="100%" height="100%"  border="0">
<tr>
<td height="100" colspan="2">
    <table width=100%>
    <tr><td align=left>
    <a href=./index.php><img border=0 src="<?php echo $STYLEPATH ?>/logo.gif"></a>
    </td>
    </tr>
    </table>
</td>
</tr>
<tr><td height="100" colspan="2">
<?php
main_menu();
?>
</td></tr>
<table width="100%" height="100%"  border="0">
<tr>
<?php

side_menu();

?>
<td valign=top>

