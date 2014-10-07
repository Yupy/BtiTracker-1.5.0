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

		if (isset($user['view_users']))
			$user['view_users'] = $user['view_users'];
		if (isset($user['torrentsperpage']))
			$user['torrentsperpage'] = (int)$user['torrentsperpage'];
		if (isset($user['uid']))
			$user['uid'] = (int)$user['uid'];
		if (isset($user['edit_users']))
			$user['edit_users'] = $user['edit_users'];
		if (isset($user['delete_users']))
			$user['delete_users'] = $user['delete_users'];
		if (isset($user['admin_access']))
			$user['admin_access'] = $user['admin_access'];
		if (isset($user['username']))
			$user['username'] = security::html_safe($user['username']);
		if (isset($user['avatar']))
			$user['avatar'] = $user['avatar'];
		if (isset($user['email']))
			$user['email'] = $user['email'];
		if (isset($user['language']))
			$user['language'] = (int)$user['language'];
		if (isset($user['style']))
			$user['style'] = (int)$user['style'];
		if (isset($user['flag']))
			$user['flag'] = (int)$user['flag'];
		if (isset($user['time_offset']))
			$user['time_offset'] = $user['time_offset'];
		if (isset($user['topicsperpage']))
			$user['topicsperpage'] = (int)$user['topicsperpage'];
		if (isset($user['postsperpage']))
			$user['postsperpage'] = (int)$user['postsperpage'];
		if (isset($user['id_level']))
			$user['id_level'] = (int)$user['id_level'];
		if (isset($user['password']))
			$user['password'] = $user['password'];
		if (isset($user['lastconnect']))
			$user['lastconnect'] = $user['lastconnect'];
		if (isset($user['level']))
			$user['level'] = $user['level'];
		if (isset($user['joined']))
			$user['joined'] = $user['joined'];
		if (isset($user['can_upload']))
			$user['can_upload'] = $user['can_upload'];
		if (isset($user['view_news']))
			$user['view_news'] = $user['view_news'];
		if (isset($user['view_torrents']))
			$user['view_torrents'] = $user['view_torrents'];
		if (isset($user['WT']))
			$user['WT'] = (int)$user['WT'];
		if (isset($user['view_forum']))
			$user['view_forum'] = $user['view_forum'];
		if (isset($user['edit_news']))
			$user['edit_news'] = $user['edit_news'];
		if (isset($user['delete_news']))
			$user['delete_news'] = $user['delete_news'];
		if (isset($user['edit_forum']))
			$user['edit_forum'] = $user['edit_forum'];
		if (isset($user['delete_forum']))
			$user['delete_forum'] = $user['delete_forum'];
		if (isset($user['edit_torrents']))
			$user['edit_torrents'] = $user['edit_torrents'];
		if (isset($user['can_download']))
			$user['can_download'] = $user['can_download'];
		if (isset($user['delete_torrents']))
			$user['delete_torrents'] = $user['delete_torrents'];
		if (isset($user['random']))
			$user['random'] = (int)$user['random'];
		if (isset($user['flags']))
			$user['flags'] = (int)$user['flags'];
	}

}

?>
