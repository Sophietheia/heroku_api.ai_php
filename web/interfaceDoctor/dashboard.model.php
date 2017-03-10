<?php

function addNewRdv($rdv){
  $db = db_connect();

  $query = pg_prepare($db, "add_rdv", "INSERT INTO meetings(label, location, date_meeting, time_meeting, id_user, id_person) VALUES($1, $2, $3, $4, $5, $6);");
  pg_execute($db, "add_rdv", array($rdv['label'], $rdv['location'], $rdv['date'], $rdv['time'], $rdv['idPatient'], $rdv['idDoc']));
}


function addNewRappel($rappel){
  $db = db_connect();

  $query = pg_prepare($db, "add_rappel", "INSERT INTO meetings(label, date_meeting, time_meeting, id_user) VALUES($1, $2, $3, $4);");
  pg_execute($db, "add_rappel", array($rappel['label'], $rappel['date'], $rappel['time'], $rappel['idPatient']));
}

function changeStatus($idUser){

   $db = db_connect();

   $query = pg_prepare($db, "change_status", "UPDATE users SET status='true' WHERE id=$1");

   pg_execute($db, "change_status", array($idUser));
 }


function changeStade($idUser,$newStade){

   $db = db_connect();

   $query = pg_prepare($db, "change_stade", "UPDATE users SET stade=$1 WHERE id=$2");

   pg_execute($db, "change_stade", array($newStade, $idUser));
 }

function checkUserExist($username,$phone){
  $db = db_connect();

  $query = pg_prepare($db, "check_user", "SELECT * FROM users WHERE username=$1 OR phonenumber=$2 LIMIT 1;");
  $res = pg_execute($db, "check_user", array($username,$phone));

  $arr = pg_fetch_array($res);

  if(!empty($arr['username']) || !empty($arr['phonenumber'])){
    return true;
  }
  else {
    return false;
  }
}


function getUsersListByDoctor($iddoc){
  $db = db_connect();

  $query = pg_prepare($db, "get_users", "SELECT * FROM users WHERE iddoctor=$1;");

  $res = pg_execute($db, "get_users", array($iddoc));

  $arr = pg_fetch_all($res);

  return json_encode($arr);
}


function addUser($newUser){
  $db = db_connect();

  $query = pg_prepare($db, "insert_users", "INSERT INTO users(iddoctor,name,surname,username,password,phonenumber,address,status,alertzone) VALUES($1,$2,$3,$4,$5,$6,$7,$8,$9);");

  pg_execute($db, "insert_users", array($newUser['iddoctor'],$newUser['name'], $newUser['surname'], $newUser['login'], sha1($newUser['password']), $newUser['phonenumber'], $newUser['address'],true,true));
}

function checkLoginDoctor($username, $password){
  $db = db_connect();

  $query = pg_prepare($db, "check_login", "SELECT * FROM doctors WHERE username=$1;");

  $res = pg_execute($db, "check_login", array($username));

  $arr = pg_fetch_array($res);

  if($arr['password'] == $password){
    return true;
  }
  else {
    return false;
  }
}

function getIdDoc($username){
  $db = db_connect();

  $query = pg_prepare($db, "get_id", "SELECT id FROM doctors WHERE username=$1;");

  $res = pg_execute($db, "get_id", array($username));

  $arr = pg_fetch_array($res);

  return $arr['id'];
}
