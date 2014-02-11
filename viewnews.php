<?php
require_once ("include/functions.php");
require_once ("include/config.php");

dbconn();

standardheader('News');

if ($CURUSER["view_news"]=="no")
   {
       err_msg(ERROR,NOT_AUTH_VIEW_NEWS);
       stdfoot();
       exit;
}
else
    {
     block_begin("News");
     print_news();
     block_end();
     }
stdfoot();
?>