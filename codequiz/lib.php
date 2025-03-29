<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Specificeer welke Moodle-features worden ondersteund.
 */
function codequiz_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO: return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_COMPLETION_HAS_RULES: return true;
        case FEATURE_GRADE_HAS_GRADE: return false;
        default: return null;
    }
}

/**
 * Voeg nieuwe instantie toe aan de database.
 */
function codequiz_add_instance(stdClass $data, mod_codequiz_mod_form $mform = null) {
    global $DB;

    $data->timecreated = time();
    $data->timemodified = $data->timecreated;
    $data->completionpass = !empty($data->completionpass) ? 1 : 0;

    // Thresholds als integers
    $data->threshold_aspiring = isset($data->threshold_aspiring) ? (int)$data->threshold_aspiring : 0;
    $data->threshold_skilled = isset($data->threshold_skilled) ? (int)$data->threshold_skilled : 3;
    $data->threshold_expert = isset($data->threshold_expert) ? (int)$data->threshold_expert : 6;

    // Berichten per niveau (editorvelden verwerken)
    $data->message_aspiring = $data->message_aspiring['text'] ?? '';
    $data->message_skilled = $data->message_skilled['text'] ?? '';
    $data->message_expert = $data->message_expert['text'] ?? '';

    $id = $DB->insert_record('codequiz', $data);
    codequiz_save_questions($id, $data);
    return $id;
}

/**
 * Update bestaande instantie.
 */
function codequiz_update_instance(stdClass $data, mod_codequiz_mod_form $mform = null) {
    global $DB;

    $data->timemodified = time();
    $data->id = $data->instance;
    $data->completionpass = !empty($data->completionpass) ? 1 : 0;

    // Thresholds als integers
    $data->threshold_aspiring = isset($data->threshold_aspiring) ? (int)$data->threshold_aspiring : 0;
    $data->threshold_skilled = isset($data->threshold_skilled) ? (int)$data->threshold_skilled : 3;
    $data->threshold_expert = isset($data->threshold_expert) ? (int)$data->threshold_expert : 6;

    // Berichten per niveau (editorvelden verwerken)
    $data->message_aspiring = $data->message_aspiring['text'] ?? '';
    $data->message_skilled = $data->message_skilled['text'] ?? '';
    $data->message_expert = $data->message_expert['text'] ?? '';

    $DB->update_record('codequiz', $data);
    codequiz_save_questions($data->id, $data);
    return true;
}

/**
 * Verwijder een codequiz-instantie.
 */
function codequiz_delete_instance($id) {
    global $DB;

    $DB->delete_records('codequiz_results', ['codequizid' => $id]);
    $DB->delete_records('codequiz_questions', ['codequizid' => $id]);
    return $DB->delete_records('codequiz', ['id' => $id]);
}

/**
 * Ophalen van resultaat van gebruiker.
 */
function codequiz_get_result($instanceid, $userid) {
    global $DB;
    return $DB->get_record('codequiz_results', [
        'codequizid' => $instanceid,
        'userid' => $userid
    ], '*', IGNORE_MULTIPLE);
}

/**
 * Completion: is deze activiteit voltooid?
 */
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

/**
 * Completion: omschrijving voor instelling.
 */
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
 * Sla de herhaalbare vragen op.
 */
function codequiz_save_questions($quizid, $data) {
    global $DB;

    $DB->delete_records('codequiz_questions', ['codequizid' => $quizid]);

    if (!empty($data->vraagtext)) {
        foreach ($data->vraagtext as $index => $vraagtext) {
            $mediahtml = $data->mediahtml[$index]['text'] ?? '';
            $crop = isset($data->crop[$index]) ? (int)$data->crop[$index] : 1;
            $optiesjson = $data->optiesjson[$index] ?? '[]';

            if (empty(trim($mediahtml)) && isset($data->mediaupload[$index])) {
                $draftitemid = $data->mediaupload[$index];
                $context = context_module::instance($data->coursemodule);

                file_save_draft_area_files(
                    $draftitemid,
                    $context->id,
                    'mod_codequiz',
                    'mediaupload',
                    $quizid * 100 + $index
                );

                $fs = get_file_storage();
                $files = $fs->get_area_files(
                    $context->id,
                    'mod_codequiz',
                    'mediaupload',
                    $quizid * 100 + $index,
                    '',
                    false
                );

                foreach ($files as $file) {
                    $url = moodle_url::make_pluginfile_url(
                        $file->get_contextid(),
                        'mod_codequiz',
                        'mediaupload',
                        $file->get_itemid(),
                        $file->get_filepath(),
                        $file->get_filename()
                    );
                    $mediahtml = '<img src="' . $url . '" alt="">';
                    break;
                }
            }

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


/**
 * Bestandsserver: pluginfile handler voor mediaupload.
 */
function codequiz_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    global $DB;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_course_login($course, true, $cm);

    if (!has_capability('mod/codequiz:view', $context)) {
        return false;
    }

    if ($filearea !== 'mediaupload') {
        return false;
    }

    $itemid = array_shift($args);
    $filepath = '/';
    $filename = array_pop($args);
    if (!empty($args)) {
        $filepath .= implode('/', $args) . '/';
    }

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'mod_codequiz', $filearea, $itemid, $filepath, $filename);

    if (!$file || $file->is_directory()) {
        return false;
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
}
