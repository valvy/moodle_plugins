<?php
require_once('../../config.php');
require_login();

global $DB, $USER;

$courseid   = required_param('courseid', PARAM_INT);
$instanceid = required_param('instanceid', PARAM_INT);

// Verwijder alle resultaten voor deze gebruiker en codequiz
$DB->delete_records('codequiz_results', ['codequizid' => $instanceid, 'userid' => $USER->id]);

echo json_encode(['status' => 'success']);
