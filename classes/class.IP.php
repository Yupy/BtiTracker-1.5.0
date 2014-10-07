<?php
/*
* BtiTracker v1.5.0 is a php tracker system for BitTorrent, easy to setup and configure.
* This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
* Updated and Maintained by Yupy.
* Copyright (C) 2004-2014 Btiteam.org
*/
require_once(CLASS_PATH . 'class.String.php');
require_once(CLASS_PATH . 'class.Vars.php');

class ip {
	const IP4 = 1;
	const IP6 = 2;

	private static $ip_cache = array();

	public static function ip2hex($ip) {
		$in_addr = self::ip2addr($ip);
		if (!$in_addr)
			return false;

		return _string::str2hex($in_addr);
	}

	public static function hex2ip($hex_ip) {
		$in_addr = _string::hex2str($hex_ip);
		$ip = self::addr2ip($in_addr);

		return $ip;
	}

	public static function ip2hex6($ip) {
		$in_addr = self::ip2addr6($ip);
		if (!$in_addr)
			return false;

		return _string::str2hex($in_addr);
	}

	public static function addr2ip($addr) {
		$ip = @inet_ntop($addr);
		if (!$ip || !self::type($ip, $type))
			return false;

		return $ip;
	}

	public static function ip2addr($ip) {
		$addr = self::type($ip, $type);

		return $addr;
	}

	public static function addr2addr6($addr) {
		$len = strlen($addr);
		if ($len === 16)
			return $addr;
		elseif ($len === 4)
			return "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff".$addr;
		else
			return false;
	}

	public static function ip2addr6($ip) {
		$addr = self::type($ip, $type);

		if (!$addr)
			return false;

		if ($type === self::IP4)
			$addr = "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff".$addr;

		return $addr;
	}

	public static function type(&$ip, &$type, &$ip2 = NULL) {
		if (isset(self::$ip_cache[$ip])) {
			list($ip, $type, $addr, $ip2) = self::$ip_cache[$ip];
			return $addr;
		}

		$ip2 = false;

		$addr = @inet_pton($ip);
		if (!$addr) {
			$type = false;
			return false;
		}

		$len = strlen($addr);

		if ($len === 4)
			$type = self::IP4;
		elseif ($len === 16) {
			$first12 = $addr[0].$addr[1].$addr[2].$addr[3].$addr[4].$addr[5].$addr[6].$addr[7].$addr[8].$addr[9].$addr[10].$addr[11];
			$last4 = $addr[12].$addr[13].$addr[14].$addr[15];
			switch ($first12) {
				case "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff":
				case "\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff\x00":
				case "\x00\x64\xff\x9b\x00\x00\x00\x00\x00\x00\x00\x00":
					$addr = $last4;
					$type = self::IP4;
					break;
				case "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00":
					if (!_string::bincmp($addr, "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00", 127))
						$type = self::IP6;
					else {
						$addr = $last4;
						$type = self::IP4;
					}
					break;
				default:
					$type = self::IP6;
					if ($addr[0].$addr[1] === "\x20\x02") {
						// 6to4
						$ip4 = $addr[2].$addr[3].$addr[4].$addr[5];
						$ip4 = @inet_ntop($ip4);
						if ($ip4)
							$ip2 = $ip4;
					}
					elseif ($addr[0].$addr[1].$addr[2].$addr[3] === "\x20\x01\x00\x00") {
						// Teredo
						$ip4 = $last4;
						$ip4 = @unpack('N', $ip4);
						if ($ip4)
							$ip2 = long2ip(~$ip4[1]);	// Flip the bits
					}
					break;
			}
		}
		else
			$type = false;

		self::$ip_cache[$ip] = array(@inet_ntop($addr), $type, $addr, $ip2);
		$ip = self::$ip_cache[$ip][0];

		return $addr;
	}

	public static function net_match($ip, $network) {
		$addr = self::type($ip, $type);
		if ($type === self::IP4) {
			$ip_up = unpack('N', $addr);
			$ip_long = $ip_up[1];

			$net_arr = explode('/', $network, 2);
			$network_long = ip2long($net_arr[0]);

			$netmask = isset($net_arr[1]) && $net_arr[1] !== '' ? $net_arr[1] : 32;

			$mask = self::netmask($netmask, self::IP4);

			if ($network_long === false || $mask === false)
				return false;

			return ($ip_long & $mask) === ($network_long & $mask);
		}
		elseif ($type === self::IP6) {
			// The only way to verify ipv6 cidr is to convert the binary packed ipv6 to a binary (0s and 1s) string,
			// and check that the length of the first cidr bytes match.
			$net_arr = explode('/', $network, 2);

			$net_addr = self::type($net_arr[0], $net_type);

			$netmask = isset($net_arr[1]) && $net_arr[1] !== '' ? $net_arr[1] : 128;
			$mask = self::netmask($netmask, self::IP6);

			if (!$net_addr || $net_type !== self::IP6 || $mask === false)
				return false;


			return !_string::bincmp($addr, $net_addr, $mask);
		}
		else // invalid ip
			return false;
	}

