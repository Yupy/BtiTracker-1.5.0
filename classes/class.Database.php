<?php 

    class QuickDB 
    { 
        public $con             = null;        // for db connection 
        public $dbselect        = null;        // for db selection 
        private $result         = null;        // for mysql result resource id 
        private $row             = null;        // for fetched row 
        private $rows             = null;        // for number of rows fetched 
        private $affected         = null;        // for number of rows affected 
        private $insert_id         = null;        // for last inserted id 
        private $query             = null;        // for the last run query 
        private $show_errors     = null;        // for knowing whether to display errors 
        private $emsg             = null;        // for mysql error description 
        private $eno             = null;        // for mysql error number 
         
         
        // Intialize the class with connection to db 
        public function __construct($dbhost, $dbuser, $dbpass, $database, $persistent = false, $show_errors = true) 
        { 
            if ($show_errors == true) 
            { 
                $this->show_errors = true; 
            } 
             
            if ($persistent == true) 
            { 
                $this->con = @($GLOBALS["___mysqli_ston"] = mysqli_connect($dbhost,  $dbuser,  $dbpass)); 
            } 
            else 
            { 
                $this->con = @($GLOBALS["___mysqli_ston"] = mysqli_connect($dbhost,  $dbuser,  $dbpass)); 
            } 
             
            if ($this->con) 
            { 
                $this->dbselect = $result = ((bool)mysqli_query( $this->con, "USE $db")); 
                mysqli_query($GLOBALS["___mysqli_ston"], "SET NAMES utf8"); 
                mysqli_query($GLOBALS["___mysqli_ston"], "SET CHARACTER SET utf8"); 
                mysqli_query($GLOBALS["___mysqli_ston"], "SET COLLATION_CONNECTION = 'utf8_general_ci'"); 
                return $result; 
            } 
            else 
            { 

            } 
        } 
         
        // Close the connection to database 
        public function __destruct() 
        { 
            $this->close(); 
        } 

        // Close the connection to database 
        public function close() 
        { 
            $result = @((is_null($___mysqli_res = mysqli_close($this->con))) ? false : $___mysqli_res); 
            return $result; 
        } 
     
        // stores mysql errors 
        private function setError($msg, $no) 
        { 
            $this->emsg = $msg; 
            $this->eno = $no; 
             
            if ($this->show_errors == true) 
            { 
                print '<div style="margin-top:5px;margin-bottom:5px;background:#f6f6f6; padding:5px; font-size:13px; font-family:verdana; border:1px solid #cccccc;"> 
                        <span style="color:#ff0000;">MySQL Error Number</span> : ' . $no . '<br /> 
                        <span style="color:#ff0000;">MySQL Error Message</span> : ' . $msg . '</div>'; 
            } 
        } 
         
     
        ################################################# 
        #                General Functions                # 
        ################################################# 
     
        // Runs the SQL query (general execute query function) 
        public function execute($command) 
        { 
            # Params: 
            #         $command = query command 
             
            if (!$command) 
            { 
                exit("No Query Command Specified !!"); 
            } 
             
            $this->query = $command; 
             
            // For Operational query 
            if     ( 
                (stripos($command, "insert ") !== false) || 
                (stripos($command, "update ") !== false) || 
                (stripos($command, "delete ") !== false) || 
                (stripos($command, "replace ") !== false) 
                ) 
            { 
                $this->result = mysqli_query($GLOBALS["___mysqli_ston"], $command) or $this->setError(((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)), ((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_errno($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_errno()) ? $___mysqli_res : false))); 

                if (stripos($command, "insert ") !== false) 
                { 
                    if ($this->result) 
                    { 
                        $this->insert_id = intval(((is_null($___mysqli_res = mysqli_insert_id($GLOBALS["___mysqli_ston"]))) ? false : $___mysqli_res)); 
                    } 
                } 

                if ($this->result) 
                { 
                    $this->affected = intval(mysqli_affected_rows($GLOBALS["___mysqli_ston"])); 
                    // return the number of rows affected 
                    return $this->result; 
                } 
            } 
            else 
            { 
                // For Selection query 
                $this->result = mysqli_query($GLOBALS["___mysqli_ston"], $command) or $this->setError(((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)), ((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_errno($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_errno()) ? $___mysqli_res : false))); 
                if ($this->result) 
                { 
                    $this->rows = @intval(mysqli_num_rows($this->result)); 
                    // return the query resource for later processing 
                    return $this->result; 
                } 
            } 
        }     

        // Gets records from table 
        public function select($table, $rows = "*", $condition = null, $order = null) 
        { 
            # Params: 
            #         $table = the name of the table 
            #        $rows = rows to be selected 
            #         $condition = example: where id = 99 
            #        $order = ordering field name 

            if (!$table) 
            { 
                exit("No Table Specified !!"); 
            } 
             
            $sql = "select $rows from $table"; 

            if($condition) 
            { 
                $sql .= ' where ' . $condition; 
            } 
            else if($order) 
            { 
                $sql .= ' order by ' . $order; 
            } 

            $this->query = $sql; 
            $this->result = mysqli_query($GLOBALS["___mysqli_ston"], $sql) or $this->setError(((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)), ((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_errno($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_errno()) ? $___mysqli_res : false))); 

            if ($this->result) 
            { 
                $this->rows = intval(mysqli_num_rows($this->result)); 
                // return the query resource for later processing 
                return $this->result; 
            } 
        }     


        // Inserts records 
        public function insert($table, $data) 
        { 
            # Params: 
            #         $table = the name of the table 
            #         $data = field/value pairs to be inserted 
             
            if ($table) 
            { 
                if ($data) 
                { 
                    $this->result = mysqli_query($GLOBALS["___mysqli_ston"], "insert into $table set $data") or $this->setError(((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)), ((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_errno($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_errno()) ? $___mysqli_res : false))); 
                    $this->query = "insert into $table set $data"; 

                    if ($this->result) 
                    { 
                        $this->affected = intval(mysqli_affected_rows($GLOBALS["___mysqli_ston"])); 
                        $this->insert_id = intval(((is_null($___mysqli_res = mysqli_insert_id($GLOBALS["___mysqli_ston"]))) ? false : $___mysqli_res)); 
                        // return the number of rows affected 
                        return $this->affected; 
                    } 
                } 
                else 
                { 
                    print "No Data Specified !!"; 
                } 
            } 
            else 
            { 
                print "No Table Specified !!"; 
            } 
        } 

        // Updates records 
        public function update($table, $data, $condition) 
        { 
            # Params: 
            #         $table = the name of the table 
            #         $data = field/value pairs to be updated 
            #         $condition = example: where id = 99 

            if ($table) 
            { 
                if ($data) 
                { 
                    if ($condition) 
                    { 
                        $this->result = mysqli_query($GLOBALS["___mysqli_ston"], "update $table set $data where $condition") or $this->setError(((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)), ((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_errno($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_errno()) ? $___mysqli_res : false))); 
                        $this->query = "update $table set $data where $condition"; 

                        if ($this->result) 
                        { 
                            $this->affected = intval(mysqli_affected_rows($GLOBALS["___mysqli_ston"])); 
                            // return the number of rows affected 
                            return $this->affected; 
                        } 
                    } 
                    else 
                    { 
                        print "No Condition Specified !!"; 
                    } 
                } 
                else 
                { 
                    print "No Data Specified !!"; 
                } 
            } 
            else 
            { 
                print "No Table Specified !!"; 
            } 
        } 

        // Deletes records 
        public function delete($table, $condition) 
        { 
            # Params: 
            #         $table = the name of the table 
            #         $condition = example: where id = 99 

            if ($table) 
            { 
                if ($condition) 
                { 
                    $this->result = mysqli_query($GLOBALS["___mysqli_ston"], "delete from $table where $condition") or $this->setError(((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)), ((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_errno($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_errno()) ? $___mysqli_res : false))); 
                    $this->query = "delete from $table where $condition"; 

                    if ($this->result) 
                    { 
                        $this->affected = intval(mysqli_affected_rows($GLOBALS["___mysqli_ston"])); 
                        // return the number of rows affected 
                        return $this->affected; 
                    } 
                } 
                else 
                { 
                    print "No Condition Specified !!"; 
                } 
            } 
            else 
            { 
                print "No Table Specified !!"; 
            } 
        } 

        // returns table data in array 
        public function load_array() 
        { 
            $arr = array(); 
             
            while ($row = mysqli_fetch_object($this->result)) 
            { 
                $arr[] = $row; 
            } 

            return $arr; 
        } 


        // print a complete table from the specified table 
        public function get_html($command, $display_field_headers = true, $table_attribs = 'border="0" cellpadding="3" cellspacing="2" style="padding-bottom:5px; border:1px solid #cccccc; font-size:13px; font-family:verdana;"') 
        { 
            if (!$command) 
            { 
                exit("No Query Command Specified !!"); 
            } 

            $this->query = $command; 
            $this->result = mysqli_query($GLOBALS["___mysqli_ston"], $command) or $this->setError(((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)), ((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_errno($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_errno()) ? $___mysqli_res : false))); 
             
            if ($this->result) 
            { 
                $this->rows = intval(mysqli_num_rows($this->result)); 
                 
                $num_fields = (($___mysqli_tmp = mysqli_num_fields($this->result)) ? $___mysqli_tmp : false); 

                print '<br /><br /><div> 
                        <table ' . $table_attribs . '>' 
                        . "\n" . '<tr>'; 

                if ($display_field_headers == true) 
                { 
                    // printing table headers 
                    for($i = 0; $i < $num_fields; $i++) 
                    { 
                        $field = (((($___mysqli_tmp = mysqli_fetch_field_direct($this->result, mysqli_field_tell($this->result))) && is_object($___mysqli_tmp)) ? ( (!is_null($___mysqli_tmp->primary_key = ($___mysqli_tmp->flags & MYSQLI_PRI_KEY_FLAG) ? 1 : 0)) && (!is_null($___mysqli_tmp->multiple_key = ($___mysqli_tmp->flags & MYSQLI_MULTIPLE_KEY_FLAG) ? 1 : 0)) && (!is_null($___mysqli_tmp->unique_key = ($___mysqli_tmp->flags & MYSQLI_UNIQUE_KEY_FLAG) ? 1 : 0)) && (!is_null($___mysqli_tmp->numeric = (int)(($___mysqli_tmp->type <= MYSQLI_TYPE_INT24) || ($___mysqli_tmp->type == MYSQLI_TYPE_YEAR) || ((defined("MYSQLI_TYPE_NEWDECIMAL")) ? ($___mysqli_tmp->type == MYSQLI_TYPE_NEWDECIMAL) : 0)))) && (!is_null($___mysqli_tmp->blob = (int)in_array($___mysqli_tmp->type, array(MYSQLI_TYPE_TINY_BLOB, MYSQLI_TYPE_BLOB, MYSQLI_TYPE_MEDIUM_BLOB, MYSQLI_TYPE_LONG_BLOB)))) && (!is_null($___mysqli_tmp->unsigned = ($___mysqli_tmp->flags & MYSQLI_UNSIGNED_FLAG) ? 1 : 0)) && (!is_null($___mysqli_tmp->zerofill = ($___mysqli_tmp->flags & MYSQLI_ZEROFILL_FLAG) ? 1 : 0)) && (!is_null($___mysqli_type = $___mysqli_tmp->type)) && (!is_null($___mysqli_tmp->type = (($___mysqli_type == MYSQLI_TYPE_STRING) || ($___mysqli_type == MYSQLI_TYPE_VAR_STRING)) ? "type" : "")) &&(!is_null($___mysqli_tmp->type = ("" == $___mysqli_tmp->type && in_array($___mysqli_type, array(MYSQLI_TYPE_TINY, MYSQLI_TYPE_SHORT, MYSQLI_TYPE_LONG, MYSQLI_TYPE_LONGLONG, MYSQLI_TYPE_INT24))) ? "int" : $___mysqli_tmp->type)) &&(!is_null($___mysqli_tmp->type = ("" == $___mysqli_tmp->type && in_array($___mysqli_type, array(MYSQLI_TYPE_FLOAT, MYSQLI_TYPE_DOUBLE, MYSQLI_TYPE_DECIMAL, ((defined("MYSQLI_TYPE_NEWDECIMAL")) ? constant("MYSQLI_TYPE_NEWDECIMAL") : -1)))) ? "real" : $___mysqli_tmp->type)) && (!is_null($___mysqli_tmp->type = ("" == $___mysqli_tmp->type && $___mysqli_type == MYSQLI_TYPE_TIMESTAMP) ? "timestamp" : $___mysqli_tmp->type)) && (!is_null($___mysqli_tmp->type = ("" == $___mysqli_tmp->type && $___mysqli_type == MYSQLI_TYPE_YEAR) ? "year" : $___mysqli_tmp->type)) && (!is_null($___mysqli_tmp->type = ("" == $___mysqli_tmp->type && (($___mysqli_type == MYSQLI_TYPE_DATE) || ($___mysqli_type == MYSQLI_TYPE_NEWDATE))) ? "date " : $___mysqli_tmp->type)) && (!is_null($___mysqli_tmp->type = ("" == $___mysqli_tmp->type && $___mysqli_type == MYSQLI_TYPE_TIME) ? "time" : $___mysqli_tmp->type)) && (!is_null($___mysqli_tmp->type = ("" == $___mysqli_tmp->type && $___mysqli_type == MYSQLI_TYPE_SET) ? "set" : $___mysqli_tmp->type)) &&(!is_null($___mysqli_tmp->type = ("" == $___mysqli_tmp->type && $___mysqli_type == MYSQLI_TYPE_ENUM) ? "enum" : $___mysqli_tmp->type)) && (!is_null($___mysqli_tmp->type = ("" == $___mysqli_tmp->type && $___mysqli_type == MYSQLI_TYPE_GEOMETRY) ? "geometry" : $___mysqli_tmp->type)) && (!is_null($___mysqli_tmp->type = ("" == $___mysqli_tmp->type && $___mysqli_type == MYSQLI_TYPE_DATETIME) ? "datetime" : $___mysqli_tmp->type)) && (!is_null($___mysqli_tmp->type = ("" == $___mysqli_tmp->type && (in_array($___mysqli_type, array(MYSQLI_TYPE_TINY_BLOB, MYSQLI_TYPE_BLOB, MYSQLI_TYPE_MEDIUM_BLOB, MYSQLI_TYPE_LONG_BLOB)))) ? "blob" : $___mysqli_tmp->type)) && (!is_null($___mysqli_tmp->type = ("" == $___mysqli_tmp->type && $___mysqli_type == MYSQLI_TYPE_NULL) ? "null" : $___mysqli_tmp->type)) && (!is_null($___mysqli_tmp->type = ("" == $___mysqli_tmp->type) ? "unknown" : $___mysqli_tmp->type)) && (!is_null($___mysqli_tmp->not_null = ($___mysqli_tmp->flags & MYSQLI_NOT_NULL_FLAG) ? 1 : 0)) ) : false ) ? $___mysqli_tmp : false); 
                        print "<td bgcolor='#f6f6f6' style=' border:1px solid #cccccc;'><strong style='color:#666666;'>" . ucwords($field->name) . "</strong></td>\n"; 
                    } 
                    print "</tr>\n"; 
                } 
                 
                // printing table rows 
                while($row = mysqli_fetch_row($this->result)) 
                { 
                    print "<tr>"; 
                 
                    foreach($row as $td) 
                    { 
                        print "<td bgcolor='#f6f6f6'>$td</td>\n"; 
                    } 
                 
                    print "</tr>\n"; 
                } 
                print "</table></div><br /><br />"; 
            } 
        } 
         
         
        public function last_insert_id() 
        { 
            if ($this->insert_id) 
            { 
                return $this->insert_id; 
            } 
        } 
         
        // Counts all records from a table 
        public function count_all($table) 
        { 
            if (!$table) 
            { 
                exit("No Table Specified !!"); 
            } 
             
            $this->result = mysqli_query($GLOBALS["___mysqli_ston"], "select count(*) as total from $table") or $this->setError(((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)), ((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_errno($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_errno()) ? $___mysqli_res : false))); 
            $this->query = "select count(*) as total from $table"; 

            if ($this->result) 
            { 
                $this->row = mysqli_fetch_array($this->result); 
                return intval($this->row["total"]); 
            } 
        } 
         
        // Counts records based on specified criteria 
        public function count_rows($command) 
        { 
            # Params: 
            #         $command = query command 

            if (!$command) 
            { 
                exit("No Query Command Specified !!"); 
            } 
         
            $this->query = $command; 
            $this->result = mysqli_query($GLOBALS["___mysqli_ston"], $command) or $this->setError(((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)), ((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_errno($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_errno()) ? $___mysqli_res : false))); 

            if ($this->result) 
            { 
                return intval(mysqli_num_rows($this->result)); 
            } 
        } 

        // Updates a row if it exists or adds if it doesn't already exist. 
        public function insert_update($table, $data, $condition) 
        { 
            # Params: 
            #         $table = the name of the table 
            #         $data = field/value pairs to be added/updated 
            #         $condition = example: where id = 99 

            if ($table) 
            { 
                if ($data) 
                { 
                    if ($condition) 
                    { 
                        if ($this->row_exists("select * from $table where $condition")) 
                        { 
                            $this->result = mysqli_query($GLOBALS["___mysqli_ston"], "update $table set $data where $condition") or $this->setError(((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)), ((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_errno($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_errno()) ? $___mysqli_res : false))); 
                            $this->query = "update $table set $data where $condition"; 

                            if ($this->result) 
                            { 
                                $this->affected = intval(mysqli_affected_rows($GLOBALS["___mysqli_ston"])); 
                                // return the number of rows affected 
                                return $this->affected; 
                            } 
                        } 
                        else 
                        { 
                            $this->result = mysqli_query($GLOBALS["___mysqli_ston"], "insert into $table set $data") or $this->setError(((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)), ((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_errno($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_errno()) ? $___mysqli_res : false))); 
                            $this->query = "insert into $table set $data"; 

                            if ($this->result) 
                            { 
                                $this->insert_id = intval(((is_null($___mysqli_res = mysqli_insert_id($GLOBALS["___mysqli_ston"]))) ? false : $___mysqli_res)); 
                                $this->affected = intval(mysqli_affected_rows($GLOBALS["___mysqli_ston"])); 
                                // return the number of rows affected 
                                return $this->affected; 
                            } 
                        } 
                    } 
                    else 
                    { 
                        print "No Condition Specified !!"; 
                    } 
                } 
                else 
                { 
                    print "No Data Specified !!"; 
                } 
            } 
            else 
            { 
                print "No Table Specified !!"; 
            } 
        } 


        // Runs the sql query with claus "limit x, x" 
        public function select_limited($table, $start, $return_count, $condition = null, $order = null) 
        { 
            # Params: 
            #         $start = starting row for limit clause 
            #         $return_count = number of records to fetch 
            #         $condition = example: where id = 99 
            #         $order = ordering field name 
             
            if ($table && $start >= 0 && $return_count) 
            { 
                if ($condition) 
                { 
                    if ($order) 
                    { 
                        $this->result = mysqli_query($GLOBALS["___mysqli_ston"], "select * from $table where $condition order by $order limit $start, $return_count") or $this->setError(((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)), ((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_errno($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_errno()) ? $___mysqli_res : false))); 
                        $this->query = "select * from $table where $condition order by $order limit $start, $return_count"; 
                    } 
                    else 
                    { 
                        $this->result = mysqli_query($GLOBALS["___mysqli_ston"], "select * from $table where $condition limit $start, $return_count") or $this->setError(((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)), ((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_errno($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_errno()) ? $___mysqli_res : false))); 
                        $this->query = "select * from $table where $condition limit $start, $return_count"; 
                    } 
                } 
                else 
                { 
                    if ($order) 
                    { 
                        $this->result = mysqli_query($GLOBALS["___mysqli_ston"], "select * from $table order by $order limit $start, $return_count") or $this->setError(((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)), ((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_errno($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_errno()) ? $___mysqli_res : false))); 
                        $this->query = "select * from $table order by $order limit $start, $return_count"; 
                    } 
                    else 
                    { 
                        $this->result = mysqli_query($GLOBALS["___mysqli_ston"], "select * from $table limit $start, $return_count") or $this->setError(((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)), ((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_errno($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_errno()) ? $___mysqli_res : false))); 
                        $this->query = "select * from $table limit $start, $return_count"; 
                    } 
                } 

                if ($this->result) 
                { 
                    $this->rows = intval(mysqli_num_rows($this->result)); 
                    // return the query resource for later processing 
                    return $this->result; 
                } 
            } 
            else 
            { 
                print "Parameter Missing !!"; 
            } 
        }     

         
        ################################################# 
        #                Utility Functions                # 
        ################################################# 
         
        // Fetchs array 
        public function fetch_array($result) 
        { 
            return mysqli_fetch_array($result); 
        } 
		
		// Fetchs assoc
        public function fetch_assoc($result) 
        { 
            return mysqli_fetch_assoc($result); 
        } 
		
		// Fetchs object
        public function fetch_object($result) 
        { 
            return mysqli_fetch_object($result); 
        } 
         
        // Gets table name 
        public function table_name($result, $i) 
        { 
            return ((mysqli_data_seek($result,  $i) && (($___mysqli_tmp = mysqli_fetch_row($result)) !== NULL)) ? array_shift($___mysqli_tmp) : false); 
        } 

        // Counts rows from last Select query 
        public function count_select() 
        { 
            if ($this->rows) 
            { 
                return $this->rows; 
            } 
        } 

        // Gets the number of affected rows after Operational query has executed 
        public function count_affected() 
        { 
            if ($this->affected) 
            { 
                return $this->affected; 
            } 
        } 

        // Checks whether a table has records         
        public function has_rows($table) 
        { 
            $rows = $this->count_all($table); 
             
            if ($rows) 
            { 
                return true; 
            } 
            else 
            { 
                return false; 
            } 
        } 
         
        // Checks whether or not a row exists with specified criteria 
        public function row_exists($command) 
        { 
            # Params: 
            #         $command = query command 

            if (!$command) 
            { 
                exit("No Query Command Specified !!"); 
            } 
         
            $this->query = $command; 
            $this->result = mysqli_query($GLOBALS["___mysqli_ston"], $command) or $this->setError(((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)), ((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_errno($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_errno()) ? $___mysqli_res : false))); 

            if ($this->result) 
            { 
                if (mysqli_num_rows($this->result)) 
                { 
                    return true; 
                } 
                else 
                { 
                    return false; 
                } 
            } 
        } 

        // Returns single fetched row 
        public function fetch_row($command) 
        { 

            if (!$command) 
            { 
                exit("No Query Command Specified !!"); 
            } 

            $this->query = $command; 
            $this->result = mysqli_query($GLOBALS["___mysqli_ston"], $command) or $this->setError(((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)), ((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_errno($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_errno()) ? $___mysqli_res : false))); 

            if ($this->result) 
            { 
                $this->rows = intval(mysqli_num_rows($this->result)); 
                $this->row = mysqli_fetch_object($this->result); 
                return $this->row; 
            } 
        } 
         
         
        // Returns single field value 
        public function fetch_value($table, $field, $condition = null) 
        { 

            if (!$table || !$field) 
            { 
                exit("Arguments Missing !!"); 
            } 

            $query = "select $field from $table"; 
             
            if ($condition != null) 
            { 
                $query = "select $field from $table where $condition"; 
            } 
             
            $this->query = $query; 
            $this->result = mysqli_query($GLOBALS["___mysqli_ston"], $query) or $this->setError(((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)), ((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_errno($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_errno()) ? $___mysqli_res : false))); 

            if ($this->result) 
            { 
                $this->rows = intval(mysqli_num_rows($this->result)); 
                $this->row = mysqli_fetch_object($this->result); 
                return $this->row->$field; 
            } 
        } 
         
         
        // Returns the last run query 
        public function last_query() 
        { 
            if ($this->query) 
            { 
                return $this->alert_msg($this->query); 
            } 
        } 
         
         
        // Gets today's date 
        public function get_date($format = null) 
        { 
            # Params: 
            #        $format = date format like Y-m-d 
             
            if ($format) 
            { 
                $today = date($format); 
            } 
            else 
            { 
                $today = date("Y-m-d"); 
            } 
             
            return $today; 
        } 
         
        // Gets currents time 
        public function get_time($format = null) 
        { 
            # Params: 
            #        $format = date format like H:m:s 
             
            if ($format) 
            { 
                $time = date($format); 
            } 
            else 
            { 
                $time = date("H:m:s"); 
            } 
             
            return $time; 
        } 

        // Adds slash to the string irrespective of the setting of getmagicquotesgpc 
        public function escape_string($value) 
        { 
            if (is_string($value)) 
            { 
                if (get_magic_quotes_gpc()) 
                { 
                        $value = stripslashes($value); 
                } 

                if (!is_numeric($value)) 
                { 
                    $value = ((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $value) : ((trigger_error("Error...", E_USER_ERROR)) ? "" : "")); 
                } 
            } 
             
            return $value; 
        }  
         
        // This function can be used to discard any characters that can be used to manipulate the SQL queries or SQL injection 
        public function is_valid($input) 
        { 
            $input = strtolower($input); 
             
            if (str_word_count($input) > 1) 
            { 
                $loop = "true"; 
                $input = explode(" ",$input); 
            } 
             
            $bad_strings = array("'","--","select","union","insert","update","like","delete","1=1","or"); 
         
            if ($loop) 
            { 
                foreach($input as $value) 
                { 
                    if (in_array($value, $bad_strings)) 
                    { 
                      return false; 
                    } 
                    else 
                    { 
                      return true; 
                    } 
                } 
            } 
            else 
            { 
                if (in_array($input, $bad_strings)) 
                { 
                  return false; 
                } 
                else 
                { 
                  return true; 
                } 
            } 
        } 
     
        // lists tables of database 
        public function list_tables() 
        { 
            $this->result = mysqli_query($GLOBALS["___mysqli_ston"], "show tables"); 
            $this->query = "show tables"; 
             
            if ($this->result) 
            { 
                $tables = array(); 
                while($row = mysqli_fetch_array($this->result)) 
                { 
                    $tables[] = $row[0]; 
                } 
                 
                foreach ($tables as $table) 
                { 
                    print $table . "<br />"; 
                } 
            } 
        } 

        // provides info about given table 
        public function table_info($table) 
        { 
            if ($table) 
            { 
                $this->result = mysqli_query($GLOBALS["___mysqli_ston"], "select * from $table"); 
                $this->query = "select * from $table"; 

                $fields = (($___mysqli_tmp = mysqli_num_fields($this->result)) ? $___mysqli_tmp : false); 
                $rows   = mysqli_num_rows($this->result); 
                $table = (mysqli_fetch_field_direct($this->result,  0)->table); 

                print "    The '<strong>" . $table . "</strong>' table has <strong>" . $fields . "</strong> fields and <strong>" . $rows . "</strong> 
                        record(s) with following fields.\n<br /><ul>"; 

                for ($i=0; $i < $fields; $i++) 
                { 
                    $type  = ((is_object($___mysqli_tmp = mysqli_fetch_field_direct($this->result, 0)) && !is_null($___mysqli_tmp = $___mysqli_tmp->type)) ? ((($___mysqli_tmp = (string)(substr(( (($___mysqli_tmp == MYSQLI_TYPE_STRING) || ($___mysqli_tmp == MYSQLI_TYPE_VAR_STRING) ) ? "string " : "" ) . ( (in_array($___mysqli_tmp, array(MYSQLI_TYPE_TINY, MYSQLI_TYPE_SHORT, MYSQLI_TYPE_LONG, MYSQLI_TYPE_LONGLONG, MYSQLI_TYPE_INT24))) ? "int " : "" ) . ( (in_array($___mysqli_tmp, array(MYSQLI_TYPE_FLOAT, MYSQLI_TYPE_DOUBLE, MYSQLI_TYPE_DECIMAL, ((defined("MYSQLI_TYPE_NEWDECIMAL")) ? constant("MYSQLI_TYPE_NEWDECIMAL") : -1)))) ? "real " : "" ) . ( ($___mysqli_tmp == MYSQLI_TYPE_TIMESTAMP) ? "timestamp " : "" ) . ( ($___mysqli_tmp == MYSQLI_TYPE_YEAR) ? "year " : "" ) . ( (($___mysqli_tmp == MYSQLI_TYPE_DATE) || ($___mysqli_tmp == MYSQLI_TYPE_NEWDATE) ) ? "date " : "" ) . ( ($___mysqli_tmp == MYSQLI_TYPE_TIME) ? "time " : "" ) . ( ($___mysqli_tmp == MYSQLI_TYPE_SET) ? "set " : "" ) . ( ($___mysqli_tmp == MYSQLI_TYPE_ENUM) ? "enum " : "" ) . ( ($___mysqli_tmp == MYSQLI_TYPE_GEOMETRY) ? "geometry " : "" ) . ( ($___mysqli_tmp == MYSQLI_TYPE_DATETIME) ? "datetime " : "" ) . ( (in_array($___mysqli_tmp, array(MYSQLI_TYPE_TINY_BLOB, MYSQLI_TYPE_BLOB, MYSQLI_TYPE_MEDIUM_BLOB, MYSQLI_TYPE_LONG_BLOB))) ? "blob " : "" ) . ( ($___mysqli_tmp == MYSQLI_TYPE_NULL) ? "null " : "" ), 0, -1))) == "") ? "unknown" : $___mysqli_tmp) : false);
                    $name  = ((($___mysqli_tmp = mysqli_fetch_field_direct($this->result, 0)->name) && (!is_null($___mysqli_tmp))) ? $___mysqli_tmp : false); 
                    $len   = ((($___mysqli_tmp = mysqli_fetch_fields($this->result)) && (isset($___mysqli_tmp[0]))) ? $___mysqli_tmp[0]->length : false); 
                    $flags = (($___mysqli_tmp = mysqli_fetch_field_direct($this->result,  $i)->flags) ? (string)(substr((($___mysqli_tmp & MYSQLI_NOT_NULL_FLAG)       ? "not_null "       : "") . (($___mysqli_tmp & MYSQLI_PRI_KEY_FLAG)        ? "primary_key "    : "") . (($___mysqli_tmp & MYSQLI_UNIQUE_KEY_FLAG)     ? "unique_key "     : "") . (($___mysqli_tmp & MYSQLI_MULTIPLE_KEY_FLAG)   ? "unique_key "     : "") . (($___mysqli_tmp & MYSQLI_BLOB_FLAG)           ? "blob "           : "") . (($___mysqli_tmp & MYSQLI_UNSIGNED_FLAG)       ? "unsigned "       : "") . (($___mysqli_tmp & MYSQLI_ZEROFILL_FLAG)       ? "zerofill "       : "") . (($___mysqli_tmp & 128)                        ? "binary "         : "") . (($___mysqli_tmp & 256)                        ? "enum "           : "") . (($___mysqli_tmp & MYSQLI_AUTO_INCREMENT_FLAG) ? "auto_increment " : "") . (($___mysqli_tmp & MYSQLI_TIMESTAMP_FLAG)      ? "timestamp "      : "") . (($___mysqli_tmp & MYSQLI_SET_FLAG)            ? "set "            : ""), 0, -1)) : false); 
                     
                    print "<strong><li>" . $type . " " . $name . " " . $len . " " . $flags . "</strong></li>\n"; 
                } 
                print "</ul>"; 
                 
            } 
            else 
            { 
                print "The table not specified !!"; 
            } 
        } 


        // displays any mysql errors generated 
        public function display_errors() 
        { 
            if ($this->show_errors == false) 
            { 
                if ($this->emsg) 
                { 
                    return '<div style="background:#f6f6f6; margin-top:5px;margin-bottom:5px;padding:5px; font-size:13px; font-family:verdana; border:1px solid #cccccc;"> 
                            <span style="color:#ff0000;">MySQL Error Number</span> : ' . $this->eno . '<br /> 
                            <span style="color:#ff0000;">MySQL Error Message</span> : ' . $this->emsg . '</div>'; 
                } 
                else 
                { 
                    return '<br /><br /><div style="background:#f6f6f6; padding:5px; font-size:13px; font-family:verdana; border:1px solid #cccccc;"> 
                            <strong>No Erros Found !!</strong> 
                            </div>'; 
                } 
            } 
        } 

        // to display success message 
        public function success_msg($msg) 
        { 
            print '<br /><br /><div align="center" style="background:#EEFDD7; padding:5px; font-size:13px; font-family:verdana; border:1px solid #8DD607;"> 
                    ' . $msg . ' 
                    </div><br />'; 
        } 
     
        // to display failure message 
        public function failure_msg($msg) 
        { 
            print '<br /><br /><div align="center" style="background:#FFF2F2; padding:5px; font-size:13px; font-family:verdana; border:1px solid #FF8080;"> 
                    ' . $msg . ' 
                    </div><br />'; 
        } 

        // to display general alert message 
        public function alert_msg($msg) 
        { 
            print '    <br /><br /><div align="center" style="background:#FFFFCC; padding:5px; font-size:13px; font-family:verdana; border:1px solid #CCCC33;"> 
                    ' . $msg . ' </div><br />'; 
        } 
    //////////////////////////////////////////////////////// 
    } 

?>
