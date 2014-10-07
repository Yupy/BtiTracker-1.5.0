<?php
/*
* BtiTracker v1.5.0 is a php tracker system for BitTorrent, easy to setup and configure.
* This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
* Updated and Maintained by Yupy.
* Copyright (C) 2004-2014 Btiteam.org
*/

utf8::init();
class utf8 {
	const TRANSKEY = 'utf8::utf8_win_iso::translation_table';
	const NBSP = "\xC2\xA0";	// &nbsp; # Non-Breaking Space
	const TRIM_CHARLIST = "\x00..\x20\x7F..\xC2\xA0\xE2\x80\x80..\xE2\x80\x8D\xE2\x80\xAF\xE2\x81\x9F\xE2\x81\xA0\xE3\x80\x80\xEF\xBB\xBF";

	private static $trans_table = array();
	private static $utf8validator = false;

	public static function init() {
		self::$utf8validator = (bool)extension_loaded('utf8validator');
		mb_internal_encoding('UTF-8');
		mb_language('uni');
		mb_regex_encoding('UTF-8');
		mb_detect_order(array('UTF-8', 'ISO-8859-1'));
		mb_substitute_character(0xFFFD);

			$win		= "\x80".implode('', range("\x82", "\x8c"))."\x8e".implode('', range("\x91", "\x9c")).implode('', range("\x9e", "\xff"));
			$win_iso	= "\x81\x8d\x8f\x90\x9d";
			$iso		= implode('', range("\x80", "\xff"));
			$winlen		= strlen($win);
			$winisolen	= strlen($win_iso);
			$isolen		= strlen($iso);

			$trans = array(
				'iso_to_utf8'	=> array(),
				'win_to_utf8'	=> array(),
				'from_utf8'		=> array(),
			);

			for ($i = 0; $i < $isolen; $i++) {
				$utf8 = mb_convert_encoding($iso[$i], 'UTF-8', 'ISO-8859-1');
				$trans['iso_to_utf8'][$iso[$i]] = $utf8;
				$trans['from_utf8'][$utf8] = $iso[$i];
			}

			for ($i = 0; $i < $winlen; $i++) {
				$utf8 = mb_convert_encoding($win[$i], 'UTF-8', 'Windows-1252');
				$trans['win_to_utf8'][$win[$i]] = $utf8;
				$trans['from_utf8'][$utf8] = $win[$i];
			}

			for ($i = 0; $i < $winisolen; $i++) {
				$utf8 = mb_convert_encoding($win_iso[$i], 'UTF-8', 'ISO-8859-1');
				$trans['win_to_utf8'][$win_iso[$i]] = $utf8;
			}

		self::$trans_table = $trans;
	}

	// Returns NULL on error, true or false otherwise
	public static function is_ascii($string) {
		if (!is_string($string))
			return NULL;

		$valid = preg_match('#^[\x00-\x7F]*$#Ds', $string);
		if ($valid === false)
			return NULL;

		return (bool)$valid;
	}

	// Returns NULL on error, true or false otherwise
	public static function is_utf8($string, &$strlen = 0) {
		if (self::$utf8validator)
			return utf8validator($string, $strlen);
		else {
			if (!is_string($string))
				return NULL;

			$valid = preg_match('##Dsu', $string);

			if ($valid === false) {
				$error = preg_last_error();
				switch ($error) {
					case PREG_BAD_UTF8_ERROR:
					case PREG_BAD_UTF8_OFFSET_ERROR:
						return false;

				default:
					return NULL;
				}
			}

			$strlen = self::strlen($string);
			return (bool)$valid;
		}
	}

	public static function bin2utf8($string, $win1252 = true) {
		return strtr($string, ($win1252 ? self::$trans_table['win_to_utf8'] : self::$trans_table['iso_to_utf8']));
	}

	public static function utf82bin($string) {
		return strtr($string, self::$trans_table['from_utf8']);
	}

	public static function to_utf8($data) {
		if (is_string($data)) {
			if (!self::is_utf8($data))
				$data = self::bin2utf8($data); // Windows-1252 - ISO-8859-1 hybrid, lossless

			return $data;
		}
		elseif (is_array($data)) {
			$newdata = array();
			foreach ($data as $key => $value) {
				$key = self::to_utf8($key);
				$newdata[$key] = self::to_utf8($value);
			}
			return $newdata;
		}
		else
			return $data;
	}

	public static function strlen($str) {
		return mb_strlen($str, 'UTF-8');
	}

	public static function substr($str, $start, $length = -1) {
		return mb_substr($str, $start, $length, 'UTF-8');
	}

	public static function strpos($haystack, $needle, $offset = 0) {
		return mb_strpos($haystack, $needle, $offset, 'UTF-8');
	}

	public static function stripos($haystack, $needle, $offset = 0) {
		return mb_stripos($haystack, $needle, $offset, 'UTF-8');
	}

	public static function strrpos($haystack, $needle, $offset = 0) {
		return mb_strrpos($haystack, $needle, $offset, 'UTF-8');
	}

	public static function strripos($haystack, $needle, $offset = 0) {
		return mb_strripos($haystack, $needle, $offset, 'UTF-8');
	}

	public static function substr_count($haystack, $needle) {
		return mb_substr_count($haystack, $needle, 'UTF-8');
	}

	public static function strtoupper($str) {
		return mb_convert_case($str, MB_CASE_UPPER, 'UTF-8');
	}

	public static function strtolower($str) {
		return mb_convert_case($str, MB_CASE_LOWER, 'UTF-8');
	}

	public static function ltrim($str, $charlist = self::TRIM_CHARLIST) {
		if (!is_string($str) || !is_string($charlist))
			return false;

		$charlist = preg_quote($charlist, '#');
		$charlist = strtr($charlist, array('\\.\\.' => '-'));

		return preg_replace('#^['.$charlist.']+#Dsu', '', $str);
	}

	public static function rtrim($str, $charlist = self::TRIM_CHARLIST) {
		if (!is_string($str) || !is_string($charlist))
			return false;

		$charlist = preg_quote($charlist, '#');
		$charlist = strtr($charlist, array('\\.\\.' => '-'));

		return preg_replace('#['.$charlist.']+$#Dsu', '', $str);
	}

	public static function trim($str, $charlist = self::TRIM_CHARLIST) {
		return self::ltrim(self::rtrim($str, $charlist), $charlist);
	}

	public static function ucwords($str) {
		return mb_convert_case($str, MB_CASE_TITLE, 'UTF-8');
	}

	public static function ucfirst($str) {
		$len = self::strlen($str);
		if (!$len)
			return '';

		return mb_strtoupper(mb_substr($str, 0, 1, 'UTF-8'), 'UTF-8').mb_substr($str, 1, $len, 'UTF-8');
	}
}

?>