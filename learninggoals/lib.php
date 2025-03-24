<?php
defined('MOODLE_INTERNAL') || die();

function learninggoals_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_INTRO: return true;
        default: return null;
    }
}

function learninggoals_add_instance(stdClass $data, mod_learninggoals_mod_form $mform = null) {
    global $DB;
    $data->timecreated = time();
    $data->timemodified = $data->timecreated;
    return $DB->insert_record('learninggoals', $data);
}

function learninggoals_update_instance(stdClass $data, mod_learninggoals_mod_form $mform = null) {
    global $DB;
    $data->timemodified = time();
    $data->id = $data->instance;
    return $DB->update_record('learninggoals', $data);
}

function learninggoals_delete_instance($id) {
    global $DB;
    return $DB->delete_records('learninggoals', ['id' => $id]);
}
