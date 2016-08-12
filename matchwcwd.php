<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Untitled Document</title>
<style>
hr{margin-bottom:5px;clear:both;}
img{vertical-align:middle;margin:5px}
div{clear:both;}
.thumb{float:left;}
.qwd{margin-bottom:8px;}
</style>
</head>

<body><?php
ini_set('memory_limit', '-1');
set_time_limit(360000);
error_reporting(E_ALL & ~E_NOTICE);
$europeana=false;
$musee='Q1499958';
$frcrea=false;
$host = '127.0.0.1'; 
$user = 'root'; 
$pass = ''; 
$db = 'shiva';
$phase=1;
// 1. Requête sur artiste renseigné
// 2. Requête sur titre dans Description
// 3. Requête sur artiste dans Description

function label($wdq,$l){
	$qitem_path="https://www.wikidata.org/w/api.php?action=wbgetentities&ids=".$wdq."&format=json&props=labels";

	$dfic =file_get_contents($qitem_path,true);
	$data_item=json_decode($dfic,true);
	$ent_qwd=$data_item["entities"][$wdq]["labels"];
	$label="";
	if ($ent_qwd[$l]["value"])
		$label=$ent_qwd[$l]["value"];
	else{
		if (($ent_qwd)&&($l=="en"))
			$label=$ent_qwd[key($ent_qwd)]["value"];
	}
	return $label;
}

function thumb($img){
	$img=str_replace(" ","_",$img);
	$digest = md5($img);
	$folder = $digest[0] . '/' . $digest[0] . $digest[1] . '/' . urlencode($img);
	$urlthumb="http://upload.wikimedia.org/wikipedia/commons/thumb/" . $folder."/150px-".urlencode($img);
	return $urlthumb;
}
function simplexml_load_file_from_url($url, $timeout = 30){
	$context = stream_context_create(array('http'=>array('user_agent' => 'PHP script','timeout' => (int)$timeout)));
	$data = file_get_contents($url, false, $context);
	if(!$data){
		trigger_error('Cannot load data from url: ' . $url, E_USER_NOTICE);
		return false;
	}
	return simplexml_load_string($data);
}
function getjpegsize($img_loc) {
	$handle = fopen($img_loc, "rb");// or die("Invalid file stream.");
	$new_block = NULL;
	if(!feof($handle)) {
		$new_block = fread($handle, 32);
		$i = 0;
		if($new_block[$i]=="\xFF" && $new_block[$i+1]=="\xD8" && $new_block[$i+2]=="\xFF" && $new_block[$i+3]=="\xE0") {
			$i += 4;
			if($new_block[$i+2]=="\x4A" && $new_block[$i+3]=="\x46" && $new_block[$i+4]=="\x49" && $new_block[$i+5]=="\x46" && $new_block[$i+6]=="\x00") {
				// Read block size and skip ahead to begin cycling through blocks in search of SOF marker
				$block_size = unpack("H*", $new_block[$i] . $new_block[$i+1]);
				$block_size = hexdec($block_size[1]);
				while(!feof($handle)) {
					$i += $block_size;
					$new_block .= fread($handle, $block_size);
					if($new_block[$i]=="\xFF") {
						// New block detected, check for SOF marker
						$sof_marker = array("\xC0", "\xC1", "\xC2", "\xC3", "\xC5", "\xC6", "\xC7", "\xC8", "\xC9", "\xCA", "\xCB", "\xCD", "\xCE", "\xCF");
						if(in_array($new_block[$i+1], $sof_marker)) {
							// SOF marker detected. Width and height information is contained in bytes 4-7 after this byte.
							$size_data = $new_block[$i+2] . $new_block[$i+3] . $new_block[$i+4] . $new_block[$i+5] . $new_block[$i+6] . $new_block[$i+7] . $new_block[$i+8];
							$unpacked = unpack("H*", $size_data);
							$unpacked = $unpacked[1];
							$height = hexdec($unpacked[6] . $unpacked[7] . $unpacked[8] . $unpacked[9]);
							$width = hexdec($unpacked[10] . $unpacked[11] . $unpacked[12] . $unpacked[13]);
							return array($width, $height);
						} else {
							// Skip block marker and read block size
							$i += 2;
							$block_size = unpack("H*", $new_block[$i] . $new_block[$i+1]);
							$block_size = hexdec($block_size[1]);
						}
					} else {
						return FALSE;
					}
				}
			}
		}
	}
	return FALSE;
}
function dimimg($img){
	$width="";
	$height="";
	$urlapimagnus="https://tools.wmflabs.org/magnus-toolserver/commonsapi.php?image=".urlencode($img);
 	$xml = simplexml_load_file_from_url($urlapimagnus);
	 if ($xml){
 		$width=intval($xml->xpath('//file/width/text()')[0]);
 		$height=intval($xml->xpath('//file/height/text()')[0]);
 	}
 	else{
 		$size=getjpegsize($urlimg);
 		if (!(isset($size[1])))
			 $size=getimagesize($urlimg);
 		$width=$size[0];
 		$height=$size[1];
 	}
	return $width." × ".$height;
}

