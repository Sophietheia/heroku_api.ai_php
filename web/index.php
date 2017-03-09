<?php

define("ID", 1);
define("IDDOC", 20);

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\Extension\Core\Type\FormType;
// use Silex\Provider\FormServiceProvider;
// use Silex\Provider\CsrfServiceProvider;

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

require('functions.php');


//////function for adding a person in the database

// Web handlers

$app->get('/', function() use($app){
  return $app['twig']->render('index.twig');
});

$app->get('/talk', function() use($app){
  return $app['twig']->render('talk.twig');
});

$app->get('/exitZone', function() use($app){
  //Do Something
  return '';
});

$app->post('/alert', function(Request $request) use($app){

  $username = $request->get('username');
  $idDoc = $request->request->get('idDoc');
  $idPatient = $request->request->get('idPatient');

  file_put_contents("php://stderr", "username: ".$request->get('username')."\n");


  if(!empty($username)){
    set_alert($username);
    return '';
  }else if(!empty($idDoc) && !empty($idPatient)){
    remove_alert($idDoc,$idPatient);
    return $app['twig']->render('dashboard.twig');
  }

  return '';
});

$app->get('/register', function() use($app){
  return 'register page';
});

require('interfaceDoctor/dashboard.controller.php');

$app->post('/login', function(Request $request) use($app){
  //Database connection
	$db = db_connect();

	$username = $request->request->get('username');
  $password = $request->request->get('password');

  file_put_contents("php://stderr", "username: ".$username." password: ".$password."\n");

  $response = array();

  $response['connection'] = test_login($db,$username,$password);

  return json_encode($response);
});


$app->post('/memory', function(Request $request) use($app){
  //Database connection
	$db = db_connect();
  //**************************

  $response = array();
    //$response['stade'] = "1";
  $query = pg_prepare($db, "get_stade", "SELECT stade FROM users WHERE id=$1;");
    $result= pg_execute($db, "get_stade", array(ID));
    $response=pg_fetch_row($result);
    if($response[0]==1)
    	$response['stade']=true;
 	if($response[0]==0)
    	$response['stade']=false;

  return json_encode($response);
});

$app->post('/reminders', function(Request $request) use($app){
  $db = db_connect();

  $username = $request->get('username');

  $result = get_reminders($db,$username);

  $response["json"] = array();

while ($row = pg_fetch_array($result)) {
  $remind = array();
  $remind["name"] = $row["label"];
  $remind["description"] = $row["label"];
  $remind["date_task"] = $row["date_meeting"];

  array_push($response["json"], $remind);
}

  return json_encode($response);
});

$app->post('/register', function() use($app){
  return 'register page';
});

$app->get('/privacy', function() use($app) {
	// $app['monolog']->addDebug('logging output.');
 //  	return $app['twig']->render('index.twig');
	return 'Page under construction';
});

require('webhook.php');

$app->run();
