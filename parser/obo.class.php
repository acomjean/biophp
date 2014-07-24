<?php

namespace biophp\parser;

class obo {
	private $file;
	private $obo;
	private $gz;

	public function __construct ($file) {
		$this->gz   = substr($file, -3) === '.gz';
		$this->file = $file;
		$this->obo = array(
			'Header' => array(),
			'Term'   => array(),
			'Typedef'=> array()
		);
		$fopen = $this->gz ? 'gzopen' : 'fopen';
		$feof  = $this->gz ? 'gzeof'  : 'feof';
		$fgets = $this->gz ? 'gzgets' : 'fgets';
		$f = $fopen($file, 'r');
		if (!$f)
			throw new \Exception("Cannot open file: $file");
		$flag = '';
		$id = '';
		$fields = array();
		while (!$feof($f)) {
			$line = trim($fgets($f));
			if (!$line) continue;
			if ($line == '[Term]' or $line == '[Typedef]' or $line == '[Instance]') {
				if ($flag == 'Term' and sizeof($fields)>0 and $id) {
					$this->obo['Term'][$id] = new \biophp\parser\oboterm($id, $fields);
					$id = '';
					$fields = array();
				}
				$flag = substr($line, 1, -1);
			} else {
				$field = substr($line, 0, strpos($line, ':'));
				$value = substr($line, strlen($field)+2);
				if ($flag and $field=='id')  {
					$id = substr($line, 4);
				} elseif (!$flag) { // headers
					switch ($field) {
						case 'subsetdef':
						case 'import':
						case 'synonymtypedef':
						case 'idspace':
						case 'id-mapping':
						case 'remark':
							$this->obo['Header'][$field][] = $value;
							break;
						default:
							$this->obo['Header'][$field] = $value;
							break;
					}
				} elseif ($flag == 'Term') {
					switch ($field) {
						case 'alt_id':
						case 'comment':
						case 'subset':
						case 'synonym':
						case 'xref':
						case 'intersection_of':
						case 'replaced_by':
						case 'consider':
						case 'use_term':
						case 'relationship':
						case 'disjoint_from':
						case 'union_of':
						case 'is_a':
							$fields[$field][] = $value;
							break;
						default:
							$fields[$field] = $value;
							break;
					}
				} elseif ($flag == 'Typedef') {
					switch ($field) {
						case 'alt_id':
						case 'comment':
						case 'subset':
						case 'synonym':
						case 'xref':
						case 'intersection_of':
						case 'replaced_by':
						case 'consider':
						case 'use_term':
						case 'relationship':
						case 'disjoint_from':
						case 'union_of':
						case 'is_a':
						case 'transitive_over':
							$this->obo[$field][] = $value;
							break;
						default:
							$this->obo[$field] = $value;
							break;
					}
				} elseif ($flag == 'Instance') {
					$this->obo[$field] = $value;
				}
			}
		}
		fclose($f);
	}

	/**
	 * @desc: get an obo term object by its id
	 * @param: $id, the id of the term
	 * @return: oboterm object
	 * @new: oboterm
	 */
	public function term($id) {
		return $this->obo['Term'][$id];
	}

	/**
	 * @desc: get the root term(s)
	 * @return: oboterm object(s)
	 * @new: oboterm
	 */
	public function &root() {
		$roots = array();
		foreach ($this->obo['Term'] as $id => $term) {
			// may be should add more specific conditions
			if (sizeof($term->is_a())==0 and sizeof($term->relationship('part_of'))==0 and !$term->is_obsolete()) {
				$roots[] = $term;
			}
		}
		return sizeof($roots)==1 ? $roots[0] : $roots;
	}

	/**
	 * @desc: get the size of the obo (number of terms)
	 * @return: the number of terms
	 */
	public function size () {
		return sizeof($this->obo['Term']);
	}

	/**
	 * @desc: get all terms of the obo
	 * @return: array(term obj1, ...)
	 */
	public function &terms () {
		return $this->obo['Term'];
	}

