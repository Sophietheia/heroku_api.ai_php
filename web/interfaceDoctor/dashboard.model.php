<?php

function addNewRdv($rdv){
  $db = db_connect();

  echo '<h3>'.$rdv['label'].'</h3>';

  $query = pg_prepare($db, "add_rdv", "INSERT INTO meetings(label, location, date_meeting, time_meeting, id_user, id_person) VALUES($1, $2, $3, $4, $5, $6);");
  pg_execute($db, "add_rdv", array($rdv['label'], $rdv['location'], $rdv['date'], $rdv['time'], $rdv['idPatient'], $rdv['idDoc']));
}
