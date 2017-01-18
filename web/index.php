<?php

define("ID", 1);

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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

//*******************************************a few functions*************************************************//




//////function for adding a person in the database
function addPerson($db, $id, $surname, $name=false, $relation=false){
	if($name && $relation){
		$query = pg_prepare($db, "add_person", "INSERT INTO relations(surname, id_user, link_user, name) VALUES($2, $1, $3, $4)");

		if(pg_execute($db, "add_person", array($id, $surname, $relation, $name)))
			return getLastIdOfPerson($db, ID);
	}
	else if($relation){
		$query = pg_prepare($db, "add_person", "INSERT INTO relations(surname, id_user, link_user) VALUES($2, $1, $3)");

		if(pg_execute($db, "add_person", array($id, $surname, $relation)))
			return getLastIdOfPerson($db, ID);
	}
	else if($name){
		$query = pg_prepare($db, "add_person", "INSERT INTO relations(surname, id_user, name) VALUES($2, $1, $3)");

		if(pg_execute($db, "add_person", array($id, $surname, $name)))
			return getLastIdOfPerson($db, ID);
	}
	else{
		$query = pg_prepare($db, "add_person", "INSERT INTO relations(surname, id_user) VALUES($2, $1)");

		if(pg_execute($db, "add_person", array($id, $surname)))
			return getLastIdOfPerson($db, ID);
	}
	
	return NULL;
}

function addLink($db, $id, $surname, $relation){
	$query = pg_prepare($db, "add_link", "INSERT INTO relations(surname, id_user, link_user) VALUES($2, $1, $3);");

		if(pg_execute($db, "add_link", array($id, $surname, $relation)))
			return "Your ".$relation." was added !";
		else
			return "pb to add link";
}



//////function for finding a name of a person in db 

function findNameSurname($db, $id){
	$query = pg_prepare($db, "surname_name", "SELECT name, surname FROM relations WHERE id_user=$1");

	return pg_execute($db, "surname_name", array($id));
}

function getLastIdOfPerson($db, $id){
	$query = pg_prepare($db, "id", "SELECT id FROM relations WHERE id_user=$1 ORDER BY id DESC LIMIT 1;");

	if($result = pg_execute($db, "id", array(ID))){
		while($arr = pg_fetch_assoc($result)){
			return $arr['id'];
		}
	}

	return NULL;
}
//////function to check nb of surnames 
function checkNbOfSurnames($db, $id, $surname){
	$nb=0;
	$result = findNameSurname($db, ID);

	while($arr = pg_fetch_assoc($result)){
		if($arr['surname']==$surname){
			$nb++;
		}
	}

	return $nb;
}


////function to get id thanks to surname

function getIdByName($db, $id, $surname, $name=false){
	$nb=10;
	$nb=0;//checkNbOfSurnames($db, ID, $surname);

	if($name){
		$query = pg_prepare($db, "get_id", "SELECT id FROM relations WHERE surname=$1 AND name=$2");

		$res = pg_execute($db, "get_id", array($surname, $name));

		while($arr = pg_fetch_assoc($res))
			$id_perso = $arr['id'];

		return $id_perso;
	}
	//if user has only one surname
	else if($nb<=1){
		$query = pg_prepare($db, "get_id", "SELECT id FROM relations WHERE surname=$1;");

		$res = pg_execute($db, "get_id", array($surname));

		while($arr = pg_fetch_assoc($res))
			$id_perso = $arr['id'];

		if(!empty($id_perso))
			return $id_perso;
	}
	else{
		return addPerson($db, ID, $surname, $name);
	}

	return false;
}

//***********************************************************************************************************//

// Web handlers


////// Connexion to the database 

$app->get('/privacy', function(Request $request) use($app) {
	echo 'Page under construction';
}

$app->post('/webhook', function(Request $request) use($app) {

	//-----------------------DATABASE---------------------------
	$conn_string = "host=ec2-54-163-254-48.compute-1.amazonaws.com 
			port=5432 
			dbname=d9dbi6cl08c0i 
			user=ztlvocffwufbva 
			password=CjNAryYzgpTUxM2ZsCu7wmdXmj";

			$db = pg_connect($conn_string);
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
		$nb=0;
		$parameters=$result['parameters'];
		$surname=$parameters['surname'];
		$name=$parameters['name'];
		$relation=$parameters['relation'];

		$nb=checkNbOfSurnames($db, ID, $surname);

		if($nb>1 && !empty($name)){
			$speech = addLink($db, ID, $surname, $name, $relation);
		}
		else if($nb>1){
			$speech="There is more than 1 person called ".$parameters['surname'].". Which one are you talking about ?";
		}
		else if($nb<=1){
			addPerson($db, ID, $surname, $name, $relation);
			$speech="Your ".$relation." was added !";
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
		$query = pg_prepare($db, "location_meeting", "SELECT location, label FROM meetings WHERE id_user=$1 AND date_meeting>='$today' GROUP BY label, id HAVING date_meeting=MIN(date_meeting);");
		
		$result = pg_execute($db, "location_meeting", array(ID));

		$arr = pg_fetch_array ($result, 0, PGSQL_NUM);


		while($arr = pg_fetch_array($result)){
			$location = $arr['location'];
			$label = $arr['label'];
		}
	

		if($location)
		{
			$speech=$speech="Your next meeting is ".$label." at ".$location;
		}

		else
		{
			$speech="You don't have any meeting";
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

