<?php
namespace biophp\math\ml;

// see: http://suanpalm3.kmutnb.ac.th/teacher/FileDL/choochart82255418560.pdf
// and  http://www.cnblogs.com/zhangchaoyang/articles/2586402.html
class naiveBayes {
	
	private $handler;
	private $debug;
	
	public function d ($msg) {
		fprintf(STDERR, "- $msg\n");
	}
	
	public function __construct ($trainingSet = array(), $type=NULL) {
		$this->debug = true;
		$type = $this->setTrainingSet($trainingSet, $type);
		if ($this->debug) 
			self::d("Debug Info >> NaiveBayes: type -> $type");
		if ($type == 'numeric')
			$this->handler = new \biophp\math\ml\nbNumeric($trainingSet, $this->debug);
		else
			$this->handler = new \biophp\math\ml\nbNonimal($trainingSet, $this->debug);
		$this->handler->train();
	}
	
	/**
	 * @desc: set training set
	 * @param: $trainingSet, the training set
	 *         array(
	 *	         array (
	 *              'data' => (
	 *              	feature name 1 => value 1,
	 *              	feature name 2 => value 2,
	 *              	...)
	 *              'class' => class1
	 *	         )
	 *            
	 *	         ...,
	 *         ) OR
	 *         array(
	 *	         array (
	 *              'data' => array(feature1, featuer2, ...)
	 *              'class' => class2
	 *	         )
	 *            
	 *	         ...,
	 *         )  
	 * @param: $type, whether the data is numeric or nominal
	 *         if it is NULL, it will be automatically detected
	 */
	private function setTrainingSet (&$trainingSet, $type=NULL) {
		$keys   = array_keys($trainingSet[0]['data']);
		if ($keys[0]===0) { // 2nd form, transform into 1st form
			$type = 'numeric';
			$trainingSet = $this->transformData($trainingSet);
		} else {
			if ($type === NULL) {
				$values = array_values($trainingSet[0]['data']);
				$type = (is_numeric($values[0]) ? 'numeric' : 'nominal');
			} 
		}
		return $type;
	}
	
	/**
	 * @desc: transform 2nd form of data to 1st form
	 * @param: $data, the data
	 *            array(array('data'=>array(f1, f2, ...), 'class'=>1), ...)
	 *         => array(array('data'=>array(f1=>1, f2=>2, ...), 'class'=>1), ...)
	 * @return: the transformed data
	 */
	private function &transformData(&$data) {
		$ret = $data;
		foreach ($data as $i=>$t) {
			$d = array();
			foreach ($t['data'] as $fname) 
				$d[$fname] ++;
			$ret[$i]['data'] = $d;
		}
		return $ret;
	}
	
	/**
	 * @desc: get the probability of class c a test set
	 * @param: $data, the data
	 * @param: $class, the class
	 * @return: the probability
	 */
	public function prob (&$data, $class) {
		return $this->handler->prob($data, $class);
	}
	
	/**
	 * @desc: classify the data
	 * @param: $data, the data
	 * @return: the class
	 */
	public function classify(&$data) {
		return $this->handler->classify($data);
	}
	
}

class nbNumeric {
	
	private $fl;            // feature list
	private $ts;            // training set
	private $featureProbs;  // feature probabilities to different classes
	private $priorProbs;    // prior probabilities
	private $debug;
	
	public function __construct($trainingSet, $debug) {
		$this->ts = $trainingSet;
		$this->debug = $debug;
	}
	
