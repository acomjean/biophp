<?php

namespace biophp\parser;

class txt {
	private $file;
	private $outformat;
	private $filename;
	private $delimit;
	private $gz;
	public $line;

	public function __construct ($f, $outformat, $delimit) {
		$this->gz = substr($f, -3) === '.gz';
		$fopen = $this->gz ? 'gzopen' : 'fopen';
		$this->file = @$fopen ($f, 'r');
		if (!$this->file) throw new \Exception ("Cannot open file: $f");
		$this->filename = $f;
		$this->outformat = trim($outformat);
		$this->delimit = $delimit;
	}

	// format the array from a line to the out fomat
	private function format ($array) {
		if (!$this->outformat) return $array;
		$format = $this->outformat;
		if (substr($format, 0, 1)!=='(' or substr($format, -1) !== ')') 
			$format = '(' . $format . ')';
		preg_replace("/([a-zA-z]+)/", "\"$1\"", $format);
		$range = range(1, sizeof($array));
		// compile .
		if (strpos($format, '.')!==false) {
			preg_match_all("/(?:^|[^\d])(\d+)\s*:/", $format, $m);
			$dots = array();
			foreach ($range as $r) 
				if (!in_array($r, $m[1])) $dots[] = $r;
			$format = str_replace('.', '(' . implode(',', $dots) . ')', $format);
		}
		
		// compile _
		if (strpos($format, '_')!==false) {
			preg_match_all("/(?<=^|[^\d])(\d+)\s*:\s*_/", $format, $m, PREG_SET_ORDER);
			foreach ($m as $match) {
				$rangex = $range;
				unset($rangex[$match[1]-1]);
				$underline = "(". implode(',', $rangex) .")";
				$format = preg_replace("/(?<=^|[^\d]){$match[0]}/", $match[1] . ":$underline", $format);
			}
		}
		
		// compile *
		$format = str_replace('(*)', '(' . implode(',', $range) . ')', $format);
		$format = str_replace('*', '(' . implode(',', $range) . ')', $format);
		
		$format = str_replace(array('(', ':'), array('array(', '=>'), $format);
		$format = preg_replace("/(\d+)/e", "$1-1", $format);
		$format = preg_replace("/(\d+)/", "\$array[$1]", $format);
		$format = 'return ' . $format. ';'; 
		try {
			return eval($format);
		} catch (\Exception $ex) {
			throw new \Exception ("Unable to evaluate: $format"); 
		}
	}

	/**
	 * @desc: get all results
	 * @param: whether to make the values unique, limited to the 2nd layer
	 * @param: $skip_empty_lines, whether skip the empty lines, default: true.
	 * @return: array(outformat1, ...)
	 */
	public function readAll ($uniqe=true, $skip_empty_lines=true) {
		$ret = array();
		if (strpos($this->outformat, ":")===false and strpos($this->outformat, "=>")===false) // no keys
			while ($o = $this->read()) 
				$ret[] = array_shift($o);
		else 
			while (!$this->eof()) {
				$o = (array)$this->read();
				list($key, $value) = each($o);
				if (!$key) continue;
				if (!isset($ret[$key]))
					$ret[$key] = $value;
				else {
					$value = (array)$value;
					list ($k, $v) = each($value);
					if (!isset($ret[$key][$k])) $ret[$key][$k] = $v;
					else {
						if ($k===0) 
							$ret[$key] = $unique 
								? array_unique(array_merge((array)$ret[$key], (array)$value))
								: array_merge((array)$ret[$key], (array)$value);
						else 
							$ret[$key][$k] = $unique
								? array_unique(array_merge((array)$ret[$key][$k], (array)$v))
								: array_merge((array)$ret[$key][$k], (array)$v);
					}
				}
			}
		return $ret;
	}
	
	/**
	 * @desc: tell whether the file is ended
	 * @return: true|false
	 */
	public function eof () {
		$feof = $this->gz ? 'gzeof' : 'feof';
		return $feof($this->file);
	}

	/**
	 * @desc: get result from one line, and move cursor to next line
	 * @param: $skip_empty_lines, whether skip the empty lines, default: true.
	 * @return: data formatted with outformat
	 */
	public function read ($skip_empty_lines=true) {
		$fgets = $this->gz ? 'gzgets' : 'fgets';
		$line = trim($fgets($this->file), "\n");
		if ($line==='' and $skip_empty_lines) 
			return $this->eof() ? false : $this->read($skip_empty_lines);
		$this->line = $line;
		$array = explode($this->delimit, $line);
		return $this->format ($array);
	}
	
	/**
	 * @desc: rewind the file cursor
	 */
	public function reset () {
		$rewind = $this->gz ? 'gzrewind' : 'rewind';
		$rewind ($this->file);
	}

	public function __destruct () {
		$fclose = $this->gz ? 'gzclose' : 'fclose';
		if ($this->file)
			fclose($this->file);
	}
}