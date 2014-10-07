<?php
/*
* BtiTracker v1.5.0 is a php tracker system for BitTorrent, easy to setup and configure.
* This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
* Updated and Maintained by Yupy.
* Copyright (C) 2004-2014 Btiteam.org
*/

class security {
	private static $valid_tlds = array(
		'ac','ad','ae','af','ag','ai','al','am','an','ao','aq',
		'ar','as','at','au','aw','az','ax','ba','bb','bd','be',
		'bf','bg','bh','bi','bj','bm','bn','bo','br','bs','bt',
		'bv','bw','by','bz','ca','cc','cd','cf','cg','ch','ci',
		'ck','cl','cm','cn','co','cr','cs','cu','cv','cx','cy',
		'cz','de','dj','dk','dm','do','dz','ec','ee','eg','eh',
		'er','es','et','eu','fi','fj','fk','fm','fo','fr','ga',
		'gb','gd','ge','gf','gg','gh','gi','gl','gm','gn','gp',
		'gq','gr','gs','gt','gu','gw','gy','hk','hm','hn','hr',
		'ht','hu','id','ie','il','im','in','io','iq','ir','is',
		'it','je','jm','jo','jp','ke','kg','kh','ki','km','kn',
		'kp','kr','kw','ky','kz','la','lb','lc','li','lk','lr',
		'ls','lt','lu','lv','ly','ma','mc','md','mg','mh','mk',
		'ml','mm','mn','mo','mp','mq','mr','ms','mt','mu','mv',
		'mw','mx','my','mz','na','nc','ne','nf','ng','ni','nl',
		'no','np','nr','nu','nz','om','pa','pe','pf','pg','ph',
		'pk','pl','pm','pn','pr','ps','pt','pw','py','qa','re',
		'ro','ru','rw','sa','sb','sc','sd','se','sg','sh','si',
		'sj','sk','sl','sm','sn','so','sr','st','sv','sy','sz',
		'tc','td','tf','tg','th','tj','tk','tl','tm','tn','to',
		'tp','tr','tt','tv','tw','tz','ua','ug','uk','um','us',
		'uy','uz','va','vc','ve','vg','vi','vn','vu','wf','ws',
		'ye','yt','yu','za','zm','zw','biz','com','info','name',
		'net','org','edu','xxx','me',

		#'aero','gov','travel','pro','int','mil','jobs','mobi' #Example of Domains we don't want to see on our Tracker =]
	);

	public static function html_safe($data, $onlyspecialchars = true, $strip_invalid = true) {
		if (!is_scalar($data))
			return '';

		$invalid_chars = array("\x00","\x01","\x02","\x03","\x04","\x05","\x06","\x07","\x08","\x0b","\x0c",
			"\x0e","\x0f","\x10","\x11","\x12","\x13","\x14","\x15","\x16","\x17","\x18","\x19","\x1a","\x1b",
			"\x1c","\x1d","\x1e","\x1f", "\x7f");

		if ($strip_invalid)
			$data = str_replace($invalid_chars, '', $data);

		if ($onlyspecialchars)
			return htmlspecialchars($data, ENT_QUOTES, 'UTF-8', true);
		else
			return htmlentities($data, ENT_QUOTES, 'UTF-8', true);
	}

	public static function valid_email($email) {
		if (preg_match('/^[\w.+-]+@(?:[\w.-]+\.)+([a-z]{2,6})$/isD', $email, $m)) {
			if (self::valid_tld($m[1]))
				return true;
		}
		return false;
	}

	private static function valid_tld($tld) {
		$tld = strtolower($tld);
		if (in_array($tld, self::$valid_tlds, true))
			return true;
		else
			return false;
	}

}

?>