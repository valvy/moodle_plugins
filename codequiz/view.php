<?php
// ===== ./codequiz/view.php =====

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
$stored_result = codequiz_get_result($cm->instance, $USER->id);
if ($stored_result) {
    $stored_result_data = [
        "labels" => json_decode($stored_result->labels, true),
        "message" => $stored_result->message
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

echo $OUTPUT->header();
?>

<script>
  window.codequizConfig = {
    courseid: <?php echo $course->id; ?>,
    instanceid: <?php echo $cm->instance; ?>,
    storedResult: <?php echo json_encode($stored_result_data); ?>,
    vraagSchermen: <?php echo json_encode($vraagSchermen); ?>
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
