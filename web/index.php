<?php

define("ID", 1);
define("QUESTION", TRUE); //to implement on the end device


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

	if($result['action'] == "send.answer"){ //to remove on the final version
		$parameters=$result['parameters'];
		$nameToAdd=$parameters['names'];

		$check=TRUE;

		$query = pg_prepare($db, "find", "SELECT nom, prenom FROM entourage WHERE id_utilisateur=$1");

		$result = pg_execute($db, "find", array(ID));

		$speech = "Nothing to change !";

		while($check && $arr = pg_fetch_assoc($result)){
			$name=$arr['nom'];
			if(!$name){
				$check=FALSE;
			}
		}

		if(!$check){
			$query = pg_prepare($db, "new_name", "UPDATE entourage SET nom=$3 WHERE prenom=$2 AND id_utilisateur=$1");
			if(pg_execute($db, "new_name", array(ID, $arr['prenom'], $nameToAdd))){
				$speech = $arr['prenom']." changed !";
			}
			else{
				$speech = "nothing changed for ".$arr['prenom'];
			}

			
		}
	}
	else if($result['action'] == "find.name"){
		$parameters=$result['parameters'];
		$surname=$parameters['names'];

		//---------------Erreur_1ere_lettre_MAJ-----------------
		$premierelettre = strtoupper(substr($surname, 0, 1)); 
		$reste=substr($surname, 1);
		$surname=$premierelettre.$reste;
		//------------------------------------------------------


		//-----------------------DATABASE-----------------------
		$query = pg_prepare($db, "prenom_nom", "SELECT nom FROM entourage WHERE prenom = $1 AND id_utilisateur=$2");

		$result = pg_execute($db, "prenom_nom", array($surname, ID));

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
		$query = "INSERT INTO entourage(prenom,id_utilisateur) VALUES('$surname',$1)";

		$result = pg_query($db, $query, array(ID));
		//------------------------------------------------------

		$query = pg_prepare($db, "prenom_nom", "SELECT nom, prenom FROM entourage WHERE id_utilisateur=$1");

		$result = pg_execute($db, "prenom_nom", array(ID));

		while($arr = pg_fetch_assoc($result)){
			if($arr['nom']==""){
				$speech="What's the name of ".$arr['prenom']." ?";
			}
		}
	}
	else if($result['action'] == "hello"){
		$check=TRUE;

		$query = pg_prepare($db, "hello_find", "SELECT nom, prenom FROM entourage WHERE id_utilisateur=$1");

		$result = pg_execute($db, "hello_find", array(ID));

		$speech = "Hello !";

		while($check && $arr = pg_fetch_assoc($result)){
			$name=$arr['nom'];
			if(!$name){
				$speech ="Hello ! I have a question... What's the family name of ".$arr['prenom']." ?";
				$check=FALSE;
			}
		}
	}
	else if($result['action'] == "register.rdv"){
		$parameters=$result['parameters'];
		$label=$parameters['rdv'];
		$date_rdv=$parameters['date'];
		$time=$parameters['time'];
		//$surname; //to fill in
		$id_perso;
		$surname=$parameters['names'];
		$lieu=$parameters['lieux'];

		if($surname){
			$query = pg_prepare($db, "get_id", "SELECT id FROM entourage WHERE prenom=$1");

			$res = pg_execute($db, "get_id", array($surname));

			while($arr = pg_fetch_assoc($res)){
				$id_perso = $arr['id'];
			}
		}

		$query = "INSERT INTO rdv(label, lieu, date_rdv, time_rdv, id_personne) VALUES('$label', '$lieu', '$date_rdv', '$time', '$id_perso');";


		$result = pg_query($db, $query);

		$speech="rdv added";
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