	public static function netmask($netmask, $type = self::IP4) {
		if ($type === self::IP4) {			
			$mask_long = @ip2long($netmask);

			if ($mask_long === false) {
				$mask_long = 0 + $netmask;
				if ($mask_long != $netmask)
					return false;

				elseif ($mask_long < 0 || $mask_long > 32)
					return false;

				// If the netmask is cidr, take 255.255.255.255 int (32bit maxed) and shift them off the end to get the
				// netmask. The "& 0xffffffff" at the end is to keep only the first 32 bits of the number, since on 64bit
				// systems it will come back with a very large number (because it won't be shifting them off the end).
				$mask = (0xffffffff << (32 - $mask_long)) & 0xffffffff;
			}
			elseif (long2ip($mask_long) == $netmask)
				$mask = $mask_long;
			else
				return false;

			return $mask;
		}
		elseif ($type === self::IP6) {
			// This doesn't return the same type of data as the ipv4 section because of the limited int size, instead it
			// simply verifies that the cidr values are valid (between 0 and 128) and returns the int value instead of
			// string.

			$mask = (int)$netmask;
			if ($mask < 0 || $mask > 128)
				return false;

			return $mask;
		}
		else
			return false;
	}

	public static function netmask_range($ip, $netmask, &$min, &$max, $raw = false) {
		$addr = self::type($ip, $type);
		if ($type === self::IP4) {
			$long_ip = ip2long($ip);
			$netmask = $netmask !== '' ? $netmask : 32;
			$mask = self::netmask($netmask, self::IP4);
			if ($mask === false)
				return false;

			$min = $long_ip & $mask;
			$max = $min ^ ~$mask & 0xffffffff;

			if (!$raw) {
				$min = long2ip($min);
				$max = long2ip($max);
			}

			return true;
		}
		elseif ($type === self::IP6) {
			$netmask = $netmask !== '' ? $netmask : 128;
			$mask = self::netmask($netmask, self::IP6);
			if ($mask === false)
				return false;

			$range = _string::bin_range($addr, $min, $max, $mask);
			if ($range === false)
				return false;

			if (!$raw) {
				$min = self::addr2ip($min);
				$max = self::addr2ip($max);
			}

			return true;
		}
		else
			return false;
	}

	public static function ip_match($allow, $ip) {
		if (strpos($allow, '*') === false && strpos($allow, '?') === false)
			return self::net_match($ip, $allow);
		else
			return _string::glob_match($ip, $allow, true);
	}

	public static function verify_ip($allowed, $ip) {
		foreach ($allowed as $allow) {
			if (self::ip_match($allow, $ip))
				return true;
		}
		return false;
	}


