<?php
function clock_display($clocktype)
{
if ($clocktype == true) {
	$clock = 'anaclock.swf';
	$cheight = '130';
	$cwidth = '130';

} else {
	$clock = 'digiclock.swf';
        $cheight = '50';
        $cwidth = '100';
}
?>
<OBJECT classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0" width="<?php echo $cwidth; ?>" height="<?php echo $cheight; ?>">
<PARAM NAME=movie VALUE="./addons/clock/<?php echo $clock; ?>">
<PARAM NAME=quality VALUE=high>
<PARAM NAME=bgcolor VALUE=#FFFFFF>
<param name="wmode" value="transparent">
<param name="menu" value="false">
<EMBED src="./addons/clock/<?php echo $clock; ?>" quality=high bgcolor=#FFFFFF WIDTH="<?php echo $cwidth; ?>" HEIGHT="<?php echo $cheight; ?>" wmode="transparent" TYPE="application/x-shockwave-flash" PLUGINSPAGE="http://www.macromedia.com/go/getflashplayer" menu="false" >
</EMBED></OBJECT>
<?php
}
?>