	/**
	 * @desc: use part_of and is_a to build the tree structure
	 * @param: $rtree, the reversed tree
	 * @return: array(father_id1=>array(son_id1, ...), ...)
	 */
	public function &tree (&$rtree = NULL) {
		$tree = array();
		foreach ($this->obo['Term'] as $term) {
			$id = $term->id();
			foreach ( array_merge(
				(array)$term->relationship('part_of'), 
				(array)$term->is_a(),
				(array)$term->relationship("regulates"),
				(array)$term->relationship("positively_regulates"),
				(array)$term->relationship("negatively_regulates")
				//(array)$term->relationship("occurs_in"),
			) as $father) {
				$tree[$father][] = $id;
				if ($rtree!==NULL) $rtree[$id][] = $father;
			}
			if ($term->is_obsolete()) {
				$tree[$id] = array();
				if ($rtree!==NULL) $rtree[$id] = array();
			}
		}
		$roots = $this->root();
		if ($roots instanceof oboterm) {
			$tree['__ROOT__'][] = $roots->id();
			if ($rtree!==NULL) $rtree[$roots->id()][] = '__ROOT__';
		} else
			foreach ($this->root() as $root) {
				$tree['__ROOT__'][] = $root->id();
				if ($rtree!==NULL) $rtree[$root->id()][] = '__ROOT__';
			}
		return $tree;
	}

	/**
	 * @desc: get children of a term
	 * @id: the id of the father term
	 * @param: $all, whether get all offsprings or just direct children, default: false
	 * @param: $tree, The tree (father=>sons). If not offered, will use $this->tree()
	 * @return: array(id1, ...)
	 */
	public function &children($id, $all=false, $tree=NULL) {
		$ret = array();
		if (!$all) {
			foreach ($this->obo['Term'] as $term) {
				if (in_array ($id, array_merge(
					(array)$term->relationship('part_of'), 
					(array)$term->is_a(),
					(array)$term->relationship("regulates"),
					(array)$term->relationship("positively_regulates"),
					(array)$term->relationship("negatively_regulates")
					//(array)$term->relationship("occurs_in"),
				)))
				$ret[] = $term->id();
			}
		} else {
			if (empty($tree)) $tree = $this->tree();
				
			$children = (array)$tree[$id];
			$ret = $children;
			while (sizeof($children)) {
				$children1 = $children;
				$children  = array();
				foreach ($children1 as $child) {
					//if (!is_array($children)) print_r($children);
					$ret = array_merge($ret, (array)$tree[$child]);
					$children = array_unique(array_merge($children, (array)$tree[$child]));
				}
			}
		}
		$ret = array_unique($ret);
		return $ret;
	}
	
	/**
	 * @desc: get fathers of a term
	 * @id: the id of the child term
	 * @param: $all, whether get all fathers or just direct father, default: false
	 * @param: $rtree, The reverse tree (son=>fathers). If not offered, will use $this->tree()
	 * @return: array(id1, ...)
	 */
	public function &fathers($id, $all=false, &$rtree=NULL) {
		$ret = array();
		if (!$all) {
			$term = $this->obo['Term'][$id];
			$ret = array_unique(
				array_merge(
					(array)$term->relationship('part_of'), 
					(array)$term->is_a(),
					(array)$term->relationship("regulates"),
					(array)$term->relationship("positively_regulates"),
					(array)$term->relationship("negatively_regulates")
					//(array)$term->relationship("occurs_in"),
				)
			);
		} else {
			if (empty($rtree)) $this->tree($rtree);
			
			$fathers = (array)$rtree[$id];
			$ret = $fathers;
			while (sizeof($fathers)) {
				$fathers1 = $fathers;
				$fathers = array();
				foreach ($fathers1 as $father) {
					if ($rtree[$father] == '__ROOT__' or $father == '__ROOT__') continue;
					$ret = array_merge($ret, (array)$rtree[$father]);
					$fathers = array_unique(array_merge($fathers, (array)$rtree[$father]));
				}
			}
		}
		$ret = array_unique($ret);
		$ret = array_diff($ret, array("__ROOT__"));
		return $ret;
	}

