<?php
defined('MOODLE_INTERNAL') || die();

function codequiz_reset_user_completion($userid, $courseid, $instanceid) {
    global $DB;

    if ($cm = get_coursemodule_from_instance('codequiz', $instanceid, $courseid)) {
        $DB->delete_records('course_modules_completion', [
            'coursemoduleid' => $cm->id,
            'userid' => $userid
        ]);

        // Forceer recomputatie
        $completion = new completion_info(get_course($courseid));
        $completion->invalidatecache($cm->id, $userid);
    }
}

/**
 * Welke Moodle features deze module ondersteunt.
 */
function codequiz_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        default:
            return null;
    }
}

/**
 * Toevoegen van een nieuwe activiteit.
 */
function codequiz_add_instance(stdClass $data, mod_codequiz_mod_form $mform = null) {
    global $DB;

    $data->timecreated = time();
    $data->timemodified = $data->timecreated;
    $data->completionpass = !empty($data->completionpass) ? 1 : 0;

    return $DB->insert_record('codequiz', $data);
}

/**
 * Bijwerken van een bestaande activiteit.
 */
function codequiz_update_instance(stdClass $data, mod_codequiz_mod_form $mform = null) {
    global $DB;

    $data->timemodified = time();
    $data->id = $data->instance;
    $data->completionpass = !empty($data->completionpass) ? 1 : 0;

    return $DB->update_record('codequiz', $data);
}

/**
 * Verwijderen van een activiteit en bijbehorende resultaten.
 */
function codequiz_delete_instance($id) {
    global $DB;

    $DB->delete_records('codequiz_results', ['codequizid' => $id]);
    return $DB->delete_records('codequiz', ['id' => $id]);
}

/**
 * Ophalen van eerder opgeslagen resultaat.
 */
function codequiz_get_result($instanceid, $userid) {
    global $DB;
    return $DB->get_record('codequiz_results', [
        'codequizid' => $instanceid,
        'userid' => $userid
    ], '*', IGNORE_MULTIPLE);
}

function codequiz_get_completion_state($course, $cm, $userid, $type) {
    global $DB;

    // Directe database check zonder instance lookup
    return $DB->record_exists_sql(
        "SELECT 1
         FROM {codequiz_results} cr
         JOIN {codequiz} c ON c.id = cr.codequizid
         WHERE cr.userid = :userid
           AND c.id = :instanceid
           AND c.completionpass = 1",
        [
            'userid' => $userid,
            'instanceid' => $cm->instance
        ]
    );
}

// Update de get_completion_rule_descriptions functie:
function codequiz_get_completion_rule_descriptions($cm) {
    global $DB;
    $instance = $DB->get_record('codequiz', ['id' => $cm->instance]);

    $descriptions = [];
    if (!empty($instance->completionpass)) {
        $descriptions[] = get_string('completionpass', 'codequiz');
    }
    return $descriptions;
}

/**
 * Controle of de regel geactiveerd is in het formulier.
 */
function codequiz_completion_rule_enabled($data) {
    return !empty($data->completionpass);
}


function codequiz_get_coursemodule_info($coursemodule) {
    global $DB;
    $instance = $DB->get_record('codequiz', ['id' => $coursemodule->instance]);

    $info = new cached_cm_info();
    $info->customcompletionrules = ['completionpass' => $instance->completionpass];
    return $info;
}