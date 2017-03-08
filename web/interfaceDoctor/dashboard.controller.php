<?php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

require('dashboard.model.php');

$app->get('/dashboardDoctor', function() use($app){
  $app['idDoc'] = IDDOC; //$_COOKIE["idDoc"];
  $app['users'] = json_decode(getUsersListByDoctor($app['idDoc']), true);
  $app['notif'] = '';
  return $app['twig']->render('dash.twig');
});

$app->post('/dashboardDoctor', function(Request $request) use($app){

  $app['idDoc'] = IDDOC; //$_COOKIE["idDoc"];
  $app['users'] = json_decode(getUsersListByDoctor($app['idDoc']), true);

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

    $app['notif'] = "Votre rappel vient d'être ajouté.";
  }
  else if($type == "add"){
    $newUser['iddoctor'] = $request->get('iddoc');
    $newUser['login'] = $request->get('login');
    $newUser['password'] = $request->get('password');
    $newUser['name'] = $request->get('name');
    $newUser['surname'] = $request->get('surname');
    $newUser['phonenumber'] = $request->get('phonenumber');
    $newUser['address'] = $request->get('address');

    // $error = checkUserExist($newUser['login']);
    //
    // if($error == "username"){
    //
    // }
    // else if($error == "email"){
    //
    // }
    // else{
      addUser($newUser);
      $app['notif'] = "Patient ajouté.";
    // }
  }
  else if($type == "changeStatus"){
    $idUser=$request->get('idPatientChangeStatus');
    changeStatus($idUser);
    $app['notif'] = "Status changed.";
  }

  return $app['twig']->render('dash.twig');
});