if (isset($_GET['m']))
	if ($_GET['m']!="")
		$musee=$_GET['m'];
$musee2=$musee;
if (isset($_GET['m2']))
	if ($_GET['m2']!="")
		$musee2=$_GET['m2'];
$link = mysqli_connect  ($host,$user,$pass,$db) or die ('Erreur : '.mysqli_error());
$link->set_charset("utf8");

$cpt2=0;
$lgs="en,fr,nl,it,es,pt,de";

//Europeana
if ($europeana)
   	$sparql="SELECT distinct ?item ?itemLabel ?titreFR ?crea ?creaLabel ?p727 WHERE {
	?item wdt:P195 wd:".$musee.".
	OPTIONAL {?item wdt:P727 ?p727.}	
	MINUS {?item wdt:P18 ?img.}
	OPTIONAL {?item wdt:P170 ?crea.}
	OPTIONAL {?item rdfs:label ?titreFR.
	FILTER (lang(?titreFR)=(\"fr\"))}
	SERVICE wikibase:label {
	bd:serviceParam wikibase:language \"".$lgs.",ar,be,bg,bn,ca,cs,da,el,et,fa,fi,he,hi,hu,hy,id,ja,jv,ko,nb,eo,pa,pl,ro,ru,sh,sk,sr,sv,sw,te,th,tr,uk,yue,vec,vi,zh\"
	}
}ORDER BY ?crea";

else{
	if ($frcrea)	
		$sparql="SELECT distinct ?item ?itemLabel ?titreFR ?crea ?creaLabel ?creaFR ?p973 WHERE {
		?item wdt:P195 wd:".$musee.".
		OPTIONAL {?item wdt:P973 ?p973.}	
		MINUS {?item wdt:P18 ?img.}
		OPTIONAL {?item wdt:P170 ?crea.}
		OPTIONAL {?item rdfs:label ?titreFR.
		FILTER (lang(?titreFR)=(\"fr\"))}
		OPTIONAL {?crea rdfs:label ?creaFR.
		FILTER (lang(?creaFR)=(\"fr\"))}
		SERVICE wikibase:label {
		bd:serviceParam wikibase:language \"".$lgs.",ar,be,bg,bn,ca,cs,da,el,et,fa,fi,he,hi,hu,hy,id,ja,jv,ko,nb,eo,pa,pl,ro,ru,sh,sk,sr,sv,sw,te,th,tr,uk,yue,vec,vi,zh\"
		}
		}ORDER BY ?crea";
	else
		$sparql="SELECT distinct ?item ?itemLabel ?titreFR ?crea ?creaLabel ?p973 WHERE {
		 ?item wdt:P195 wd:".$musee.".
		  OPTIONAL {?item wdt:P973 ?p973.}	
		 MINUS {?item wdt:P18 ?img.}
		 OPTIONAL {?item wdt:P170 ?crea.}
		 OPTIONAL {?item rdfs:label ?titreFR.
		 FILTER (lang(?titreFR)=(\"fr\"))}
		 SERVICE wikibase:label {
		  bd:serviceParam wikibase:language \"".$lgs.",ar,be,bg,bn,ca,cs,da,el,et,fa,fi,he,hi,hu,hy,id,ja,jv,ko,nb,eo,pa,pl,ro,ru,sh,sk,sr,sv,sw,te,th,tr,uk,yue,vec,vi,zh\"
		 }
		}ORDER BY ?crea";
}

$apisparql="https://query.wikidata.org/sparql?format=json&query=".urlencode($sparql);

$dfic=file_get_contents($apisparql,true);
$data_item=json_decode($dfic,true);
//var_dump($data_item);
$lienreq="";

foreach ($data_item["results"]["bindings"] as $key => $value){
	$qwd=str_replace("http://www.wikidata.org/entity/","",$value["item"]["value"]);
	//$musee2=str_replace("http://www.wikidata.org/entity/","",$value["coll"]["value"]);
	$lbQmulti=$value["itemLabel"]["value"];
	$lbQfr=$value["titreFR"]["value"];
	$qcrea=str_replace("http://www.wikidata.org/entity/","",$value["crea"]["value"]);
	if ($frcrea)	
		$crea=$value["creaFR"]["value"];
	else
		$crea="";
	if ($qcrea!=""){
		$api="https://www.wikidata.org/w/api.php?action=wbgetentities&props=labels&ids=".$qcrea."&languages=fr&format=json";
		$dfic2=file_get_contents($api,true);
		$data_item2=json_decode($dfic2,true);
		foreach ($data_item2["entities"] as $key2 => $value2)
			$crea=$value2["labels"]["fr"]["value"];
	}
	
	$qcrea=str_replace("http://www.wikidata.org/entity/","",$value["crea"]["value"]);
	$creamulti=$value["creaLabel"]["value"];
	if ($crea=="")
		$crea=$creamulti;
    if ($europeana)
        $lien=$value["p727"]["value"];
    else
        $lien=$value["p973"]["value"];
	
	// Requete sur artiste renseigné
	//$query = "SELECT DISTINCT id,file,title from wcfiles where qartist='".$qcrea."' AND wikidata = '' AND wcfiles.title LIKE  \"%".str_replace("\"","\\\"",$lbQmulti)."%\"";
	if (!(($phase==3)&&($creamulti==""))){
		switch ($phase){
			case 1:		// 1. Requête sur artiste renseigné
				$query = "SELECT DISTINCT id,file,title,description from wcfiles where Qartist!='' AND Qartist!='0' AND Qartist='".$qcrea."' AND qloc='".$musee2."' AND wikidata = ''";
				break;
			case 2:		// 2. Requête sur titre dans Description
				$query = "SELECT DISTINCT id,file,title,description from wcfiles where qloc='".$musee2."' AND wikidata = '' AND wcfiles.description LIKE  \"%".str_replace("\"","\\\"",$lbQmulti)."%\"";
				break;
			case 3:		// 3. Requête sur artiste dans Description	
				$query = "SELECT DISTINCT id,file,title,description from wcfiles where qloc='".$musee2."' AND wikidata = '' AND wcfiles.description LIKE  \"%".str_replace("\"","\\\"",$creamulti)."%\"";
				break;
		}
		$testmatch = mysqli_query($link,$query) or die (mysqli_error());
		$cpt=0;
		$num_rows = mysqli_num_rows($testmatch);
		while ($match=mysqli_fetch_assoc($testmatch)){
			if ($cpt==0){
				echo "\n<hr/><div  class=\"qwd\"><b><a href=\"https://www.wikidata.org/wiki/".$qwd."\">".$qwd."</a>";
				if ($lien!=""){
					if ($europeana)
						echo " - <a href=\"http://www.europeana.eu/portal/fr/record/".$lien."\">lien p727</a>";
					else 
						echo " - <a href=\"".$lien."\">lien p973</a>";
				}
				echo " ".$lbQmulti."</b> - ".$crea." - ".$qcrea."</div>";
				$cpt2++;
			}
			//$file=str_replace("\$£",",",$match["file"]);
			$file=str_replace("_"," ",$match["file"]);
			echo "<div><div class=\"thumb\"><a href=\"https://commons.wikimedia.org/wiki/File:".$file."\"><img src=\"".thumb($file)."\" /></a></div>";
			
			echo "<b><a href=\"https://tools.wmflabs.org/wikidata-todo/quick_statements.php?list=".$qwd."%09P18%09%22".str_replace("\"","\\\"",$file)."%22%0A".$qwd."%09Dfr%09%22peinture%20de%20".str_replace("\"","\\\"",$crea)."%22\">Add</a> ";
	
			if	(($lbQfr=="")&&($lbQmulti!="")){
				echo "<a href=\"https://tools.wmflabs.org/wikidata-todo/quick_statements.php?list=".$qwd."%09P18%09%22".str_replace("\"","\\\"",$file)."%22%0A".$qwd."%09Lfr%09%22".str_replace("\"","\\\"",$lbQmulti)."%22%0A".$qwd."%09Dfr%09%22peinture%20de%20".str_replace("\"","\\\"",$crea)."%22\">Add+label</a></b> ".$lbQmulti;
				if ($lien!=""){
					if ($europeana)
						echo " (<a href=\"http://www.europeana.eu/portal/fr/record/".$lien."\">lien p727</a>)";
					else 
						echo " (<a href=\"".$lien."\">lien p973</a>)";
				}
				echo " <b>";
				echo " <a href=\"https://tools.wmflabs.org/wikidata-todo/quick_statements.php?list=".$qwd."%09Lfr%09%22".str_replace("\"","\\\"",$lbQmulti)."%22%0A".$qwd."%09Dfr%09%22peinture%20de%20".str_replace("\"","\\\"",$crea)."%22\">Label</a>";
			}
			echo "</b>";
			echo " <a href=\"done2.php?id=".$match["id"]."&qwd=".$qwd."\">done</a>";
			echo " <a href=\"skip2.php?id=".$match["id"]."\">skip</a>";
			echo " <a href=\"already.php?id=".$match["id"]."\">already</a> <br/>";
			//if (($num_rows>1)&&($num_rows<6))
			//	echo "\n".dimimg($file);
			echo "\n<br/><b>".$match["title"]."</b>";
			echo "\n<br/>".$match["description"];
			echo "\n<br/><a href=\"https://commons.wikimedia.org/wiki/File:".$file."\">".$file."</a>";
			echo "</div>";
			
			$cpt++;
		}
	}
}

echo "done $cpt2";
?>
</body>
</html>
