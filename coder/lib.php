<?php
defined('MOODLE_INTERNAL') || die();

function coder_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_INTRO: return true;
        default: return null;
    }
}

function coder_add_instance(stdClass $data, mod_coder_mod_form $mform = null) {
    global $DB;
    $data->timecreated = time();
    $data->timemodified = $data->timecreated;
    return $DB->insert_record('coder', $data);
}

function coder_update_instance(stdClass $data, mod_coder_mod_form $mform = null) {
    global $DB;
    $data->timemodified = time();
    $data->id = $data->instance;
    return $DB->update_record('coder', $data);
}

function coder_delete_instance($id) {
    global $DB;
    return $DB->delete_records('coder', ['id' => $id]);
}
