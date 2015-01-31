<?php
/*
* BtiTracker v1.5.0 is a php tracker system for BitTorrent, easy to setup and configure.
* This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
* Updated and Maintained by Yupy.
* Copyright (C) 2004-2014 Btiteam.org
*/

require_once(INCL_PATH . 'define_bits.php');

class misc {
        const MB = 1024;
        
	const MINUTE = 60;
	const HOUR = 3600;
	const DAY = 86400;
	const WEEK = 604800;
	const MONTH = 2629744;
	const YEAR = 31556926;
	
	const PAGER_SHOW_PAGES = BIT_1;
	const PAGER_NO_SEPARATOR = BIT_2;
	const PAGER_LAST_PAGE_DEFAULT = BIT_3;
	const PAGER_NO_NAV = BIT_4;
	const PAGER_ONLY_PAGES = BIT_5;

	public static function makesize($Size, $Levels = 2) {
		$Units = array(' BiT', ' KiB', ' MiB', ' GiB', ' TiB', ' PiB', ' EiB', ' ZiB', ' YiB');
		$Size = (double)$Size;
		for ($Steps = 0; abs($Size) >= self::MB; $Size /= self::MB, $Steps++) {
		}
		if (func_num_args() == 1 && $Steps >= 4) {
			$Levels++;
		}
		return number_format($Size, $Levels) . $Units[$Steps];
	}
	
        public static function time_ago($timestamp) {
                $timestamp = (int)$timestamp;
                $current_time = vars::$timestamp;
                $diff = $current_time - $timestamp;
		
                //intervals in seconds
                $intervals = array (
                       'year' => selft::YEAR, 'month' => self::MONTH, 'week' => self::WEEK, 'day' => self::DAY, 'hour' => self::HOUR, 'minute'=> self::MINUTE
                );
		
                //now we just find the difference
                if ($diff == 0) {
                       return 'just now';
                }
		
                if ($diff < self::MINUTE) {
                        return $diff == 1 ? $diff . ' second ago' : $diff . ' seconds ago';
                }
		
                if ($diff >= self::MINUTE && $diff < $intervals['hour']) {
                        $diff = floor($diff / $intervals['minute']);
                        return $diff == 1 ? $diff . ' minute ago' : $diff . ' minutes ago';
                }
		
                if ($diff >= $intervals['hour'] && $diff < $intervals['day']) {
                        $diff = floor($diff / $intervals['hour']);
                        return $diff == 1 ? $diff . ' hour ago' : $diff . ' hours ago';
                }
		
                if ($diff >= $intervals['day'] && $diff < $intervals['week']) {
                        $diff = floor($diff / $intervals['day']);
                        return $diff == 1 ? $diff . ' day ago' : $diff . ' days ago';
                }
		
                if ($diff >= $intervals['week'] && $diff < $intervals['month']) {
                        $diff = floor($diff / $intervals['week']);
                        return $diff == 1 ? $diff . ' week ago' : $diff . ' weeks ago';
                }
		
                if ($diff >= $intervals['month'] && $diff < $intervals['year']) {
                       $diff = floor($diff / $intervals['month']);
                       return $diff == 1 ? $diff . ' month ago' : $diff . ' months ago';
                }
		
                if ($diff >= $intervals['year']) {
                        $diff = floor($diff / $intervals['year']);
                        return $diff == 1 ? $diff . ' year ago' : $diff . ' years ago';
                }
        }
	
        public static function make_utf8($Str) {
	        if ($Str != '') {
		         if (self::is_utf8($Str)) {
			          $Encoding = 'UTF-8';
		         }
		         if (empty($Encoding)) {
			          $Encoding = mb_detect_encoding($Str, 'UTF-8, ISO-8859-1');
		         }
		         if (empty($Encoding)) {
			          $Encoding = 'ISO-8859-1';
		         }
		         if ($Encoding == 'UTF-8') {
			          return $Str;
		         } else {
			          return @mb_convert_encoding($Str, 'UTF-8', $Encoding);
		         }
	        }
        }
	
