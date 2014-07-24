<?php

namespace biophp;
if (!defined("PHP_TAB")) define("PHP_TAB", "\t");

//description: file format parser
class parser {

	/**
	 * @desc: fixed delimit file parser, gz file supported
	 * @param: $file, the file to be parsed
	 * @param: $outformat, the outformat, default: *
	 * @param: $delimit, the delimit
	 *         number: the column index, starting from 1
	 *         _     : the rest of the columns compared to its key
	 *         *     : all columns
	 *         .     : the rest unmentioned keys
	 *         examples:
	 *            columns   : 1,2,3,4,5
	 *            1:_       : array('1'=>array('2','3','4','5'))
	 *            ''(empty) : array('1','2','3','4','5')
	 *            1:(2:3)   : array('1'=>array('2'=>'3'))
	 *            1:(2:.)   : array('1'=>array('2'=>array('3','4','5')))
	 * @return: txt object
	 * @import: txt.class.php
	 */
	static public function txt ($file, $outformat = '*', $delimit = PHP_TAB) {
		require_once __DIR__ . DIRECTORY_SEPARATOR . "parser" . DIRECTORY_SEPARATOR . "txt.class.php";
		return new \biophp\parser\txt($file, $outformat, $delimit);
	}
	
	/**
	 * @desc: obo file parser, gz file supported
	 * @param: $file, the obo file to be parsed
	 * @return: obo object
	 * @import: obo.class.php
	 */
	static public function obo ($file) {
		return new \biophp\parser\obo($file);
	}
	
	/**
	 * @desc: MIPS PPI file parser
	 * @param: $file, the MIPS file using PSI-MI 2.5 format
	 * @return: mips object
	 * @import: mips.class.php
	 */
	static public function mips($file) {
		return new \biophp\parser\mips($file);
	}
	
	/**
	 * @desc: get kegg info using http://rest.kegg.jp
	 * @return: kegg object
	 * @import: kegg.class.php
	 */
	static public function kegg() {
		return new \biophp\parser\kegg();
	}
}