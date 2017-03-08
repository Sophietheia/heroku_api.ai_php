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

function getUsersListByDoctor($iddoc){
  $db = db_connect();

  $query = pg_prepare($db, "get_users", "SELECT * FROM users WHERE iddoctor=$1;");

  $res = pg_execute($db, "get_users", array($iddoc));

  $arr = pg_fetch_all($res);

  return json_encode($arr);
}

function addUser($newUser){
  $db = db_connect();

  $query = pg_prepare($db, "insert_users", "INSERT INTO users(iddoctor,name,surname,username,password,phonenumber,address) VALUES($1,$2,$3,$4,$5,$6,$7);");

  pg_execute($db, "insert_users", array($newUser['iddoctor'],$newUser['name'], $newUser['surname'], $newUser['login'], sha1($newUser['password']), $newUser['phonenumber'], $newUser['address']));
}
