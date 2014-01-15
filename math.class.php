<?php
namespace biophp;

class math {

	/**
	 * @desc: Machine Learning
	 * @usage: \biophp\math\ml::...()
	 * @import: ml.class.php
	 */
	static public function ml () {}
	
	/**
	 * @desc: Statistics
	 * @usage: \biophp\math\stat::...()
	 * @import: stat.class.php
	 */
	static public function stat () {}
	
	/**
	 * Draw the distribution for a serial of data
	 * The generated data could be put into excel to draw the curve
	 * @param array $data The data
	 * @param int $bins Number of bins
	 * @param float $start The start point
	 * @param float $end The end point
	 * @param string $file If given, ouput will be written to the file,
	 *        otherwise to STDOUT
	 * @return
	 * START bin1       bin2                         END
	 *   |----x1----|----x2----|----...----|----xn----|
	 *   |----y1----|----y2----|----...----|----yn----|
	 *   y(n) is the number of data points in bin(n)
	 */
	static public function distribution($data, $bins=10, $start=0, $end=1, $file="") {
		$f    = (!empty($file)) ? fopen($file, 'w') : STDOUT;
		$xs   = array();
		$cell = ($end-$start)/$bins;
		for ($i=0; $i<$bins; $i++) 
			$xs[] = ($start+$cell/2) + $i*$cell;
		fwrite($f, implode("\t", $xs) . PHP_EOL);
		
		$ys   = array_fill(0, $bins, 0);
		foreach ($data as $d) 
			$ys[floor($d/$cell)] ++;
		
		fwrite($f, implode("\t", $ys));
		if (!empty($file)) fclose($file);
	}
	
	/**
	 * Matrix
	 * @param mixed $forc The matrix file or multi-dimension array
	 * @return biophp\math\matrix object
	 * @import: matrix.class.php
	 */
	static public function &matrix($forc) {
		$m = new math\matrix($forc);
		return $m;
	}
	
	/**
	 * Sparse Matrix
	 * @param mixed $forc The matrix file or multi-dimension array
	 *        The array should be in format $array[$i][$j] = $value
	 * @return biophp\math\sparse object
	 * @import: sparse.class.php
	 */
	static public function &sparse($forc) {
		$m = new math\sparse($forc);
		return $m;
	}
	
	/**
	 * Calculate log(x!)
	 */
	static public function logfact ($x) {
		$ser = (   1.000000000190015
	                + 76.18009172947146   / ($x + 2)
	                - 86.50532032941677   / ($x + 3)
	                + 24.01409824083091   / ($x + 4)
	                - 1.231739572450155   / ($x + 5)
	                + 0.12086509738661e-2 / ($x + 6)
	                - 0.5395239384953e-5  / ($x + 7) );
		$tmp = $x + 6.5;
		return ($x + 1.5) * log($tmp) - $tmp + log(2.5066282746310005 * $ser / ($x+1));
	}

	/**
	 * Calculate x!
	 */
	static public function fact($x) {
		$ret = 1;
		while ($x) $ret*=$x--;
		return $ret;	
	}
}