	/**
	 * @desc: find the co-fathers of two set of terms
	 * @param: $gos1, the go term set 1
	 * @param: $gos2, the go term set 2
	 * @param: $rtree, the reversed tree, son => fathers
	 * @return: the cofathers, array(father1=>n1, ...),
	 *          n1 is the total length retrived
	 */
	public function &cofathers ($gos1, $gos2, &$rtree = NULL) {
		$ret = array();
		$gos1 = (array)$gos1;
		$gos2 = (array)$gos2;
		if (sizeof($gos1)==0 or sizeof($gos2)==0) return $ret;
		$co  = array_intersect($gos1, $gos2);
		if (sizeof($co)>0) {
			$ret = array_combine ($co, array_fill(0, sizeof($co), 0));
			return $ret;
		}
		if ($rtree === NULL) {
			if (!$this->rtree) {
				$this->rtree = array();
				$this->tree($this->rtree);
			}
			$rtree = &$this->rtree;
		}
		$i = 0; $j = 0; // how many levels are retrived together
		$fa1[$i] = array_combine($gos1, array_fill(0, sizeof($gos1), 1));
		$fa2[$j] = array_combine($gos2, array_fill(0, sizeof($gos2), 1));
		while (true) {
			if ($i <= $j) { $tofind = &$fa1; $tocheck = &$fa2; $i++; }
			else          { $tofind = &$fa2; $tocheck = &$fa1; $j++; }

			$newindex = sizeof($tofind);
			foreach ($tofind[$newindex-1] as $tf=>$_) {
				$fa = $rtree[$tf];
				if (!$fa) continue;
				foreach ($fa as $f) $tofind[$newindex][$f] = 1;
			}
			if (!$tofind[$newindex]) {
				//unset($this->rtree);
				//unset($rtree);
				return $ret;
			}
			$onemoretime = true;
			foreach ($tocheck as $k=>$toc) {
				foreach ($toc as $t=>$_) {
					if (isset($ret[$t]) or !isset($tofind[$newindex][$t])) continue;
					$ret[$t] = $newindex + $k;
					if ($k < sizeof($tocheck)-1)
						$onemoretime = false;
				}
				if (!$onemoretime) break;
			}

			if (sizeof($ret)>0 and !$onemoretime and $i==$j) {
				//unset($this->rtree);
				//unset($rtree);
				return $ret;
			}
		}
	}

	/**
	 * @desc: get id-name list of all terms
	 * @return array(id1=>name1, ...)
	 */
	public function &idNameList() {
		$ret = array();
		foreach ($this->obo['Term'] as $term) {
			$ret[$term->id()] = $term->name();
		}
		return $ret;
	}
}


// obo term
class oboterm {
	private $fields;
	private $id;

	public function __construct($id, $fields) {
		$this->id     = $id;
		$this->fields = $fields;
	}

	/**
	 * @desc: get the id of the term
	 * @return: the id
	 */
	public function id() {
		return $this->id;
	}

	/**
	 * @desc: get the name of the term
	 * @return: the name
	 */
	public function name() {
		return $this->fields['name'];
	}

	/**
	 * @desc: get the creator of the term
	 * @return: the creator
	 */
	public function created_by() {
		return $this->fields['created_by'];
	}

	/**
	 * @desc: get the creation date of the term
	 * @return: the creation date
	 */
	public function creation_date() {
		return $this->fields['creation_date'];
	}

	/**
	 * @desc: get the definition of the term
	 * @return: the definition
	 */
	public function def() {
		return $this->fields['def'];
	}

	/**
	 * @desc: get the alternative ids of the term
	 * @return: array(id1,...)
	 */
	public function alt_id() {
		return $this->fields['alt_id'];
	}

