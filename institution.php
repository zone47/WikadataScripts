<?php
/* / */
// Script for getting the Wikidata Q item of an Institution Template in Commons if it exists
// Works for name redirections 
// For using it direclty on zone47.com :
// http://zone47.com/dev/institution.php?s=Musée des Augustins de Toulouse
// With php script:
// $data=file_get_contents("http://zone47.com/dev/institution.php?s=Musée%20des%20Augustins%20de%20Toulouse",true);

$tpl=$_GET["s"];

if ($tpl!=""){
	$tpl=trim(urldecode($tpl));
	$tpl=str_replace("{{","",$tpl);
	$tpl=str_replace("}}","",$tpl);
	if (substr($tpl,0,12)!="Institution:")
		$tpl="Institution:".$tpl;
		
	$redir="";
	
	$api_url="https://commons.wikimedia.org/w/api.php?continue=&action=query&format=json&export=&titles=".urlencode($tpl);
	$dfile=file_get_contents($api_url,true);
	$data_tpl=json_decode($dfile,true);
	$export=$data_tpl["query"]["export"]["*"];
	preg_match('#redirect title=.*[^>]#',$export,$matches);
	if ($matches){
		$redir=str_replace("redirect title=\"","",$matches[0]);
		$redir=str_replace("\" />","",$redir);
		$tpl=$redir;
	}
	
	$api_url="https://commons.wikimedia.org/w/api.php?continue=&action=query&format=json&export=&titles=".urlencode($tpl);
	$dfile=file_get_contents($api_url,true);
	$data_tpl=json_decode($dfile,true);
	$export=$data_tpl["query"]["export"]["*"];
	
	$qwd="";
	preg_match('#Wikidata *= *Q[0-9]*#i',$export,$matches);
	if ($matches){
		preg_match('#Q[0-9]*#',$matches[0],$tbl_qwd);
		$qwd=$tbl_qwd[0];
	}
	if ($qwd=="")
		echo "Not found";
	else
		echo $qwd;
}
else
	echo "Nothing to search. Add a string for parameter: /creator.php?s=xxxx";
	
	
?>