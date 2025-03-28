<?php
require_once('../../config.php');

$courseid   = required_param('courseid', PARAM_INT);
$instanceid = required_param('instanceid', PARAM_INT);

if (!$cm = get_coursemodule_from_instance('codequiz', $instanceid, $courseid)) {
    print_error('invalidcoursemodule');
}
$course = get_course($courseid);
require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/codequiz:managedashboard', $context);

$PAGE->set_url('/mod/codequiz/dashboard.php', ['courseid' => $courseid, 'instanceid' => $instanceid]);
$PAGE->set_title('Code Quiz Dashboard');
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('incourse');

// Haal alle resultaten op voor deze codequiz
$results = $DB->get_records('codequiz_results', ['codequizid' => $instanceid]);
$totalSubmissions = count($results);

echo $OUTPUT->header();
?>

<style>
.dashboard-wrapper {
    padding: 20px;
}
.dashboard-table {
    width: 100%;
    border-collapse: collapse;
}
.dashboard-table th, .dashboard-table td {
    border: 1px solid #ccc;
    padding: 8px;
    text-align: left;
}
.dashboard-table th {
    background-color: #f5f5f5;
}
</style>

<div class="dashboard-wrapper">
    <h2>Code Quiz Dashboard</h2>
    <p>Totaal aantal inzendingen: <?php echo $totalSubmissions; ?></p>

    <?php if ($totalSubmissions > 0): ?>
        <table class="dashboard-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Gebruikersnaam</th>
                    <th>Label</th>
                    <th>Tijd van inzending</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $result):
                    $user = $DB->get_record('user', ['id' => $result->userid]);
                    $fullname = fullname($user);
                    $decodedLabel = json_decode($result->labels, true);
                    if (is_array($decodedLabel)) {
                        $labelText = implode(", ", $decodedLabel);
                    } else {
                        $labelText = $result->labels;
                    }
                ?>
                    <tr>
                        <td><?php echo $result->id; ?></td>
                        <td><?php echo $fullname; ?></td>
                        <td><?php echo $labelText; ?></td>
                        <td><?php echo date('Y-m-d H:i:s', $result->timecreated); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Geen inzendingen gevonden.</p>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Dashboard geladen');
    // Voeg hier extra JavaScript toe voor interactiviteit of verdere functionaliteit
});
</script>

<?php
echo $OUTPUT->footer();
