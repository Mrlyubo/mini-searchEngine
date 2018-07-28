<?php

$start = "https://www.google.com";
echo "craling started..."."\n";
$pdo = new PDO('mysql:host=127.0.0.1;dbname=howsearch','root',''); 
// Our 2 global arrays containing our links to be crawled.
$already_crawled = array();
$craling = array();//to avoid infinite looping back and forth
/*				$rows = $pdo->query("SELECT * FROM `index` WHERE url_hash='".md5($details->URL)."'");
				$rows = $rows->fetchColumn();
				echo $rows."\n";*/

function get_details($url){

	$options = array('http'=>array('method'=>"GET", 'headers'=>"User-Agent: howBot/0.1\n"));
	// Create the stream context.
	$context = stream_context_create($options);
	// Create a new instance of PHP's DOMDocument class.
	$doc = new DOMDocument();
	// Use file_get_contents() to download the page, pass the output of file_get_contents()
	// to PHP's DOMDocument class.
	@$doc->loadHTML(@file_get_contents($url, false, $context)); 	

	// Create an array of all of the title tags.
	$title = $doc->getElementsByTagName("title");
	// There should only be one <title> on each page, so our array should have only 1 element.
	$title = $title->item(0)->nodeValue;

	$description = "";
	$keywords = "";
	$metas = $doc->getElementsByTagName("meta");
	for($i = 0;$i < $metas->length; $i++){
		$meta = $metas->item($i);
		if(strtolower($meta->getAttribute("name"))=="description")
			$description = $meta->getAttribute("content");	
		if(strtolower($meta->getAttribute("name")) == "keywords")
			$keywords = $meta->getAttribute("content");	
	
	}
	return '{ "Title": "'.str_replace("\n", "", $title).'", "Description": "'.str_replace("\n", "", $description).'", "Keywords": "'.str_replace("\n", "", $keywords).'", "URL": "'.$url.'"}';
}



function follow_links($url) {
	// Give our function access to our crawl arrays.
	global $already_crawled;
	global $crawling;
	global $pdo;

	// The array that we pass to stream_context_create() to modify our User Agent.
	$options = array('http'=>array('method'=>"GET", 'headers'=>"User-Agent: howBot/0.1\n"));
	// Create the stream context.
	$context = stream_context_create($options);
	// Create a new instance of PHP's DOMDocument class.
	$doc = new DOMDocument();
	// Use file_get_contents() to download the page, pass the output of file_get_contents()
	// to PHP's DOMDocument class.
	@$doc->loadHTML(@file_get_contents($url, false, $context)); //use @ to supress non-important  error which is caused by website is not 100% html.
	// Create an array of all of the links we find on the page.
	$linklist = $doc->getElementsByTagName("a");
	// Loop through all of the links we find.
	foreach ($linklist as $link) {
		$l = $link->getAttribute("href");
		// Process all of the links we find. This is covered in part 2 and part 3 of the video series.
		if (substr($l, 0, 1) == "/" && substr($l, 0, 2) != "//") {
			$l = parse_url($url)["scheme"]."://".parse_url($url)["host"].$l;
		} else if (substr($l, 0, 2) == "//") {
			$l = parse_url($url)["scheme"].":".$l;
		} else if (substr($l, 0, 2) == "./") {
			$l = parse_url($url)["scheme"]."://".parse_url($url)["host"].dirname(parse_url($url)["path"]).substr($l, 1);
		} else if (substr($l, 0, 1) == "#") {
			$l = parse_url($url)["scheme"]."://".parse_url($url)["host"].parse_url($url)["path"].$l;
		} else if (substr($l, 0, 3) == "../") {
			$l = parse_url($url)["scheme"]."://".parse_url($url)["host"]."/".$l;
		} else if (substr($l, 0, 11) == "javascript:") {
			continue;
		} else if (substr($l, 0, 5) != "https" && substr($l, 0, 4) != "http") {
			$l = parse_url($url)["scheme"]."://".parse_url($url)["host"]."/".$l;
		}
		// If the link isn't already in our crawl array add it, otherwise ignore it.
		if (!in_array($l, $already_crawled)) {
				$already_crawled[] = $l;
				$crawling[] = $l;
				
				$details =  json_decode(get_details($l)."\n");

				echo $details->URL." ";
				echo $details->Description." ";
				//echo md5($details->URL)."\n";
				
				$rows = $pdo->query("SELECT * FROM `index` WHERE url='".$details->URL."'");
				$rows = $rows->fetchColumn();
				
				$params = array(':title'=>$details->Title, ':description'=>$details->Description, ':keywords'=>$details->Keywords, ':url'=>$details->URL, 'url_hash'=>md5($details->URL));

				if($rows > 0){
					echo "UPDATE"."\n";
					if(!is_null($params[':title']) && !is_null($params[':description']) && $params[':title']!='' ){

						$result = $pdo->prepare("UPDATE `index` SET title=:title, description=:description, keywords=:keywords, url=:url,url_hash=:url_hash WHERE url=:url" );
						$result = $result->execute($params);
						echo "INSERT Database"."\n";
					}

				}else{
					echo "INSERT"."\n";
					if(!is_null($params[':title']) && !is_null($params[':description']) && $params[':title']!='' ){

						$result = $pdo->prepare("INSERT INTO `index` VALUES('',:title, :description, :keywords, :url, :url_hash)");
						$result = $result->execute($params);
						echo "INSERT Database"."\n";
					}
				}
				//echo $rows."\n";
				//echo  get_details($l)."\n";
				// Output the page title, descriptions, keywords and URL. This output is
				// piped off to an external file using the command line.
				
		} 

	}
	//remove the first item of the array.
	array_shift($crawling);
	foreach ($already_crawled as $site){
		follow_links($site); 
	}
}
// Begin the crawling process by crawling the starting link first.
follow_links($start);
//print_r($already_crawled);
//$pdo->query("SELECT * FROM iendex");