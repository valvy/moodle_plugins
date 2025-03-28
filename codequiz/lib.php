<?php
// ===== ./codequiz/lib.php =====

defined('MOODLE_INTERNAL') || die();

function codequiz_reset_user_completion($userid, $courseid, $instanceid) {
    global $DB;

    if ($cm = get_coursemodule_from_instance('codequiz', $instanceid, $courseid)) {
        $DB->delete_records('course_modules_completion', [
            'coursemoduleid' => $cm->id,
            'userid' => $userid
        ]);

        $completion = new completion_info(get_course($courseid));
        $completion->invalidatecache($cm->id, $userid);
    }
}

function codequiz_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return false; // GEEN GRADES
        default:
            return null;
    }
}

function codequiz_add_instance(stdClass $data, mod_codequiz_mod_form $mform = null) {
    global $DB;

    $data->timecreated = time();
    $data->timemodified = $data->timecreated;
    $data->completionpass = !empty($data->completionpass) ? 1 : 0;

    $id = $DB->insert_record('codequiz', $data);
    codequiz_save_questions($id, $data);
    return $id;
}

function codequiz_update_instance(stdClass $data, mod_codequiz_mod_form $mform = null) {
    global $DB;

    $data->timemodified = time();
    $data->id = $data->instance;
    $data->completionpass = !empty($data->completionpass) ? 1 : 0;

    $DB->update_record('codequiz', $data);
    codequiz_save_questions($data->id, $data);
    return true;
}

function codequiz_delete_instance($id) {
    global $DB;

    $DB->delete_records('codequiz_results', ['codequizid' => $id]);
    $DB->delete_records('codequiz_questions', ['codequizid' => $id]);
    return $DB->delete_records('codequiz', ['id' => $id]);
}

function codequiz_get_result($instanceid, $userid) {
    global $DB;
    return $DB->get_record('codequiz_results', [
        'codequizid' => $instanceid,
        'userid' => $userid
    ], '*', IGNORE_MULTIPLE);
}

function codequiz_get_completion_state($course, $cm, $userid, $type) {
    global $DB;

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

function codequiz_get_completion_rule_descriptions($cm) {
    global $DB;
    $instance = $DB->get_record('codequiz', ['id' => $cm->instance]);

    $descriptions = [];
    if (!empty($instance->completionpass)) {
        $descriptions[] = get_string('completionpass', 'codequiz');
    }
    return $descriptions;
}

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

/**
 * Sla de vragen op in de database.
 */
function codequiz_save_questions($quizid, $data) {
    global $DB;

    $DB->delete_records('codequiz_questions', ['codequizid' => $quizid]);

    if (!empty($data->vraagtext)) {
        foreach ($data->vraagtext as $index => $vraagtext) {
            $mediahtml = $data->mediahtml[$index]['text'] ?? '';
            $crop = isset($data->crop[$index]) ? (int)$data->crop[$index] : 1;
            $optiesjson = $data->optiesjson[$index] ?? '[]';

            $vraag = new stdClass();
            $vraag->codequizid = $quizid;
            $vraag->vraag = $vraagtext;
            $vraag->mediahtml = $mediahtml;
            $vraag->crop = $crop;
            $vraag->opties = $optiesjson;
            $vraag->sortorder = $index;

            $DB->insert_record('codequiz_questions', $vraag);
        }
    }
}