<?php

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
  // req = request.get_json(silent=True, force=True)
	$req=$request;

	echo "Request: ".json_encode($request);
  //   print("Request:")
  //   print(json.dumps(req, indent=4))

  //   res = makeWebhookResult(req)
	//----------------------------------------------------
	// if req.get("result").get("action") != "find.name":
	if($req['result']['action'] != "find.name")
		$res={};
	 //        return {}
	else{
		$result=$req['result'];
		$parameters=$result['parameters'];
		$surname=$parameters['names'];

		$users={'Florian':'Adonis', 'Emna':'Bouzouita', 'Alex':'Guilngar'};

		$speech="The name of ".$surname." is ".$users[$surname].".";

		echo "Response:".$speech;

		$res={
			"speech": $speech, 
			"displayText": $speech, 
			"data": {}, 
			"contextOut": [],
			"source": "apiai-test-php"
	    };
	}

 //    result = req.get("result")
 //    parameters = result.get("parameters")
 //    surname = parameters.get("names")

 //    users = {'Florian':'Adonis', 'Emna':'Bouzouita', 'Alex':'Guilngar'}

 //    speech = "The name of " + surname + " is " + str(users[surname]) + "."
 //    print("Response:")
 //    print(speech)

// return {
//         "speech": speech,
//         "displayText": speech,
//         #"data": {},
//         # "contextOut": [],
//         "source": "apiai-onlinestore-shipping"
//     }

//--------------------------------------------------------
  //   res = json.dumps(res, indent=4)
	$res=json_encode($res);

	echo $res;
  //   print(res)
  //   r = make_response(res)
	$r = new Response($res);
	$r->headers->set('Content-Type', 'application/json');

  //   r.headers['Content-Type'] = 'application/json'
  //   return r

	return $r;
});

$app->run();
