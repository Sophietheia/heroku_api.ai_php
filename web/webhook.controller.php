<?php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

require('webhook.model.php');

$app->post('/webhook', function(Request $request) use($app) {

	$result = $request->request->get('result');
	$user_id = $request->request->get('sessionId');

	logPerso("sessionID: ",$user_id);

/////if user wants to change name

	if($result['action'] == "change.name"){
		$parameters=$result['parameters'];
		$nameToAdd=$parameters['last-name'];

		$check=TRUE;

		$result = findNameSurname($id);

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
			if(pg_execute($db, "new_name", array($id, $arr['surname'], $nameToAdd))){
				$speech = $arr['surname']." changed !";
			}
			else{
				$speech = "couldn't change the name of ".$arr['surname'];
			}
		}
	}


  else if($result['action'] == "send.alert"){
    alert_user($id);
    $speech = "alert sent !";
  }


	////if user wants to find name of a person in his relatives
	else if($result['action'] == "find.name"){
		$parameters=$result['parameters'];
		$surname=$parameters['names'];

		$surname=ucfirst($surname);

		$id = get_id_person($user_id);
		$name = get_name_from_relation($surname,$id);

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
			$id = get_id_person($user_id);
			addPerson($id, $surname, $name);

			$speech=$surname." ".$name." added !";
		}
		else{
			addPerson($id, $surname);

			$result = findNameSurname($id);

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

		$nb = checkNbOfSurnames($id, $surname);

		if($nb>1 && !empty($name)){
			$speech = addLink($id, $surname, $name, $relation);
		}
		else if($nb>1){
			$speech="There is more than 1 person called ".$surname.". Which one are you talking about ?";
		}
		else if($nb<=1){
			addPerson($id, $surname, $name, $relation);
			$speech="Your ".$relation." was added ! nb:";
		}

	}



	////if user says hello, response with name of user
	else if($result['action'] == "hello"){

		$sessionId = $request->request->get('sessionId');

		$check=TRUE;

		$result = findNameSurname($id);

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

			$id_perso=getIdByName($id, $surname, $name);


			if(isset($parameters['lieux']))
				$location=$parameters['lieux'];

			if($id_perso){
				add_rdv($label, $location, $date_meeting, $time, $id, $id_perso);

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

		$id = get_id_person($user_id);
		$meeting = location_meeting($id);

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
		$id = get_id_person($user_id);
		$meeting = date_meeting($id);

    $label = $meeting[0];
		$date = $meeting[1];

		if($date){
			$speech="Your next meeting is ".$label." on ".$date;
		}
		else{
			$speech="You have no next meeting";
		}
	}




	//// if user asks about the person he has a meeting with
	else if($result['action'] == "avec.qui.meeting"){
		$id = get_id_person($user_id);
		$meeting = with_who_meeting($id);

    $label = $meeting[0];
		$name = $meeting[1];
		$surname = $meeting[2];

		if($label){
			$speech="Your next meeting is ".$label." with ".$surname." ".$name;
		}
		else{
			$speech="You have no next meeting";
		}
	}




	//// if user wants to know their next meeting
	else if($result['action'] == "label.meeting"){
		logPerso("userIdLabelMeeting:",$user_id);

		$id = get_id_person($user_id);

		logPerso("idLabelMeeting:",$id);

		$meeting = label_meeting($id);

    $label = $meeting[0];

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
