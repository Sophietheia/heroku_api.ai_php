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

	$result = $request->request->get('result');

		if($result['action'] != "find.name")
			$speech="Je ne sais pas. Action: ";
		else{
			$parameters=$result['parameters'];
			$surname=$parameters['names'];

			//---------------Erreur_1ere_lettre_MAJ-----------------
			$premierelettre = strtoupper(substr($surname, 0, 1)); 
			$reste=substr($surname, 1);
			$surname=$premierelettre.$reste;
			//------------------------------------------------------


			//-----------------------DATABASE-----------------------
			$conn_string = "host=ec2-54-163-254-48.compute-1.amazonaws.com 
			port=5432 
			dbname=d9dbi6cl08c0i 
			user=ztlvocffwufbva 
			password=CjNAryYzgpTUxM2ZsCu7wmdXmj";

			$db = pg_connect($conn_string);

			$query = pg_prepare($db, "prenom_nom", 'SELECT nom FROM users WHERE prenom = $1');

			$result = pg_execute($db, "prenom_nom", array($surname));

			$arr = pg_fetch_array ($result, 0, PGSQL_NUM);
			$name = $arr[0];

			//------------------------------------------------------

			$users=array(
				'Florian'=>'Adonis',
				'Emna'=>'Bouzouita',
				'Alex'=>'Guilngar'
			);

			$speech="The name of ".$surname." is ".$name.".";	
		}

	$res=array(
		"speech"=> $speech, 
		"displayText"=> $speech,
		"source"=> "apiai-test-php"
    );

	return $app->json($res);
});

$app->run();
