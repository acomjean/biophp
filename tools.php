#!/usr/bin/env php
<?php
if ($argc==0) {
	echo usage();
	exit ();
}
require_once (__DIR__ . '/biophp.php');
$subcommand = $argv[1];
define (NORMAL_COLOR, 'default');
define (HIGH_COLOR, 'lightpurple');

switch ($subcommand) {
	case 'help':
		echo usage();
		break;
	case 'usage':
		echo "\nUsage for biophp :\n\n";
		echo "biophp\n";
		$namespaces = $argv[2];
		$subfiles = glob (__DIR__ . '/*.class.php');

		// list all sub-namespaces
		if ($argc == 2 or ($namespaces=='biophp')) {

			foreach ($subfiles as $subfile) {
				$filename = pathinfo ($subfile, PATHINFO_FILENAME);
				$subname  = substr($filename, 0, strlen($filename) - 6);
				echo \biophp\utils::color("  [+] " . $subname . "\n", NORMAL_COLOR);
			}
		} else {
			// parse namespaces
			$ns = explode('.', $namespaces);

			$class = array_shift($ns);
			if ($class=='biophp')
				$class = array_shift($ns);
			$flag  = false;
			foreach ($subfiles as $subfile) {
				$filename = pathinfo ($subfile, PATHINFO_FILENAME);
				$subname  = substr($filename, 0, strlen($filename) - 6);
				if ($subname == $class) {
					echo \biophp\utils::color("  [-] " . $subname . "\n", HIGH_COLOR);
					$flag = true;
					require_once($subfile); 
					$fc = new ReflectionClass("biophp\\$class");
					if ($class == 'constants') {
						$constants = $fc->getConstants();
						$constclasses = parseConstants(__DIR__ . "/$class");
						if (sizeof($ns) == 0) {
							foreach ($constants as $constname => $constvalue) {
								echo \biophp\utils::color("      [+] :: " . $constname . "\n", NORMAL_COLOR);
							}
							foreach ($constclasses as $classname=>$consts) {
								echo \biophp\utils::color("      [+] \\  " . $classname . "\n", NORMAL_COLOR);
							}
						} else {
							$constOrClass = array_shift($ns);
							if (isset($constants[$constOrClass])) {
								if (sizeof($ns)) {
									error( "Error: wrong path [biophp.$class.$constOrClass.{$ns[0]}]");
								}
								$constcomments = parseComments($subfile, '          ');
								foreach ($constants as $constname => $constvalue) {
									if ($constOrClass!=$constname)
										echo \biophp\utils::color("      [+] :: " . $constname . "\n", NORMAL_COLOR);
									else {
										echo \biophp\utils::color("      [-] :: " . $constname . "\n", HIGH_COLOR);
										echo \biophp\utils::color($constcomments[$constname]['info'], HIGH_COLOR);
										echo \biophp\utils::color("          | @equals: " . $constvalue . "\n", HIGH_COLOR);
									}	
								}
								foreach ($constclasses as $classname=>$consts) {
									echo \biophp\utils::color("      [+] \\  " . $classname . "\n", NORMAL_COLOR);
								}
							} elseif (isset($constclasses[$constOrClass])) {
								foreach ($constants as $constname => $constvalue) {
									echo \biophp\utils::color("      [+] :: " . $constname . "\n", NORMAL_COLOR);
								}
								foreach ($constclasses as $classname=>$consts) {
									if ($classname != $constOrClass) 
										echo \biophp\utils::color("      [+] \  " . $classname . "\n", NORMAL_COLOR);
									else {
										echo \biophp\utils::color("      [-] \  " . $classname . "\n", HIGH_COLOR);
										$comments = parseComments(__DIR__ . "/$class/$constOrClass.class.php", '              ');
										if ((sizeof($ns) and !isset($comments[$ns[0]])) or sizeof($ns)>1) {
											error( "Error: wrong path [biophp.$class.$constOrClass.".implode(".", $ns)."]");
										}
										foreach ($comments as $key=>$value) {
											if ($key!=$ns[0]) 
												echo \biophp\utils::color("          [+] :: " . $key . "\n", NORMAL_COLOR);
											else {
												echo \biophp\utils::color("          [-] :: " . $key . "\n", HIGH_COLOR); 
												echo \biophp\utils::color($value['info'], HIGH_COLOR); 
												if (isset($consts[$key]))
													echo \biophp\utils::color("              | @equals: " . $consts[$key] . "\n", HIGH_COLOR); 
											}
										}
									}
								}
							} else {
								error( "Error: wrong path [biophp.$class.$constOrClass]");
							}
						}
					} else {
					
						if (sizeof($ns)==0) {
							foreach ($fc->getMethods() as $method) {
								if (!$method->isPublic()) continue;
								echo \biophp\utils::color("      [+] :: " . $method->name . "\n", NORMAL_COLOR);
							}
						} else {
							$subclass = array_shift($ns);
							$subflag = false;
							foreach ($fc->getMethods() as $method) {
								if ($subclass == $method->name) {
									$subflag = true;
									$toprint = ""; 
									echo \biophp\utils::color("      [-] :: " . $method->name . "\n", HIGH_COLOR);
									$comments = parseComments ($subfile, '          ');
									if (sizeof($ns)==0) { 
										echo \biophp\utils::color($comments[$method->name]['info'], HIGH_COLOR);
										if (isset($comments[$method->name]['sons']) and sizeof($comments[$method->name]['sons'])>0) { 
											foreach ($comments[$method->name]['sons'] as $son=>$_) { 
												echo \biophp\utils::color("          [+] -> $son\n", NORMAL_COLOR);
											}
										}
									} else {
										$i = 0;
										$path = "biophp.{$method->name}";
										while ($subclass) {
											$subclass1 = array_shift($ns);
											$path .= ".$subclass1";
											$comments1 = null;
											if (isset($comments[$subclass]['sons']) and sizeof($comments[$subclass]['sons'])>0) {
												$ssflag = false;
												foreach ($comments[$subclass]['sons'] as $k => $v) {
													if ($subclass1 == $k) {
														$ssflag = true;
														echo \biophp\utils::color(str_repeat(' ', 6+($i+1)*4) . "[-] -> $k\n", HIGH_COLOR);
														if (sizeof($ns)==0)
															echo \biophp\utils::color($v['info'], HIGH_COLOR);
														$comments1 = array($subclass1=>$v);
													} else { 
														$toprint = \biophp\utils::color(str_repeat(' ', 6+($i+1)*4) . "[+] -> $k\n", NORMAL_COLOR) . $toprint;
													}
												}
												if ($subclass1 and !$ssflag) {
													error( "Error: wrong path [$path]");
												}
												$comments = $comments1;
											}
											$subclass = $subclass1;
											$i ++;
										}
									}
									echo $toprint;
								} else {
									echo \biophp\utils::color("      [+] :: " . $method->name . "\n", NORMAL_COLOR);
								}
							}
							
						
							if ($subclass and !$subflag) {
								error( "Error: wrong path [biophp.$class.$subclass]");
							}
						}
					}
				} else {
					echo \biophp\utils::color("  [+] " . $subname . "\n", NORMAL_COLOR);
				}
			}
			if (!$flag) {
				error( "Error: wrong path [biophp.$class]");
			}
		}
		break;
	case 'constupdate':
		if ($argc < 4) {
			echo "\nError: not enough arguments.\n";
			echo usage();
			exit ();
		}
		$oldfile = $argv[2];
		$newfile = $argv[3];
		
		$one2one = (isset($argv[4]) and $argv[4] === 'true');
		$old     = \biophp\parser::txt($oldfile, 'k:1,v:2');
		$allold  = array();
		while ($o = $old->read()) 
			$allold[$o['k']] = $one2one ? $o['v'] : explode('|', $o['v']);
		
		$new     = \biophp\parser::txt($newfile, 'k:1,v:2');
		while ($newobj = $new->read()) {
			if (!isset($allold[$newobj['k']])) {
				fwrite(STDERR, "- Adding " . $newobj['k'] . ": " . $newobj['v'] . "\n"); 
				$allold[$newobj['k']] = array($newobj['v']);
			} else {
				if ($one2one) {
					fwrite(STDERR, "- Replacing " . $newobj['k'] . ": " . $allold[$newobj['k']] . " -> " . $newobj['v'] . "\n"); 
					$allold[$newobj['k']] = $newobj['v'];
				} else {
					$oldvals = $allold[$newobj['k']];
					$newvals = explode('|', $newobj['v']);
					fwrite(STDERR, "- Combining " . $newobj['k'] . ": " . implode("|", $oldvals) . " -> ");
					$allold[$newobj['k']] = array_unique(array_merge($oldvals, $newvals));
					fwrite(STDERR, implode("|", $allold[$newobj['k']]) . "\n"); 
				}
			}
		}
		unset($new); unset($old); // release the file
		$gz = gzopen ($oldfile, 'w9');
		foreach ($allold as $key=>$val) 
			gzwrite($gz, "$key\t" . implode("|", (array)$val) . "\n");
		gzclose($gz);
		break;
	default:
		echo "\nError: unknown subcommand: $subcommand\n";
		echo usage();
		break;
}
echo "\n";

