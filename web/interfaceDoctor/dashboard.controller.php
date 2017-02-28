<?php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

require('dashboard.model.php');

$app->get('/dashboardDoctor', function() use($app){
  $app['idDoc'] = IDDOC;
  $app['users'] = json_decode(getUsersList(), true);
  $app['test2'] = 'test 2 vide';
  return $app['twig']->render('dashboard.twig');
});

$app->post('/dashboardDoctor', function(Request $request) use($app){
  $app['idDoc'] = IDDOC;
  $app['users'] = json_decode(getUsersList(), true);

  $app['test2'] = strval($request->get('label'));

  $newRdv['idDoc'] = $request->get('idDoc');
  $newRdv['idPatient'] = $request->get('idPatient');
  $newRdv['label'] = strval($request->get('label'));
  $newRdv['date'] = date('Y-m-d', strtotime($request->get('date')));
  $newRdv['time'] = date('h:i:s', strtotime($request->get('time')));
  $newRdv['label'] = $request->get('location');

  newRdv($newRdv);

  return $app['twig']->render('dashboard.twig');
});
