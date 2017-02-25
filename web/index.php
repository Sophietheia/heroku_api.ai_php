<?php

define("ID", 1);
define("IDDOC", 1234);

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\Extension\Core\Type\FormType;
//use Silex\Provider\FormServiceProvider;

require('../vendor/autoload.php');


$app = new Silex\Application();
$app['debug'] = true;

$app->before(function (Request $request) {
    if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
        $data = json_decode($request->getContent(), true);
        $request->request->replace(is_array($data) ? $data : array());
    }
});

//$app->register(new FormServiceProvider());

// Register the monolog logging service
$app->register(new Silex\Provider\MonologServiceProvider(), array(
  'monolog.logfile' => 'php://stderr',
));

// Register view rendering
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/views',
));

require 'functions.php';


//////function for adding a person in the database

// Web handlers

$app->get('/', function() use($app){
  return $app['twig']->render('index.twig');
});

$app->get('/talk', function() use($app){
  return $app['twig']->render('talk.twig');
});

$app->get('/alert', function() use($app){
  //to something
  return '';
});

$app->get('/register', function() use($app){
  return 'register page';
});

$app->get('/dashboardDoctor', function() use($app){
  $app['idDoc'] = IDDOC;
  $app['users'] = json_decode(getUsersList(), true);
  $app['test2'] = 'test 2 vide';
  return $app['twig']->render('dashboard.twig');
});

$app->post('/dashboardDoctor', function(Request $request) use($app){
  $app['idDoc'] = IDDOC;
  $app['users'] = json_decode(getUsersList(), true);

  $form = $this->createBuilder(FormType::class, $data)
        ->add('idDoc')
        ->add('idPatient')
        ->add('label')
        ->add('date')
        ->add('time')
        ->add('location')
        ->getForm();

  $form->handleRequest($request);

  if ($form->isSubmitted() && $form->isValid()) {
      $app['test2'] = $form->getData();
  }

  return $app['twig']->render('dashboard.twig');
});

$app->post('/login', function(Request $request) use($app){
  //Database connection
	$db = db_connect();

	//$result = $request->request->get('result');

  //Test login to perform
  //**************************
  //**************************
  $response = array();
  $response['connection'] = true;

  return json_encode($response);
});

$app->get('/testdb', function() use($app){
    if (isset($_GET["info"])) {

      $db = db_connect();

      // array for JSON response
      $response = array();
      $meetings = $_GET['info'];

      // get a product from products table
      $query = pg_prepare($db, "get_meetings", "SELECT * FROM users WHERE id_user=$1;");
      $result = pg_execute($db, "get_meetings", array(ID));

      if (!empty($result)) {
          // check for empty result
          if (pg_num_rows($result) > 0) {

              // user node
              $response["meetings"] = array();

              while ($row = pg_fetch_array($result)) {
                $meeting = array();
                $meeting["label"] = $row["label"];
                $meeting["location"] = $row["location"];
                $meeting["date_meeting"] = $row["date_meeting"];
                // success
                $response["success"] = 1;

                array_push($response["meetings"], $meeting);
              }

              // echoing JSON response
              return json_encode($response);
          } else {
              // no product found
              $response["success"] = 2;
              $response["message"] = "No meeting found";

              // echo no users JSON
              return json_encode($response);
          }
      } else {
          // no product found
          $response["success"] = 3;
          $response["message"] = "No meeting found";

          // echo no users JSON
          return json_encode($response);
      }
  } else {
      // required field is missing
      $response["success"] = 4;
      $response["message"] = "Required field(s) is missing";

      // echoing JSON response
      return json_encode($response);
  }
});

$app->post('/register', function() use($app){
  return 'register page';
});

$app->get('/privacy', function() use($app) {
	// $app['monolog']->addDebug('logging output.');
 //  	return $app['twig']->render('index.twig');
	return 'Page under construction';
});