// functions

function parseComments ($file, $placeholder, $class='') {
	$contents = file_get_contents($file);
	if (!$class) list($class) = explode('.', pathinfo($file, PATHINFO_FILENAME));
	$start = strpos($contents, "\nclass $class ");
	$contents = substr($contents, $start, strpos($contents, "\n}", $start));
	preg_match_all(
		"#/\*\*([\s\S]+?)\*/\s+(?:static\s+public\s+function\s*&|static\s+public\s+function|public\s+static\s+function\s*&|public\s+static\s+function|public\s+function|public\s+function\s*&|const)\s*([\w_]+?)(?:\s|\(|=)#", 
		$contents, $m, PREG_SET_ORDER);
	$ret = array();
	foreach ($m as $desc) {
		$func = $desc[2];
		$info = $desc[1];
		if (preg_match("#\n\s*\* @import:\s+([\w.]+)\s*$#", $info, $o)) {
			$info = str_replace(trim($o[0]), "", $info);
			$information = preg_replace(array("#(^|\n)\s*\* @#", "#\n\s*\*#"), array("$1$placeholder| @", "\n$placeholder|"), $info);
			$importfile = str_replace(".class.php", "/{$o[1]}", $file);
			$ret[$func]['sons'] = parseComments($importfile, $placeholder . '    ');
		} elseif (preg_match("#\n\s*\* @new:\s+(\w+)\s*$#", $info, $n)) { 
			$info = str_replace(trim($n[0]), "", $info);
			$information = preg_replace(array("#(^|\n)\s*\* @#", "#\n\s*\*#"), array("$1$placeholder| @", "\n$placeholder|"), $info);
			$ret[$func]['sons'] = parseComments($file, $placeholder . '    ', $n[1]);
		} else {
			$information = preg_replace(array("#(^|\n)\s*\* @#", "#\n\s*\*#"), array("$1$placeholder| @", "\n$placeholder|"), $info);
		}
		$information = preg_replace('/fg:(\w+)(?=,|\n)/e', "\biophp\utils::color('$1', '$1')", $information);
		$information = preg_replace('/bg:(\w+)(?=,|\n)/e', "\biophp\utils::color('$1', '', '$1')", $information);
		$ret[$func]['info'] = rtrim($information) . "\n";
	}
	return $ret;
}

