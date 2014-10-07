<?php
# IMPORTANT: Do not edit below unless you know what you are doing!
if(!defined('IN_INSTALL'))
  die('Hacking attempt!');
require_once('../include/config.php');
/*/ BigDump ver. 0.24b from 2006-06-25/*/
$filename        = '../sql/database.sql';     // Specify the dump filename to suppress the file selection dialog
$linespersession = 3000;   // Lines to be executed per one import session
$delaypersession = 0;      // You can specify a sleep time in milliseconds after each session
                           // Works only if JavaScript is activated. Use to reduce server overrun
// Allowed comment delimiters: lines starting with these strings will be dropped by BigDump
$comment[]='#';           // Standard comment lines are dropped by default
$comment[]='-- ';
// $comment[]='---';      // Uncomment this line if using proprietary dump created by outdated mysqldump
// $comment[]='/*!';         // Or add your own string to leave out other proprietary things
// Connection character set should be the same as the dump file character set (utf8, latin1, cp1251, koi8r etc.)
// See http://dev.mysql.com/doc/refman/5.0/en/charset-charsets.html for the full list
$db_connection_char_set = '';
// *******************************************************************************************
// If not familiar with PHP please don't change anything below this line
// *******************************************************************************************
$copyright = '<center><p><br><Br><br>© 2003-2006 <a href="mailto:alexey@ozerov.de">Alexey Ozerov</a> - <a href="http://www.ozerov.de/bigdump" target="_blank">BigDump Home</a></p>';
$error = false;
$file  = false;
// Check PHP version
if (!$error && !function_exists('version_compare'))
{ echo ("<p class=\"error\">PHP version 4.1.0 is required for BigDump to proceed. You have PHP ".phpversion()." installed. Sorry!</p>\n");
  $error=true;
}
// Calculate PHP max upload size (handle settings like 10M or 100K)
if (!$error)
{ $upload_max_filesize=ini_get("upload_max_filesize");
  if (eregi("([0-9]+)K",$upload_max_filesize,$tempregs)) $upload_max_filesize=$tempregs[1]*1024;
  if (eregi("([0-9]+)M",$upload_max_filesize,$tempregs)) $upload_max_filesize=$tempregs[1]*1024*1024;
  if (eregi("([0-9]+)G",$upload_max_filesize,$tempregs)) $upload_max_filesize=$tempregs[1]*1024*1024*1024;
}
// Handle file upload
$upload_dir=dirname(isset($_SERVER["PATH_TRANSLATED"])?$_SERVER["PATH_TRANSLATED"]:$_SERVER["SCRIPT_FILENAME"]);
if (!$error && isset($_REQUEST["uploadbutton"]))
{ if (is_uploaded_file($_FILES["dumpfile"]["tmp_name"]) && ($_FILES["dumpfile"]["error"])==0)
  { 
    $uploaded_filename=str_replace(" ","_",$_FILES["dumpfile"]["name"]);
    $uploaded_filepath=str_replace("\\","/",$upload_dir."/".$uploaded_filename);
        
    if (file_exists($uploaded_filename))
    { echo ("<p class=\"error\">File $uploaded_filename already exist! Delete and upload again!</p>\n");
    }
    else if (eregi("(\.php|\.php3|\.php4|\.php5)$",$uploaded_filename))
    { echo ("<p class=\"error\">You may not upload this type of files.</p>\n");
    }
    else if (!@move_uploaded_file($_FILES["dumpfile"]["tmp_name"],$uploaded_filepath))
    { echo ("<p class=\"error\">Error moving uploaded file ".$_FILES["dumpfile"]["tmp_name"]." to the $uploaded_filepath</p>\n");
      echo ("<p>Check the directory permissions for $upload_dir (must be 777)!</p>\n");
    }
    else
    { echo ("<p class=\"success\">Uploaded file saved as $uploaded_filename</p>\n");
    }
  }
  else
  { echo ("<p class=\"error\">Error uploading file ".$_FILES["dumpfile"]["name"]."</p>\n");
  }
}
// Handle file deletion (delete only in the current directory for security reasons)
if (!$error && isset($_REQUEST["delete"]) && $_REQUEST["delete"]!=basename($_SERVER["SCRIPT_FILENAME"]))
{ if (@unlink(basename($_REQUEST["delete"])))
    echo ("<p class=\"success\">".$_REQUEST["delete"]." was removed successfully</p>\n");
  else
    echo ("<p class=\"error\">Can't remove ".$_REQUEST["delete"]."</p>\n");
}
// Connect to the database
if (!$error)
{ $dbconnection = @mysql_connect($dbhost,$dbuser,$dbpass); 
  if ($dbconnection) 
    $db = mysql_select_db($database);
  if (!$dbconnection || !$db) 
  { echo ("<p class=\"error\">Database connection failed due to ".mysql_error()."</p>\n");
    echo ("<p>Edit the database settings in ".$_SERVER["SCRIPT_FILENAME"]." or contact your database provider</p>\n");
    $error=true;
  }
  if (!$error && $db_connection_char_set!=='')
    @mysql_query("SET NAMES $db_connection_char_set", $dbconnection);
}
// List uploaded files in multifile mode
if (!$error && !isset($_REQUEST["fn"]) && $filename=="")
{ if ($dirhandle = opendir($upload_dir)) 
  { $dirhead=false;
    while (false !== ($dirfile = readdir($dirhandle)))
    { if ($dirfile != "." && $dirfile != ".." && $dirfile!=basename($_SERVER["SCRIPT_FILENAME"]))
      { if (!$dirhead)
        { echo ("<table cellspacing=\"2\" cellpadding=\"2\">\n");
          echo ("<tr><th>Filename</th><th>Size</th><th>Date&Time</th><th>Type</th><th>&nbsp;</th><th>&nbsp;</th>\n");
          $dirhead=true;
        }
        echo ("<tr><td>$dirfile</td><td class=\"right\">".filesize($dirfile)."</td><td>".date ("Y-m-d H:i:s", filemtime($dirfile))."</td>");
        if (eregi("\.gz$",$dirfile)) 
          echo ("<td>GZip</td>");
        else 
          echo ("<td>SQL</td>");
        if (!eregi("\.gz$",$dirfile) || function_exists("gzopen")) 
          echo ("<td><a href=\"".$_SERVER["PHP_SELF"]."?action=step3&start=1&fn=$dirfile&foffset=0&totalqueries=0\">Start Import</a> into $database at $dbhost</td>\n");
        else
          echo ("<td>&nbsp;</td>\n");
        echo ("<td><a href=\"".$_SERVER["PHP_SELF"]."?action=step3&delete=$dirfile\">Delete file</a></td></tr>\n");
      } 

    }
    if ($dirhead) echo ("</table>\n");
    else echo ("<p>No uploaded files found in the working directory</p>\n");
    closedir($dirhandle); 
  }
  else
  { echo ("<p class=\"error\">Error listing directory $upload_dir</p>\n");
    $error=true;
  }
}
// Single file mode
if (!$error && !isset ($_REQUEST["fn"]) && $filename!="")
{   echo ("<p class=error>Please click on Next for start import as shown below and wait for import.</P>");
    echo ("<p>Start Import from $filename into $database at $dbhost<br />\n<br />\n<input type=\"button\" name=\"continue\" value=\"Next >>\" onclick=\"javascript:document.location.href='".$_SERVER["PHP_SELF"]."?action=step3&start=1&fn=$filename&foffset=0&totalqueries=0'\" /></p>\n");
}
// File Upload Form
if (!$error && !isset($_REQUEST["fn"]) && $filename=="")
{ 
// Test permissions on working directory
  do { $tempfilename=time().".tmp"; } while (file_exists($tempfilename));
  if (!($tempfile=@fopen($tempfilename,"w")))
  { echo ("<p>Upload form disabled. Permissions for the working directory <i>$upload_dir</i> <b>must be set to 777</b> in order ");
    echo ("to upload files from here. Alternatively you can upload your dump files via FTP.</p>\n");
  }
  else
  { fclose($tempfile);
    unlink ($tempfilename);
 
    echo ("<p>You can now upload your dump file up to $upload_max_filesize bytes (".round ($upload_max_filesize/1024/1024)." Mbytes)  ");
    echo ("directly from your browser to the server. Alternatively you can upload your dump files of any size via FTP.</p>\n");
?>
<form method="POST" action="<?php echo ($_SERVER["PHP_SELF"]); ?>?action=step3" enctype="multipart/form-data">
<input type="hidden" name="MAX_FILE_SIZE" value="$upload_max_filesize">
<p>Dump file: <input type="file" name="dumpfile" accept="*/*" size=60"></p>
<p><input type="submit" name="uploadbutton" value="Upload"></p>
</form>
<?php
  }
}