	/**
	 * @desc: train the data
	 */
	public function train () {
		$totalNums    = array();
		$featureFrqs  = array();
		$priorNums    = array();
		$trainDocNum  = 0;
		foreach ($this->ts as $t) {
			$class = $t['class'];
			$priorNums[$class] ++;
			$trainDocNum ++;
			foreach ($t['data'] as $f => $v) {
				$this->fl[$f] = 1;
				$totalNums[$class] += $v;
				$featureFrqs[$f][$class] += $v;
			}
		}
		
		// convert frequencies to probabilities
		foreach ($priorNums as $class => $pn) {
			$this->priorProbs[$class] = $pn / $trainDocNum;
			if ($this->debug) 
				naiveBayes::d("Debug Info >> NaiveBayes: Prior Probability [$class] = {$this->priorProbs[$class]} ($pn/$trainDocNum)");
		}
		
		foreach ($this->fl as $feature=>$_) {
			foreach ($this->priorProbs as $class=>$_) {// Laplace estimate
				$this->featureProbs[$feature][$class] = (floatval($featureFrqs[$feature][$class])+1) / ($totalNums[$class]+sizeof($this->fl));
				if ($this->debug) 
						naiveBayes::d("Debug Info >> NaiveBayes: Feature Probability [$feature.$class] = {$this->featureProbs[$feature][$class]} " .
							"(Laplace: ({$featureFrqs[$feature][$class]}+1)/({$totalNums[$class]}+".sizeof($this->fl)."))");
			}
		}
	}
	
	
	/**
	 * @desc: calculate the probability of class 0 a test set
	 *        P' = P(w|C=0)P(C=0)   [P(C=0) === $this->priorProb0]
	 *           = P(w|C=0) * P(C=0)
	 *           = P(w1|C=0)*,...,P(wn|C=0)*P(C=0)
	 *        Use prob to get the real probability
	 *        The real probability should be normalized by P(w) =
	 *        P(w|C=0)P(C=0) + ... + P(w|C=N)P(C=N)
	 * @param: $data, the array containing testing elements
	 * @param: $class, the class
	 * @param: $log, whether output the natural log form
	 * @return: the un-normalized probability
	 */
	public function probUnn ($data, $class, $log = false) {
		$keys = array_keys($data);
		if ($keys[0]===0) {
			$d = array();
			foreach ($data as $e)
				$d[$e] ++;
			$data = $d;
		}
		if ($log) {
			$p = log($this->priorProbs[$class]);
			foreach ($this->fl as $f=>$_)
				$p += $data[$f] * log ($this->featureProbs[$f][$class]);
		} else {
			$p = $this->priorProbs[$class];
			foreach ($this->fl as $f=>$_)
				$p *= pow($this->featureProbs[$f][$class], $data[$f]);
		}
		return $p;
	}
	
	/**
	 * @desc: get the real probability of class for data
	 * @param: $data, the data
	 * @param: $class, the class
	 */
	public function prob (&$data, $class) {
		$fullProb = 0;
		$prob = 0;
		foreach ($this->priorProbs as $c=>$_) {
			$p = $this->probUnn($data, $c, true);
			if ($this->debug)
				naiveBayes::d("Debug Info >> Naive Bayes: Unnormalized testing probability [$c] = ".$this->probUnn($data, $c).", (logP = $p)");
			if ($c==$class) 
				$prob = exp($p);
			$fullProb += exp($p);
		}
		return $prob/$fullProb;
	}
	
	/**
	 * @desc: classify the test data
	 * @param: $data, the array containing testing elements
	 * @return: the class 
	 */
	public function classify (&$data) {
		$maxProb = 0;
		$i = 0;
		$class = '';
		foreach ($this->priorProbs as $c=>$_) {
			$prob = $this->testProbUnn($data, $class, true);
			if ($i++==0 or $prob > $maxProb) {
				$maxProb = $prob;
				$class = $c;
			} 
		}
		return $class;
	}
	
}

class nbNonimal {
	
	private $fl;            // feature list
	private $ts;            // training set
	private $featureProbs;  // feature probabilities to different classes
	private $priorProbs;    // prior probabilities
	private $debug;
	
	public function __construct($trainingSet, $debug) {
		$this->ts = $trainingSet;
		$this->debug = $debug;
	}
	
