<?php
  function get_name_from_relation($surname, $id){
    $db = db_connect();

    $query = pg_prepare($db, "surname_name", "SELECT name FROM relations WHERE surname=$1 AND id_user=$2");
		$result = pg_execute($db, "surname_name", array($surname, $id));

		$name = pg_fetch_row($result);

		return $name[0];
  }

  function get_id_person($user_id){
    $db = db_connect();

    $query = pg_prepare($db, "id", "SELECT id FROM users WHERE req_id=$1");
		$result = pg_execute($db, "id", array($user_id));

    $id = pg_fetch_row($result);

    return $id[0];
  }

  function add_rdv($label, $location, $date_meeting, $time, $id, $id_perso){
    $db = db_connect();

    $query = "INSERT INTO meetings(label, location, date_meeting, time_meeting, id_user, id_person) VALUES('$label', '$location', '$date_meeting', '$time', '".$id."', '$id_perso');";

    pg_query($db, $query);
  }

  function location_meeting($id){
    $db = db_connect();

    $today=date("Y-m-d");
		$query = pg_prepare($db, "location_meeting", "SELECT label, location FROM meetings WHERE id_user=$1 AND date_meeting>=$2 GROUP BY label, id HAVING date_meeting=MIN(date_meeting);");

		$result = pg_execute($db, "location_meeting", array($id,$today));

		$meeting = pg_fetch_row($result);

    return $meeting;
  }

  function date_meeting($id){
    $db = db_connect();

    $today=date("Y-m-d");
		$query = pg_prepare($db, "date_meeting", "SELECT label, date_meeting FROM meetings WHERE id_user=$1 AND date_meeting>=$2 GROUP BY label, id HAVING date_meeting=MIN(date_meeting);");

		$result = pg_execute($db, "date_meeting", array($id,$today));

		$meeting = pg_fetch_row($result);

    return $meeting;
  }

  function with_who_meeting($id){
    $db = db_connect();

    $today=date("Y-m-d");
    $query = pg_prepare($db, "with_meeting", "SELECT meetings.label AS label, relations.name AS name, relations.surname AS surname FROM meetings, relations WHERE meetings.id_user=$1 AND meetings.date_meeting>='$today' AND meetings.id_person=relations.id GROUP BY meetings.label, meetings.id, relations.id HAVING meetings.date_meeting=MIN(meetings.date_meeting);
");

		$result = pg_execute($db, "with_meeting", array($id));

		$meeting = pg_fetch_row($result);

    return $meeting;
  }

  function label_meeting($id){
    $db = db_connect();

    $today=date("Y-m-d");
    $query = pg_prepare($db, "what_meeting", "SELECT label FROM meetings WHERE id_user=$1 AND date_meeting>='$today' GROUP BY label, id HAVING date_meeting=MIN(date_meeting);");

		$result = pg_execute($db, "what_meeting", array($id));

		$meeting = pg_fetch_row($result);

    return $meeting;
  }

?>
