<?php
namespace biophp\constants;

// all id here refers to entrez gene id
class msig {
	
	static private $db;
	
	/**
	 * @desc: get gene sets of the type
	 * @param: $type The gene set types
	 *         See http://www.broadinstitute.org/gsea/msigdb/annotate.jsp
	 *         Use plus sign(+) to separate different types (c2.cp.kegg+c5.mf)
	 * @param: $species, The species. Default: human
	 * @return: array( <Gene Set Name> => <Entrez Gene Ids>)
	 */
	 
	static public function &getSets ($type, $species='human') {
		if (!self::$db) 
			self::$db = new \SQLite3(__DIR__ . "/msig/$species.sqlite", SQLITE3_OPEN_READONLY);
		
		$types = explode("+", $type);
		$ret = array();
		foreach ($types as $type) {
			$type = self::$db->escapeString($type);
			$result = self::$db->query("SELECT id,genes FROM msig WHERE type LIKE '$type%'");
			while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
				$ret[$row['id']] = $row['genes'];
			}
		}
		return $ret;
	}
	
	/**
	 * @desc: get gene set of the id and type
	 * @param: $type The gene set type
	 *         See http://www.broadinstitute.org/gsea/msigdb/annotate.jsp
	 * @param: $id The id
	 * @param: $species, The species. Default: human
	 * @return: The gene ids
	 */
	static public function get ($id, $type, $species='human') {
		if (!self::$db) 
			self::$db = new \SQLite3(__DIR__ . "/msig/$species.sqlite", SQLITE3_OPEN_READONLY);
		
		$id = $db->escapeString($id);
		$type = $db->escapeString($type);
		return self::$db->querySingle("SELECT genes FROM msig WHERE id='$id' AND type LIKE '$type%'");
	}
	
}
	