	public static function valid_ip($ip) {
			$valid_ip = 1;
			$addr = self::type($ip, $type);
			if ($type === self::IP4) {
				$long_ip = ip2long($ip);

				// reserved/unroutable IANA IPv4 addresses
				// http://www.iana.org/assignments/ipv4-address-space
				// As of 2011-02-03

				$reserved_ips = array (
					array(0x00000000	, 0x00ffffff),	// 0.0.0.0		- 0.255.255.255		0 - IANA - Local Identification [RFC1122]
					array(0x03000000	, 0x03ffffff),	// 3.0.0.0		- 3.255.255.255		3 - General Electric Company

					array(0x06000000	, 0x07ffffff),	// 6.0.0.0		- 7.255.255.255		6 - Army Information Systems Center
														//									7 - DoD Information Systems Agency Network

					array(0x09000000	, 0x0bffffff),	// 9.0.0.0		- 11.255.255.255	9 - IBM
														//									10 - IANA - Private Use [RFC1918]
														//									11 - DoD Intel Information Systems

					array(0x0d000000	, 0x0dffffff),	// 13.0.0.0		- 13.255.255.255	13 - Xerox Corporation
					array(0x0f000000	, 0x11ffffff),	// 15.0.0.0		- 17.255.255.255	15 - Hewlett-Packard Company
														//									16 - Digital Equipment Corporation
														//									17 - Apple Computer Inc.

					array(0x13000000	, 0x16ffffff),	// 19.0.0.0		- 22.255.255.255	19 - Ford Motor Company
														//									20 - Computer Sciences Corporation
														//									21 - DoD Defense Data Network
														//									22 - Defense Information Systems Agency

					array(0x19000000	, 0x1affffff),	// 25.0.0.0		- 26.255.255.255	25 - UK Ministry of Defence
														//									26 - Defense Information Systems Agency

					array(0x1c000000	, 0x1effffff),	// 28.0.0.0		- 30.255.255.255	28 - DoD DSI-North
														//									29 - Defense Information Systems Agency
														//									30 - Defense Information Systems Agency

					array(0x21000000	, 0x22ffffff),	// 33.0.0.0		- 34.255.255.255	33 - Defense Logistics Agency Systems Automation Center
														//									34 - Halliburton Company

					array(0x28000000	, 0x28ffffff),	// 40.0.0.0		- 40.255.255.255	40 - Eli Lilly and Company

					array(0x2f000000	, 0x30ffffff),	// 47.0.0.0		- 48.255.255.255	47 - Bell-Northern Research
														//									48 - Prudential Securities Inc.


					array(0x33000000	, 0x39ffffff),	// 51.0.0.0		- 57.255.255.255	51 - UK Government Department for Work and Pensions
														//									52 - E.I. duPont de Nemours and Co., Inc.
														//									53 - Cap Debis CCS
														//									54 - Merck and Co., Inc.
														//									55 - DoD Network Information Center
														//									56 - US Postal Service
														//									57 - SITA

					array(0x7f000000	, 0x7fffffff),	// 127.0.0.0	- 127.255.255.255	127 - IANA - Loopback [RFC1122]

					array(0xa9fe0000	, 0xa9feffff),	// 169.254.0.0	- 169.254.255.255	IANA - Link Local [RFC3927]
					array(0xac100000	, 0xac1fffff),	// 172.16.0.0	- 172.31.255.255	IANA - Private Use [RFC1918]

					array(0xc0000000	, 0xc00000ff),	// 192.0.0.0	- 192.0.0.255		IANA - IETF protocol assignments [RFC5735]
					array(0xc0000200	, 0xc00002ff),	// 192.0.2.0	- 192.0.2.255		IANA - TEST-NET-1 [RFC5737]
					array(0xc0586300	, 0xc05863ff),	// 192.88.99.0	- 192.88.99.255		IANA - 6to4 Relay Anycast [RFC3068]
					array(0xc0a80000	, 0xc0a8ffff),	// 192.168.0.0	- 192.168.255.255	IANA - Private Use [RFC1918]
					array(0xc6120000	, 0xc613ffff),	// 198.18.0.0	- 198.19.255.255	IANA - IPv4 Special Purpose Address Registry [RFC2544]
					array(0xc6336400	, 0xc63364ff),	// 198.51.100.0	- 198.51.100.255	IANA - TEST-NET-2 [RFC5737]
					array(0xcb007100	, 0xcb0071ff),	// 203.0.113.0	- 203.0.113.255		IANA - TEST-NET-3 [RFC5737]

					array(0xd6000000	, 0xd7ffffff),	// 214.0.0.0	- 215.255.255.255	214 - US DoD
														//									215 - US DoD

					array(0xe0000000	, 0xffffffff),	// 224.0.0.0	- 255.255.255.255	IANA - Multicast [RFC2171] / Future use [RFC1122]
				);

				foreach ($reserved_ips as $range) {
					if (($long_ip >= $range[0]) && ($long_ip <= $range[1])) {
						$valid_ip = 0;
						break;
					}
				}
			}
			elseif ($type === self::IP6) {
				// IPv6 Global Unicast Address Assignments [0]
				// http://www.iana.org/assignments/ipv6-unicast-address-assignments
				// [last updated 2008-05-13]
				$valid_nets = array(
					'2001:0000::/32',	// Teredo
//					'2001:0000::/23',	// IANA			01 Jul 99	[1]
					'2001:0200::/23',	// APNIC		01 Jul 99
					'2001:0400::/23',	// ARIN			01 Jul 99
					'2001:0600::/23',	// RIPE NCC		01 Jul 99
					'2001:0800::/23',	// RIPE NCC		01 May 02
					'2001:0A00::/23',	// RIPE NCC		02 Nov 02
					'2001:0C00::/23',	// APNIC		01 May 02	[2]
					'2001:0E00::/23',	// APNIC		01 Jan 03
					'2001:1200::/23',	// LACNIC		01 Nov 02
					'2001:1400::/23',	// RIPE NCC		01 Feb 03
					'2001:1600::/23',	// RIPE NCC		01 Jul 03
					'2001:1800::/23',	// ARIN			01 Apr 03
					'2001:1A00::/23',	// RIPE NCC		01 Jan 04
					'2001:1C00::/22',	// RIPE NCC		01 May 04
					'2001:2000::/20',	// RIPE NCC		01 May 04
					'2001:3000::/21',	// RIPE NCC		01 May 04
					'2001:3800::/22',	// RIPE NCC		01 May 04
//					'2001:3C00::/22',	// RESERVED		11 Jun 04	[3]
					'2001:4000::/23',	// RIPE NCC		11 Jun 04
					'2001:4200::/23',	// AfriNIC		01 Jun 04
					'2001:4400::/23',	// APNIC		11 Jun 04
					'2001:4600::/23',	// RIPE NCC		17 Aug 04
					'2001:4800::/23',	// ARIN			24 Aug 04
					'2001:4A00::/23',	// RIPE NCC		15 Oct 04
					'2001:4C00::/23',	// RIPE NCC		17 Dec 04
					'2001:5000::/20',	// RIPE NCC		10 Sep 04
					'2001:8000::/19',	// APNIC		30 Nov 04
					'2001:A000::/20',	// APNIC		30 Nov 04
					'2001:B000::/20',	// APNIC		08 Mar 06
					'2002:0000::/16',	// 6to4			01 Feb 01	[4]
					'2003:0000::/18',	// RIPE NCC		12 Jan 05
					'2400:0000::/12',	// APNIC		03 Oct 06
					'2600:0000::/12',	// ARIN			03 Oct 06
					'2610:0000::/23',	// ARIN			17 Nov 05
					'2620:0000::/23',	// ARIN			12 Sep 06
					'2800:0000::/12',	// LACNIC		03 Oct 06
					'2A00:0000::/12',	// RIPE NCC		03 Oct 06
					'2C00:0000::/12',	// AfriNIC		03 Oct 06
				);

				/*
				Notes:
				[1]  IANA Special Purpose Address Block [RFC4773].
				     See: http://www.iana.org/assignments/iana-ipv6-special-registry

				[2]  2001:0DB8::/32 has been assigned as a NON-ROUTABLE
				     range to be used for documentation purpose [RFC3849].

				[3]  2001:3C00::/22 is reserved for possible future allocation
				     to the RIPE NCC.

				[4]  2002::/16 is reserved for use in 6to4 deployments [RFC3056].
				*/

				$reserved_nets = array(
					'2001:DB8::/32',			// NON-ROUTABLE range to be used for documentation purpose [RFC3849]
					'2001:10::/28',				// Overlay Routable Cryptographic Hash IDentifiers (ORCHID) addresses [RFC4843]
				);

				if (!(self::verify_ip($valid_nets, $ip) && !self::verify_ip($reserved_nets, $ip)))
					$valid_ip = 0;
			}
			else
				$valid_ip = 0;

		return (bool)$valid_ip;
	}

