<?php

require_once("include/smilies.php");
require_once("include/functions.php");
require_once("include/config.php");

dbconn();

standardheader('More Details',false);

if (!isset($_GET["form"])||!isset($_GET["text"]))
    {
    err_msg("Error!","Missing parameter!");
    print("</body></html>");
    die();
    }

$parentform=htmlsafechars(urldecode($_GET["form"]));
$parentarea=htmlsafechars(urldecode($_GET["text"]));
?>
<script language='javascript'>

function SmileIT(smile,textarea){
    // Attempt to create a text range (IE).
    if (typeof(textarea.caretPos) != "undefined" && textarea.createTextRange)
    {
        var caretPos = textarea.caretPos;

        caretPos.text = caretPos.text.charAt(caretPos.text.length - 1) == ' ' ? smile + ' ' : smile
        caretPos.select();
    }
    // Mozilla text range replace.
    else if (typeof(textarea.selectionStart) != "undefined")
    {
        var begin = textarea.value.substr(0, textarea.selectionStart);
        var end = textarea.value.substr(textarea.selectionEnd);
        var scrollPos = textarea.scrollTop;

        textarea.value = begin + smile + end;

        if (textarea.setSelectionRange)
        {
            textarea.focus();
            textarea.setSelectionRange(begin.length + smile.length, begin.length + smile.length);
        }
        textarea.scrollTop = scrollPos;
    }
    // Just put it on the end.
    else
    {
        textarea.value += smile;
        textarea.focus(textarea.value.length - 1);
    }
}
</script>

<table class="lista" width="100%" cellpadding="1" cellspacing="1">
<tr>
<?php

global $count;

while ((list($code, $url) = each($smilies))) {
   if ($count % 3==0)
      print("\n<tr>");

      print("\n\t<td class=\"lista\" align=\"center\"><a href=\"javascript: SmileIT('".str_replace("'","\'",$code)."',window.opener.document.forms.$parentform.$parentarea);\"><img border=0 src=images/smilies/".$url."></a></td>");
      $count++;

   if ($count % 3==0)
      print("\n</tr>");
}

while ((list($code, $url) = each($privatesmilies))) {
   if ($count % 3==0)
      print("\n<tr>");

      print("\n\t<td class=\"lista\" align=\"center\"><a href=\"javascript: SmileIT('".str_replace("'","\'",$code)."',window.opener.document.forms.$parentform.$parentarea);\"><img border=0 src=images/smilies/".$url."></a></td>");
      $count++;

   if ($count % 3==0)
      print("\n</tr>");
}

?>
</tr>
</table>
<div align="center">
  <a href="javascript: window.close()"><?php echo CLOSE; ?></a>
</div>
</body>
</html>