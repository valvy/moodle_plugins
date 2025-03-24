<?php
defined('MOODLE_INTERNAL') || die();

function codequiz_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_INTRO: return true;
        default: return null;
    }
}

function codequiz_add_instance(stdClass $data, mod_codequiz_mod_form $mform = null) {
    global $DB;
    $data->timecreated = time();
    $data->timemodified = $data->timecreated;
    return $DB->insert_record('codequiz', $data);
}

function codequiz_update_instance(stdClass $data, mod_codequiz_mod_form $mform = null) {
    global $DB;
    $data->timemodified = time();
    $data->id = $data->instance;
    return $DB->update_record('codequiz', $data);
}

function codequiz_delete_instance($id) {
    global $DB;
    return $DB->delete_records('codequiz', ['id' => $id]);
}

/**
 * Haal het meest recente quizresultaat op voor een bepaalde gebruiker en codequiz instance.
 *
 * @param int $instanceid Het id van de codequiz.
 * @param int $userid Het id van de gebruiker.
 * @return stdClass|false Het resultaatrecord of false als er niets gevonden is.
 */
function codequiz_get_result($instanceid, $userid) {
    global $DB;
    return $DB->get_record('codequiz_results', ['codequizid' => $instanceid, 'userid' => $userid], '*', IGNORE_MULTIPLE);
}