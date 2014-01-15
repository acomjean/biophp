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
		$shortopts = '';
		$longopts  = array();
		$parsedopts= array();
		foreach ($opts as $opt) {
			if (!$opt[0]) continue;
			if (isset($opt[2]) and $opt[2]===self::ARG_REQUIRED) {
				$parsedopts[$opt[0]]['required'] = true;
				$parsedopts[$opt[0]]['info']     = $opt[1];
				$parsedopts[$opt[0]]['long']     = isset($opt[3]) ? $opt[3] : '';
				$parsedopts[$opt[0]]['default']  = true;
				$shortopts .= $opt[0] . ':';
				if ($parsedopts[$opt[0]]['long'])
					$longopts[] = $parsedopts[$opt[0]]['long'] . ':';
			} else {
				$parsedopts[$opt[0]]['required'] = false;
				$parsedopts[$opt[0]]['info']     = $opt[1];
				$parsedopts[$opt[0]]['long']     = isset($opt[3]) ? $opt[3] : '';
				$parsedopts[$opt[0]]['default']  = isset($opt[2]) ? $opt[2] : '';
				$shortopts .= $opt[0] . '::';
				if ($parsedopts[$opt[0]]['long'])
					$longopts[] = $parsedopts[$opt[0]]['long'] . '::';
			}
		}
		
		$help = self::getHelp ($parsedopts, $desc);
		
		$options = getopt ($shortopts, $longopts);
		$options['--help'] = $help;
		foreach ($parsedopts as $k => $info) {
			if ($info['long'] and $options[$info['long']]) {
				$options[$k] = $options[$info['long']];
				unset ($options[$info['long']]);
			}
			if ($info['required'] and !isset($options[$k])) { //requirement check
				fwrite(STDERR, self::color("\nError: Option -$k is required.\n", "lightred"));
				fwrite(STDERR, $help);
				exit ();
			}
			if (!isset($options[$k]) and $info['default']!==self::ARG_REQUIRED)
				$options[$k] = $info['default'];
		}

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
		$ret .= self::color("Usage: {$argv[0]} <options>\n", "yellow");
		$ret .= "  " . str_replace("\n", "\n  ", $desc);
		$ret .= "\n\n";
		$ret .= "Options:\n";
		foreach ($parsedopts as $k => $info) {
			if ($info['default'] and !$info['required'])
				$ret .= self::color("  -$k", "lightgreen");
			else
				$ret .= self::color("  -$k", "lightcyan");
			$ret .= self::color(str_pad($info['long'] ? ", --{$info['long']}" : " ", 15), "lightcyan");
			$ret .= $info['info'] . "\n";
			if ($info['default'] and !$info['required'])
				$ret .= self::color("                   Default: " . $info['default'] . "\n", "lightgreen");
			//$ret .= "  --------------------------------------------------------------------------------------------\n";
		}
		$ret .= "\n";
		return $ret;
	}

}