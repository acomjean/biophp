<?php
namespace biophp;

class utils {

	const ARG_REQUIRED = true;

	/**
	 * @desc: parse the arguments and print the usage if arguments are not legal
	 * @param: $opts, the options
	 *         array(
	 *             array(short opt, description, self::ARG_REQUIED|default value, long opt)
	 *             ...
	 *         )
	 *         E.g.: array( array('i', 'input file', \biophp\utils::ARG_REQUIED, 'input'), ... )
	 *               array( array('i', 'input file', './input.txt', 'input'), ... )
	 * @param: $desc, the description of the script
	 * @return: options with value, short opt as key
	 *         array('i'=>'something', ...)
	 */
	static public function args ($opts, $desc = "") {
		
		$parsedopts= array();
		foreach ($opts as $opt) {
			$flag = $opt[0];
			if (empty($flag)) $flag = $opt[3];
			$info = $opt[1];
			$deft = isset($opt[2]) ? $opt[2] : '';
			$long = isset($opt[3]) ? $opt[3] : '';
			
			$parsedopts[$flag]['info'] = $info;
			$parsedopts[$flag]['deft'] = $deft;
			$parsedopts[$flag]['long'] = $long;
			$parsedopts[$flag]['rqed'] = $deft === self::ARG_REQUIRED;
			
		}
		
		$help = self::getHelp ($parsedopts, $desc);
		$options = self::getopts ();
		$options['-help'] = $help;
		
		foreach ($parsedopts as $k => $info) {
			if ($k == '-rest') { 
				if ($info['rqed'] and empty($options['-rest'])) 
					$options['-error'][] = $info['info'] . " is required.";
			} else {
				if ($info['long'] and isset($options[$info['long']])) {
					$options[$k] = $options[$info['long']];
					unset ($options[$info['long']]);
				}
				if (!isset($options[$k]) and !$info['rqed']) {
					$options[$k] = $info['deft'];
				}
				if ($info['rqed'] and !isset($options[$k])) { //requirement check
					$options['-error'][] = "Option [". (strlen($k)==1?"-":"--") ."$k] is required.";
				}			
			}
		}
		
		if (!empty($options['-error'])) {
			
			fwrite(STDERR, self::color("\n ERROR:", "lightred") . PHP_EOL);
			fwrite(STDERR, self::color(" ------", "lightred") . PHP_EOL);
			foreach ($options['-error'] as $error) {
				fwrite(STDERR, "   $error\n");
			}
			fwrite(STDERR, PHP_EOL);
			fwrite(STDERR, $help);
			exit ();
		}
		unset($options['-error']);

		return $options;
	}

	/**
	 * @desc: echo a colored string to the console
	 * @param: $string, the string
	 * @param: $fgcolor, the foreground color
	 *         Supported colors: fg:black, fg:darkgray, fg:blue, fg:lightblue, fg:green, fg:lightgreen, fg:cyan
	 *         fg:lightcyan, fg:red, fg:lightred, fg:purple, fg:lightpurple, fg:brown, fg:yellow, fg:lightgray, fg:default
	 * @param: $bgcolor, the background color
	 *         Supported colors: bg:red, bg:green, bg:yellow, bg:blue, bg:magenta, bg:cyan, bg:lightgray, bg:darkgray, 
	 *         bg:lightred, bg:lightgreen, bg:lightyellow, bg:lightblue, bg:lightmagenta, bg:lightcyan, bg:white
	 * @return: the colored string
	 */
	static public function color($string, $fgcolor, $bgcolor=null) {
		$fg = '';
		switch ($fgcolor) {
			case 'black':       $fg = "\033[0;30m"; break;
			case 'dark gray':
			case 'darkgray':    $fg = "\033[1;30m"; break;
			
			case 'brown':       $fg = "\033[0;33m"; break;
			case 'yellow':      $fg = "\033[1;33m"; break;
			
			case 'light gray':
			case 'lightgray':   $fg = "\033[0;37m"; break;
			case 'white':       $fg = "\033[1;37m"; break;
			
			case 'red':         $fg = "\033[0;31m"; break;
			case 'light red':
			case 'lightred':    $fg = "\033[1;31m"; break;
			
			case 'purple':      $fg = "\033[0;35m"; break;
			case 'light purple':
			case 'lightpurple': $fg = "\033[1;35m"; break;
			
			case 'blue':        $fg = "\033[0;34m"; break;
			case 'light blue':
			case 'lightblue':   $fg = "\033[1;34m"; break;
			
			case 'green':       $fg = "\033[0;32m"; break;
			case 'light green':
			case 'lightgreen':  $fg = "\033[1;32m"; break;
			
			case 'cyan':        $fg = "\033[0;36m"; break;
			case 'light cyan':
			case 'lightcyan':   $fg = "\033[1;36m"; break;
			
			case 'default':	    
			default:            $fg = "\033[0;39m"; break;
		}
		$bg = '';
		switch ($bgcolor) {
			case 'black':        $bg = "\033[40m";   break;
			case 'red':          $bg = "\033[41m";   break;
			case 'green':        $bg = "\033[42m";   break;
			case 'yellow':       $bg = "\033[43m";   break;
			case 'blue':         $bg = "\033[44m";   break;
			case 'magenta':      $bg = "\033[45m";   break;
			case 'cyan':         $bg = "\033[46m";   break;
			case 'lightgray':      
			case 'light gray':   $bg = "\033[47m";   break;
			case 'darkgray':      
			case 'dark gray':    $bg = "\033[100m";  break;
			case 'lightred':      
			case 'light red':    $bg = "\033[101m";  break;
			case 'lightgreen':      
			case 'light green':  $bg = "\033[102m";  break;
			case 'lightyellow':      
			case 'light yellow': $bg = "\033[103m";  break;
			case 'lightblue':      
			case 'light blue':   $bg = "\033[104m";  break;
			case 'lightmagenta':      
			case 'light magenta':$bg = "\033[105m";  break;
			case 'lightcyan':      
			case 'light cyan':   $bg = "\033[106m";  break;    
			case 'white':        $bg = "\033[107m";  break;
			case 'default':      $bg = "\033[49m";   break;
		}
		return "$fg$bg$string\033[0m";
	}

