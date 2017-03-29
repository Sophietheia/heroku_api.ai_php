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

  //algo to double-hash username into an id
  function sha1random(){
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $limite = strlen($characters);
    $len = 40;
    for($i=0;$i<$len;$i++){
      $rand = rand(0, $limite - 1);
      $randString+=$characters[$rand];
    }

    return $randString;
  }

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

  function addLink($db, $id, $surname, $name, $relation){
  	$query = pg_prepare($db, "update_link", "UPDATE relations SET link_user = $4 WHERE id=$1 AND surname=$2 AND name=$3;");

  		if(pg_execute($db, "update_link", array($id, $surname, $name, $relation)))
  			return "Your ".$relation." was added !";
  		else
  			return "pb to add link";
  }


//////// function to get all the reminders of a person
function get_reminders($db, $username){
  $query = pg_prepare($db, "reminders", "SELECT * FROM meetings M,users U WHERE M.id_user=U.id AND U.username=$1");

  return pg_execute($db, "reminders", array($username));
}


////// function to test the logging
function test_login($db, $uname, $pass){
  $query = pg_prepare($db, "logging", "SELECT password FROM users WHERE username=$1");
  $result = pg_execute($db, "logging", array($uname));

  $arr = pg_fetch_assoc($result);

  if(sha1($pass) == $arr['password']){
    $login = true;
  }
  else {
    $login = false;
  }

  return $login;
}

  //////function for finding a name of a person in db

  function findNameSurname($db, $id){
  	$query = pg_prepare($db, "surname_name", "SELECT name, surname FROM relations WHERE id_user=$1");

  	return pg_execute($db, "surname_name", array($id));
  }


  ////// function to get the last Id

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
    $query = pg_prepare($db, "surname_name", "SELECT name, surname FROM relations WHERE surname=$2 AND id_user=$1");

  	$res = pg_execute($db, "surname_name", array($id,$surname));

  	$arr = pg_fetch_all($res);

  	return count($arr);
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

  function getUsersList(){
    $db = db_connect();

    $query = pg_prepare($db, "get_users", "SELECT * FROM users;");

    $res = pg_execute($db, "get_users", array());

    $arr = pg_fetch_all($res);

    return json_encode($arr);
  }

  function set_alert($username){
    $db = db_connect();

    $query = pg_prepare($db, "update_status", "UPDATE users SET status=false WHERE username=$1;");
  	pg_execute($db, "update_status", array($username));
  }

  function set_alertzone($username){
    $db = db_connect();

    $query = pg_prepare($db, "update_status_zone", "UPDATE users SET alertzone=false WHERE username=$1;");
  	pg_execute($db, "update_status_zone", array($username));
  }

  function remove_alert($idDoc,$idPatient){
    $db = db_connect();

    $query = pg_prepare($db, "update_status", "UPDATE users SET status=true WHERE idDoctor=$1 AND id=$2;");
  	pg_execute($db, "update_status", array($idDoc,$idPatient));
  }

?>
