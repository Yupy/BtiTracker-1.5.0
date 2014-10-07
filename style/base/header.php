<?php
/*
* BtiTracker v1.5.0 is a php tracker system for BitTorrent, easy to setup and configure.
* This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
* Updated and Maintained by Yupy.
* Copyright (C) 2004-2014 Btiteam.org
*/

require_once(INCL_PATH . 'functions.php');
require_once(INCL_PATH . 'blocks.php');

if (user::$current['uid'] > 1) {
?>
    <table width='100%' height='100%'  border='0'>
    <tr>
        <td height='100' colspan='2'>
            <table width='100%'>
            <tr>
			    <td align='left'><a href='./index.php'><img border='0' src='<?php echo $STYLEPATH ?>/logo.gif'></a></td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
	    <td height='100' colspan='2'><?php main_menu(); ?></td>
	</tr>
    <table width='100%' height='100%' border='0'>
    <tr>
        <?php side_menu(); ?>
        <td valign='top'>
    <?php
}

?>