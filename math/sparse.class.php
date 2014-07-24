<?php
namespace biophp\math;
use biophp;

class sparse {

	private $array;
	private $nrows;
	private $ncols;
	
	public function __construct ($forc = array()) {
		if (is_string($forc))
			$this->load($forc);
		else 
			$this->array = $forc;
		$this->nrows = 0;
		$this->ncols = 0;
	}
	
	public function load ($sparsefile, $sep = "\t") {
		$f = fopen($sparsefile, 'r');
		while (!feof($f)) {
			$line = trim(fgets($f));
			if(!$line) continue;
			$tmp = explode($sep, $line);
			$this->array[$tmp[0]][$tmp[1]] = $tmp[2];
		}
		fclose($f);
	}
	
	private function calcDimension () {
		if ($this->nrows > 0) return;
		foreach ($this->array as $rowid => $rows) {
			if ($rowid > $this->nrows)
				$this->nrows = $rowid;
			foreach ((array)$rows as $colid => $v) {
				if ($colid > $this->ncols)
					$this->ncols = $colid;
			}
		}
	}
	
	public function nrows () {
		$this->calcDimension();
		return $this->nrows;
	}
	
	public function ncols () {
		$this->calcDimension();
		return $this->ncols;
	}
	
	public function dimen() {
		$this->calcDimension();
		return array($this->nrows, $this->ncols);
	}
	
	public function size($n = '') {
		if (!$n) return $this->dimen();
		if ($n==1) return $this->nrows();
		if ($n==2) return $this->ncols();
	}
	
	public function arr () {
		return $this->array;
	}
	
	public function X ($x) {
		if (is_numeric($x)) {
			$array = array();
			foreach ($this->array as $rowid => $rows) {
				foreach ($rows as $colid => $v) {
					$array[$rowid][$colid] = $x * $v;
				}
			}
			return new sparse($array);
		} elseif (is_array($x)) {
			$xsp = new sparse($x);
			return $this->X($xsp);
		} elseif (is_a($x, __CLASS__)) {
			if ($this->ncols() != $x->nrows()) 
				throw new \Exception('The dimensions are not consistent ('.$this->ncols().', '.$x->nrows().').');
			$array = array();
			for ($k = 1; $k<=$x->ncols(); $k++) {
				for ($i = 1; $i <= $this->nrows(); $i++) {
					$array[$i][$k] = 0;
					for ($j = 1; $j<=$this->ncols(); $j++) {
						$array[$i][$k] += $this->at($i, $j) * $x->at($j, $k);
					}
					if ($array[$i][$k] == 0) unset($array[$i][$k]);
				}
			}
			return new sparse($array);
		}
	}
	
	public function xVec ($vec, $debug = false) { // starts with 1!!
		$ret = array_fill(1, $this->nrows(), 0);
		$vsize = max(array_keys($vec));
		if ($this->ncols() != $vsize)
			throw new \Exception('The dimension are not consistent ('. $this->ncols() . ', ' . $vsize .')');
		for ($i=1; $i<=$vsize; $i++) {  // cols of this, or rows of vec
			if (!isset($vec[$i]) or !$vec[$i]) continue; // skip cols of this if $vec[$i] = 0
			for ($j=1; $j<=$this->nrows(); $j++) {
				if (!isset($this->array[$j][$i]) or !$this->array[$j][$i]) continue;
				$ret[$j] += $this->array[$j][$i] * $vec[$i];
			}
		}
		return $ret;
	}
	
	public function plus ($x) {
		if (is_array($x)) {
			$xsp = new sparse($x);
			return $this->X($xsp);
		} elseif (is_a($x, __CLASS__)) {
			if ($this->ncols() != $x->ncols() or $this->nrows() != $x->nrows()) 
				throw new \Exception('The dimensions are not consistent.');
			$ret = new sparse();
			for ($i = 1; $i<= $this->nrows(); $i++) {
				for ($j = 1; $j<= $this->ncols(); $j++) {
					$ret->set($i, $j, $this->at($i, $j) + $x->at($i, $j));
				}
			}
			return $ret;
		}
	}
	
	public function full () {
		$ret = array();
		for ($i = 1; $i<=$this->nrows(); $i++) {
			for ($j = 1; $j<=$this->ncols(); $j++) {
				if (isset($this->array[$i][$j]))
					$ret[$i][$j] = $this->array[$i][$j];
				else
					$ret[$i][$j] = 0;
			}
		}
	}
	
	public function at ($i, $j) {
		$ret = array();
		if ($i == ":") {
			for ($m = 1; $m <= $this->nrows(); $m++) {
				$ret[$m] = isset($this->array[$m][$j]) ?
					$this->array[$m][$j] : 0;
			}
		} elseif ($j == ":") {
			for ($m = 1; $m <= $this->ncols(); $m++) {
				$ret[$m] = isset($this->array[$i][$m]) ?
					$this->array[$i][$m] : 0;
			}
		} else {
			$ret = isset($this->array[$i][$j]) ? $this->array[$i][$j] : 0;
		}
		return $ret;		
	}
	
	public function get($i, $j) { return $this->at($i, $j); }
	public function set($i, $j, $v) { $this->array[$i][$j] = $v; }

}