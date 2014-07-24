<?php

require_once __DIR__ ."/biophp.php";
/*
echo "- Testing biophp\utils\args\n";
$opts = \biophp\utils::args(array(
	array('i', 'PPI file', true, 'ppi'),
	array('g', 'GO obo file', true, 'goobo'),
	array('a', 'GO annotation file', true, 'goanno'),
	array('c', 'GO annotation cutoff', 1000, 'cutoff'),
	array('n', 'Number of pairs to fetch', 100, 'number'),
));

foreach ($opts as $k=>$v) {
	echo "  $k : $v\n";
}

echo "- Testing \biophp\constants\species\spToTaxid\n";
$taxid = \biophp\constants\species::spToTaxid('human');
echo "  The taxonomy id of human is: $taxid \n";
echo "- Testing \biophp\constants\genes\idToXref\n";*/
$genes = \biophp\constants\gene::convert(1, 'id', 'ensemblGene');
echo "  The ensembl gene id of entrez gene id 1 is: $genes\n";
echo "  The entrez gene id of ENSG00000183044 is " . \biophp\constants\gene::convert('ENSG00000183044', 'EnsemblGene', 'id') . "\n";

$genes = \biophp\constants\gene::convert(array(1,10,100,101,102,103,104,107,108,109,111,112,113,115,116,117,118,119,12,120,123,124,126,127,128,13,130,131,132,134,135,136,14,140,141,142,143,146,148,15,150,151,152,153,154),
										 'id', 'symbol', true);

//print_r (\biophp\constants\gene::convert($genes, 'synonyms', 'id', true));

print_r($genes);

print_r (\biophp\math\gsea::hg(array(
"ADORA2A",
"ADORA2B",
"ADRA1A",
"ADRA1D",
"ADRB1",
"ADRB2",
"ADCYAP1R1",
"ADORA1",
"ADORA3",
"ADRA2A",
"ADRA2B",
"ADRA2C",
"ADCY3",
"ADCY9",
"ADCY1",
"ADCY2",
"ADCY7",
"ADCY6",
"ADCY5",
"ADA",
"ADK",
"ADH1A",
"ADH1C",
"ADH4",
"ADH5",
"ADH6",
"ADH7",
"PARP1",
"PARP4",
"ADAM10",
"ADAR",
"NAT2",
"AANAT",
"A1BG",
"AADAC",
"AAMP",
"ADAM8",
"ADARB1",
"ADCYAP1",
"ADD1",
"ADD2",
"ADD3",
"ADPRH",
"SERPINA3"
)
,
"symbol",
"c2.cp.kegg",
"symbol",
.05,
10,
false

));


