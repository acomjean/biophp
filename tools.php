#!/usr/bin/env php
<?php
if ($argc==0) {
	echo usage();
	exit ();
}
require_once (__DIR__ . '/biophp.php');
$subcommand = $argv[1];
define ('NORMAL_COLOR', 'default');
define ('HIGH_COLOR', 'lightpurple');

switch ($subcommand) {
	case 'help':
		echo usage();
		break;
	case 'usage':
		echo "\nUsage for biophp :\n\n";
		echo "biophp\n";
		$namespaces = @$argv[2];
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
											if ($key!=@$ns[0]) 
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
	case 'update':
		$cmd = parseAddUpdateCommands();
		$db = new SQLite3(__DIR__ . "/constants/{$cmd['t']}/{$cmd['s']}.sqlite", SQLITE3_OPEN_READWRITE);
		if ($cmd['m'] == 'replace') {
			$db->exec("DELETE FROM {$cmd['n']}");
			echo "{$cmd['n']} truncated!\n";
			
			if ($cmd['c'] != 'id') {			
				$transformer = array();
				
				$ret = $db->query ("SELECT id,name FROM {$cmd['c']}");
				while ($row = $ret->fetchArray(SQLITE3_ASSOC)) {
					$transformer[$row['name']] = $row['id'];
				}
			}
			
			$sql = "INSERT INTO {$cmd['n']} \n";
			$f = fopen($cmd['datafile'], 'r');
			$i = 0; $k = 0;
			while (!feof($f)) {
				$line = trim(fgets($f));
				if (!$line) continue;
				$tmp = explode("\t", $line);
				
				if ($cmd['c'] != 'id') {
					if (!isset($transformer[$tmp[0]])) continue;
					$tmp[0] = $transformer[$tmp[0]];
				}
				$tmp[0] = $db->escapeString($tmp[0]);
				$tmp[1] = $db->escapeString($tmp[1]);
				
				$sql .= $i++ == 0
					? "      SELECT '{$tmp[0]}' AS id, '{$tmp[1]}' AS name \n"
					: "UNION SELECT '{$tmp[0]}', '{$tmp[1]}' \n";
				
				if ($i % 500 == 0) {
					$db->exec($sql);
					$sql = "INSERT INTO {$cmd['n']} \n";
					$i = 0;
				}
				
				$k ++;
			}
			fclose($f);
			if ($sql != "INSERT INTO {$cmd['n']} \n") {
				$db->exec($sql);
			}
			
			echo "$k records added!\n";
			
		} else { // combine
			
			if ($cmd['c'] != 'id') {			
				$transformer = array();
				
				$ret = $db->query ("SELECT id,name FROM {$cmd['c']}");
				while ($row = $ret->fetchArray(SQLITE3_ASSOC)) {
					$transformer[$row['name']] = $row['id'];
				}
			}
			
			$f = fopen($cmd['datafile'], 'r');
			while (!feof($f)) {
				$line = trim(fgets($f));
				if (!$line) continue;
				$tmp = explode("\t", $line);
				
				if ($cmd['c'] != 'id') {
					if (!isset($transformer[$tmp[0]])) continue;
					$tmp[0] = $transformer[$tmp[0]];
				}
				$tmp[0] = $db->escapeString($tmp[0]);
				$tmp[1] = $db->escapeString($tmp[1]);
				
				if ($cmd['o']) { // one-to-one, then just replace
					$ret = $db->querySingle("SELECT name FROM {$cmd['n']} WHERE id='{$tmp[0]}'");
					if (!$ret) {
						$db->exec("INSERT INTO {$cmd['n']} (id,name) VALUES('{$tmp[0]}', '{$tmp[1]}')");
						echo "Record ({$tmp[0]}, {$tmp[1]}) added\n";
					} elseif (strtolower($ret) != strtolower($tmp[1])) {
						$db->exec("UPDATE {$cmd['n']} SET name = '{$tmp[1]}' WHERE id='{$tmp[0]}'");
						echo "Record {$tmp[0]} updated from $ret to {$tmp[1]}\n";
					}
				} else { // add relation if not exists
					$ret = $db->querySingle("SELECT id FROM {$cmd['n']} WHERE id='{$tmp[0]}' AND name='{$tmp[1]}'");
					if (!$ret) {
						$db->exec("INSERT INTO {$cmd['n']} (id,name) VALUES('{$tmp[0]}', '{$tmp[1]}')");
						echo "Record ({$tmp[0]}, {$tmp[1]}) added\n";
					}
				}
			}
			fclose($f);
		}
		
		break;
	case 'add':
		$cmd = parseAddUpdateCommands();
		$db = new SQLite3(__DIR__ . "/constants/{$cmd['t']}/{$cmd['s']}.sqlite", SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);
		$db->exec("CREATE TABLE IF NOT EXISTS {$cmd['n']} (id CHAR(50) COLLATE NOCASE, name CHAR(50) COLLATE NOCASE)");
		$db->exec("CREATE INDEX IF NOT EXISTS index_{$cmd['n']}_id ON {$cmd['n']}(id COLLATE NOCASE)");
		$db->exec("CREATE INDEX IF NOT EXISTS index_{$cmd['n']}_name ON {$cmd['n']}(name COLLATE NOCASE)");
		
		if ($cmd['c'] != 'id') {			
			$transformer = array();
			
			$ret = $db->query ("SELECT id,name FROM {$cmd['c']}");
			while ($row = $ret->fetchArray(SQLITE3_ASSOC)) {
				$transformer[$row['name']] = $row['id'];
			}
		}
			
		$sql = "INSERT INTO {$cmd['n']} \n";
		$f = fopen($cmd['datafile'], 'r');
		$i = 0; $k = 0;
		while (!feof($f)) {
			$line = trim(fgets($f));
			if (!$line) continue;
			$tmp = explode("\t", $line);
			
			if ($cmd['c'] != 'id') {
				if (!isset($transformer[$tmp[0]])) continue;
				$tmp[0] = $transformer[$tmp[0]];
			}
			$tmp[0] = $db->escapeString($tmp[0]);
			$tmp[1] = $db->escapeString($tmp[1]);
			
			$sql .= $i++ == 0
				? "      SELECT '{$tmp[0]}' AS id, '{$tmp[1]}' AS name \n"
				: "UNION SELECT '{$tmp[0]}', '{$tmp[1]}' \n";
			
			if ($i % 500 == 0) {
				$db->exec($sql);
				$sql = "INSERT INTO {$cmd['n']} \n";
				$i = 0;
			}
			
			$k ++;
		}
		fclose($f);
		if ($sql != "INSERT INTO {$cmd['n']} \n") {
			$db->exec($sql);
		}
		echo "$k records added!\n";
		
		break;
	
	case 'msig':		
	
		$cmd     = parseMSigCommands();
		$type    = $cmd['t'];
		$species = $cmd['s'];
		$file    = $cmd['datafile'];

		// create table if not exists
		$db = new \SQLite3 (__DIR__ . "/constants/msig/msig.sqlite", SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
		$db->exec("CREATE TABLE IF NOT EXISTS $species (id CHAR(100) PRIMARY KEY COLLATE NOCASE, genes CHAR(20000) COLLATE NOCASE, type CHAR(20) COLLATE NOCASE)");
		$db->exec("CREATE INDEX IF NOT EXISTS index_msig_type ON $species(type COLLATE NOCASE)");
		
		$f = fopen($file, "r");
		$insert = 0; $update = 0;
		while (!feof($f)) {
			$line = trim(fgets($f));
			if (!$line) continue;
			list ($id,,$genes) = explode("\t", $line, 3);
			$test = $db->querySingle ("SELECT id FROM $species WHERE id = '$id' AND type = '$type'");
			if (empty($test)) {
				$db->exec("INSERT INTO $species (id, genes, type) VALUES('$id', '$genes', '$type')");
				$insert ++;
			} else {
				$db->exec("UPDATE $species SET genes = '$genes' WHERE id = '$id' AND type = '$type'");
				$update ++;
			}
		}
		fclose($f);
		echo "$insert records inserted!\n$update record may be updated!\n";
		break;
		
	default:
		error("Error: unknown subcommand: $subcommand", true);
		break;
}
echo "\n";

// functions

function parseAddUpdateCommands() {
	global $argv;
	$argv1 = $argv;
	array_shift($argv1); // shift the subcommand
	
	$ret = array(
		's' => 'human',
		'o' => 'true',
		'm' => 'combine',
		'c' => 'id',
		't' => 'gene'
	);
	while ($argv1) {
		$opt = array_shift ($argv1);
		if (in_array($opt, array("-s", "-o", "-m", "-c", "-n", "-t"))) {
			$ret[substr($opt, 1)] = array_shift($argv1);
		} else {
			$ret['datafile'] = $opt;
		}		
	}
	if (!isset($ret['datafile']) or !is_file($ret['datafile'])) 
		error ("Error: data file does not exist or is missed.", true);
	
	if (!isset($ret['n'])) 
		error ("Error: table name (-n) missed." ,true);

	$ret['o'] = $ret['o'] === 'true';
	$ret['n'] = strtolower($ret['n']);
	return $ret;
}

function parseMSigCommands() {
	global $argv;
	$argv1 = $argv;
	array_shift($argv1); // shift the subcommand
	
	$ret = array(
		's' => 'human'
	);
	while ($argv1) {
		$opt = array_shift ($argv1);
		if (in_array($opt, array("-s", "-t"))) {
			$ret[substr($opt, 1)] = array_shift($argv1);
		} else {
			$ret['datafile'] = $opt;
		}		
	}
	if (!isset($ret['datafile']) or !is_file($ret['datafile'])) 
		error ("Error: data file does not exist or is missed.", true);
	
	if (!isset($ret['t'])) 
		error ("Error: msig type (-t) missed." ,true);

	return $ret;
}

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

function error ($msg, $printusage = false) {
	echo "\n" . \biophp\utils::color($msg, 'lightred') . "\n";
	if ($printusage) echo usage();
	exit();
}

function usage () {
	global $argv;
	$ret = "\n";
	$ret .= "Usage: {$argv[0]} <Subcommand> <Options>\n\n";
	$ret .= "Subcommand:\n\n";
	$ret .= "  help            Print this help\n";
	$ret .= "  usage           Get the usage of biophp\n";
	$ret .= "  msig            Update MsigDB\n";
	$ret .= "  update          Update the constants with a new file\n";
	$ret .= "  add             Add data of a new constant type\n\n";
	$ret .= "Options:\n\n";
	$ret .= "  [namespace/class/function/constant]\n";
	$ret .= "      Only for subcommand usage, terms are connected by dot (.) \n";
	$ret .= "      Example: \n";
	$ret .= "                  {$argv[0]} usage parser.txt\n\n";
	$ret .= "  [-t <type>] <data file>\n";
	$ret .= "      Only for subcommand msig\n";
	$ret .= "      -t          Required. The type of this msig collection.\n";
	$ret .= "      -s          Species, default: human.\n\n";
	$ret .= "  [-s <species> -o <one2one> -m <manner> -c <column> -n <name> -t <type>] <data file>\n";
	$ret .= "      Only for subcommand update and add\n";
	$ret .= "      data file:  The constants data file. \n";
	$ret .= "                  Tab delimited: \n";
	$ret .= "                    1st column: the connected column (-c)\n";
	$ret .= "                    2nd column: the new column (-n)\n";
	$ret .= "      -s          The species. e.g.: human, mouse, ...\n";
	$ret .= "                  Default: human\n";
	$ret .= "      -o          Whether it is a one-to-one relation (true|false)\n";
	$ret .= "                  If it is, -m=combine will replace the record; \n";
	$ret .= "                  Otherwise it is combined.\n";
	$ret .= "                  Default: true\n";
	$ret .= "      -m          The manner (replace or combine). Whether to totally replace the \n";
	$ret .= "                  table with the data file, or combine it with the current table.\n";
	$ret .= "                  Default: combine\n";
	$ret .= "      -c          The connected column with the current database.\n";
	$ret .= "                  Default: id\n";
	$ret .= "      -n          The name of the table to add or to update\n";
	$ret .= "      -t          The type of the constant. e.g.: gene, kinase, ...\n";
	$ret .= "                  Default: gene\n";
	return $ret;
}