// Print the current mySQL connection charset

if (!$error && !isset($_REQUEST["fn"]) && $filename=="")
{ 
  $result = mysql_query("SHOW VARIABLES LIKE 'character_set_connection';");
  $row = mysql_fetch_assoc($result);
  if ($row) 
  { $charset = $row['Value'];
    echo ("<p>Note: The current mySQL connection charset is <i>$charset</i>. Your dump file must be encoded in <i>$charset</i> in order to avoid problems with non-latin characters. You can change the connection charset using the \$db_connection_char_set variable in bigdump.php</p>\n");
  }
}

// Open the file

if (!$error && isset($_REQUEST["fn"]))
{ 

// Recognize GZip filename

  if (eregi("\.gz$",$_REQUEST["fn"])) 
    $gzipmode=true;
  else
    $gzipmode=false;

  if ((!$gzipmode && !$file=fopen($_REQUEST["fn"],"rt")) || ($gzipmode && !$file=gzopen($_REQUEST["fn"],"rt")))
  { echo ("<p class=\"error\">Can't open ".$_REQUEST["fn"]." for import</p>\n");
    echo ("<p>You have to upload the ".$_REQUEST["fn"]." to the server</p>\n");
    $error=true;
  }

// Get the file size (can't do it fast on gzipped files, no idea how)

  else if ((!$gzipmode && fseek($file, 0, SEEK_END)==0) || ($gzipmode && gzseek($file, 0)==0))
  { if (!$gzipmode) $filesize = ftell($file);
    else $filesize = gztell($file);                   // Always zero, ignore
  }
  else
  { echo ("<p class=\"error\">I can't get the filesize of ".$_REQUEST["fn"]."</p>\n");
    $error=true;
  }
}