	/**
	 * @desc: get the comments of the term
	 * @return: array(comment1,...)
	 */
	public function comment() {
		return $this->fields['comment'];
	}

	/**
	 * @desc: get the subsets of the term
	 * @return: array(subset1,...)
	 */
	public function subset() {
		return $this->fields['subset'];
	}

	/**
	 * @desc: get the synonyms of the term
	 * @return: array(synonym1,...)
	 */
	public function synonym() {
		return $this->fields['synonym'];
	}

	/**
	 * @desc: get the xrefs of the term
	 * @return: array(xref1,...)
	 * @todo: may need further parsing
	 */
	public function xref() {
		return $this->fields['xref'];
	}

	/**
	 * @desc: get the relationships of the term
	 * @return: array(relationship1,...)
	 */
	public function relationship($rel="") {
		if (!$rel)
			return $this->fields['relationship'];
		else {
			$ret = array();
			foreach ((array)$this->fields['relationship'] as $relship) {
				list($name, $go) = explode(" ", $relship);
				$ret[] = $go;
			}
			return $ret;
		}
	}

	/**
	 * @desc: tell whether the term is anonymous
	 * @return: true|false
	 */
	public function is_anonymous() {
		return $this->fields['is_anonymous'] == 'true';
	}

	/**
	 * @desc: tell whether the term is obsolete
	 * @return: true|false
	 */
	public function is_obsolete() {
		return $this->fields['is_obsolete'] == 'true';
	}

	/**
	 * @desc: get the term ids that have the relation is_a with this term
	 * @return: array(id1,...)
	 */
	public function is_a () {
		if (!isset($this->fields['is_a'])) return array();
		$ret = array();
		foreach ((array)$this->fields['is_a'] as $is_a) {
			$tmp = explode(' ! ', $is_a);
			$ret[] = $tmp[0];
		}
		return $ret;
	}

	/**
	 * @desc: get the term ids that have the relation intersection_of with this term
	 * @return: array(id1,...)
	 */
	public function intersection_of() {
		if (!isset($this->fields['intersection_of'])) return array();
		$ret = array();
		foreach ((array)$this->fields['intersection_of'] as $intersection_of) {
			$tmp = explode(' ! ', $intersection_of);
			$ret[] = $tmp[0];
		}
		return $ret;
	}

	/**
	 * @desc: get the term ids that have the relation union_of with this term
	 * @return: array(id1,...)
	 */
	public function union_of() {
		if (!isset($this->fields['union_of'])) return array();
		$ret = array();
		foreach ((array)$this->fields['union_of'] as $union_of) {
			$tmp = explode(' ! ', $union_of);
			$ret[] = $tmp[0];
		}
		return $ret;
	}

	/**
	 * @desc: get the term ids that have the relation disjoint_from with this term
	 * @return: array(id1,...)
	 */
	public function disjoint_from() {
		if (!isset($this->fields['disjoint_from'])) return array();
		$ret = array();
		foreach ((array)$this->fields['disjoint_from'] as $disjoint_from) {
			$tmp = explode(' ! ', $disjoint_from);
			$ret[] = $tmp[0];
		}
		return $ret;
	}

	/**
	 * @desc: get the term ids that have the relation replaced_by with this term
	 * @return: array(id1,...)
	 */
	public function replaced_by() {
		if (!isset($this->fields['replaced_by'])) return array();
		$ret = array();
		foreach ((array)$this->fields['replaced_by'] as $replaced_by) {
			$tmp = explode(' ! ', $replaced_by);
			$ret[] = $tmp[0];
		}
		return $ret;
	}

	/**
	 * @desc: get the term ids that have the relation consider with this term
	 * @return: array(id1,...)
	 */
	public function consider() {
		if (!isset($this->fields['consider'])) return array();
		$ret = array();
		foreach ((array)$this->fields['consider'] as $consider) {
			$tmp = explode(' ! ', $consider);
			$ret[] = $tmp[0];
		}
		return $ret;
	}


}
