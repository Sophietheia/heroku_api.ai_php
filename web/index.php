<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

require('../vendor/autoload.php');

$app = new Silex\Application();
$app['debug'] = true;

// Register the monolog logging service
$app->register(new Silex\Provider\MonologServiceProvider(), array(
  'monolog.logfile' => 'php://stderr',
));

// Register view rendering
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/views',
));

// Our web handlers

$app->get('/', function() use($app) {
  $app['monolog']->addDebug('logging output.');
  return $app['twig']->render('index.twig');
});

$app->post('/webhook', function(Request $request) use($app) {
	//$req=json_encode($request);
	$req=json_decode($req, true);

	echo "Request : ".json_encode($req);

	foreach($req['result'] as $key => $value){
		if($value['action'] != "find.name")
			$res="empty";
		else{
			$result=$req['result'];
			$parameters=$result['parameters'];
			$surname=$parameters['names'];

			$users=array(
				'Florian'=>'Adonis',
				'Emna'=>'Bouzouita',
				'Alex'=>'Guilngar'
			);

			$users=json_encode($users);

			$speech="The name of ".$surname." is ".$users[$surname].".";

			echo "Response: ".$speech;

			$res=array(
				"speech"=> $speech, 
				"displayText"=> $speech, 
				"data"=> [], 
				"contextOut"=> [],
				"source"=> "apiai-test-php"
		    );
		}
	}

	//$res=json_encode($res);

	echo $res;
	//$r = new Response($res);
	//$r->headers->set('Content-Type', 'application/json');

	//return $r;
	return $app->json($res);
});

$app->run();
