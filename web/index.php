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
function addPerson($db, $id, $surname, $name=false, $relation=NULL){
	if($name){
		$query = pg_prepare($db, "add_person", "INSERT INTO entourage(prenom, id_utilisateur, lien_utilisateur, nom) VALUES($2, $1, $3, $4)");

		if(pg_execute($db, "add_person", array($id, $surname, $relation, $name)))
			return getLastIdOfPerson($db, ID);
	}
	else{
		$query = pg_prepare($db, "add_person", "INSERT INTO entourage(prenom, id_utilisateur, lien_utilisateur) VALUES($2, $1, $3)");

		if(pg_execute($db, "add_person", array($id, $surname, $relation)))
			return getLastIdOfPerson($db, ID);
	}
		return NULL;
}

function findNameSurname($db, $id){
	$query = pg_prepare($db, "prenom_nom", "SELECT nom, prenom FROM entourage WHERE id_utilisateur=$1");

	return pg_execute($db, "prenom_nom", array($id));
}

function getLastIdOfPerson($db, $id){
	$query = pg_prepare($db, "id", "SELECT id FROM entourage WHERE id_utilisateur=$1 ORDER BY id DESC LIMIT 1;");

	if($result = pg_execute($db, "id", array(ID))){
		while($arr = pg_fetch_assoc($result)){
			return $arr['id'];
		}
	}

	return NULL;
}

//***********************************************************************************************************//

