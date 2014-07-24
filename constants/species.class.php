<?php
namespace biophp\constants;

class species {
	/**
	 * @desc: get taxonomy id of a species
	 * @param: $species, the species, either common name (human) 
	 *         or taxonomy name (homo sapiens)
	 * @return: the taxonomy id
	 */ 
	static public function spToTaxid ($species) {
		switch (strtolower($species)) {
			case 'arabidopsis thaliana':
			case 'a. thaliana':
			case 'mouse-ear cress':
			case 'thale cress':
			case 'thale-cress':            return 3702;
			case 'escherichia coli':
			case 'e. coli':                return 562;
			case 'pneumocystis carinii':
			case 'p. carinii':             return 4754;
			case 'bos taurus':
			case 'b. taurus':
			case 'even-toed ungulates':
			case 'domestic cow':
			case 'domestic cattle':
			case 'cow':
			case 'bovine':                 return 9913;
			case 'hepatitis c virus':
			case 'h. c virus':
			case 'human hepatitis c virus':
			case 'hcv':                    return 11103;
			case 'rattus norvegicus':
			case 'r. norvegicus':
			case 'rats':
			case 'rat':
			case 'brown rat':              return 10116;
			case 'caenorhabditis elegans':
			case 'c. elegans':
			case 'rhabditis elegans':
			case 'nematode':               return 6239;
			case 'homo sapiens':
			case 'h. sapiens':
			case 'human':                  return 9606;
			case 'saccharomyces cerevisiae':
			case 's. cerevisiae':
			case 'baker\'s yeast':
			case 'yeast':
			case 'lager beer yeast':
			case 'brewer\'s yeast':        return 4932;
			case 'chlamydomonas reinhardtii':
			case 'c. reinhardtii':
			case 'chlamydomonas smithii':  return 3055;
			case 'mus musculus':
			case 'm. musculus':
			case 'mouse':                  return 10090;
			case 'schizosaccharomyces pombe':
			case 's. pombe':               return 4896;
			case 'danio rerio':
			case 'zebrafish':
			case 'd. rerio':
			case 'zebra fish':
			case 'zebra danio':
			case 'leopard danio':          return 7955;
			case 'mycoplasma pneumoniae':
			case 'm. pneumoniae':          return 2104;
			case 'takifugu rubripes':
			case 't. rubripes':
			case 'torafugu':
			case 'tiger puffer':           return 31033;
			case 'dictyostelium discoideum':
			case 'd. discoideum':          return 44689;
			case 'oryza sativa':
			case 'o. sativa':
			case 'rice':                   return 4530;
			case 'xenopus laevis':
			case 'x. laevis':
			case 'platanna':
			case 'common platanna':
			case 'clawed frog':            return 8355;
			case 'drosophila melanogaster':
			case 'd. melanogaster':
			case 'fruit fly':              return 7227;
			case 'plasmodium falciparum':
			case 'p. falciparum':          return 5833;
			case 'zea mays':
			case 'z. mays':
			case 'maize':                  return 4577;
		}
		return 0;
	}
	
	/**
	 * @desc: get the species name of a taxonomy id
	 * @param: $taxid, the taxonomy id
	 * @return: the species name
	 */ 
	static public function taxidToSp ($taxid) {
		switch ($taxid) {
			case 3702:  return 'Arabidopsis thaliana';
			case 562:   return 'Escherichia coli';
			case 4754:  return 'Pneumocystis carinii';
			case 9913:  return 'Bos taurus';
			case 11103: return 'Hepatitis C virus';
			case 10116: return 'Rattus norvegicus';
			case 6239:  return 'Caenorhabditis elegans';
			case 9606:  return 'Homo sapiens';
			case 4932:  return 'Saccharomyces cerevisiae';
			case 3055:  return 'Chlamydomonas reinhardtii';
			case 10090: return 'Mus musculus';
			case 4896:  return 'Schizosaccharomyces pombe';
			case 7955:  return 'Danio rerio';
			case 2104:  return 'Mycoplasma pneumoniae';
			case 31033: return 'Takifugu rubripes';
			case 44689: return 'Dictyostelium discoideum';
			case 4530:  return 'Oryza sativa';
			case 8355:  return 'Xenopus laevis';
			case 7227:  return 'Drosophila melanogaster';
			case 5833:  return 'Plasmodium falciparum';
			case 4577:  return 'Zea mays';
		}
		return '';
	}
}
