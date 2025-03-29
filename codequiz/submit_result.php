<?php
require_once('../../config.php');
require_login();

global $DB, $USER, $CFG;

// Verkrijg de parameters uit de POST-request.
$courseid   = required_param('courseid', PARAM_INT);
$instanceid = required_param('instanceid', PARAM_INT);
$labels     = required_param('labels', PARAM_RAW);
$message    = required_param('message', PARAM_TEXT);
$answers    = required_param('answers', PARAM_RAW);

// Check eerst of er al een resultaat is.
$existing = $DB->get_record('codequiz_results', [
    'codequizid' => $instanceid,
    'userid' => $USER->id,
    'courseid' => $courseid
]);

$record = new stdClass();
$record->codequizid  = $instanceid;
$record->courseid    = $courseid;
$record->userid      = $USER->id;
$record->labels      = $labels;
$record->message     = $message;
$record->answers     = $answers;
$record->timecreated = time();

if ($existing) {
    // Update het bestaande resultaat
    $record->id = $existing->id;
    $DB->update_record('codequiz_results', $record);
    $insertid = $existing->id;
} else {
    // Maak een nieuw resultaat aan
    $insertid = $DB->insert_record('codequiz_results', $record);
}

// Completion update forceren
require_once($CFG->libdir.'/completionlib.php');

$course = get_course($courseid);
$cm = get_coursemodule_from_instance('codequiz', $instanceid, $courseid);

$completion = new completion_info($course);
$completion->update_state($cm, COMPLETION_COMPLETE, $USER->id);

echo json_encode(['status' => 'success', 'id' => $insertid]);
