<?php 

$pdo = new PDO('mysql:host=127.0.0.1;dbname=howsearch','root','');

$search = $_GET['q'];


$searche = explode(" ",$search);
//print_r($searche);

$x = 0;
$construct = "";
$params = array(); 

foreach($searche as $term){
	$x++;
	if($x == 1){
		$construct .= "title LIKE CONCAT('%',:search$x,'%') OR description LIKE CONCAT('%',:search$x,'%') OR keywords LIKE CONCAT('%',:search$x,'%')";
	} else {
		$construct .= "AND title LIKE CONCAT('%',:search$x,'%') OR description LIKE CONCAT('%',:search$x,'%') OR keywords LIKE CONCAT('%',:search$x,'%')";	// can use OR
	}
	$params[":search$x"]= $term;
}

//SELECT *FROM `index` WHERE title LIKE '%$How%'
//SELECT *FROM `index` WHERE title LIKE '%$How%' OR title LIKE '%$to%'
//SELECT *FROM `index` WHERE title LIKE '%$How%' OR title LIKE '%$to%' OR title LIKE '%$code%'


//$results = $pdo->query("SELECT * FROM `index` WHERE title LIKE '%$search%'");
$results = $pdo->prepare("SELECT * FROM `index` WHERE $construct");

$results->execute($params);

if($results->rowCount() == 0){
	echo "0 results found! <hr />" ;
}else{
	echo $results->rowCount()." results found!<hr />";
}


echo "<pre>";// format the data.
foreach($results->fetchAll() as $result){
	echo $result["title"]."<br />";
	if($result["description"] == ""){
		echo "No description available."."<br />";
	}else{
		echo $result["description"]."<br />";
	}
	echo $result["url"]."<br />";
	echo "<hr />";

}
//print_r($results->fetchAll());


