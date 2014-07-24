<?php
namespace biophp\math;
require_once (__DIR__ . "/stat.class.php");
require_once (__DIR__ . "/../constants/gene.class.php");

class gsea {

	const GSEA_HUMAN_GENE_NO = 45956;
	const GSEA_MOUSE_GENE_NO = 34566;
	
	/**
	 * @desc: do the hypergeometric test with the MsigDB
	 * @param: $params, The parameters, array.
	 *   genelist, The gene list
	 *   informat, The gene id format of the input gene list
	 *         Default: id
	 *   msig, The gene set in MsigDb
	 *         Default: c2.cp.kegg
	 *   pvalue, The pvalue cutoff
	 *         Default: 0.05
	 *   top, The top enriched set to show ordered by pvalue
	 *         Default: 10 (pvalue and top cutoff take effect together)
	 *   outformat, The output gene id format in details to show the overlap
	 *         If detail = true
	 *   species, The species. Default: human
	 *   detail, Whether to show the details (overlaps)
	 * @return: array( <gene set name> => array( pvalue => pvalue, overlaps => <Overlap genes> ) )
	 */
	static public function &hg ($params = array(
		'genelist'   => '',
		'informat'   => '',
		'misg'       => '',
		'outformat'  => '',
		'pvalue'     => '',
		'top'        => '',
		'species'    => '',
		'detail'     => ''
	)) {  
		
		$params = array_replace(array(
			'genelist'   => '',
			'informat'   => 'id',
			'misg'       => 'c2.cpg.kegg',
			'outformat'  => 'id',
			'pvalue'     => .05,
			'top'        => 10,
			'species'    => 'human',
			'detail'     => false
		), $params);
		extract($params);
		if (empty($genelist)) 
			throw new \Exception('No gene list offered');
		
		
		if ($informat != "id") 
			$genelist = \biophp\constants\gene::convert($genelist, $informat, "id");
		
		$ret = array();
		$genesets = \biophp\constants\msig::getSets($msig);
		
		$n = sizeof($genelist);
		$gs = array();
		$overlaps = array();
		foreach ($genesets as $id => $genes) {
			$genes = explode("\t", $genes);
			$m = sizeof($genes);
			$ov = array_intersect($genelist, $genes);
			$pv = \biophp\math\stat::hypergeometric(sizeof($ov), $m, $n, self::GSEA_HUMAN_GENE_NO);
			if ($pv <= $pvalue) {
				$gs[$id] = $pv;
				if ($detail) {
					$overlaps[$id] = $ov;
				}
			}
		}
		asort($gs);
		if ($top > 0 and sizeof($gs)>$top) 
			$gs = array_slice($gs, 0, $top);
		
		foreach ($gs as $id => $p) {
			$ret[$id] = array("pvalue" => $p);
			if ($detail) {
				$overlap = $overlaps[$id];
				if ($outformat != "id") {
					$overlap = \biophp\constants\gene::convert($overlap, "id", $outformat);
				}
				$ret[$id]["overlaps"] = $overlap;
			}
		}
		
		return $ret;
	}
	
	/**
	 * @desc: do the KS-like gsea
	 * @param: $genelist, The sorted gene list
	 * @param: $informat, The gene id format of the input gene list
	 *         Default: id
	 * @param: $ref, An array of ref genes
	 * @param: $refformat, Similar as $informat, the gene format of $ref
	 * @param: $nperm, No. of permutations, 0 means no permutations will be performed and no p-value offered
	 *         Default: 1000 ($nperm should > 100, and $nperm % 10 sould = 0)
	 * @param: $corr, The correlation of gene in $genelist with the phenotype
	 *         Default: array(1,1,...)
	 * @param: $weight: The weight of GSEA algorithm
	 * @return: array("ES" => <The Enrichment Score>, "p"=> <The pvalue (if $nperm offered)>)
	 */
	static public function ks ($genelist, $informat="id", $ref=array(), $refformat="id", $nperm=1000, $corr=array(), $weight=1) {
		
		if ($informat != "id") 
			$genelist = \biophp\constants\gene::convert($genelist, $informat, "id");
		if ($refformat != "id")
			$ref = \biophp\constants\gene::convert($ref, $refformat, "id");
		
		$ob_es = self::es($genelist, "id", $ref, "id", $corr, $weight);
		if ($nperm == 0) 
			return array("ES" => $ob_es, "p" => "N/A");
		
		$pos = 0; $p = "N/A";
		for ($i = 100; $i<$nperm; $i*=10) {
			$pos = 0;
			for ($j = 0; $j<$i; $j++) {
				shuffle ($genelist);
				$es = self::es($genelist, "id", $ref, "id", $corr, $weight);
				if ($es > $ob_es) $pos ++;
			}
			if ($pos > 0) {
				$p = ($pos+1) / $i;
				break;
			}
		}
		if ($p == "N/A") $p = ($pos+1)/$nperm;
		return array("ES" => $ob_es, "p" => $p);
	}
	
	/**
	 * @desc: do the KS-like gsea without permutation
	 * @param: $genelist, The sorted gene list
	 * @param: $informat, The gene id format of the input gene list
	 *         Default: id
	 * @param: $ref, An array of ref genes
	 * @param: $refformat, Similar as $informat, the gene format of $ref
	 * @param: $corr, The correlation of gene in $genelist with the phenotype
	 *         Default: array(1,1,...)
	 * @param: $weight: The weight of GSEA algorithm
	 * @return: The enrichment score
	 */
	static public function es ($genelist, $informat="id", $ref=array(), $refformat="id", $corr=array(), $weight=1) {
		if ($informat != "id") 
			$genelist = \biophp\constants\gene::convert($genelist, $informat, "id");
		if ($refformat != "id")
			$ref = \biophp\constants\gene::convert($ref, $refformat, "id");
			
		$NR = sizeof(array_intersect($genelist, $ref));
		$N  = sizeof($genelist);
		$Nh = sizeof($ref);
		$ref = array_fill_keys ($ref, 1);
		
		if (empty($corr)) $corr = array_fill(0, $N, 1);
		
		$es = -1; $phit = 0; $pmiss = 0;
		foreach ($genelist as $i=>$g) {
			if (isset($ref[$g])) {
				$phit += pow($corr[$i], $weight)/$NR;
			} else {
				$pmiss += 1/($N-$Nh);
			}
			if ($phit - $pmiss > $es)
				$es = $phit - $pmiss;
		}
		return $es;
	}

}