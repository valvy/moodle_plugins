<?php
defined('MOODLE_INTERNAL') || die();

function coder_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        default:
            return null;
    }
}

function coder_add_instance(stdClass $data, mod_coder_mod_form $mform = null) {
    global $DB;
    $data->timecreated = time();
    $data->timemodified = $data->timecreated;
    // Zorg dat er altijd een waarde is, zelfs als de docent niets invult.
    if (!isset($data->pythoncode)) {
        $data->pythoncode = '';
    }
    return $DB->insert_record('coder', $data);
}

function coder_update_instance(stdClass $data, mod_coder_mod_form $mform = null) {
    global $DB;
    $data->timemodified = time();
    $data->id = $data->instance;
    // Indien de pythoncode niet is gezet, sla een lege string op.
    if (!isset($data->pythoncode)) {
        $data->pythoncode = '';
    }
    return $DB->update_record('coder', $data);
}

function coder_delete_instance($id) {
    global $DB;
    return $DB->delete_records('coder', ['id' => $id]);
}
