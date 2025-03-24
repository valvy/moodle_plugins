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
