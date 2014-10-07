<?php
/*
* BtiTracker v1.5.0 is a php tracker system for BitTorrent, easy to setup and configure.
* This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
* Updated and Maintained by Yupy.
* Copyright (C) 2004-2014 Btiteam.org
*/
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'include'.DIRECTORY_SEPARATOR.'functions.php');

dbconn();

standardheader('More Details', false);

if (!isset($_GET["form"]) || !isset($_GET["text"]))
{
    err_msg("Error!", "Missing parameter!");
    print("</body></html>");
    die();
}

$parentform = htmlentities(urldecode($_GET["form"]));
$parentarea = htmlentities(urldecode($_GET["text"]));

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

<table class='lista' width='100%' cellpadding='1' cellspacing='1'>
<tr>
<?php

global $count;

	$Smileys = array(
		':angry:'			=> 'angry.gif',
		':-D'				=> 'biggrin.gif',
		':D'				=> 'biggrin.gif',
		':|'				=> 'blank.gif',
		':-|'				=> 'blank.gif',
		':blush:'			=> 'blush.gif',
		':cool:'			=> 'cool.gif',
		':(('			=> 'crying.gif',
		':crying:'			=> 'crying.gif',
		':<<:'			=> 'eyesright.gif',
		':frown:'			=> 'frown.gif',
		'<3'				=> 'heart.gif',
		':unsure:'			=> 'hmm.gif',
		':lol:'				=> 'laughing.gif',
		':ninja:'			=> 'ninja.gif',
		':no:'				=> 'no.gif',
		':nod:'				=> 'nod.gif',
		':ohno:'			=> 'ohnoes.gif',
		':ohnoes:'			=> 'ohnoes.gif',
		':omg:'				=> 'omg.gif',
		':o'				=> 'ohshit.gif',
		':O'				=> 'ohshit.gif',
		':paddle:'			=> 'paddle.gif',
		':('				=> 'sad.gif',
		':-('				=> 'sad.gif',
		':shifty:'			=> 'shifty.gif',
		':sick:'			=> 'sick.gif',
		':)'				=> 'smile.gif',
		':-)'				=> 'smile.gif',
		':sorry:'			=> 'sorry.gif',
		':thanks:'			=> 'thanks.gif',
		':P'				=> 'tongue.gif',
		':p'				=> 'tongue.gif',
		':-P'				=> 'tongue.gif',
		':-p'				=> 'tongue.gif',
		':wave:'			=> 'wave.gif',
		';)'				=> 'wink.gif',
		':wink:'			=> 'wink.gif',
		':creepy:'			=> 'creepy.gif',
		':worried:'			=> 'worried.gif',
		':wtf:'				=> 'wtf.gif',
		':wub:'				=> 'wub.gif',
	);

while ((list($code, $url) = each($Smileys))) {
    if ($count % 3 == 0)
        print("\n<tr>");

    print("\n\t<td class='lista' align='center'><a href=\"javascript: SmileIT('".str_replace("'","\'",$code)."',window.opener.document.forms.$parentform.$parentarea);\"><img border='0' src='images/smilies/".$url."'></a></td>");
    $count++;

    if ($count % 3 == 0)
        print("\n</tr>");
}

?>
</tr>
</table>
<div align='center'>
  <a href='javascript: window.close()'><?php echo CLOSE; ?></a>
</div>
</body>
</html>