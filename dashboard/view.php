<?php
require_once('../../config.php');

$id = required_param('id', PARAM_INT);
[$course, $cm] = get_course_and_cm_from_cmid($id, 'dashboard');
require_login($course, true, $cm);

$PAGE->set_url('/mod/dashboard/view.php', ['id' => $id]);
$PAGE->set_title($cm->name);
$PAGE->set_heading($course->fullname);

// Load optional CSS
$PAGE->requires->css(new moodle_url('/mod/dashboard/style.css'));

echo $OUTPUT->header();

global $DB, $USER;

// Haal dashboard instance op
$instance = $DB->get_record('dashboard', ['id' => $cm->instance], '*', MUST_EXIST);

// Welkomstbericht personaliseren
$bericht = format_text($instance->welkomstbericht, FORMAT_HTML);
$bericht = str_replace('{{naam}}', fullname($USER), $bericht);
echo $bericht;

// Haal quizresultaten op (optioneel)
if (!empty($instance->codequizid)) {
    $result = $DB->get_record('codequiz_results', [
        'codequizid' => $instance->codequizid,
        'userid' => $USER->id
    ]);

    $quiz = $DB->get_record('codequiz', ['id' => $instance->codequizid]);
    echo "<div style='background:#eee; padding:1em; border:1px solid #ccc;'>";
    echo "Debug info:<br>";
    echo "codequizid uit dashboard: <strong>{$instance->codequizid}</strong><br>";
    echo "userid van huidige gebruiker: <strong>{$USER->id}</strong><br>";
    echo "</div>";
    if ($result && $quiz) {
        // Labels tellen
        $labels = explode(',', $result->labels);
        $niveau = 'Onbekend';

        if (count($labels) >= $quiz->threshold_expert) {
            $niveau = 'Expert Developer';
        } else if (count($labels) >= $quiz->threshold_skilled) {
            $niveau = 'Skilled Developer';
        } else {
            $niveau = 'Aspiring Developer';
        }

        echo html_writer::tag('div', "Jouw niveau: <strong>$niveau</strong>", ['class' => 'user-level']);
    } else {
        echo html_writer::tag('div', 'Geen resultaat gevonden voor de gekoppelde quiz.', ['class' => 'user-level']);
    }
}

echo $OUTPUT->footer();
