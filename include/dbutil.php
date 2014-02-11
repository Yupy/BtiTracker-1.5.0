<?php

if (!defined("IN_ACP"))
    die("No direct access!");

block_begin("Database Tools");
	
	
switch($action)
    {
    case 'runqry':
        if (isset($_POST["confirm"]) && $_POST["confirm"]==FRM_CONFIRM)
            {
              if ($_POST["runquery"]!="")
                    {
                    // just in case...
                    $_POST["runquery"]=strip_tags($_POST["runquery"]);
                    $thequery=strtoupper($_POST["runquery"]);
                    // try to run the query
                    $dbres=run_query(unesc($_POST["runquery"])) or die(((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)));
                    if (in_array(substr($thequery,0,strpos($thequery," ")),array("SELECT","SHOW","EXPLAIN","DESCRIBE")))
                      // display result
                          {
                           // display result
                           $ad_display.="
                           <br />
                           <form name=\"dbutil\" action=\"admincp.php?user=".$CURUSER["uid"]."&code=".$CURUSER["random"]."&do=dbutil&action=qry\" method=\"post\">
                           <table class=\"lista\" cellspacing=\"1\" cellpadding=\"0\" align=\"center\" border=\"0\" width=\"98%\">
                           <tr>
                           <td>".
                           unesc($_POST["runquery"])
                           ."
                           </td>
                           </tr>
                           <tr>
                           <td>
                           <table class=\"lista\" cellspacing=\"1\" cellpadding=\"0\" align=\"center\" border=\"0\" width=\"100%\">
                           <tr>";
                           // display header (fields' name)
                           $i=0;
                           $field=array();
                           while ($fname=(((($___mysqli_tmp = mysqli_fetch_field_direct($dbres, mysqli_field_tell($dbres))) && is_object($___mysqli_tmp)) ? ( (!is_null($___mysqli_tmp->primary_key = ($___mysqli_tmp->flags & MYSQLI_PRI_KEY_FLAG) ? 1 : 0)) && (!is_null($___mysqli_tmp->multiple_key = ($___mysqli_tmp->flags & MYSQLI_MULTIPLE_KEY_FLAG) ? 1 : 0)) && (!is_null($___mysqli_tmp->unique_key = ($___mysqli_tmp->flags & MYSQLI_UNIQUE_KEY_FLAG) ? 1 : 0)) && (!is_null($___mysqli_tmp->numeric = (int)(($___mysqli_tmp->type <= MYSQLI_TYPE_INT24) || ($___mysqli_tmp->type == MYSQLI_TYPE_YEAR) || ((defined("MYSQLI_TYPE_NEWDECIMAL")) ? ($___mysqli_tmp->type == MYSQLI_TYPE_NEWDECIMAL) : 0)))) && (!is_null($___mysqli_tmp->blob = (int)in_array($___mysqli_tmp->type, array(MYSQLI_TYPE_TINY_BLOB, MYSQLI_TYPE_BLOB, MYSQLI_TYPE_MEDIUM_BLOB, MYSQLI_TYPE_LONG_BLOB)))) && (!is_null($___mysqli_tmp->unsigned = ($___mysqli_tmp->flags & MYSQLI_UNSIGNED_FLAG) ? 1 : 0)) && (!is_null($___mysqli_tmp->zerofill = ($___mysqli_tmp->flags & MYSQLI_ZEROFILL_FLAG) ? 1 : 0)) && (!is_null($___mysqli_type = $___mysqli_tmp->type)) && (!is_null($___mysqli_tmp->type = (($___mysqli_type == MYSQLI_TYPE_STRING) || ($___mysqli_type == MYSQLI_TYPE_VAR_STRING)) ? "type" : "")) &&(!is_null($___mysqli_tmp->type = ("" == $___mysqli_tmp->type && in_array($___mysqli_type, array(MYSQLI_TYPE_TINY, MYSQLI_TYPE_SHORT, MYSQLI_TYPE_LONG, MYSQLI_TYPE_LONGLONG, MYSQLI_TYPE_INT24))) ? "int" : $___mysqli_tmp->type)) &&(!is_null($___mysqli_tmp->type = ("" == $___mysqli_tmp->type && in_array($___mysqli_type, array(MYSQLI_TYPE_FLOAT, MYSQLI_TYPE_DOUBLE, MYSQLI_TYPE_DECIMAL, ((defined("MYSQLI_TYPE_NEWDECIMAL")) ? constant("MYSQLI_TYPE_NEWDECIMAL") : -1)))) ? "real" : $___mysqli_tmp->type)) && (!is_null($___mysqli_tmp->type = ("" == $___mysqli_tmp->type && $___mysqli_type == MYSQLI_TYPE_TIMESTAMP) ? "timestamp" : $___mysqli_tmp->type)) && (!is_null($___mysqli_tmp->type = ("" == $___mysqli_tmp->type && $___mysqli_type == MYSQLI_TYPE_YEAR) ? "year" : $___mysqli_tmp->type)) && (!is_null($___mysqli_tmp->type = ("" == $___mysqli_tmp->type && (($___mysqli_type == MYSQLI_TYPE_DATE) || ($___mysqli_type == MYSQLI_TYPE_NEWDATE))) ? "date " : $___mysqli_tmp->type)) && (!is_null($___mysqli_tmp->type = ("" == $___mysqli_tmp->type && $___mysqli_type == MYSQLI_TYPE_TIME) ? "time" : $___mysqli_tmp->type)) && (!is_null($___mysqli_tmp->type = ("" == $___mysqli_tmp->type && $___mysqli_type == MYSQLI_TYPE_SET) ? "set" : $___mysqli_tmp->type)) &&(!is_null($___mysqli_tmp->type = ("" == $___mysqli_tmp->type && $___mysqli_type == MYSQLI_TYPE_ENUM) ? "enum" : $___mysqli_tmp->type)) && (!is_null($___mysqli_tmp->type = ("" == $___mysqli_tmp->type && $___mysqli_type == MYSQLI_TYPE_GEOMETRY) ? "geometry" : $___mysqli_tmp->type)) && (!is_null($___mysqli_tmp->type = ("" == $___mysqli_tmp->type && $___mysqli_type == MYSQLI_TYPE_DATETIME) ? "datetime" : $___mysqli_tmp->type)) && (!is_null($___mysqli_tmp->type = ("" == $___mysqli_tmp->type && (in_array($___mysqli_type, array(MYSQLI_TYPE_TINY_BLOB, MYSQLI_TYPE_BLOB, MYSQLI_TYPE_MEDIUM_BLOB, MYSQLI_TYPE_LONG_BLOB)))) ? "blob" : $___mysqli_tmp->type)) && (!is_null($___mysqli_tmp->type = ("" == $___mysqli_tmp->type && $___mysqli_type == MYSQLI_TYPE_NULL) ? "null" : $___mysqli_tmp->type)) && (!is_null($___mysqli_tmp->type = ("" == $___mysqli_tmp->type) ? "unknown" : $___mysqli_tmp->type)) && (!is_null($___mysqli_tmp->not_null = ($___mysqli_tmp->flags & MYSQLI_NOT_NULL_FLAG) ? 1 : 0)) ) : false ) ? $___mysqli_tmp : false))
                                {
                                $ad_display.="<td align=\"center\" class=\"header\">{$fname->name}</td>";
                                $field[$i]=$fname->name;
                                $i++;
                                }
                           $ad_display.="</tr>";
                           while ($fname=mysqli_fetch_array($dbres))
                              {
                              $ad_display.="
                              <tr>";
                              for($i=0; $i<count($field);$i++)
                                  $ad_display.="<td class=\"lista\">".$fname[$field[$i]]."</td>";
                              $ad_display.="
                              </tr>";
                              }
                           $ad_display.="
                           </table>
                           </td>
                           </tr>
                           </table>
                           </form>";
                          }
                    else
                        // display num rows affected...
                        $ad_display.="
                           <table class=\"lista\" cellspacing=\"1\" cellpadding=\"0\" align=\"center\" border=\"0\" width=\"98%\">
                           <tr>
                           <td class=\"lista\">".
                           unesc($_POST["runquery"])
                           ."<tr>
                           <td class=\"header\">".
                            mysqli_info($GLOBALS["___mysqli_ston"])
                            ."
                           </td>
                           </tr>
                           </table>
                            ";
               }
         }
         else
            header("Location: admincp.php?user=".$CURUSER["uid"]."&code=".$CURUSER["random"]."&do=dbutil&action=qry");
        break;
    case 'qry':  // display tables
        $ad_display.="
        <form name=\"dbutil\" action=\"admincp.php?user=".$CURUSER["uid"]."&code=".$CURUSER["random"]."&do=dbutil&action=runqry\" method=\"post\">
        <table class=\"lista\" cellspacing=\"1\" cellpadding=\"0\" align=\"center\" border=\"0\" width=\"100%\">
        <tr>
        <td class=\"lista\" align=\"center\">
        Input a query and press confirm to run it on current datase.
        <textarea name=\"runquery\" rows=\"8\" cols=\"75\"></textarea>
        </td>
        </tr>
        <tr>
        <td align=\"center\" class=\"header\">
        <input type=\"submit\" name=\"confirm\" value=\"".FRM_CONFIRM."\" />&nbsp;&nbsp;&nbsp;
        <input type=\"submit\" name=\"confirm\" value=\"".FRM_CANCEL."\" /></td>
        </tr>
        </table>
        </form>";
        break;
    case 'tables':
        if (isset($_POST["doit"]) && isset($_POST["tname"]))
          {
            $table_action=$_POST["doit"];
            $tables=implode(",",$_POST["tname"]);
            switch ($table_action)
               {
                case 'Repair':
                    $dbres=run_query("REPAIR TABLE $tables");
                    break;
                case 'Analyze':
                    $dbres=run_query("ANALYZE TABLE $tables");
                    break;
                case 'Optimize':
                    $dbres=run_query("OPTIMIZE TABLE $tables");
                    break;
                case 'Check':
                    $dbres=run_query("CHECK TABLE $tables");
                    break;
                case 'Delete':
                    $dbres=run_query("DROP TABLE $tables");
                    header("Location: admincp.php?user=".$CURUSER["uid"]."&code=".$CURUSER["random"]."&do=dbutil&action=status");
                    exit();
                    break;
             }
             $ad_display.="
               <table class=\"lista\" cellspacing=\"1\" cellpadding=\"0\" align=\"center\" border=\"0\" width=\"100%\">
               <tr>
               <td align=\"center\" class=\"header\">Table</td>
               <td align=\"center\" class=\"header\">Operation</td>
               <td align=\"center\" class=\"header\">Info</td>
               <td align=\"center\" class=\"header\">Status</td>
               </tr>
             ";
             while ($tstatus=mysqli_fetch_array($dbres))
                  {
                     $ad_display.="
                     <tr>
                     <td class=\"lista\">{$tstatus['Table']}</td>
                     <td class=\"lista\" align=\"center\">{$tstatus['Op']}</td>
                     <td class=\"lista\" align=\"center\">{$tstatus['Msg_type']}</td>
                     <td class=\"lista\" align=\"right\">{$tstatus['Msg_text']}</td>
                     </tr>
                     ";
             }
             $ad_display.="
                </table>";
        }
         else
            header("Location: admincp.php?user=".$CURUSER["uid"]."&code=".$CURUSER["random"]."&do=dbutil&action=status");
        break;
    case 'status':
        $dbstatus=run_query("SHOW TABLE STATUS");
        if (mysqli_num_rows($dbstatus)>0)
            {
             $ad_display.="
               <script type=\"text/javascript\">
               <!--
               function SetAllCheckBoxes(FormName, FieldName, CheckValue)
               {
                    if(!document.forms[FormName])
                    return;
                    var objCheckBoxes = document.forms[FormName].elements[FieldName];
                    if(!objCheckBoxes)
                    return;
                    var countCheckBoxes = objCheckBoxes.length;
                    if(!countCheckBoxes)
                    objCheckBoxes.checked = CheckValue;
                    else
                    // set the check value for all check boxes
                    for(var i = 0; i < countCheckBoxes; i++)
                    objCheckBoxes[i].checked = CheckValue;
               }
               -->
               </script>
               <form name=\"dbutil\" action=\"admincp.php?user=".$CURUSER["uid"]."&code=".$CURUSER["random"]."&do=dbutil&action=tables\" method=\"post\">
               <table class=\"lista\" cellspacing=\"1\" cellpadding=\"0\" align=\"center\" border=\"0\" width=\"100%\">
               <tr>
               <td align=\"center\" class=\"header\">&nbsp;</td>
               <td align=\"center\" class=\"header\">Name</td>
               <td align=\"center\" class=\"header\">Records</td>
               <td align=\"center\" class=\"header\">Data Length</td>
               <td align=\"center\" class=\"header\">Overhead</td>
               </tr>
               ";
               $tables=0;
               $bytes=0;
               $records=0;
               $overhead=0;
            // display current status for tables
                while ($tstatus=mysqli_fetch_array($dbstatus))
                    {
                     $ad_display.="
                     <tr>
                     <td class=\"lista\" align=\"center\"><input type=\"checkbox\" name=\"tname[]\" value=\"{$tstatus['Name']}\" /></td>
                     <td class=\"lista\">{$tstatus['Name']}</td>
                     <td class=\"lista\" align=\"right\">{$tstatus['Rows']}</td>
                     <td class=\"lista\" align=\"right\">".makesize($tstatus['Data_length']+$tstatus['Index_length'])."</td>
                     <td class=\"lista\" align=\"right\">".($tstatus['Data_free']==0?"-":makesize($tstatus['Data_free']))."</td>
                     </tr>
                     ";
                    $tables++;
                    $bytes+=$tstatus['Data_length']+$tstatus['Index_length'];
                    $records+=$tstatus['Rows'];
                    $overhead+=$tstatus['Data_free'];
                    }
                $ad_display.="
                <tr>
                <td align=\"center\" class=\"lista\"><input type=\"checkbox\" name=\"all\" onclick=\"SetAllCheckBoxes('dbutil','tname[]',this.checked)\" /></td>
                <td align=\"center\" class=\"lista\">$tables table(s)</td>
                <td align=\"right\" class=\"lista\">$records</td>
                <td align=\"right\" class=\"lista\">".makesize($bytes)."</td>
                <td align=\"right\" class=\"lista\">".makesize($overhead)."</td>
                </tr>
                <tr>
                <td colspan=\"5\">
                &nbsp;&nbsp;If checked:&nbsp;&nbsp;
                <input type=\"submit\" name=\"doit\" value=\"Repair\" />&nbsp;&nbsp;
                <input type=\"submit\" name=\"doit\" value=\"Optimize\" />&nbsp;&nbsp;
                <input type=\"submit\" name=\"doit\" value=\"Analyze\" />&nbsp;&nbsp;
                <input type=\"submit\" name=\"doit\" value=\"Check\" />&nbsp;&nbsp;
                <input type=\"submit\" name=\"doit\" value=\"Delete\" onclick=\"return confirm('Warning, this will delete selected tables!')\" />
                </td>
                </tr>
                </table>
                ";
               unset($tables);
               unset($bytes);
               unset($records);
               unset($overhead);
            }
        break;
    default:

        print("
		<table class=\"lista\" cellspacing=\"1\" cellpadding=\"2\" align=\"center\" border=\"0\" width=\"100%\">
        <tr>
        <td class=\"header\" align=\"center\" width=\"50%\" colspan=\"3\">
        <input type=\"button\" name=\"query\" value=\"Query\" onclick=\"window.location.href='admincp.php?user=".$CURUSER["uid"]."&code=".$CURUSER["random"]."&do=dbutil&action=qry'\" />
        </td>
        <td class=\"header\" align=\"center\" width=\"50%\" colspan=\"2\">
        <input type=\"button\" name=\"status\" value=\"DB Status\" onclick=\"window.location.href='admincp.php?user=".$CURUSER["uid"]."&code=".$CURUSER["random"]."&do=dbutil&action=status'\" />
        </td>
        </tr>
        </table>
        ");
		// thanks to tdbdev.net and CoLdFuSiOn for the mysql stats code
		include(dirname(__FILE__)."/mysql_stats.php");
		print("<div align=\"center\">The code for mysql server status is provided by CoLdFuSiOn (Tbdev.net)</div>");
        break;
}

echo $ad_display;
block_end();

?>