<?php
/*
* BtiTracker v1.5.0 is a php tracker system for BitTorrent, easy to setup and configure.
* This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
* Updated and Maintained by Yupy.
* Copyright (C) 2004-2014 Btiteam.org
*/
require_once(CLASS_PATH . 'class.UTF8.php');

_string::init();
class _string {
	public static $chr = array();
	public static $ord = array();
	public static $b2c = array();
	public static $c2b = array();
	public static $clr_bits = array(0x00, 0x80, 0xC0, 0xE0, 0xF0, 0xF8, 0xFC, 0xFE);
	public static $set_bits = array(0xFF, 0x7F, 0x3F, 0x1F, 0x0F, 0x07, 0x03, 0x01);

	private static $glob_search = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]';
	private static $glob_replace = 'abcdefghijklmnopqrstuvwxyz{|}';
	private static $glob_regexp = array(
		'\\?'	=> '.',
		'\\*'	=> '.*?',
	);

	public static function init() {
			$chrs = $ords = array();
			for ($i = 0; $i <= 0xFF; $i++) {
				$char = chr($i);
				$bin = sprintf('%08b', $i);
				$chrs[$i] = $char;
				$ords[$char] = $i;
				$b2cs[$bin] = $char;
				$c2bs[$char] = $bin;
			}

			$chars = array($chrs, $ords, $b2cs, $c2bs);

		self::$chr = $chars[0];
		self::$ord = $chars[1];
		self::$b2c = $chars[2];
		self::$c2b = $chars[3];
	}

	public static function str2bin($string) {
		return strtr($string, self::$c2b);
	}

	public static function bin2str($bin) {
		$len = strlen($bin);
		if ($len % 8) {
			trigger_error('Input string length must be a multiple of 8 in '.__METHOD__, E_USER_WARNING);
			return '';
		}

		$slen = $len / 8;
		$string = strtr($bin, self::$b2c);

		if (strlen($string) != $slen) {
			trigger_error('Input string must contain only binary digits in '.__METHOD__, E_USER_WARNING);
			return '';
		}

		return $string;
	}

	public static function str2hex($string) {
		if ($string === NULL)
			return NULL;

		return bin2hex($string);
	}

	public static function hex2str($hex) {
		if ($hex === NULL)
			return NULL;

		return pack('H*', $hex);
	}

	public static function xor_string($string, $xor_with) {
		for ($i = 0, $strlen = strlen($string), $xorlen = strlen($xor_with); $i < $strlen; $i++) {
			// get the xor position
			$p = $i % $xorlen;

			// xor here
			$r = (self::$ord[$string[$i]] ^ self::$ord[$xor_with[$p]]) & 0xff;

			// add data to new string
			$string[$i] = self::$chr[$r];
		}
		return $string;
	}

	public static function glob_match($string, $mask, $case_sensitive = false) {
		if (!is_string($string) || !is_string($mask))
			return false;

		if (!$case_sensitive) {
			$string = strtr($string, self::$glob_search, self::$glob_replace);
			$mask = strtr($mask, self::$glob_search, self::$glob_replace);
		}

		$mask  = preg_quote($mask, '#');
		$mask = '#^'.strtr($mask, self::$glob_regexp).'$#sDU';
		return (bool)(preg_match($mask, $string));
	}

	public static function bincmp($str1, $str2, $len) {
		if (!is_int($len) || !is_string($str1) || !is_string($str2))
			return false;

		if ($len === 0 || $str1 === $str2)
			return 0;

		$extra_len = $len % 8;
		$base_len = $len - $extra_len;
		$base_length = $base_len / 8;

		if ($base_len > 0) {
			$basecmp = strncmp($str1, $str2, $base_length);
			if ($basecmp)
				return $basecmp;
		}
		if ($extra_len === 0)
			return 0;

		$ord1 = self::$ord[$str1[$base_length]];
		$ord2 = self::$ord[$str2[$base_length]];
		$bit = self::$clr_bits[$extra_len];

		if (($ord1 & $bit) === ($ord2 & $bit))
			return 0;
		elseif ($ord1 < $ord2)
			return -1;
		else
			return 1;
	}

	public static function bin_range($string, &$min, &$max, $len) {
        if (!is_int($len) || !is_string($string))
            return false;

        $strlen = strlen($string);
        $bitlen = $strlen * 8;

        if ($len < 0 || $len > $bitlen)
            return false;

        $extra_len = $len % 8;
        $base_len = $len - $extra_len;
        $base_length = $base_len / 8;

        if ($base_len > 0)
            $min = $max = substr($string, 0, $base_length);
        else
            $min = $max = '';

		if ($extra_len > 0) {
			$ord = self::$ord[$string[$base_length]];
			$clr_bit = self::$clr_bits[$extra_len];
			$set_bit = self::$set_bits[$extra_len];

			$min .= self::$chr[($ord & $clr_bit)];
			$max .= self::$chr[($ord | $set_bit)];
		}

		$min = str_pad($min, $strlen, "\x00", STR_PAD_RIGHT);
		$max = str_pad($max, $strlen, "\xFF", STR_PAD_RIGHT);
		return true;
	}

	public static function is_hex($str) {
		return ctype_xdigit($str);
	}

	public static function b64_decode($data) {
		if (strpos($data, "\x00") !== false)
			return false;

		return base64_decode($data, true);
	}

	public static function b64_encode($data) {
		return base64_encode($data);
	}

	public static function random($length = 50) {
		$rand = '';
		for ($i = 0; $i < $length; $i++)
			$rand .= chr(mt_rand() & 0xff);
		return $rand;
	}

	public static function cut_word($txt, $max_length = 24, $pad_with = '&#8203;') {
		if (empty($txt))
			return false;

		for ($c = 0, $a = 0, $g = 0, $txtlen = strlen($txt); $c < $txtlen; $c++) {
			$d[($c + $g)] = $txt[$c];
			if ($txt[$c] != ' ')
				$a++;
			elseif ($txt[$c] == ' ')
				$a = 0;
			if ($a > $max_length) {
				$g++;
				$d[($c + $g)] = $pad_with;
				$a = 0;
			}
		}
		return implode('', $d);
	}

	public static function shorten_string($str, $max_length, $mid_cut = false) {
		if (!is_scalar($str))
			return false;

		if (!is_int($max_length))
			return false;

		$length = utf8::strlen($str);
		if ($length <= $max_length)
			return $str;
		elseif ($mid_cut) {
			$mid = (int)ceil($max_length / 2);
			$string = utf8::substr($str, 0, $mid).'...'.utf8::substr($str, $mid);
		}
		else
			return utf8::substr($str, 0, $max_length).'...';
	}
};

?>