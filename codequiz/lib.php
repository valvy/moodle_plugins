<?php
defined('MOODLE_INTERNAL') || die();

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

    // 1. Check of de module de rule gebruikt
    $instance = $DB->get_record('codequiz', ['id' => $cm->instance]);
    if (empty($instance->completionpass)) {
        return false;  // 🚨 Regel is niet geactiveerd
    }

    // 2. Check of de gebruiker een resultaat heeft
    return $DB->record_exists('codequiz_results', [
        'codequizid' => $cm->instance,
        'userid' => $userid
    ]);
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

    // 🚨 Directe toewijzing aan customcompletionrules ipv customdata
    if (!empty($instance->completionpass)) {
        $info->customcompletionrules = ['completionpass' => 1];
    }

    return $info;
}