// ****************************************************
// START IMPORT SESSION HERE
// ****************************************************

if (!$error && isset($_REQUEST["start"]) && isset($_REQUEST["foffset"]))
{

// Check start and foffset are numeric values

  if (!is_numeric($_REQUEST["start"]) || !is_numeric($_REQUEST["foffset"]))
  { echo ("<p class=\"error\">UNEXPECTED: Non-Numeric values for start and foffset</p>\n");
    $error=true;
  }

  if (!$error)
  { $_REQUEST["start"]   = floor($_REQUEST["start"]);
    $_REQUEST["foffset"] = floor($_REQUEST["foffset"]);
    echo ("<p>Processing file: ".$_REQUEST["fn"]."</p>\n");
    echo ("<p>Starting at the line: ".$_REQUEST["start"]."</p>\n");
  }

// Check $_REQUEST["foffset"] upon $filesize (can't do it on gzipped files)

  if (!$error && !$gzipmode && $_REQUEST["foffset"]>$filesize)
  { echo ("<p class=\"error\">UNEXPECTED: Can't set file pointer behind the end of file</p>\n");
    $error=true;
  }

// Set file pointer to $_REQUEST["foffset"]

  if (!$error && ((!$gzipmode && fseek($file, $_REQUEST["foffset"])!=0) || ($gzipmode && gzseek($file, $_REQUEST["foffset"])!=0)))
  { echo ("<p class=\"error\">UNEXPECTED: Can't set file pointer to offset: ".$_REQUEST["foffset"]."</p>\n");
    $error=true;
  }

// Start processing queries from $file

  if (!$error)
  { $query="";
    $queries=0;
    $totalqueries=$_REQUEST["totalqueries"];
    $linenumber=$_REQUEST["start"];
    $querylines=0;
    $inparents=false;

// Stay processing as long as the $linespersession is not reached or the query is still incomplete

    while ($linenumber<$_REQUEST["start"]+$linespersession || $query!="")
    { 

// Read the whole next line

      $dumpline = "";
      while (!feof($file) && substr ($dumpline, -1) != "\n") 
      { if (!$gzipmode)
          $dumpline .= fgets($file, DATA_CHUNK_LENGTH);
        else
          $dumpline .= gzgets($file, DATA_CHUNK_LENGTH);
      }
      if ($dumpline==="") break;
      
// Handle DOS and Mac encoded linebreaks (I don't know if it will work on Win32 or Mac Servers)

      $dumpline=ereg_replace("\r\n$", "\n", $dumpline);
      $dumpline=ereg_replace("\r$", "\n", $dumpline);
      
// DIAGNOSTIC
// echo ("<p>Line $linenumber: $dumpline</p>\n");

// Skip comments and blank lines only if NOT in parents

      if (!$inparents)
      { $skipline=false;
        reset($comment);
        foreach ($comment as $comment_value)
        { if (!$inparents && (trim($dumpline)=="" || strpos ($dumpline, $comment_value) === 0))
          { $skipline=true;
            break;
          }
        }
        if ($skipline)
        { $linenumber++;
          continue;
        }
      }

// Remove double back-slashes from the dumpline prior to count the quotes ('\\' can only be within strings)
      
      $dumpline_deslashed = str_replace ("\\\\","",$dumpline);

// Count ' and \' in the dumpline to avoid query break within a text field ending by ;
// Please don't use double quotes ('"')to surround strings, it wont work

      $parents=substr_count ($dumpline_deslashed, "'")-substr_count ($dumpline_deslashed, "\\'");
      if ($parents % 2 != 0)
        $inparents=!$inparents;

// Add the line to query

      $query .= $dumpline;

// Don't count the line if in parents (text fields may include unlimited linebreaks)
      
      if (!$inparents)
        $querylines++;
      
// Stop if query contains more lines as defined by MAX_QUERY_LINES

      if ($querylines>MAX_QUERY_LINES)
      {
        echo ("<p class=\"error\">Stopped at the line $linenumber. </p>");
        echo ("<p>At this place the current query includes more than ".MAX_QUERY_LINES." dump lines. That can happen if your dump file was ");
        echo ("created by some tool which doesn't place a semicolon followed by a linebreak at the end of each query, or if your dump contains ");
        echo ("extended inserts. Please read the BigDump FAQs for more infos.</p>\n");
        $error=true;
        break;
      }

// Execute query if end of query detected (; as last character) AND NOT in parents

      if (ereg(";$",trim($dumpline)) && !$inparents)
      { if (!mysql_query(trim($query), $dbconnection))
        { echo ("<p class=\"error\">Error at the line $linenumber: ". trim($dumpline)."</p>\n");
          echo ("<p>Query: ".trim($query)."</p>\n");
          echo ("<p>MySQL: ".mysql_error()."</p>\n");
          $error=true;
          break;
        }
        $totalqueries++;
        $queries++;
        $query="";
        $querylines=0;
      }
      $linenumber++;
    }
  }

// Get the current file position

  if (!$error)
  { if (!$gzipmode) 
      $foffset = ftell($file);
    else
      $foffset = gztell($file);
    if (!$foffset)
    { echo ("<p class=\"error\">UNEXPECTED: Can't read the file pointer offset</p>\n");
      $error=true;
    }
  }

// Finish message and restart the script

  if (!$error)
  { echo ("<p>Stopping at the line: ".($linenumber-1)."</p>\n");
    echo ("<p>Queries performed (this session/total): $queries/$totalqueries</p>\n");
    echo ("<p>Total bytes processed: $foffset (".round($foffset/1024)." KB)");
    if ($filesize>0)
      echo (" or ".round($foffset/$filesize*100)."%");
    echo ("</p>\n");
    if ($linenumber<$_REQUEST["start"]+$linespersession)
    { echo ("<br><p class=\"success\">Congratulations: End of file reached, assuming OK</p><br>\n");
        Print("<p class=\"error\">Please click next!</p><br>");
        print("<div align=\"center\"><input type=\"button\" class=\"button\" name=\"continue\" value=\"Next >>\" onclick=\"javascript:document.location.href='index.php?action=step4'\" /></div>");
        echo $copyright;
      $error=false;
    }
    else
    { if ($delaypersession!=0)
        echo ("<p>Now I'm <b>waiting $delaypersession milliseconds</b> before starting next session...</p>\n");
      echo ("<script language=\"JavaScript\" type=\"text/javascript\">window.setTimeout('location.href=\"".$_SERVER["PHP_SELF"]."?action=step3&start=$linenumber&fn=".$_REQUEST["fn"]."&foffset=$foffset&totalqueries=$totalqueries\";',500+$delaypersession);</script>\n");
      echo ("<noscript>\n");
      echo ("<p><a href=\"".$_SERVER["PHP_SELF"]."?action=step3&start=$linenumber&fn=".$_REQUEST["fn"]."&foffset=$foffset&totalqueries=$totalqueries\">Continue from the line $linenumber</a> (Enable JavaScript to do it automatically)</p>\n");
      echo ("</noscript>\n");
      echo ("<p>Press <a href=\"".$_SERVER["PHP_SELF"]."?action=step3\">STOP</a> to abort the import <b>OR WAIT!</b></p>\n");
    }
  }
  else 
    echo ("<p class=\"error\">Stopped on error</p>\n");
}

if ($error)
  echo ("<p><input type=\"button\" class=\"button\" name=\"continue\" value=\"Start from the beginning\" onclick=\"javascript:document.location.href='".$_SERVER["PHP_SELF"]."?action=step3'\"> (DROP the old tables before restarting)</p>\n");

if ($dbconnection) mysql_close();
if ($file && !$gzipmode) fclose($file);
else if ($file && $gzipmode) gzclose($file);
?>
</td></tr></table>
</body>
</html>
<?php
// end of file!
?>