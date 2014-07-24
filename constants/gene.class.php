<?php
namespace biophp\constants;
require_once (__DIR__ . "/../parser.class.php");

// all id here refers to entrez gene id
class gene {

	static private $db;
	
	/**
	 * @desc: convert a (list of) gene(s) to another name format
	 * @param: $g, the gene (list)
	 * @param: $from, The input format
	 * @param: $to, The output format
	 * @param: $assoc, Whether ouput the associated values
	 * @param: $species, The species
	 * @return: The converted name (list). The keys are the input if $assoc = true
	 */
	static public function convert ($g, $from, $to, $assoc = false, $species = 'human') {
		if (!self::$db) 
			self::$db = new \SQLite3(__DIR__ . "/gene/$species.sqlite", SQLITE3_OPEN_READONLY);
		
		$from = self::$db->escapeString(strtolower($from));
		$to   = self::$db->escapeString(strtolower($to));
		if (!is_array($g)) {
			$g = self::$db->escapeString($g);
			if ($from == 'id' or $from == 'entrez') 
				$ret = self::$db->querySingle("SELECT name FROM $to WHERE id = '$g'");
			elseif ($to == 'id' or $to == 'entrez')
				$ret = self::$db->querySingle("SELECT id FROM $from WHERE name = '$g'");
			else 
				$ret = self::$db->querySingle("SELECT b.name FROM $from AS a, $to as b WHERE a.id = b.id AND a.name = '$g'");
			return $assoc ? array($g => $ret) : $ret;
		} else {
			
			$g = array_map(array(self::$db, 'escapeString'), $g);
			$ret = array();
			
			if (!$assoc) {
				if ($from == 'id' or $from == 'entrez') {
					$result = self::$db->query ("SELECT name FROM $to WHERE id IN ('". implode("','", $g) ."')");
					while ($row = $result->fetchArray(SQLITE3_ASSOC)) 
						$ret[$row['name']] = 1;
				} elseif ($to == 'id' or $to == 'entrez') {
					$result = self::$db->query ("SELECT id FROM $from WHERE name IN ('". implode("','", $g) ."')");
					while ($row = $result->fetchArray(SQLITE3_ASSOC)) 
						$ret[$row['id']] = 1;
				} else {
					$result = self::$db->query ("SELECT b.name FROM $from AS a, $to AS b WHERE a.id = b.id AND a.name IN ('". implode("','", $g) ."')");
					while ($row = $result->fetchArray(SQLITE3_ASSOC))
						$ret[$row['b.name']] = 1;
				}
				return array_keys($ret);
			} else {
				if ($from == 'id' or $from == 'entrez') {
					foreach ($g as $gene) 
						$ret[$gene] = self::$db->querySingle("SELECT name FROM $to WHERE id = '$gene'");
				} elseif ($to == 'id' or $to == 'entrez') {
					foreach ($g as $gene)
						$ret[$gene] = self::$db->querySingle("SELECT id FROM $from WHERE name = '$gene'");
				} else {
					foreach ($g as $gene)
						$ret[$gene] = self::$db->querySingle("SELECT b.name FROM $from AS a, $to AS b WHERE a.id = b.id AND a.name = '$gene'");
				}
				return $ret;
			}
			
		}
	}
	
	
}