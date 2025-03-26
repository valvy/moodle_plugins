<?php
defined('MOODLE_INTERNAL') || die();

function coder_supports($feature) {
    switch ($feature) {
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

    // Extract editor data
    $data->welkomstbericht = $data->welkomstbericht['text'] ?? '';
    $data->welkomstbericht_format = $data->welkomstbericht['format'] ?? FORMAT_HTML;

    // Defaults voor andere velden
    $data->pythoncode        = $data->pythoncode ?? '';
    $data->showexpert        = $data->showexpert ?? 0;
    $data->showskilled       = $data->showskilled ?? 1;
    $data->showaspiring      = $data->showaspiring ?? 1;
    $data->applicatie_naam   = $data->applicatie_naam ?? 'Opdracht1';
    $data->pagetitle         = $data->pagetitle ?? 'Forensische ICT Opdracht';
    $data->submissionurl     = $data->submissionurl ?? 'https://app.codegra.de/';
    $data->outputexample     = $data->outputexample ?? '';

    $id = $DB->insert_record('coder', $data);

    // Sla headerafbeelding op
    if (!empty($data->headerimage)) {
        $context = context_module::instance($data->coursemodule);
        file_save_draft_area_files(
            $data->headerimage,
            $context->id,
            'mod_coder',
            'headerimage',
            $id,
            ['subdirs' => 0, 'maxfiles' => 1, 'accepted_types' => ['image']]
        );
    }

    return $id;
}

function coder_update_instance(stdClass $data, mod_coder_mod_form $mform = null) {
    global $DB;

    $data->timemodified = time();
    $data->id = $data->instance;

    // Extract editor data
    $data->welkomstbericht = $data->welkomstbericht['text'] ?? '';
    $data->welkomstbericht_format = $data->welkomstbericht['format'] ?? FORMAT_HTML;

    // Defaults voor andere velden
    $data->pythoncode        = $data->pythoncode ?? '';
    $data->showexpert        = $data->showexpert ?? 0;
    $data->showskilled       = $data->showskilled ?? 1;
    $data->showaspiring      = $data->showaspiring ?? 1;
    $data->applicatie_naam   = $data->applicatie_naam ?? 'Opdracht1';
    $data->pagetitle         = $data->pagetitle ?? 'Forensische ICT Opdracht';
    $data->submissionurl     = $data->submissionurl ?? 'https://app.codegra.de/';
    $data->outputexample     = $data->outputexample ?? '';

    $DB->update_record('coder', $data);

    // Sla headerafbeelding op
    if (!empty($data->headerimage)) {
        $context = context_module::instance($data->coursemodule);
        file_save_draft_area_files(
            $data->headerimage,
            $context->id,
            'mod_coder',
            'headerimage',
            $data->id,
            ['subdirs' => 0, 'maxfiles' => 1, 'accepted_types' => ['image']]
        );
    }

    return true;
}

function coder_delete_instance($id) {
    global $DB;
    return $DB->delete_records('coder', ['id' => $id]);
}

function coder_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_login($course, true, $cm);

    if ($filearea === 'headerimage') {
        $itemid = array_shift($args);
        $filepath = '/' . implode('/', array_slice($args, 0, -1)) . '/';
        $filename = end($args);

        $fs = get_file_storage();
        $file = $fs->get_file($context->id, 'mod_coder', 'headerimage', $itemid, $filepath, $filename);
        if (!$file || $file->is_directory()) {
            return false;
        }

        send_stored_file($file, 0, 0, $forcedownload, $options);
    }

    return false;
}
