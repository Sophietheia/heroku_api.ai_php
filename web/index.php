<?php

define("ID", 1);
define("IDDOC", 20);

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


//////function for adding a person in the database

// Web handlers

$app->get('/', function() use($app){
  session_start();
  if($_SESSION['connected']){
    return $app->redirect($app['url_generator']->generate('dashboardDoctor'));
  }
  else{
    $app['warning'] = "";
    return $app['twig']->render('index2.twig');
  }
})->bind("home");

$app->post('/', function(request $request) use($app){
  $username = $request->get('username');
  $password = sha1($request->get('password'));

  $login = checkLoginDoctor($username,$password);

  if($login){
    session_start();
    $_SESSION['connected'] = true;
    $_SESSION['idDoc'] = getIdDoc($username);

    return $app->redirect($app['url_generator']->generate('dashboardDoctor'));
  }
  else{
    $app['warning'] = "username or password does not exist";

    return $app['twig']->render('index2.twig');
  }
});

$app->get('/zone', function() use($app){
  //do something
  return '';
});

$app->post('/register', function(request $request) use($app){
  $username = $request->get('username');
  $surname = $request->get('surname');
  $name = $request->get('name');
  $email = $request->get('email');

  if(check_doctor_exist($username,$email)){
    return $app->redirect($app['url_generator']->generate('home'));
  }
  else{
    $id = add_doctor($username,$surname,$name,$email);
    session_start();
    $_SESSION['connected'] = true;
    $_SESSION['idDoc'] = $id;
    return $app->redirect($app['url_generator']->generate('dashboardDoctor'));
  }
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

  $username = $request->get('username');
  //Database connection
	$db = db_connect();
  //**************************

  $response = array();
    //$response['stade'] = 1;
  $query = pg_prepare($db, "get_stade", "SELECT stade FROM users WHERE id=$1;");
    $result= pg_execute($db, "get_stade", array($username));
    $response=pg_fetch_array($result);

    if($response['stade']==1)
    	$response['stade']=true;
 	  if($response['stade']==2)
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

$app->get('/privacy', function() use($app) {
	// $app['monolog']->addDebug('logging output.');
 //  	return $app['twig']->render('index.twig');
	return 'Page under construction';
});

require('webhook.php');

$app->run();