function parseConstants ($path) {
	$ret = array();
	foreach (glob("$path/*.class.php") as $file) {
		require_once($file);
		list($name,) = explode('.', pathinfo($file, PATHINFO_FILENAME)); 
		$obj = new ReflectionClass("\biophp\constants\\$name");
		$ret[$name] = $obj->getConstants();
	}
	return $ret;
}

function error ($msg) {
	echo "\n" . \biophp\utils::color($msg, '', 'lightred') . "\n\n";
	exit();
}

function usage () {
	global $argv;
	$ret = "\n";
	$ret .= "Usage: {$argv[0]} <Subcommand> <Options>\n\n";
	$ret .= "Subcommand:\n\n";
	$ret .= "  help          Print this help\n";
	$ret .= "  usage         Get the usage of biophp\n";
	$ret .= "  constupdate   Update the constants with a new file\n\n";
	$ret .= "Options:\n\n";
	$ret .= "  <namespace/class/function/constant>\n";
	$ret .= "      Only for subcommand usage, terms are connected by dot (.) \n";
	$ret .= "      Example: {$argv[0]} usage parser.txt\n\n";
	$ret .= "  <Constants data file> <New file to update> <One2one=false>\n";
	$ret .= "      Only for subcommand constupdate\n";
	$ret .= "      Constants data file: The constants data file. \n";
	$ret .= "               E.g.: constants/genes/human/id-Symbol.txt.gz\n";
	$ret .= "      New file to update: The new id-xref file, using TAB as separator\n";
	$ret .= "      One2one: whether the xref value is unique for a gene.\n";
	$ret .= "               If set to true, the old value will be replaced by the new value.\n";
	$ret .= "               Otherwise, the values will be combined. Defaut: false\n\n";
	return $ret;
}