<?php
require_once('../../config.php');
require_login();

global $DB, $USER;

// Verkrijg de parameters uit de POST-request.
$courseid   = required_param('courseid', PARAM_INT);
$instanceid = required_param('instanceid', PARAM_INT);
$labels     = required_param('labels', PARAM_RAW);  // Verwacht een JSON-string
$message    = required_param('message', PARAM_TEXT);

// Bouw het record dat in de database komt.
$record = new stdClass();
$record->codequizid  = $instanceid;
$record->courseid    = $courseid;
$record->userid      = $USER->id;
$record->labels      = $labels;
$record->message     = $message;
$record->timecreated = time();

// Sla het resultaat op in de tabel.
$insertid = $DB->insert_record('codequiz_results', $record);

// Geef een JSON-response terug.
echo json_encode(['status' => 'success', 'id' => $insertid]);
