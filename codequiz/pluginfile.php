require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/codequiz/lib.php');

$contextid = required_param('contextid', PARAM_INT);
$filearea = required_param('filearea', PARAM_ALPHAEXT);
$itemid = required_param('itemid', PARAM_INT);
$filepath = required_param('filepath', PARAM_PATH);
$filename = required_param('filename', PARAM_FILE);

$context = context::instance_by_id($contextid);
$cm = get_coursemodule_from_id('codequiz', $context->instanceid, 0, false, MUST_EXIST);
$course = get_course($cm->course);

require_login($course, true, $cm);

codequiz_pluginfile($course, $cm, $context, $filearea, [$itemid, trim($filepath, '/'), $filename], false);
