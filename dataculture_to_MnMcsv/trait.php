<?php
/* / */
$dos="***"; // Chemin du dossier

$xml = new DOMDocument;
$xml->load($dos."Liste_d_autorites_Genese_Joconde.rdf");

$xsl = new DOMDocument;
$xsl->load($dos."dataculture_to_MnMcsv.xsl");

$proc = new XSLTProcessor;
$proc->importStyleSheet($xsl);

$data=$proc->transformToXML($xml);
$fic = fopen($dos."MnM_genese.csv", "w");
fputs($fic, $data);
fclose($fic);

?>