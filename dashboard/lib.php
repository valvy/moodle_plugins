<?php
defined('MOODLE_INTERNAL') || die();

function dashboard_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_INTRO: return true;
        default: return null;
    }
}

function dashboard_add_instance(stdClass $data, mod_dashboard_mod_form $mform = null) {
    global $DB;
    $data->timecreated = time();
    $data->timemodified = $data->timecreated;
    return $DB->insert_record('dashboard', $data);
}

function dashboard_update_instance(stdClass $data, mod_dashboard_mod_form $mform = null) {
    global $DB;
    $data->timemodified = time();
    $data->id = $data->instance;
    return $DB->update_record('dashboard', $data);
}

function dashboard_delete_instance($id) {
    global $DB;
    return $DB->delete_records('dashboard', ['id' => $id]);
}