	/**
	 * @desc: train the data
	 */
	public function train () {
		$totalNums    = array();
		$featureFrqs  = array();
		$priorNums    = array();
		$trainDocNum  = 0;
		foreach ($this->ts as $t) {
			$class = $t['class'];
			$priorNums[$class] ++;
			$trainDocNum ++;
			foreach ($t['data'] as $f => $v) {
				$this->fl[$f][$v] = 1;
				$totalNums[$f][$class] ++;
				$featureFrqs[$f][$class][$v] ++;
			}
		}
		
		// convert frequencies to probabilities
		foreach ($priorNums as $class => $pn) {
			$this->priorProbs[$class] = $pn / $trainDocNum;
			if ($this->debug) 
				naiveBayes::d("Debug Info >> NaiveBayes: Prior Probability [$class] = {$this->priorProbs[$class]} ($pn/$trainDocNum)");
		}
		
		foreach ($this->fl as $fname => $fvalue_) {
			foreach ($this->priorProbs as $class=>$_) {
				$zeroflag = false;
				if (isset($featureFrqs[$fname]) and isset($featureFrqs[$fname][$class])) {
					foreach ($fvalue_ as $fvalue => $frq) {
						if (!isset($featureFrqs[$fname][$class][$fvalue]) or
							$featureFrqs[$fname][$class][$fvalue] == 0) {
							$featureFrqs[$fname][$class][$fvalue] = 0;
							$zeroflag = true;
							break;
						}
					}
				} else {
					foreach ($fvalue_ as $fvalue => $frq) 
						$featureFrqs[$fname][$class][$fvalue] = 0;
					$zeroflag = true;
				} 
				if ($zeroflag) {
					foreach ($fvalue_ as $fvalue => $frq) { // Laplace
						$this->featureProbs[$fname][$class][$fvalue] =
							(floatval($featureFrqs[$fname][$class][$fvalue])+1) / ($totalNums[$fname][$class] + sizeof($featureFrqs[$fname][$class]));
						if ($this->debug) 
							naiveBayes::d("Debug Info >> NaiveBayes: Feature Probability [$fname.$fvalue.$class] = {$this->featureProbs[$fname][$class][$fvalue]} " .
								"(Laplace: ({$featureFrqs[$fname][$class][$fvalue]}+1)/({$totalNums[$fname][$class]}+".sizeof($featureFrqs[$fname][$class])."))");
					}
				} else {
					foreach ($fvalue_ as $fvalue => $frq) {
						$this->featureProbs[$fname][$class][$fvalue] =
							$featureFrqs[$fname][$class][$fvalue] / $totalNums[$fname][$class];
						if ($this->debug)
							naiveBayes::d("Debug Info >> NaiveBayes: Feature Probability [$fname.$fvalue.$class] = {$this->featureProbs[$fname][$class][$fvalue]} " .
								"({$featureFrqs[$fname][$class][$fvalue]}/{$totalNums[$fname][$class]})");
					}
				}
			}
			
		}
	}
	
	
	/**
	 * @desc: calculate the probability of class 0 a test set
	 *        P' = P(w|C=0)P(C=0)   [P(C=0) === $this->priorProb0]
	 *           = P(w|C=0) * P(C=0)
	 *           = P(w1|C=0)*,...,P(wn|C=0)*P(C=0)
	 *        Use prob to get the real probability
	 *        The real probability should be normalized by P(w) =
	 *        P(w|C=0)P(C=0) + ... + P(w|C=N)P(C=N)
	 * @param: $data, the array containing testing elements
	 * @param: $class, the class
	 * @param: $log, whether output the natural log form
	 * @return: the un-normalized probability
	 */
	public function probUnn ($data, $class, $log = false) {
		if ($log) { 
			$p = log($this->priorProbs[$class]);
			foreach ($this->fl as $f => $_) 
				$p += log ($this->featureProbs[$f][$class][$data[$f]]);
		} else {
			$p = $this->priorProbs[$class];
			foreach ($this->fl as $f => $_)
				$p *= $this->featureProbs[$f][$class][$data[$f]];
		}
		return $p;
	}
	
	/**
	 * @desc: get the real probability of class for data
	 * @param: $data, the data
	 * @param: $class, the class
	 */
	public function prob (&$data, $class) {
		$fullProb = 0;
		$prob = 0;
		foreach ($this->priorProbs as $c=>$_) {
			$p = $this->probUnn($data, $c, true);
			if ($this->debug)
				naiveBayes::d("Debug Info >> Naive Bayes: Unnormalized testing probability [$c] = ".$this->probUnn($data, $c).", (logP = $p) ");
			if ($c==$class) 
				$prob = exp($p);
			$fullProb += exp($p);
		}
		return $prob/$fullProb;
	}
	
	/**
	 * @desc: classify the test data
	 * @param: $data, the array containing testing elements
	 * @return: the class 
	 */
	public function classify (&$data) {
		$maxProb = 0;
		$i = 0;
		$class = '';
		foreach ($this->priorProbs as $c=>$_) {
			$prob = $this->testProbUnn($data, $class, true);
			if ($i++==0 or $prob > $maxProb) {
				$maxProb = $prob;
				$class = $c;
			} 
		}
		return $class;
	}
}