$app->post('/webhook', function(Request $request) use($app) {

	//-----------------------DATABASE---------------------------
	$db = db_connect();
	//----------------------------------------------------------

	$result = $request->request->get('result');


/////if user wants to change name

	if($result['action'] == "change.name"){ //to remove on the final version
		$parameters=$result['parameters'];
		$nameToAdd=$parameters['last-name'];

		$check=TRUE;

		$result = findNameSurname($db, ID);

		$speech = "Nothing to change !";

		while($check && $arr = pg_fetch_assoc($result)){
			$name=$arr['name'];
			$surname=$arr['surname'];

			if($name && strtolower($surname)==strtolower($parameters['surname'])){
				$speech=$surname." has a name already !";
			}
			else if(!$name && strtolower($surname)==strtolower($parameters['surname'])){
				$check=FALSE;
			}
		}

		if(!$check){
			$query = pg_prepare($db, "new_name", "UPDATE relations SET name=$3 WHERE surname=$2 AND id_user=$1");
			if(pg_execute($db, "new_name", array(ID, $arr['surname'], $nameToAdd))){
				$speech = $arr['surname']." changed !";
			}
			else{
				$speech = "couldn't change the name of ".$arr['surname'];
			}
		}
	}


  else if($result['action'] == "send.alert"){
    alert_user(ID);
    $speech = "alert sent !";
  }


	////if user wants to find name of a person in his relatives
	else if($result['action'] == "find.name"){
		$parameters=$result['parameters'];
		$surname=$parameters['names'];

		//---------------Erreur_1ere_lettre_MAJ-----------------
		$firstletter = strtoupper(substr($surname, 0, 1));
		$reste=substr($surname, 1);
		$surname=$firstletter.$reste;
		//------------------------------------------------------


		//-----------------------DATABASE-----------------------
		$query = pg_prepare($db, "surname_name", "SELECT name FROM relations WHERE surname = $1 AND id_user=$2");

		$result = pg_execute($db, "surname_name", array($surname, ID));

		$arr = pg_fetch_array ($result, 0, PGSQL_NUM);

		$name = $arr[0];
		//------------------------------------------------------

		if($name)
			$speech="The name of ".$surname." is ".$name.".";
		else
			$speech="You don't know that person.";
	}





	///if user wants to add a new person
	else if($result['action'] == "add.person"){
		$parameters=$result['parameters'];
		$surname=$parameters['names'];
		$name=$parameters['last-name'];
		//-----------------------DATABASE-----------------------
		if(!empty($name)){
			addPerson($db, ID, $surname, $name);

			$speech=$surname." ".$name." added !";
		}
		else{
			addPerson($db, ID, $surname);

			$result = findNameSurname($db, ID);

			while($arr = pg_fetch_assoc($result)){
				if($arr['name']==""){
					$speech="What's the name of ".$arr['surname']." ?";
				}
			}
		}
		//------------------------------------------------------
	}



	///if user wants to specify if person is "brother" "sister"...
	else if($result['action'] == "add.link"){
		$nb = 0;
		$parameters = $result['parameters'];
		$surname = ucfirst($parameters['surname']);
		$name = $parameters['last-name'];
		$relation = $parameters['relation'];

		$nb = checkNbOfSurnames($db, ID, $surname);

		if($nb>1 && !empty($name)){
			$speech = addLink($db, ID, $surname, $name, $relation);
		}
		else if($nb>1){
			$speech="There is more than 1 person called ".$surname.". Which one are you talking about ?";
		}
		else if($nb<=1){
			addPerson($db, ID, $surname, $name, $relation);
			$speech="Your ".$relation." was added ! nb:";
		}

	}



	////if user says hello, response with name of user
	else if($result['action'] == "hello"){
		$check=TRUE;

		$result = findNameSurname($db, ID);

		$speech = "Hello ! How are you ?";

		while($check && $arr = pg_fetch_assoc($result)){
			$name=$arr['name'];
			if(!$name){
				$speech ="Hello ! I have a question... What's the family name of ".$arr['surname']." ?";
				$check=FALSE;
			}
		}
	}



	///if user wants to register a meeting
	else if($result['action'] == "register.rdv"){
		$parameters=$result['parameters'];
		$label=$parameters['rdv'];

		if(isset($parameters['date']))
			$date_meeting=$parameters['date'];

		// if(isset($parameters['time']))
		// 	$time=$parameters['time'];
		// else
			$time='00:00:00';

		$id_perso=false;

		if(isset($parameters['names'])){
			$perso_added=false;
			$name=$parameters['last-name'];
			$surname=$parameters['names'];

			$id_perso=getIdByName($db, ID, $surname, $name);


			if(isset($parameters['lieux']))
				$location=$parameters['lieux'];

			if($id_perso){
				$query = "INSERT INTO meetings(label, location, date_meeting, time_meeting, id_user, id_person) VALUES('$label', '$location', '$date_meeting', '$time', '".ID."', '$id_perso');";

				pg_query($db, $query);

				$speech="rdv added !";
			}
			else
				$speech="pb id_perso";
		}
		else
			$speech="pb adding the rdv";
	}





	////if user wants to know location of meeting
	else if($result['action'] == "location.meeting"){

		$today=date("Y-m-d");
		$query = pg_prepare($db, "location_meeting", "SELECT label, location FROM meetings WHERE id_user=$1 AND date_meeting>=$2 GROUP BY label, id HAVING date_meeting=MIN(date_meeting);");

		$result = pg_execute($db, "location_meeting", array(ID,$today));

		$meeting = pg_fetch_row($result);

    $label = $meeting[0];
		$location = $meeting[1];

		if($location!=""){
			$speech="Your next meeting is ".$label." at ".$location;
		}
    else{
			$speech="No location";
		}

	}




	///if user wants to know the date of meeting
	else if($result['action'] == "date.meeting"){
		$today=date("Y-m-d");

		$query = pg_prepare($db, "next_meeting", "SELECT date_meeting, label FROM meetings WHERE id_user=$1 AND date_meeting>='$today' GROUP BY label, id HAVING date_meeting=MIN(date_meeting);");

		$result = pg_execute($db, "next_meeting", array(ID));

		while($arr = pg_fetch_array($result)){
			$date = $arr['date_meeting'];
			$label = $arr['label'];
		}

		if($date){
			$speech="Your next meeting is ".$label." on ".$date;
		}
		else{
			$speech="You have no next meeting";
		}
	}




	//// if user asks about the person he has a meeting with
	else if($result['action'] == "avec.qui.meeting"){
		$today=date("Y-m-d");

		$query = pg_prepare($db, "with_meeting", "SELECT meetings.label AS label, relations.name AS name, relations.surname AS surname FROM meetings, relations WHERE meetings.id_user=$1 AND meetings.date_meeting>='$today' AND meetings.id_person=relations.id GROUP BY meetings.label, meetings.id, relations.id HAVING meetings.date_meeting=MIN(meetings.date_meeting);
");

		$result = pg_execute($db, "with_meeting", array(ID));

		while($arr = pg_fetch_array($result)){
			$name = $arr['name'];
			$surname = $arr['surname'];
			$label = $arr['label'];
		}

		if($label){
			$speech="Your next meeting is ".$label." with ".$surname." ".$name;
		}
		else{
			$speech="You have no next meeting";
		}
	}




	//// if user wants to add a label to a meeting
	else if($result['action'] == "label.meeting"){
		$today=date("Y-m-d");

		$query = pg_prepare($db, "label_meeting", "SELECT label FROM meetings WHERE id_user=$1 AND date_meeting>='$today' GROUP BY label, id HAVING date_meeting=MIN(date_meeting);");

		$result = pg_execute($db, "label_meeting", array(ID));

		while($arr = pg_fetch_array($result)){

			$label = $arr['label'];
		}

		if($label){
			$speech="Your next meeting is ".$label;
		}
		else{
			$speech="You have no next meeting";
		}
	}

	else{
		$speech="I do not understand...";
	}

	$res=array(
		"speech"=> $speech,
		"displayText"=> $speech,
		"source"=> "apiai-test-php"
    );

	return $app->json($res);
});

$app->run();
