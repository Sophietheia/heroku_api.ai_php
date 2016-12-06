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

	echo "Request: ".json_decode($request);
  //   print("Request:")
  //   print(json.dumps(req, indent=4))

  //   res = makeWebhookResult(req)

  //   res = json.dumps(res, indent=4)
  //   print(res)
  //   r = make_response(res)
  //   r.headers['Content-Type'] = 'application/json'
  //   return r
});

$app->run();
