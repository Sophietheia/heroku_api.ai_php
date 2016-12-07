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

// Our web handlers

$app->post('/webhook', function(Request $request) use($app) {
	//$req=json_encode($request);
	//$req=json_decode($request, true);

	$result = $request->request->get('result');

    // data is an array with "name", "email", and "message" keys
    // $data = $post->getData();

	// $req = $request->request->get('result');

	//echo "Request : ".json_encode($req);

		 if($result['action'] != "find.name")
			$speech="Je ne sais pas. Action: ";
		else{
			$parameters=$result['parameters'];
			$surname=$parameters['names'];

			$users=array(
				'Florian'=>'Adonis',
				'Emna'=>'Bouzouita',
				'Alex'=>'Guilngar'
			);

			// $users=json_encode($users);

			$speech="The name of ".$surname." is ".$users[$surname].".";

			// $speech="The name of Florian is Adonis.";

			
		}
	// }

	$res=array(
		"speech"=> $speech, 
		"displayText"=> $speech, 
		// "data"=> [], 
		// "contextOut"=> [],
		"source"=> "apiai-test-php"
    );

	//$res=json_encode($res);

	//echo $res;
	//$r = new Response($res);
	//$r->headers->set('Content-Type', 'application/json');

	//return $r;
	return $app->json($res);
});

$app->run();
