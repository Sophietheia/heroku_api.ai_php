<?php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

require('dashboard.model.php');

$app->get('/dashboardDoctor', function() use($app){
  $app['idDoc'] = IDDOC;
  $app['users'] = json_decode(getUsersList(), true);
  $app['notif'] = '';
  return $app['twig']->render('dashboard.twig');
});

$app->post('/dashboardDoctor', function(Request $request) use($app){
  $app['idDoc'] = IDDOC;
  $app['users'] = json_decode(getUsersList(), true);

  $app['notif'] = "Votre rdv vient d'être ajouté.";

  $newRdv['idDoc'] = $request->get('idDoc');
  $newRdv['idPatient'] = $request->get('idPatient');
  $newRdv['label'] = $request->get('label');
  $newRdv['date'] = $request->get('date-rdv');
  $newRdv['time'] = $request->get('time-rdv');
  $newRdv['location'] = $request->get('location-rdv');

  addNewRdv($newRdv);

  return $app['twig']->render('dashboard.twig');
});
