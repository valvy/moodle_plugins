<?php
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/course/moodleform_mod.php');

class mod_dashboard_mod_form extends moodleform_mod {
    function definition() {
        $mform = $this->_form;

        // Activiteitsnaam
        $mform->addElement('text', 'name', get_string('name'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        // Standaard intro veld
        $this->standard_intro_elements();

        // ✅ Rich text editor voor welkomstbericht
        $mform->addElement('editor', 'welkomstbericht_editor', 'Bericht aan gebruiker');
        $mform->setType('welkomstbericht_editor', PARAM_RAW);
        $mform->addRule('welkomstbericht_editor', null, 'required', null, 'client');

        // ✅ Verwijzing naar gekoppelde codequiz
        $mform->addElement('text', 'codequizid', 'CodeQuiz ID');
        $mform->setType('codequizid', PARAM_INT);
        $mform->addRule('codequizid', null, 'required', null, 'client');

        // ✅ Checkbox om opdrachten zonder label te tonen
        $mform->addElement('advcheckbox', 'toonalles', 'Toon opdrachten zonder label voor alle niveaus');
        $mform->setType('toonalles', PARAM_BOOL);

        // Standaard coursemodule elementen + save-knoppen
        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    // ✅ Zet editorwaarde correct bij openen/bewerken
    function data_preprocessing(&$default_values) {
        if (isset($default_values['welkomstbericht'])) {
            $default_values['welkomstbericht_editor']['text']   = $default_values['welkomstbericht'];
            $default_values['welkomstbericht_editor']['format'] = FORMAT_HTML;
        }
    }

    // ✅ Zet editorwaarde correct bij initialisatie
    function set_data($data) {
        if (isset($data->welkomstbericht)) {
            $data->welkomstbericht_editor = [
                'text' => $data->welkomstbericht,
                'format' => FORMAT_HTML
            ];
        }
        parent::set_data($data);
    }
}
