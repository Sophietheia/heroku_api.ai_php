<?php

function addNewRdv($rdv){
  $db = db_connect();

  $query = pg_prepare($db, "add_rdv", "INSERT INTO meetings(label, location, date_meeting, time_meeting, id_user) VALUES($1, $2, $3, $4, $5);");
  pg_execute($db, "add_rdv", array($rdv['label'], $rdv['location'], $rdv['date'], $rdv['time'], $rdv['idPatient']));
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

 function changeZoneStatus($idUser){

    $db = db_connect();

    $query = pg_prepare($db, "change_zone_status", "UPDATE users SET alertzone='true' WHERE id=$1");

    pg_execute($db, "change_zone_status", array($idUser));
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

  $query = pg_prepare($db, "insert_users", "INSERT INTO users(iddoctor,name,surname,username,password,phonenumber,address,status,alertzone,radius,stade,req_id) VALUES($1,$2,$3,$4,$5,$6,$7,$8,$9,$10,$11,$12);");

  pg_execute($db, "insert_users", array($newUser['iddoctor'],$newUser['name'], $newUser['surname'], $newUser['login'], sha1($newUser['password']), $newUser['phonenumber'], $newUser['address'],true,true,$newUser['radius'],$newUser['stage'],$newUser['req_id']));
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

function check_doctor_exist($username,$email){
  $db = db_connect();

  $query = pg_prepare($db, "check_doctor", "SELECT * FROM doctors WHERE username=$1 OR email=$2 LIMIT 1;");
  $res = pg_execute($db, "check_doctor", array($username,$email));

  $arr = pg_fetch_array($res);

  if(!empty($arr['username']) || !empty($arr['email'])){
    return true;
  }
  else {
    return false;
  }
}

function add_doctor($username,$surname,$name,$email,$password){
  $db = db_connect();

  $query = pg_prepare($db, "add_doctor", "INSERT INTO doctors(username,surname,name,email,password) VALUES($1,$2,$3,$4,$5)");
  pg_execute($db, "add_doctor", array($username,$surname,$name,$email, $password));

  $query = pg_prepare($db, "id_doctor", "SELECT id FROM doctors WHERE username=$1;");
  $res = pg_execute($db, "id_doctor", array($username));

  $arr = pg_fetch_array($res);

  return $arr['id'];
}

function getIdDoc($username){
  $db = db_connect();

  $query = pg_prepare($db, "get_id", "SELECT id FROM doctors WHERE username=$1;");

  $res = pg_execute($db, "get_id", array($username));

  $arr = pg_fetch_array($res);

  return $arr['id'];
}