	static private function getHelp (&$parsedopts, $desc) {
		global $argv;
		$ret = "";
		if (isset($parsedopts['-rest'])) {
			$usage = $parsedopts['-rest']['long']=='first' 
				? "USAGE: {$argv[0]} {$parsedopts['-rest']['info']} [OPTIONS]"
				: "USAGE: {$argv[0]} [OPTIONS] {$parsedopts['-rest']['info']}";
		} else {
			$usage = "USAGE: {$argv[0]} <OPTIONS>";
		}
		$ret .= self::color(" $usage", "yellow") . PHP_EOL;
		$ret .= self::color(" ". str_repeat('-', strlen($usage)), "yellow") . PHP_EOL;
		$ret .= "   " . str_replace("\n", "\n   ", $desc);
		$ret .= "\n\n";
		$ret .= self::color("\n OPTIONS:", "lightgreen") . PHP_EOL;
		$ret .= self::color(" --------", "lightgreen") . PHP_EOL;
		$maxlen = 0;
		$optfmt = array();
		foreach ($parsedopts as $k => $info) {
			if ($k=='-rest') continue;
			$opt   = strlen($k) == 1 ? "-$k" . ($info['long'] ? ", --{$info['long']}" : "") : "--$k";
			$color = ($info['deft'] and !$info['rqed']) ? 'default' : 'lightblue';
			
			if (strlen($opt) > $maxlen) 
				$maxlen = strlen($opt);
			
			$optfmt[$k]['opt']   = $opt;
			$optfmt[$k]['color'] = $color;
			$optfmt[$k]['info']  = $info['info'];
			$optfmt[$k]['deft']  = $color == 'default' ? $info['deft'] : "";
		}
		$padlen = max($maxlen, 20);
		foreach ($optfmt as $k => $fmt) {
			$ret .= self::color(str_pad("   {$fmt['opt']}", $padlen), $fmt['color']);
			$ret .= self::color(str_replace("\n", "\n" . str_repeat(" ", $padlen), $fmt['info']), $fmt['color']) . PHP_EOL;
			if ($fmt['deft']) {
				$ret .= str_repeat(" ", $padlen) . self::color("DEFAULT: {$fmt['deft']}", $fmt['color']) . PHP_EOL;
			}
		}
		$ret .= "\n";
		return $ret;
	}
	
	static public function getopts() {
		global $argv, $argc;
		$ret = array();
		$ret['-error'] = array();
		$ret['-rest'] = array();
		for ($i=1; $i<$argc; $i++) {
			$arg = $argv[$i];
			if (strpos($arg, '-') === 0) {
				switch (true) {
					case preg_match("/^--([\w-]+)=([\"']?)(.+)\\2$/", $arg, $m) or
						 preg_match("/^-([\w-]+)=([\"']?)(.+)\\2$/", $arg, $m):
						$ret[$m[1]] = $m[3];
						break;
					case preg_match("/^--([\w-]+)$/", $arg, $m) or
						 preg_match("/^-(\w{1})$/", $arg, $m):
						if ($i==$argc-1) { // the last arg
							$ret[$m[1]] = true;
						} else {
							$argnext = $argv[$i+1];
							if (strpos($argnext, '-') === 0) {
								$ret[$m[1]] = true;
							} else {
								$ret[$m[1]] = $argnext;
								++$i;
							}
						}
						break;
					case preg_match("/^-(\w)([\"']?)(.+)\\2$/", $arg, $m):
						$ret[$m[1]] = $m[3];
						break;
					default:
						$ret['-error'][] = "Unknown option [$arg].";
				}
			} else {
				$ret['-rest'][] = $arg;
			}
		}
		return $ret;
	}

}