        public static function is_utf8($Str) {
	        return preg_match('%^(?:
		    [\x09\x0A\x0D\x20-\x7E]			 // ASCII
		    | [\xC2-\xDF][\x80-\xBF]			// non-overlong 2-byte
		    | \xE0[\xA0-\xBF][\x80-\xBF]		// excluding overlongs
		    | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2} // straight 3-byte
		    | \xED[\x80-\x9F][\x80-\xBF]		// excluding surrogates
		    | \xF0[\x90-\xBF][\x80-\xBF]{2}	 // planes 1-3
		    | [\xF1-\xF3][\x80-\xBF]{3}		 // planes 4-15
		    | \xF4[\x80-\x8F][\x80-\xBF]{2}	 // plane 16
		    )*$%xs', $Str
	       );
        }
	
	public static function pager($rpp, $count, $href, $options = 0, $pagename = 'page') {
		$show_pages		= (bool)($options & self::PAGER_SHOW_PAGES);
		$no_sep				= (bool)($options & self::PAGER_NO_SEPARATOR);
		$lastpagedefault	= (bool)($options & self::PAGER_LAST_PAGE_DEFAULT);
		$no_nav				= (bool)($options & self::PAGER_NO_NAV);
		$only_pages			= (bool)($options & self::PAGER_ONLY_PAGES);

		$pages = ceil($count / $rpp);

		if ($only_pages)
			$dpage = ceil($pages / 2);
		else {
			$pagedefault = $lastpagedefault ? $pages : 1;

			if (isset($_GET[$pagename])) {
				$dpage = 0 + $_GET[$pagename];
				if ($dpage < 1)
					$dpage = $pagedefault;
				elseif ($dpage > $pages)
					$dpage = $pages;
			}
			else
				$dpage = $pagedefault;
		}

		$page = $dpage - 1;

		$pager = $frontpager = $backpager = $pagerstr = '';

		$startp = "<p class='browse_changepage'>";
		$endp = "</p>";
		$spacep = $no_sep ? "&nbsp;" : "&nbsp;|&nbsp;";
		$dotp = "...";
		$sepp = "&nbsp;-&nbsp;";
		$midl = $only_pages ? " class='g_bllink'" : " class='g_bblink'";
		$dotspace = $show_pages ? 5 : 2;
		$mp = $pages - 1;

		$as = "&lt;&lt;&nbsp;Prev";

		if ($page >= 1) {
			$frontpager .= '<a href="'.$href.$pagename.'='.($dpage - 1).'"'.$midl.'>';
			$frontpager .= $as;
			$frontpager .= '</a>';
		}
		else
			$frontpager .= '<b>'.$as.'</b>';

		$as = "Next&nbsp;&gt;&gt;";
		if ($page < $mp && $mp >= 0) {
			$backpager .= '<a href="'.$href.$pagename.'='.($dpage + 1).'"'.$midl.'>';
			$backpager .= $as;
			$backpager .= '</a>';
		}
		else
			$backpager .= '<b>'.$as.'</b>';

		if ($count) {
			$pagerarr = array();
			$dotted = 0;
			$dotend = $pages - $dotspace;
			$curdotend = $page - $dotspace;
			$curdotstart = $page + $dotspace;
			for ($i = 0; $i < $pages; $i++) {
				if (($i >= $dotspace && $i <= $curdotend) || ($i >= $curdotstart && $i < $dotend)) {
					if (!$dotted) {
						$pagerarr[] = $dotp;
						$dotted = 1;
					}
					continue;
				}
				$dotted = 0;
				$start = $i * $rpp + 1;
				$end = $start + $rpp - 1;
				if ($end > $count)
					$end = $count;

				$text = $show_pages ? ($i + 1) : $start.$sepp.$end;

				if ($only_pages || $i != $page)
					$pagerarr[] = '<a href="'.$href.$pagename.'='.($i + 1).'"'.$midl.'>'.$text.'</a>';
				else
					$pagerarr[] = '<b>'.$text.'</b>';
			}

			$pagerstr = implode($spacep, $pagerarr);
		}
		
		$start = $page * $rpp;

		$pager = (!$only_pages ? $startp : '').(!$no_nav ? $frontpager.$spacep : '').$pagerstr.(!$no_nav ? $spacep.$backpager : '').(!$only_pages ? $endp : '');

		if ($only_pages)
			return $pager;
		else
			return array($pager, 'LIMIT '.$start.','.$rpp);
	}

}

?>
