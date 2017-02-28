<?php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$app->get('/dashboardDoctor', function() use($app){
  $app['idDoc'] = IDDOC;
  $app['users'] = json_decode(getUsersList(), true);
  $app['test2'] = 'test 2 vide';
  return $app['twig']->render('dashboard.twig');
});

$app->post('/dashboardDoctor', function(Request $request) use($app){
  $app['idDoc'] = IDDOC;
  $app['users'] = json_decode(getUsersList(), true);

  // $form = $app['form.factory']->createBuilder(FormType::class, $data)
  //       ->add('idDoc')
  //       ->add('idPatient')
  //       ->add('label')
  //       ->add('date')
  //       ->add('time')
  //       ->add('location')
  //       ->getForm();
  //
  // $form->handleRequest($request);
  //
  // if ($form->isSubmitted() && $form->isValid()) {
  //     $app['test2'] = $form->getData();
  // }

  $data = $request->request->get('form');

  $app['test2'] = $data['label'];

  return $app['twig']->render('dashboard.twig');
});
