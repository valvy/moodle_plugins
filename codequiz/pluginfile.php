<?php
// ===== ./mod/codequiz/pluginfile.php =====
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../config.php');
require_login();

function codequiz_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    global $DB;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    // Controleer of de gebruiker toegang heeft
    require_course_login($course, true, $cm);

    if (!has_capability('mod/codequiz:view', $context)) {
        return false;
    }

    // Alleen bestanden in 'mediaupload' worden geserveerd
    if ($filearea !== 'mediaupload') {
        return false;
    }

    // Parameters uit de $args array
    // args = [itemid, [path1, path2, ...,] filename]
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

    // Lever bestand op
    send_stored_file($file, 0, 0, $forcedownload, $options);
}
