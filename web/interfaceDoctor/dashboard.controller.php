<?php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

require('dashboard.model.php');

$app->get('/dashboardDoctor', function() use($app){
  $app['idDoc'] = IDDOC; //$_COOKIE["idDoc"];
  $app['users'] = json_decode(getUsersList(), true);
  $app['notif'] = '';
  return $app['twig']->render('dash.twig');
});

$app->post('/dashboardDoctor', function(Request $request) use($app){

  $app['idDoc'] = IDDOC; //$_COOKIE["idDoc"];
  $app['users'] = json_decode(getUsersListByDoctor($app['idDoc']), true);



  $app['notif'] = "Votre rdv vient d'être ajouté.";

  $newRdv['idDoc'] = $request->get('idDoc');
  $newRdv['idPatient'] = $request->get('idPatient');
  $newRdv['label'] = $request->get('label');
  $newRdv['date'] = $request->get('date-rdv');
  $newRdv['time'] = $request->get('time-rdv');
  $newRdv['location'] = $request->get('location-rdv');

  addNewRdv($newRdv);

  return $app['twig']->render('dash.twig');
});
