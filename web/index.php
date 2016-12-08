<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

require('../vendor/autoload.php');


$app = new Silex\Application();
$app['debug'] = true;

$app->before(function (Request $request) {
    if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
        $data = json_decode($request->getContent(), true);
        $request->request->replace(is_array($data) ? $data : array());
    }
});

// Register the monolog logging service
$app->register(new Silex\Provider\MonologServiceProvider(), array(
  'monolog.logfile' => 'php://stderr',
));

// Register view rendering
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/views',
));

// Web handlers

$app->post('/webhook', function(Request $request) use($app) {

	//-----------------------DATABASE---------------------------
	$conn_string = "host=ec2-54-163-254-48.compute-1.amazonaws.com 
			port=5432 
			dbname=d9dbi6cl08c0i 
			user=ztlvocffwufbva 
			password=CjNAryYzgpTUxM2ZsCu7wmdXmj";

			$db = pg_connect($conn_string);
	//----------------------------------------------------------

	$result = $request->request->get('result');

	if($result['action'] == "find.name"){
		$parameters=$result['parameters'];
		$surname=$parameters['names'];

		//---------------Erreur_1ere_lettre_MAJ-----------------
		$premierelettre = strtoupper(substr($surname, 0, 1)); 
		$reste=substr($surname, 1);
		$surname=$premierelettre.$reste;
		//------------------------------------------------------


		//-----------------------DATABASE-----------------------
		$query = pg_prepare($db, "prenom_nom", 'SELECT nom FROM users WHERE prenom = $1');

		$result = pg_execute($db, "prenom_nom", array($surname));

		$arr = pg_fetch_array ($result, 0, PGSQL_NUM);
		$name = $arr[0];
		//------------------------------------------------------

		if($name)
			$speech="The name of ".$surname." is ".$name.".";	
		else
			$speech="You don't know that person.";
	}
	else if($result['action'] == "add.person"){
		$parameters=$result['parameters'];
		$surname=$parameters['names'];
		//-----------------------DATABASE-----------------------
		$query = "INSERT INTO users(prenom) VALUES('$surname');";

		$result = pg_query($db, $query);
		//------------------------------------------------------

		$query = pg_prepare($db, "prenom_nom", 'SELECT nom, prenom FROM users');

		$result = pg_execute($db, "prenom_nom");

		while($arr = pg_fetch_assoc($result)){
			if($arr['nom']==""){
				$speech="What's the name of ".$arr['prenom']." ?";
			}
		}

	}
	else if($result['action'] == "hello"){
		$check=false;

		$query = pg_prepare($db, "prenom_nom", 'SELECT nom, prenom FROM users');

		$result = pg_execute($db, "prenom_nom");

		while($arr = pg_fetch_assoc($result)){
			if($arr['nom']==""){
				$speech="Hello ! I have a question... What's the family name of ".$arr['prenom']." ?";
				$check=true;
			}
		}

		if(!$check){
			$speech="Hello !";
		}
	}
	else{
		$speech="I do not understand...";
	}

	$res=array(
		"speech"=> $speech, 
		"displayText"=> $speech,
		"source"=> "apiai-test-php"
    );

	return $app->json($res);
});

$app->run();
