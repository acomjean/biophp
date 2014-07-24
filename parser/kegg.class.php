<?php
namespace biophp\parser;
require_once __DIR__ . "/../biophp.php";

class kegg {
	
	const API_URL = "http://rest.kegg.jp/";
	
	/**
	 * @desc: list a kegg database
	 * @param: $db, kegg database name
	 *         see: http://www.kegg.jp/kegg/rest/keggapi.html
	 * @param: $file, if offered, write the information into file
	 * @return: array(kegg obj id => kegg obj info, ...)
	 */
	public function &listdb ($db, $file='') {
		$t = \biophp\parser::txt(self::API_URL . "list/$db");
		if ($file) $f = fopen($file, 'w');
		$ret = array();
		while ($l = $t->read()) {
			$ret [$l[0]] = $l[1];
			if ($file) fwrite($f, $l . "\n");
		}
		if ($file) fclose($file);
		return $ret;
	}
	
	/**
	 * @desc: get the information of an kegg object
	 * @param: $objid, the object id
	 * @return: array(key=>val, ...)
	 */
	public function &object($objid) {
		$url = self::API_URL . "get/$objid";
		$ret = array();
		$f   = fopen($url, 'r');
		$lasttitle = '';
		$content   = '';
		while (!feof($f)) {
			$line  = fgets($f);
			$title = trim(substr($line, 0, 12));
			$cont  = trim(substr($line, 12));
			if ($title) {
				if ($lasttitle) {
					$ret[$lasttitle] = $content;
					$content = '';
				} 
				$content .= $cont;
				$lasttitle = $title;
			} else {
				$content .= "\n$cont";
			}
		}
		fclose($f);
		return $ret;
	}
}
