<?php
/* / */
/* Script pour récupérer à partir d'une catégorie de Wikimedia Commons et de ses sous-catégories, les infos suivantes de chaque fichier image à partir des modèles "artwork" et "information", ainsi que les catégories  :
- artist (alias : artist_display_name, artist_name, author)
- Qartist 
- title
- description
- date (pretty_display_date, latest_date, latest_date_display)
- medium (technique, facet_medium)
- dimensions (size,pretty_dimensions)
- institution (gallery, collection_display_name, collection)
- Qinstitution
- department
- accession_number (inventory_number)
- other_versions
- references
- wikidata
- p973 (absolute_url, detail_url)
- object (object_work_type)
- source
- categories
Étrangement, après l'avoir utilisé sur pas mal de fichiers ça semble bien fonctionner. Si vous voulez l'améliorer, mieux vaudrait éviter, garder l'idée parce qu'on besoin de ce genre de trucs et repartir sur quelque chose d'autrement plus solide :-)

--
-- Structure de la table `wcfiles`
--

CREATE TABLE IF NOT EXISTS `wcfiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file` varchar(255) DEFAULT '',
  `artist` text,
  `Qartist` varchar(10) NOT NULL DEFAULT '',
  `title` text,
  `description` text,
  `date` text,
  `medium` text,
  `dimensions` text,
  `institution` text,
  `Qinstitution` varchar(10) NOT NULL DEFAULT '',
  `department` text,
  `accession_number` text,
  `other_versions` text,
  `references` text,
  `wikidata` text,
  `p973` text,
  `object` text,
  `source` text,
  `categories` text,
  `qloc` varchar(10) NOT NULL DEFAULT '',
  `qcrea` varchar(10) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
*/		
set_time_limit(36000);
ini_set('memory_limit', '-1');
error_reporting(E_ALL & ~E_NOTICE);
$host = '127.0.0.1'; 
$user = 'root'; 
$pass = ''; 
$db = 'shiva';
$link = mysqli_connect  ($host,$user,$pass,$db) or die ('Erreur : '.mysqli_error());
$link->set_charset("utf8");

$lg="fr";
$commonscat="Category:Paintings in the Gemeentemuseum Den Haag";
$search=0; // 0 institution - 1 (ou autre) artiste
$Qsearch="Q1499958";
$Qlabel="Gemeentemuseum"; // Si déjà renseigné
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Fichiers dans une catégorie</title>
</head>

<body>
<?php

$tab_fic=array();
$tab_category=array();
function esc_dblq($text){
	return str_replace("\"","\\\"",$text);
}

function cherche_fic($cat){
	global $tab_fic,$tab_category;
	//echo "<br/> ". $cat;
	$cat=str_replace(" ","_",$cat);
	$url="https://commons.wikimedia.org/w/api.php?action=query&list=categorymembers&cmlimit=500&format=json&cmtype=file&cmtitle=".$cat;
	$dfile=file_get_contents($url);
	$data_file=json_decode($dfile,true);
	$data_pages=$data_file["query"]["categorymembers"];
	for ($i=0;$i<count($data_pages);$i++){	
		$newfic=str_replace("File:","",$data_pages[$i]["title"]);
		if (!(in_array($newfic, $tab_fic)))
			$tab_fic[]=$newfic;
	}
	$url="https://commons.wikimedia.org/w/api.php?action=query&list=categorymembers&cmlimit=500&format=json&cmtype=subcat&cmtitle=".$cat;
	$dfile=file_get_contents($url);
	$data_file=json_decode($dfile,true);
	$data_pages=$data_file["query"]["categorymembers"];
	for ($i=0;$i<count($data_pages);$i++){	
		$newcat=$data_pages[$i]["title"];
		if (!(in_array($newcat, $tab_category))){
			$tab_category[]=$newcat;
			cherche_fic($newcat);
		}
	}
}

cherche_fic($commonscat);
$nbfiles=count($tab_fic);
/* Avoir la liste des fichiers
for ($i=0;$i<$nbfiles;$i++){	
	echo "<br/>+".$tab_fic[$i];
}*/

