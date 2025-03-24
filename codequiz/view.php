<?php
require_once('../../config.php');

$id = required_param('id', PARAM_INT);
[$course, $cm] = get_course_and_cm_from_cmid($id, 'codequiz');
require_login($course, true, $cm);

$PAGE->set_url('/mod/codequiz/view.php', ['id' => $id]);
$PAGE->set_title($cm->name);
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();

global $DB, $USER;
$instance = $DB->get_record('codequiz', ['id' => $cm->instance], '*', MUST_EXIST);

$bericht = format_text($instance->welkomstbericht, FORMAT_HTML);
$bericht = str_replace('{{naam}}', fullname($USER), $bericht);
echo $bericht;

echo $OUTPUT->footer();