	public static function get_ip() {
		if (isset($_SERVER['HTTP_CLIENT_IP']) && self::valid_ip($_SERVER['HTTP_CLIENT_IP']))
			return $_SERVER['HTTP_CLIENT_IP'];

		elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR']))  {
			$forwarded = explode(',',$_SERVER['HTTP_X_FORWARDED_FOR']);
			foreach($forwarded as $proxied_ip) {
				$pip = trim($proxied_ip);
				if (self::valid_ip($pip))
					return $pip;
			}
		}
		return vars::$realip;
	}

	public static function ip_port($in, &$ip, &$port, &$type, &$addr) {
			if (!preg_match('#^([0-9a-f:]+)$#i', $in, $matches) &&
				!preg_match('#^\[([0-9a-f:]+)\](?::([0-9]{1,5}))?$#i', $in, $matches) &&
				!preg_match('#^([0-9.]+)(?::([0-9]{1,5}))?$#', $in, $matches)) {
				return false;
			}

			$tip = $matches[1];
			$taddr = self::type($tip, $ttype);
			if (!$taddr) {
				return false;
			}
			$taddr6 = self::ip2addr6($tip);

			$tport = 0;
			if (isset($matches[2])) {
				$tport = (int)$matches[2];

				if ($tport < 1 || $tport > 65535) {
					return false;
				}
			}

			if (!self::valid_ip($tip)) {
				return false;
			}

			$ip_port = array($tip, $tport, $ttype, $taddr, $taddr6);

		list($ip, $port, $type, $addr, $addr6) = $ip_port;

		return $addr6;
	}
};

?>