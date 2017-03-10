<?php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

require('dashboard.model.php');

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
    $newUser['address'] = $request->get('address');

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
  else if($type == "stade"){
    $idUser=$request->get('idPatientStade');
    $newStade=$request->get('stage');
    changeStade($idUser, $newStade);
    $app['notif'] = "State of disease changed.";
  }
  else if($type == "logout"){
    session_unset();
  }

  $app['users'] = json_decode(getUsersListByDoctor($app['idDoc']), true);

  return $app['twig']->render('dash.twig');
});
