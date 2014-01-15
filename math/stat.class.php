<?php
namespace biophp\math;

class stat {

	static public function mean ($arr) {
		return array_sum($arr)/sizeof($arr);
	}
	
	static public function sd ($arr) {
		$n = sizeof($arr);
		$mean = self::mean($arr);
		$s = 0;
		foreach ($arr as $x) 
			$s += ($x - $mean) * ($x - $mean);
		return sqrt($s/($n-1));
	}
	
	static public function pcc ($arr1, $arr2) {
		$n = sizeof($arr1);
		if ($n != sizeof($arr2))
			throw new \Exception ('Two arrays should be in the same dimension to calculate PCC');
		$s = 0;
		$sd1 = self::sd($arr1);
		$sd2 = self::sd($arr2);
		$m1  = self::mean($arr1);
		$m2  = self::mean($arr2);
		foreach ($arr1 as $i=>$x) 
			$s += (($x - $m1)/$sd1) * (($arr2[$i] - $m2)/$sd2);
		return $s/($n-1);
	}

	/**
	 * Calculate p-value of hypergeometric test
	 * $i : overlop number
	 * $m : set 1 size
	 * $n : set 2 size
	 * $N : background size
	 *               C(m,i) * C(N-m, n-i)
	 * log(P) = log[----------------------]
	 *                   C(N, n)
	 *               m!/(i!(m-i)!) * (N-m)!/(n-i)!(N-m-n+i)!)
	 *        = log[------------------------------------------]
	 *                          N!/(n!(N-n)!)
	 *        =   logfact(m) + logfact(N-m) + logfact(n) + logfact(N-n)
	 *          - logfact(i) - logfact(m-i) - logfact(n-i) - logfact(N-m-n+i) - logfact(N)
	 */      
	static public function hypergeometric ($i, $m, $n, $N) {
		require_once __DIR__ . "/../math.class.php";
		$ret =  \biophp\math::logfact($m) + \biophp\math::logfact($N-$m) + \biophp\math::logfact($n) + \biophp\math::logfact($N-$n)
		      - \biophp\math::logfact($i) - \biophp\math::logfact($m-$i) - \biophp\math::logfact($n-$i) - \biophp\math::logfact($N-$m-$n+$i)
			  - \biophp\math::logfact($N);
		return exp($ret);
	}

}
