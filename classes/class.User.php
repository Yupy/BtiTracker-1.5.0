<?php
/*
* BtiTracker v1.5.0 is a php tracker system for BitTorrent, easy to setup and configure.
* This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
* Updated and Maintained by Yupy.
* Copyright (C) 2004-2014 Btiteam.org
*/
require_once(CLASS_PATH . 'class.Security.php');

class user {
    public static $current = NULL;

	public static function prepare_user(&$user, $curuser = false) {
		if ($curuser && empty($user))
			die;

		if (isset($user['torrentsperpage']))
			$user['torrentsperpage'] = (int)$user['torrentsperpage'];
		if (isset($user['uid']))
			$user['uid'] = (int)$user['uid'];
		if (isset($user['username']))
			$user['username'] = security::html_safe($user['username']);
		if (isset($user['language']))
			$user['language'] = (int)$user['language'];
		if (isset($user['style']))
			$user['style'] = (int)$user['style'];
		if (isset($user['flag']))
			$user['flag'] = (int)$user['flag'];
		if (isset($user['topicsperpage']))
			$user['topicsperpage'] = (int)$user['topicsperpage'];
		if (isset($user['postsperpage']))
			$user['postsperpage'] = (int)$user['postsperpage'];
		if (isset($user['id_level']))
			$user['id_level'] = (int)$user['id_level'];
		if (isset($user['WT']))
			$user['WT'] = (int)$user['WT'];
		if (isset($user['random']))
			$user['random'] = (int)$user['random'];
		if (isset($user['flags']))
			$user['flags'] = (int)$user['flags'];
	}

}

?>
