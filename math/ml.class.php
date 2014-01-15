<?php

namespace biophp\math;

// machine learning
class ml {
	/**
	 * @desc: naive bayes handler
	 * @param: $trainingSet, the training set
	 *         array(
	 *	         array (
	 *              'data' => (
	 *              	feature name 1 => value 1,
	 *              	feature name 2 => value 2,
	 *              	...)
	 *              'class' => 0|1
	 *	         )
	 *            
	 *	         ...,
	 *         ) OR
	 *         array(
	 *	         array (
	 *              'data' => array(feature1, featuer2, ...)
	 *              'class' => 0|1
	 *	         )
	 *            
	 *	         ...,
	 *         )  
	 * @param: $type, whether the data is numeric or nominal
	 *         if it is NULL, it will be automatically detected
	 * @import: naiveBayes.class.php
	 */
	static public function naiveBayes ($trainingSet, $type=NULL) {
		return new \biophp\math\ml\naiveBayes($trainingSet, $type);
	}
	
}