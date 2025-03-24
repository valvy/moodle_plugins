<?php
require_once('../../config.php');

$id = required_param('id', PARAM_INT);
[$course, $cm] = get_course_and_cm_from_cmid($id, 'learninggoals');
require_login($course, true, $cm);

$PAGE->set_url('/mod/learninggoals/view.php', ['id' => $id]);
$PAGE->set_title($cm->name);
$PAGE->set_heading($course->fullname);

// Load external CSS file (optional)
$PAGE->requires->css(new moodle_url('/mod/learninggoals/style.css'));

echo $OUTPUT->header();

global $DB, $USER;
$instance = $DB->get_record('learninggoals', ['id' => $cm->instance], '*', MUST_EXIST);

$bericht = format_text($instance->welkomstbericht, FORMAT_HTML);
$bericht = str_replace('{{naam}}', fullname($USER), $bericht);
echo $bericht;

echo $OUTPUT->footer();
