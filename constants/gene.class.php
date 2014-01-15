<?php
namespace biophp\constants;
require_once (__DIR__ . "/../parser.class.php");

// all id here refers to entrez gene id
class gene {

	static private $multiRecordDb = array('RefSeq', 'Synonyms', 'Unigene', 'UniProt', 'EnsemblProtein');
	static private $cache = array();
	
	/**
	 * @desc: convert a (list of) xref id(s) to entrez gene id(s)
	 * @param: $xref, the xref value (list)
	 * @param: $xrefdb, the external db
	 *         Supported db: Ensembl, HGNC(human only), MGI(mouse only), 
	 *         RefSeq, Symbol, Synonyms, Unigene, UniProt
	 *         More than one dbs are also supported to search for the id
	 *         E.g.: Symbol+Synonyms, if Symbol not found than find Synonyms
	 * @param: $species, the species, default: human
	 *         Supported species: human, mouse
	 * @param: $case, whether to search case-sensitively or case-insensitively
	 *         Default: true (case-sensitive)
	 * @return: the entrez gene id. 
	 *         (if input is a list, return an array with the xref id as key, 
	 *         and entrez gene id as value)
	 */
	static public function &xref2Id ($xref, $xrefdb, $case=true, $species = 'human') {
		$dbs   = explode('+', $xrefdb);
		$xref1 = (array)$xref;
		$ret   = array();
		while (sizeof($dbs) and sizeof($xref1)) {
			$db  = array_shift($dbs);
			$all = self::allXref2Id($db, $case, $species);
			$tmp = array();
			foreach ($xref1 as $x) {
				$y = !$case ? strtoupper($x) : $x;
				if (isset($all[$y])) $ret[$x] = $all[$y];
				else $tmp[] = $x;
			}
			$xref1 = $tmp;
		}
		foreach ($xref1 as $x) $ret[$x] = '';
		if (!is_array($xref)) return $ret[$xref];
		return $ret;
	}
	
	/**
	 * @desc: convert a (list of) entrez gene id(s) to xref value(s)
	 * @param: $id, the entrez gene id (list)
	 * @param: $xrefdb, the external db
	 *         Supported db: Ensembl, HGNC(human only), MGI(mouse only), 
	 *         RefSeq, Symbol, Synonyms, Unigene, UniProt
	 * @param: $species, the species, default: human
	 *         Supported species: human, mouse
	 * @return: the xref value (Synonyms, RefSeq, Unigene, UniProt returns an array). 
	 *         (if input is a list, return an array with entrez gene id as key, 
	 *         and xref value as value)
	 */
	static public function &id2Xref ($id, $xrefdb, $species = 'human') {
		$all = self::allId2Xref ($xrefdb, $species);
		if (is_array($id)) {
			foreach ($id as $i)
				$ret[$i] = @$all[$i];
		} else $ret = @$all[$id];
		return $ret;
	}
	
	static public function &xref2Xref ($xrefs, $from, $to, $species = 'human') {
		$ret = array();
		$ids = self::xref2Id ($xrefs, $from, $species);
		$out = self::id2Xref ($ids, $to, $species);
		if (!is_array($out) and !is_array($ids)) return $out;
		foreach ($ids as $xref => $id) {
			$ret[$xref] = $out[$id];
		}
		return $ret;
	}
	
	/**
	 * @desc: load all xref to id array
	 * @param: $xrefdb, the external db
	 *         Supported db: Ensembl, HGNC(human only), MGI(mouse only), 
	 *         RefSeq, Symbol, Synonyms, Unigene, UniProt
	 * @param: $case, whether to search case-sensitively or case-insensitively
	 *         Default: true (case-sensitive)
	 * @param: $species, the species, default: human
	 *         Supported species: human, mouse
	 * @return: the array of all xref values to ids
	 */
	static public function &allXref2Id ($xrefdb, $case=true, $species = 'human') {
		$case = intval($case);
		if (isset(self::$cache["xref2id_{$xrefdb}_{$case}_$species"])) 
			return self::$cache["xref2id_{$xrefdb}_{$case}_$species"];
			
		$datafile = __DIR__ . "/gene/$species/$xrefdb.txt.gz";
		$dataobj  = \biophp\parser::txt($datafile);
		self::$cache["xref2id_{$xrefdb}_{$case}_$species"] = array();
		while ($obj = $dataobj->read()) {
			list($key, $val) = $obj;
			$val = explode('|', $val);
			foreach ($val as $v)
				self::$cache["xref2id_{$xrefdb}_{$case}_$species"][!$case ? strtoupper($v) : $v] = $key;
		}
		return self::$cache["xref2id_{$xrefdb}_{$case}_$species"];
	}
	
	/**
	 * @desc: load all id to id array
	 * @param: $xrefdb, the external db
	 *         Supported db: Ensembl, HGNC(human only), MGI(mouse only), 
	 *         RefSeq, Symbol, Synonyms, Unigene, UniProt
	 * @param: $species, the species, default: human
	 *         Supported species: human, mouse
	 * @return: the array of all ids to xref values
	 */
	static public function &allId2Xref ($xrefdb, $species = 'human') {
		if (isset(self::$cache["id2xref_{$xrefdb}_$species"])) 
			return self::$cache["id2xref_{$xrefdb}_$species"];
	
		$datafile = __DIR__ . "/gene/$species/$xrefdb.txt.gz";
		$dataobj  = \biophp\parser::txt($datafile, '"key":1,"val":2');
		self::$cache["id2xref_{$xrefdb}_$species"] = array();
		while ($obj = $dataobj->read()) {
			$key = $obj['key'];
			self::$cache["id2xref_{$xrefdb}_$species"][$key] = in_array($xrefdb, self::$multiRecordDb) ? explode('|', $obj['val']) : $obj['val'];
		}
		return self::$cache["id2xref_{$xrefdb}_$species"];
	}
	
	/**
	 * @desc: release the cache if conversion finished
	 */
	static public function clearCache() {
		self::$cache = array();
	}
	
}