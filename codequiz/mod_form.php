<?php
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->libdir . '/filelib.php');

class mod_codequiz_mod_form extends moodleform_mod {

    public function definition() {
        $mform = $this->_form;

        // Activiteitsnaam
        $mform->addElement('text', 'name', get_string('name'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        // Introductie
        $this->standard_intro_elements();

        // Herhaalbare vragen
        $repeatarray = [];

        $repeatarray[] = $mform->createElement('text', 'vraagtext', 'Vraagtekst');
        $repeatarray[] = $mform->createElement('filemanager', 'mediaupload', 'Afbeelding (upload)', null, [
            'subdirs' => 0,
            'maxbytes' => 10485760,
            'maxfiles' => 1,
            'accepted_types' => ['image'],
            'return_types' => FILE_INTERNAL
        ]);
        $repeatarray[] = $mform->createElement('editor', 'mediahtml', 'Media (HTML, optioneel)');
        $repeatarray[] = $mform->createElement('selectyesno', 'crop', 'Media croppen?');
        $repeatarray[] = $mform->createElement('textarea', 'optiesjson', 'Opties (JSON)');

        $repeateloptions = [
            'vraagtext' => ['type' => PARAM_TEXT],
            'mediahtml' => ['type' => PARAM_RAW],
            'crop' => ['type' => PARAM_INT],
            'optiesjson' => ['type' => PARAM_RAW],
        ];

        $this->repeat_elements(
            $repeatarray,
            1,
            $repeateloptions,
            'vragen_repeats',
            'vragen_add_fields',
            1,
            get_string('addquestion', 'codequiz'),
            true
        );

        $this->standard_coursemodule_elements();
        $this->add_completion_rules();
        $this->add_action_buttons();
    }

    public function add_completion_rules() {
        $mform = $this->_form;

        $mform->addElement('checkbox', 'completionpass',
            get_string('completionpass_label', 'codequiz'),
            get_string('completionpass', 'codequiz')
        );
        $mform->addHelpButton('completionpass', 'completionpass', 'codequiz');

        return ['completionpass'];
    }

    public function completion_rule_enabled($data) {
        return !empty($data['completionpass']);
    }

    public function get_completion_rule_descriptions() {
        return [
            'completionpass' => get_string('completionpass', 'codequiz')
        ];
    }

    public function get_data() {
        $data = parent::get_data();
        if ($data) {
            $data->completionpass = !empty($data->completionpass) ? 1 : 0;
        }
        return $data;
    }

    public function data_preprocessing(&$defaultvalues) {
    global $DB;

    if ($this->is_add_repeat_elements()) {
        error_log('DEBUG: Nieuw herhaalveld toegevoegd — data_preprocessing() overslaan');
        return;
    }

    parent::data_preprocessing($defaultvalues);
    $defaultvalues['completionpass'] = $this->get_current_completionpass();

    if (empty($this->current) || empty($this->current->id)) {
        error_log("DEBUG: Geen bestaande instance ID — stoppen");
        return;
    }

    $context = $this->context;
    $questions = $DB->get_records('codequiz_questions', ['codequizid' => $this->current->id], 'sortorder ASC');

    $vraagtext = [];
    $mediahtml = [];
    $mediaupload = [];
    $crop = [];
    $optiesjson = [];

    $i = 0;
    foreach ($questions as $question) {
        $vraagtext[$i] = $question->vraag ?? '';

        $mediahtml[$i] = [
            'text' => $question->mediahtml ?? '',
            'format' => FORMAT_HTML
        ];

        $crop[$i] = isset($question->crop) ? (int)$question->crop : 1;

        $optiesjson[$i] = isset($question->opties) && is_string($question->opties)
            ? $question->opties
            : json_encode([], JSON_PRETTY_PRINT);

        $draftid = file_get_submitted_draft_itemid("mediaupload[{$i}]");
        file_prepare_draft_area(
            $draftid,
            $context->id,
            'mod_codequiz',
            'mediaupload',
            $this->current->id * 100 + $i,
            [
                'subdirs' => 0,
                'maxbytes' => 10485760,
                'maxfiles' => 1,
                'accepted_types' => ['image'],
                'return_types' => FILE_INTERNAL
            ]
        );
        $mediaupload[$i] = $draftid;

        $i++;
    }

    // 📌 Zet aantal herhalingen vóór je de velden vult
    $defaultvalues['vragen_repeats'] = $i;

    $defaultvalues['vraagtext'] = $vraagtext;
    $defaultvalues['mediahtml'] = $mediahtml;
    $defaultvalues['crop'] = $crop;
    $defaultvalues['optiesjson'] = $optiesjson;
    $defaultvalues['mediaupload'] = $mediaupload;

    // 🐞 Debug
    error_log("DEBUG: data_preprocessing — vragen_repeats = $i");
    error_log("DEBUG: vraag 1 = " . ($vraagtext[0] ?? 'niet gezet'));
    error_log("DEBUG: vraag 2 = " . ($vraagtext[1] ?? 'niet gezet'));
}


    /**
     * Detecteert of de gebruiker op "voeg extra vraag toe" heeft geklikt.
     */
    private function is_add_repeat_elements(): bool {
        return optional_param('vragen_add_fields', 0, PARAM_INT) > 0;
    }

    private function get_current_completionpass() {
        global $DB;

        if ($this->current && $this->current->id) {
            return $DB->get_field('codequiz', 'completionpass', ['id' => $this->current->id]);
        }
        return 0;
    }
}
