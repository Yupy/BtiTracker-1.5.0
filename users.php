<?php
require_once ("include/functions.php");
require_once ("include/config.php");

dbconn();

standardheader('Members');

if ($CURUSER["view_users"]=="no")
   {
       err_msg(ERROR,NOT_AUTHORIZED." ".MEMBERS."!");
       stdfoot();
       exit;
}
else
    {
     block_begin(MEMBERS_LIST);
     print_users();
     block_end();
     }
stdfoot();

?>