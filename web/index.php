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

			//------------------------------------------------------
			$premierelettre = strtoupper(substr($surname, 0)); 
			$reste=substr($surname, 1);
			$surname=$premierelettre.$reste;
			//------------------------------------------------------

			$users=array(
				'Florian'=>'Adonis',
				'Emna'=>'Bouzouita',
				'Alex'=>'Guilngar'
			);

			$speech="The name of ".$surname." is ".$users[$surname].".";	
		}

	$res=array(
		"speech"=> $speech, 
		"displayText"=> $speech,
		"source"=> "apiai-test-php"
    );

	return $app->json($res);
});

$app->run();
