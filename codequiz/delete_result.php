<?php
require_once('../../config.php');
require_login();

global $DB, $USER, $CFG;

$courseid = required_param('courseid', PARAM_INT);
$instanceid = required_param('instanceid', PARAM_INT);

// Verwijder resultaten
$DB->delete_records('codequiz_results', [
    'codequizid' => $instanceid,
    'userid' => $USER->id,
    'courseid' => $courseid
]);

// Directe completion reset
require_once($CFG->libdir.'/completionlib.php');

if ($cm = get_coursemodule_from_instance('codequiz', $instanceid, $courseid)) {
    $completion = new completion_info(get_course($courseid));

    // Forceer reset van de status
    $completion->delete_all_state($cm, $USER->id);

    // Alternative: Directe database update
    $DB->delete_records('course_modules_completion', [
        'coursemoduleid' => $cm->id,
        'userid' => $USER->id
    ]);
}

// Cache leegmaken
purge_all_caches();

echo json_encode(['status' => 'success']);