for ($i=0;$i<$nbfiles;$i++){	
	$file=$tab_fic[$i];
	$file=str_replace(" ","_",$file);
	$dfile=file_get_contents("https://commons.wikimedia.org/w/api.php?action=query&prop=revisions&titles=File:".$file."&format=json&rvprop=content",true);
	$data_file=json_decode($dfile,true);
	$data_page=$data_file["query"]["pages"];
	foreach ($data_page as $k=> $v){
		$page=$k;
		break;
	}
	$data=serialize($data_page[$page]["revisions"]);
	//echo $data;
	$artworfk=false;
	$description=false;
	$chaine_courante="";
	$niv=0;
	$nivcrochets=0;
	$templatefaite=false;
	$champfait=false;
	$valeurfaite=false;
	$champs=array();
	$valeurs=array();
	$champs[0]="";
	$valeurs[0]="";
	$cptchamp=0;
	$art_desc=false;
	$champec=true;
	for ($j=0;$j<strlen($data);$j++){
		if (!$templatefaite){
			$car=$data{$j};
			if ($car=="{"){
				$niv++;
				if ($niv>4)
					$chaine_courante.=$car;
				else
					$chaine_courante="";
			}
			elseif ($car=="}"){
				if ($niv==4){ // Fin template
					if ($art_desc){
						$valeurfaite=true;
						$templatefaite=true;
						if ($art_desc)
							$cptchamp++;
					}
				}
				else
					$chaine_courante.=$car;
				$niv--;
			}
			elseif ($car=="="){
				if (($niv==4)&&($champec))  // Fin champ
					$champfait=true;
				else
					$chaine_courante.=$car;
			}
			elseif ($car=="|"){
				if (($niv==4)&&($nivcrochets==0)) {// Fin couple champ/valeur
					$valeurfaite=true;
					$champec=true;
					if ($art_desc)
						$cptchamp++;
				}
				else
					$chaine_courante.=$car;
			}
			elseif ($car=="["){
				$nivcrochets++;
				$chaine_courante.=$car;
			}
			elseif ($car=="]"){
				$nivcrochets--;
				$chaine_courante.=$car;
			}

			else
				$chaine_courante.=$car;
		}
		else
			// on récupère le dernier
			$valeurfaite=true;
		
		if ($champfait){
			//echo "<br/>champ fait - $cptchamp - $art_desc - ".$chaine_courante;
			if ($art_desc)
				$champs[$cptchamp]=str_replace(" ","_",strtolower(trim($chaine_courante)));
			$chaine_courante="";
			$champfait=false;
			$champec=false;
		}
		if ($valeurfaite){
			//echo "<br/>val fait - $cptchamp - $art_desc - ".$chaine_courante;
			$chaine_courante=trim($chaine_courante);
			if ($art_desc){
				if ((substr($chaine_courante,0,7)!="[[User:")&&($chaine_courante!="{{own}}")&&($chaine_courante!="{{Own}}"))
					$valeurs[$cptchamp-1]=$chaine_courante;
			}
			if ((strtolower($chaine_courante)=="artwork")||(strtolower($chaine_courante)=="information")||(strtolower($chaine_courante)=="google art project")||(strtolower($chaine_courante)=="google cultural institut")){
				$art_desc=true;
			}
			$chaine_courante="";
			$valeurfaite=false;
		}
		//echo "<br/>car : ".$car." niv ".$niv." - ".$chaine_courante;
		if ($templatefaite)
			break;
	}
	if (count($champs)==0)
		echo '<br/>Pb <a href="https://commons.wikimedia.org/wiki/File:'.str_replace("\"","&quot;",$file).'">'.$file.'</a>';
	
	$wcprops = array(
		"artist" => "", 
		"Qartist" => "", 
		"title" => "", 
		"description" => "", 
		"date" => "", 
		"medium" => "", 
		"dimensions" => "",
		"institution" => "", 
		"Qinstitution" => "",
		"department" => "", 
		"accession_number" => "",
		"other_versions" => "", 
		"references" => "", 
		"wikidata" => "",
		"p973" => "", 
		"object" => "", 
		"source" => "", 
		"categories" => ""
	);
	for ($j=0;$j<count($champs);$j++){
		/*if (isset($valeurs[$j]))
			echo "<br/><b>".$champs[$j]."</b> : ".$valeurs[$j];*/
		$champs[$j]=str_replace("wiki_","",$champs[$j]);
		$champs[$j]=str_replace("commons_","",$champs[$j]);
		switch ($champs[$j]){
			case "artist_display_name":
			case "artist_name":
			case "author":
				$datakey=array_search("artist", $champs);
				if ($datakey==0)
					$wcprops["artist"]=$valeurs[$j];
				else{
					if  ($valeurs[$datakey]=="")
						$wcprops["artist"]=$valeurs[$j];
				}
				break;
			case "pretty_display_date":
			case "latest_date":
			case "latest_date_display":
				$datakey=array_search("date", $champs);
				if ($datakey==0)
					$wcprops["date"]=$valeurs[$j];
				else{
					if  ($valeurs[$datakey]=="")
						$wcprops["date"]=$valeurs[$j];
				}
				break;
			case "technique":
			case "facet_medium":
				$datakey=array_search("medium", $champs);
				if ($datakey==0)
					$wcprops["medium"]=$valeurs[$j];
				else{
					if  ($valeurs[$datakey]=="")
						$wcprops["medium"]=$valeurs[$j];
				}
				break;
			case "size":
			case "pretty_dimensions":
				$datakey=array_search("dimensions", $champs);
				if ($datakey==0)
					$wcprops["dimensions"]=$valeurs[$j];
				else{
					if  ($valeurs[$datakey]=="")
						$wcprops["dimensions"]=$valeurs[$j];
				}
				break;
			case "gallery":
			case "collection_display_name":
			case "collection":
				$datakey=array_search("institution", $champs);
				if ($datakey==0)
					$wcprops["institution"]=$valeurs[$j];
				else{
					if  ($valeurs[$datakey]=="")
						$wcprops["institution"]=$valeurs[$j];
				}
				break;
			case "inventory_number":
				$datakey=array_search("accession_number", $champs);
				if ($datakey==0)
					$wcprops["accession_number"]=$valeurs[$j];
				else{
					if  ($valeurs[$datakey]=="")
						$wcprops["accession_number"]=$valeurs[$j];
				}
				break;
			case "object_work_type":
				$datakey=array_search("object", $champs);
				if ($datakey==0)
					$wcprops["object"]=$valeurs[$j];
				else{
					if  ($valeurs[$datakey]=="")
						$wcprops["object"]=$valeurs[$j];
				}
				break;
			case "absolute_url":
			case "detail_url":
				$datakey=array_search("p973", $champs);
				if ($datakey==0)
					$wcprops["p973"]="http://www.googleartproject.com".$valeurs[$j];
				else{
					if  ($valeurs[$datakey]=="")
						$wcprops["p973"]="http://www.googleartproject.com".$valeurs[$j];
				}
				break;
		}
		
		if (isset($valeurs[$j])){
			if (array_key_exists($champs[$j],$wcprops)){
				if ($wcprops[$champs[$j]]=="")
					$wcprops[$champs[$j]]=$valeurs[$j];
			}
		}
	}
	
	if ($wcprops["artist"]!=""){
		$string = $wcprops["artist"];
		$pattern = '/\{\{.*:/i';
		$string = trim(preg_replace($pattern,"", $string));
		$pattern = '/\}\}/i';
		$string = trim(preg_replace($pattern,"", $string));
		if ($string==$Qlabel)
			$wcprops["Qartist"]=$Qsearch;
		else{
			$data=file_get_contents("http://zone47.com/dev/creator.php?s=".urlencode($string),true);
		
			if ($data=="Not found")
				$data='0';
	
			$wcprops["Qartist"]=$data;
		}
	}
	if ($wcprops["institution"]!=""){
		$string = $wcprops["institution"];
		$pattern = '/\{\{.*:/i';
		$string = trim(preg_replace($pattern,"", $string));
		$pattern = '/\}\}/i';
		$string = trim(preg_replace($pattern,"", $string));
		if ($string==$Qlabel)
			$wcprops["Qinstitution"]=$Qsearch;
		else{
			$data=file_get_contents("http://zone47.com/dev/institution.php?s=".urlencode($string),true);
		
			if ($data=="Not found")
				$data='0';
	
			$wcprops["Qinstitution"]=$data;
		}
	}
	
	preg_match_all('#\[\[.*Category:(.*)\]\]#i',$data,$matchprop);
	if ($matchprop){
		for ($j=0;$j<count($matchprop[1]);$j++){
			if ($j!=0)
				$wcprops["categories"].="¤";
			$newcat=explode("|",$matchprop[1][$j]);
			$wcprops["categories"].=$newcat[0];
		}
	}
	/*echo "<br />****";
	foreach ($wcprops as $key => $value) {
		if ($value!="")
			echo "<br /><b>$key</b> : ".$value;
	}*/
  	$sql="SELECT id FROM wcfiles WHERE file = \"".esc_dblq($file)."\"";
	$rep=mysqli_query($link,$sql);
	if (mysqli_num_rows($rep)==0){
		$sql="INSERT INTO wcfiles (file,";
		if ($search==0)
			$sql.="qloc";
		else
			$sql.="qcrea";
		foreach($wcprops as $key => $value)
			$sql.=",`".$key."`";
		$sql.=") VALUES (\"".str_replace("_"," ",esc_dblq($file))."\",\"".$Qsearch."\"";
		foreach($wcprops as $key => $value)
			$sql.=",\"".esc_dblq($value)."\"";
		$sql.=")";
		$rep=mysqli_query($link,$sql);
	}
	//echo "<hr />";
}

echo "<br/>done ".$nbfiles;
?>
</body>
</html>