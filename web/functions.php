<?php

  function db_connect(){
    $conn_string = "host=ec2-54-163-254-48.compute-1.amazonaws.com
        port=5432
        dbname=d9dbi6cl08c0i
        user=ztlvocffwufbva
        password=CjNAryYzgpTUxM2ZsCu7wmdXmj";

        $db = pg_connect($conn_string);

        return $db;
  }

  ////// function to get the last Id

  function getLastIdOfPerson($id){
    $db = db_connect();

  	$query = pg_prepare($db, "last_id", "SELECT id FROM relations WHERE id_user=$1 ORDER BY id DESC LIMIT 1;");
  	$result = pg_execute($db, "last_id", array($id));

  	$arr = pg_fetch_row($result);

    logPerso("idiiii:",$arr[0]);

  	return $arr[0];
  }

  //algo to double-hash username into an id
  function sha1random(){
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $limite = strlen($characters);
    $randString="";
    $len = 28;
    for($i=0;$i<$len;$i++){
      $rand = rand(0, $limite - 1);
      $randString.=$characters[$rand];
    }

    return $randString;
  }

  function addPerson($id, $surname, $relation=false){
    $db = db_connect();

		$query = pg_prepare($db, "add_person", "INSERT INTO relations(surname, id_user, link_user) VALUES($2, $1, $3)");
		pg_execute($db, "add_person", array($id, $surname, $relation));

    return getLastIdOfPerson($id);
  }

  function addLink($id, $surname, $relation){
    $db = db_connect();

  	$query = pg_prepare($db, "update_link", "UPDATE relations SET link_user = $3 WHERE id_user=$1 AND surname=$2;");
    pg_execute($db, "update_link", array($id, $surname, $relation));

    return "Your ".$relation." was added !";
  }


//////// function to get all the reminders of a person
function get_reminders($id){
  $db = db_connect();

  $today = date("Y-m-d");

  $query = pg_prepare($db, "reminders", "SELECT M.label, M.date_meeting, M.time_meeting, M.id_person FROM meetings M WHERE M.date_meeting>=$1 AND M.id_user=$2 ORDER BY M.date_meeting, M.time_meeting;");

  $result = pg_execute($db, "reminders", array($today, $id));

  $response["json"] = array();

  while ($row = pg_fetch_array($result)) {
    $remind = array();
    $remind["label"] = $row["label"];
    $remind["date_task"] = $row["date_meeting"];
    $remind["time_task"] = $row["time_meeting"];
    $remind["id_person"] = $row["id_person"];

    array_push($response["json"], $remind);
  }

  $query = pg_prepare($db, "reminders2", "SELECT surname FROM relations WHERE id=$1;");

  foreach($reminder as $response["json"]){
    if($reminder['id_person']=="")
      $reminder['id_person']="docteur";
    else{
      $result = pg_execute($db, "reminders2", array($today, $id));
      $arr = pg_fetch_row($result);
      $reminder['id_person'] = $arr[0];
    }
  }

  return $response;
}


////// function to test the logging
function test_login($uname, $pass){
  $db = db_connect();

  $query = pg_prepare($db, "logging", "SELECT password, req_id FROM users WHERE username=$1");
  $result = pg_execute($db, "logging", array($uname));

  $arr = pg_fetch_assoc($result);

  if(sha1($pass) == $arr['password']){
    $login = $arr['req_id'];
  }
  else {
    $login = "-1";
  }

  return $login;
}

  //////function for finding a name of a person in db

  function findNameSurname($id){
    $db = db_connect();

  	$query = pg_prepare($db, "surname_name", "SELECT name, surname FROM relations WHERE id_user=$1");

  	return pg_execute($db, "surname_name", array($id));
  }



  //////function to check nb of surnames
  function checkNbOfSurnames($id, $surname){
    $db = db_connect();

    $query = pg_prepare($db, "surname_name", "SELECT name, surname FROM relations WHERE surname=$2 AND id_user=$1");

  	$res = pg_execute($db, "surname_name", array($id,$surname));

  	$arr = pg_fetch_all($res);

  	return count($arr);
  }


  ////function to get id thanks to surname

  function getIdByName($id, $surname){
    $db = db_connect();

  	$nb=10;
  	$nb=0;//checkNbOfSurnames($db, ID, $surname);

  		$query = pg_prepare($db, "get_id", "SELECT id FROM relations WHERE surname=$1");

  		$res = pg_execute($db, "get_id", array($surname));

  		$arr = pg_fetch_row($res);

      if($arr[0])
        $id_perso = $arr[0];
      else
        $id_perso = addPerson($id, $surname);

  		return $id_perso;
  }

  function getUsersList(){
    $db = db_connect();

    $query = pg_prepare($db, "get_users", "SELECT * FROM users;");

    $res = pg_execute($db, "get_users", array());

    $arr = pg_fetch_all($res);

    return json_encode($arr);
  }

  function set_alert($user_id){
    $db = db_connect();

    $query = pg_prepare($db, "update_status", "UPDATE users SET status=false WHERE req_id=$1;");
  	pg_execute($db, "update_status", array($user_id));
  }

  function set_alertzone($user_id){
    $db = db_connect();

    $query = pg_prepare($db, "update_status_zone", "UPDATE users SET alertzone=false WHERE req_id=$1;");
  	pg_execute($db, "update_status_zone", array($user_id));
  }

  function remove_alert($idDoc,$idPatient){
    $db = db_connect();

    $query = pg_prepare($db, "update_status", "UPDATE users SET status=true WHERE idDoctor=$1 AND id=$2;");
  	pg_execute($db, "update_status", array($idDoc,$idPatient));
  }

  function get_stade($user_id){
    $db = db_connect();

    $query = pg_prepare($db, "get_stade", "SELECT stade FROM users WHERE req_id=$1;");
    $result= pg_execute($db, "get_stade", array($user_id));

    return pg_fetch_array($result);
  }

  function get_user_address($user_id){
    $db = db_connect();

    $query = pg_prepare($db, "get_address", "SELECT address FROM users WHERE req_id=$1;");
    $res = pg_execute($db, "get_address", array($user_id));
    $arr = pg_fetch_array($res);

    return $arr['address'];
  }

  function get_user_radius($user_id){
    $db = db_connect();

    $query = pg_prepare($db, "get_radius", "SELECT radius FROM users WHERE req_id=$1;");
    $res = pg_execute($db, "get_radius", array($user_id));
    $arr = pg_fetch_array($res);

    return $arr['radius'];
  }

  function logPerso($label,$value){
    file_put_contents("php://stderr", $label.": ".$value."\n");
  }

?>
