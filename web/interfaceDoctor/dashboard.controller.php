<?php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

require('dashboard.model.php');

$app->get('/', function() use($app){
  session_start();
  if($_SESSION['connected']){
    return $app->redirect($app['url_generator']->generate('dashboardDoctor'));
  }
  else{
    $app['warning'] = "";
    return $app['twig']->render('index3.twig');
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

    return $app['twig']->render('index3.twig');
  }
});

$app->get('/dashboardDoctor', function() use($app){
  session_start();
  if(!$_SESSION['connected'])
    return $app->redirect($app['url_generator']->generate('home'));

  $app['connected'] = true;
  $app['idDoc'] = $_SESSION['idDoc'];
  $app['users'] = json_decode(getUsersListByDoctor($app['idDoc']), true);

  $app['notif'] = '';
  return $app['twig']->render('dash.twig');
})->bind("dashboardDoctor");

$app->post('/dashboardDoctor', function(Request $request) use($app){
  session_start();
  if(!$_SESSION['connected'])
    return $app->redirect($app['url_generator']->generate('home'));

  $app['connected'] = true;
  $app['idDoc'] = $_SESSION['idDoc'];

  $type = $request->get('type');

  if($type == "rdv"){

    $newRdv['idDoc'] = $request->get('idDocRdv');
    $newRdv['idPatient'] = $request->get('idPatientRdv');
    $newRdv['label'] = $request->get('label');
    $newRdv['date'] = $request->get('date-rdv');
    $newRdv['time'] = $request->get('time-rdv');
    $newRdv['location'] = $request->get('location-rdv');

    addNewRdv($newRdv);

    $app['notif'] = "Votre rdv vient d'être ajouté.";
  }
  else if($type == "rappel"){
    $newRappel['idPatient'] = $request->get('idPatientRappel');
    $newRappel['label'] = $request->get('label2');
    $newRappel['date'] = $request->get('date-rappel2');
    $newRappel['time'] = $request->get('time-rappel2');

    logPerso("label2: ",$newRappel['label']);
    logPerso("date2: ",$newRappel['date']);

    addNewRappel($newRappel);

    $app['notif'] = "Your reminder was just added.";
  }
  else if($type == "add"){
    $newUser['iddoctor'] = $request->get('iddoc');
    $newUser['login'] = $request->get('login');
    $newUser['password'] = $request->get('password');
    $newUser['name'] = $request->get('name');
    $newUser['surname'] = $request->get('surname');
    $newUser['phonenumber'] = $request->get('phonenumber');
    $newUser['address'] = $request->get('st_number').' '.$request->get('st_name').', '.$request->get('code_postal').' '.$request->get('city').', '.$request->get('country');
    $newUser['radius'] = $request->get('radius');
    $newUser['stage'] = $request->get('stage');
    $newUser['req_id'] = sha1random();

    if(checkUserExist($newUser['login'],$newUser['phonenumber'])){
      $app['notif'] = "Patient already exists.";
    }
    else{
      addUser($newUser);
      $app['notif'] = "Patient added.";
    }
  }
  else if($type == "changeStatus"){
    $idUser=$request->get('idPatientStatus');
    changeStatus($idUser);
    $app['notif'] = "Status changed.";
  }
  else if($type == "changeZoneStatus"){
    $idUser=$request->get('idPatientZoneStatus');
    changeZoneStatus($idUser);
    $app['notif'] = "Zone status changed.";
  }
  else if($type == "stade"){
    $idUser=$request->get('idPatientStade');
    $newStade=$request->get('stage');
    changeStade($idUser, $newStade);
    $app['notif'] = "State of disease changed.";
  }

  $app['users'] = json_decode(getUsersListByDoctor($app['idDoc']), true);

  return $app['twig']->render('dash.twig');
});

$app->get('/logout', function() use($app){
  session_start();
  session_unset();
  return $app->redirect($app['url_generator']->generate('home'));
})->bind("logout");

$app->post('/register', function(request $request) use($app){
  $username = $request->get('username');
  $surname = $request->get('surname');
  $name = $request->get('name');
  $email = $request->get('email');
  $password = $request->get('password');

  if(check_doctor_exist($username,$email)){
    return $app->redirect($app['url_generator']->generate('home'));
  }
  else{
    $id = add_doctor($username,$surname,$name,$email,sha1($password));
    session_start();
    $_SESSION['connected'] = true;
    $_SESSION['idDoc'] = $id;
    return $app->redirect($app['url_generator']->generate('dashboardDoctor'));
  }
});

$app->get('/privacy', function() use($app) {
	// $app['monolog']->addDebug('logging output.');
 //  	return $app['twig']->render('index.twig');
	return 'Page under construction';
});
