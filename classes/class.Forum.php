<?php

class Forum {

    public static function get_forum_access_levels($forumid)
    {
        global $Memcached, $db;
		
        if (($arr = $Memcached->get_value("ForrumAccess::".$forumid)) == false) {	
            $res = $db->execute("
			                    SELECT 
								    minclassread, 
									minclasswrite, 
									minclasscreate 
								FROM 
								    forums 
								WHERE id = ".$forumid) or $db->display_errors();

        if ($db->count_select($res) != 1)
            return false;

        $arr = $db->fetch_assoc($res);
	
        $arr['minclassread'] = 0 + (int)$arr['minclassread'];
        $arr['minclasswrite'] = 0 + (int)$arr['minclasswrite'];
        $arr['minclasscreate'] = 0 + (int)$arr['minclasscreate'];
	
    	$Memcached->cache_value("ForrumAccess::".$forumid, $arr, 10800);
        }
    return array("read" => $arr["minclassread"], "write" => $arr["minclasswrite"], "create" => $arr["minclasscreate"]);
    }
	
	public static function delete_access_levels_cache($forumid)
	{
	    global $Memcached;
		
		$id = 0 + (int)$forumid;
	    $Key = "ForrumAccess::".$id;
        $Memcached->delete_value($Key);
    }

}

?>