print_r (\biophp\math\gsea::ks(array(1,2,3,4,5), "id", array(4,5), "id", 1000));
/*
echo "- Testing Naive Bayes\n";
$trainingSet = array(
	array(
		'data' => array('Outlook'=>'Sunny', 'Temp'=>'Hot', 'Hum'=>'High', 'Windy'=>'Weak'),
		'class' => 'No'
	),
	array(
		'data' => array('Outlook'=>'Sunny', 'Temp'=>'Hot', 'Hum'=>'High', 'Windy'=>'Strong'),
		'class' => 'No'
	),
	array(
		'data' => array('Outlook'=>'Overcast', 'Temp'=>'Hot', 'Hum'=>'High', 'Windy'=>'Weak'),
		'class' => 'Yes'
	),
	array(
		'data' => array('Outlook'=>'Rain', 'Temp'=>'Mild', 'Hum'=>'High', 'Windy'=>'Weak'),
		'class' => 'Yes'
	),
	array(
		'data' => array('Outlook'=>'Rain', 'Temp'=>'Cool', 'Hum'=>'Normal', 'Windy'=>'Weak'),
		'class' => 'Yes'
	),
	array(
		'data' => array('Outlook'=>'Rain', 'Temp'=>'Cool', 'Hum'=>'Normal', 'Windy'=>'Strong'),
		'class' => 'No'
	),
	array(
		'data' => array('Outlook'=>'Overcast', 'Temp'=>'Cool', 'Hum'=>'Normal', 'Windy'=>'Strong'),
		'class' => 'Yes'
	),
	array(
		'data' => array('Outlook'=>'Sunny', 'Temp'=>'Mild', 'Hum'=>'High', 'Windy'=>'Weak'),
		'class' => 'No'
	),
	array(
		'data' => array('Outlook'=>'Sunny', 'Temp'=>'Cool', 'Hum'=>'Normal', 'Windy'=>'Weak'),
		'class' => 'Yes'
	),
	array(
		'data' => array('Outlook'=>'Rain', 'Temp'=>'Mild', 'Hum'=>'Normal', 'Windy'=>'Weak'),
		'class' => 'Yes'
	),
	array(
		'data' => array('Outlook'=>'Sunny', 'Temp'=>'Mild', 'Hum'=>'Normal', 'Windy'=>'Strong'),
		'class' => 'Yes'
	),
	array(
		'data' => array('Outlook'=>'Overcast', 'Temp'=>'Mild', 'Hum'=>'High', 'Windy'=>'Strong'),
		'class' => 'Yes'
	),
	array(
		'data' => array('Outlook'=>'Overcast', 'Temp'=>'Hot', 'Hum'=>'Normal', 'Windy'=>'Weak'),
		'class' => 'Yes'
	),
	array(
		'data' => array('Outlook'=>'Rain', 'Temp'=>'Mild', 'Hum'=>'High', 'Windy'=>'Strong'),
		'class' => 'No'
	)
);
$nb = \biophp\math\ml::naiveBayes($trainingSet);
$data = array('Outlook'=>'Sunny', 'Temp'=>'Cool', 'Hum'=>'High', 'Windy'=>'Strong');
$p1 = $nb->prob ($data, 'Yes');
$p0 = $nb->prob ($data, 'No');
echo "  P1: $p1\n  P0: $p0\n";

$trainingSet = array(
	array('data'=>array('kill'=>2, 'bomb'=>1, 'kidnap'=>3, 'TV'=>1), 'class'=>1),
	array('data'=>array('kill'=>1, 'bomb'=>1, 'kidnap'=>1), 'class'=>1),
	array('data'=>array('kill'=>1, 'bomb'=>1, 'kidnap'=>2, 'movie'=>1), 'class'=>1),
	array('data'=>array('bomb'=>1, 'music'=>2, 'movie'=>1, 'TV'=>1), 'class'=>0),
	array('data'=>array('kidnap'=>1, 'music'=>1, 'movie'=>1), 'class'=>0),
	array('data'=>array('music'=>2, 'movie'=>2), 'class'=>0)
	array('data'=>explode(' ', 'kill kill bomb kidnap kidnap kidnap TV'), 'class'=>1),
	array('data'=>explode(' ', 'kill bomb kidnap'), 'class'=>1),
	array('data'=>explode(' ', 'kill bomb kidnap kidnap movie'), 'class'=>1),
	array('data'=>explode(' ', 'bomb music music movie TV'), 'class'=>0),
	array('data'=>explode(' ', 'kidnap music movie'), 'class'=>0),
	array('data'=>explode(' ', 'music music movie movie'), 'class'=>0)
);

$nb = \biophp\math\ml::naiveBayes($trainingSet);
$data = array('kill', 'bomb', 'kidnap', 'TV', 'kill', 'kidnap');
$p1 = $nb->prob ($data, 1);
$p0 = $nb->prob ($data, 0);
echo "  P1: $p1\n  P0: $p0\n";

echo "- Testing distribution \n";
biophp\math::distribution(array(0.1,.1,.2,.11,.32,.44,.22,.21,.22,.55,.9,.99,.84,.43));

echo "\n- Testing matrix\n";
$array = array(array(1,0,2), array(-1,3,1));
$m = biophp\math::matrix($array);
$a = array(array(3,1), array(2,1), array(1,0));
$a = $m->X($a);
$a->output();
$m->output();

echo biophp\math::fact(5) . PHP_EOL;
echo biophp\math::logfact(5) . PHP_EOL;
echo log(biophp\math::fact(5)) . PHP_EOL;

echo biophp\math\stat::hypergeometric(4, 10, 5, 50) . PHP_EOL;
echo biophp\math\stat::hypergeometric(5, 10, 5, 50) . PHP_EOL;*/
