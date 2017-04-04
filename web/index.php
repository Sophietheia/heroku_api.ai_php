<?php

define("ID", 1);

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\Extension\Core\Type\FormType;

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

$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

require('functions.php');
require('interfaceDoctor/dashboard.controller.php');

// Web handlers

$app->post('/zone', function(request $request) use($app){

  $user_id = $request->get('sessionId');

  $address = get_user_address($user_id);

  $prepAddr = str_replace(' ','+',$address);
  $geocode=file_get_contents('https://maps.google.com/maps/api/geocode/json?address='.$prepAddr.'&sensor=false');
  $output= json_decode($geocode);

  $tab['latitude'] = $output->results[0]->geometry->location->lat;
  $tab['longitude'] = $output->results[0]->geometry->location->lng;

  logPerso(longitude, $tab['longitude']  );
  logPerso(latitude, $tab['latitude'] );

  $tab['radius'] = get_user_radius($user_id);

    logPerso(radius, $tab['radius'] );

  return json_encode($tab);
});

$app->get('/talk', function() use($app){
  return $app['twig']->render('talk.twig');
});

$app->post('/exitZone', function(Request $request) use($app){
  $user_id = $request->get('sessionId');

  set_alertzone($user_id);

  return '';
});

$app->post('/alert', function(Request $request) use($app){

  $user_id = $request->get('sessionId');
  $idDoc = $request->request->get('idDoc');
  $idPatient = $request->request->get('idPatient');

  file_put_contents("php://stderr", "sessionId: ".$request->get('sessionId')."\n");


  if(!empty($user_id)){
    set_alert($user_id);
    return '';
  }else if(!empty($idDoc) && !empty($idPatient)){
    remove_alert($idDoc,$idPatient);               ///////////////////// NOTCHANGED.......
    return $app['twig']->render('dashboard.twig');
  }

  return '';
});

//login user patient
$app->post('/login', function(Request $request) use($app){
  //Database connection
	$db = db_connect();

	$username = $request->request->get('username');
  $password = $request->request->get('password');

  file_put_contents("php://stderr", "username: ".$username." password: ".$password."\n");

  $response = array();

  $response['user_id'] = test_login($username,$password);

  return json_encode($response);
});


$app->post('/memory', function(Request $request) use($app){

  $user_id = $request->get('sessionId');

  $response = array();

  $response = get_stade($user_id);

  return json_encode($response);
});

$app->post('/reminders', function(Request $request) use($app){
  $db = db_connect();

  $user_id = $request->get('sessionId');

  logPerso("sessionID: ", $user_id);

  $id = get_id_person($user_id);

  $response["json"] = array();

  $response["json"] = get_reminders($id);

  logPerso("test label: ", $response['json'][0]["label"]);

  return json_encode($response);
});

require('webhook.controller.php');

$app->run();
