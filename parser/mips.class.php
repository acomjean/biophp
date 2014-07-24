<?php

namespace biophp\parser;

class mips {
	private $file;
	private $interactions;
	private $shortname;
	private $name;
	private $refs;

	public function __construct ($file) {
		$xml = simplexml_load_file($file);
		$this->shortname = strval($xml->entry->source->names->shortLabel);
		$this->name      = strval($xml->entry->source->names->fullName);
		$this->refs      = array();
		foreach ($xml->entry->source->bibref->xref->primaryRef as $ref) {
			$attr = $ref->attributes();
			$this->refs[] = array('db'=>strval($attr->db), 'id'=>strval($attr->id));
		}
		foreach ($xml->entry->interactionList->interaction as $interaction) {
			$this->interactions[] = new \biophp\parser\mipsinteraction($interaction->asXML());
		}
	}
	
	/**
	 * @desc: get the interactions
	 * @return: the array of interaction objects
	 * @new: mipsinteraction
	 */
	public function interactions () {
		return $this->interactions;
	}
	
	/**
	 * @desc: get the number of interactions
	 * @return: the number
	 */
	public function size () {
		return sizeof($this->interactions);
	}
	
	/**
	 * @desc: get the name of the database
	 * @return: the name
	 */
	public function name () {
		return $this->name;
	}
	
	/**
	 * @desc: get the short name of the database
	 * @return: the short name
	 */
	public function shortname() {
		return $this->shortname;
	}
	
	/**
	 * @desc: get the references of the database
	 * @return: array(array('db'=>'PubMed', id=>'123456'), ...)
	 */
	public function refs() {
		return $this->refs;
	}
}



// MIPS interaction
class mipsinteraction {
	
	private $experiments;
	private $protein1;
	private $protein2;
	
	public function __construct ($xml) {
		$doc = simplexml_load_string($xml);
		foreach ($doc->experimentList->experimentDescription as $exp) {
			$exprefs = array();
			foreach ($exp->bibref->xref->primaryRef as $expref) {
				$expattrs = $expref->attributes();
				$exprefs[] = array('db'=>strval($expattrs->db), 'id'=>strval($expattrs->id));
			}
			$interactionDetectLabel = strval($doc->experimentList->experimentDescription->interactionDetection->names->shortLabel);
			$indetattrs = $doc->experimentList->experimentDescription->interactionDetection->xref->primaryRef->attributes();
			$interactionDetectRef = array('db'=>strval($indetattrs->db), 'id'=>strval($indetattrs->id));
		}
		$this->experiments['ref'] = $exprefs;
		$this->experiments['label'] = $interactionDetectLabel;
		$this->experiments['detectref'] = $interactionDetectRef;
		
		$this->protein1['name'] = strval($doc->participantList->proteinParticipant[0]->proteinInteractor->names->fullName);
		$taxattr = $doc->participantList->proteinParticipant[0]->proteinInteractor->organism->attributes();
		$this->protein1['taxid'] = strval($taxattr->ncbiTaxId);
		$attrs = $doc->participantList->proteinParticipant[0]->proteinInteractor->xref->primaryRef->attributes();
		$this->protein1['ref']  = array('db'=>strval($attrs->db), 'id'=>strval($attrs->id));
		$attrs = $doc->participantList->proteinParticipant[0]->confidence->attributes();
		$this->protein1['confidence'] = array('unit'=>strval($attrs->unit), 'value'=>strval($attrs->value));
		$this->protein1['isTagged'] = strval($doc->participantList->proteinParticipant[0]->isTaggedProtein);
		$this->protein1['isOverexpressed'] = strval($doc->participantList->proteinParticipant[0]->isOverexpressedProtein);
		
		$this->protein2['name'] = strval($doc->participantList->proteinParticipant[1]->proteinInteractor->names->fullName);
		$taxattr = $doc->participantList->proteinParticipant[1]->proteinInteractor->organism->attributes();
		$this->protein2['taxid'] = strval($taxattr->ncbiTaxId);
		$attrs = $doc->participantList->proteinParticipant[1]->proteinInteractor->xref->primaryRef->attributes();
		$this->protein2['ref']  = array('db'=>strval($attrs->db), 'id'=>strval($attrs->id));
		$attrs = $doc->participantList->proteinParticipant[1]->confidence->attributes();
		$this->protein2['confidence'] = array('unit'=>strval($attrs->unit), 'value'=>strval($attrs->value));
		$this->protein2['isTagged'] = strval($doc->participantList->proteinParticipant[1]->isTaggedProtein);
		$this->protein2['isOverexpressed'] = strval($doc->participantList->proteinParticipant[1]->isOverexpressedProtein);
	}
	
	/**
	 * @desc: get the reference about the expriments
	 * @return: array(array('db'=>'PubMed', 'id'=>123456), ...)
	 */
	public function expRefs () {
		return $this->experiments['ref'];
	}
	
	/**
	 * @desc: get the label of the expriments
	 * @return: the label (e.g.: two hybrid)
	 */
	public function expLabel () {
		return $this->experiments['label'];
	}
	
	/**
	 * @desc: get the references of the detection methods
	 * @return: array('db'=>'MI', 'id'=>'0018')
	 */
	public function expDetectRef () {
		return $this->experiments['detectref'];
	}
	
	/**
	 * @desc: get the name of protein1
	 * @return: the name
	 */
	public function protein1Name () {
		return $this->protein1['name'];
	}
	
	/**
	 * @desc: get the taxonomy id of protein1
	 * @return: the taxonomy id
	 */
	public function protein1Taxid () {
		return $this->protein1['taxid'];
	}
	
	/**
	 * @desc: get the reference of protein1
	 * @return: array('db'=>'SP', 'id'=>'P27005')
	 */
	public function protein1Ref () {
		return $this->protein1['ref'];
	}
	
	/**
	 * @desc: get the confidence of protein1
	 * @return: array('unit'=>'boolean', 'value'=>'1')
	 */
	public function protein1Confidence () {
		return $this->protein1['confidence'];
	}
	
	/**
	 * @desc: tell if protein1 is tagged
	 * @return: true|false
	 */
	public function protein1isTagged () {
		return $this->protein1['isTagged'] == 'true';
	}
	
	/**
	 * @desc: tell if protein1 is overexpressed
	 * @return: true|false
	 */
	public function protein1isOverexpressed () {
		return $this->protein1['isOverexpressed'] == 'true';
	}
	
	
	/**
	 * @desc: get the name of protein2
	 * @return: the name
	 */
	public function protein2Name () {
		return $this->protein2['name'];
	}
	
	/**
	 * @desc: get the taxonomy id of protein2
	 * @return: the taxonomy id
	 */
	public function protein2Taxid () {
		return $this->protein2['taxid'];
	}
	
	/**
	 * @desc: get the reference of protein2
	 * @return: array('db'=>'SP', 'id'=>'P27005')
	 */
	public function protein2Ref () {
		return $this->protein2['ref'];
	}
	
	/**
	 * @desc: get the confidence of protein2
	 * @return: array('unit'=>'boolean', 'value'=>'1')
	 */
	public function protein2Confidence () {
		return $this->protein2['confidence'];
	}
	
	/**
	 * @desc: tell if protein2 is tagged
	 * @return: true|false
	 */
	public function protein2isTagged () {
		return $this->protein2['isTagged'] == 'true';
	}
	
	/**
	 * @desc: tell if protein2 is overexpressed
	 * @return: true|false
	 */
	public function protein2isOverexpressed () {
		return $this->protein2['isOverexpressed'] == 'true';
	}
}