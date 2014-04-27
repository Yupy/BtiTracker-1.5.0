<?php

class Forum {
    const ACCESS_TIME = 10800;
    const GET_TOPIC_TIME = 300;

    public static function get_forum_access_levels($forumid)
    {
        global $Memcached, $db;
		
        if (($arr = $Memcached->get_value("ForumAccess::".$forumid)) == false) {	
            $res = $db->execute("SELECT minclassread, minclasswrite, minclasscreate FROM forums WHERE id = ".$forumid) or $db->display_errors();

        if ($db->count_select($res) != 1)
            return false;

        $arr = $db->fetch_assoc($res);
	
        $arr['minclassread'] = 0 + (int)$arr['minclassread'];
        $arr['minclasswrite'] = 0 + (int)$arr['minclasswrite'];
        $arr['minclasscreate'] = 0 + (int)$arr['minclasscreate'];
	
    	$Memcached->cache_value("ForumAccess::".$forumid, $arr, self::ACCESS_TIME);
        }
        return array("read" => $arr["minclassread"], "write" => $arr["minclasswrite"], "create" => $arr["minclasscreate"]);
    }
	
	public static function delete_access_levels_cache($forumid)
	{
	    global $Memcached;
		
		$id = 0 + (int)$forumid;
	    $Key = "ForumAccess::".$id;
        $Memcached->delete_value($Key);
    }
	
    public static function get_topic_forum($topicid)
    {
        global $db, $Memcached;
	
	    if (($arr = $Memcached->get_value("Forum::ID::".$topicid)) == false) {
            $res = $db->execute("SELECT forumid FROM topics WHERE id = ".(int)$topicid) or $db->display_errors();

        if ($db->count_select($res) != 1)
            return false;

        $arr = $db->fetch_row($res);
	    $Memcached->cache_value("Forum::ID::".$topicid, $arr, self::GET_TOPIC_TIME);
        }
        return $arr[0];
    }

}

?>
