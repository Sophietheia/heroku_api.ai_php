<?php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

require('dashboard.model.php');

$app->get('/dashboardDoctor', function() use($app){
  $app['idDoc'] = IDDOC;
  $app['users'] = json_decode(getUsersList(), true);
  return $app['twig']->render('dashboard.twig');
});

$app->post('/dashboardDoctor', function(Request $request) use($app){
  $app['idDoc'] = IDDOC;
  $app['users'] = json_decode(getUsersList(), true);

  $newRdv['idDoc'] = $request->get('idDoc');
  $newRdv['idPatient'] = $request->get('idPatient');
  $newRdv['label'] = $request->get('label');
  $newRdv['date'] = $request->get('date');
  $newRdv['time'] = $request->get('time');
  $newRdv['label'] = $request->get('location');

  newRdv($newRdv);

  return $app['twig']->render('dashboard.twig');
});
