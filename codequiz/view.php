<?php

require_once('../../config.php');
require_once($CFG->dirroot.'/mod/codequiz/lib.php');

$id = required_param('id', PARAM_INT);
[$course, $cm] = get_course_and_cm_from_cmid($id, 'codequiz');
require_login($course, true, $cm);

$PAGE->set_url('/mod/codequiz/view.php', ['id' => $id]);
$PAGE->set_title($cm->name);
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('incourse');

global $DB, $USER;
$instance = $DB->get_record('codequiz', ['id' => $cm->instance], '*', MUST_EXIST);

// Belangrijk: Haal hier het resultaat op uit de database
$stored_result = codequiz_get_result($cm->instance, $USER->id);
if ($stored_result) {
    $stored_result_data = [
        "labels" => json_decode($stored_result->labels, true),
        "message" => $stored_result->message,
        "answers" => json_decode($stored_result->answers, true)
    ];
} else {
    $stored_result_data = null;
}

$questions = $DB->get_records('codequiz_questions', ['codequizid' => $cm->instance], 'sortorder ASC');
$vraagSchermen = [];
foreach ($questions as $q) {
    $vraagSchermen[] = [
        'vraag' => $q->vraag,
        'mediaHTML' => $q->mediahtml,
        'crop' => (bool)$q->crop,
        'opties' => json_decode($q->opties, true)
    ];
}

// Thresholds ophalen (als integers)
$labelthresholds = [
    'aspiring' => (int)$instance->threshold_aspiring,
    'skilled' => (int)$instance->threshold_skilled,
    'expert' => (int)$instance->threshold_expert
];

// Berichten ophalen
$messages = [
    'aspiring' => $instance->message_aspiring,
    'skilled' => $instance->message_skilled,
    'expert' => $instance->message_expert
];

// Voornaam gebruiker ophalen
$userfirstname = ucfirst(strtolower($USER->firstname));

echo $OUTPUT->header();

$context = context_module::instance($cm->id);
if (has_capability('mod/codequiz:managedashboard', $context)) {
    $dashboardurl = new moodle_url('/mod/codequiz/dashboard.php', ['courseid' => $course->id, 'instanceid' => $cm->instance]);
    echo html_writer::tag('div', html_writer::link($dashboardurl, get_string('dashboard', 'codequiz'), ['class' => 'dashboard-link']), ['style' => 'margin-bottom: 20px;']);

    echo html_writer::div("Gekoppelde CodeQuiz ID: <strong>{$cm->instance}</strong>");
}
?>

<script>
  window.codequizConfig = {
    courseid: <?php echo $course->id; ?>,
    instanceid: <?php echo $cm->instance; ?>,
    storedResult: <?php echo json_encode($stored_result_data); ?>,
    vraagSchermen: <?php echo json_encode($vraagSchermen); ?>,
    labelThresholds: <?php echo json_encode($labelthresholds); ?>,
    messages: <?php echo json_encode($messages); ?>,
    userFirstName: <?php echo json_encode($userfirstname); ?>
  };
</script>

<link rel="stylesheet" href="<?php echo $CFG->wwwroot; ?>/mod/codequiz/styles.css">

<div class="codequiz-wrapper">
  <div id="quiz-container">
    <div class="nav-buttons">
      <button id="prevBtn">Vorige</button>
      <button id="nextBtn" disabled>Volgende</button>
    </div>
  </div>
</div>

<script src="script.js"></script>

<?php
echo $OUTPUT->footer();
