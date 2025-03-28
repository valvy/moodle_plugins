<?php
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/completionlib.php');

require_login();

header('Content-Type: application/json');

$id = required_param('id', PARAM_INT); // Course module ID
$cm = get_coursemodule_from_id('coder', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$context = context_module::instance($cm->id);

require_capability('mod/coder:view', $context);

// Verifieer sessie (CSRF bescherming)
if (!confirm_sesskey()) {
    echo json_encode([
        'success' => false,
        'error' => 'Sessie ongeldig'
    ]);
    exit;
}

// Probeer completion bij te werken
$completion = new completion_info($course);

if ($completion->is_enabled($cm)) {
    $completion->update_state($cm, COMPLETION_COMPLETE, $USER->id);
    echo json_encode(['success' => true]);
} else {
    // Geen crash, gewoon vriendelijk melden
    echo json_encode([
        'success' => false,
        'error' => 'Voltooiing is niet ingeschakeld voor deze activiteit.'
    ]);
}
exit;