// Web handlers

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

	if($result['action'] == "change.name"){ //to remove on the final version
		$parameters=$result['parameters'];
		$nameToAdd=$parameters['last-name'];

		$check=TRUE;

		$result = findNameSurname($db, ID);

		$speech = "Nothing to change !";

		while($check && $arr = pg_fetch_assoc($result)){
			$name=$arr['nom'];
			$surname=$arr['prenom'];

			if($name && strtolower($surname)==strtolower($parameters['surname'])){
				$speech=$surname." has a name already !";
			}
			else if(!$name && strtolower($surname)==strtolower($parameters['surname'])){
				$check=FALSE;
			}
		}

		if(!$check){
			$query = pg_prepare($db, "new_name", "UPDATE entourage SET nom=$3 WHERE prenom=$2 AND id_utilisateur=$1");
			if(pg_execute($db, "new_name", array(ID, $arr['prenom'], $nameToAdd))){
				$speech = $arr['prenom']." changed !";
			}
			else{
				$speech = "couldn't change the name of ".$arr['prenom'];
			}
		}
	}
	else if($result['action'] == "find.name"){
		$parameters=$result['parameters'];
		$surname=$parameters['names'];

		//---------------Erreur_1ere_lettre_MAJ-----------------
		$premierelettre = strtoupper(substr($surname, 0, 1)); 
		$reste=substr($surname, 1);
		$surname=$premierelettre.$reste;
		//------------------------------------------------------


		//-----------------------DATABASE-----------------------
		$query = pg_prepare($db, "prenom_nom", "SELECT nom FROM entourage WHERE prenom = $1 AND id_utilisateur=$2");

		$result = pg_execute($db, "prenom_nom", array($surname, ID));

		$arr = pg_fetch_array ($result, 0, PGSQL_NUM);

		$name = $arr[0];
		//------------------------------------------------------

		if($name)
			$speech="The name of ".$surname." is ".$name.".";	
		else
			$speech="You don't know that person.";
	}
	else if($result['action'] == "add.person"){
		$parameters=$result['parameters'];
		$surname=$parameters['names'];
		//-----------------------DATABASE-----------------------
		addPerson($db, ID, $surname);
		//------------------------------------------------------

		$result = findNameSurname($db, ID);

		while($arr = pg_fetch_assoc($result)){
			if($arr['nom']==""){
				$speech="What's the name of ".$arr['prenom']." ?";
			}
		}
	}
	else if($result['action'] == "add.link"){
		$nb=0;
		$parameters=$result['parameters'];
		$surname=$parameters['surname'];
		$relation=$parameters['relation'];

		$result = findNameSurname($db, ID);

		while($arr = pg_fetch_assoc($result)){
			if($arr['prenom']==$parameters['surname']){
				$nb++;
			}
		}

		if($nb>1){
			$peech="There is more than 1 person called ".$parameters['surname'].". Which one are you talking about ?";
		}
		else if($nb<=1){
			addPerson($db, ID, $parameters['surname'], $arr['nom'], $relation);
			$speech="Your ".$relation." was added !";
		}

	}
	else if($result['action'] == "hello"){
		$check=TRUE;

		$result = findNameSurname($db, ID);

		$speech = "Hello ! How are you ?";

		while($check && $arr = pg_fetch_assoc($result)){
			$name=$arr['nom'];
			if(!$name){
				$speech ="Hello ! I have a question... What's the family name of ".$arr['prenom']." ?";
				$check=FALSE;
			}
		}
	}
	else if($result['action'] == "register.rdv"){
		$parameters=$result['parameters'];
		$label=$parameters['rdv'];

		if(isset($parameters['date']))
			$date_rdv=$parameters['date'];

		// if(isset($parameters['time']))
		// 	$time=$parameters['time'];
		// else
			$time='00:00:00';

		$id_perso;

		if(isset($parameters['names'])){
			$perso_added=false;
			$name=$parameters['names'];
			$surname=$parameters['surname'];

			$query = pg_prepare($db, "get_id", "SELECT id FROM entourage WHERE prenom=$1 AND nom=$2");

			$res = pg_execute($db, "get_id", array($surname, $name));

			if($res){
				while($arr = pg_fetch_assoc($res))
					$id_perso = $arr['id'];
			}
			else{
				if($id_perso = addPerson($db, ID, $surname, $name))
					$perso_added=true;
			}
		}

		if(isset($parameters['lieux']))
			$lieu=$parameters['lieux'];

		$query = "INSERT INTO rdv(label, lieu, date_rdv, time_rdv, id_utilisateur, id_personne) VALUES('$label', '$lieu', '$date_rdv', '$time', '".ID."', 3);";//'$id_perso');";

		if($perso_added)
			$speech="What's the name of ";
		else if($result = pg_query($db, $query))
			$speech="rdv added";
		else
			$speech="pb adding the rdv";
	}
	else if($result['action'] == "location.meeting"){
		
		$today=date("Y-m-d");	
		$query = pg_prepare($db, "location_meeting", "SELECT lieu, label FROM rdv WHERE id_utilisateur=$1 AND date_rdv>='$today' GROUP BY label, id HAVING date_rdv=MIN(date_rdv);");
		
		$result = pg_execute($db, "location_meeting", array(ID));

		$arr = pg_fetch_array ($result, 0, PGSQL_NUM);


		while($arr = pg_fetch_array($result)){
			$location = $arr['lieu'];
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
	else if($result['action'] == "date.meeting"){
		$today=date("Y-m-d");

		$query = pg_prepare($db, "next_meeting", "SELECT date_rdv, label FROM rdv WHERE id_utilisateur=$1 AND date_rdv>='$today' GROUP BY label, id HAVING date_rdv=MIN(date_rdv);");
		
		$result = pg_execute($db, "next_meeting", array(ID));

		while($arr = pg_fetch_array($result)){
			$date = $arr['date_rdv'];
			$label = $arr['label'];
		}

		if($date){
			$speech="Your next meeting is ".$label." on ".$date;
		}
		else{
			$speech="You have no next meeting";
		}
	}
	else if($result['action'] == "avec.qui.meeting"){
		$today=date("Y-m-d");

		$query = pg_prepare($db, "avec_meeting", "SELECT rdv.label AS label, entourage.nom AS nom, entourage.prenom AS prenom FROM rdv, entourage WHERE rdv.id_utilisateur=$1 AND rdv.date_rdv>='$today' AND rdv.id_personne=entourage.id GROUP BY rdv.label, rdv.id, entourage.id HAVING rdv.date_rdv=MIN(rdv.date_rdv);
");
		
		$result = pg_execute($db, "avec_meeting", array(ID));

		while($arr = pg_fetch_array($result)){
			$nom = $arr['nom'];
			$prenom = $arr['prenom'];
			$label = $arr['label'];
		}

		if($label){
			$speech="Your next meeting is ".$label." with ".$prenom." ".$nom;
		}
		else{
			$speech="You have no next meeting";
		}
	}
	else if($result['action'] == "label.meeting"){
		$today=date("Y-m-d");

		$query = pg_prepare($db, "label_meeting", "SELECT label FROM rdv WHERE id_utilisateur=$1 AND date_rdv>='$today' GROUP BY label, id HAVING date_rdv=MIN(date_rdv);");
		
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

