<?php
// ===== ./codequiz/mod_form.php =====

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/course/moodleform_mod.php');

class mod_codequiz_mod_form extends moodleform_mod {
    public function definition() {
        $mform = $this->_form;

        // Activiteitsnaam
        $mform->addElement('text', 'name', get_string('name'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        // Introductie (standaard Moodle)
        $this->standard_intro_elements();

        // Welkomstbericht
        $mform->addElement('textarea', 'welkomstbericht', get_string('welkomstbericht', 'codequiz'), 'wrap="virtual" rows="5" cols="50"');
        $mform->setType('welkomstbericht', PARAM_RAW);
        $mform->addRule('welkomstbericht', get_string('required'), 'required', null, 'client');

        // Voeg configureerbare vragen toe via repeat_elements
        $repeatarray = [];
        $repeatarray[] = $mform->createElement('text', 'vraagtext', 'Vraagtekst');
        $repeatarray[] = $mform->createElement('editor', 'mediahtml', 'Media (HTML, optioneel)');
        $repeatarray[] = $mform->createElement('selectyesno', 'crop', 'Media croppen?');
        $repeatarray[] = $mform->createElement('textarea', 'optiesjson', 'Opties (JSON)');

        $repeatno = 3;
        $repeateloptions = [
            'vraagtext' => ['type' => PARAM_TEXT, 'default' => 'Vul hier je vraag in...'],
            'mediahtml' => ['type' => PARAM_RAW, 'default' => ['text' => '<img src=\"https://example.com/voorbeeld.jpg\" alt=\"Voorbeeld\">', 'format' => FORMAT_HTML]],
            'crop' => ['default' => 1],
            'optiesjson' => ['type' => PARAM_RAW, 'default' => json_encode([
                ["text" => "Ja", "value" => 1],
                ["text" => "Nee", "value" => 0]
            ], JSON_PRETTY_PRINT)],
        ];

        $this->repeat_elements($repeatarray, $repeatno, $repeateloptions, 'vragen_repeats', 'vragen_add_fields', 1, null, true);

        // Standaard coursemodule settings
        $this->standard_coursemodule_elements();

        // Completion rules
        $this->add_completion_rules();

        // Opslaan-knoppen
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
        parent::data_preprocessing($defaultvalues);

        $defaultvalues['completionpass'] = $this->get_current_completionpass();
    }

    private function get_current_completionpass() {
        global $DB;

        if ($this->current && $this->current->id) {
            return $DB->get_field('codequiz', 'completionpass', ['id' => $this->current->id]);
        }
        return 0;
    }
}
