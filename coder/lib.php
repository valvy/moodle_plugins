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

    // Zorg dat alle velden een waarde hebben.
    $data->pythoncode     = isset($data->pythoncode) ? $data->pythoncode : '';
    $data->showexpert     = isset($data->showexpert) ? $data->showexpert : 0;
    $data->showskilled    = isset($data->showskilled) ? $data->showskilled : 1;
    $data->showaspiring   = isset($data->showaspiring) ? $data->showaspiring : 1;
    $data->applicatie_naam= isset($data->applicatie_naam) ? $data->applicatie_naam : 'Opdracht1';
    $data->pagetitle      = isset($data->pagetitle) ? $data->pagetitle : 'Forensische ICT Opdracht';
    $data->submissionurl  = isset($data->submissionurl) ? $data->submissionurl : 'https://app.codegra.de/';
    $data->outputexample  = isset($data->outputexample) ? $data->outputexample : '<div>Welk woordje wil je versleutelen? <strong><em>hallo</em></strong><br>Welke sleutel wil je gebruiken? <strong><em>3</em></strong><br>De versleuteling van pizza is kdoor</div>';

    return $DB->insert_record('coder', $data);
}

function coder_update_instance(stdClass $data, mod_coder_mod_form $mform = null) {
    global $DB;
    $data->timemodified = time();
    $data->id = $data->instance;

    $data->pythoncode     = isset($data->pythoncode) ? $data->pythoncode : '';
    $data->showexpert     = isset($data->showexpert) ? $data->showexpert : 0;
    $data->showskilled    = isset($data->showskilled) ? $data->showskilled : 1;
    $data->showaspiring   = isset($data->showaspiring) ? $data->showaspiring : 1;
    $data->applicatie_naam= isset($data->applicatie_naam) ? $data->applicatie_naam : 'Opdracht1';
    $data->pagetitle      = isset($data->pagetitle) ? $data->pagetitle : 'Forensische ICT Opdracht';
    $data->submissionurl  = isset($data->submissionurl) ? $data->submissionurl : 'https://app.codegra.de/';
    $data->outputexample  = isset($data->outputexample) ? $data->outputexample : '<div>Welk woordje wil je versleutelen? <strong><em>hallo</em></strong><br>Welke sleutel wil je gebruiken? <strong><em>3</em></strong><br>De versleuteling van pizza is kdoor</div>';

    return $DB->update_record('coder', $data);
}

function coder_delete_instance($id) {
    global $DB;
    return $DB->delete_records('coder', ['id' => $id]);
}
