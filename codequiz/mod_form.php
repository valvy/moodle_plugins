<?php
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

        // Standaard coursemodule settings
        $this->standard_coursemodule_elements();

        // Opslaan-knoppen
        $this->add_action_buttons();
    }

    /**
     * ✅ Completionregel wordt hier correct geregistreerd én aangemaakt.
     */
    public function add_completion_rules() {
        $mform = $this->_form;

        $mform->addElement('advcheckbox', 'completionpass', get_string('completionpass', 'codequiz'), '', [0, 1]);
        $mform->setDefault('completionpass', 1);
        $mform->addHelpButton('completionpass', 'completionpass', 'codequiz');

        return ['completionpass'];
    }

    /**
     * ✅ Moodle checkt hiermee of de regel actief is.
     */
    public function completion_rule_enabled($data) {
        return !empty($data->completionpass);
    }

    /**
     * ✅ UI-label voor de regel in het formulier.
     */
    public function get_completion_rule_descriptions() {
        return [
            'completionpass' => get_string('completionpass', 'codequiz')
        ];
    }

    /**
     * ✅ Verbindt de formwaarde aan de internal completion engine.
     */
    public function get_data() {
        $data = parent::get_data();

        if ($data) {
            $data->customcompletionrules = [
                'completionpass' => !empty($data->completionpass)
            ];
        }

        return $data;
    }
}
