<?php
namespace biophp\math;

class matrix {

	private $matrix = array();
	private $colsize = 0;
	private $rowsize = 0;

	public function __construct(&$forc) {
		if (is_string($forc)) {
			$this->loadFile($forc);
		} else {
			$this->matrix = $forc;
			$this->rowsize = sizeof($forc);
			$this->colsize = sizeof($forc[0]);
		}
	}
	
	public function loadFile($file) {
		$f = fopen($file, 'r');
		while (!feof($f)) {
			$line = trim(fgets($f));
			if ($line==='') continue;
			$this->matrix[$this->rowsize] = explode("\t", $line);
			$size = sizeof($this->matrix[$this->rowsize++]);
			if ($size > $this->colsize)
				$this->colsize = $size;
		}
		fclose($f);
	}
	
	public function &_($i=":", $j=":") {
		if ($i === ":" and $j===":") return $this->matrix;
		if ($i === ":") {
			$ret = array();
			foreach ($this->matrix as $x=>$row) 
				$ret[] = isset($row[$j]) ? $row[$j] : $this->_($j, $x);
			return $ret;
		} elseif ($j === ":") {
			$size = sizeof($this->matrix[$i]);
			$ret  = $this->matrix[$i];
			if ($size == $this->colsize) return $ret;
			for ($x = $size; $x < $this->colsize; $x ++) 
				$ret[$x] = $this->_($j, $x);
			return $ret;
		} else 
			return isset($this->matrix[$i][$j]) ? $this->matrix[$i][$j] : $this->matrix[$j][$i];
	}
	
	public function colsize() {
		return $this->colsize;
	}
	
	public function rowsize () {
		return $this->rowsize;
	}
	
	public function &X (&$x) {
		$ret = array();
		if (is_a($x, __CLASS__)) {
			if ($this->colsize != $x->rowsize()) 
				throw new \Exception("Dimension error for multiplying");
			for ($i=0; $i<$this->rowsize; $i++) {
				for ($j=0; $j<$x->colsize(); $j++) {
					$ret[$i][$j] += $this->_($i, $j) * $x->_($j, $i);
				}
			}
		} else if (is_array($x)) { 
			if (is_array($x[0])) { 
				if ($this->colsize != sizeof($x)) 
					throw new \Exception("Dimension error for multiplying");
					
				for ($i=0; $i<$this->rowsize; $i++) {
					for ($j=0; $j<sizeof($x[0]); $j++) {
						$ret[$i][$j] += $this->_($i, $j) * $x[$j][$i];
					}
				}
			} else {
				if (sizeof($x)!==$this->rowsize)
					throw new \Exception('Vector('. sizeof($x) .') is not in the same size('. $this->rowsize .') of rows of the matrix.');
				for ($i=0; $i<$this->colsize; $i++) {
					$col = $this->_(":", $i); 
					for ($j=0; $j<$this->rowsize; $j++) {
						$ret[$i] += $col[$j]*$x[$j];
					}
				}
				return $ret;
			}
		} else {
			for ($i=0; $i<$this->rowsize; $i++) {
				for ($j=0; $j<$this->colsize; $j++) {
					$ret[$i][$j] = $this->_($i, $j) * $x;
				}
			} 
		}
		$class = __CLASS__;
		$ret = new $class($ret);
		return $ret;
	}
	
	public function output ($file = '') {
		$f = empty($file) ? STDOUT : fopen($file, 'w');
		for ($i=0; $i<$this->rowsize; $i++) {
			for ($j=0; $j<$this->colsize; $j++) {
				fwrite($f, $this->_($i, $j) . (($j < $this->colsize-1) ? "\t" : "") );
			}
			fwrite ($f, PHP_EOL);
		}
		if (!empty($file)) fclose($f);
	}
	
	public function t () {
		$ret = array();
		for ($i=0; $i<$this->rowsize; $i++) {
			for ($j=0; $j<$this->colsize; $j++) {
				$ret[$i][$j] = $this->_($j, $i);
			}
		}
		$class = __CLASS__;
		$ret = new $class($ret);
		return $ret;
	}

}